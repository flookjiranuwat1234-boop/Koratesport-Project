<?php
require_once __DIR__ . '/../config/db.php';

$teamNames = [
    'KORAT VIPERS', 'BACON TIME', 'BURIRAM UNITED', 'TALON ESPORT',
    'HYDRA ESPORT', 'EARENA ESPORT', 'KING OF GAMERS', 'VALOR CITY KORAT',
    'FULL SENSE', 'PSG ESPORTS', 'BLACK PEARL ESPORT', 'PHOENIX FORCE',
    'NEXUS GAMING', 'DRAGON X ESPORT', 'SHADOW WOLVES', 'CYBER KNIGHTS'
];

$inClause = "'" . implode("','", $teamNames) . "'";
$teams = $pdo->query("
    SELECT team_id, name, captain_player_id 
    FROM teams 
    WHERE name IN ($inClause)
    ORDER BY team_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

echo "Total Teams found: " . count($teams) . "\n\n";

foreach ($teams as $idx => $t) {
    echo "=================================================================\n";
    echo ($idx + 1) . ". TEAM: {$t['name']} (ID: {$t['team_id']})\n";
    echo "=================================================================\n";
    
    $stmt = $pdo->prepare("
        SELECT tm.team_member_id, tm.in_game_role, tm.is_substitute, p.player_id, p.display_name, u.username, u.email
        FROM team_members tm
        JOIN players p ON tm.player_id = p.player_id
        JOIN users u ON p.user_id = u.user_id
        WHERE tm.team_id = ?
        ORDER BY tm.is_substitute ASC, tm.team_member_id ASC
    ");
    $stmt->execute([$t['team_id']]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($members as $m) {
        $isCap = ($m['player_id'] == $t['captain_player_id']);
        $tag = $isCap ? '[กัปตัน (ตัวจริง)]' : ($m['is_substitute'] ? '[ตัวสำรอง]' : '[ตัวจริง]');
        printf("  %-18s | %-12s | %-18s | %s\n", $m['display_name'], $m['username'], $tag, $m['in_game_role']);
    }
    echo "\n";
}

require_once __DIR__ . '/../includes/auth.php';
$testUser = loginUser($pdo, 'fs_cap', '123456');
echo "Login verification test (New Team Captain): User '{$testUser['username']}' logged in successfully with password '123456'!\n";
