<?php
// ════════════════════════════════════════════════════════════════════════════
//  db.php — Database connection, schema, and seed data for Chomneanh
// ════════════════════════════════════════════════════════════════════════════
//
//  HOW TO SWITCH BETWEEN LOCAL (XAMPP) AND RAILWAY:
//  - Local XAMPP: set USE_RAILWAY to false and fill the LOCAL_* constants.
//  - Railway:     set USE_RAILWAY to true (or set the env vars on Railway).
//    Railway automatically provides MYSQLHOST, MYSQLPORT, etc. as env vars,
//    so on Railway you usually don't need to touch anything.
// ════════════════════════════════════════════════════════════════════════════

// Flip this to false when running on local XAMPP.
define('USE_RAILWAY', true);

// ---- Local XAMPP credentials -------------------------------------------------
define('LOCAL_HOST', '127.0.0.1');
define('LOCAL_PORT', '3307');      // XAMPP default is 3306 (yours may be 3307)
define('LOCAL_USER', 'root');
define('LOCAL_PASS', '');
define('LOCAL_NAME', 'chomneanh');

// ---- Railway credentials -----------------------------------------------------
// These read from Railway's environment variables first, with a manual fallback.
define('RW_HOST', getenv('MYSQLHOST')     ?: 'reseau.proxy.rlwy.net');
define('RW_PORT', getenv('MYSQLPORT')     ?: '27525');
define('RW_USER', getenv('MYSQLUSER')     ?: 'root');
define('RW_PASS', getenv('MYSQLPASSWORD') ?: 'KrhzCBogMmiIOvpBXVjTCBsGelDJIodA');
define('RW_NAME', getenv('MYSQLDATABASE') ?: 'railway');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    if (USE_RAILWAY) {
        $host = RW_HOST; $port = RW_PORT; $user = RW_USER; $pass = RW_PASS; $name = RW_NAME;
    } else {
        $host = LOCAL_HOST; $port = LOCAL_PORT; $user = LOCAL_USER; $pass = LOCAL_PASS; $name = LOCAL_NAME;
    }

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
    }
    return $pdo;
}

