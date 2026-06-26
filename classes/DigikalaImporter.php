<?php
class DigikalaImporter
{
    public static function extractDkp(string $input): int
    {
        $input = trim($input);

        if (preg_match('/dkp-(\d+)/i', $input, $m)) {
            return (int)$m[1];
        }

        if (preg_match('/\b(\d{5,})\b/', $input, $m)) {
            return (int)$m[1];
        }

        return 0;
    }

    public static function fetchProduct(int $dkp): array
    {
        if ($dkp <= 0) {
            throw new RuntimeException('لینک یا کد DKP معتبر نیست.');
        }

        $url = "https://api.digikala.com/v2/product/{$dkp}/";
        $json = self::httpGetJson($url);

        $product = $json['data']['product'] ?? null;
        if (!$product) {
            throw new RuntimeException('اطلاعات محصول از دیجی‌کالا دریافت نشد.');
        }

        return self::normalizeProduct($product, $dkp);
    }

    private static function httpGetJson(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (compatible; VaL3RImporter/1.0; +https://val3r.ir)',
            ],
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new RuntimeException('خطا در اتصال به دیجی‌کالا: ' . $error);
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('دیجی‌کالا پاسخ نامعتبر داد. کد وضعیت: ' . $status);
        }

        $json = json_decode((string)$body, true);
        if (!is_array($json)) {
            throw new RuntimeException('پاسخ دیجی‌کالا JSON معتبر نیست.');
        }

