<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/cart.php';

session_start();

$action = $_GET['action'] ?? $_POST['action'] ?? 'summary';
$isAjax = (
    (isset($_POST['ajax']) && $_POST['ajax'] === '1') ||
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
);

function cart_json_response(PDO $pdo): array
{
    $cart = cart_items();
    $rows = [];
    $total = 0;

    if ($cart) {
        $ids = array_map('intval', array_keys($cart));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders) AND is_active = 1");
        $stmt->execute($ids);
        $products = $stmt->fetchAll();

        foreach ($products as $p) {
            $productId = (int)$p['id'];
            $qty = (int)($cart[$productId] ?? 0);
            $rowTotal = $qty * (int)$p['price'];
            $total += $rowTotal;

            $rows[$productId] = [
                'qty' => $qty,
                'row_total' => money($rowTotal),
                'row_total_raw' => $rowTotal,
            ];
        }
    }

    return [
        'ok' => true,
        'cart_count' => cart_count(),
        'total' => money($total),
        'total_raw' => $total,
        'rows' => $rows,
        'is_empty' => cart_count() === 0,
    ];
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($productId > 0) {
        if (isset($_POST['set_qty']) && $_POST['set_qty'] === '1') {
            cart_update($productId, $quantity);
        } else {
            cart_add($productId, $quantity);
        }
    }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['qty'] ?? [] as $productId => $quantity) {
        cart_update((int)$productId, (int)$quantity);
    }
}

if ($action === 'remove') {
    cart_remove((int)($_GET['id'] ?? $_POST['id'] ?? 0));
}

$response = cart_json_response($pdo);

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$redirect = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '?page=cart');
header('Location: ' . $redirect);
exit;
