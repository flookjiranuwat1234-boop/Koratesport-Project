<?php
require_once __DIR__ . '/../config/db.php';

$tournamentId = 23; // korat esport ep1

$newTeamNames = [
    'FULL SENSE', 'PSG ESPORTS', 'BLACK PEARL ESPORT', 'PHOENIX FORCE',
    'NEXUS GAMING', 'DRAGON X ESPORT', 'SHADOW WOLVES', 'CYBER KNIGHTS'
];

$inClause = "'" . implode("','", $newTeamNames) . "'";
$newTeams = $pdo->query("SELECT team_id, name, captain_player_id, category FROM teams WHERE name IN ($inClause)")->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($newTeams) . " teams to register into Tournament #$tournamentId (korat esport ep1)...\n";

foreach ($newTeams as $t) {
    $teamId = $t['team_id'];
    $category = $t['category'] ?: 'open';

    // 1. เพิ่มใน tournament_registrations
    $chk = $pdo->prepare("SELECT tournament_registration_id FROM tournament_registrations WHERE tournament_id = ? AND team_id = ?");
    $chk->execute([$tournamentId, $teamId]);
    $regId = $chk->fetchColumn();

    if (!$regId) {
        $insReg = $pdo->prepare("
            INSERT INTO tournament_registrations (tournament_id, team_id, category, status, checkin_status, registered_at)
            VALUES (?, ?, ?, 'approved', 'checked_in', NOW())
        ");
        $insReg->execute([$tournamentId, $teamId, $category]);
        $regId = $pdo->lastInsertId();
        echo "Registered team {$t['name']} (ID: $teamId) -> Registration ID: $regId [status: approved, checked_in]\n";
    } else {
        $pdo->prepare("UPDATE tournament_registrations SET status = 'approved', checkin_status = 'checked_in', registered_at = NOW() WHERE tournament_registration_id = ?")
            ->execute([$regId]);
        echo "Updated team {$t['name']} (ID: $teamId) -> Registration ID: $regId\n";
    }

    // 2. Snapshot สมาชิก 6 คนของทีมเข้า tournament_rosters สำหรับ Tournament 23
    $members = $pdo->prepare("
        SELECT tm.player_id, tm.in_game_role, tm.is_substitute,
               (CASE WHEN t.captain_player_id = tm.player_id THEN 1 ELSE 0 END) AS is_captain
        FROM team_members tm
        JOIN teams t ON t.team_id = tm.team_id
        WHERE tm.team_id = ? AND tm.is_active = 1
    ");
    $members->execute([$teamId]);
    $rosterList = $members->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rosterList as $m) {
        $chkRoster = $pdo->prepare("SELECT roster_id FROM tournament_rosters WHERE tournament_id = ? AND team_id = ? AND player_id = ?");
        $chkRoster->execute([$tournamentId, $teamId, $m['player_id']]);
        $rosterId = $chkRoster->fetchColumn();

        if (!$rosterId) {
            $insRoster = $pdo->prepare("
                INSERT INTO tournament_rosters (tournament_id, team_id, player_id, in_game_role, is_captain, is_substitute, registered_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $insRoster->execute([$tournamentId, $teamId, $m['player_id'], $m['in_game_role'], $m['is_captain'], $m['is_substitute']]);
        } else {
            $pdo->prepare("
                UPDATE tournament_rosters
                SET in_game_role = ?, is_captain = ?, is_substitute = ?
                WHERE roster_id = ?
            ")->execute([$m['in_game_role'], $m['is_captain'], $m['is_substitute'], $rosterId]);
        }
    }
}

echo "=== SUCCESSFULLY REGISTERED ALL 8 TEAMS TO TOURNAMENT 23 (korat esport ep1) ===\n";
