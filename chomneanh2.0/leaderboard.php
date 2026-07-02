<?php
require_once __DIR__ . '/layout.php';
requireLogin();
initDB();
if (isset($_GET['logout'])) logoutUser();

$user = currentUser(); $uid = $user['id']; $pdo = getDB();
$stats = ['streak'=>userStreak($pdo,$uid),'completions'=>userTotalCompletions($pdo,$uid),'points'=>userPoints($pdo,$uid)];

$rows = $pdo->query("
    SELECT u.id, u.username, u.avatar,
           (COALESCE(cc.cnt,0)*".POINTS_PER_COMPLETION." + COALESCE(ub.cnt,0)*".POINTS_PER_BADGE.") AS points,
           COALESCE(cc.cnt,0) AS completions, COALESCE(ub.cnt,0) AS badges
    FROM users u
    LEFT JOIN (SELECT user_id,COUNT(*) cnt FROM challenge_completions GROUP BY user_id) cc ON cc.user_id=u.id
    LEFT JOIN (SELECT user_id,COUNT(*) cnt FROM user_badges GROUP BY user_id) ub ON ub.user_id=u.id
    ORDER BY points DESC, u.created_at ASC
")->fetchAll();

$myRank=null;
foreach ($rows as $i=>$r) { if ((int)$r['id']===$uid) { $myRank=$i+1; break; } }

render_page_open('Leaderboard - Chomneanh', 'leaderboard', $user, $stats);

function medal_color($rank){ return $rank===1?'#FFC800':($rank===2?'#B8B8B8':($rank===3?'#E8923C':null)); }
?>
<div class="mx-auto max-w-3xl px-4 py-6">
  <div class="anim-slideup rounded-3xl p-6 text-white mb-5 relative overflow-hidden" style="background:#1CB0F6;box-shadow:0 5px 0 #1899D6;">
    <div style="position:absolute;right:-20px;top:-20px;width:130px;height:130px;border-radius:50%;background:rgba(255,255,255,.16);"></div>
    <h1 class="font-display text-2xl font-extrabold relative">Leaderboard</h1>
    <p class="text-sm font-semibold relative" style="color:rgba(255,255,255,.9);">10 points per challenge · 25 per badge</p>
  </div>

  <div class="anim-slideup card3d overflow-hidden">
    <?php foreach ($rows as $i=>$r):
      $rank=$i+1; $me=((int)$r['id']===$uid); $m=medal_color($rank); ?>
      <div class="flex items-center gap-3 px-4 py-3" style="<?= $i>0?'border-top:2px solid #F0F0F0;':'' ?><?= $me?'background:#F0FBE5;':'' ?>">
        <div class="w-8 text-center flex-shrink-0">
          <?php if ($m): ?><span style="display:inline-grid;place-items:center;width:28px;height:28px;border-radius:9px;background:<?= $m ?>;color:#fff;font-weight:900;font-size:14px;font-family:'Baloo 2';"><?= $rank ?></span>
          <?php else: ?><span class="font-extrabold text-sm" style="color:#AFAFAF;"><?= $rank ?></span><?php endif; ?>
        </div>
        <?= avatar_html($r['username'], $r['avatar'] ?? 'coral', 38) ?>
        <div class="min-w-0 flex-1">
          <p class="font-display font-extrabold text-sm truncate" style="color:#3C3C3C;"><?= htmlspecialchars($r['username']) ?><?php if($me):?><span class="ml-1.5 text-[10px] font-extrabold uppercase" style="color:#58CC02;">You</span><?php endif;?></p>
          <p class="text-xs font-bold" style="color:#AFAFAF;"><?= (int)$r['completions'] ?> done · <?= (int)$r['badges'] ?> badges</p>
        </div>
        <div class="flex items-center gap-1 flex-shrink-0"><?= img_or_dot(stat_img('points'),15,4,'') ?><span class="font-display font-extrabold text-sm" style="color:#3C3C3C;"><?= (int)$r['points'] ?></span></div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><div class="p-5 text-center text-sm font-bold" style="color:#AFAFAF;">No rankings yet — be the first!</div><?php endif; ?>
  </div>
  <div class="h-6"></div>
</div>
<?php render_page_close(); ?>