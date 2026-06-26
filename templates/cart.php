<?php
$pageTitle = 'سبد خرید | VaL3R';
$cart = cart_items();
$products = [];
$total = 0;

if ($cart) {
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
}

include __DIR__ . '/header.php';
?>

<section class="v5-page-hero compact">
    <div class="container">
        <span class="v5-eyebrow">Shopping Cart</span>
        <h1>سبد خرید</h1>
        <p>محصولات انتخاب‌شده را بررسی کنید و برای ثبت سفارش ادامه دهید.</p>
    </div>
</section>

<section class="v5-section">
    <div class="container">

        <div class="v5-checkout-steps">
            <div class="active"><b>1</b><span>سبد خرید</span><small>بررسی محصولات</small></div>
            <i></i>
            <div><b>2</b><span>ثبت سفارش</span><small>اطلاعات ارسال</small></div>
            <i></i>
            <div><b>3</b><span>پرداخت</span><small>نهایی‌سازی خرید</small></div>
        </div>

        <?php if (!$products): ?>
            <div class="v5-empty-state wide">
                <strong>سبد خرید شما خالی است.</strong>
                <p>برای شروع خرید، محصولات VaL3R را مشاهده کنید.</p>
                <a href="<?= BASE_URL ?>?page=products">مشاهده محصولات</a>
            </div>
        <?php else: ?>
            <div class="v5-cart-layout" data-cart-area>
                <form class="v5-cart-list" method="post" action="<?= BASE_URL ?>ajax/cart.php?action=update" data-auto-cart-form>
                    <input type="hidden" name="ajax" value="1">

                    <div class="v5-cart-list-head">
                        <div>
                            <span>Selected items</span>
                            <h2>محصولات سبد خرید</h2>
                        </div>
                        <strong><b data-cart-count><?= cart_count() ?></b> کالا</strong>
                    </div>

                    <?php foreach ($products as $p):
                        $qty = (int)($cart[$p['id']] ?? 1);
                        $rowTotal = $qty * (int)$p['price'];
                        $total += $rowTotal;
                        $img = get_primary_image($pdo, (int)$p['id']);
                        $productUrl = BASE_URL . '?page=product&id=' . (int)$p['id'];
                    ?>
                        <div class="v5-cart-row" data-cart-row="<?= (int)$p['id'] ?>">
                            <a class="v5-cart-img" href="<?= $productUrl ?>">
                                <img src="<?= e(product_image_url($img)) ?>" alt="<?= e($p['name']) ?>">
                            </a>

                            <div class="v5-cart-product">
                                <a href="<?= $productUrl ?>"><?= e($p['name']) ?></a>
                                <span><?= money($p['price']) ?></span>
                            </div>

                            <div class="qty-control">
                                <button type="button" data-qty-minus>-</button>
                                <input type="number" name="qty[<?= (int)$p['id'] ?>]" value="<?= $qty ?>" min="0" max="<?= max(1, (int)$p['stock']) ?>" data-auto-qty data-product-id="<?= (int)$p['id'] ?>">
                                <button type="button" data-qty-plus>+</button>
                            </div>

                            <strong class="v5-row-total" data-row-total="<?= (int)$p['id'] ?>"><?= money($rowTotal) ?></strong>

                            <a class="v5-remove-link" href="<?= BASE_URL ?>ajax/cart.php?action=remove&id=<?= (int)$p['id'] ?>" data-cart-remove="<?= (int)$p['id'] ?>">حذف</a>
                        </div>
                    <?php endforeach; ?>
                </form>

                <aside class="v83-cart-summary">
                    <div class="v83-summary-glow"></div>

                    <div class="v83-summary-head">
                        <span>Order Summary</span>
                        <h2>خلاصه سفارش</h2>
                        <p>مرور نهایی سبد خرید قبل از ثبت سفارش</p>
                    </div>

                    <div class="v83-summary-metrics">
                        <div>
                            <small>تعداد کالاها</small>
                            <strong data-summary-count><?= cart_count() ?></strong>
                        </div>
                        <div>
                            <small>جمع سبد خرید</small>
                            <strong data-cart-total><?= money($total) ?></strong>
                        </div>
                    </div>

                    <div class="v83-summary-note">
                        <b>مرحله بعد</b>
                        <span>اطلاعات ارسال و هزینه نهایی در صفحه ثبت سفارش مشخص می‌شود.</span>
                    </div>

                    <div class="v83-summary-actions">
                        <a class="v83-primary-btn" href="<?= BASE_URL ?>?page=checkout">ادامه ثبت سفارش</a>
                        <a class="v83-secondary-btn" href="<?= BASE_URL ?>?page=products">اضافه کردن محصولات دیگر</a>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
