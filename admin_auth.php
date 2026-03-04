<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if admin is active (you can add this check from database if needed)
// For now, just return the session data
function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
}

function getCurrentAdminId() {
    return $_SESSION['admin_id'];
}

function getCurrentAdminName() {
    return $_SESSION['admin_name'];
}

function getCurrentAdminRole() {
    return $_SESSION['admin_role'];
}
?>

<?php
// session_start();

// // DEVELOPMENT BYPASS - REMOVE IN PRODUCTION! http://localhost/saferidebd/admin_dashboard.php?dev_login=1
// $dev_mode = true; // Set to false in production

// if ($dev_mode) {
//     // Check if bypass cookie exists
//     if (isset($_COOKIE['admin_bypass']) && $_COOKIE['admin_bypass'] === 'allowed') {
//         // Set session if not already set
//         if (!isset($_SESSION['admin_id'])) {
//             $_SESSION['admin_id'] = 1;
//             $_SESSION['admin_username'] = 'dev_admin';
//             $_SESSION['admin_name'] = 'Development Admin';
//             $_SESSION['admin_role'] = 'super_admin';
//         }
//     }
// }

// // Regular authentication check
// if (!isset($_SESSION['admin_id'])) {
//     // In dev mode, auto-login with a special URL parameter
//     if ($dev_mode && isset($_GET['dev_login'])) {
//         $_SESSION['admin_id'] = 1;
//         $_SESSION['admin_username'] = 'dev_admin';
//         $_SESSION['admin_name'] = 'Development Admin';
//         $_SESSION['admin_role'] = 'super_admin';
        
//         // Set a cookie for future requests
//         setcookie('admin_bypass', 'allowed', time() + 3600, '/');
        
//         // Remove the parameter and redirect
//         header("Location: " . str_replace('?dev_login', '', $_SERVER['REQUEST_URI']));
//         exit();
//     } else {
//         header("Location: admin_login.php");
//         exit();
//     }
// }

// function isSuperAdmin() {
//     return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
// }

// function getCurrentAdminId() {
//     return $_SESSION['admin_id'] ?? 0;
// }

// function getCurrentAdminName() {
//     return $_SESSION['admin_name'] ?? 'Developer';
// }

// function getCurrentAdminRole() {
//     return $_SESSION['admin_role'] ?? 'super_admin';
// }
?>