<?php
// ════════════════════════════════════════════════════════════════════════════
//  layout.php — shared UI pieces for the pure-PHP pages
//  (sidebar, top bar, help widget, PNG-icon helper, data helpers)
// ════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/theme.php';

// ── PNG image with gray-circle fallback ─────────────────────────────────────
// Renders <img> if the file exists, else a gray circle placeholder.
function img_or_dot(string $path, int $size = 40, int $radius = 12, string $alt = ''): string {
    $full = __DIR__ . '/' . $path;
    if (is_file($full)) {
        return '<img src="'.htmlspecialchars($path).'" alt="'.htmlspecialchars($alt).'" '
             . 'style="width:'.$size.'px;height:'.$size.'px;border-radius:'.$radius.'px;object-fit:contain;flex-shrink:0;"/>';
    }
    return '<span aria-label="'.htmlspecialchars($alt).'" '
         . 'style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;background:#E5E5E5;display:inline-block;flex-shrink:0;"></span>';
}

// asset path builders
function skill_img($id)      { return "assets/skills/$id.png"; }
function step_img($id,$i)    { return "assets/steps/$id-".($i+1).".png"; }
function badge_img($code)    { return "assets/badges/$code.png"; }
function stat_img($name)     { return "assets/stats/$name.png"; }
function nav_img($name)      { return "assets/nav/$name.png"; }

// ── category colours ────────────────────────────────────────────────────────
function cat_colors(string $key): array {
    $C = [
        'cooking'   => ['bg'=>'#FFF1E8','fg'=>'#E8612C','sh'=>'#F3D9C8'],
        'art'       => ['bg'=>'#FDEBF4','fg'=>'#D6418A','sh'=>'#F3D2E4'],
        'language'  => ['bg'=>'#FFEBEC','fg'=>'#E0394B','sh'=>'#F3D0D3'],
        'practical' => ['bg'=>'#E9F8F0','fg'=>'#1E9E68','sh'=>'#CDEBDD'],
        'wellness'  => ['bg'=>'#E8F2FF','fg'=>'#2D7FD6','sh'=>'#CFE2F6'],
        'tech'      => ['bg'=>'#EFEBFE','fg'=>'#7C53E0','sh'=>'#DDD3F5'],
        'finance'   => ['bg'=>'#E8F8F0','fg'=>'#0E9F6E','sh'=>'#CCEEDD'],
        'fitness'   => ['bg'=>'#FFEEE8','fg'=>'#F2622E','sh'=>'#F5D8C9'],
        'learning'  => ['bg'=>'#EAF1FF','fg'=>'#3667D6','sh'=>'#D2DFF6'],
    ];
    return $C[$key] ?? $C['cooking'];
}
function badge_colors(string $key): array {
    $C = [
        'green'=>['bg'=>'#E7F8EF','fg'=>'#1E9E68'], 'orange'=>['bg'=>'#FFF1E5','fg'=>'#EA7317'],
        'violet'=>['bg'=>'#F0EBFE','fg'=>'#7C53E0'], 'red'=>['bg'=>'#FFEBEC','fg'=>'#E0394B'],
        'amber'=>['bg'=>'#FEF5E0','fg'=>'#D69A0E'], 'blue'=>['bg'=>'#E8F2FF','fg'=>'#2D7FD6'],
    ];
    return $C[$key] ?? $C['amber'];
}
function avatar_hex(string $key): string {
    $A = ['coral'=>'#FF4B4B','blue'=>'#1CB0F6','green'=>'#58CC02','violet'=>'#CE82FF','amber'=>'#FFC800','pink'=>'#EC4899'];
    return $A[$key] ?? '#FF4B4B';
}

// ── avatar circle with initials ─────────────────────────────────────────────
function avatar_html(string $name, string $avatar, int $size = 40): string {
    $hex = avatar_hex($avatar);
    $ini = strtoupper(substr($name, 0, 2));
    return '<span style="width:'.$size.'px;height:'.$size.'px;border-radius:'.(int)($size*0.3).'px;background:'.$hex.';color:#fff;'
         . 'display:grid;place-items:center;font-weight:900;font-size:'.(int)($size*0.4).'px;font-family:\'Baloo 2\',sans-serif;flex-shrink:0;">'
         . htmlspecialchars($ini).'</span>';
}

