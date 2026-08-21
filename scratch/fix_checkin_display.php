<?php
require_once __DIR__ . '/../config/db.php';

// 1. อัปเดตข้อมูลขนาดทีมในตาราง games ให้ถูกต้องตามประเภทเกม
$pdo->exec("
    UPDATE games 
    SET roster_size_min = 5, roster_size_max = 6, play_mode = 'team', is_team_based = 1 
    WHERE game_id IN (1, 6, 26) OR name LIKE '%RoV%' OR name LIKE '%Arena of Valor%'
");

$pdo->exec("
    UPDATE games 
    SET roster_size_min = 4, roster_size_max = 5, play_mode = 'team', is_team_based = 1 
    WHERE game_id IN (2, 27) OR name LIKE '%Free Fire%' OR name LIKE '%Freefire%'
");

echo "Updated games roster sizes successfully.\n";

// 2. ทำการเช็คอินสมาชิกทุกคนของทั้ง 8 ทีมใน Tournament 23 (korat esport ep1) เข้าตาราง tournament_player_checkins
$tournamentId = 23;
$teamNames = [
    'FULL SENSE', 'PSG ESPORTS', 'BLACK PEARL ESPORT', 'PHOENIX FORCE',
    'NEXUS GAMING', 'DRAGON X ESPORT', 'SHADOW WOLVES', 'CYBER KNIGHTS'
];

$inClause = "'" . implode("','", $teamNames) . "'";
$teams = $pdo->query("SELECT team_id, name FROM teams WHERE name IN ($inClause)")->fetchAll(PDO::FETCH_ASSOC);

$totalCheckins = 0;
foreach ($teams as $t) {
    $teamId = $t['team_id'];
    
    // ดึงสมาชิกทุกคนในทีม
    $mStmt = $pdo->prepare("SELECT player_id FROM team_members WHERE team_id = ? AND is_active = 1");
    $mStmt->execute([$teamId]);
    $members = $mStmt->fetchAll(PDO::FETCH_COLUMN);

    $ins = $pdo->prepare("
        INSERT IGNORE INTO tournament_player_checkins (tournament_id, team_id, player_id, checked_in_at)
        VALUES (?, ?, ?, NOW())
    ");

    foreach ($members as $pId) {
        $ins->execute([$tournamentId, $teamId, $pId]);
        $totalCheckins++;
    }
    
    // อัปเดตสถานะของทีมใน tournament_registrations เป็น checked_in
    $pdo->prepare("
        UPDATE tournament_registrations 
        SET checkin_status = 'checked_in', status = 'approved', checkin_at = IF(checkin_at IS NULL, NOW(), checkin_at)
        WHERE tournament_id = ? AND team_id = ?
    ")->execute([$tournamentId, $teamId]);
}

echo "Checked in {$totalCheckins} players across " . count($teams) . " teams in Tournament #$tournamentId.\n";
