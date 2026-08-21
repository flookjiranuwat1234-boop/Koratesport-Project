<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/bracket.php';

$tournamentId = 23;

// กำหนด 4 ทีมชาย และ 4 ทีมหญิง
$maleTeams = ['FULL SENSE', 'PSG ESPORTS', 'NEXUS GAMING', 'DRAGON X ESPORT'];
$femaleTeams = ['BLACK PEARL ESPORT', 'PHOENIX FORCE', 'SHADOW WOLVES', 'CYBER KNIGHTS'];

// 1. อัปเดต category ใน teams และ tournament_registrations
foreach ($maleTeams as $name) {
    $pdo->prepare("UPDATE teams SET category = 'male', team_category = 'male' WHERE name = ?")->execute([$name]);
    $pdo->prepare("
        UPDATE tournament_registrations tr
        JOIN teams t ON t.team_id = tr.team_id
        SET tr.category = 'male'
        WHERE tr.tournament_id = ? AND t.name = ?
    ")->execute([$tournamentId, $name]);
}

foreach ($femaleTeams as $name) {
    $pdo->prepare("UPDATE teams SET category = 'female', team_category = 'female' WHERE name = ?")->execute([$name]);
    $pdo->prepare("
        UPDATE tournament_registrations tr
        JOIN teams t ON t.team_id = tr.team_id
        SET tr.category = 'female'
        WHERE tr.tournament_id = ? AND t.name = ?
    ")->execute([$tournamentId, $name]);
}

// 2. ล้างแมตช์เดิมของ Tournament 23 ออก
$pdo->prepare("DELETE FROM bracket_edges WHERE match_id IN (SELECT match_id FROM matches WHERE tournament_id = ?)")->execute([$tournamentId]);
$pdo->prepare("DELETE FROM matches WHERE tournament_id = ?")->execute([$tournamentId]);

// 3. สร้างสายการแข่งขันใหม่ (Single Elimination แบบแยกชาย/หญิง)
$maxRounds = generateSingleEliminationBracket($pdo, $tournamentId);

echo "Bracket regenerated successfully! Max rounds: $maxRounds\n";

// ตรวจสอบแมตช์ที่ถูกสร้าง
$matches = $pdo->query("
    SELECT m.match_id, m.bracket_type, m.category, m.round_number, m.match_index,
           t1.name as team1_name, t2.name as team2_name
    FROM matches m
    LEFT JOIN teams t1 ON m.team1_id = t1.team_id
    LEFT JOIN teams t2 ON m.team2_id = t2.team_id
    WHERE m.tournament_id = $tournamentId
    ORDER BY m.bracket_type, m.round_number, m.match_index
")->fetchAll(PDO::FETCH_ASSOC);

echo "\nCreated Matches in Tournament #$tournamentId:\n";
foreach ($matches as $m) {
    printf(" [%-15s] Round %d Match %d | %-20s VS %s\n", 
        $m['bracket_type'], $m['round_number'], $m['match_index'], 
        $m['team1_name'] ?: 'TBD', $m['team2_name'] ?: 'TBD');
}
