<?php
$pageTitle = 'محصولات VaL3R';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM products WHERE is_active = 1";
$params = [];
if ($categoryId > 0) { $sql .= " AND category_id = ?"; $params[] = $categoryId; }
if ($q !== '') { $sql .= " AND (name LIKE ? OR short_description LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
include __DIR__ . '/header.php';
?>

<section class="v5-page-hero">
    <div class="container">
        <span class="v5-eyebrow">VaL3R Store</span>
        <h1>محصولات والر</h1>
        <p>محصولات لوازم شخصی برقی VaL3R را با تجربه‌ای سریع، مینیمال و موبایل‌پسند انتخاب کنید.</p>
    </div>
</section>

<section class="v5-section">
    <div class="container">
        <form class="v5-search" method="get" data-products-search>
            <input type="hidden" name="page" value="products">
            <input name="q" value="<?= e($q) ?>" placeholder="جستجوی محصول...">
            <button>جستجو</button>
        </form>

        <div class="v5-shop-layout">
            <aside class="v5-shop-sidebar">
                <a class="<?= $categoryId === 0 ? 'active' : '' ?>" href="<?= BASE_URL ?>?page=products" data-category-filter="0">همه محصولات</a>
                <?php foreach ($categories as $cat): ?>
                    <a class="<?= $categoryId === (int)$cat['id'] ? 'active' : '' ?>" href="<?= BASE_URL ?>?page=products&category=<?= (int)$cat['id'] ?>" data-category-filter="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></a>
                <?php endforeach; ?>
            </aside>
            <div class="v5-product-grid" data-products-grid>
                <?php if (!$products): ?><div class="v5-empty">محصولی پیدا نشد.</div><?php endif; ?>
                <?php foreach ($products as $p) render_product_card($pdo, $p); ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
