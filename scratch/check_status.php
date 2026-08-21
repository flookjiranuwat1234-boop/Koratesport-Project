<?php
require_once __DIR__ . '/../config/db.php';

$tIds = $pdo->query("SELECT team_id, name FROM teams WHERE name IN (
    'KORAT VIPERS', 'BACON TIME', 'BURIRAM UNITED', 'TALON ESPORT',
    'HYDRA ESPORT', 'EARENA ESPORT', 'KING OF GAMERS', 'VALOR CITY KORAT',
    'FULL SENSE', 'PSG ESPORTS', 'BLACK PEARL ESPORT', 'PHOENIX FORCE',
    'NEXUS GAMING', 'DRAGON X ESPORT', 'SHADOW WOLVES', 'CYBER KNIGHTS'
)")->fetchAll(PDO::FETCH_KEY_PAIR);

echo "Teams found:\n";
print_r($tIds);

$teamIdList = implode(',', array_keys($tIds));

$matches = $pdo->query("SELECT match_id, tournament_id, team1_id, team2_id, status, winner_team_id FROM matches WHERE team1_id IN ($teamIdList) OR team2_id IN ($teamIdList)")->fetchAll(PDO::FETCH_ASSOC);
echo "Matches count: " . count($matches) . "\n";
print_r($matches);

$regs = $pdo->query("SELECT * FROM tournament_registrations WHERE team_id IN ($teamIdList)")->fetchAll(PDO::FETCH_ASSOC);
echo "Registrations count: " . count($regs) . "\n";
print_r($regs);

$rosters = $pdo->query("SELECT * FROM tournament_rosters WHERE team_id IN ($teamIdList)")->fetchAll(PDO::FETCH_ASSOC);
echo "Tournament rosters count: " . count($rosters) . "\n";
print_r($rosters);
