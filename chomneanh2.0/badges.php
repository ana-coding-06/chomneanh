<?php
require_once __DIR__ . '/layout.php';
requireLogin();
initDB();
if (isset($_GET['logout'])) logoutUser();

$user = currentUser(); $uid = $user['id']; $pdo = getDB();
$stats = ['streak'=>userStreak($pdo,$uid),'completions'=>userTotalCompletions($pdo,$uid),'points'=>userPoints($pdo,$uid)];
$badges = $pdo->query('SELECT * FROM badges ORDER BY sort_order ASC')->fetchAll();
$earned = earned_badge_codes($pdo, $uid);

render_page_open('Badges - Chomneanh', 'badges', $user, $stats);
?>
<div class="mx-auto max-w-3xl px-4 py-6">
  <div class="anim-slideup rounded-3xl p-6 mb-5 relative overflow-hidden" style="background:#FFC800;box-shadow:0 5px 0 #E6A100;">
    <div style="position:absolute;right:-20px;top:-20px;width:130px;height:130px;border-radius:50%;background:rgba(255,255,255,.2);"></div>
    <h1 class="font-display text-2xl font-extrabold relative" style="color:#7A5C00;">Total Badges</h1>
    <p class="text-sm font-bold relative" style="color:#7A5C00;"><?= count($earned) ?> of <?= count($badges) ?> unlocked</p>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
    <?php foreach ($badges as $b):
      $has=in_array($b['code'],$earned,true); $bc=badge_colors($b['color']); ?>
      <div class="anim-slideup card3d flex flex-col items-center p-5 text-center" style="opacity:<?= $has?'1':'0.55' ?>;">
        <span style="width:64px;height:64px;border-radius:18px;background:<?= $has?$bc['bg']:'#EFEFEF' ?>;display:grid;place-items:center;margin-bottom:10px;">
          <?= img_or_dot(badge_img($b['code']), 44, 12, $b['title']) ?>
        </span>
        <p class="font-display font-extrabold text-sm" style="color:#3C3C3C;"><?= htmlspecialchars($b['title']) ?></p>
        <p class="text-xs font-semibold mt-1" style="color:#777;"><?= htmlspecialchars($b['description']) ?></p>
        <?php if ($has): ?><span class="mt-2 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full text-white" style="background:#58CC02;">Unlocked</span><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="h-6"></div>
</div>
<?php render_page_close(); ?>