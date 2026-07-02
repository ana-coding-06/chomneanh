# Chomneanh — Learn a skill a day

A playful micro-learning web app. Users pick a skill, follow step-by-step
instructions, complete a locked daily challenge, earn badges, build streaks,
and climb a leaderboard.

## Files

| File | Purpose |
|------|---------|
| `db.php` | DB connection + auto-creates all tables + seeds skills/steps/challenges/badges |
| `auth.php` | Register, login, logout, sessions, streak/points/badge logic |
| `api.php` | JSON API: bootstrap, toggle_step, complete_challenge, leaderboard |
| `theme.php` | Shared design tokens + brand mark for login/register |
| `login.php` | Login page |
| `register.php` | Sign-up page (with avatar colour picker) |
| `index.php` | Main app: dashboard, skill detail, badges, leaderboard, profile |
| `Dockerfile` | Railway deployment (php:8.2-cli + pdo_mysql) |
| `chomneanh.png` | (optional) your logo — drop it in and it's used automatically |

## Database tables (auto-created)

`users`, `skills`, `skill_steps_content`, `challenges`, `user_progress`,
`challenge_completions`, `badges`, `user_badges`. Skills, steps, challenges,
and badges are seeded automatically on first load — no SQL import needed.

## Run locally (XAMPP)

1. Create a database: `CREATE DATABASE chomneanh;`
2. In `db.php` set `define('USE_RAILWAY', false);` and fill the `LOCAL_*` values
   (XAMPP default user `root`, blank password; set `LOCAL_PORT` to your port — 3306 or 3307).
3. Put the folder in `C:\xampp\htdocs\chomneanh\`
4. Start Apache + MySQL, then open `http://localhost/chomneanh/register.php`

## Deploy on Railway

1. Push these files to a GitHub repo.
2. On Railway: New Project → Deploy from GitHub repo → pick the repo.
3. Add a MySQL service to the same project.
4. In `db.php` keep `define('USE_RAILWAY', true);`. The file already reads
   Railway's `MYSQL*` environment variables automatically; the hardcoded
   fallbacks are only used if the env vars are missing.
5. Under the web service → Settings → Networking → Generate Domain.
6. Open `https://your-app.up.railway.app/register.php`

The `Dockerfile` uses `php:8.2-cli` (not Apache) to avoid the "More than one
MPM loaded" crash, installs the `pdo_mysql` driver, and serves on `$PORT`.

## The step-lock rule

The "Mark today's challenge done" button is disabled until every step for that
skill is checked. This is enforced twice: visually in the UI, and again
server-side in `api.php` (`complete_challenge` counts the user's completed steps
vs the skill's total and refuses if they don't match), so it can't be bypassed.

## Points & badges

- Each challenge completion = 10 points, each badge = 25 points.
- Badges auto-award when conditions are met (first challenge, 3/7-day streak,
  5/10 challenges, 3 different skills) with a confetti + toast celebration.
- The leaderboard ranks all users by points and highlights your own row.
