<?php
/*
|--------------------------------------------------------------------------
| Email Configuration Setup
|--------------------------------------------------------------------------
| Setup page for email configuration
| Author: Assistant
| Version: 1.0
|
*/

require_once('includes/config.php');
require_once('includes/database.php');

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_config') {
        $config_content = "<?php\n";
        $config_content .= "// Email Configuration Constants\n";
        $config_content .= "define('EMAIL_HOST', '" . $_POST['email_host'] . "');\n";
        $config_content .= "define('EMAIL_PORT', " . $_POST['email_port'] . ");\n";
        $config_content .= "define('EMAIL_USERNAME', '" . $_POST['email_username'] . "');\n";
        $config_content .= "define('EMAIL_PASSWORD', '" . $_POST['email_password'] . "');\n";
        $config_content .= "define('EMAIL_FROM_EMAIL', '" . $_POST['from_email'] . "');\n";
        $config_content .= "define('EMAIL_FROM_NAME', '" . $_POST['from_name'] . "');\n";
        $config_content .= "define('EMAIL_SMTP_SECURE', '" . $_POST['smtp_secure'] . "');\n";
        $config_content .= "define('EMAIL_SMTP_AUTH', " . ($_POST['smtp_auth'] ? 'true' : 'false') . ");\n";
        $config_content .= "define('EMAIL_SMTP_DEBUG', " . ($_POST['smtp_debug'] ? 'true' : 'false') . ");\n";
        $config_content .= "\n// Email Templates Configuration\n";
        $config_content .= "define('EMAIL_TEMPLATE_PATH', __DIR__ . '/email_templates/');\n";
        $config_content .= "\n// Email Types Configuration\n";
        $config_content .= "define('EMAIL_ORDER_CONFIRMATION', 'order_confirmation');\n";
        $config_content .= "define('EMAIL_ORDER_STATUS_UPDATE', 'order_status_update');\n";
        $config_content .= "define('EMAIL_PAYMENT_CONFIRMATION', 'payment_confirmation');\n";
        $config_content .= "define('EMAIL_INVOICE_READY', 'invoice_ready');\n";
        $config_content .= "define('EMAIL_CUSTOMER_REGISTRATION', 'customer_registration');\n";
        $config_content .= "define('EMAIL_PASSWORD_RESET', 'password_reset');\n";
        $config_content .= "define('EMAIL_ADMIN_NOTIFICATION', 'admin_notification');\n";
        $config_content .= "\n// Email Settings for Different Environments\n";
        $config_content .= "if (defined('DEBUG') && DEBUG) {\n";
        $config_content .= "    define('EMAIL_SMTP_DEBUG', true);\n";
        $config_content .= "    define('EMAIL_TEST_MODE', true);\n";
        $config_content .= "} else {\n";
        $config_content .= "    define('EMAIL_SMTP_DEBUG', false);\n";
        $config_content .= "    define('EMAIL_TEST_MODE', false);\n";
        $config_content .= "}\n";
        $config_content .= "\n?>";
        
        if (file_put_contents('includes/email_config.php', $config_content)) {
            $message = 'Email configuration updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update email configuration.';
            $messageType = 'error';
        }
    }
}

// Load current configuration
$current_config = [];
if (file_exists('includes/email_config.php')) {
    $config_file = file_get_contents('includes/email_config.php');
    preg_match_all("/define\('([^']+)',\s*'?([^']*)'?\);/", $config_file, $matches);
    for ($i = 0; $i < count($matches[1]); $i++) {
        $current_config[$matches[1][$i]] = $matches[2][$i];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0">Email Configuration Setup</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="update_config">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email_host" class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" id="email_host" name="email_host" 
                                               value="<?php echo htmlspecialchars($current_config['EMAIL_HOST'] ?? 'smtp.gmail.com' 'smtp.outlook.com'); ?>" required>
                                        <div class="form-text">e.g., smtp.gmail.com, smtp.outlook.com</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email_port" class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" id="email_port" name="email_port" 
                                               value="<?php echo htmlspecialchars($current_config['EMAIL_PORT'] ?? '587'); ?>" required>
                                        <div class="form-text">587 for TLS, 465 for SSL</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email_username" class="form-label">Email Username</label>
                                        <input type="email" class="form-control" id="email_username" name="email_username" 
                                               value="<?php echo htmlspecialchars($current_config['EMAIL_USERNAME'] ?? ''); ?>" required>
                                        <div class="form-text">Your email address</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email_password" class="form-label">Email Password</label>
                                        <input type="password" class="form-control" id="email_password" name="email_password" 
                                               value="<?php echo htmlspecialchars($current_config['EMAIL_PASSWORD'] ?? ''); ?>" required>
                                        <div class="form-text">Use app password for Gmail</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="from_email" class="form-label">From Email</label>
                                        <input type="email" class="form-control" id="from_email" name="from_email" 
                                               value="<?php echo htmlspecialchars($current_config['EMAIL_FROM_EMAIL'] ?? ''); ?>" required>
                                        <div class="form-text">Sender email address</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="from_name" class="form-label">From Name</label>
                                        <input type="text" class="form-control" id="from_name" name="from_name" 
                                               value="<?php echo htmlspecialchars($current_config['EMAIL_FROM_NAME'] ?? 'Inventory System'); ?>" required>
                                        <div class="form-text">Sender display name</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="smtp_secure" class="form-label">SMTP Security</label>
                                        <select class="form-control" id="smtp_secure" name="smtp_secure">
                                            <option value="tls" <?php echo ($current_config['EMAIL_SMTP_SECURE'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo ($current_config['EMAIL_SMTP_SECURE'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="smtp_auth" name="smtp_auth" 
                                                   <?php echo ($current_config['EMAIL_SMTP_AUTH'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="smtp_auth">
                                                SMTP Authentication
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="smtp_debug" name="smtp_debug" 
                                                   <?php echo ($current_config['EMAIL_SMTP_DEBUG'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="smtp_debug">
                                                Debug Mode
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Configuration</button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="mt-4">
                            <h4>Popular Email Provider Settings</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Gmail</h5>
                                    <ul>
                                        <li>SMTP Host: smtp.gmail.com</li>
                                        <li>Port: 587 (TLS) or 465 (SSL)</li>
                                        <li>Security: TLS or SSL</li>
                                        <li>Authentication: Required</li>
                                        <li>Note: Use App Password instead of regular password</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Outlook/Hotmail</h5>
                                    <ul>
                                        <li>SMTP Host: smtp-mail.outlook.com</li>
                                        <li>Port: 587</li>
                                        <li>Security: TLS</li>
                                        <li>Authentication: Required</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
