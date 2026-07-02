<?php
// ════════════════════════════════════════════════════════════════════════════
//  auth.php — Sessions, authentication, and shared scoring/badge helpers
// ════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: landing.php');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'       => $_SESSION['user_id']  ?? 0,
        'username' => $_SESSION['username'] ?? '',
        'email'    => $_SESSION['email']    ?? '',
        'avatar'   => $_SESSION['avatar']   ?? 'coral',
    ];
}

function registerUser(string $username, string $email, string $password, string $avatar = 'coral'): array {
    $pdo = getDB();

    if (strlen($username) < 3 || strlen($username) > 50)
        return ['ok' => false, 'error' => 'Username must be 3–50 characters.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['ok' => false, 'error' => 'That email address doesn\'t look right.'];
    if (strlen($password) < 1)
        return ['ok' => false, 'error' => 'Please enter a password.'];

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch())
        return ['ok' => false, 'error' => 'That username or email is already taken.'];

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO users (username, email, password, avatar) VALUES (?, ?, ?, ?)')
        ->execute([$username, $email, $hash, $avatar]);

    return ['ok' => true];
}

function loginUser(string $login, string $password): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ?');
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password']))
        return ['ok' => false, 'error' => 'Incorrect username/email or password.'];

    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['avatar']   = $user['avatar'] ?? 'coral';
    return ['ok' => true];
}

function logoutUser(): void {
    session_destroy();
    header('Location: landing.php');
    exit;
}

// ════════════════════════════════════════════════════════════════════════════
//  SCORING + STREAK + BADGE HELPERS (shared by api.php and pages)
// ════════════════════════════════════════════════════════════════════════════

// Points formula: each challenge completion = 10 pts, each badge = 25 pts.
define('POINTS_PER_COMPLETION', 10);
define('POINTS_PER_BADGE', 25);

function userTotalCompletions(PDO $pdo, int $uid): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM challenge_completions WHERE user_id = ?');
    $stmt->execute([$uid]);
    return (int)$stmt->fetch()['c'];
}

function userDistinctSkills(PDO $pdo, int $uid): int {
    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT skill_id) AS c FROM challenge_completions WHERE user_id = ?');
    $stmt->execute([$uid]);
    return (int)$stmt->fetch()['c'];
}

// Consecutive-day streak across all skills, counting up to today.
function userStreak(PDO $pdo, int $uid): int {
    $stmt = $pdo->prepare('SELECT DISTINCT completion_date FROM challenge_completions WHERE user_id = ?');
    $stmt->execute([$uid]);
    $set = [];
    foreach ($stmt->fetchAll() as $r) $set[$r['completion_date']] = true;
    if (!$set) return 0;

    $streak = 0;
    $cursor = new DateTime('today');
    if (!isset($set[$cursor->format('Y-m-d')])) {
        $cursor->modify('-1 day'); // allow streak to count up to yesterday
    }
    while (isset($set[$cursor->format('Y-m-d')])) {
        $streak++;
        $cursor->modify('-1 day');
    }
    return $streak;
}

function userBadgeCount(PDO $pdo, int $uid): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM user_badges WHERE user_id = ?');
    $stmt->execute([$uid]);
    return (int)$stmt->fetch()['c'];
}

function userPoints(PDO $pdo, int $uid): int {
    return userTotalCompletions($pdo, $uid) * POINTS_PER_COMPLETION
         + userBadgeCount($pdo, $uid)      * POINTS_PER_BADGE;
}

// Checks all badge requirements and awards any newly-earned badges.
// Returns an array of badge rows that were newly awarded (for confetti / toasts).
function evaluateBadges(PDO $pdo, int $uid): array {
    $completions = userTotalCompletions($pdo, $uid);
    $streak      = userStreak($pdo, $uid);
    $distinct    = userDistinctSkills($pdo, $uid);

    $already = [];
    $stmt = $pdo->prepare('SELECT badge_id FROM user_badges WHERE user_id = ?');
    $stmt->execute([$uid]);
    foreach ($stmt->fetchAll() as $r) $already[(int)$r['badge_id']] = true;

    $newlyEarned = [];
    foreach ($pdo->query('SELECT * FROM badges')->fetchAll() as $badge) {
        if (isset($already[(int)$badge['id']])) continue;

        $met = false;
        switch ($badge['requirement_type']) {
            case 'total_completions': $met = $completions >= (int)$badge['requirement_value']; break;
            case 'streak':            $met = $streak      >= (int)$badge['requirement_value']; break;
            case 'distinct_skills':   $met = $distinct    >= (int)$badge['requirement_value']; break;
        }
        if ($met) {
            $ins = $pdo->prepare('INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)');
            $ins->execute([$uid, (int)$badge['id']]);
            if ($ins->rowCount() > 0) $newlyEarned[] = $badge;
        }
    }
    return $newlyEarned;
}
