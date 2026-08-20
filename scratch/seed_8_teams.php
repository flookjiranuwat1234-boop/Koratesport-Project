<?php
require_once 'config/db.php';

$teamsData = [
    [
        'team_name' => 'KORAT VIPERS',
        'category' => 'open',
        'members' => [
            ['username' => 'vipers_cap', 'email' => 'vipers_cap@korat.esport', 'name' => 'VIPER_Almighty', 'role' => 'Captain & Mid Lane', 'is_cap' => true],
            ['username' => 'vipers_dsl', 'email' => 'vipers_dsl@korat.esport', 'name' => 'VIPER_Kaiser', 'role' => 'Dark Slayer Lane', 'is_cap' => false],
            ['username' => 'vipers_jg',  'email' => 'vipers_jg@korat.esport',  'name' => 'VIPER_Shadow', 'role' => 'Jungle', 'is_cap' => false],
            ['username' => 'vipers_adl', 'email' => 'vipers_adl@korat.esport', 'name' => 'VIPER_Sniper', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false],
            ['username' => 'vipers_sup', 'email' => 'vipers_sup@korat.esport', 'name' => 'VIPER_Guardian', 'role' => 'Support / Roamer', 'is_cap' => false],
        ]
    ],
    [
        'team_name' => 'BACON TIME',
        'category' => 'open',
        'members' => [
            ['username' => 'bacon_cap', 'email' => 'bacon_cap@korat.esport', 'name' => 'BAC_MeMarkz', 'role' => 'Captain & Mid Lane', 'is_cap' => true],
            ['username' => 'bacon_dsl', 'email' => 'bacon_dsl@korat.esport', 'name' => 'BAC_Cherie', 'role' => 'Dark Slayer Lane', 'is_cap' => false],
            ['username' => 'bacon_jg',  'email' => 'bacon_jg@korat.esport',  'name' => 'BAC_007x', 'role' => 'Jungle', 'is_cap' => false],
            ['username' => 'bacon_adl', 'email' => 'bacon_adl@korat.esport', 'name' => 'BAC_JJak', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false],
            ['username' => 'bacon_sup', 'email' => 'bacon_sup@korat.esport', 'name' => 'BAC_Moss', 'role' => 'Support / Roamer', 'is_cap' => false],
        ]
    ],
    [
        'team_name' => 'BURIRAM UNITED',
        'category' => 'open',
        'members' => [
            ['username' => 'brutd_cap', 'email' => 'brutd_cap@korat.esport', 'name' => 'BRU_NuNu', 'role' => 'Captain & Mid Lane', 'is_cap' => true],
            ['username' => 'brutd_dsl', 'email' => 'brutd_dsl@korat.esport', 'name' => 'BRU_Overfly', 'role' => 'Dark Slayer Lane', 'is_cap' => false],
            ['username' => 'brutd_jg',  'email' => 'brutd_jg@korat.esport',  'name' => 'BRU_F1', 'role' => 'Jungle', 'is_cap' => false],
            ['username' => 'brutd_adl', 'email' => 'brutd_adl@korat.esport', 'name' => 'BRU_Difoxn', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false],
            ['username' => 'brutd_sup', 'email' => 'brutd_sup@korat.esport', 'name' => 'BRU_Isilindilz', 'role' => 'Support / Roamer', 'is_cap' => false],
        ]
    ],
    [
        'team_name' => 'TALON ESPORT',
        'category' => 'open',
        'members' => [
            ['username' => 'talon_cap', 'email' => 'talon_cap@korat.esport', 'name' => 'TLN_IpodPro', 'role' => 'Captain & Mid Lane', 'is_cap' => true],
            ['username' => 'talon_dsl', 'email' => 'talon_dsl@korat.esport', 'name' => 'TLN_NTnz', 'role' => 'Dark Slayer Lane', 'is_cap' => false],
            ['username' => 'talon_jg',  'email' => 'talon_jg@korat.esport',  'name' => 'TLN_PogPog', 'role' => 'Jungle', 'is_cap' => false],
            ['username' => 'talon_adl', 'email' => 'talon_adl@korat.esport', 'name' => 'TLN_Erez', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false],
            ['username' => 'talon_sup', 'email' => 'talon_sup@korat.esport', 'name' => 'TLN_Tony', 'role' => 'Support / Roamer', 'is_cap' => false],
        ]
    ],
    [
        'team_name' => 'HYDRA ESPORT',
        'category' => 'open',
        'members' => [
            ['username' => 'hydra_cap', 'email' => 'hydra_cap@korat.esport', 'name' => 'HDR_JinWoo', 'role' => 'Captain & Mid Lane', 'is_cap' => true],
            ['username' => 'hydra_dsl', 'email' => 'hydra_dsl@korat.esport', 'name' => 'HDR_Gunnar', 'role' => 'Dark Slayer Lane', 'is_cap' => false],
            ['username' => 'hydra_jg',  'email' => 'hydra_jg@korat.esport',  'name' => 'HDR_BaeBae', 'role' => 'Jungle', 'is_cap' => false],
            ['username' => 'hydra_adl', 'email' => 'hydra_adl@korat.esport', 'name' => 'HDR_Shark', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false],
            ['username' => 'hydra_sup', 'email' => 'hydra_sup@korat.esport', 'name' => 'HDR_Aom', 'role' => 'Support / Roamer', 'is_cap' => false],
        ]
    ],
    [
        'team_name' => 'EARENA ESPORT',
        'category' => 'open',
        'members' => [
            ['username' => 'earena_cap', 'email' => 'earena_cap@korat.esport', 'name' => 'EA_Gabriel', 'role' => 'Captain & Mid Lane', 'is_cap' => true],
            ['username' => 'earena_dsl', 'email' => 'earena_dsl@korat.esport', 'name' => 'EA_SNow', 'role' => 'Dark Slayer Lane', 'is_cap' => false],
            ['username' => 'earena_jg',  'email' => 'earena_jg@korat.esport',  'name' => 'EA_Blue', 'role' => 'Jungle', 'is_cap' => false],
            ['username' => 'earena_adl', 'email' => 'earena_adl@korat.esport', 'name' => 'EA_Moowan', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false],
            ['username' => 'earena_sup', 'email' => 'earena_sup@korat.esport', 'name' => 'EA_TaoX', 'role' => 'Support / Roamer', 'is_cap' => false],
        ]
    ],
    [
        'team_name' => 'KING OF GAMERS',
        'category' => 'open',
        'members' => [
            ['username' => 'kog_cap', 'email' => 'kog_cap@korat.esport', 'name' => 'KOG_Panda', 'role' => 'Captain & Mid Lane', 'is_cap' => true],
            ['username' => 'kog_dsl', 'email' => 'kog_dsl@korat.esport', 'name' => 'KOG_Titan', 'role' => 'Dark Slayer Lane', 'is_cap' => false],
            ['username' => 'kog_jg',  'email' => 'kog_jg@korat.esport',  'name' => 'KOG_Wolf', 'role' => 'Jungle', 'is_cap' => false],
            ['username' => 'kog_adl', 'email' => 'kog_adl@korat.esport', 'name' => 'KOG_Fox', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false],
            ['username' => 'kog_sup', 'email' => 'kog_sup@korat.esport', 'name' => 'KOG_Bear', 'role' => 'Support / Roamer', 'is_cap' => false],
        ]
    ],
    [
        'team_name' => 'VALOR CITY KORAT',
        'category' => 'open',
        'members' => [
            ['username' => 'valor_cap', 'email' => 'valor_cap@korat.esport', 'name' => 'VCK_NakhonPro', 'role' => 'Captain & Mid Lane', 'is_cap' => true],
            ['username' => 'valor_dsl', 'email' => 'valor_dsl@korat.esport', 'name' => 'VCK_Blade', 'role' => 'Dark Slayer Lane', 'is_cap' => false],
            ['username' => 'valor_jg',  'email' => 'valor_jg@korat.esport',  'name' => 'VCK_Hunter', 'role' => 'Jungle', 'is_cap' => false],
            ['username' => 'valor_adl', 'email' => 'valor_adl@korat.esport', 'name' => 'VCK_Marksman', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false],
            ['username' => 'valor_sup', 'email' => 'valor_sup@korat.esport', 'name' => 'VCK_Shield', 'role' => 'Support / Roamer', 'is_cap' => false],
        ]
    ],
];

