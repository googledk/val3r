<?php
require_once __DIR__ . '/jalali.php';
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($amount): string {
    return number_format((float)$amount, 0) . ' تومان';
}



if (!function_exists('discount_percent')) {
    function discount_percent($oldPrice, $price): int {
        $oldPrice = (float)($oldPrice ?? 0);
        $price = (float)($price ?? 0);
        if ($oldPrice <= 0 || $price <= 0 || $oldPrice <= $price) return 0;
        return (int)round((($oldPrice - $price) / $oldPrice) * 100);
    }
}

if (!function_exists('price_block')) {
    function price_block($oldPrice, $price, string $class = ''): string {
        $oldPrice = (float)($oldPrice ?? 0);
        $price = (float)($price ?? 0);
        $discount = discount_percent($oldPrice, $price);
        ob_start();
        ?>
        <div class="v-price <?= e($class) ?>">
            <?php if ($discount > 0): ?>
                <del><?= money($oldPrice) ?></del>
            <?php endif; ?>
            <strong><?= money($price) ?></strong>
        </div>
        <?php
        return trim(ob_get_clean());
    }
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function is_post(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function post($key, $default = '') {
    return $_POST[$key] ?? $default;
}

function get($key, $default = '') {
    return $_GET[$key] ?? $default;
}


if (!function_exists('clean_product_description')) {
    function clean_product_description(?string $text): string {
        $text = trim((string)$text);
        if ($text === '') return '';

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);

        // Split by blank lines first. If there are no blank lines, keep as one paragraph.
        $parts = preg_split("/\n{2,}/u", $text);
        $unique = [];
        $out = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;

            // Normalize for duplicate detection.
            $key = mb_strtolower(preg_replace('/\s+/u', ' ', strip_tags($part)));
            $key = trim($key);

            if ($key === '') continue;
            if (isset($unique[$key])) continue;

            $unique[$key] = true;
            $out[] = $part;
        }

        // If Digikala duplicated the same long text without blank separator, detect exact half duplication.
        if (count($out) === 1) {
            $one = $out[0];
            $norm = preg_replace('/\s+/u', ' ', $one);
            $len = mb_strlen($norm);
            if ($len > 80 && $len % 2 === 0) {
                $half = (int)($len / 2);
                $a = trim(mb_substr($norm, 0, $half));
                $b = trim(mb_substr($norm, $half));
                if ($a === $b) {
                    $out[0] = $a;
                }
            }
        }

        return trim(implode("\n\n", $out));
    }
}

function product_image_url(?string $imagePath): string {
    if (!$imagePath) {
        return BASE_URL . 'images/product-placeholder.svg';
    }
    if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
        return $imagePath;
    }
    return UPLOAD_PRODUCTS_URL . ltrim($imagePath, '/');
}

function slider_image_url(?string $imagePath): string {
    if (!$imagePath) {
        return BASE_URL . 'images/slider-placeholder.svg';
    }
    if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
        return $imagePath;
    }
    return UPLOAD_SLIDERS_URL . ltrim($imagePath, '/');
}

function get_primary_image(PDO $pdo, int $productId): ?string {
    $stmt = $pdo->prepare("
        SELECT image_path FROM product_images
        WHERE product_id = ?
        ORDER BY is_main DESC, sort_order ASC, id ASC
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    $row = $stmt->fetch();
    return $row['image_path'] ?? null;
}

function upload_image(array $file, string $dir): ?string {
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('خطا در آپلود فایل.');
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('حجم عکس بیشتر از حد مجاز است.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        throw new RuntimeException('فرمت عکس مجاز نیست. فقط JPG، PNG و WEBP.');
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg'
    };

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = rtrim($dir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('ذخیره عکس انجام نشد.');
    }

    return $filename;
}

function page_content(PDO $pdo, string $slug, string $default = ''): string {
    $stmt = $pdo->prepare("SELECT content FROM pages WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row['content'] ?? $default;
}




function render_product_card(PDO $pdo, array $p): void {
    $img = get_primary_image($pdo, (int)$p['id']);
    $oldPrice = $p['old_price'] ?? 0;
    $price = $p['price'] ?? 0;
    $discount = discount_percent($oldPrice, $price);
    $isFeatured = !empty($p['is_featured']);
?>
<article class="v5-product-card">
    <a class="v5-product-image" href="<?= BASE_URL ?>?page=product&id=<?= (int)$p['id'] ?>">
        <div class="v-product-labels">
            <?php if ($discount > 0): ?><span class="discount"><?= $discount ?>٪</span><?php endif; ?>
            <?php if ($isFeatured): ?><span class="featured">منتخب</span><?php endif; ?>
        </div>
        <img loading="lazy" src="<?= e(product_image_url($img)) ?>" alt="<?= e($p['name']) ?>">
    </a>
    <div class="v5-product-body">
        <a href="<?= BASE_URL ?>?page=product&id=<?= (int)$p['id'] ?>"><h3><?= e($p['name']) ?></h3></a>
        <div class="v5-product-foot">
            <?= price_block($oldPrice, $price, 'card') ?>
            <?php if ((int)$p['stock'] > 0): ?>
                <form method="post" action="<?= BASE_URL ?>ajax/cart.php?action=add" data-quick-add>
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit">افزودن</button>
                </form>
            <?php else: ?>
                <span class="v5-soldout">ناموجود</span>
            <?php endif; ?>
        </div>
    </div>
</article>
<?php
}


if (!function_exists('get_related_products')) {
    function get_related_products(PDO $pdo, int $productId, ?int $categoryId, int $limit = 4): array {
        $limit = max(1, min(12, (int)$limit));

        if ($categoryId) {
            $stmt = $pdo->prepare("
                SELECT * FROM products
                WHERE is_active = 1 AND id != ? AND category_id = ?
                ORDER BY is_featured DESC, created_at DESC
                LIMIT {$limit}
            ");
            $stmt->execute([$productId, $categoryId]);
            $items = $stmt->fetchAll();

            if (count($items) >= $limit) {
                return $items;
            }
        }

        $stmt = $pdo->prepare("
            SELECT * FROM products
            WHERE is_active = 1 AND id != ?
            ORDER BY is_featured DESC, created_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('order_status_fa')) {
    function order_status_fa(string $status): string {
        return match ($status) {
            'new' => 'جدید',
            'processing' => 'در حال پردازش',
            'sent' => 'ارسال شده',
            'delivered' => 'تحویل شده',
            'cancelled' => 'لغو شده',
            default => $status
        };
    }
}

if (!function_exists('payment_status_fa')) {
    function payment_status_fa(string $status): string {
        return match ($status) {
            'pending' => 'در انتظار پرداخت',
            'paid' => 'پرداخت شده',
            'failed' => 'ناموفق',
            'cancelled' => 'لغو شده',
            default => $status
        };
    }
}
