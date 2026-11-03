<?php
/** Logs the user out and returns to the login page. */
require_once __DIR__ . '/includes/auth.php';
start_secure_session();
logout_user();

start_secure_session();
$_SESSION['flash'] = 'You have been logged out.';
redirect('/login.php');
