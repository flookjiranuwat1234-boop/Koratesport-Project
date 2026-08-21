<?php
// admin/checkin-teams.php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

// ดึงข้อมูล User ปัจจุบันที่ Login อยู่
$currentUser = [
    'username' => $_SESSION['username'] ?? null,
    'role' => $_SESSION['role'] ?? null,
];

$tournamentId = (int) ($_GET['tournament_id'] ?? 0);
$statusFilter = trim($_GET['status_filter'] ?? 'all');
$teamSearch = trim($_GET['team_search'] ?? '');
$error = '';
$success = '';

// ==========================================
// อัปเกรดฐานข้อมูลอัตโนมัติ
// ==========================================
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tournament_player_checkins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT NOT NULL,
            team_id INT NULL,
            player_id INT NOT NULL,
            qr_code_token VARCHAR(64) NULL,
            checked_in_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_t_p (tournament_id, player_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Exception $e) {}

// ตรวจสอบโหมดของทัวร์นาเมนต์ที่เลือก (solo หรือ team)
$tournament = null;
$isSolo = false;
$minRosterRequired = 1;

if ($tournamentId) {
    $tQuery = $pdo->prepare("
        SELECT t.*, g.name AS game_name, g.play_mode, g.roster_size_min 
        FROM tournaments t 
        JOIN games g ON t.game_id = g.game_id 
        WHERE t.tournament_id = :id
    ");
    $tQuery->execute(['id' => $tournamentId]);
    $tournament = $tQuery->fetch();
    if ($tournament) {
        if ($tournament['play_mode'] === 'solo' || isSoloGame($tournament['game_name'])) {
            $isSolo = true;
            $minRosterRequired = 1;
        } else {
            $minRosterRequired = max(1, (int) ($tournament['roster_size_min'] ?? 5));
        }
    }
}

// ==========================================
// ฟังก์ชันช่วยอัปเดตสถานะของทีมตามจำนวนนักกีฬาที่เช็คอิน
// ==========================================
function updateTeamCheckinState($pdo, $tournamentId, $teamId, $minRequired) {
    // นับจำนวนสมาชิกในทีมที่เช็คอินแล้ว
    $cStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT tpc.player_id) 
        FROM tournament_player_checkins tpc
        JOIN team_members tm ON tm.player_id = tpc.player_id AND tm.team_id = :tid AND tm.is_active = 1
        WHERE tpc.tournament_id = :tour_id
    ");
    $cStmt->execute(['tid' => $teamId, 'tour_id' => $tournamentId]);
    $checkedCount = (int) $cStmt->fetchColumn();

    // ดึง registration ปัจจุบัน
    $rStmt = $pdo->prepare("SELECT tournament_registration_id, status, checkin_status FROM tournament_registrations WHERE tournament_id = :tour_id AND team_id = :tid");
    $rStmt->execute(['tour_id' => $tournamentId, 'tid' => $teamId]);
    $reg = $rStmt->fetch();

    if ($reg) {
        if ($checkedCount >= $minRequired) {
            $pdo->prepare("
                UPDATE tournament_registrations 
                SET checkin_status = 'checked_in', 
                    status = IF(status IN ('pending', 'approved'), 'approved', status),
                    checkin_at = IF(checkin_at IS NULL, NOW(), checkin_at)
                WHERE tournament_registration_id = :rid
            ")->execute(['rid' => $reg['tournament_registration_id']]);
        } elseif ($checkedCount > 0) {
            $pdo->prepare("
                UPDATE tournament_registrations 
                SET checkin_status = 'incomplete',
                    checkin_at = IF(checkin_at IS NULL, NOW(), checkin_at)
                WHERE tournament_registration_id = :rid
            ")->execute(['rid' => $reg['tournament_registration_id']]);
        } else {
            if ($reg['checkin_status'] !== 'walkover' && $reg['checkin_status'] !== 'withdrawn') {
                $pdo->prepare("
                    UPDATE tournament_registrations 
                    SET checkin_status = 'not_checked_in', checkin_at = NULL
                    WHERE tournament_registration_id = :rid
                ")->execute(['rid' => $reg['tournament_registration_id']]);
            }
        }
    }
}

// ==========================================
// การจัดการคำขอ POST (Check-in & Status Changes)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. เช็คอินด้วยการกรอกรหัส / สแกน QR Token
        if ($action == 'checkin') {
            $token = trim($_POST['token'] ?? '');

            if ($isSolo) {
                // เช็คอินแบบเดี่ยว (Solo)
                $stmt = $pdo->prepare("
                    SELECT tr.tournament_registration_id, tr.player_id, tr.checkin_status, u.username AS participant_name
                    FROM tournament_registrations tr
                    JOIN players p ON p.player_id = tr.player_id
                    JOIN users u ON u.user_id = p.user_id
                    WHERE tr.qr_code_token = :token AND tr.tournament_id = :tid
                ");
                $stmt->execute(['token' => $token, 'tid' => $tournamentId]);
                $reg = $stmt->fetch();

                if (!$reg) {
                    $error = 'ไม่พบรหัสเช็คอินนี้ในรายการแข่งขันเดี่ยวนี้';
                } else {
                    $pdo->prepare("
                        UPDATE tournament_registrations
                        SET checkin_status = 'checked_in', checkin_at = NOW(), status = 'approved'
                        WHERE tournament_registration_id = :id
                    ")->execute(['id' => $reg['tournament_registration_id']]);

                    $pdo->prepare("
                        INSERT IGNORE INTO tournament_player_checkins (tournament_id, player_id, qr_code_token)
                        VALUES (:tid, :pid, :token)
                    ")->execute(['tid' => $tournamentId, 'pid' => $reg['player_id'], 'token' => $token]);

                    $success = 'เช็คอินผู้เล่นเดี่ยว "' . $reg['participant_name'] . '" สำเร็จแล้ว!';
                }
            } else {
                // เช็คอินแบบทีม (Team)
                $stmt = $pdo->prepare("
                    SELECT tr.tournament_registration_id, tr.team_id, tr.checkin_status, t.name AS participant_name
                    FROM tournament_registrations tr
                    JOIN teams t ON t.team_id = tr.team_id
                    WHERE tr.qr_code_token = :token AND tr.tournament_id = :tid
                ");
                $stmt->execute(['token' => $token, 'tid' => $tournamentId]);
                $reg = $stmt->fetch();

                if (!$reg) {
                    $error = 'ไม่พบรหัสเช็คอินนี้ในรายการแข่งขันทีมนี้';
                } else {
                    // ทำการเช็คอินสมาชิกทุกคนในทีม
                    $mStmt = $pdo->prepare("SELECT player_id FROM team_members WHERE team_id = :tid AND is_active = 1");
                    $mStmt->execute(['tid' => $reg['team_id']]);
                    $members = $mStmt->fetchAll(PDO::FETCH_COLUMN);

                    $ins = $pdo->prepare("
                        INSERT IGNORE INTO tournament_player_checkins (tournament_id, team_id, player_id, qr_code_token)
                        VALUES (:tid, :team_id, :pid, :token)
                    ");
                    foreach ($members as $pId) {
                        $ins->execute(['tid' => $tournamentId, 'team_id' => $reg['team_id'], 'pid' => $pId, 'token' => $token]);
                    }

                    $pdo->prepare("
                        UPDATE tournament_registrations
                        SET checkin_status = 'checked_in', checkin_at = NOW(), status = 'approved'
                        WHERE tournament_registration_id = :id
                    ")->execute(['id' => $reg['tournament_registration_id']]);

                    $success = 'เช็คอินทีม "' . $reg['participant_name'] . '" (ครบทุกคน) เรียบร้อยแล้ว!';
                }
            }
        }

        // 2. เช็คอิน / ยกเลิกเช็คอิน นักกีฬารายบุคคล (Individual Player Toggle)
        if ($action == 'toggle_player_checkin') {
            $pId = (int) $_POST['player_id'];
            $teamId = (int) $_POST['team_id'];
            $chkState = (int) $_POST['is_checked'];

            if ($chkState === 1) {
                $pdo->prepare("
                    INSERT IGNORE INTO tournament_player_checkins (tournament_id, team_id, player_id)
                    VALUES (:tid, :team_id, :pid)
                ")->execute(['tid' => $tournamentId, 'team_id' => $teamId, 'pid' => $pId]);
            } else {
                $pdo->prepare("
                    DELETE FROM tournament_player_checkins 
                    WHERE tournament_id = :tid AND player_id = :pid
                ")->execute(['tid' => $tournamentId, 'pid' => $pId]);
            }

            updateTeamCheckinState($pdo, $tournamentId, $teamId, $minRosterRequired);
            $success = 'อัปเดตสถานะเช็คอินรายบุคคลเรียบร้อยแล้ว!';
        }

        // 3. เช็คอินทุกคนในทีมทันที (Quick Check-in All)
        if ($action == 'quick_checkin_all') {
            $regId = (int) $_POST['registration_id'];
            $teamId = (int) $_POST['team_id'];

            $mStmt = $pdo->prepare("SELECT player_id FROM team_members WHERE team_id = :tid AND is_active = 1");
            $mStmt->execute(['tid' => $teamId]);
            $members = $mStmt->fetchAll(PDO::FETCH_COLUMN);

            $ins = $pdo->prepare("
                INSERT IGNORE INTO tournament_player_checkins (tournament_id, team_id, player_id)
                VALUES (:tid, :team_id, :pid)
            ");
            foreach ($members as $pId) {
                $ins->execute(['tid' => $tournamentId, 'team_id' => $teamId, 'pid' => $pId]);
            }

            $pdo->prepare("
                UPDATE tournament_registrations 
                SET checkin_status = 'checked_in', checkin_at = NOW(), status = 'approved'
                WHERE tournament_registration_id = :rid
            ")->execute(['rid' => $regId]);

            $success = 'เช็คอินสมาชิกทุกคนเรียบร้อยแล้ว!';
        }

        // 4. เปลี่ยนสถานะเป็น: ผ่านเข้าสู่การจัดสาย (Qualified for Bracket)
        if ($action == 'set_status_qualified') {
            $regId = (int) $_POST['registration_id'];
            $pdo->prepare("
                UPDATE tournament_registrations 
                SET checkin_status = 'qualified', status = 'approved', checkin_at = IF(checkin_at IS NULL, NOW(), checkin_at)
                WHERE tournament_registration_id = :rid
            ")->execute(['rid' => $regId]);
            $success = 'กำหนดสถานะ "ผ่านเข้าสู่การจัดสายการแข่งขัน (Qualified)" เรียบร้อยแล้ว!';
        }

        // 5. ปรับสถานะเป็น: แพ้บาย / Walkover (WO)
        if ($action == 'set_status_walkover') {
            $regId = (int) $_POST['registration_id'];
            $pdo->prepare("
                UPDATE tournament_registrations 
                SET checkin_status = 'walkover', status = 'walkover'
                WHERE tournament_registration_id = :rid
            ")->execute(['rid' => $regId]);
            $success = 'ปรับสถานะเป็น "แพ้บาย / Walkover (WO)" เรียบร้อยแล้ว!';
        }

        // 6. ปรับสถานะเป็น: ถอนตัว (Withdrawn)
        if ($action == 'set_status_withdrawn') {
            $regId = (int) $_POST['registration_id'];
            $pdo->prepare("
                UPDATE tournament_registrations 
                SET checkin_status = 'withdrawn', status = 'withdrawn'
                WHERE tournament_registration_id = :rid
            ")->execute(['rid' => $regId]);
            $success = 'ปรับสถานะเป็น "ถอนตัว (Withdrawn)" เรียบร้อยแล้ว!';
        }

        // 7. อนุมัติการสมัคร (Approve Registration)
        if ($action == 'set_status_approved') {
            $regId = (int) $_POST['registration_id'];
            $pdo->prepare("
                UPDATE tournament_registrations 
                SET status = 'approved', checkin_status = 'not_checked_in'
                WHERE tournament_registration_id = :rid
            ")->execute(['rid' => $regId]);
            $success = 'อนุมัติการสมัครแข่งขันเรียบร้อยแล้ว (รอการ Check-in ในวันแข่ง)!';
        }

        // 8. รีเซ็ตสถานะกลับเป็น รอรายงานตัว (Reset to Not Checked-in)
        if ($action == 'set_status_reset') {
            $regId = (int) $_POST['registration_id'];
            $teamId = (int) ($_POST['team_id'] ?? 0);
            
            if ($teamId) {
                $pdo->prepare("DELETE FROM tournament_player_checkins WHERE tournament_id = :tid AND team_id = :team_id")
                    ->execute(['tid' => $tournamentId, 'team_id' => $teamId]);
            }
            $pdo->prepare("
                UPDATE tournament_registrations 
                SET checkin_status = 'not_checked_in', status = 'approved', checkin_at = NULL
                WHERE tournament_registration_id = :rid
            ")->execute(['rid' => $regId]);
            $success = 'รีเซ็ตสถานะการเช็คอินเรียบร้อยแล้ว';
        }
    }
}

// ดึงทัวร์นาเมนต์ทั้งหมด
$tournaments = $pdo->query("
    SELECT t.tournament_id, t.name, t.gender_category, g.name AS game_name, g.play_mode 
    FROM tournaments t 
    JOIN games g ON t.game_id = g.game_id
    WHERE t.status IN ('ongoing', 'registration_closed', 'registration_open') 
    ORDER BY t.created_at DESC
")->fetchAll();

$registrations = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'incomplete' => 0,
    'checked_in' => 0,
    'qualified' => 0,
    'walkover' => 0,
    'withdrawn' => 0
];

if ($tournamentId) {
    // ดึงข้อมูลการสมัครทั้งหมดในทัวร์นาเมนต์นี้
    if ($isSolo) {
        $sql = "
            SELECT tr.*, p.display_name AS participant_name, p.avatar_path, p.image_path,
                   u.username,
                   (SELECT COUNT(*) FROM tournament_player_checkins tpc WHERE tpc.tournament_id = tr.tournament_id AND tpc.player_id = tr.player_id) AS checked_in_members_count,
                   1 AS total_members_count
            FROM tournament_registrations tr
            JOIN players p ON p.player_id = tr.player_id
            JOIN users u ON u.user_id = p.user_id
            WHERE tr.tournament_id = :tid
        ";
        $params = ['tid' => $tournamentId];

        if ($teamSearch !== '') {
            $sql .= " AND (p.display_name LIKE :search OR u.username LIKE :search2)";
            $params['search'] = "%{$teamSearch}%";
            $params['search2'] = "%{$teamSearch}%";
        }
        $sql .= " ORDER BY tr.registered_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $registrations = $stmt->fetchAll();
    } else {
        $sql = "
            SELECT tr.*, t.name AS participant_name, t.logo_path,
                   (SELECT COUNT(DISTINCT tpc.player_id) 
                    FROM tournament_player_checkins tpc 
                    JOIN team_members tm ON tm.player_id = tpc.player_id AND tm.team_id = tr.team_id AND tm.is_active = 1
                    WHERE tpc.tournament_id = tr.tournament_id) AS checked_in_members_count,
                   (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = tr.team_id AND tm.is_active = 1) AS total_members_count
            FROM tournament_registrations tr
            JOIN teams t ON t.team_id = tr.team_id
            WHERE tr.tournament_id = :tid
        ";
        $params = ['tid' => $tournamentId];

        if ($teamSearch !== '') {
            $sql .= " AND t.name LIKE :search";
            $params['search'] = "%{$teamSearch}%";
        }
        $sql .= " ORDER BY tr.registered_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $registrations = $stmt->fetchAll();
    }

    // คำนวณสรุปสถิติ 7 สถานะ
    $allStmt = $pdo->prepare("SELECT status, checkin_status FROM tournament_registrations WHERE tournament_id = :tid");
    $allStmt->execute(['tid' => $tournamentId]);
    $allRows = $allStmt->fetchAll();

    $stats['total'] = count($allRows);
    foreach ($allRows as $row) {
        $st = $row['status'];
        $cst = $row['checkin_status'];

        if ($st === 'pending') {
            $stats['pending']++;
        } elseif ($st === 'walkover' || $cst === 'walkover') {
            $stats['walkover']++;
        } elseif ($st === 'withdrawn' || $cst === 'withdrawn') {
            $stats['withdrawn']++;
        } elseif ($cst === 'qualified') {
            $stats['qualified']++;
        } elseif ($cst === 'checked_in') {
            $stats['checked_in']++;
        } elseif ($cst === 'incomplete') {
            $stats['incomplete']++;
        } elseif ($st === 'approved') {
            $stats['approved']++;
        }
    }

    // กรองตามแท็บที่เลือก
    if ($statusFilter !== 'all') {
        $registrations = array_filter($registrations, function($r) use ($statusFilter) {
            $st = $r['status'];
            $cst = $r['checkin_status'];

            if ($statusFilter === 'pending') return $st === 'pending';
            if ($statusFilter === 'approved') return $st === 'approved' && $cst === 'not_checked_in';
            if ($statusFilter === 'incomplete') return $cst === 'incomplete';
            if ($statusFilter === 'checked_in') return $cst === 'checked_in';
            if ($statusFilter === 'qualified') return $cst === 'qualified';
            if ($statusFilter === 'walkover') return $st === 'walkover' || $cst === 'walkover';
            if ($statusFilter === 'withdrawn') return $st === 'withdrawn' || $cst === 'withdrawn';
            return true;
        });
    }
}

// ดึงข้อมูลสมาชิกของทุกทีมในทัวร์นาเมนต์นี้สำหรับ Individual Modal
$teamRostersMap = [];
if ($tournamentId && !$isSolo) {
    $rosterStmt = $pdo->prepare("
        SELECT tm.team_id, tm.player_id, tm.in_game_role, tm.is_substitute,
               p.display_name, p.avatar_path, p.image_path,
               (SELECT COUNT(*) FROM tournament_player_checkins tpc WHERE tpc.tournament_id = :tid AND tpc.player_id = tm.player_id) AS is_checked_in
        FROM team_members tm
        JOIN players p ON p.player_id = tm.player_id
        JOIN tournament_registrations tr ON tr.team_id = tm.team_id AND tr.tournament_id = :tid2
        WHERE tm.is_active = 1
        ORDER BY tm.is_substitute ASC, p.display_name ASC
    ");
    $rosterStmt->execute(['tid' => $tournamentId, 'tid2' => $tournamentId]);
    $allRosters = $rosterStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allRosters as $rItem) {
        $teamRostersMap[$rItem['team_id']][] = $rItem;
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบเช็คอินก่อนจัดสายการแข่งขัน - Korat Esport</title>
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,300;0,400;0,500;0,600;0,700;1,800&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            orange: '#FF5500',
                            glow: '#FF6600',
                            lightbg: '#F4F6F9',
                            sidebar: '#0F172A',
                        }
                    },
                    fontFamily: {
                        sans: ['Kanit', 'sans-serif'],
                        display: ['Orbitron', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { display: none; }
        html, body {
            -ms-overflow-style: none;
            scrollbar-width: none;
            background-color: #F4F6F9;
        }
        .nav-item { transition: all 0.2s ease; }
        .nav-item:hover, .nav-item.active {
            background: rgba(255, 85, 0, 0.12);
            color: #FF5500;
            border-left: 4px solid #FF5500;
        }
    </style>
</head>
<body class="text-slate-800 font-sans min-h-screen flex antialiased">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-brand-sidebar text-slate-300 flex flex-col fixed inset-y-0 left-0 z-50 shadow-xl">
        <div class="p-6 border-b border-slate-800 flex items-center gap-3">
            <img src="../assets/img/logo.png" alt="Korat Esport" class="h-10 w-auto filter drop-shadow" onError="this.src='https://placehold.co/80x80/0F172A/FF5500?text=KE';">
            <div>
                <h1 class="font-display font-black text-lg text-white tracking-wider">KORAT <span class="text-brand-orange">ESPORT</span></h1>
                <p class="text-[10px] tracking-widest text-slate-400 uppercase font-semibold">Admin Command Center</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-1 text-sm font-medium">
            <a href="dashboard.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-r-xl text-slate-400 hover:text-white">
                <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                <span>หน้าหลัก (Dashboard)</span>
            </a>
            <a href="manage-tournament.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-r-xl text-slate-400 hover:text-white">
                <i class="fa-solid fa-trophy w-5 text-center"></i>
                <span>จัดการทัวร์นาเมนต์</span>
            </a>
            <a href="manage-teams.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-r-xl text-slate-400 hover:text-white">
                <i class="fa-solid fa-people-group w-5 text-center"></i>
                <span>จัดการทีม/ผู้สมัคร</span>
            </a>
            <a href="manage-members.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-r-xl text-slate-400 hover:text-white">
                <i class="fa-solid fa-users-gear w-5 text-center"></i>
                <span>จัดการสมาชิก</span>
            </a>
            <a href="manage-news.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-r-xl text-slate-400 hover:text-white">
                <i class="fa-solid fa-newspaper w-5 text-center"></i>
                <span>จัดการข่าวสาร</span>
            </a>
            <a href="manage-gallery.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-r-xl text-slate-400 hover:text-white">
                <i class="fa-solid fa-images w-5 text-center"></i>
                <span>จัดการแกลเลอรี่</span>
            </a>
            <a href="recommended-lodging.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-r-xl text-slate-400 hover:text-white">
                <i class="fa-solid fa-hotel w-5 text-center"></i>
                <span>ที่พักแนะนำ</span>
            </a>
            <a href="record-match.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-r-xl text-slate-400 hover:text-white">
                <i class="fa-solid fa-pen-to-square w-5 text-center"></i>
                <span>บันทึกผลแมตช์</span>
            </a>
            <a href="checkin-teams.php" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-r-xl text-white">
                <i class="fa-solid fa-user-check w-5 text-center text-brand-orange"></i>
                <span>เช็คอิน & ตรวจสอบสายแข่ง</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-9 h-9 rounded-full bg-brand-orange text-white flex items-center justify-center font-bold text-sm shrink-0">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="truncate">
                        <div class="text-sm font-bold text-white truncate">
                            <?= htmlspecialchars($currentUser['username'] ?? 'Admin User') ?>
                        </div>
                        <span class="inline-block text-[10px] font-semibold text-brand-orange bg-brand-orange/10 px-2 py-0.2 rounded uppercase">
                            <?= htmlspecialchars($currentUser['role'] ?? 'Administrator') ?>
                        </span>
                    </div>
                </div>
                <a href="../auth/logout.php" title="ออกจากระบบ" class="text-slate-400 hover:text-rose-400 transition-colors p-2 text-base">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 ml-64 min-h-screen flex flex-col">

        <!-- Header Panel -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-0 z-40 shadow-sm">
            <div>
                <h1 class="text-xl font-extrabold font-display text-slate-900 tracking-wide uppercase flex items-center gap-2">
                    <span class="w-2 h-6 bg-brand-orange rounded-full inline-block"></span>
                    ระบบ Check-in ก่อนจัดสายการแข่งขัน <span class="text-brand-orange">(PRE-BRACKET CHECK-IN)</span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">คัดกรองนักกีฬารายบุคคล/รายทีมก่อนจัดสายการแข่งขัน ป้องกันการใส่ทีมที่สละสิทธิ์หรือแพ้บายเข้าสายแข่ง</p>
            </div>
            
            <a href="../pages/index.php" target="_blank" class="text-xs font-semibold text-slate-600 hover:text-brand-orange transition-colors flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg">
                <i class="fa-solid fa-globe"></i> หน้าหลักเว็บไซต์
            </a>
        </header>

        <main class="p-8 space-y-6 flex-1">

            <!-- SELECT TOURNAMENT CARD -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase text-slate-700 tracking-wider">
                            <i class="fa-solid fa-trophy text-brand-orange mr-1"></i> เลือกทัวร์นาเมนต์ที่ต้องการดำเนินการ
                        </label>
                        <form method="GET" action="checkin-teams.php" id="tournamentSelectForm">
                            <select name="tournament_id" onchange="this.form.submit()" 
                                class="w-full md:w-96 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 font-medium focus:bg-white focus:outline-none focus:border-brand-orange transition-all cursor-pointer">
                                <option value="">-- กรุณาเลือกรายการแข่งขัน --</option>
                                <?php foreach ($tournaments as $t): ?>
                                    <?php 
                                        $genderLabel = '';
                                        if ($t['gender_category'] == 'male') $genderLabel = ' [รุ่นชาย]';
                                        elseif ($t['gender_category'] == 'female') $genderLabel = ' [รุ่นหญิง]';
                                        else $genderLabel = ' [ทั่วไป]';
                                    ?>
                                    <option value="<?= $t['tournament_id']; ?>" <?= ($t['tournament_id'] == $tournamentId) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($t['name'] . $genderLabel); ?> [<?= htmlspecialchars($t['game_name']); ?> - <?= ($t['play_mode'] === 'solo') ? 'เดี่ยว' : 'ทีม'; ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <?php if ($tournamentId): ?>
                        <div class="flex items-center gap-3">
                            <a href="manage-tournament.php" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all flex items-center gap-1.5">
                                <i class="fa-solid fa-sitemap text-brand-orange"></i> จัดการสายแข่ง (Bracket)
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($tournamentId): ?>

                <!-- Alert Messages -->
                <?php if ($error): ?>
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-3 shadow-sm">
                        <i class="fa-solid fa-circle-xmark text-xl shrink-0 text-rose-500"></i>
                        <span><?= htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3 shadow-sm">
                        <i class="fa-solid fa-circle-check text-xl shrink-0 text-emerald-500"></i>
                        <span class="font-bold"><?= htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <!-- STAT COUNTERS (7 STATUSES) -->
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm text-center">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">ทั้งหมด</span>
                        <span class="text-xl font-black font-display text-slate-800"><?= $stats['total']; ?></span>
                    </div>
                    <div class="bg-blue-50/60 p-3.5 rounded-2xl border border-blue-200 shadow-sm text-center">
                        <span class="text-[10px] font-bold uppercase text-blue-600 block">สมัครแล้ว</span>
                        <span class="text-xl font-black font-display text-blue-800"><?= $stats['pending']; ?></span>
                    </div>
                    <div class="bg-indigo-50/60 p-3.5 rounded-2xl border border-indigo-200 shadow-sm text-center">
                        <span class="text-[10px] font-bold uppercase text-indigo-600 block">ได้รับอนุมัติ</span>
                        <span class="text-xl font-black font-display text-indigo-800"><?= $stats['approved']; ?></span>
                    </div>
                    <div class="bg-amber-50/60 p-3.5 rounded-2xl border border-amber-200 shadow-sm text-center">
                        <span class="text-[10px] font-bold uppercase text-amber-600 block">เช็คอินไม่ครบ</span>
                        <span class="text-xl font-black font-display text-amber-800"><?= $stats['incomplete']; ?></span>
                    </div>
                    <div class="bg-emerald-50/60 p-3.5 rounded-2xl border border-emerald-200 shadow-sm text-center">
                        <span class="text-[10px] font-bold uppercase text-emerald-600 block">Check-in แล้ว</span>
                        <span class="text-xl font-black font-display text-emerald-800"><?= $stats['checked_in']; ?></span>
                    </div>
                    <div class="bg-orange-50/60 p-3.5 rounded-2xl border border-orange-200 shadow-sm text-center ring-2 ring-brand-orange/30">
                        <span class="text-[10px] font-bold uppercase text-brand-orange block">เข้าสู่จัดสาย</span>
                        <span class="text-xl font-black font-display text-brand-orange"><?= $stats['qualified']; ?></span>
                    </div>
                    <div class="bg-rose-50/60 p-3.5 rounded-2xl border border-rose-200 shadow-sm text-center">
                        <span class="text-[10px] font-bold uppercase text-rose-600 block">แพ้บาย/WO</span>
                        <span class="text-xl font-black font-display text-rose-800"><?= $stats['walkover']; ?></span>
                    </div>
                    <div class="bg-slate-100 p-3.5 rounded-2xl border border-slate-300 shadow-sm text-center">
                        <span class="text-[10px] font-bold uppercase text-slate-500 block">ถอนตัว</span>
                        <span class="text-xl font-black font-display text-slate-700"><?= $stats['withdrawn']; ?></span>
                    </div>
                </div>

                <!-- SCANNER & INPUT SECTION -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-3 gap-2">
                        <div>
                            <h2 class="text-base font-bold font-display text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-qrcode text-brand-orange text-lg"></i>
                                สแกน QR Code หรือกรอกรหัสเช็คอิน
                            </h2>
                            <p class="text-xs text-slate-500">ใช้เครื่องสแกนบาร์โค้ดยิง QR Code จากหน้าโปรไฟล์ของนักกีฬา/กัปตัน หรือพิมพ์ Token 10 หลัก</p>
                        </div>
                        <div class="text-xs text-slate-500 font-medium">
                            กติกาเกมนี้ต้องการ: <strong class="text-brand-orange"><?= $isSolo ? 'นักกีฬาเดี่ยว 1 คน' : "สมาชิกตัวจริงอย่างน้อย {$minRosterRequired} คน"; ?></strong>
                        </div>
                    </div>

                    <form method="POST" class="flex flex-col sm:flex-row gap-3 pt-1">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">
                        <input type="hidden" name="action" value="checkin">
                        
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-barcode text-base"></i>
                            </span>
                            <input type="text" name="token" autofocus required
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl pl-12 pr-4 py-3 text-base font-mono font-bold text-slate-900 tracking-widest uppercase focus:bg-white focus:outline-none focus:border-brand-orange transition-all placeholder-slate-400"
                                placeholder="สแกน หรือพิมพ์รหัส Token ที่นี่...">
                        </div>

                        <button type="submit" 
                            class="px-8 py-3 rounded-xl bg-brand-orange hover:bg-brand-glow text-white font-bold text-sm uppercase tracking-wider transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer shrink-0">
                            <i class="fa-solid fa-user-check"></i>
                            <span>ยืนยันเช็คอินทันที</span>
                        </button>
                    </form>
                </div>

                <!-- FILTER TABS & SEARCH -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                        
                        <!-- Status Tabs -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-full">
                            <?php
                                $tabs = [
                                    'all' => ['label' => 'ทั้งหมด', 'count' => $stats['total']],
                                    'pending' => ['label' => 'สมัครแล้ว', 'count' => $stats['pending']],
                                    'approved' => ['label' => 'ได้รับอนุมัติ', 'count' => $stats['approved']],
                                    'incomplete' => ['label' => 'เช็คอินไม่ครบ', 'count' => $stats['incomplete']],
                                    'checked_in' => ['label' => 'Check-in แล้ว', 'count' => $stats['checked_in']],
                                    'qualified' => ['label' => 'เข้าสู่จัดสาย', 'count' => $stats['qualified']],
                                    'walkover' => ['label' => 'แพ้บาย/WO', 'count' => $stats['walkover']],
                                    'withdrawn' => ['label' => 'ถอนตัว', 'count' => $stats['withdrawn']],
                                ];
                            ?>
                            <?php foreach ($tabs as $key => $tab): ?>
                                <a href="checkin-teams.php?tournament_id=<?= $tournamentId; ?>&status_filter=<?= $key; ?>&team_search=<?= urlencode($teamSearch); ?>"
                                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 <?= ($statusFilter === $key) ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                                    <span><?= $tab['label']; ?></span>
                                    <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= ($statusFilter === $key) ? 'bg-brand-orange text-white' : 'bg-slate-200 text-slate-700'; ?>"><?= $tab['count']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <!-- Live Search Input -->
                        <form method="GET" action="checkin-teams.php" class="relative shrink-0 w-full md:w-64">
                            <input type="hidden" name="tournament_id" value="<?= $tournamentId; ?>">
                            <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter); ?>">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" name="team_search" value="<?= htmlspecialchars($teamSearch); ?>" placeholder="ค้นหาชื่อทีม / ผู้เล่น..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-8 py-2 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-brand-orange font-medium">
                            <?php if ($teamSearch !== ''): ?>
                                <a href="checkin-teams.php?tournament_id=<?= $tournamentId; ?>&status_filter=<?= htmlspecialchars($statusFilter); ?>" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-rose-500">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- PARTICIPANTS DATA TABLE -->
                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="p-3.5"><?= $isSolo ? 'ผู้เล่น / นักกีฬา' : 'ทีม / สโมสร'; ?></th>
                                    <th class="p-3.5 text-center">สมาชิกที่เช็คอิน</th>
                                    <th class="p-3.5 text-center">รหัส Token</th>
                                    <th class="p-3.5 text-center">สถานะการแข่งขัน</th>
                                    <th class="p-3.5 text-center">เวลา Check-in</th>
                                    <th class="p-3.5 text-right">การจัดการสถานะ (Actions)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php if (count($registrations) == 0): ?>
                                    <tr>
                                        <td colspan="6" class="p-10 text-center text-slate-400 text-xs">
                                            <i class="fa-solid fa-inbox text-2xl mb-2 text-slate-300 block"></i>
                                            ไม่พบข้อมูลผู้เข้าแข่งขันในสถานะนี้
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($registrations as $r): ?>
                                    <?php
                                        $st = $r['status'];
                                        $cst = $r['checkin_status'];
                                        $checkedCount = (int) ($r['checked_in_members_count'] ?? 0);
                                        $totalCount = (int) ($r['total_members_count'] ?? 1);

                                        // กำหนดสถานะและสี Badge ให้ชัดเจนทั้ง 7 สถานะ
                                        $badgeHtml = '';
                                        if ($st === 'walkover' || $cst === 'walkover') {
                                            $badgeHtml = '<span class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-bold flex items-center gap-1 justify-center"><i class="fa-solid fa-ban"></i> แพ้บาย / WO</span>';
                                        } elseif ($st === 'withdrawn' || $cst === 'withdrawn') {
                                            $badgeHtml = '<span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-300 text-[11px] font-bold flex items-center gap-1 justify-center"><i class="fa-solid fa-user-xmark"></i> ถอนตัว</span>';
                                        } elseif ($cst === 'qualified') {
                                            $badgeHtml = '<span class="px-2.5 py-1 rounded-full bg-orange-50 text-brand-orange border border-orange-300 text-[11px] font-bold flex items-center gap-1 justify-center shadow-xs"><i class="fa-solid fa-trophy"></i> ผ่านเข้าสู่การจัดสาย</span>';
                                        } elseif ($cst === 'checked_in') {
                                            $badgeHtml = '<span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold flex items-center gap-1 justify-center"><i class="fa-solid fa-circle-check"></i> Check-in แล้ว</span>';
                                        } elseif ($cst === 'incomplete') {
                                            $badgeHtml = '<span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-300 text-[11px] font-bold flex items-center gap-1 justify-center"><i class="fa-solid fa-triangle-exclamation"></i> Check-in ไม่ครบ</span>';
                                        } elseif ($st === 'approved') {
                                            $badgeHtml = '<span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-[11px] font-bold flex items-center gap-1 justify-center"><i class="fa-solid fa-file-circle-check"></i> ได้รับอนุมัติแล้ว</span>';
                                        } else {
                                            $badgeHtml = '<span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-bold flex items-center gap-1 justify-center"><i class="fa-solid fa-clock"></i> สมัครแล้ว (รอตรวจ)</span>';
                                        }

                                        $isQualified = ($cst === 'checked_in' || $cst === 'qualified');
                                    ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors <?= $isQualified ? 'bg-emerald-50/10' : ''; ?>">
                                        <td class="p-3.5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center font-bold text-brand-orange shrink-0">
                                                    <?php 
                                                        $img = $isSolo ? ($r['avatar_path'] ?? $r['image_path'] ?? null) : ($r['logo_path'] ?? null);
                                                    ?>
                                                    <?php if ($img && file_exists('../assets/' . $img)): ?>
                                                        <img src="../assets/<?= htmlspecialchars($img); ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <i class="fa-solid <?= $isSolo ? 'fa-user' : 'fa-shield'; ?>"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <span class="font-bold text-slate-900 text-xs block"><?= htmlspecialchars($r['participant_name']); ?></span>
                                                    <span class="text-[10px] text-slate-400">สมัครเมื่อ: <?= date('d/m/Y H:i', strtotime($r['registered_at'])); ?></span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="p-3.5 text-center">
                                            <?php if ($isSolo): ?>
                                                <span class="text-xs font-bold <?= $checkedCount > 0 ? 'text-emerald-600' : 'text-slate-400'; ?>">
                                                    <?= $checkedCount > 0 ? '1/1 คน (เช็คอินแล้ว)' : '0/1 คน (ยังไม่มา)'; ?>
                                                </span>
                                            <?php else: ?>
                                                <div class="flex flex-col items-center gap-1">
                                                    <span class="text-xs font-bold <?= $checkedCount >= $minRosterRequired ? 'text-emerald-600' : ($checkedCount > 0 ? 'text-amber-600' : 'text-slate-400'); ?>">
                                                        <?= $checkedCount; ?> / <?= max($totalCount, $minRosterRequired); ?> คน
                                                    </span>
                                                    <div class="w-20 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                        <?php $pct = min(100, round(($checkedCount / max(1, $totalCount, $minRosterRequired)) * 100)); ?>
                                                        <div class="h-full <?= $checkedCount >= $minRosterRequired ? 'bg-emerald-500' : ($checkedCount > 0 ? 'bg-amber-500' : 'bg-slate-300'); ?>" style="width: <?= $pct; ?>%"></div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td class="p-3.5 text-center font-mono text-xs font-bold text-slate-600 tracking-wider">
                                            <?= htmlspecialchars($r['qr_code_token'] ?? '-'); ?>
                                        </td>

                                        <td class="p-3.5 text-center">
                                            <?= $badgeHtml; ?>
                                        </td>

                                        <td class="p-3.5 text-center text-xs text-slate-500">
                                            <?= !empty($r['checkin_at']) ? date('H:i:s d/m/Y', strtotime($r['checkin_at'])) : '<span class="text-slate-300">-</span>'; ?>
                                        </td>

                                        <td class="p-3.5 text-right">
                                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                                <?php if (!$isSolo): ?>
                                                    <!-- ปุ่มเปิด Modal เช็คอินรายบุคคล -->
                                                    <button type="button" onclick="openRosterModal(<?= $r['team_id']; ?>, '<?= htmlspecialchars(addslashes($r['participant_name'])); ?>')"
                                                            class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
                                                            title="ตรวจสอบและเช็คอินรายบุคคล">
                                                        <i class="fa-solid fa-users-viewfinder text-brand-orange"></i>
                                                        <span>รายบุคคล</span>
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Quick Check-in -->
                                                <?php if ($cst !== 'checked_in' && $cst !== 'qualified'): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="<?= $isSolo ? 'checkin' : 'quick_checkin_all'; ?>">
                                                        <input type="hidden" name="registration_id" value="<?= $r['tournament_registration_id']; ?>">
                                                        <input type="hidden" name="team_id" value="<?= $r['team_id'] ?? 0; ?>">
                                                        <input type="hidden" name="token" value="<?= htmlspecialchars($r['qr_code_token'] ?? ''); ?>">
                                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition-all flex items-center gap-1 shadow-xs cursor-pointer" title="เช็คอินทันที">
                                                            <i class="fa-solid fa-check"></i>
                                                            <span>เช็คอิน</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Qualify for Bracket -->
                                                <?php if ($cst === 'checked_in'): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="set_status_qualified">
                                                        <input type="hidden" name="registration_id" value="<?= $r['tournament_registration_id']; ?>">
                                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-brand-orange hover:bg-brand-glow text-white text-xs font-bold transition-all flex items-center gap-1 shadow-xs cursor-pointer" title="ผ่านเข้าสู่การจัดสาย">
                                                            <i class="fa-solid fa-award"></i>
                                                            <span>พร้อมจัดสาย</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Approve Registration (If Pending) -->
                                                <?php if ($st === 'pending'): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="set_status_approved">
                                                        <input type="hidden" name="registration_id" value="<?= $r['tournament_registration_id']; ?>">
                                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all flex items-center gap-1 shadow-xs cursor-pointer" title="อนุมัติการสมัคร">
                                                            <i class="fa-solid fa-thumbs-up"></i>
                                                            <span>อนุมัติ</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Walkover / WO -->
                                                <?php if ($st !== 'walkover' && $cst !== 'walkover'): ?>
                                                    <form method="POST" onsubmit="return confirm('คุณต้องการปรับสถานะเป็น แพ้บาย/Walkover (WO) ใช่หรือไม่? (ทีมนี้จะไม่ถูกนำไปจัดสายการแข่งขัน)')">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="set_status_walkover">
                                                        <input type="hidden" name="registration_id" value="<?= $r['tournament_registration_id']; ?>">
                                                        <button type="submit" class="px-2 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition-all flex items-center gap-1 cursor-pointer" title="ปรับเป็นแพ้บาย (WO)">
                                                            <i class="fa-solid fa-ban"></i>
                                                            <span>WO</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Withdrawn -->
                                                <?php if ($st !== 'withdrawn' && $cst !== 'withdrawn'): ?>
                                                    <form method="POST" onsubmit="return confirm('ยืนยันปรับสถานะเป็น ถอนตัว (Withdrawn)?')">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="set_status_withdrawn">
                                                        <input type="hidden" name="registration_id" value="<?= $r['tournament_registration_id']; ?>">
                                                        <button type="submit" class="px-2 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-all cursor-pointer" title="ถอนตัว">
                                                            <i class="fa-solid fa-xmark"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Reset Button -->
                                                <?php if ($cst !== 'not_checked_in' || $st === 'walkover' || $st === 'withdrawn'): ?>
                                                    <form method="POST" onsubmit="return confirm('รีเซ็ตสถานะกลับเป็น รอรายงานตัว?')">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="set_status_reset">
                                                        <input type="hidden" name="registration_id" value="<?= $r['tournament_registration_id']; ?>">
                                                        <input type="hidden" name="team_id" value="<?= $r['team_id'] ?? 0; ?>">
                                                        <button type="submit" class="px-2 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 text-xs font-bold transition-all cursor-pointer" title="รีเซ็ต">
                                                            <i class="fa-solid fa-rotate-left"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MODAL เช็คอินนักกีฬารายบุคคล (Individual Player Modal) -->
                <div id="rosterCheckinModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
                    <div class="bg-white max-w-lg w-full rounded-3xl p-6 border border-slate-200 shadow-2xl space-y-4 max-h-[90vh] flex flex-col">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-brand-orange/10 text-brand-orange flex items-center justify-center font-bold">
                                    <i class="fa-solid fa-user-check"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold font-display text-slate-900">เช็คอินนักกีฬารายบุคคล</h3>
                                    <p class="text-xs text-slate-500">ทีม <span id="modalTeamTitle" class="font-bold text-brand-orange"></span> (เกณฑ์ขั้นต่ำ: <?= $minRosterRequired; ?> คน)</p>
                                </div>
                            </div>
                            <button type="button" onclick="closeRosterModal()" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div id="modalMemberList" class="space-y-2 overflow-y-auto flex-1 pr-1">
                            <!-- รายชื่อสมาชิกจะถูก Render ผ่าน JavaScript -->
                        </div>

                        <div class="border-t border-slate-100 pt-3 flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">ติ๊กถูกเพื่อเช็คอินสมาชิกแต่ละคน</span>
                            <button type="button" onclick="closeRosterModal()" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold cursor-pointer">
                                ปิดหน้าต่าง
                            </button>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <!-- SCRIPT FOR INDIVIDUAL CHECK-IN MODAL & ACTIONS -->
    <script>
        const teamRosters = <?= json_encode($teamRostersMap); ?>;
        const tournamentId = <?= (int) $tournamentId; ?>;
        const csrfToken = "<?= $csrfToken; ?>";

        function openRosterModal(teamId, teamName) {
            document.getElementById('modalTeamTitle').innerText = teamName;
            const container = document.getElementById('modalMemberList');
            container.innerHTML = '';

            const members = teamRosters[teamId] || [];

            if (members.length === 0) {
                container.innerHTML = '<div class="p-6 text-center text-slate-400 text-xs">ไม่พบรายชื่อสมาชิกในทีมนี้</div>';
            } else {
                members.forEach(m => {
                    const row = document.createElement('div');
                    row.className = 'p-3 rounded-2xl border flex items-center justify-between gap-3 ' + (m.is_checked_in > 0 ? 'bg-emerald-50/60 border-emerald-200' : 'bg-slate-50 border-slate-200');

                    const avatarSrc = m.avatar_path || m.image_path;
                    const avatarHtml = avatarSrc ? 
                        `<img src="../assets/${escapeHtml(avatarSrc)}" class="w-8 h-8 rounded-xl object-cover border border-slate-200 shrink-0">` :
                        `<div class="w-8 h-8 rounded-xl bg-slate-200 flex items-center justify-center text-brand-orange font-bold text-xs shrink-0"><i class="fa-solid fa-user"></i></div>`;

                    const roleTag = m.in_game_role ? `<span class="text-[10px] bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-semibold ml-1.5">${escapeHtml(m.in_game_role)}</span>` : '';
                    const subTag = m.is_substitute == 1 ? `<span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-bold ml-1">ตัวสำรอง</span>` : `<span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold ml-1">ตัวจริง</span>`;

                    row.innerHTML = `
                        <div class="flex items-center gap-3">
                            ${avatarHtml}
                            <div>
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="text-xs font-bold text-slate-900">${escapeHtml(m.display_name)}</span>
                                    ${subTag}
                                    ${roleTag}
                                </div>
                                <span class="text-[10px] text-slate-400">${m.is_checked_in > 0 ? '<i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i>เช็คอินแล้ว' : '<i class="fa-solid fa-clock text-amber-500 mr-1"></i>ยังไม่มารายงานตัว'}</span>
                            </div>
                        </div>

                        <form method="POST" class="shrink-0">
                            <input type="hidden" name="csrf_token" value="${csrfToken}">
                            <input type="hidden" name="action" value="toggle_player_checkin">
                            <input type="hidden" name="team_id" value="${teamId}">
                            <input type="hidden" name="player_id" value="${m.player_id}">
                            <input type="hidden" name="is_checked" value="${m.is_checked_in > 0 ? 0 : 1}">
                            
                            <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer ${m.is_checked_in > 0 ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-xs'}">
                                <i class="fa-solid ${m.is_checked_in > 0 ? 'fa-xmark' : 'fa-check'}"></i>
                                <span>${m.is_checked_in > 0 ? 'ยกเลิก' : 'เช็คอินคนนี้'}</span>
                            </button>
                        </form>
                    `;
                    container.appendChild(row);
                });
            }

            document.getElementById('rosterCheckinModal').classList.remove('hidden');
        }

        function closeRosterModal() {
            document.getElementById('rosterCheckinModal').classList.add('hidden');
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>