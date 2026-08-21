<?php
require_once __DIR__ . '/../config/db.php';

// Check Tournament 22 and 23
foreach ([22, 23] as $tId) {
    // Checkin members in tournament_player_checkins if missing
    $teams = $pdo->query("SELECT tr.team_id, t.name FROM tournament_registrations tr JOIN teams t ON t.team_id = tr.team_id WHERE tr.tournament_id = $tId")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($teams as $t) {
        $mStmt = $pdo->prepare("SELECT player_id FROM team_members WHERE team_id = ? AND is_active = 1");
        $mStmt->execute([$t['team_id']]);
        $members = $mStmt->fetchAll(PDO::FETCH_COLUMN);

        $ins = $pdo->prepare("INSERT IGNORE INTO tournament_player_checkins (tournament_id, team_id, player_id, checked_in_at) VALUES (?, ?, ?, NOW())");
        foreach ($members as $pId) {
            $ins->execute([$tId, $t['team_id'], $pId]);
        }
    }

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

    echo "=== Check-in data for Tournament #$tId ===\n";
    foreach ($rows as $r) {
        echo " - Team [{$r['team_id']}] {$r['name']}: {$r['checked_in_members_count']} / {$r['total_members_count']} คน\n";
    }
    echo "\n";
}
