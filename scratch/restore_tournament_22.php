<?php
require_once __DIR__ . '/../config/db.php';

$tournamentId = 22;

// 1. ปรับ max_teams ของ Tournament 22 กลับเป็น 8 ทีมเหมือนเดิม
$pdo->prepare("UPDATE tournaments SET max_teams = 8 WHERE tournament_id = ?")->execute([$tournamentId]);

// 2. รายชื่อ 8 ทีมใหม่ (ID 111 - 118)
$newTeamNames = [
    'FULL SENSE', 'PSG ESPORTS', 'BLACK PEARL ESPORT', 'PHOENIX FORCE',
    'NEXUS GAMING', 'DRAGON X ESPORT', 'SHADOW WOLVES', 'CYBER KNIGHTS'
];

$inClause = "'" . implode("','", $newTeamNames) . "'";
$newTeamIds = $pdo->query("SELECT team_id FROM teams WHERE name IN ($inClause)")->fetchAll(PDO::FETCH_COLUMN);

if (!empty($newTeamIds)) {
    $idsStr = implode(',', $newTeamIds);
    
    // ลบออกจาก tournament_registrations สำหรับ Tournament 22
    $delReg = $pdo->prepare("DELETE FROM tournament_registrations WHERE tournament_id = ? AND team_id IN ($idsStr)");
    $delReg->execute([$tournamentId]);
    echo "Deleted " . $delReg->rowCount() . " registration records for new teams from Tournament 22.\n";

    // ลบออกจาก tournament_rosters สำหรับ Tournament 22
    $delRoster = $pdo->prepare("DELETE FROM tournament_rosters WHERE tournament_id = ? AND team_id IN ($idsStr)");
    $delRoster->execute([$tournamentId]);
    echo "Deleted " . $delRoster->rowCount() . " roster records for new teams from Tournament 22.\n";
}

echo "=== TOURNAMENT 22 RESTORED TO 8 TEAMS ONLY (max_teams = 8) ===\n";
