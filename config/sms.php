<?php
// SMS.ir settings for VaL3R OTP login
// IMPORTANT: After installation, create a fresh API key in SMS.ir panel and replace this value.
define('SMSIR_API_KEY', 'd08f2DnM3QcLja3IaCnmQUbPsndiOw9iNw60Teexxrsmsri4');
define('SMSIR_TEMPLATE_ID', 718329);
define('SMSIR_CODE_PARAMETER', 'CODE');

// true = shows OTP in verify page for test/debug.
// Set to false after real SMS is confirmed working.
define('OTP_DEBUG_MODE', false);

define('OTP_EXPIRE_MINUTES', 2);
define('OTP_RESEND_SECONDS', 60);
define('OTP_MAX_ATTEMPTS', 3);
define('OTP_MAX_SENDS_PER_HOUR', 5);
