<?php
function cart_items(): array {
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int {
    return array_sum(array_map('intval', cart_items()));
}

function cart_add(int $productId, int $quantity = 1): void {
    if ($quantity < 1) $quantity = 1;
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
}

function cart_update(int $productId, int $quantity): void {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

function cart_remove(int $productId): void {
    unset($_SESSION['cart'][$productId]);
}

function cart_clear(): void {
    $_SESSION['cart'] = [];
}
