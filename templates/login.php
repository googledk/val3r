<?php
if (is_logged_in()) {
    redirect(BASE_URL . '?page=account');
}

$pageTitle = 'ورود یا ثبت‌نام | VaL3R';
include __DIR__ . '/header.php';
?>

<section class="v6-auth-page" data-auth-page>
    <div class="container v6-auth-layout">
        <div class="v6-auth-visual">
            <span>VaL3R Secure Login</span>
            <h1>ورود سریع با شماره موبایل</h1>
            <p>کد تأیید یکبارمصرف برای شما پیامک می‌شود و بعد از ورود، سفارش‌ها و اطلاعات حساب در دسترس خواهد بود.</p>

            <div class="v6-auth-points">
                <div>OTP واقعی SMS.ir</div>
                <div>بدون رمز عبور</div>
                <div>امن و سریع</div>
            </div>
        </div>

        <div class="v6-auth-card">
            <div class="v6-auth-head">
                <span>Login / Register</span>
                <h2>ورود یا ثبت‌نام</h2>
                <p data-auth-subtitle>شماره موبایل خود را وارد کنید تا کد تایید برایتان ارسال شود.</p>
            </div>

            <div class="v6-auth-message" data-auth-message hidden></div>

            <form data-send-otp-form>
                <label class="v6-field">
                    <span>شماره موبایل</span>
                    <div class="v6-input-wrap">
                        <b>+98</b>
                        <input name="mobile" inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" placeholder="09123456789" autofocus>
                    </div>
                </label>

                <button class="v6-auth-btn" type="submit">دریافت کد ورود</button>
            </form>

            <form data-verify-otp-form hidden>
                <input type="hidden" name="mobile" data-otp-mobile>

                <div class="v6-otp-info">
                    کد ارسال‌شده به <strong data-mobile-preview></strong> را وارد کنید.
                </div>

                <div class="v6-otp-boxes" dir="ltr" data-otp-boxes>
                    <input maxlength="1" inputmode="numeric">
                    <input maxlength="1" inputmode="numeric">
                    <input maxlength="1" inputmode="numeric">
                    <input maxlength="1" inputmode="numeric">
                    <input maxlength="1" inputmode="numeric">
                    <input maxlength="1" inputmode="numeric">
                </div>

                <button class="v6-auth-btn" type="submit">تأیید و ورود</button>

                <div class="v6-resend-row">
                    <button type="button" data-resend-otp disabled>ارسال مجدد کد</button>
                    <span data-otp-timer>60</span>
                </div>

                <button type="button" class="v6-change-mobile" data-change-mobile>تغییر شماره موبایل</button>
            </form>

            <div class="v6-auth-note">
                با ورود، امکان مشاهده سفارش‌ها، ذخیره آدرس و پیگیری خرید فعال می‌شود.
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
