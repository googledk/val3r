<?php
$pageTitle = 'تماس با ما | VaL3R ایران';
$content = page_content($pdo, 'contact');
include __DIR__ . '/header.php';
?>

<section class="v5-contact-hero">
    <div class="container">
        <span class="v5-eyebrow">Contact VaL3R Iran</span>
        <h1>تماس با ما</h1>
        <p>برای پرسش درباره خرید، سفارش، همکاری یا پشتیبانی محصولات والر، فرم زیر را ارسال کنید.</p>
    </div>
</section>

<section class="v5-section">
    <div class="container v5-contact-layout">
        <aside class="v5-contact-info">
            <span class="v5-eyebrow">Official Representative</span>
            <h2>VaL3R Iran</h2>
            <p>پیام‌های فرم تماس مستقیماً برای مدیریت سایت ارسال می‌شود.</p>

            <div class="v5-contact-info-list">
                <div>
                    <strong>ایمیل</strong>
                    <span>info@val3r.ir</span>
                </div>
                <div>
                    <strong>موضوعات قابل پیگیری</strong>
                    <span>خرید، سفارش، گارانتی، همکاری و پشتیبانی</span>
                </div>
                <div>
                    <strong>پاسخگویی</strong>
                    <span>همه روزه در ساعات کاری</span>
                </div>
            </div>

            <?php if ($content): ?>
                <div class="v5-contact-content"><?= nl2br(e($content)) ?></div>
            <?php endif; ?>
        </aside>

        <form class="v5-contact-form" method="post" action="<?= BASE_URL ?>ajax/contact.php" data-contact-form>
            <div class="v5-form-head">
                <span>Send Message</span>
                <h2>فرم ارتباط</h2>
                <p>هیچ‌کدام از فیلدها اجباری نیستند. هر مقدار اطلاعاتی که لازم است وارد کنید.</p>
            </div>

            <div class="contact-result" data-contact-result hidden></div>

            <label>نام و نام خانوادگی
                <input name="name" placeholder="مثلاً علیرضا احمدی">
            </label>
            <label>عنوان
                <input name="subject" placeholder="موضوع پیام">
            </label>
            <label>شماره همراه
                <input name="mobile" placeholder="09123456789">
            </label>
            <label>توضیحات
                <textarea name="message" rows="7" placeholder="پیام خود را بنویسید..."></textarea>
            </label>

            <button class="v5-save-btn full" type="submit">ارسال پیام</button>
        </form>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