// ════════════════════════════════════════════════════════════════════════════
//  DATA HELPERS — load skills/steps/challenges/badges + a user's progress
// ════════════════════════════════════════════════════════════════════════════
function load_all_skills(PDO $pdo): array {
    $skills = $pdo->query('SELECT * FROM skills ORDER BY sort_order ASC')->fetchAll();
    $steps = [];
    foreach ($pdo->query('SELECT * FROM skill_steps_content ORDER BY skill_id, step_order ASC')->fetchAll() as $r) {
        $steps[$r['skill_id']][] = $r;
    }
    $chall = [];
    foreach ($pdo->query('SELECT * FROM challenges ORDER BY skill_id, id ASC')->fetchAll() as $r) {
        $chall[$r['skill_id']][] = $r['challenge_text'];
    }
    foreach ($skills as &$s) {
        $s['steps']      = $steps[$s['id']] ?? [];
        $s['challenges'] = $chall[$s['id']] ?? [];
    }
    unset($s);
    return $skills;
}

function load_user_progress(PDO $pdo, int $uid): array {
    $prog = [];
    $stmt = $pdo->prepare('SELECT skill_id, step_index FROM user_progress WHERE user_id = ?');
    $stmt->execute([$uid]);
    foreach ($stmt->fetchAll() as $r) $prog[$r['skill_id']]['steps'][] = (int)$r['step_index'];

    $stmt = $pdo->prepare('SELECT skill_id, completion_date FROM challenge_completions WHERE user_id = ? ORDER BY completion_date ASC');
    $stmt->execute([$uid]);
    foreach ($stmt->fetchAll() as $r) {
        $prog[$r['skill_id']]['dates'][] = $r['completion_date'];
        $prog[$r['skill_id']]['last']    = $r['completion_date'];
    }
    return $prog;
}

function earned_badge_codes(PDO $pdo, int $uid): array {
    $stmt = $pdo->prepare('SELECT b.code FROM user_badges ub JOIN badges b ON b.id = ub.badge_id WHERE ub.user_id = ?');
    $stmt->execute([$uid]);
    return array_map(fn($r) => $r['code'], $stmt->fetchAll());
}