$passHash = password_hash('123456', PASSWORD_DEFAULT);
$secHash = password_hash('korat', PASSWORD_DEFAULT);

$createdTeams = 0;
$createdPlayers = 0;

foreach ($teamsData as $tData) {
    // 1. ดึงหรือสร้างทีม
    $chkTeam = $pdo->prepare("SELECT team_id FROM teams WHERE name = ?");
    $chkTeam->execute([$tData['team_name']]);
    $teamId = $chkTeam->fetchColumn();

    $captainPlayerId = null;
    $memberPlayerIds = [];

    // 2. สร้าง User & Player สำหรับสมาชิก 5 คน
    foreach ($tData['members'] as $m) {
        // 2.1 สร้าง User
        $chkUser = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $chkUser->execute([$m['username'], $m['email']]);
        $userId = $chkUser->fetchColumn();

        if (!$userId) {
            $insU = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, status, security_question, security_answer_hash)
                VALUES (?, ?, ?, 'athlete', 'active', 'จังหวัดบ้านเกิด', ?)
            ");
            $insU->execute([$m['username'], $m['email'], $passHash, $secHash]);
            $userId = (int) $pdo->lastInsertId();
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ?, status = 'active' WHERE user_id = ?")
                ->execute([$passHash, $userId]);
        }

        // 2.2 สร้าง Player
        $chkPlayer = $pdo->prepare("SELECT player_id FROM players WHERE user_id = ?");
        $chkPlayer->execute([$userId]);
        $playerId = $chkPlayer->fetchColumn();

        $bioText = "นักกีฬาอีสปอร์ตสังกัดทีม {$tData['team_name']} ตำแหน่ง {$m['role']}";

        if (!$playerId) {
            $insP = $pdo->prepare("
                INSERT INTO players (user_id, display_name, bio, category)
                VALUES (?, ?, ?, ?)
            ");
            $insP->execute([$userId, $m['name'], $bioText, $tData['category']]);
            $playerId = (int) $pdo->lastInsertId();
        } else {
            $pdo->prepare("UPDATE players SET display_name = ?, bio = ?, category = ? WHERE player_id = ?")
                ->execute([$m['name'], $bioText, $tData['category'], $playerId]);
        }

        if ($m['is_cap']) {
            $captainPlayerId = $playerId;
        }

        $memberPlayerIds[] = [
            'player_id' => $playerId,
            'role' => $m['role'],
            'is_cap' => $m['is_cap']
        ];
        $createdPlayers++;
    }

    // 2.3 สร้างทีมหรืออัปเดตทีม
    if (!$teamId) {
        $insT = $pdo->prepare("
            INSERT INTO teams (name, game_id, captain_player_id, category)
            VALUES (?, 1, ?, ?)
        ");
        $insT->execute([$tData['team_name'], $captainPlayerId, $tData['category']]);
        $teamId = (int) $pdo->lastInsertId();
        $createdTeams++;
    } else {
        $pdo->prepare("UPDATE teams SET captain_player_id = ? WHERE team_id = ?")
            ->execute([$captainPlayerId, $teamId]);
    }

    // 2.4 ผูกสมาชิกเข้าตาราง team_members
    foreach ($memberPlayerIds as $mb) {
        $chkTm = $pdo->prepare("SELECT team_member_id FROM team_members WHERE team_id = ? AND player_id = ?");
        $chkTm->execute([$teamId, $mb['player_id']]);
        $tmId = $chkTm->fetchColumn();

        if (!$tmId) {
            $pdo->prepare("
                INSERT INTO team_members (team_id, player_id, in_game_role, is_active, is_substitute)
                VALUES (?, ?, ?, 1, 0)
            ")->execute([$teamId, $mb['player_id'], $mb['role']]);
        } else {
            $pdo->prepare("
                UPDATE team_members 
                SET in_game_role = ?, is_active = 1, is_substitute = 0 
                WHERE team_member_id = ?
            ")->execute([$mb['role'], $tmId]);
        }
    }
}

echo "Successfully seeded 8 Teams and 40 Players into MySQL database!\n";
