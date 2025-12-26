<?php
/*
|--------------------------------------------------------------------------
| Authentication Handler
|--------------------------------------------------------------------------
| Handles user authentication for the inventory management system
| Author: Assistant
| Version: 1.0
|
*/

require_once('includes/load.php');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $req_fields = array('username', 'password');
    validate_fields($req_fields);
    
    $username = remove_junk($_POST['username']);
    $password = remove_junk($_POST['password']);
    
    if (empty($errors)) {
        $user = authenticate_v2($username, $password);
        
        if ($user) {
            // Create session with id
            $session->login($user['id']);
            
            // Update sign in time
            updateLastLogIn($user['id']);
            
            // Redirect user to appropriate page by user level
            if ($user['user_level'] === '1') {
                $session->msg("s", "Hello " . $user['username'] . ", Welcome to Inventory System.");
                redirect('admin.php', false);
            } elseif ($user['user_level'] === '2') {
                $session->msg("s", "Hello " . $user['username'] . ", Welcome to Inventory System.");
                redirect('special.php', false);
            } else {
                $session->msg("s", "Hello " . $user['username'] . ", Welcome to Inventory System.");
                redirect('home.php', false);
            }
        } else {
            $session->msg("d", "Sorry Username/Password incorrect.");
            redirect('index.php', false);
        }
    } else {
        $session->msg("d", $errors);
        redirect('login.php', false);
    }
} else {
    redirect('login.php', false);
}

?>