// ════════════════════════════════════════════════════════════════════════════
//  PAGE CHROME — call render_page_open() at top, render_page_close() at bottom
// ════════════════════════════════════════════════════════════════════════════
function render_page_open(string $title, string $active, array $user, array $stats): void {
    $hasLogo = is_file(__DIR__ . '/chomneanh.png');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php renderHead($title); ?>
  <style>
    @keyframes slideup { 0%{transform:translateY(14px);opacity:0} 100%{transform:translateY(0);opacity:1} }
    @keyframes slidein { 0%{transform:translateX(-100%)} 100%{transform:translateX(0)} }
    .anim-slideup { animation:slideup .35s cubic-bezier(0.34,1.56,0.64,1) both; }
    .card-hover { transition:transform .12s cubic-bezier(0.34,1.56,0.64,1), box-shadow .12s ease; }
    .card-hover:hover { transform:translateY(-3px); box-shadow:0 6px 0 #E5E5E5; }
    .tap:active { transform:translateY(2px); }
    #mobileNav { display:none; }
    #navToggle:checked ~ #mobileNav { display:block; }
    @media (prefers-reduced-motion: reduce){ .anim-slideup,.card-hover{animation:none!important;transition:none!important} }
  </style>
</head>
<body style="background:#fff;">
<div class="min-h-screen flex">

  <?php // ── Desktop sidebar ─────────────────────────────────────────────── ?>
  <aside class="hidden md:flex flex-col w-60 shrink-0 p-4 sticky top-0 h-screen" style="border-right:2px solid #E5E5E5;">
    <?= sidebar_inner($active, $hasLogo) ?>
  </aside>

  <div class="flex-1 min-w-0">
    <?php // ── Top bar ──────────────────────────────────────────────────── ?>
    <div class="sticky top-0 z-40 bg-white" style="border-bottom:2px solid #E5E5E5;">
      <div class="flex items-center justify-between px-4 py-3">
        <div class="flex items-center gap-2">
          <label for="navToggle" class="md:hidden tap grid place-items-center rounded-xl cursor-pointer" style="width:38px;height:38px;border:2px solid #E5E5E5;" aria-label="Open menu">
            <i class="ti ti-menu-2" style="font-size:20px;color:#3C3C3C;"></i>
          </label>
          <span class="md:hidden font-display text-lg font-extrabold" style="color:#58CC02;">Chomneanh</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="flex items-center gap-1.5 rounded-full px-3 py-1.5" style="background:#FFF4D6;">
            <?= img_or_dot(stat_img('streak'), 18, 4, 'streak') ?>
            <span class="text-sm font-extrabold" style="color:#E6A100;"><?= (int)$stats['streak'] ?></span>
          </div>
          <div class="flex items-center gap-1.5 rounded-full px-3 py-1.5" style="background:#E8F2FF;">
            <?= img_or_dot(stat_img('points'), 18, 4, 'points') ?>
            <span class="text-sm font-extrabold" style="color:#1899D6;"><?= (int)$stats['points'] ?></span>
          </div>
          <a href="profile.php" class="tap ml-0.5" title="Your profile">
            <?= avatar_html($user['username'], $user['avatar'], 36) ?>
          </a>
        </div>
      </div>
    </div>

    <?php // ── Mobile drawer (toggled by the checkbox hack) ───────────────── ?>
    <input type="checkbox" id="navToggle" style="display:none;"/>
    <div id="mobileNav" class="md:hidden fixed inset-0 z-50">
      <label for="navToggle" style="position:absolute;inset:0;background:rgba(0,0,0,.4);"></label>
      <div class="absolute left-0 top-0 h-full w-64 bg-white p-4" style="animation:slidein .25s ease-out;border-right:2px solid #E5E5E5;">
        <?= sidebar_inner($active, $hasLogo) ?>
      </div>
    </div>

    <?php // page content follows ?>
<?php
}

function sidebar_inner(string $active, bool $hasLogo): string {
    $items = [
        ['home','Home','index.php'],
        ['leaderboard','Leaderboard','leaderboard.php'],
        ['badges','Badges','badges.php'],
        ['profile','Profile','profile.php'],
    ];
    ob_start(); ?>
    <div class="flex flex-col h-full">
      <a href="landing.php" class="flex items-center gap-2.5 px-2 mb-6">
        <?php if ($hasLogo): ?>
          <img src="chomneanh.png" alt="Chomneanh" style="height:34px;width:auto;object-fit:contain;"/>
        <?php else: ?>
          <span style="width:34px;height:34px;border-radius:10px;background:#58CC02;display:grid;place-items:center;box-shadow:0 3px 0 #58A700;"><i class="ti ti-flame" style="font-size:19px;color:#fff;"></i></span>
        <?php endif; ?>
        <span class="font-display text-xl font-extrabold" style="color:#58CC02;">CHOMNEANH</span>
      </a>
      <nav class="space-y-1.5 flex-1">
        <?php foreach ($items as [$key,$label,$href]):
          $on = $active === $key; ?>
          <a href="<?= $href ?>" class="tap w-full flex items-center gap-3 rounded-2xl px-3.5 py-3"
             style="background:<?= $on?'#DDF4C7':'transparent' ?>;border:2px solid <?= $on?'#B8E986':'transparent' ?>;">
            <?= img_or_dot(nav_img($key), 26, 8, $label) ?>
            <span class="font-display font-extrabold text-sm" style="color:<?= $on?'#4CA700':'#3C3C3C' ?>;"><?= $label ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
      <a href="index.php?logout=1" class="tap w-full flex items-center gap-3 rounded-2xl px-3.5 py-3 mt-2" style="border:2px solid #E5E5E5;">
        <?= img_or_dot(nav_img('logout'), 26, 8, 'Log out') ?>
        <span class="font-display font-extrabold text-sm" style="color:#FF4B4B;">LOG OUT</span>
      </a>
    </div>
    <?php
    return ob_get_clean();
}

function render_page_close(): void {
    // Help widget (pure HTML/CSS + tiny JS just for open/close + FAQ toggle + submit)
    ?>
    </div><!-- /flex-1 -->
  </div><!-- /min-h-screen flex -->

  <?php render_help_widget(); ?>

</body>
</html>
<?php
}

function render_help_widget(): void {
    $faqs = [
        ['How do streaks work?', 'Your streak counts how many days in a row you complete at least one daily challenge. Miss a day and it resets so try to do one everyday!'],
        ['How do I complete a challenge?', 'Select a skill, finish every step first. Once all steps are done, the green button unlocks. Tap it to earn points and keep your streak.'],
        ['How do badges work?', 'Badges unlock automatically when you hit milestones!'],
        ['How are points calculated?', 'You earn 10 points per challenge completed and 25 points per badge earned.'],
    ];
    ?>
    <input type="checkbox" id="helpToggle" style="display:none;"/>
    <label for="helpToggle" class="tap" style="position:fixed;right:20px;bottom:20px;width:58px;height:58px;border-radius:50%;background:#1CB0F6;box-shadow:0 4px 0 #1899D6;display:grid;place-items:center;cursor:pointer;z-index:60;">
      <i class="ti ti-help" style="font-size:28px;color:#fff;"></i>
    </label>

    <div id="helpPanel" style="display:none;position:fixed;right:20px;bottom:88px;width:min(360px,calc(100vw - 40px));z-index:60;">
      <div style="background:#fff;border:2px solid #E5E5E5;border-radius:20px;box-shadow:0 8px 0 #E5E5E5;overflow:hidden;">
        <div style="background:#1CB0F6;padding:16px 18px;">
          <p class="font-display text-lg font-extrabold" style="color:#fff;">Chat with us</p>
          <p class="text-xs font-semibold" style="color:rgba(255,255,255,.9);">Tap a question or send us a message</p>
        </div>
        <div style="max-height:60vh;overflow-y:auto;padding:16px;">
          <p class="text-xs font-extrabold uppercase tracking-wide mb-2" style="color:#AFAFAF;">FAQ</p>
          <div class="space-y-2 mb-5">
            <?php foreach ($faqs as $i=>[$q,$a]): ?>
              <div class="rounded-xl" style="border:2px solid #E5E5E5;">
                <input type="checkbox" id="faq<?= $i ?>" class="faqcb" style="display:none;"/>
                <label for="faq<?= $i ?>" class="tap w-full flex items-center justify-between gap-2 px-3.5 py-2.5 text-left cursor-pointer">
                  <span class="font-bold text-sm" style="color:#3C3C3C;"><?= htmlspecialchars($q) ?></span>
                  <i class="ti ti-chevron-down" style="font-size:16px;color:#AFAFAF;"></i>
                </label>
                <p class="faqans px-3.5 pb-3 text-sm font-semibold" style="color:#777;display:none;"><?= htmlspecialchars($a) ?></p>
              </div>
            <?php endforeach; ?>
          </div>

          <p class="text-xs font-extrabold uppercase tracking-wide mb-2" style="color:#AFAFAF;">Your comment matters to us!</p>
          <?php if (isset($_GET['msg']) && $_GET['msg']==='sent'): ?>
            <div class="rounded-xl px-3.5 py-3 text-sm font-bold" style="background:#E7F8EF;color:#1E9E68;">Thanks! Your message was sent.</div>
          <?php else: ?>
            <form method="POST" action="send_message.php">
              <input type="hidden" name="from" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>"/>
              <textarea name="body" rows="4" required placeholder="What's on your mind?"
                class="w-full rounded-xl border-2 border-swan bg-polar px-3.5 py-2.5 text-sm font-semibold text-ink placeholder-wolf focus:border-sky focus:bg-white focus:outline-none transition"></textarea>
              <button type="submit" class="btn3d btn-sky w-full py-2.5 text-sm mt-2">Send</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <style>
      #helpToggle:checked ~ #helpPanel { display:block !important; }
      .faqcb:checked + label + .faqans { display:block !important; }
    </style>
    <?php
}