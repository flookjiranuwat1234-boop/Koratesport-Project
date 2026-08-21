<?php
require_once __DIR__ . '/../config/db.php';

$teamsData = [
    // --- ชุดที่ 1 (ทีม 1 - 8) ---
    [
        'team_name' => 'KORAT VIPERS',
        'category' => 'open',
        'members' => [
            ['username' => 'vipers_cap', 'email' => 'vipers_cap@korat.esport', 'name' => 'VIPER_Almighty', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'vipers_dsl', 'email' => 'vipers_dsl@korat.esport', 'name' => 'VIPER_Kaiser', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'vipers_jg',  'email' => 'vipers_jg@korat.esport',  'name' => 'VIPER_Shadow', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'vipers_adl', 'email' => 'vipers_adl@korat.esport', 'name' => 'VIPER_Sniper', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'vipers_sup', 'email' => 'vipers_sup@korat.esport', 'name' => 'VIPER_Guardian', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'vipers_sub', 'email' => 'vipers_sub@korat.esport', 'name' => 'VIPER_Reserve', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'BACON TIME',
        'category' => 'open',
        'members' => [
            ['username' => 'bacon_cap', 'email' => 'bacon_cap@korat.esport', 'name' => 'BAC_MeMarkz', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'bacon_dsl', 'email' => 'bacon_dsl@korat.esport', 'name' => 'BAC_Cherie', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'bacon_jg',  'email' => 'bacon_jg@korat.esport',  'name' => 'BAC_007x', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'bacon_adl', 'email' => 'bacon_adl@korat.esport', 'name' => 'BAC_JJak', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'bacon_sup', 'email' => 'bacon_sup@korat.esport', 'name' => 'BAC_Moss', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'bacon_sub', 'email' => 'bacon_sub@korat.esport', 'name' => 'BAC_ReMix', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'BURIRAM UNITED',
        'category' => 'open',
        'members' => [
            ['username' => 'brutd_cap', 'email' => 'brutd_cap@korat.esport', 'name' => 'BRU_NuNu', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'brutd_dsl', 'email' => 'brutd_dsl@korat.esport', 'name' => 'BRU_Overfly', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'brutd_jg',  'email' => 'brutd_jg@korat.esport',  'name' => 'BRU_F1', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'brutd_adl', 'email' => 'brutd_adl@korat.esport', 'name' => 'BRU_Difoxn', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'brutd_sup', 'email' => 'brutd_sup@korat.esport', 'name' => 'BRU_Isilindilz', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'brutd_sub', 'email' => 'brutd_sub@korat.esport', 'name' => 'BRU_Summer', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'TALON ESPORT',
        'category' => 'open',
        'members' => [
            ['username' => 'talon_cap', 'email' => 'talon_cap@korat.esport', 'name' => 'TLN_IpodPro', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'talon_dsl', 'email' => 'talon_dsl@korat.esport', 'name' => 'TLN_NTnz', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'talon_jg',  'email' => 'talon_jg@korat.esport',  'name' => 'TLN_PogPog', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'talon_adl', 'email' => 'talon_adl@korat.esport', 'name' => 'TLN_Erez', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'talon_sup', 'email' => 'talon_sup@korat.esport', 'name' => 'TLN_Tony', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'talon_sub', 'email' => 'talon_sub@korat.esport', 'name' => 'TLN_Deboom', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'HYDRA ESPORT',
        'category' => 'open',
        'members' => [
            ['username' => 'hydra_cap', 'email' => 'hydra_cap@korat.esport', 'name' => 'HDR_JinWoo', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'hydra_dsl', 'email' => 'hydra_dsl@korat.esport', 'name' => 'HDR_Gunnar', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'hydra_jg',  'email' => 'hydra_jg@korat.esport',  'name' => 'HDR_BaeBae', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'hydra_adl', 'email' => 'hydra_adl@korat.esport', 'name' => 'HDR_Shark', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'hydra_sup', 'email' => 'hydra_sup@korat.esport', 'name' => 'HDR_Aom', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'hydra_sub', 'email' => 'hydra_sub@korat.esport', 'name' => 'HDR_Zack', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'EARENA ESPORT',
        'category' => 'open',
        'members' => [
            ['username' => 'earena_cap', 'email' => 'earena_cap@korat.esport', 'name' => 'EA_Gabriel', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'earena_dsl', 'email' => 'earena_dsl@korat.esport', 'name' => 'EA_SNow', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'earena_jg',  'email' => 'earena_jg@korat.esport',  'name' => 'EA_Blue', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'earena_adl', 'email' => 'earena_adl@korat.esport', 'name' => 'EA_Moowan', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'earena_sup', 'email' => 'earena_sup@korat.esport', 'name' => 'EA_TaoX', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'earena_sub', 'email' => 'earena_sub@korat.esport', 'name' => 'EA_Ligky', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'KING OF GAMERS',
        'category' => 'open',
        'members' => [
            ['username' => 'kog_cap', 'email' => 'kog_cap@korat.esport', 'name' => 'KOG_Panda', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'kog_dsl', 'email' => 'kog_dsl@korat.esport', 'name' => 'KOG_Titan', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'kog_jg',  'email' => 'kog_jg@korat.esport',  'name' => 'KOG_Wolf', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'kog_adl', 'email' => 'kog_adl@korat.esport', 'name' => 'KOG_Fox', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'kog_sup', 'email' => 'kog_sup@korat.esport', 'name' => 'KOG_Bear', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'kog_sub', 'email' => 'kog_sub@korat.esport', 'name' => 'KOG_Viper', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'VALOR CITY KORAT',
        'category' => 'open',
        'members' => [
            ['username' => 'valor_cap', 'email' => 'valor_cap@korat.esport', 'name' => 'VCK_NakhonPro', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'valor_dsl', 'email' => 'valor_dsl@korat.esport', 'name' => 'VCK_Blade', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'valor_jg',  'email' => 'valor_jg@korat.esport',  'name' => 'VCK_Hunter', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'valor_adl', 'email' => 'valor_adl@korat.esport', 'name' => 'VCK_Marksman', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'valor_sup', 'email' => 'valor_sup@korat.esport', 'name' => 'VCK_Shield', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'valor_sub', 'email' => 'valor_sub@korat.esport', 'name' => 'VCK_Ghost', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],

    // --- ชุดที่ 2 เพิ่มใหม่อีก 8 ทีม (ทีม 9 - 16) ---
    [
        'team_name' => 'FULL SENSE',
        'category' => 'open',
        'members' => [
            ['username' => 'fs_cap', 'email' => 'fs_cap@korat.esport', 'name' => 'FS_Sharkz', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'fs_dsl', 'email' => 'fs_dsl@korat.esport', 'name' => 'FS_Leviathan', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'fs_jg',  'email' => 'fs_jg@korat.esport',  'name' => 'FS_Miracle', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'fs_adl', 'email' => 'fs_adl@korat.esport', 'name' => 'FS_Blaze', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'fs_sup', 'email' => 'fs_sup@korat.esport', 'name' => 'FS_Aegis', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'fs_sub', 'email' => 'fs_sub@korat.esport', 'name' => 'FS_Nova', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'PSG ESPORTS',
        'category' => 'open',
        'members' => [
            ['username' => 'psg_cap', 'email' => 'psg_cap@korat.esport', 'name' => 'PSG_FirstOne', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'psg_dsl', 'email' => 'psg_dsl@korat.esport', 'name' => 'PSG_Gunnz', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'psg_jg',  'email' => 'psg_jg@korat.esport',  'name' => 'PSG_Getz', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'psg_adl', 'email' => 'psg_adl@korat.esport', 'name' => 'PSG_Hrl', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'psg_sup', 'email' => 'psg_sup@korat.esport', 'name' => 'PSG_Isil', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'psg_sub', 'email' => 'psg_sub@korat.esport', 'name' => 'PSG_Mist', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'BLACK PEARL ESPORT',
        'category' => 'open',
        'members' => [
            ['username' => 'bp_cap', 'email' => 'bp_cap@korat.esport', 'name' => 'BP_Poseidon', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'bp_dsl', 'email' => 'bp_dsl@korat.esport', 'name' => 'BP_Kraken', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'bp_jg',  'email' => 'bp_jg@korat.esport',  'name' => 'BP_Phantom', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'bp_adl', 'email' => 'bp_adl@korat.esport', 'name' => 'BP_Treasure', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'bp_sup', 'email' => 'bp_sup@korat.esport', 'name' => 'BP_Anchor', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'bp_sub', 'email' => 'bp_sub@korat.esport', 'name' => 'BP_Siren', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'PHOENIX FORCE',
        'category' => 'open',
        'members' => [
            ['username' => 'px_cap', 'email' => 'px_cap@korat.esport', 'name' => 'PX_Solaris', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'px_dsl', 'email' => 'px_dsl@korat.esport', 'name' => 'PX_Ignis', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'px_jg',  'email' => 'px_jg@korat.esport',  'name' => 'PX_Flare', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'px_adl', 'email' => 'px_adl@korat.esport', 'name' => 'PX_Blaze', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'px_sup', 'email' => 'px_sup@korat.esport', 'name' => 'PX_Ashes', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'px_sub', 'email' => 'px_sub@korat.esport', 'name' => 'PX_Ember', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'NEXUS GAMING',
        'category' => 'open',
        'members' => [
            ['username' => 'nx_cap', 'email' => 'nx_cap@korat.esport', 'name' => 'NX_Vortex', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'nx_dsl', 'email' => 'nx_dsl@korat.esport', 'name' => 'NX_Titan', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'nx_jg',  'email' => 'nx_jg@korat.esport',  'name' => 'NX_Cyber', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'nx_adl', 'email' => 'nx_adl@korat.esport', 'name' => 'NX_Pulse', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'nx_sup', 'email' => 'nx_sup@korat.esport', 'name' => 'NX_Matrix', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'nx_sub', 'email' => 'nx_sub@korat.esport', 'name' => 'NX_Glitch', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'DRAGON X ESPORT',
        'category' => 'open',
        'members' => [
            ['username' => 'drx_cap', 'email' => 'drx_cap@korat.esport', 'name' => 'DRX_Bahamut', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'drx_dsl', 'email' => 'drx_dsl@korat.esport', 'name' => 'DRX_Scale', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'drx_jg',  'email' => 'drx_jg@korat.esport',  'name' => 'DRX_Fang', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'drx_adl', 'email' => 'drx_adl@korat.esport', 'name' => 'DRX_Inferno', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'drx_sup', 'email' => 'drx_sup@korat.esport', 'name' => 'DRX_Roar', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'drx_sub', 'email' => 'drx_sub@korat.esport', 'name' => 'DRX_Wyvern', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'SHADOW WOLVES',
        'category' => 'open',
        'members' => [
            ['username' => 'sw_cap', 'email' => 'sw_cap@korat.esport', 'name' => 'SW_Alpha', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'sw_dsl', 'email' => 'sw_dsl@korat.esport', 'name' => 'SW_Grim', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'sw_jg',  'email' => 'sw_jg@korat.esport',  'name' => 'SW_Stalker', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'sw_adl', 'email' => 'sw_adl@korat.esport', 'name' => 'SW_Howler', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'sw_sup', 'email' => 'sw_sup@korat.esport', 'name' => 'SW_Pack', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'sw_sub', 'email' => 'sw_sub@korat.esport', 'name' => 'SW_Ghost', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
    [
        'team_name' => 'CYBER KNIGHTS',
        'category' => 'open',
        'members' => [
            ['username' => 'ck_cap', 'email' => 'ck_cap@korat.esport', 'name' => 'CK_Arthur', 'role' => 'Captain & Mid Lane', 'is_cap' => true, 'is_sub' => 0],
            ['username' => 'ck_dsl', 'email' => 'ck_dsl@korat.esport', 'name' => 'CK_Lancelot', 'role' => 'Dark Slayer Lane', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'ck_jg',  'email' => 'ck_jg@korat.esport',  'name' => 'CK_Galahad', 'role' => 'Jungle', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'ck_adl', 'email' => 'ck_adl@korat.esport', 'name' => 'CK_Percival', 'role' => 'Abyssal Dragon Lane (Carry)', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'ck_sup', 'email' => 'ck_sup@korat.esport', 'name' => 'CK_Gawain', 'role' => 'Support / Roamer', 'is_cap' => false, 'is_sub' => 0],
            ['username' => 'ck_sub', 'email' => 'ck_sub@korat.esport', 'name' => 'CK_Merlin', 'role' => 'Substitute / ตัวสำรอง', 'is_cap' => false, 'is_sub' => 1],
        ]
    ],
];

$defaultPassword = '123456';
$passHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
$secHash = password_hash('korat', PASSWORD_DEFAULT);

$createdTeams = 0;
$createdPlayers = 0;

foreach ($teamsData as $tData) {
    // 1. ตรวจสอบหรือสร้างทีม
    $chkTeam = $pdo->prepare("SELECT team_id FROM teams WHERE name = ?");
    $chkTeam->execute([$tData['team_name']]);
    $teamId = $chkTeam->fetchColumn();

    $captainPlayerId = null;
    $memberPlayerIds = [];

    // 2. สร้าง User & Player สำหรับสมาชิกทั้ง 6 คน
    foreach ($tData['members'] as $m) {
        // 2.1 สร้าง/อัปเดต User
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

        // 2.2 สร้าง/อัปเดต Player
        $chkPlayer = $pdo->prepare("SELECT player_id FROM players WHERE user_id = ?");
        $chkPlayer->execute([$userId]);
        $playerId = $chkPlayer->fetchColumn();

        $subLabel = $m['is_sub'] ? ' [ตัวสำรอง]' : ($m['is_cap'] ? ' [กัปตันทีม]' : ' [ตัวจริง]');
        $bioText = "นักกีฬาอีสปอร์ตสังกัดทีม {$tData['team_name']} ตำแหน่ง {$m['role']}{$subLabel}";

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
            'is_cap' => $m['is_cap'],
            'is_sub' => $m['is_sub']
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
        $pdo->prepare("UPDATE teams SET captain_player_id = ?, game_id = 1, category = ? WHERE team_id = ?")
            ->execute([$captainPlayerId, $tData['category'], $teamId]);
    }

    // 2.4 ผูกสมาชิกเข้าตาราง team_members
    foreach ($memberPlayerIds as $mb) {
        $chkTm = $pdo->prepare("SELECT team_member_id FROM team_members WHERE team_id = ? AND player_id = ?");
        $chkTm->execute([$teamId, $mb['player_id']]);
        $tmId = $chkTm->fetchColumn();

        if (!$tmId) {
            $pdo->prepare("
                INSERT INTO team_members (team_id, player_id, in_game_role, is_active, is_substitute)
                VALUES (?, ?, ?, 1, ?)
            ")->execute([$teamId, $mb['player_id'], $mb['role'], $mb['is_sub']]);
        } else {
            $pdo->prepare("
                UPDATE team_members 
                SET in_game_role = ?, is_active = 1, is_substitute = ? 
                WHERE team_member_id = ?
            ")->execute([$mb['role'], $mb['is_sub'], $tmId]);
        }
    }
}

echo "=== SEEDING 16 TEAMS COMPLETED ===\n";
echo "Total Teams Processed: " . count($teamsData) . "\n";
echo "Total Players/Users: " . (count($teamsData) * 6) . "\n";
echo "Default Password for all accounts: {$defaultPassword}\n";
