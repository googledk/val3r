<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/cart.php';

session_start();

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$q = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM products WHERE is_active = 1";
$params = [];

if ($categoryId > 0) {
    $sql .= " AND category_id = ?";
    $params[] = $categoryId;
}

if ($q !== '') {
    $sql .= " AND (name LIKE ? OR short_description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

ob_start();

if (!$products) {
    echo '<div class="empty-box">محصولی با این مشخصات پیدا نشد.</div>';
} else {
    foreach ($products as $p) {
        render_product_card($pdo, $p);
    }
}

$html = ob_get_clean();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'html' => $html,
    'category' => $categoryId,
    'count' => count($products),
], JSON_UNESCAPED_UNICODE);
