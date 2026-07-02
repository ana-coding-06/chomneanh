<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/theme.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    initDB();
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if ($password !== $confirm) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        $result = registerUser($username, $email, $password, 'coral');
        if ($result['ok']) {
            loginUser($email, $password);
            header('Location: index.php');
            exit;
        }
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><?php renderHead('Sign up - Chomneanh'); ?></head>
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
      <h1 class="font-display text-2xl font-extrabold text-center mb-1.5" style="color:#3C3C3C">Create Your Account</h1>
      <p class="text-center text-sm font-semibold text-wolf mb-6">Your journey begins here!</p>

      <?php if ($error): ?>
        <div class="mb-5 flex items-start gap-2.5 rounded-2xl border-2 px-4 py-3 text-sm font-bold"
             style="border-color:#FFC1C1;background:#FFF0F0;color:#E63F3F">
          <i class="ti ti-alert-circle text-base mt-0.5"></i><span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate class="space-y-3.5">
        <input id="username" name="username" type="text" required autocomplete="username"
          value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Username"
          class="w-full rounded-2xl border-2 border-swan bg-polar px-4 py-3.5 text-sm font-semibold text-ink placeholder-wolf focus:border-sky focus:bg-white focus:outline-none transition"/>

        <input id="email" name="email" type="email" required autocomplete="email"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="Email"
          class="w-full rounded-2xl border-2 border-swan bg-polar px-4 py-3.5 text-sm font-semibold text-ink placeholder-wolf focus:border-sky focus:bg-white focus:outline-none transition"/>

        <input id="password" name="password" type="password" required autocomplete="new-password"
          placeholder="Password"
          class="w-full rounded-2xl border-2 border-swan bg-polar px-4 py-3.5 text-sm font-semibold text-ink placeholder-wolf focus:border-sky focus:bg-white focus:outline-none transition"/>

        <input id="confirm" name="confirm" type="password" required autocomplete="new-password"
          placeholder="Confirm password"
          class="w-full rounded-2xl border-2 border-swan bg-polar px-4 py-3.5 text-sm font-semibold text-ink placeholder-wolf focus:border-sky focus:bg-white focus:outline-none transition"/>

        <button type="submit" class="btn3d btn-grass w-full py-3.5 text-base mt-1">Create account</button>
      </form>

      <p class="mt-6 text-center text-sm font-bold text-wolf">
        Already have an account?
        <a href="login.php" class="font-extrabold uppercase text-xs tracking-wide ml-1" style="color:#1CB0F6">Log in</a>
      </p>
    </div>
  </main>
</body>
</html>
