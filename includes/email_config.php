<?php
/*
|--------------------------------------------------------------------------
| Email Configuration
|--------------------------------------------------------------------------
| Email settings for the inventory management system
| Author: Assistant
| Version: 1.0
|
*/

// Email Configuration Constants
define('EMAIL_HOST', 'smtp.gmail.com');           // SMTP server (Gmail example)
define('EMAIL_PORT', 587);                         // SMTP port (587 for TLS)
define('EMAIL_USERNAME', 'harishahamed2607@gmail.com'); // Your email address
define('EMAIL_PASSWORD', 'ffxd brkg sple oykx');     // Your email password or app password
define('EMAIL_FROM_EMAIL', 'swisswoodworks@gmail.com'); // From email address
define('EMAIL_FROM_NAME', 'Swisswood Works');     // From name
define('EMAIL_SMTP_SECURE', 'tls');                // Encryption: 'tls' or 'ssl'
define('EMAIL_SMTP_AUTH', true);                   // Enable SMTP authentication
// Email Templates Configuration
define('EMAIL_TEMPLATE_PATH', __DIR__ . '/email_templates/');

// Email Types Configuration
define('EMAIL_ORDER_CONFIRMATION', 'order_confirmation');
define('EMAIL_ORDER_STATUS_UPDATE', 'order_status_update');
define('EMAIL_ORDER_REJECTION', 'order_rejection');
define('EMAIL_ORDER_ACCEPTANCE', 'order_acceptance');
define('EMAIL_PAYMENT_CONFIRMATION', 'payment_confirmation');
define('EMAIL_INVOICE_READY', 'invoice_ready');
define('EMAIL_CUSTOMER_REGISTRATION', 'customer_registration');
define('EMAIL_PASSWORD_RESET', 'password_reset');
define('EMAIL_ADMIN_NOTIFICATION', 'admin_notification');

// Email Settings for Different Environments
if (defined('DEBUG') && DEBUG) {
    // Development settings
    define('EMAIL_SMTP_DEBUG', true);
    define('EMAIL_TEST_MODE', true);
} else {
    // Production settings
    define('EMAIL_SMTP_DEBUG', false);
    define('EMAIL_TEST_MODE', false);
}

?>
