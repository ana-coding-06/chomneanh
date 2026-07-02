<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/theme.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    initDB();
    $result = loginUser(trim($_POST['login'] ?? ''), trim($_POST['password'] ?? ''));
    if ($result['ok']) { header('Location: index.php'); exit; }
    $error = $result['error'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head><?php renderHead('Log in — Chomneanh'); ?></head>
<body class="min-h-screen bg-white flex flex-col">

  <header class="w-full">
    <div class="mx-auto max-w-6xl flex items-center justify-between px-5 py-4">
      <a href="landing.php" class="flex items-center gap-2.5">
        <?= brandMark(40) ?>
        <span class="font-display text-2xl font-extrabold" style="color:#58CC02">CHOMNEANH</span>
      </a>
    </div>
  </header>

  <main class="flex-1 flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-sm animate-popin">
      <h1 class="font-display text-2xl font-extrabold text-center mb-6" style="color:#3C3C3C">Start Now!</h1>

      <?php if ($error): ?>
        <div class="mb-5 flex items-start gap-2.5 rounded-2xl border-2 px-4 py-3 text-sm font-bold"
             style="border-color:#FFC1C1;background:#FFF0F0;color:#E63F3F">
          <i class="ti ti-alert-circle text-base mt-0.5"></i><span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate class="space-y-3.5">
        <input id="login" name="login" type="text" required autocomplete="username"
          value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" placeholder="Username or email"
          class="w-full rounded-2xl border-2 border-swan bg-polar px-4 py-3.5 text-sm font-semibold text-ink placeholder-wolf focus:border-sky focus:bg-white focus:outline-none transition"/>

        <input id="password" name="password" type="password" required autocomplete="current-password"
          placeholder="Password"
          class="w-full rounded-2xl border-2 border-swan bg-polar px-4 py-3.5 text-sm font-semibold text-ink placeholder-wolf focus:border-sky focus:bg-white focus:outline-none transition"/>

        <button type="submit" class="btn3d btn-grass w-full py-3.5 text-base mt-1">Log in</button>
      </form>

      <p class="mt-6 text-center text-sm font-bold text-wolf">
        Don't have an account?
        <a href="register.php" class="font-extrabold uppercase text-xs tracking-wide ml-1" style="color:#1CB0F6">Click here</a>
      </p>
    </div>
  </main>
</body>
</html>
