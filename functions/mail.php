<?php
function send_contact_mail(array $data): bool
{
    $to = 'navvab.shamsi@gmail.com';

    $name = trim($data['name'] ?? '');
    $subjectText = trim($data['subject'] ?? '');
    $mobile = trim($data['mobile'] ?? '');
    $message = trim($data['message'] ?? '');

    $subject = 'پیام جدید از سایت VaL3R';
    if ($subjectText !== '') {
        $subject .= ' - ' . $subjectText;
    }

    $body = "پیام جدید از فرم تماس سایت VaL3R\n\n";
    $body .= "نام و نام خانوادگی:\n" . ($name !== '' ? $name : '-') . "\n\n";
    $body .= "عنوان:\n" . ($subjectText !== '' ? $subjectText : '-') . "\n\n";
    $body .= "شماره همراه:\n" . ($mobile !== '' ? $mobile : '-') . "\n\n";
    $body .= "توضیحات:\n" . ($message !== '' ? $message : '-') . "\n\n";
    $body .= "تاریخ ارسال:\n" . jdatetime(date('Y-m-d H:i:s')) . "\n";

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'From: VaL3R Website <no-reply@val3r.ir>';
    $headers[] = 'Reply-To: no-reply@val3r.ir';

    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
}
