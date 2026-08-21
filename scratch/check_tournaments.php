<?php
require_once __DIR__ . '/../config/db.php';

$tournaments = $pdo->query("SELECT tournament_id, name, game_id, max_teams, status, gender_category FROM tournaments")->fetchAll(PDO::FETCH_ASSOC);
echo "Tournaments:\n";
print_r($tournaments);

$regs = $pdo->query("
    SELECT tr.tournament_registration_id, tr.tournament_id, t.name as tour_name, tr.team_id, tm.name as team_name, tr.status, tr.checkin_status
    FROM tournament_registrations tr
    JOIN tournaments t ON t.tournament_id = tr.tournament_id
    JOIN teams tm ON tm.team_id = tr.team_id
    WHERE tr.team_id >= 103
    ORDER BY tr.tournament_id, tr.team_id
")->fetchAll(PDO::FETCH_ASSOC);
echo "Registrations for teams >= 103:\n";
print_r($regs);
