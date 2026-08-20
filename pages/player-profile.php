<?php
// pages/player-profile.php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

// ตรวจสอบสถานะการเข้าสู่ระบบ
$isLoggedIn = isLoggedIn();
$currentUser = [
    'username' => $_SESSION['username'] ?? null,
    'role' => $_SESSION['role'] ?? null,
];

$playerId = (int) ($_GET['id'] ?? 0);
$error = '';
$success = '';

$pStmt = $pdo->prepare("SELECT * FROM players WHERE player_id = :id");
$pStmt->execute(['id' => $playerId]);
$player = $pStmt->fetch();

if (!$player) {
    die('
    <div style="min-height:100vh; background-color:#0F1117; color:#fff; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:sans-serif; padding:2rem; text-align:center;">
        <h1 style="font-size:2rem; color:#FF5500; font-weight:bold;">ไม่พบโปรไฟล์นักกีฬานี้</h1>
        <p style="color:#aaa; margin-top:0.5rem;">ข้อมูลผู้เล่นอาจถูกลบหรือระบุรหัสไม่ถูกต้อง</p>
        <a href="index.php" style="margin-top:2rem; padding:0.8rem 1.5rem; background-color:#FF5500; color:#fff; text-decoration:none; border-radius:12px; font-weight:bold;">&larr; กลับไปหน้าแรก</a>
    </div>
    ');
}

// เจ้าของโปรไฟล์เท่านั้นที่แก้ไขได้
$isOwner = $isLoggedIn && isset($_SESSION['user_id']) && $player['user_id'] == $_SESSION['user_id'];

// บันทึกการแก้ไขโปรไฟล์
if ($isOwner && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    } else {
        $displayName = trim($_POST['display_name']);
        $realName = trim($_POST['real_name']);
        $bio = trim($_POST['bio']);
        $showRealName = isset($_POST['show_real_name']) ? 1 : 0;

        if ($displayName == '') {
            $error = 'ชื่อในเกมห้ามว่าง';
        } else {
            try {
                $avatarPath = null;
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $avatarPath = handleImageUpload($_FILES['avatar'], 'avatars');
                }

                if ($avatarPath) {
                    deleteUploadedImage($player['avatar_path']);
                    $update = $pdo->prepare("
                        UPDATE players
                        SET display_name = :display_name, real_name = :real_name,
                            bio = :bio, show_real_name = :show_real_name, avatar_path = :avatar_path
                        WHERE player_id = :id
                    ");
                    $update->execute([
                        'display_name' => $displayName,
                        'real_name' => $realName ?: null,
                        'bio' => $bio,
                        'show_real_name' => $showRealName,
                        'avatar_path' => $avatarPath,
                        'id' => $playerId,
                    ]);
                } else {
                    $update = $pdo->prepare("
                        UPDATE players
                        SET display_name = :display_name, real_name = :real_name,
                            bio = :bio, show_real_name = :show_real_name
                        WHERE player_id = :id
                    ");
                    $update->execute([
                        'display_name' => $displayName,
                        'real_name' => $realName ?: null,
                        'bio' => $bio,
                        'show_real_name' => $showRealName,
                        'id' => $playerId,
                    ]);
                }

                $success = 'บันทึกโปรไฟล์เรียบร้อยแล้ว';

                $pStmt->execute(['id' => $playerId]);
                $player = $pStmt->fetch();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// ทีมที่ผู้เล่นนี้สังกัดอยู่ (ทุกเกม) ผ่านตาราง team_members และ teams
$teams = $pdo->prepare("
    SELECT t.team_id, t.name, g.name AS game_name, t.team_category
    FROM team_members tm
    JOIN teams t ON t.team_id = tm.team_id
    LEFT JOIN games g ON g.game_id = t.game_id
    WHERE tm.player_id = :player_id AND tm.is_active = 1
");
$teams->execute(['player_id' => $playerId]);
$teams = $teams->fetchAll();

// ดึงทีมทั้งหมดที่ผู้เล่นเคยลงแข่งในประวัติศาสตร์ (Historical Rosters) รวมทีมปัจจุบัน
$rosterTeams = $pdo->prepare("
    SELECT DISTINCT team_id FROM (
        SELECT team_id FROM tournament_rosters WHERE player_id = :pid AND team_id IS NOT NULL
        UNION
        SELECT team_id FROM team_members WHERE player_id = :pid AND is_active = 1
    ) AS all_teams
");
$rosterTeams->execute(['pid' => $playerId]);
$allTeamIds = $rosterTeams->fetchAll(PDO::FETCH_COLUMN);

// ดึงตารางการแข่งขันและประวัติการแข่งของทีมที่เคยลงเล่น
$myMatches = [];
if (!empty($allTeamIds)) {
    $placeholders = implode(',', array_fill(0, count($allTeamIds), '?'));
    $mStmt = $pdo->prepare("
        SELECT m.*, t1.name as t1_name, t2.name as t2_name, tour.name as tour_name
        FROM matches m
        JOIN tournaments tour ON m.tournament_id = tour.tournament_id
        LEFT JOIN teams t1 ON m.team1_id = t1.team_id
        LEFT JOIN teams t2 ON m.team2_id = t2.team_id
        WHERE (m.team1_id IN ($placeholders) OR m.team2_id IN ($placeholders))
        ORDER BY m.match_id DESC
    ");
    $mStmt->execute(array_merge($allTeamIds, $allTeamIds));
    $myMatches = $mStmt->fetchAll();
}

// ดึง Ranking รายเกมพร้อมคำนวณอันดับที่แท้จริง (Rank Position #)
$gameRankings = [];
$rStmt = $pdo->prepare("
    SELECT pr.*, g.name AS game_name, g.game_id
    FROM player_rankings pr
    JOIN games g ON g.game_id = pr.game_id
    WHERE pr.player_id = :player_id
    ORDER BY pr.points DESC
");
$rStmt->execute(['player_id' => $playerId]);
$rawRankings = $rStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rawRankings as $r) {
    // คำนวณอันดับในเกมนี้ (Count how many players have more points in the same game)
    $rankPosStmt = $pdo->prepare("
        SELECT COUNT(*) + 1 AS actual_rank 
        FROM player_rankings 
        WHERE game_id = :gid AND (points > :pts OR (points = :pts AND player_id < :pid))
    ");
    $rankPosStmt->execute(['gid' => $r['game_id'], 'pts' => $r['points'], 'pid' => $playerId]);
    $r['actual_rank'] = $rankPosStmt->fetchColumn() ?: 1;
    $gameRankings[] = $r;
}

// ดึงทัวร์นาเมนต์ทั้งหมดที่นักกีฬาลงทะเบียนแข่งขัน (แยกตาม Tournament และทีมที่สังกัดในรายการนั้น)
$careerStmt = $pdo->prepare("
    SELECT tr.roster_id, tr.in_game_role, tr.is_captain, tr.is_substitute, tr.registered_at,
           t.tournament_id, t.name AS tournament_name, t.status AS tournament_status, t.start_date, t.end_date, t.venue_address,
           tm.name AS team_name, tm.team_id, tm.logo_path AS team_logo,
           g.name AS game_name, g.game_id
    FROM tournament_rosters tr
    JOIN tournaments t ON t.tournament_id = tr.tournament_id
    LEFT JOIN teams tm ON tm.team_id = tr.team_id
    LEFT JOIN games g ON g.game_id = t.game_id
    WHERE tr.player_id = :pid
    ORDER BY (CASE WHEN t.status IN ('ongoing', 'registration_open') THEN 0 ELSE 1 END), t.start_date DESC, t.tournament_id DESC
");
$careerStmt->execute(['pid' => $playerId]);
$playerTournaments = $careerStmt->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('roundName')) {
    function roundName($roundNum, $totalRounds = 4) {
        $fromFinal = $totalRounds - $roundNum;
        if ($fromFinal === 0) return 'รอบชิงชนะเลิศ (Grand Finals)';
        if ($fromFinal === 1) return 'รอบรองชนะเลิศ (Semi-Finals)';
        if ($fromFinal === 2) return 'รอบก่อนรองชนะเลิศ (Quarter-Finals)';
        if ($fromFinal === 3) return 'รอบ 16 ทีม (Round of 16)';
        if ($fromFinal === 4) return 'รอบ 32 ทีม (Round of 32)';
        return "รอบที่ $roundNum";
    }
}

// คำนวณ Competition Path และ Next Match สำหรับแต่ละ Tournament
$activeTournamentsData = [];
$pastTournamentsData = [];

foreach ($playerTournaments as $pt) {
    $tid = $pt['tournament_id'];
    $teamId = $pt['team_id'];
    
    // ดึง Matches ทั้งหมดของทีมใน Tournament นี้
    $tMatches = [];
    if (!empty($teamId)) {
        $tMatchesStmt = $pdo->prepare("
            SELECT m.*, 
                   t1.name AS t1_name, t2.name AS t2_name,
                   (SELECT name FROM tournament_groups WHERE tournament_group_id = m.group_id) AS group_name
            FROM matches m
            LEFT JOIN teams t1 ON t1.team_id = m.team1_id
            LEFT JOIN teams t2 ON t2.team_id = m.team2_id
            WHERE m.tournament_id = :tid AND (m.team1_id = :team_id OR m.team2_id = :team_id)
            ORDER BY m.round_number ASC, m.match_id ASC
        ");
        $tMatchesStmt->execute(['tid' => $tid, 'team_id' => $teamId]);
        $tMatches = $tMatchesStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // หา Next Match, Completed Matches, และสถานะการผ่านเข้ารอบ
    $nextMatch = null;
    $hasLostKnockout = false;
    $completedMatches = [];
    
    foreach ($tMatches as $m) {
        if ($m['status'] === 'completed' || $m['status'] === 'walkover') {
            $completedMatches[] = $m;
            if ($m['winner_team_id'] != $teamId && empty($m['group_id'])) {
                $hasLostKnockout = true;
            }
        } elseif ($nextMatch === null && in_array($m['status'], ['scheduled', 'in_progress', 'pending'])) {
            $nextMatch = $m;
        }
    }
    
    $pt['matches'] = $tMatches;
    $pt['completed_matches'] = $completedMatches;
    $pt['next_match'] = $nextMatch;
    $pt['has_lost'] = $hasLostKnockout;

    if (in_array($pt['tournament_status'], ['ongoing', 'registration_open'])) {
        $activeTournamentsData[] = $pt;
    } else {
        $pastTournamentsData[] = $pt;
    }
}

// ตรวจสอบว่าผู้เล่นคนนี้ติด Top Player ในเกมใดๆ หรือไม่
$isTopPlayer = false;
foreach ($gameRankings as $rk) {
    if (!empty($rk['actual_rank']) && $rk['actual_rank'] <= 3) {
        $isTopPlayer = true;
        break;
    }
}

$csrfToken = $isOwner ? generateCsrfToken() : '';
?>
<!DOCTYPE html>
<html lang="th" class="h-full scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($player['display_name']); ?> - Korat Esport</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,300;0,400;0,500;0,600;0,700;1,800&family=Orbitron:wght@700;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            orange: '#FF5500',
                            glow: '#FF7700',
                            dark: '#0A0A0C',
                            panel: '#121318'
                        }
                    },
                    fontFamily: {
                        sans: ['Kanit', 'sans-serif'],
                        display: ['Orbitron', 'sans-serif']
                    },
                    boxShadow: {
                        'orange-glow': '0 0 25px rgba(255, 85, 0, 0.45)',
                        'gold-glow': '0 0 25px rgba(245, 158, 11, 0.6)'
                    }
                }
            }
        }
    </script>

    <style>
        ::-webkit-scrollbar {
            display: none;
        }
        html,
        body {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        body {
            background-color: #0F1117;
            color: #f3f4f6;
        }
        .bg-esports-arena {
            background: linear-gradient(to bottom, rgba(15, 17, 23, 0.45), rgba(15, 17, 23, 0.85)),
                url('https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .glass-nav {
            background: rgba(15, 17, 23, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            transform: translateY(-6px);
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 85, 0, 0.6);
            box-shadow: 0 15px 35px -5px rgba(255, 85, 0, 0.35);
        }
        .grid-bg {
            background-image: radial-gradient(rgba(255, 255, 255, 0.15) 1px, transparent 0);
            background-size: 24px 24px;
        }
        #particles-canvas {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 1;
        }
        @keyframes avatarPulseGlow {
            0%, 100% { box-shadow: 0 0 15px rgba(255, 85, 0, 0.4); border-color: #FF5500; }
            50% { box-shadow: 0 0 35px rgba(255, 85, 0, 0.8); border-color: #ff8844; }
        }
        .avatar-pulse-glow {
            animation: avatarPulseGlow 3s infinite;
        }
        @keyframes shimmerAnim {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .shimmer-badge {
            background: linear-gradient(90deg, rgba(245, 158, 11, 0.2) 0%, rgba(255, 255, 255, 0.4) 50%, rgba(245, 158, 11, 0.2) 100%);
            background-size: 200% 100%;
            animation: shimmerAnim 3s infinite linear;
            border: 1px solid rgba(245, 158, 11, 0.6);
        }
        @keyframes rowFadeIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .stat-row {
            animation: rowFadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0; transition: all 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-animate {
            animation: slideDown 0.4s ease forwards;
        }
    </style>
</head>

<body class="text-gray-100 font-sans min-h-screen overflow-x-hidden antialiased">

    <div class="fixed inset-0 bg-esports-arena z-0 pointer-events-none"></div>
    <div class="fixed inset-0 grid-bg opacity-30 z-0 pointer-events-none"></div>
    <canvas id="particles-canvas"></canvas>

    <div class="relative z-10 flex flex-col min-h-screen">

        <header class="sticky top-0 z-50 glass-nav transition-all">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <a href="index.php" class="flex items-center gap-3 group">
                        <img src="../assets/img/logo.png" alt="Korat Esport"
                            class="h-11 w-auto filter drop-shadow group-hover:scale-105 transition-transform"
                            onError="this.src='https://placehold.co/100x100/121318/FF5500?text=KE';">
                        <div>
                            <span class="font-display font-black text-xl tracking-wider text-white group-hover:text-brand-orange transition-colors">KORAT <span class="text-brand-orange">ESPORT</span></span>
                            <span class="block text-[10px] tracking-widest text-gray-200 font-bold uppercase -mt-1">Official Arena & Hub</span>
                        </div>
                    </a>

                    <nav class="hidden md:flex items-center gap-1 lg:gap-2">
                        <a href="index.php" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-200 hover:text-brand-orange hover:bg-white/10 transition-all">หน้าแรก</a>
                        <a href="tournaments.php" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-200 hover:text-brand-orange hover:bg-white/10 transition-all">ทัวร์นาเมนต์</a>
                        <a href="ranking.php" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-200 hover:text-brand-orange hover:bg-white/10 transition-all">ตารางคะแนน</a>
                        <a href="news.php" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-200 hover:text-brand-orange hover:bg-white/10 transition-all">ข่าวสาร</a>
                        <a href="gallery.php" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-200 hover:text-brand-orange hover:bg-white/10 transition-all">แกลเลอรี่</a>
                        <?php if ($isLoggedIn): ?>
                            <a href="lodging.php" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-200 hover:text-brand-orange hover:bg-white/10 transition-all">ที่พักแนะนำ</a>
                        <?php endif; ?>
                    </nav>

                    <div class="flex items-center gap-4 text-base font-bold">
                        <?php if ($isLoggedIn): ?>
                            <div class="flex items-center gap-3 bg-white/10 border border-white/20 p-1.5 pl-3.5 rounded-2xl backdrop-blur-md">
                                <div class="flex flex-col text-right">
                                    <span class="text-sm font-bold text-white leading-tight"><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></span>
                                    <span class="text-[10px] font-semibold text-brand-orange uppercase tracking-wider"><?= htmlspecialchars($currentUser['role'] ?? 'Player') ?></span>
                                </div>
                                <a href="profile.php" title="จัดการโปรไฟล์" class="w-9 h-9 rounded-xl bg-brand-orange hover:bg-brand-glow text-white flex items-center justify-center transition-all shadow-md">
                                    <i class="fa-solid fa-user-gear text-sm"></i>
                                </a>
                                <a href="../auth/logout.php" title="ออกจากระบบ" class="w-9 h-9 rounded-xl bg-rose-500/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 flex items-center justify-center transition-all">
                                    <i class="fa-solid fa-right-from-bracket text-sm"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <a href="../auth/login.php" class="text-brand-orange hover:text-brand-glow transition-colors">เข้าสู่ระบบ</a>
                            <a href="../auth/register.php" class="text-white hover:text-brand-orange transition-colors">สมัครสมาชิก</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full space-y-12">

            <div class="glass-panel p-6 sm:p-10 rounded-3xl border border-white/20 shadow-2xl flex flex-col md:flex-row items-center gap-8"
                data-aos="zoom-in" data-aos-duration="800">
                <?php
                $avatarSrc = 'https://placehold.co/150x150/121318/FF5500?text=Avatar';
                if (!empty($player['avatar_path'])) {
                    $path = trim($player['avatar_path']);
                    if (strpos($path, 'http') === 0) {
                        $avatarSrc = $path;
                    } else {
                        $cleanPath = ltrim($path, '/');
                        $avatarSrc = (strpos($cleanPath, 'assets/') === 0) ? '../' . $cleanPath : '../assets/' . $cleanPath;
                    }
                }
                ?>
                <img id="profile-avatar-preview" src="<?= htmlspecialchars($avatarSrc) ?>"
                    alt="<?php echo htmlspecialchars($player['display_name']); ?>"
                    class="w-32 h-32 sm:w-40 sm:h-40 rounded-2xl object-cover border-2 border-brand-orange avatar-pulse-glow shrink-0 bg-black/40 p-1"
                    onError="this.src='https://placehold.co/150x150/121318/FF5500?text=Avatar';">

                <div class="space-y-3 text-center md:text-left flex-1">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-orange/20 border border-brand-orange/40 text-brand-orange text-xs font-bold uppercase tracking-widest">
                        <i class="fa-solid fa-gamepad"></i> Athlete Profile
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center gap-3">
                        <h1 class="text-3xl sm:text-4xl font-black font-display text-white uppercase tracking-wide">
                            <?php echo htmlspecialchars($player['display_name']); ?>
                        </h1>
                        <?php if ($isTopPlayer): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider shimmer-badge text-amber-300 inline-flex items-center gap-1.5 w-fit mx-auto md:mx-0 shadow-gold-glow">
                                <i class="fa-solid fa-crown"></i> Top Player
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($player['show_real_name'] && $player['real_name']): ?>
                        <p class="text-sm text-gray-300 font-medium">ชื่อจริง: <?php echo htmlspecialchars($player['real_name']); ?></p>
                    <?php endif; ?>

                    <?php if ($player['bio']): ?>
                        <p class="text-xs sm:text-sm text-gray-300 leading-relaxed pt-2 font-normal">
                            <?php echo nl2br(htmlspecialchars($player['bio'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ================= 1. GAME-SPECIFIC RANKINGS (แยก Ranking ตามเกม) ================= -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-white/15 pb-3">
                    <h2 class="text-xl font-bold font-display text-white uppercase tracking-wider flex items-center gap-2" data-aos="fade-right">
                        <i class="fa-solid fa-ranking-star text-amber-400"></i> อันดับและคะแนนสะสมแยกตามเกม (GAME RANKINGS)
                    </h2>
                    <span class="text-xs text-gray-400 font-normal">คะแนนคำนวณแยกตามแต่ละเกม ไม่รวมกัน</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" data-aos="fade-up">
                    <?php if (empty($gameRankings)): ?>
                        <div class="col-span-full glass-panel p-8 text-center text-gray-400 rounded-2xl">
                            ยังไม่มีคะแนนหรืออันดับ Ranking ในระบบ
                        </div>
                    <?php else: ?>
                        <?php foreach ($gameRankings as $grIndex => $gr): 
                            $actRank = (int) $gr['actual_rank'];
                            $rankBg = 'bg-white/10 text-gray-300 border-white/20';
                            if ($actRank === 1) $rankBg = 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-gold-glow';
                            elseif ($actRank === 2) $rankBg = 'bg-slate-300/20 text-slate-200 border-slate-300/40';
                            elseif ($actRank === 3) $rankBg = 'bg-amber-700/20 text-amber-400 border-amber-600/40';
                        ?>
                            <div class="glass-panel p-5 rounded-2xl border border-white/15 hover:border-brand-orange/50 transition-all space-y-4 relative overflow-hidden shadow-xl">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-brand-orange/20 border border-brand-orange/40 flex items-center justify-center text-brand-orange text-xs font-bold">
                                            <i class="fa-solid fa-gamepad"></i>
                                        </div>
                                        <h3 class="font-bold text-white text-base font-display">
                                            <?= htmlspecialchars($gr['game_name']); ?>
                                        </h3>
                                    </div>
                                    <span class="px-3 py-1 rounded-xl text-xs font-display font-black border <?= $rankBg; ?>">
                                        <?= $actRank === 1 ? '👑 ' : ''; ?>#<?= $actRank; ?>
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-white/10 text-center">
                                    <div class="bg-black/30 p-2.5 rounded-xl border border-white/5">
                                        <span class="text-[10px] text-gray-400 uppercase font-bold block">คะแนนสะสม</span>
                                        <span class="font-display font-black text-brand-orange text-lg"><?= number_format($gr['points']); ?> <span class="text-[10px] text-gray-400 font-normal">PTS</span></span>
                                    </div>
                                    <div class="bg-black/30 p-2.5 rounded-xl border border-white/5">
                                        <span class="text-[10px] text-gray-400 uppercase font-bold block">Win Rate</span>
                                        <span class="font-mono font-bold text-emerald-400 text-base"><?= $gr['win_rate']; ?>%</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-xs text-gray-300 pt-1 px-1">
                                    <span>ผลงาน: <strong class="text-emerald-400"><?= $gr['wins']; ?>W</strong> - <strong class="text-rose-400"><?= $gr['losses']; ?>L</strong> (<?= $gr['matches_played']; ?> นัด)</span>
                                    <?php if (!empty($gr['podium_finishes'])): ?>
                                        <span class="text-amber-300 font-semibold"><i class="fa-solid fa-trophy text-[10px]"></i> Top 3: <?= $gr['podium_finishes']; ?> ครั้ง</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ================= 2. ACTIVE TOURNAMENTS & COMPETITION PATH (ทัวร์นาเมนต์ที่กำลังเข้าร่วม & เส้นทางแข่ง) ================= -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-white/15 pb-3">
                    <h2 class="text-xl font-bold font-display text-white uppercase tracking-wider flex items-center gap-2" data-aos="fade-right">
                        <i class="fa-solid fa-fire text-brand-orange"></i> ทัวร์นาเมนต์ที่กำลังเข้าร่วม & เส้นทางการแข่งขัน (LIVE TOURNAMENTS)
                    </h2>
                    <span class="text-xs text-gray-400 font-normal">ติดตามผลการแข่งและนัดถัดไปแบบเรียลไทม์</span>
                </div>

                <?php if (empty($activeTournamentsData)): ?>
                    <div class="glass-panel p-8 text-center text-gray-400 rounded-2xl" data-aos="fade-up">
                        <i class="fa-solid fa-calendar-xmark text-3xl text-gray-500 mb-2 block"></i>
                        ไม่มีทัวร์นาเมนต์ที่กำลังแข่งขันในขณะนี้
                    </div>
                <?php else: ?>
                    <div class="space-y-6" data-aos="fade-up">
                        <?php foreach ($activeTournamentsData as $at): 
                            $nxt = $at['next_match'];
                            $tid = $at['tournament_id'];
                            $myTeamId = $at['team_id'];
                            $isEliminated = $at['has_lost'];
                        ?>
                            <div class="glass-panel rounded-3xl p-6 border border-white/20 shadow-2xl space-y-5 relative overflow-hidden">
                                <!-- Tournament Header -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-white/15 pb-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $at['tournament_status'] === 'ongoing' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-blue-500/20 text-blue-300 border border-blue-500/30' ?>">
                                                <?= $at['tournament_status'] === 'ongoing' ? '🔴 กำลังแข่งขัน (Ongoing)' : '🟡 เปิดรับสมัคร (Registration)' ?>
                                            </span>
                                            <span class="text-xs text-gray-400"><i class="fa-solid fa-gamepad text-brand-orange"></i> <?= htmlspecialchars($at['game_name']); ?></span>
                                        </div>
                                        <h3 class="text-xl font-bold font-display text-white mt-1">
                                            <?= htmlspecialchars($at['tournament_name']); ?>
                                        </h3>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="text-right">
                                            <span class="text-[10px] text-gray-400 block uppercase">ลงแข่งในนามทีม</span>
                                            <span class="text-sm font-bold text-brand-orange"><?= htmlspecialchars($at['team_name'] ?: 'บุคคลเดี่ยว'); ?></span>
                                        </div>
                                        <a href="tournament-detail.php?id=<?= $tid; ?>&category=<?= urlencode($at['team_category'] ?? 'open'); ?>&highlight=<?= $myTeamId; ?>" 
                                           class="px-4 py-2 rounded-xl bg-brand-orange hover:bg-brand-glow text-white text-xs font-bold transition-all shadow-md flex items-center gap-1.5 whitespace-nowrap">
                                            <i class="fa-solid fa-sitemap"></i> ดูสายแข่ง (Highlight ทีมฉัน)
                                        </a>
                                    </div>
                                </div>

                                <!-- NEXT MATCH CARD -->
                                <div class="p-4 rounded-2xl bg-black/40 border border-white/10 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                                            <i class="fa-solid fa-bolt text-brand-orange"></i> แมตช์การแข่งขันนัดถัดไป (NEXT MATCH)
                                        </span>
                                        <?php if ($isEliminated): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">สิ้นสุดเส้นทาง (ตกรอบแล้ว)</span>
                                        <?php elseif ($nxt): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">รอลงแข่งขัน</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">รอกำหนดคู่แข่งรอบต่อไป</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($nxt): 
                                        $opponentName = ($nxt['team1_id'] == $myTeamId) ? ($nxt['t2_name'] ?: 'รอผู้ชนะรอบก่อน') : ($nxt['t1_name'] ?: 'รอผู้ชนะรอบก่อน');
                                    ?>
                                        <div class="flex flex-col sm:flex-row items-center justify-between p-3 rounded-xl bg-white/5 border border-white/10 gap-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-brand-orange/20 border border-brand-orange/40 flex items-center justify-center text-brand-orange font-bold text-sm shrink-0">
                                                    VS
                                                </div>
                                                <div>
                                                    <div class="text-xs text-gray-400">คู่ต่อสู้รอบ <?= roundName($nxt['round_number'], 5); ?>:</div>
                                                    <div class="text-sm font-bold text-white"><?= htmlspecialchars($opponentName); ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="text-right text-xs space-y-1">
                                                <div class="text-gray-300">
                                                    <i class="fa-regular fa-calendar text-brand-orange mr-1"></i>
                                                    <?= $nxt['match_day'] ? 'Day ' . $nxt['match_day'] . ' - ' : '' ?>
                                                    <?= $nxt['scheduled_at'] ? date('d/m/Y H:i น.', strtotime($nxt['scheduled_at'])) : 'รอกำหนดเวลา' ?>
                                                </div>
                                                <?php if (!empty($nxt['venue_station'])): ?>
                                                    <div class="text-amber-300"><i class="fa-solid fa-location-dot mr-1"></i> โซน/สนาม: <?= htmlspecialchars($nxt['venue_station']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php elseif (!$isEliminated): ?>
                                        <div class="p-3 rounded-xl bg-white/5 border border-white/10 text-xs text-gray-300 flex items-center gap-2">
                                            <i class="fa-solid fa-hourglass-half text-amber-400"></i> ผ่านเข้ารอบแล้ว กำลังรอผลสรุปคู่แข่งขันสายประกบเพื่อทราบทีมคู่แข่งรอบถัดไป
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- COMPETITION PATH TIMELINE -->
                                <div class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">
                                        <i class="fa-solid fa-timeline text-brand-orange mr-1"></i> ประวัติเส้นทางการแข่งในรายการนี้ (Competition Path):
                                    </span>
                                    <?php if (empty($at['completed_matches'])): ?>
                                        <div class="text-xs text-gray-500 italic p-2">ยังไม่มีแมตช์ที่แข่งขันจบในรายการนี้</div>
                                    <?php else: ?>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                            <?php foreach ($at['completed_matches'] as $cm): 
                                                $isWinner = ($cm['winner_team_id'] == $myTeamId);
                                                $oppName = ($cm['team1_id'] == $myTeamId) ? ($cm['t2_name'] ?: 'คู่แข่ง') : ($cm['t1_name'] ?: 'คู่แข่ง');
                                                $myScore = ($cm['team1_id'] == $myTeamId) ? $cm['team1_score'] : $cm['team2_score'];
                                                $oppScore = ($cm['team1_id'] == $myTeamId) ? $cm['team2_score'] : $cm['team1_score'];
                                            ?>
                                                <div class="p-2.5 rounded-xl border <?= $isWinner ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-rose-500/10 border-rose-500/30' ?> text-xs flex items-center justify-between">
                                                    <div>
                                                        <span class="font-bold block text-white"><?= roundName($cm['round_number'], 5); ?></span>
                                                        <span class="text-[11px] text-gray-300">vs <?= htmlspecialchars($oppName); ?></span>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="px-2 py-0.5 rounded font-mono font-bold text-xs <?= $isWinner ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' ?>">
                                                            <?= $isWinner ? 'ชนะ' : 'แพ้' ?> <?= $myScore ?> - <?= $oppScore ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ================= 3. TOURNAMENT CAREER HISTORY (ประวัติการแข่งในอดีต) ================= -->
            <div class="space-y-4">
                <h2 class="text-xl font-bold font-display text-white uppercase tracking-wider flex items-center gap-2 border-b border-white/15 pb-3"
                    data-aos="fade-right">
                    <i class="fa-solid fa-trophy text-amber-400"></i> ประวัติการเข้าร่วมทัวร์นาเมนต์ทั้งหมด (Tournament Career History)
                </h2>

                <div class="glass-panel rounded-2xl overflow-hidden shadow-xl border border-white/15">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-200">
                            <thead class="bg-black/40 text-xs uppercase font-bold text-gray-300 border-b border-white/15 font-display">
                                <tr>
                                    <th class="p-4">ทัวร์นาเมนต์</th>
                                    <th class="p-4">เกม</th>
                                    <th class="p-4">ทีมที่ลงแข่งขันในรายการนี้</th>
                                    <th class="p-4 text-center">บทบาท / ตำแหน่ง</th>
                                    <th class="p-4 text-center">สถานะผู้เล่น</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10 font-medium">
                                <?php if (empty($playerTournaments)): ?>
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-gray-400">ยังไม่มีประวัติการเข้าร่วมทัวร์นาเมนต์ในระบบ</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($playerTournaments as $th): ?>
                                        <tr class="hover:bg-white/10 transition-colors">
                                            <td class="p-4">
                                                <a href="tournament-detail.php?id=<?= $th['tournament_id']; ?>" class="font-bold text-white hover:text-brand-orange transition-colors flex items-center gap-1.5">
                                                    <i class="fa-solid fa-trophy text-xs text-amber-400"></i>
                                                    <?= htmlspecialchars($th['tournament_name']); ?>
                                                </a>
                                            </td>
                                            <td class="p-4 text-xs text-gray-300">
                                                <?= htmlspecialchars($th['game_name'] ?? '-'); ?>
                                            </td>
                                            <td class="p-4 font-bold">
                                                <?php if (!empty($th['team_id'])): ?>
                                                    <a href="team-profile.php?id=<?= $th['team_id']; ?>" class="text-brand-orange hover:underline flex items-center gap-1">
                                                        <i class="fa-solid fa-shield-halved text-xs"></i>
                                                        <?= htmlspecialchars($th['team_name']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-purple-400 font-bold"><i class="fa-solid fa-user"></i> บุคคลเดี่ยว (Solo)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 text-center text-xs">
                                                <?php if (!empty($th['is_captain'])): ?>
                                                    <span class="px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30">
                                                        👑 <?= htmlspecialchars($th['in_game_role'] ?: 'กัปตันทีม'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-1 rounded-lg bg-white/10 text-gray-300 font-medium">
                                                        <?= htmlspecialchars($th['in_game_role'] ?: 'ผู้เล่น'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 text-center text-xs">
                                                <?php if ($th['is_substitute']): ?>
                                                    <span class="text-amber-400 font-semibold">🟡 ตัวสำรอง</span>
                                                <?php else: ?>
                                                    <span class="text-emerald-400 font-semibold">🟢 ตัวจริง</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if ($isOwner): ?>
                <div class="glass-panel p-6 sm:p-10 rounded-3xl border border-white/20 shadow-2xl space-y-6"
                    data-aos="fade-up" data-aos-duration="1000">
                    <div class="border-b border-white/15 pb-4">
                        <h2 class="text-xl font-bold font-display text-white uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-user-gear text-brand-orange"></i> แก้ไขโปรไฟล์ส่วนตัว
                        </h2>
                        <p class="text-xs text-gray-400 mt-1">อัปเดตข้อมูลส่วนตัว รูปโปรไฟล์ และรายละเอียดของคุณ</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="p-4 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs flex items-center gap-2 alert-animate">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div id="success-alert"
                            class="p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-xs flex items-center gap-2 alert-animate">
                            <i class="fa-solid fa-circle-check"></i> <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="profile-form" method="POST" enctype="multipart/form-data" class="space-y-6"
                        onsubmit="handleFormSubmit(this)">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase text-gray-300 tracking-wider">รูปโปรไฟล์ (ไม่บังคับ)</label>
                            <input type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png,image/webp"
                                class="w-full text-xs text-gray-300 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-orange file:text-white hover:file:bg-brand-glow file:cursor-pointer bg-black/40 border border-white/15 rounded-xl p-2"
                                onchange="previewAvatar(event)">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase text-gray-300 tracking-wider">ชื่อในเกม (Display Name)</label>
                                <input type="text" name="display_name"
                                    value="<?php echo htmlspecialchars($player['display_name']); ?>" required
                                    class="w-full bg-black/40 border border-white/15 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-orange font-medium">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase text-gray-300 tracking-wider">ชื่อ-สกุลจริง (ไม่บังคับ)</label>
                                <input type="text" name="real_name"
                                    value="<?php echo htmlspecialchars($player['real_name'] ?? ''); ?>"
                                    class="w-full bg-black/40 border border-white/15 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-orange font-medium">
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="show_real_name" id="show_real_name" <?php echo $player['show_real_name'] ? 'checked' : ''; ?>
                                class="w-4 h-4 rounded accent-brand-orange bg-black/40 border-white/20">
                            <label for="show_real_name" class="text-xs text-gray-300 font-semibold cursor-pointer">แสดงชื่อ-สกุลจริงบนโปรไฟล์สาธารณะ</label>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase text-gray-300 tracking-wider">เกี่ยวกับฉัน (Bio)</label>
                            <textarea name="bio" rows="4" placeholder="แนะนำตัวสั้นๆ หรือช่องทางการติดต่อ..."
                                class="w-full bg-black/40 border border-white/15 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-brand-orange font-medium"><?php echo htmlspecialchars($player['bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" id="submit-btn"
                                class="px-8 py-3.5 rounded-xl bg-brand-orange hover:bg-brand-glow text-white font-bold text-xs uppercase tracking-wider transition-all shadow-orange-glow cursor-pointer flex items-center gap-2">
                                <span id="btn-text">บันทึกโปรไฟล์</span>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </main>

        <footer class="border-t border-white/15 bg-slate-950/80 backdrop-blur-md mt-auto py-8 text-xs text-gray-400">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left">
                <div>
                    <p class="text-gray-300 font-semibold">&copy; <?= date('Y') ?> KORAT ESPORT. All rights reserved.</p>
                    <p class="text-[11px] text-gray-400 mt-1">ศูนย์กลางข้อมูลข่าวสารและการแข่งขันอีสปอร์ตจังหวัดนครราชสีมา</p>
                </div>
                <div class="flex items-center gap-4 text-gray-300">
                    <a href="https://www.facebook.com/koratesport/" target="_blank" rel="noopener noreferrer" title="Facebook: Korat Esport" class="hover:text-brand-orange transition-colors"><i class="fa-brands fa-facebook text-lg"></i></a>
                    <a href="https://www.youtube.com/@koratesport" target="_blank" rel="noopener noreferrer" title="YouTube: Korat Esport" class="hover:text-brand-orange transition-colors"><i class="fa-brands fa-youtube text-lg"></i></a>
                </div>
            </div>
        </footer>

    </div>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            AOS.init({ once: true, duration: 800, easing: 'ease-out-cubic' });

            const counters = document.querySelectorAll('.stat-counter');
            counters.forEach(counter => {
                const target = +counter.getAttribute('data-target');
                let count = 0;
                const increment = Math.max(1, Math.ceil(target / 25));

                const updateCount = () => {
                    count += increment;
                    if (count < target) {
                        counter.innerText = count.toLocaleString();
                        setTimeout(updateCount, 25);
                    } else {
                        counter.innerText = target.toLocaleString();
                    }
                };
                updateCount();
            });

            const successAlert = document.getElementById('success-alert');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    successAlert.style.opacity = '0';
                    successAlert.style.transform = 'translateY(-10px)';
                    setTimeout(() => successAlert.remove(), 600);
                }, 4000);
            }

            const canvas = document.getElementById('particles-canvas');
            const ctx = canvas.getContext('2d');

            let widthWin = canvas.width = window.innerWidth;
            let heightWin = canvas.height = window.innerHeight;

            window.addEventListener('resize', () => {
                widthWin = canvas.width = window.innerWidth;
                heightWin = canvas.height = window.innerHeight;
            });

            class Particle {
                constructor() { this.reset(); }
                reset() {
                    this.x = Math.random() * widthWin;
                    this.y = heightWin + Math.random() * 100;
                    this.size = Math.random() * 2 + 0.5;
                    this.speedY = Math.random() * 0.5 + 0.1;
                    this.speedX = (Math.random() - 0.5) * 0.2;
                    this.opacity = Math.random() * 0.4 + 0.1;
                }
                update() {
                    this.y -= this.speedY;
                    this.x += this.speedX;
                    if (this.y < -10) this.reset();
                }
                draw() {
                    ctx.fillStyle = `rgba(255, 85, 0, ${this.opacity})`;
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                }
            }

            const particles = Array.from({ length: 35 }, () => new Particle());

            function animateParticles() {
                ctx.clearRect(0, 0, widthWin, heightWin);
                particles.forEach(p => { p.update(); p.draw(); });
                requestAnimationFrame(animateParticles);
            }
            animateParticles();
        });

        function previewAvatar(event) {
            const file = event.target.files[0];
            if (file) {
                const previewImg = document.getElementById('profile-avatar-preview');
                previewImg.src = URL.createObjectURL(file);
            }
        }

        function handleFormSubmit(form) {
            const btn = document.getElementById('submit-btn');
            const btnText = document.getElementById('btn-text');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> กำลังบันทึก...';
        }
    </script>
</body>

</html>