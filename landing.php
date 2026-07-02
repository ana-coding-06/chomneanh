<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/theme.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head><?php renderHead('CHOMNEANH'); ?></head>
<body class="min-h-screen bg-white flex flex-col">

  <!-- Top bar -->
  <header class="w-full">
    <div class="mx-auto max-w-6xl flex items-center justify-between px-5 py-4">
      <div class="flex items-center gap-2.5">
        <?= brandMark(40) ?>
        <span class="font-display text-2xl font-extrabold" style="color:#58CC02">CHOMNEANH</span>
      </div>
      <a href="messages.php" class="text-xs sm:text-sm font-extrabold uppercase tracking-wide" style="color:#1CB0F6">Admin Panel</a>
    </div>
  </header>

  <!-- Hero -->
  <main class="flex-1 flex items-center">
    <div class="mx-auto max-w-6xl w-full px-5 py-10 grid md:grid-cols-2 gap-10 items-center">

      <!-- Illustration: friendly cluster of skill icon tiles -->
      <div class="order-1 md:order-none flex justify-center">
        <div class="relative" style="width:300px;height:300px;">
          <!-- floating colorful skill tiles -->
          <span class="absolute animate-floaty" style="left:90px;top:20px;animation-delay:0s">
            <span style="display:grid;place-items:center;width:96px;height:96px;border-radius:28px;background:#58CC02;box-shadow:0 6px 0 #58A700;">
              <i class="ti ti-flame" style="font-size:48px;color:#fff"></i></span>
          </span>
          <span class="absolute animate-floaty" style="left:0;top:90px;animation-delay:.6s">
            <span style="display:grid;place-items:center;width:78px;height:78px;border-radius:22px;background:#1CB0F6;box-shadow:0 6px 0 #1899D6;">
              <i class="ti ti-palette" style="font-size:38px;color:#fff"></i></span>
          </span>
          <span class="absolute animate-floaty" style="right:0;top:70px;animation-delay:1.1s">
            <span style="display:grid;place-items:center;width:82px;height:82px;border-radius:24px;background:#FFC800;box-shadow:0 6px 0 #E6A100;">
              <i class="ti ti-coffee" style="font-size:40px;color:#fff"></i></span>
          </span>
          <span class="absolute animate-floaty" style="left:40px;bottom:30px;animation-delay:.3s">
            <span style="display:grid;place-items:center;width:80px;height:80px;border-radius:24px;background:#CE82FF;box-shadow:0 6px 0 #A560E8;">
              <i class="ti ti-barbell" style="font-size:40px;color:#fff"></i></span>
          </span>
          <span class="absolute animate-floaty" style="right:30px;bottom:20px;animation-delay:.9s">
            <span style="display:grid;place-items:center;width:88px;height:88px;border-radius:26px;background:#FF4B4B;box-shadow:0 6px 0 #E63F3F;">
              <i class="ti ti-language" style="font-size:42px;color:#fff"></i></span>
          </span>
        </div>
      </div>

      <!-- Headline + buttons -->
      <div class="text-center md:text-left">
        <h1 class="font-display text-3xl sm:text3xl font-extrabold leading-tight" style="color:#3C3C3C">
          Build your skills with chomneanh starting today!
        </h1>
        <div class="mt-8 flex flex-col gap-3.5 max-w-sm mx-auto md:mx-0">
          <a href="register.php" class="btn3d btn-grass py-3.5 text-center text-base">Get started!</a>
          <a href="login.php" class="btn3d btn-white py-3.5 text-center text-base">I already have an account</a>
        </div>
      </div>
    </div>
  </main>

  <!-- Bottom category strip -->
  <footer class="border-t-2 border-swan">
    <div class="mx-auto max-w-6xl px-5 py-5 flex flex-wrap items-center justify-center gap-x-7 gap-y-3">
      <?php
        // Each: [file-name-in-assets/categories, Label]
        $cats = [
          ['cooking','Cooking'], ['art','Art'], ['language','Language'],
          ['fitness','Fitness'], ['wellness','Wellness'], ['finance','Finance'],
          ['tech','Tech'], ['learning','Learning'],
        ];
        foreach ($cats as [$file,$label]):
          $path = "assets/categories/$file.png";
          $exists = is_file(__DIR__ . '/' . $path); ?>
          <div class="flex items-center gap-2">
            <?php if ($exists): ?>
              <img src="<?= $path ?>" alt="<?= $label ?>" style="width:24px;height:24px;object-fit:contain;"/>
            <?php else: ?>
              <span style="width:24px;height:24px;border-radius:50%;background:#E5E5E5;display:inline-block;"></span>
            <?php endif; ?>
            <span class="text-xs font-extrabold uppercase tracking-wide" style="color:#777"><?= $label ?></span>
          </div>
      <?php endforeach; ?>
    </div>
  </footer>
</body>
</html>