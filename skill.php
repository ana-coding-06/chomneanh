<?php
require_once __DIR__ . '/layout.php';
requireLogin();
initDB();
if (isset($_GET['logout'])) logoutUser();

$user = currentUser();
$uid  = $user['id'];
$pdo  = getDB();

$sid = $_GET['id'] ?? '';
$skill = null;
foreach (load_all_skills($pdo) as $s) { if ($s['id'] === $sid) { $skill = $s; break; } }
if (!$skill) { header('Location: index.php'); exit; }

$today = date('Y-m-d');

// ── Handle POST actions (toggle step / complete challenge) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_step') {
        $i = (int)($_POST['step_index'] ?? -1);
        $checked = (int)($_POST['checked'] ?? 1);
        if ($i >= 0) {
            if ($checked) {
                $pdo->prepare('INSERT IGNORE INTO user_progress (user_id, skill_id, step_index) VALUES (?,?,?)')->execute([$uid,$sid,$i]);
            } else {
                $pdo->prepare('DELETE FROM user_progress WHERE user_id=? AND skill_id=? AND step_index=?')->execute([$uid,$sid,$i]);
            }
        }
    } elseif ($action === 'complete_challenge') {
        // server-side step-lock
        $tot = count($skill['steps']);
        $stmt = $pdo->prepare('SELECT COUNT(*) c FROM user_progress WHERE user_id=? AND skill_id=?');
        $stmt->execute([$uid,$sid]);
        $done = (int)$stmt->fetch()['c'];
        if ($tot > 0 && $done >= $tot) {
            $pdo->prepare('INSERT IGNORE INTO challenge_completions (user_id, skill_id, completion_date) VALUES (?,?,?)')->execute([$uid,$sid,$today]);
            evaluateBadges($pdo, $uid);
            header('Location: skill.php?id='.urlencode($sid).'&done=1'); exit;
        }
    }
    header('Location: skill.php?id='.urlencode($sid)); exit;
}

// ── Load current state ──────────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT step_index FROM user_progress WHERE user_id=? AND skill_id=?');
$stmt->execute([$uid,$sid]);
$completed = array_map(fn($r)=>(int)$r['step_index'], $stmt->fetchAll());

$stmt = $pdo->prepare('SELECT completion_date FROM challenge_completions WHERE user_id=? AND skill_id=? ORDER BY completion_date ASC');
$stmt->execute([$uid,$sid]);
$dates = array_map(fn($r)=>$r['completion_date'], $stmt->fetchAll());

$totalSteps  = count($skill['steps']);
$pct         = $totalSteps ? (int)round(count($completed)/$totalSteps*100) : 0;
$doneToday   = in_array($today, $dates, true);
$allStepsDone= count($completed) >= $totalSteps;
$challengeIdx= count($dates) % max(1,count($skill['challenges']));
$challenge   = $skill['challenges'][$challengeIdx] ?? '';
$c           = cat_colors($skill['color']);

$stats = ['streak'=>userStreak($pdo,$uid),'completions'=>userTotalCompletions($pdo,$uid),'points'=>userPoints($pdo,$uid)];

render_page_open(htmlspecialchars($skill['title']).' - Chomneanh', 'home', $user, $stats);
?>

