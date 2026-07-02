<?php
// Handles the "Get Help" message form, then returns to the page it came from.
require_once __DIR__ . '/auth.php';
requireLogin();
initDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim($_POST['body'] ?? '');
    if ($body !== '') {
        if (mb_strlen($body) > 2000) $body = mb_substr($body, 0, 2000);
        $u = currentUser();
        getDB()->prepare('INSERT INTO messages (user_id, username, email, body) VALUES (?,?,?,?)')
               ->execute([$u['id'], $u['username'], $u['email'], $body]);
    }
}
// go back to where they were, with a success flag
$from = $_POST['from'] ?? 'index.php';
$from = strtok($from, '?');                 // strip old query
header('Location: ' . $from . '?msg=sent');
exit;