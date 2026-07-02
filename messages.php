<?php
// ════════════════════════════════════════════════════════════════════════════
//  messages.php — password-protected admin page to read help messages
// ════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/theme.php';

// ── CHANGE THIS PASSWORD ─────────────────────────────────────────────────────
define('ADMIN_PASSWORD', 'vina123');   // <-- edit this to your own password
// ─────────────────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) session_start();
initDB();

// handle logout of admin view
if (isset($_GET['adminout'])) { unset($_SESSION['is_admin']); header('Location: messages.php'); exit; }

// handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pass'])) {
    if (hash_equals(ADMIN_PASSWORD, $_POST['admin_pass'])) {
        $_SESSION['is_admin'] = true;
    } else {
        $error = 'Wrong password.';
    }
}

$isAdmin = !empty($_SESSION['is_admin']);
$rows = [];
if ($isAdmin) {
    $rows = getDB()->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head><?php renderHead('Admin - Chomneanh'); ?></head>
<body class="min-h-screen bg-white">

<?php if (!$isAdmin): ?>
  <!-- Password gate -->
  <div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm animate-popin">
      <div class="mb-6 text-center">
        <div class="inline-block mb-3"><?= brandMark(52) ?></div>
        <h1 class="font-display text-2xl font-extrabold" style="color:#3C3C3C">Admin access</h1>
        <p class="text-sm font-semibold text-wolf mt-1">Enter the password to view messages</p>
      </div>
      <?php if ($error): ?>
        <div class="mb-4 rounded-2xl border-2 px-4 py-3 text-sm font-bold" style="border-color:#FFC1C1;background:#FFF0F0;color:#E63F3F"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" class="space-y-3.5">
        <input type="password" name="admin_pass" required placeholder="Password"
          class="w-full rounded-2xl border-2 border-swan bg-polar px-4 py-3.5 text-sm font-semibold text-ink placeholder-wolf focus:border-sky focus:bg-white focus:outline-none transition"/>
        <button type="submit" class="btn3d btn-grass w-full py-3.5 text-base">Log in</button>
      </form>
      <p class="mt-5 text-center text-sm font-bold text-wolf"><a href="index.php" style="color:#1CB0F6">← Back to app</a></p>
    </div>
  </div>

<?php else: ?>
  <!-- Messages list -->
  <div class="mx-auto max-w-3xl px-4 py-6">
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-2.5">
        <?= brandMark(38) ?>
        <h1 class="font-display text-2xl font-extrabold" style="color:#3C3C3C">Help messages</h1>
      </div>
      <a href="?adminout=1" class="btn3d btn-white px-4 py-2 text-xs">Log out</a>
    </div>

    <p class="text-sm font-bold text-wolf mb-4"><?= count($rows) ?> message<?= count($rows)===1?'':'s' ?></p>

    <?php if (!$rows): ?>
      <div class="card3d p-8 text-center text-sm font-bold text-wolf">No messages yet.</div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($rows as $m): ?>
          <div class="card3d p-4">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <span class="font-display font-extrabold text-sm" style="color:#3C3C3C"><?= htmlspecialchars($m['username'] ?: 'Unknown') ?></span>
                <span class="text-xs font-semibold text-wolf"><?= htmlspecialchars($m['email'] ?: '') ?></span>
              </div>
              <span class="text-xs font-semibold text-wolf"><?= htmlspecialchars($m['created_at']) ?></span>
            </div>
            <p class="text-sm font-semibold" style="color:#3C3C3C;white-space:pre-wrap"><?= htmlspecialchars($m['body']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="mt-6 text-center"><a href="index.php" class="text-sm font-bold" style="color:#1CB0F6">← Back to app</a></p>
  </div>
<?php endif; ?>

</body>
</html>
