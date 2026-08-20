<?php
require_once 'config/db.php';

// 1. ตรวจสอบหรือสร้าง Profile สำหรับ test_player08 (user_id = 17)
$pStmt = $pdo->prepare("SELECT player_id FROM players WHERE user_id = 17");
$pStmt->execute();
$pid = (int) $pStmt->fetchColumn();

if (!$pid) {
    $ins = $pdo->prepare("
        INSERT INTO players (user_id, display_name, bio, category)
        VALUES (17, 'test_player08', 'กัปตันทีม RoV & Pro Athlete ตัวแทนจังหวัดนครราชสีมา แชมป์ Korat Esport Championship 🏆', 'open')
    ");
    $ins->execute();
    $pid = (int) $pdo->lastInsertId();
    echo "Created player ID $pid for user 17\n";
} else {
    $pdo->prepare("
        UPDATE players 
        SET bio = 'กัปตันทีม RoV & Pro Athlete ตัวแทนจังหวัดนครราชสีมา แชมป์ Korat Esport Championship 🏆'
        WHERE player_id = :pid
    ")->execute(['pid' => $pid]);
    echo "Updated player ID $pid\n";
}

// 2. สร้างทีม 'KORAT VIPERS' ให้กับ test_player08 ถ้ายังไม่มี
$tStmt = $pdo->prepare("SELECT team_id FROM teams WHERE captain_player_id = :pid LIMIT 1");
$tStmt->execute(['pid' => $pid]);
$teamId = (int) $tStmt->fetchColumn();

if (!$teamId) {
    $insTeam = $pdo->prepare("
        INSERT INTO teams (name, game_id, captain_player_id, category)
        VALUES ('KORAT VIPERS', 1, :pid, 'open')
    ");
    $insTeam->execute(['pid' => $pid]);
    $teamId = (int) $pdo->lastInsertId();
    echo "Created team KORAT VIPERS (team_id $teamId)\n";

    // เพิ่ม test_player08 เป็นสมาชิกในทีม
    $pdo->prepare("
        INSERT INTO team_members (team_id, player_id, in_game_role, is_active)
        VALUES (:tid, :pid, 'Captain & Carry', 1)
    ")->execute(['tid' => $teamId, 'pid' => $pid]);
}

// 3. ลงทะเบียนทัวร์นาเมนต์ให้ทีมนี้ (Tournament ID 21 หรือ 19)
$chkReg = $pdo->prepare("SELECT tournament_registration_id FROM tournament_registrations WHERE team_id = :tid");
$chkReg->execute(['tid' => $teamId]);
if (!$chkReg->fetchColumn()) {
    $qrToken = strtoupper(bin2hex(random_bytes(5)));
    $pdo->prepare("
        INSERT INTO tournament_registrations (tournament_id, team_id, status, qr_code_token, checkin_status, registered_at)
        VALUES (21, :tid, 'approved', :token, 'not_checked_in', NOW())
    ")->execute(['tid' => $teamId, 'token' => $qrToken]);
    echo "Registered team $teamId for tournament 21 with QR Token $qrToken\n";
}

// 4. เพิ่มประวัติแมตช์การแข่งขันให้ทีม (Tournament 21)
$chkMatch = $pdo->prepare("SELECT match_id FROM matches WHERE team1_id = :tid OR team2_id = :tid");
$chkMatch->execute(['tid' => $teamId]);
if (!$chkMatch->fetchColumn()) {
    $pdo->prepare("
        INSERT INTO matches (tournament_id, round_number, match_index, team1_id, team2_id, team1_score, team2_score, winner_team_id, status, completed_at)
        VALUES (21, 1, 1, :tid, 63, 2, 1, :tid, 'completed', NOW())
    ")->execute(['tid' => $teamId]);
    echo "Created match history for team $teamId\n";
}

echo "Setup for test_player08 completed successfully!\n";
