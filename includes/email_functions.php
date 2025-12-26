<?php
/*
|--------------------------------------------------------------------------
| Email Helper Functions
|--------------------------------------------------------------------------
| Email utility functions for the inventory management system
| Author: Assistant
| Version: 1.0
|
*/

require_once(__DIR__ . '/email_config.php');
require_once(__DIR__ . '/src/PHPMailer.php');
require_once(__DIR__ . '/src/SMTP.php');
require_once(__DIR__ . '/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Initialize PHPMailer with configuration
 * @return PHPMailer
 */
function initializeMailer() {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = EMAIL_HOST;
        $mail->SMTPAuth = EMAIL_SMTP_AUTH;
        $mail->Username = EMAIL_USERNAME;
        $mail->Password = EMAIL_PASSWORD;
        $mail->SMTPSecure = EMAIL_SMTP_SECURE;
        $mail->Port = EMAIL_PORT;
        
        // Debug mode
        if (EMAIL_SMTP_DEBUG) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        }
        
        // Sender info
        $mail->setFrom(EMAIL_FROM_EMAIL, EMAIL_FROM_NAME);
        $mail->isHTML(true);
        
        return $mail;
    } catch (Exception $e) {
        error_log("Email initialization failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send a simple email
 * @param string $to Email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $toName Recipient name (optional)
 * @return bool
 */
function sendEmail($to, $subject, $body, $toName = '') {
    $mail = initializeMailer();
    if (!$mail) {
        return false;
    }
    
    try {
        $mail->addAddress($to, $toName);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $result = $mail->send();
        
        if ($result) {
            error_log("Email sent successfully to: $to");
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email with template
 * @param string $to Email address
 * @param string $template Template name
 * @param array $data Template data
 * @param string $toName Recipient name (optional)
 * @return bool
 */
function sendTemplateEmail($to, $template, $data = [], $toName = '') {
    $templateContent = loadEmailTemplate($template, $data);
    if (!$templateContent) {
        return false;
    }
    
    $subject = $templateContent['subject'];
    $body = $templateContent['body'];
    
    return sendEmail($to, $subject, $body, $toName);
}

/**
 * Load email template
 * @param string $template Template name
 * @param array $data Template data
 * @return array|false
 */
function loadEmailTemplate($template, $data = []) {
    $templateFile = EMAIL_TEMPLATE_PATH . $template . '.php';
    
    if (!file_exists($templateFile)) {
        error_log("Email template not found: $template");
        return false;
    }
    
    // Extract variables for template
    extract($data);
    
    ob_start();
    include $templateFile;
    $body = ob_get_clean();
    
    // Get subject from template
    $subject = isset($subject) ? $subject : 'Notification from Inventory System';
    
    return [
        'subject' => $subject,
        'body' => $body
    ];
}

/**
 * Send order confirmation email
 * @param array $order Order data
 * @param array $customer Customer data
 * @param array $items Order items
 * @return bool
 */
function sendOrderConfirmationEmail($order, $customer, $items) {
    $data = [
        'order' => $order,
        'customer' => $customer,
        'items' => $items,
        'subject' => 'Order Confirmation - ' . $order['order_number']
    ];
    
    return sendTemplateEmail($customer['email'], EMAIL_ORDER_CONFIRMATION, $data, $customer['name']);
}

/**
 * Send order acceptance email
 * @param array $order Order data
 * @param array $customer Customer data
 * @param string $customerMessage Personalized message for customer
 * @param string $estimatedDelivery Estimated delivery date
 * @return bool
 */
function sendOrderAcceptanceEmail($order, $customer, $customerMessage = '', $estimatedDelivery = '') {
    $data = [
        'order' => $order,
        'customer' => $customer,
        'customerMessage' => $customerMessage,
        'estimatedDelivery' => $estimatedDelivery,
        'subject' => 'Order Accepted - ' . $order['order_number']
    ];
    
    return sendTemplateEmail($customer['email'], EMAIL_ORDER_ACCEPTANCE, $data, $customer['name']);
}

/**
 * Send order rejection email
 * @param array $order Order data
 * @param array $customer Customer data
 * @param string $rejectionReason Reason for rejection
 * @return bool
 */
function sendOrderRejectionEmail($order, $customer, $rejectionReason = '') {
    $data = [
        'order' => $order,
        'customer' => $customer,
        'rejectionReason' => $rejectionReason,
        'subject' => 'Order Rejection Notice - ' . $order['order_number']
    ];
    
    return sendTemplateEmail($customer['email'], EMAIL_ORDER_REJECTION, $data, $customer['name']);
}

/**
 * Send order status update email
 * @param array $order Order data
 * @param array $customer Customer data
 * @param string $newStatus New order status
 * @return bool
 */
function sendOrderStatusUpdateEmail($order, $customer, $newStatus) {
    $data = [
        'order' => $order,
        'customer' => $customer,
        'newStatus' => $newStatus,
        'subject' => 'Order Status Update - ' . $order['order_number']
    ];
    
    return sendTemplateEmail($customer['email'], EMAIL_ORDER_STATUS_UPDATE, $data, $customer['name']);
}

/**
 * Send payment confirmation email
 * @param array $order Order data
 * @param array $customer Customer data
 * @return bool
 */
function sendPaymentConfirmationEmail($order, $customer) {
    $data = [
        'order' => $order,
        'customer' => $customer,
        'subject' => 'Payment Confirmed - ' . $order['order_number']
    ];
    
    return sendTemplateEmail($customer['email'], EMAIL_PAYMENT_CONFIRMATION, $data, $customer['name']);
}

/**
 * Send invoice ready email
 * @param array $order Order data
 * @param array $customer Customer data
 * @param string $invoiceNumber Invoice number
 * @return bool
 */
function sendInvoiceReadyEmail($order, $customer, $invoiceNumber) {
    $data = [
        'order' => $order,
        'customer' => $customer,
        'invoiceNumber' => $invoiceNumber,
        'subject' => 'Invoice Ready - ' . $invoiceNumber
    ];
    
    return sendTemplateEmail($customer['email'], EMAIL_INVOICE_READY, $data, $customer['name']);
}

/**
 * Send customer registration email
 * @param array $customer Customer data
 * @return bool
 */
function sendCustomerRegistrationEmail($customer) {
    $data = [
        'customer' => $customer,
        'subject' => 'Welcome to Our Store!'
    ];
    
    return sendTemplateEmail($customer['email'], EMAIL_CUSTOMER_REGISTRATION, $data, $customer['name']);
}

/**
 * Send password reset email
 * @param string $email Customer email
 * @param string $resetToken Reset token
 * @param string $customerName Customer name
 * @return bool
 */
function sendPasswordResetEmail($email, $resetToken, $customerName = '') {
    $data = [
        'resetToken' => $resetToken,
        'customerName' => $customerName,
        'subject' => 'Password Reset Request'
    ];
    
    return sendTemplateEmail($email, EMAIL_PASSWORD_RESET, $data, $customerName);
}

/**
 * Send admin notification email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param array $additionalData Additional data
 * @return bool
 */
function sendAdminNotificationEmail($subject, $message, $additionalData = []) {
    $adminEmail = EMAIL_FROM_EMAIL; // You can configure admin email separately
    
    $data = [
        'message' => $message,
        'additionalData' => $additionalData,
        'subject' => 'Admin Notification: ' . $subject
    ];
    
    return sendTemplateEmail($adminEmail, EMAIL_ADMIN_NOTIFICATION, $data, 'Admin');
}

/**
 * Test email configuration
 * @param string $testEmail Test email address
 * @return array
 */
function testEmailConfiguration($testEmail = null) {
    $testEmail = $testEmail ?: EMAIL_FROM_EMAIL;
    
    $result = [
        'success' => false,
        'message' => '',
        'details' => []
    ];
    
    try {
        $mail = initializeMailer();
        if (!$mail) {
            $result['message'] = 'Failed to initialize mailer';
            return $result;
        }
        
        // Test SMTP connection
        $mail->smtpConnect();
        $result['details'][] = 'SMTP connection successful';
        
        // Test email sending
        $mail->addAddress($testEmail);
        $mail->Subject = 'Test Email from Inventory System';
        $mail->Body = '<h1>Test Email</h1><p>This is a test email to verify email configuration.</p>';
        
        if ($mail->send()) {
            $result['success'] = true;
            $result['message'] = 'Test email sent successfully';
            $result['details'][] = 'Email sent to: ' . $testEmail;
        } else {
            $result['message'] = 'Failed to send test email';
        }
        
    } catch (Exception $e) {
        $result['message'] = 'Email test failed: ' . $e->getMessage();
        $result['details'][] = 'Error: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Format currency for email
 * @param float $amount Amount to format
 * @param string $currency Currency code
 * @return string
 */
function formatEmailCurrency($amount, $currency = 'LKR') {
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Format date for email
 * @param string $date Date string
 * @param string $format Date format
 * @return string
 */
function formatEmailDate($date, $format = 'F j, Y g:i A') {
    return date($format, strtotime($date));
}

?>
