<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/cart.php';
require_once __DIR__ . '/functions/auth.php';

session_start();

$page = $_GET['page'] ?? 'home';

// FORCE_CONTACT_TEMPLATE_V352
if ($page === 'contact') {
    include __DIR__ . '/templates/contact.php';
    exit;
}

$routes = [
    'home' => 'templates/home.php',
    'products' => 'templates/products.php',
    'product' => 'templates/product.php',
    'cart' => 'templates/cart.php',
    'checkout' => 'templates/checkout.php',
    'track' => 'templates/track.php',
    'login' => 'templates/login.php',
    'verify' => 'templates/verify.php',
    'logout' => 'templates/logout.php',
    'about' => 'templates/about.php',
    'contact' => 'templates/contact.php',
    'payment' => 'templates/payment.php',
    'account' => 'templates/account.php',
    'account_order' => 'templates/account_order.php',
];

if (!isset($routes[$page])) {
    http_response_code(404);
    $pageTitle = 'صفحه پیدا نشد';
    include __DIR__ . '/templates/header.php';
    echo '<section class="page-hero"><div class="container"><h1>صفحه پیدا نشد</h1></div></section>';
    include __DIR__ . '/templates/footer.php';
    exit;
}

include __DIR__ . '/' . $routes[$page];