// ════════════════════════════════════════════════════════════════════════════
//  initDB() — creates all tables (if missing) and seeds default content once.
// ════════════════════════════════════════════════════════════════════════════
function initDB(): void {
    $pdo = getDB();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            username   VARCHAR(50)  NOT NULL UNIQUE,
            email      VARCHAR(150) NOT NULL UNIQUE,
            password   VARCHAR(255) NOT NULL,
            avatar     VARCHAR(20)  DEFAULT 'coral',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS skills (
            id          VARCHAR(40) PRIMARY KEY,
            title       VARCHAR(120) NOT NULL,
            description TEXT,
            category    VARCHAR(40),
            difficulty  VARCHAR(20),
            minutes     INT,
            icon        VARCHAR(40),
            color       VARCHAR(20),
            sort_order  INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS skill_steps_content (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            skill_id   VARCHAR(40) NOT NULL,
            step_order INT NOT NULL,
            icon       VARCHAR(40),
            title      VARCHAR(120),
            detail     TEXT,
            UNIQUE KEY uq_skill_step (skill_id, step_order),
            FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS challenges (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            skill_id       VARCHAR(40) NOT NULL,
            challenge_text TEXT,
            FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_progress (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            user_id      INT NOT NULL,
            skill_id     VARCHAR(40) NOT NULL,
            step_index   INT NOT NULL,
            completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_skill_step (user_id, skill_id, step_index),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS challenge_completions (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            user_id         INT NOT NULL,
            skill_id        VARCHAR(40) NOT NULL,
            completion_date DATE NOT NULL,
            UNIQUE KEY uq_user_skill_date (user_id, skill_id, completion_date),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS badges (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            code             VARCHAR(40) NOT NULL UNIQUE,
            title            VARCHAR(80),
            description      VARCHAR(160),
            icon             VARCHAR(40),
            color            VARCHAR(20),
            requirement_type VARCHAR(40),
            requirement_value INT,
            sort_order       INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_badges (
            id        INT AUTO_INCREMENT PRIMARY KEY,
            user_id   INT NOT NULL,
            badge_id  INT NOT NULL,
            earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_badge (user_id, badge_id),
            FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
            FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT,
            username   VARCHAR(50),
            email      VARCHAR(150),
            body       TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    seedContent($pdo);
}

// ════════════════════════════════════════════════════════════════════════════
//  seedContent() — inserts default skills/steps/challenges/badges exactly once.
// ════════════════════════════════════════════════════════════════════════════
function seedContent(PDO $pdo): void {
    // If skills already exist, assume seeding is done.
    $count = (int)$pdo->query("SELECT COUNT(*) AS c FROM skills")->fetch()['c'];
    if ($count > 0) return;

    // ---- SKILLS --------------------------------------------------------------
    // color is a category token resolved to real colors in the frontend.
    $skills = [
        ['egg','Cook a Perfect Egg','Master soft, hard and fried eggs - the foundation of every quick meal.','Cooking','Beginner',7,'egg-fried','cooking',1],
        ['draw','Draw a Cartoon Face','Turn simple shapes into expressive, fun cartoon characters.','Art','Beginner',8,'palette','art',2],
        ['spanish','Speak 5 Spanish Phrases','Handle a friendly conversation with confidence.','Language','Beginner',6,'language','language',3],
        ['knots','Tie 3 Useful Knots','Knots that come in handy for camping, packing and everyday fixes.','Practical','Intermediate',9,'rotate-clockwise','practical',4],
        ['breathing','Calm Breathing in 5 Min','A quick box-breathing routine to reset your focus and mood.','Wellness','Beginner',5,'lungs','wellness',5],
        ['coffee','Brew Better Coffee','Small tweaks that turn an okay cup into a great one.','Cooking','Beginner',7,'coffee','cooking',6],
        ['typing','Touch Type Faster','Build muscle memory for hands and stop looking down.','Tech','Intermediate',8,'keyboard','tech',7],
        ['origami','Basic Paper Origami','Fold a classic origami crane from a single sheet of paper.','Art','Beginner',7,'plane','art',8],
        ['budget','Make a Simple Budget','Take control of your money with a basic monthly budget.','Finance','Beginner',9,'coin','finance',9],
        ['pushups','Do 10 Proper Push-ups','Master the correct push-up form before worrying about reps.','Fitness','Beginner',6,'barbell','fitness',10],
        ['speedread','Speed Read a Page','Simple techniques to read faster without losing comprehension.','Learning','Intermediate',7,'book','learning',11],
        ['smoothie','Make a green Smoothie','A quick, healthy smoothie that tastes good - even with greens.','Cooking','Beginner',5,'leaf','wellness',12],
    ];
    $stmt = $pdo->prepare("INSERT INTO skills (id,title,description,category,difficulty,minutes,icon,color,sort_order) VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($skills as $s) $stmt->execute($s);

    // ---- STEPS ---------------------------------------------------------------
    $steps = [
        // egg
        ['egg',1,'egg','Pick your egg','Use a fresh, room-temperature egg. Crack it on a flat surface, not the bowl edge to avoid shell bits.'],
        ['egg',2,'flame','Heat the pan','Medium-low heat with a little butter or oil. Wait until the butter foams but doesn\'t brown.'],
        ['egg',3,'clock','Cook with patience','For a sunny-side up, cover the pan for 2 minutes. Low and slow gives soft whites and a runny yolk.'],
        ['egg',4,'salt','Season at the end','Salt right before serving so the whites stay tender. Add black pepper and a pinch of flaky salt.'],
        // draw
        ['draw',1,'circle','Start with a circle','Draw a light circle for the head. Use a pencil so you can erase later.'],
        ['draw',2,'plus','Add guide lines','Draw a cross through the circle. The horizontal line is eye level, the vertical is the center.'],
        ['draw',3,'eye','Place the eyes','Two ovals on the horizontal line, one eye-width apart. Bigger eyes feel friendlier.'],
        ['draw',4,'mood-smile','Bring it to life','Add a curved smile, a small nose, and eyebrows to set the mood.'],
        // spanish
        ['spanish',1,'hand-stop','Greet someone','"Hola, ¿cómo estás?" - Hi, how are you? Say it out loud three times.'],
        ['spanish',2,'message','Reply','"Estoy bien, gracias." - I\'m good, thank you. Smile while you say it.'],
        ['spanish',3,'heart','Be polite','"Por favor" (please) and "gracias" (thank you) go a long way everywhere.'],
        ['spanish',4,'wave-sine','Say goodbye','"Hasta luego" - See you later. Practice the full mini-conversation start to finish.'],
        // knots
        ['knots',1,'rotate-clockwise','The Overhand Loop','Make a loop, pass the end through it. The simplest knot - your building block for the rest.'],
        ['knots',2,'arrow-loop-right','The Bowline','Makes a fixed loop that won\'t slip - the most useful knot you\'ll ever learn.'],
        ['knots',3,'anchor','The Cleat Hitch','Figure-eight around a cleat, finish with a locking hitch. Perfect for securing boats or tarps.'],
        ['knots',4,'repeat','Practice the set','Tie all three in a row. Speed and muscle memory come from repetition.'],
        // breathing
        ['breathing',1,'armchair','Get comfortable','Sit upright with relaxed shoulders. Rest your hands on your knees and close your eyes.'],
        ['breathing',2,'arrow-up','Inhale for 4','Breathe in slowly through your nose for a count of four. Fill your belly, not just your chest.'],
        ['breathing',3,'player-pause','Hold for 4','Gently hold the breath for four counts. Stay relaxed - no straining.'],
        ['breathing',4,'arrow-down','Exhale for 4','Release slowly through your mouth for four counts. Repeat the cycle four times.'],
        // coffee
        ['coffee',1,'scale','Measure the ratio','Aim for about 1:16 - 15g coffee to 250g water. Consistency makes it repeatable.'],
        ['coffee',2,'temperature','Mind the water','Use water just off the boil (about 93°C). Boiling water scorches the grounds.'],
        ['coffee',3,'hourglass','Bloom first','Pour a little water, wait 30 seconds for it to bubble, then pour the rest slowly in circles.'],
        ['coffee',4,'mood-tongue','Taste & adjust','Too bitter? Grind coarser. Too sour? Grind finer. Tweak one thing at a time.'],
        // typing
        ['typing',1,'hand-finger','Find the home row','Left fingers on A S D F, right on J K L ; - feel the bumps on F and J.'],
        ['typing',2,'pointer','One finger per key','Each finger owns specific keys. Never reach across - index fingers cover 2 columns each.'],
        ['typing',3,'eye','Eyes on the screen','Force yourself not to look down. It feels slow at first - that\'s normal and temporary.'],
        ['typing',4,'repeat','Repeat short drills','Type "asdf jkl;" over and over for 3 minutes. Speed comes after accuracy, not before.'],
        // origami
        ['origami',1,'file','Start with a square','Fold a square diagonally both ways, then straight both ways. Unfold each time.'],
        ['origami',2,'triangle','Make the base','Collapse along the crease lines into a small diamond - the preliminary base.'],
        ['origami',3,'arrows-left-right','Shape the wings','Fold edges to the center line on both sides, flip and repeat. Open the inner flaps gently.'],
        ['origami',4,'plane','Form head & tail','Pull the two narrow points outward for the neck and tail, then pinch the head into shape.'],
        // budget
        ['budget',1,'download','List your income','Write down every source of money each month - salary, allowance, freelance, anything.'],
        ['budget',2,'upload','List your expenses','Write every regular cost: rent, food, transport, subscriptions. Group as needs vs wants.'],
        ['budget',3,'minus','Subtract & check','Income minus expenses = what\'s left. Negative means you spend more than you earn.'],
        ['budget',4,'target','Set one saving goal','Pick one thing to save for. Even saving 5% of income each month adds up fast.'],
        // pushups
        ['pushups',1,'hand-two-fingers','Set your hands','Hands slightly wider than shoulders. Fingers spread, wrists straight under your shoulders.'],
        ['pushups',2,'line-dashed','Lock your body','Feet together, core tight, hips level - a straight line from head to heels.'],
        ['pushups',3,'arrow-down','Lower with control','Bend elbows 45° to your body. Chest nearly touches the floor.'],
        ['pushups',4,'arrow-up','Push all the way up','Fully extend your arms at the top. Don\'t let hips sag. That\'s one rep.'],
        // speedread
        ['speedread',1,'pointer','Use a pointer','Move your finger under each line. Your eyes follow movement - this stops backtracking.'],
        ['speedread',2,'volume-3','Stop subvocalizing','You don\'t need to "hear" every word. Read slightly faster than you can speak.'],
        ['speedread',3,'layout-grid','Read in chunks','Train your eyes to grab 2-3 words at a time. Focus in the middle of the group.'],
        ['speedread',4,'clock','Time yourself','Read one page normally and note the time. Re-read with the techniques and compare.'],
        // smoothie
        ['smoothie',1,'leaf','Add greens first','A handful of spinach or kale goes in first. Spinach has the mildest taste for beginners.'],
        ['smoothie',2,'apple','Add fruit','One banana plus frozen mango or berries. The sweetness hides the greens completely.'],
        ['smoothie',3,'droplet','Add liquid','About 1 cup of water, coconut water, or milk. Less = thicker, more = drinkable.'],
        ['smoothie',4,'refresh','Blend and taste','Blend 30-60 seconds until smooth. Taste — add more fruit if needed, then blend again.'],
    ];
    $stmt = $pdo->prepare("INSERT INTO skill_steps_content (skill_id,step_order,icon,title,detail) VALUES (?,?,?,?,?)");
    foreach ($steps as $s) $stmt->execute($s);

    // ---- CHALLENGES ----------------------------------------------------------
    $challenges = [
        ['egg','Fry one egg sunny-side up without breaking the yolk.'],
        ['egg','Make scrambled eggs that stay soft and creamy.'],
        ['egg','Time a soft-boiled egg to exactly 6 minutes.'],
        ['draw','Draw a happy face using only circles and curves.'],
        ['draw','Draw the same face showing a surprised expression.'],
        ['draw','Give your character a hairstyle and an accessory.'],
        ['spanish','Say all 5 phrases out loud without looking.'],
        ['spanish','Record yourself and listen back to your pronunciation.'],
        ['spanish','Use one phrase with a friend or family member today.'],
        ['knots','Tie a bowline in under 20 seconds.'],
        ['knots','Secure a backpack to a chair using two different knots.'],
        ['knots','Teach one knot to someone else.'],
        ['breathing','Complete 4 full box-breathing cycles.'],
        ['breathing','Do the routine right after waking up.'],
        ['breathing','Use it once during a stressful moment today.'],
        ['coffee','Weigh your coffee and water for one brew.'],
        ['coffee','Try a 30-second bloom before your full pour.'],
        ['coffee','Adjust your grind and taste the difference.'],
        ['typing','Type the home row keys 20 times without looking down.'],
        ['typing','Type a full sentence using only home row fingers.'],
        ['typing','Beat your previous speed on any typing test site.'],
        ['origami','Fold a complete origami crane from start to finish.'],
        ['origami','Make two cranes in under 10 minutes.'],
        ['origami','Teach someone else how to fold the base.'],
        ['budget','Write down all your expenses from last week from memory.'],
        ['budget','Check if your monthly income covers your monthly costs.'],
        ['budget','Set a savings goal and count the months to reach it.'],
        ['pushups','Do 5 push-ups with perfect form — no sagging hips.'],
        ['pushups','Complete 10 push-ups in a single set.'],
        ['pushups','Do 3 sets of 10 with 60 seconds rest between.'],
        ['speedread','Read one full page using a pointer without stopping.'],
        ['speedread','Read a page 20% faster than your normal pace.'],
        ['speedread','Summarize an article in 3 sentences after speed reading.'],
        ['smoothie','Make a green smoothie and finish the whole glass.'],
        ['smoothie','Make it two days in a row.'],
        ['smoothie','Try adding a new ingredient like ginger or chia seeds.'],
    ];
    $stmt = $pdo->prepare("INSERT INTO challenges (skill_id,challenge_text) VALUES (?,?)");
    foreach ($challenges as $c) $stmt->execute($c);

    // ---- BADGES --------------------------------------------------------------
    $badges = [
        ['first','First Step','Complete your very first challenge','plant','green','total_completions',1,1],
        ['streak3','On Fire','Reach a 3-day streak','flame','orange','streak',3,2],
        ['streak7','Unstoppable','Reach a 7-day streak','bolt','violet','streak',7,3],
        ['five','Getting Serious','Complete 5 challenges','target','red','total_completions',5,4],
        ['ten','Skill Collector','Complete 10 challenges','trophy','amber','total_completions',10,5],
        ['explorer','Explorer','Try 3 different skills','compass','blue','distinct_skills',3,6],
    ];
    $stmt = $pdo->prepare("INSERT INTO badges (code,title,description,icon,color,requirement_type,requirement_value,sort_order) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($badges as $b) $stmt->execute($b);
}

// ════════════════════════════════════════════════════════════════════════════
//  Page bootstrap — every page does `require 'db.php';`, so give them a live
//  session and a ready-to-use $db handle here. (Pages that also need the
//  helper library include functions.php themselves.)
// ════════════════════════════════════════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$db = getDB();