        return $json;
    }

    private static function normalizeProduct(array $p, int $dkp): array
    {
        $title = trim((string)($p['title_fa'] ?? $p['title_en'] ?? ''));
        $titleEn = trim((string)($p['title_en'] ?? ''));
        $url = 'https://www.digikala.com/product/dkp-' . $dkp . '/';

        $short = '';
        if (!empty($p['expert_reviews']['short_review'])) {
            $short = self::plainText($p['expert_reviews']['short_review']);
        }
        if ($short === '' && !empty($p['review']['description'])) {
            $short = self::plainText($p['review']['description']);
        }
        if ($short === '') {
            $short = $title;
        }

        $descriptionParts = [];
        if (!empty($p['expert_reviews']['description'])) {
            $descriptionParts[] = self::plainText($p['expert_reviews']['description']);
        }
        if (!empty($p['review']['description'])) {
            $descriptionParts[] = self::plainText($p['review']['description']);
        }
        if (!empty($p['expert_reviews']['advantages']) && is_array($p['expert_reviews']['advantages'])) {
            $descriptionParts[] = "مزایا:\n- " . implode("\n- ", array_map([self::class, 'plainText'], $p['expert_reviews']['advantages']));
        }
        if (!empty($p['expert_reviews']['disadvantages']) && is_array($p['expert_reviews']['disadvantages'])) {
            $descriptionParts[] = "معایب ذکرشده:\n- " . implode("\n- ", array_map([self::class, 'plainText'], $p['expert_reviews']['disadvantages']));
        }

        $description = self::uniqueParagraphs($descriptionParts);
        if ($description === '') {
            $description = $short;
        }

        $price = 0;
        $oldPrice = 0;
        if (!empty($p['default_variant']['price']['selling_price'])) {
            $price = (int)$p['default_variant']['price']['selling_price'];
            // Digikala prices are often Rial. Convert to Toman.
            if ($price > 10000) {
                $price = (int)round($price / 10);
            }
        }

        if (!empty($p['default_variant']['price']['rrp_price'])) {
            $oldPrice = (int)$p['default_variant']['price']['rrp_price'];
            if ($oldPrice > 10000) {
                $oldPrice = (int)round($oldPrice / 10);
            }
        }

        if ($oldPrice <= $price) {
            $oldPrice = 0;
        }

        $stock = 0;
        if (!empty($p['default_variant']['status']) && in_array($p['default_variant']['status'], ['marketable', 'marketable_soon'], true)) {
            $stock = 10;
        }
        if (!empty($p['default_variant']['shipment_methods']['description'])) {
            $stock = max($stock, 1);
        }

        $images = self::extractImages($p);
        $specs = self::extractSpecs($p);

        return [
            'dkp' => $dkp,
            'source_url' => $url,
            'title' => $title ?: ('محصول دیجی‌کالا ' . $dkp),
            'title_en' => $titleEn,
            'slug' => self::slugify(($titleEn ?: $title ?: 'digikala-product') . '-' . $dkp),
            'short_description' => mb_substr($short, 0, 480),
            'description' => $description,
            'price' => $price,
            'old_price' => $oldPrice,
            'stock' => $stock,
            'images' => $images,
            'specs' => $specs,
        ];
    }


    private static function extractImages(array $p): array
    {
        $images = [];

        $main = $p['images']['main'] ?? null;
        self::addImageFromNode($images, $main);

        foreach (($p['images']['list'] ?? []) as $img) {
            self::addImageFromNode($images, $img);
        }

        foreach (($p['images']['webp'] ?? []) as $img) {
            self::addImageFromNode($images, $img);
        }

        $unique = [];
        foreach ($images as $url) {
            $url = self::normalizeImageUrl($url);
            if (!$url) continue;

            $key = self::imageIdentityKey($url);
            if (!isset($unique[$key])) {
                $unique[$key] = $url;
            }
        }

        return array_values($unique);
    }

    private static function normalizeImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !str_starts_with($url, 'http')) return '';

        // Prefer original quality when Digikala gives size variants.
        $url = preg_replace('#/resize/[^/]+/#', '/', $url);
        $url = preg_replace('#\?.*$#', '', $url);

        return $url;
    }

    private static function imageIdentityKey(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $base = basename($path);

        // Remove common Digikala size suffixes and extension differences.
        $base = preg_replace('/\.(jpg|jpeg|png|webp)$/i', '', $base);
        $base = preg_replace('/(_\d+x\d+|-\d+x\d+|_\d+|-\d+)$/i', '', $base);

        return md5($base ?: $url);
    }

    private static function addImageFromNode(array &$images, $node): void
    {
        if (!$node) return;

        if (is_string($node) && str_starts_with($node, 'http')) {
            $images[] = $node;
            return;
        }

        if (!is_array($node)) return;

        foreach (['url', 'webp_url', 'storage_ids'] as $key) {
            if (empty($node[$key])) continue;

            if (is_string($node[$key]) && str_starts_with($node[$key], 'http')) {
                $images[] = $node[$key];
            } elseif (is_array($node[$key])) {
                foreach ($node[$key] as $u) {
                    if (is_string($u) && str_starts_with($u, 'http')) {
                        $images[] = $u;
                    }
                }
            }
        }

        foreach (['main', 'list'] as $key) {
            if (!empty($node[$key])) {
                self::addImageFromNode($images, $node[$key]);
            }
        }
    }

    private static function extractSpecs(array $p): array
    {
        $specs = [];

        $groups = $p['specifications'] ?? [];
        foreach ($groups as $group) {
            $attributes = $group['attributes'] ?? [];
            foreach ($attributes as $attr) {
                $key = self::plainText($attr['title'] ?? '');
                $values = $attr['values'] ?? $attr['value'] ?? [];
                if (is_string($values)) $values = [$values];

                $cleanValues = [];
                if (is_array($values)) {
                    foreach ($values as $v) {
                        if (is_array($v)) {
                            $v = $v['title'] ?? $v['value'] ?? '';
                        }
                        $v = self::plainText((string)$v);
                        if ($v !== '') $cleanValues[] = $v;
                    }
                }

                $value = implode("\n", $cleanValues);
                if ($key !== '' && $value !== '') {
                    $specs[] = ['key' => $key, 'value' => $value];
                }
            }
        }

        if (!empty($p['brand']['title_fa'])) {
            array_unshift($specs, ['key' => 'برند', 'value' => self::plainText($p['brand']['title_fa'])]);
        }

        return $specs;
    }

    public static function downloadImage(string $url, string $dir): ?string
    {
        $url = trim($url);
        if ($url === '' || !str_starts_with($url, 'http')) return null;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pathInfo = parse_url($url, PHP_URL_PATH) ?: '';
        $ext = strtolower(pathinfo($pathInfo, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }
        if ($ext === 'jpeg') $ext = 'jpg';

        $filename = 'dk-' . bin2hex(random_bytes(10)) . '.' . $ext;
        $target = rtrim($dir, '/') . '/' . $filename;

        $ch = curl_init($url);
        $fp = fopen($target, 'wb');
        if (!$fp) return null;

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 35,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (compatible; VaL3RImporter/1.0; +https://val3r.ir)',
                'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            ],
        ]);

        $ok = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $mime = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        fclose($fp);

        if (!$ok || $status < 200 || $status >= 300 || filesize($target) < 1000) {
            @unlink($target);
            return null;
        }

        if (!preg_match('#^image/(jpeg|png|webp)#i', $mime)) {
            // Some CDNs do not return proper content-type. Keep file if extension is valid.
        }

        return $filename;
    }


    private static function uniqueParagraphs(array $parts): string
    {
        $unique = [];
        $out = [];

        foreach ($parts as $part) {
            $part = trim((string)$part);
            if ($part === '') continue;

            $key = mb_strtolower(preg_replace('/\s+/u', ' ', strip_tags($part)));
            $key = trim($key);

            if ($key === '' || isset($unique[$key])) continue;

            $unique[$key] = true;
            $out[] = $part;
        }

        return trim(implode("\n\n", $out));
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9آ-ی]+/iu', '-', $text);
        $text = trim($text, '-');
        return $text ?: ('product-' . time());
    }

    private static function plainText($html): string
    {
        $text = (string)$html;
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }
}
