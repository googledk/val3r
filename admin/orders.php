<?php
require_once __DIR__ . '/_init.php';
require_admin();

if (is_post()) {
    $id = (int)post('id');
    $status = post('order_status');
    $payment = post('payment_status');
    $pdo->prepare("UPDATE orders SET order_status=?, payment_status=? WHERE id=?")->execute([$status, $payment, $id]);
    redirect(BASE_URL . 'admin/orders.php');
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
$adminTitle = 'سفارش‌ها';
include __DIR__ . '/_header.php';
?>
<div class="page-head"><div><h1>سفارش‌ها</h1><p>مشاهده و تغییر وضعیت سفارش‌های ثبت شده</p></div></div>

<div class="table-wrap">
<table>
<tr><th>کد</th><th>مشتری</th><th>موبایل</th><th>مبلغ</th><th>آدرس</th><th>تغییر وضعیت</th></tr>
<?php foreach ($orders as $o): ?>
<tr>
<td><strong><?= e($o['order_code']) ?></strong><small><?= e(jdate_human($o['created_at'])) ?></small></td>
<td><?= e($o['customer_name']) ?></td>
<td><?= e($o['customer_mobile']) ?></td>
<td><?= money($o['total_amount']) ?></td>
<td class="address-cell"><?= e($o['customer_address']) ?></td>
<td>
<form method="post" class="order-status-form">
<input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
<select name="order_status">
<?php foreach (['new'=>'جدید','processing'=>'در حال پردازش','sent'=>'ارسال شده','delivered'=>'تحویل شده','cancelled'=>'لغو شده'] as $k=>$v): ?>
<option value="<?= $k ?>" <?= $o['order_status']===$k?'selected':'' ?>><?= $v ?></option>
<?php endforeach; ?>
</select>
<select name="payment_status">
<?php foreach (['pending'=>'در انتظار','paid'=>'پرداخت شده','failed'=>'ناموفق','cancelled'=>'لغو شده'] as $k=>$v): ?>
<option value="<?= $k ?>" <?= $o['payment_status']===$k?'selected':'' ?>><?= $v ?></option>
<?php endforeach; ?>
</select>
<button>ذخیره</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
