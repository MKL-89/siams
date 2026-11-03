<?php
/** Entry point: logged-in users go to their dashboard, everyone else to the login page. */
require_once __DIR__ . '/includes/auth.php';
start_secure_session();

if (!empty($_SESSION['user_id'])) {
    redirect(ROLE_HOME[current_role()]);
}
redirect('/login.php');
