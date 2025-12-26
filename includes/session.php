<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session timeout to 30 minutes
$timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Last request was more than 30 minutes ago
    session_unset();     // Unset $_SESSION variable for this page
    session_destroy();   // Destroy session data
}
$_SESSION['last_activity'] = time(); // Update last activity timestamp

class Session {
    public $msg;
    private $user_is_logged_in = false;

    function __construct() {
        $this->flash_msg();
        $this->userLoginSetup();
    }

    public function isUserLoggedIn() {
        return $this->user_is_logged_in;
    }

    public function login($user_id) {
        $_SESSION['user_id'] = $user_id;
    }

    private function userLoginSetup() {
        if(isset($_SESSION['user_id'])) {
            $this->user_is_logged_in = true;
        } else {
            $this->user_is_logged_in = false;
        }
    }

    public function logout() {
        unset($_SESSION['user_id']);
    }

    public function msg($type = '', $msg = '') {
        if(!empty($msg)) {
            if(strlen(trim($type)) == 1) {
                $type = str_replace(array('d', 'i', 'w','s'), array('danger', 'info', 'warning','success'), $type);
            }
            $_SESSION['msg'][$type] = $msg;
        } else {
            return $this->msg;
        }
    }

    private function flash_msg() {
        if(isset($_SESSION['msg'])) {
            $this->msg = $_SESSION['msg'];
            unset($_SESSION['msg']);
        } else {
            $this->msg;
        }
    }
}

$session = new Session();
$msg = $session->msg();
?>
