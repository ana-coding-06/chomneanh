<?php
require_once __DIR__ . '/layout.php';
requireLogin();
initDB();
if (isset($_GET['logout'])) logoutUser();

$user = currentUser(); $uid = $user['id']; $pdo = getDB();
$stats = ['streak'=>userStreak($pdo,$uid),'completions'=>userTotalCompletions($pdo,$uid),'points'=>userPoints($pdo,$uid)];
$badges = $pdo->query('SELECT * FROM badges ORDER BY sort_order ASC')->fetchAll();
$earned = earned_badge_codes($pdo, $uid);

render_page_open('Profile - Chomneanh', 'profile', $user, $stats);
?>
<div class="mx-auto max-w-3xl px-4 py-6">
  <div class="anim-slideup card3d p-6 text-center" style="border-radius:10px;">
    <div class="inline-block mb-3"><?= avatar_html($user['username'], $user['avatar'], 84) ?></div>
    <h1 class="font-display text-2xl font-extrabold" style="color:#3C3C3C;"><?= htmlspecialchars($user['username']) ?></h1>
    <p class="text-sm font-semibold mt-0.5" style="color:#AFAFAF;">Beginner</p>

    <div class="grid grid-cols-4 gap-2.5 mt-5">
      <?php
        $cards = [
          ['streak','#FFF4D6',$stats['streak'],'streak'],
          ['challenges','#E7F8EF',$stats['completions'],'done'],
          ['badges','#FEF5E0',count($earned),'badges'],
          ['points','#E8F2FF',$stats['points'],'points'],
        ];
        foreach ($cards as [$img,$bg,$val,$lbl]): ?>
          <div class="rounded-2xl p-3" style="background:<?= $bg ?>;">
            <div class="flex justify-center"><?= img_or_dot(stat_img($img),22,5,$lbl) ?></div>
            <div class="font-display text-xl font-extrabold mt-1" style="color:#3C3C3C;"><?= (int)$val ?></div>
            <div class="text-[11px] font-bold" style="color:#777;"><?= $lbl ?></div>
          </div>
      <?php endforeach; ?>
    </div>
  </div>

  <h2 class="font-display text-xl font-extrabold mt-6 mb-3 px-1" style="color:#3C3C3C;">Badge collection</h2>
  <div class="anim-slideup grid grid-cols-3 sm:grid-cols-6 gap-3">
    <?php foreach ($badges as $b):
      $has=in_array($b['code'],$earned,true); $bc=badge_colors($b['color']); ?>
      <div class="flex flex-col items-center rounded-2xl p-3 text-center"
           style="border:2px solid <?= $has?$bc['bg']:'#E5E5E5' ?>;background:<?= $has?'#fff':'#F7F7F7' ?>;<?= $has?'box-shadow:0 2px 0 '.$bc['bg'].';':'' ?>opacity:<?= $has?'1':'0.55' ?>;">
        <span style="width:48px;height:48px;border-radius:14px;background:<?= $has?$bc['bg']:'#EFEFEF' ?>;display:grid;place-items:center;margin-bottom:7px;">
          <?= img_or_dot(badge_img($b['code']),30,8,$b['title']) ?>
        </span>
        <span class="text-[11px] font-extrabold leading-tight" style="color:#777;"><?= htmlspecialchars($b['title']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-6">
    <a href="index.php?logout=1" class="btn3d w-full py-3.5 text-sm flex items-center justify-center gap-2" style="background:#fff;color:#FF4B4B;box-shadow:0 4px 0 #E5E5E5;border:2px solid #E5E5E5;">
      <i class="ti ti-logout" style="font-size:17px;"></i> Log out
    </a>
  </div>
  <div class="h-6"></div>
</div>
<?php render_page_close(); ?>