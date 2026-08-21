<?php
require_once __DIR__ . '/../config/db.php';
$matches = $pdo->query('SELECT match_id, tournament_id, team1_id, team2_id, status, winner_team_id, score_team1, score_team2 FROM matches WHERE team1_id >= 103 OR team2_id >= 103')->fetchAll(PDO::FETCH_ASSOC);
echo "Matches count: " . count($matches) . "\n";
print_r($matches);
