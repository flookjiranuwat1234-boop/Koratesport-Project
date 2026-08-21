<?php
require_once __DIR__ . '/../config/db.php';

$tId = 23;
$sql = "SELECT tr.team_id, t.name,
        (SELECT COUNT(DISTINCT tpc.player_id) 
         FROM tournament_player_checkins tpc 
         JOIN team_members tm ON tm.player_id = tpc.player_id AND tm.team_id = tr.team_id AND tm.is_active = 1
         WHERE tpc.tournament_id = tr.tournament_id) AS checked_in_members_count,
        (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = tr.team_id AND tm.is_active = 1) AS total_members_count
 FROM tournament_registrations tr
 JOIN teams t ON t.team_id = tr.team_id
 WHERE tr.tournament_id = $tId";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "Check-in data for Tournament #$tId:\n";
foreach ($rows as $r) {
    echo " - Team [{$r['team_id']}] {$r['name']}: {$r['checked_in_members_count']} / {$r['total_members_count']} คน\n";
}
