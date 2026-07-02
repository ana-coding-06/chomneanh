<?php
require_once __DIR__ . '/layout.php';
requireLogin();
initDB();
if (isset($_GET['logout'])) logoutUser();

$user = currentUser();
$uid  = $user['id'];
$pdo  = getDB();

$stats = [
    'streak'      => userStreak($pdo, $uid),
    'completions' => userTotalCompletions($pdo, $uid),
    'points'      => userPoints($pdo, $uid),
];
$skills   = load_all_skills($pdo);
$progress = load_user_progress($pdo, $uid);
$earned   = earned_badge_codes($pdo, $uid);
$badges   = $pdo->query('SELECT * FROM badges ORDER BY sort_order ASC')->fetchAll();

$today = date('Y-m-d');
$hour  = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$streakMsg = $stats['streak']===0 ? "Let's start a streak today!"
           : ($stats['streak']<3 ? "You're on a {$stats['streak']} day roll!"
           : "You're on fire! {$stats['streak']} days strong");

// today's featured skill
$todays = $skills[(int)date('j') % count($skills)];
$tdState = $progress[$todays['id']] ?? [];
$tdDone  = ($tdState['last'] ?? null) === $today;

// pct helper
function skill_pct($skill, $progress) {
    $done = count($progress[$skill['id']]['steps'] ?? []);
    $tot  = count($skill['steps']);
    return $tot ? (int)round($done/$tot*100) : 0;
}

render_page_open('Home - Chomneanh', 'home', $user, $stats);
?>

