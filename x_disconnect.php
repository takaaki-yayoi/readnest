<?php
/**
 * X (Twitter) Disconnect Handler
 * Removes user's X account connection
 */

declare(strict_types=1);

require_once('config.php');
require_once('library/session.php');
require_once('library/database.php');
require_once('library/security.php');

// Initialize session
// session.php handles session start directly

// Check if user is logged in
if (!checkLogin()) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['AUTH_USER'];

// Verify CSRF token (if implemented)
// $csrf_token = $_POST['csrf_token'] ?? '';
// if (!validateCSRFToken($csrf_token)) {
//     header('Location: /account.php?x_error=invalid_csrf');
//     exit;
// }

// Remove X credentials from database
global $g_db;

$update_sql = "UPDATE b_user SET 
    x_oauth_token = NULL,
    x_oauth_token_secret = NULL,
    x_screen_name = NULL,
    x_user_id = NULL,
    x_connected_at = NULL,
    x_post_enabled = 0
    WHERE user_id = ?";

$result = $g_db->query($update_sql, [$user_id]);

if (DB::isError($result)) {
    error_log('[X Disconnect] Failed to remove credentials: ' . $result->getMessage());
    header('Location: /account.php?x_error=disconnect_failed');
    exit;
}

// Redirect to account page with success message
header('Location: /account.php?x_disconnected=success');
exit;