<div class="mx-auto max-w-3xl px-4 py-5">
  <a href="index.php" class="mb-4 inline-flex items-center gap-1 text-sm font-extrabold tap" style="color:#777;"><i class="ti ti-chevron-left" style="font-size:18px;"></i> Back</a>

  <!-- Header -->
  <div class="anim-slideup rounded-3xl p-6 text-white relative overflow-hidden" style="background:<?= $c['fg'] ?>;box-shadow:0 5px 0 <?= $c['sh'] ?>;">
    <div style="position:absolute;right:-20px;top:-20px;width:130px;height:130px;border-radius:50%;background:rgba(255,255,255,.16);"></div>
    <div class="flex items-start justify-between relative">
      <span style="width:66px;height:66px;border-radius:18px;background:rgba(255,255,255,.25);display:grid;place-items:center;">
        <?= img_or_dot(skill_img($skill['id']), 40, 11, $skill['title']) ?>
      </span>
      <div class="flex flex-col items-end gap-1.5">
        <span class="inline-flex items-center gap-1 text-xs font-extrabold rounded-full px-2.5 py-1" style="background:rgba(255,255,255,.22);"><i class="ti ti-clock" style="font-size:13px;"></i><?= (int)$skill['minutes'] ?> min/day</span>
        <span class="text-xs font-extrabold rounded-full px-2.5 py-1" style="background:rgba(255,255,255,.22);"><?= htmlspecialchars($skill['difficulty']) ?></span>
      </div>
    </div>
    <h1 class="font-display text-2xl font-extrabold mt-3 relative"><?= htmlspecialchars($skill['title']) ?></h1>
    <p class="text-sm font-semibold mt-1 relative" style="color:rgba(255,255,255,.9);"><?= htmlspecialchars($skill['description']) ?></p>
    <div class="mt-4 relative">
      <div class="flex justify-between text-xs font-extrabold mb-1.5" style="color:rgba(255,255,255,.9);"><span>Lesson progress</span><span><?= $pct ?>%</span></div>
      <div style="height:12px;background:rgba(255,255,255,.3);border-radius:99px;overflow:hidden;"><div style="height:100%;width:<?= $pct ?>%;background:#fff;border-radius:99px;"></div></div>
    </div>
  </div>

  <!-- Steps -->
  <h2 class="font-display text-xl font-extrabold mt-6 mb-3 px-1" style="color:#3C3C3C;">Step by step</h2>
  <div class="space-y-2.5">
    <?php foreach ($skill['steps'] as $i=>$step):
      $done = in_array($i, $completed, true); ?>
      <div class="anim-slideup flex items-start gap-3.5 rounded-2xl p-4"
           style="border:2px solid <?= $done?$c['sh']:'#E5E5E5' ?>;background:<?= $done?$c['bg']:'#fff' ?>;box-shadow:0 2px 0 <?= $done?$c['sh']:'#E5E5E5' ?>;">
        <span style="width:46px;height:46px;border-radius:14px;background:<?= $done?$c['fg']:'#F0F0F0' ?>;display:grid;place-items:center;flex-shrink:0;font-family:'Baloo 2',sans-serif;font-weight:800;font-size:22px;color:<?= $done?'#fff':'#AFAFAF' ?>;">
          <?= $i+1 ?>
        </span>
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <p class="font-display font-extrabold" style="color:#3C3C3C;"><?= htmlspecialchars($step['title']) ?></p>
          </div>
          <p class="text-sm font-semibold mt-1" style="color:#777;"><?= htmlspecialchars($step['detail']) ?></p>
        </div>
        <form method="POST" style="flex-shrink:0;">
          <input type="hidden" name="action" value="toggle_step"/>
          <input type="hidden" name="step_index" value="<?= $i ?>"/>
          <input type="hidden" name="checked" value="<?= $done?0:1 ?>"/>
          <button type="submit" class="tap grid place-items-center rounded-full" aria-label="<?= $done?'Uncheck':'Check' ?> step"
            style="width:36px;height:36px;border:2px solid <?= $done?$c['fg']:'#D0D0D0' ?>;background:<?= $done?$c['fg']:'transparent' ?>;cursor:pointer;">
            <i class="ti ti-check" style="font-size:19px;color:<?= $done?'#fff':'transparent' ?>;"></i>
          </button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Daily challenge -->
  <h2 class="font-display text-xl font-extrabold mt-6 mb-3 px-1" style="color:#3C3C3C;">Daily challenge</h2>
  <div class="anim-slideup rounded-2xl p-5" style="border:2px solid <?= $doneToday?$c['sh']:'#E5E5E5' ?>;background:<?= $doneToday?$c['bg']:'#fff' ?>;box-shadow:0 2px 0 <?= $doneToday?$c['sh']:'#E5E5E5' ?>;">
    <div class="flex items-start gap-3">
      <span style="width:44px;height:44px;border-radius:13px;background:<?= $c['bg'] ?>;display:grid;place-items:center;flex-shrink:0;"><i class="ti ti-target" style="font-size:23px;color:<?= $c['fg'] ?>;"></i></span>
      <p class="text-sm font-bold pt-1.5" style="color:#3C3C3C;"><?= htmlspecialchars($challenge) ?></p>
    </div>

    <?php if (!$doneToday && !$allStepsDone): ?>
      <div class="mt-4 flex items-center gap-2.5 rounded-2xl px-3.5 py-3" style="background:#F7F7F7;border:2px dashed #D8D8D8;">
        <i class="ti ti-lock" style="font-size:18px;color:#AFAFAF;"></i>
        <p class="text-xs font-bold" style="color:#777;">Finish <span style="color:#3C3C3C;">all <?= $totalSteps ?> steps</span> above to unlock <span style="color:#AFAFAF;">(<?= count($completed) ?>/<?= $totalSteps ?>)</span></p>
      </div>
    <?php endif; ?>

    <?php if ($doneToday): ?>
      <button disabled class="btn3d btn-disabled w-full py-3.5 text-sm mt-4 flex items-center justify-center gap-2" style="background:#58CC02;color:#fff;box-shadow:0 4px 0 #58A700;">
        <i class="ti ti-circle-check" style="font-size:18px;"></i> Completed today — nice work!
      </button>
    <?php elseif (!$allStepsDone): ?>
      <button disabled class="btn3d btn-disabled w-full py-3.5 text-sm mt-4 flex items-center justify-center gap-2">
        <i class="ti ti-lock" style="font-size:16px;"></i> Complete the steps first
      </button>
    <?php else: ?>
      <form method="POST" class="mt-4">
        <input type="hidden" name="action" value="complete_challenge"/>
        <button type="submit" class="btn3d btn-grass w-full py-3.5 text-sm flex items-center justify-center gap-2">
          <i class="ti ti-circle-check" style="font-size:18px;"></i> Mark today's challenge done
        </button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Calendar -->
  <h2 class="font-display text-xl font-extrabold mt-6 mb-3 px-1" style="color:#3C3C3C;">Last 2 weeks</h2>
  <div class="anim-slideup card3d p-4">
    <div class="flex justify-between gap-1">
      <?php for ($k=13;$k>=0;$k--):
        $d = date('Y-m-d', strtotime("-$k day"));
        $dd = in_array($d, $dates, true); $isT = $d===$today; ?>
        <div class="flex-1 flex flex-col items-center">
          <div class="grid place-items-center rounded-xl text-xs font-extrabold"
               style="width:32px;height:32px;background:<?= $dd?$c['fg']:'#F0F0F0' ?>;color:<?= $dd?'#fff':'#AFAFAF' ?>;<?= $isT?'outline:2px solid '.$c['fg'].';outline-offset:2px;':'' ?>">
            <?= $dd ? '<i class="ti ti-check" style="font-size:14px;"></i>' : (int)date('j', strtotime($d)) ?>
          </div>
        </div>
      <?php endfor; ?>
    </div>
    <p class="text-center text-xs font-bold mt-3" style="color:#AFAFAF;"><?= count($dates) ?> day<?= count($dates)===1?'':'s' ?> practiced on this skill</p>
  </div>
  <div class="h-6"></div>
</div>

<?php render_page_close(); ?>
