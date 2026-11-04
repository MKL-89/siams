<?php
/**
 * Opens a fresh login session for a browser tab.
 * app.js sends a tab here when the tab was opened by typing the address (or from a bookmark)
 * while another open tab is already using the account at that address. The new tab gets its
 * own session slot, so logging out in one tab never logs out the other.
 */
require_once __DIR__ . '/includes/auth.php';

for ($s = 0; $s <= MAX_SESSION_SLOT; $s++) {
    if (!isset($_COOKIE[slot_session_name($s)])) {
        header('Cache-Control: no-store');
        header('Location: ' . slot_base($s) . '/login.php?newtab=1');
        exit;
    }
}

// Every slot is in use: explain instead of sharing another tab's account.
start_secure_session();
$page_title = 'Too many accounts';
require __DIR__ . '/includes/header.php';
?>
<div class="text-center py-5">
    <div class="display-5 text-warning mb-3"><i class="bi bi-people"></i></div>
    <h1 class="h3">Too many accounts are open</h1>
    <p class="text-muted">Ten accounts are already logged in on this browser. Log out of one of them in another tab, then reload this page.</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