<div class="mx-auto max-w-4xl px-4 py-6 space-y-7">

  <!-- Hero -->
  <div class="anim-slideup rounded-3xl p-6 sm:p-7 text-white relative overflow-hidden" style="background:#58CC02;box-shadow:0 5px 0 #58A700;">
    <div style="position:absolute;right:-30px;top:-30px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.14);"></div>
    <p class="text-sm font-extrabold relative" style="color:rgba(255,255,255,.9);"><?= $greeting ?>, <?= htmlspecialchars($user['username']) ?>!</p>
    <h1 class="font-display text-2xl sm:text-3xl font-extrabold mt-1 leading-tight relative"><?= htmlspecialchars($streakMsg) ?></h1>
    <div class="mt-5 grid grid-cols-4 gap-2.5 relative">
      <?php
        $hero = [
          ['streak',     $stats['streak'],       'day streak'],
          ['challenges', $stats['completions'],  'challenges'],
          ['badges',     count($earned),         'badges'],
          ['points',     $stats['points'],       'points'],
        ];
        foreach ($hero as [$img,$val,$lbl]): ?>
          <div class="rounded-2xl px-2 py-2.5 text-center" style="background:rgba(255,255,255,.18);">
            <div class="flex justify-center mb-1"><?= img_or_dot(stat_img($img), 22, 5, $lbl) ?></div>
            <div class="text-xl sm:text-2xl font-extrabold font-display leading-none"><?= (int)$val ?></div>
            <div class="text-[10px] sm:text-xs mt-0.5 font-bold" style="color:rgba(255,255,255,.85);"><?= $lbl ?></div>
          </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Today's pick -->
  <section class="anim-slideup">
    <div class="mb-3 px-1"><h2 class="font-display text-xl font-extrabold" style="color:#3C3C3C;">Today special..</h2></div>
    <?php $tc = cat_colors($todays['color']); ?>
    <a href="skill.php?id=<?= urlencode($todays['id']) ?>" class="card-hover card3d tap w-full flex items-center gap-4 p-4 text-left">
      <span style="width:60px;height:60px;border-radius:18px;background:<?= $tc['bg'] ?>;box-shadow:0 4px 0 <?= $tc['sh'] ?>;display:grid;place-items:center;flex-shrink:0;">
        <?= img_or_dot(skill_img($todays['id']), 38, 10, $todays['title']) ?>
      </span>
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <p class="font-display font-extrabold truncate" style="color:#3C3C3C;"><?= htmlspecialchars($todays['title']) ?></p>
          <?php if ($tdDone): ?><span class="text-[11px] font-extrabold px-2 py-0.5 rounded-full text-white" style="background:#58CC02;">Done</span><?php endif; ?>
        </div>
        <p class="text-sm font-semibold truncate mt-0.5" style="color:#777;"><?= htmlspecialchars($todays['challenges'][$stats['completions'] % max(1,count($todays['challenges']))]) ?></p>
      </div>
      <i class="ti ti-chevron-right" style="font-size:24px;color:#CFCFCF;"></i>
    </a>
  </section>

  <!-- Skill library -->
  <section class="anim-slideup">
    <div class="mb-3 flex items-center justify-between px-1">
      <h2 class="font-display text-xl font-extrabold" style="color:#3C3C3C;">Pick a skill</h2>
      <span class="text-xs font-extrabold" style="color:#AFAFAF;"><?= count($skills) ?> skills</span>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <?php foreach ($skills as $skill):
        $c = cat_colors($skill['color']); $pct = skill_pct($skill, $progress); ?>
        <a href="skill.php?id=<?= urlencode($skill['id']) ?>" class="card-hover card3d tap flex flex-col p-3.5 text-left">
          <div class="mb-2.5 w-full flex items-center justify-center rounded-2xl py-4" style="background:<?= $c['bg'] ?>;">
            <?= img_or_dot(skill_img($skill['id']), 44, 12, $skill['title']) ?>
          </div>
          <p class="font-display font-extrabold text-sm leading-tight line-clamp-2" style="color:#3C3C3C;"><?= htmlspecialchars($skill['title']) ?></p>
          <div class="mt-2 flex flex-wrap gap-1.5">
            <span class="inline-flex items-center gap-1 text-[11px] font-extrabold rounded-full px-2 py-0.5" style="background:#F0F0F0;color:#777;"><i class="ti ti-clock" style="font-size:12px;"></i><?= (int)$skill['minutes'] ?>m</span>
            <span class="text-[11px] font-extrabold rounded-full px-2 py-0.5" style="background:#F0F0F0;color:#777;"><?= htmlspecialchars($skill['difficulty']) ?></span>
          </div>
          <?php if ($pct>0): ?>
            <div class="mt-2.5" style="height:8px;background:#E5E5E5;border-radius:99px;overflow:hidden;">
              <div style="height:100%;width:<?= $pct ?>%;background:#58CC02;border-radius:99px;"></div>
            </div>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Badges preview -->
  <section class="anim-slideup">
    <div class="mb-3 flex items-center justify-between px-1">
      <h2 class="font-display text-xl font-extrabold" style="color:#3C3C3C;">Total Badges</h2>
      <a href="badges.php" class="text-xs font-extrabold uppercase tracking-wide" style="color:#1CB0F6;">See all</a>
    </div>
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
      <?php foreach ($badges as $b):
        $has = in_array($b['code'], $earned, true); $bc = badge_colors($b['color']); ?>
        <div class="flex flex-col items-center rounded-2xl p-3 text-center"
             style="border:2px solid <?= $has?$bc['bg']:'#E5E5E5' ?>;background:<?= $has?'#fff':'#F7F7F7' ?>;<?= $has?'box-shadow:0 2px 0 '.$bc['bg'].';':'' ?>opacity:<?= $has?'1':'0.55' ?>;">
          <span style="width:48px;height:48px;border-radius:14px;background:<?= $has?$bc['bg']:'#EFEFEF' ?>;display:grid;place-items:center;margin-bottom:7px;">
            <?= img_or_dot(badge_img($b['code']), 30, 8, $b['title']) ?>
          </span>
          <span class="text-[11px] font-extrabold leading-tight" style="color:#777;"><?= htmlspecialchars($b['title']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <footer class="pt-2 pb-4 text-center text-xs font-bold" style="color:#BFBFBF;">@ 2026 CHOMNEANH | All Rights Reserved.</footer>
</div>

<?php render_page_close(); ?>