<?php
// config/db.php
// ไฟล์เชื่อมต่อฐานข้อมูล ให้ทุกไฟล์ include ตัวนี้แค่ตัวเดียว

$host = 'localhost';
$dbname = 'korat_esport';
$dbuser = 'root';
$dbpass = ''; // แก้เป็นรหัสจริงตอนขึ้น production

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $dbuser,
        $dbpass
    );
    // โหมด error ให้ throw exception ออกมาเลย จะได้เห็น error ชัดๆ ตอน debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // ตรวจสอบและอัปเดตคอลัมน์ที่จำเป็นอัตโนมัติ (Auto Schema Self-Healing)
    ensureCoreSchema($pdo);
} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลไม่ได้: " . $e->getMessage());
}

/**
 * ฟังก์ชันช่วยเพิ่มคอลัมน์แบบ Failsafe
 */
function addColumnIfNotExists($pdo, $table, $column, $definition) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array($column, $cols)) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    } catch (Exception $e) {
        // ข้ามหากมีข้อผิดพลาดเฉพาะเจาะจง
    }
}

/**
 * ฟังก์ชันตรวจสอบและเพิ่มคอลัมน์/ตารางที่จำเป็นในระบบโดยอัตโนมัติ
 */
function ensureCoreSchema($pdo) {
    // 1. ตาราง tournament_registrations
    try {
        $pdo->exec("ALTER TABLE tournament_registrations MODIFY COLUMN team_id INT(10) UNSIGNED NULL");
    } catch (Exception $e) {}
    addColumnIfNotExists($pdo, 'tournament_registrations', 'player_id', 'INT NULL');
    addColumnIfNotExists($pdo, 'tournament_registrations', 'category', "VARCHAR(20) NOT NULL DEFAULT 'open'");
    addColumnIfNotExists($pdo, 'tournament_registrations', 'qr_code_token', 'VARCHAR(100) NULL');
    addColumnIfNotExists($pdo, 'tournament_registrations', 'checkin_status', "VARCHAR(20) NOT NULL DEFAULT 'not_checked_in'");
    addColumnIfNotExists($pdo, 'tournament_registrations', 'checkin_at', 'DATETIME NULL');

    // 2. ตาราง tournaments
    addColumnIfNotExists($pdo, 'tournaments', 'prize_pool', 'VARCHAR(255) NULL');
    addColumnIfNotExists($pdo, 'tournaments', 'rules', 'TEXT NULL');
    addColumnIfNotExists($pdo, 'tournaments', 'description', 'TEXT NULL');
    addColumnIfNotExists($pdo, 'tournaments', 'venue_address', 'VARCHAR(255) NULL');
    addColumnIfNotExists($pdo, 'tournaments', 'image_path', 'VARCHAR(255) NULL');
    addColumnIfNotExists($pdo, 'tournaments', 'best_of', 'TINYINT NOT NULL DEFAULT 5');
    addColumnIfNotExists($pdo, 'tournaments', 'registration_start', 'DATETIME NULL');
    addColumnIfNotExists($pdo, 'tournaments', 'registration_end', 'DATETIME NULL');
    addColumnIfNotExists($pdo, 'tournaments', 'start_date', 'DATETIME NULL');
    addColumnIfNotExists($pdo, 'tournaments', 'end_date', 'DATETIME NULL');
    addColumnIfNotExists($pdo, 'tournaments', 'gender_category', "VARCHAR(20) NOT NULL DEFAULT 'open'");
    addColumnIfNotExists($pdo, 'tournaments', 'group_count', 'INT NULL');

    // 3. ตาราง games
    addColumnIfNotExists($pdo, 'games', 'play_mode', "VARCHAR(20) NOT NULL DEFAULT 'team'");
    addColumnIfNotExists($pdo, 'games', 'is_active', 'TINYINT NOT NULL DEFAULT 1');

    // 4. ตาราง teams
    addColumnIfNotExists($pdo, 'teams', 'category', "VARCHAR(20) NOT NULL DEFAULT 'open'");
    addColumnIfNotExists($pdo, 'teams', 'team_category', "VARCHAR(20) NOT NULL DEFAULT 'open'");
    addColumnIfNotExists($pdo, 'teams', 'is_solo_wrapper', 'TINYINT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'teams', 'logo_path', 'VARCHAR(255) NULL');

    // 5. ตาราง players
    addColumnIfNotExists($pdo, 'players', 'category', "VARCHAR(20) NOT NULL DEFAULT 'open'");
    addColumnIfNotExists($pdo, 'players', 'avatar_path', 'VARCHAR(255) NULL');
    addColumnIfNotExists($pdo, 'players', 'image_path', 'VARCHAR(255) NULL');
    addColumnIfNotExists($pdo, 'players', 'bio', 'TEXT NULL');

    // 6. ตาราง matches
    addColumnIfNotExists($pdo, 'matches', 'bracket_type', "VARCHAR(20) NOT NULL DEFAULT 'single'");
    addColumnIfNotExists($pdo, 'matches', 'category', "VARCHAR(20) NOT NULL DEFAULT 'open'");
    addColumnIfNotExists($pdo, 'matches', 'best_of', 'TINYINT NOT NULL DEFAULT 1');
    addColumnIfNotExists($pdo, 'matches', 'reset_match_id', 'INT NULL');
    addColumnIfNotExists($pdo, 'matches', 'match_index', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'matches', 'completed_at', 'DATETIME NULL');
    addColumnIfNotExists($pdo, 'matches', 'match_day', 'TINYINT NOT NULL DEFAULT 1');
    addColumnIfNotExists($pdo, 'matches', 'venue_station', 'VARCHAR(100) NULL');
    addColumnIfNotExists($pdo, 'matches', 'stream_url', 'VARCHAR(255) NULL');

    // 6.1 ตาราง tournament_groups
    addColumnIfNotExists($pdo, 'tournament_groups', 'category', "VARCHAR(20) NOT NULL DEFAULT 'open'");

    // 7. ตาราง accommodations
    addColumnIfNotExists($pdo, 'accommodations', 'image_path', 'VARCHAR(255) NULL');
    addColumnIfNotExists($pdo, 'accommodations', 'distance', 'VARCHAR(50) NULL');
    addColumnIfNotExists($pdo, 'accommodations', 'tournament_id', 'INT NULL');

    // 8. ตาราง users
    addColumnIfNotExists($pdo, 'users', 'status', "VARCHAR(20) NOT NULL DEFAULT 'active'");
    addColumnIfNotExists($pdo, 'users', 'security_question', 'VARCHAR(255) NULL');
    addColumnIfNotExists($pdo, 'users', 'security_answer_hash', 'VARCHAR(255) NULL');

    // 9. ตาราง team_rankings
    addColumnIfNotExists($pdo, 'team_rankings', 'category', "VARCHAR(20) NOT NULL DEFAULT 'open'");
    addColumnIfNotExists($pdo, 'team_rankings', 'points', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'team_rankings', 'matches_played', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'team_rankings', 'wins', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'team_rankings', 'losses', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'team_rankings', 'tournaments_played', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'team_rankings', 'podium_finishes', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'team_rankings', 'win_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00');

    // 10. ตาราง player_rankings
    addColumnIfNotExists($pdo, 'player_rankings', 'category', "VARCHAR(20) NOT NULL DEFAULT 'open'");
    addColumnIfNotExists($pdo, 'player_rankings', 'points', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'player_rankings', 'matches_played', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'player_rankings', 'wins', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'player_rankings', 'losses', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'player_rankings', 'tournaments_played', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'player_rankings', 'podium_finishes', 'INT NOT NULL DEFAULT 0');
    addColumnIfNotExists($pdo, 'player_rankings', 'win_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00');

    // 11. ตาราง team_members
    addColumnIfNotExists($pdo, 'team_members', 'is_substitute', 'TINYINT NOT NULL DEFAULT 0');

    // 12. ปรับขนาดฟิลด์สถานะของ tournament_registrations เพื่อรองรับสถานะละเอียด
    try {
        $pdo->exec("ALTER TABLE tournament_registrations MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'");
        $pdo->exec("ALTER TABLE tournament_registrations MODIFY COLUMN checkin_status VARCHAR(30) NOT NULL DEFAULT 'not_checked_in'");
    } catch (Exception $e) {}
    addColumnIfNotExists($pdo, 'tournament_registrations', 'admin_notes', 'TEXT NULL');
    addColumnIfNotExists($pdo, 'tournament_registrations', 'updated_by_name', 'VARCHAR(100) NULL');
    addColumnIfNotExists($pdo, 'tournament_registrations', 'updated_at', 'DATETIME NULL');

    // 13. ตารางประวัติการเช็คอินนักกีฬารายบุคคล (Individual Player Check-in)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tournament_player_checkins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tournament_id INT NOT NULL,
                team_id INT NULL,
                player_id INT NOT NULL,
                qr_code_token VARCHAR(64) NULL,
                checked_in_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_t_p (tournament_id, player_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (Exception $e) {}

    // 14. ตาราง Tournament Rosters Snapshot (บันทึกประวัติศาสตร์รายชื่อนักกีฬาในแต่ละรายการ)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tournament_rosters (
                roster_id INT AUTO_INCREMENT PRIMARY KEY,
                tournament_id INT NOT NULL,
                team_id INT NULL,
                player_id INT NOT NULL,
                in_game_role VARCHAR(60) NULL,
                is_captain TINYINT NOT NULL DEFAULT 0,
                is_substitute TINYINT NOT NULL DEFAULT 0,
                registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_t_t_p (tournament_id, team_id, player_id),
                KEY idx_p_t (player_id, tournament_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (Exception $e) {}

    // 15. ตารางคะแนนรายเกม (Match Games for Best-of-N series)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS match_games (
                match_game_id INT AUTO_INCREMENT PRIMARY KEY,
                match_id INT(10) UNSIGNED NOT NULL,
                game_number TINYINT NOT NULL,
                team1_score INT NOT NULL DEFAULT 0,
                team2_score INT NOT NULL DEFAULT 0,
                winner_team_id INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_match_game (match_id, game_number),
                FOREIGN KEY (match_id) REFERENCES matches(match_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (Exception $e) {}

    // 16. ตารางบันทึกประวัติการแก้ไขผลการอนุมัติ (Registration Audit Logs)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS registration_audit_logs (
                log_id INT AUTO_INCREMENT PRIMARY KEY,
                registration_id INT NOT NULL,
                tournament_id INT NOT NULL,
                team_id INT NULL,
                player_id INT NULL,
                old_status VARCHAR(30) NOT NULL,
                new_status VARCHAR(30) NOT NULL,
                changed_by_name VARCHAR(100) NOT NULL,
                reason TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_reg_id (registration_id),
                KEY idx_tour_id (tournament_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (Exception $e) {}

    // 17. ตารางกติกาการให้คะแนน Ranking แยกตามเกม (Game-Specific Ranking Rule Matrix)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS game_ranking_rules (
                rule_id INT AUTO_INCREMENT PRIMARY KEY,
                game_id INT UNSIGNED NOT NULL,
                rank_position VARCHAR(20) NOT NULL,
                points_awarded INT NOT NULL DEFAULT 0,
                tier VARCHAR(20) NOT NULL DEFAULT 'standard',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_game_rank_tier (game_id, rank_position, tier),
                FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Seed default rules if table is empty
        $cnt = $pdo->query("SELECT COUNT(*) FROM game_ranking_rules")->fetchColumn();
        if ($cnt == 0) {
            $allGames = $pdo->query("SELECT game_id FROM games")->fetchAll(PDO::FETCH_COLUMN);
            $defaultRules = [
                ['rank_position' => '1', 'points_awarded' => 100],
                ['rank_position' => '2', 'points_awarded' => 70],
                ['rank_position' => '3', 'points_awarded' => 50],
                ['rank_position' => '4', 'points_awarded' => 35],
                ['rank_position' => '5-8', 'points_awarded' => 20],
                ['rank_position' => '9-16', 'points_awarded' => 10],
            ];
            $insRule = $pdo->prepare("
                INSERT IGNORE INTO game_ranking_rules (game_id, rank_position, points_awarded, tier)
                VALUES (:gid, :pos, :pts, 'standard')
            ");
            foreach ($allGames as $gid) {
                foreach ($defaultRules as $r) {
                    $insRule->execute([
                        'gid' => $gid,
                        'pos' => $r['rank_position'],
                        'pts' => $r['points_awarded']
                    ]);
                }
            }
        }
    } catch (Exception $e) {}
}

/**
 * ฟังก์ชันตรวจสอบว่าเกมนี้เป็นการแข่งขันประเภทเดี่ยว (Solo) หรือไม่
 * โดยเช็คจากชื่อเกมที่มีคำเหล่านี้ผสมอยู่
 * @param string $gameName ชื่อเกมที่ต้องการตรวจสอบ
 * @return bool คืนค่า true หากเป็นเกมเดี่ยว
 */
function isSoloGame($gameName) {
    if (empty($gameName)) return false;
    
    // รายชื่อเกมที่เป็นประเภทบุคคล / เกมเดี่ยว (สามารถเพิ่มชื่อเกมเดี่ยวในอนาคตที่นี่ได้เลย)
    $soloGames = ['Tekken', 'Street Fighter', 'Efootball', 'Roblox'];
    
    foreach ($soloGames as $solo) {
        // ใช้ stripos เพื่อไม่สนใจตัวพิมพ์เล็ก-ใหญ่ (Case-insensitive)
        if (stripos($gameName, $solo) !== false) {
            return true;
        }
    }
    return false;
}