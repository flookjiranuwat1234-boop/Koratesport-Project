<?php
// includes/ranking_calculator.php
require_once __DIR__ . '/../config/db.php';

if (!function_exists('updateRankingsAfterMatch')) {
    /**
     * คำนวณและอัปเดตสถิติและคะแนน Ranking เมื่อแต่ละแมตช์จบลง
     * (Match-level Point Bonus + Win/Loss update)
     */
    function updateRankingsAfterMatch($pdo, $matchId)
    {
    $stmt = $pdo->prepare("
        SELECT m.*, t.game_id, t.tournament_id,
               COALESCE(tr1.category, t1.team_category, 'open') AS team1_cat,
               COALESCE(tr2.category, t2.team_category, 'open') AS team2_cat
        FROM matches m
        JOIN tournaments t ON t.tournament_id = m.tournament_id
        LEFT JOIN teams t1 ON t1.team_id = m.team1_id
        LEFT JOIN tournament_registrations tr1 ON tr1.tournament_id = m.tournament_id AND tr1.team_id = m.team1_id
        LEFT JOIN teams t2 ON t2.team_id = m.team2_id
        LEFT JOIN tournament_registrations tr2 ON tr2.tournament_id = m.tournament_id AND tr2.team_id = m.team2_id
        WHERE m.match_id = :id
    ");
    $stmt->execute(['id' => $matchId]);
    $m = $stmt->fetch();

    if (!$m || empty($m['winner_team_id']) || empty($m['game_id'])) {
        return;
    }

    $gameId = (int) $m['game_id'];
    $winnerId = (int) $m['winner_team_id'];
    $loserId = ($winnerId == $m['team1_id']) ? (int) $m['team2_id'] : (int) $m['team1_id'];
    $winnerCat = ($winnerId == $m['team1_id']) ? ($m['team1_cat'] ?? 'open') : ($m['team2_cat'] ?? 'open');
    $loserCat = ($loserId == $m['team1_id']) ? ($m['team1_cat'] ?? 'open') : ($m['team2_cat'] ?? 'open');

    // 1. อัปเดต Team Rankings
    if ($winnerId > 0) {
        $pdo->prepare("
            INSERT INTO team_rankings (team_id, game_id, category, points, matches_played, wins, losses, win_rate)
            VALUES (:tid, :gid, :cat, 5, 1, 1, 0, 100.00)
            ON DUPLICATE KEY UPDATE
                points = points + 5,
                matches_played = matches_played + 1,
                wins = wins + 1,
                win_rate = ROUND((wins / matches_played) * 100, 2)
        ")->execute(['tid' => $winnerId, 'gid' => $gameId, 'cat' => $winnerCat]);
    }

    if ($loserId > 0) {
        $pdo->prepare("
            INSERT INTO team_rankings (team_id, game_id, category, points, matches_played, wins, losses, win_rate)
            VALUES (:tid, :gid, :cat, 0, 1, 0, 1, 0.00)
            ON DUPLICATE KEY UPDATE
                matches_played = matches_played + 1,
                losses = losses + 1,
                win_rate = ROUND((wins / matches_played) * 100, 2)
        ")->execute(['tid' => $loserId, 'gid' => $gameId, 'cat' => $loserCat]);
    }

    // 2. อัปเดต Player Rankings สำหรับนักกีฬาทุกคนที่อยู่ใน Tournament Roster
    // ทีมชนะ (+5 แต้ม / Win + 1)
    if ($winnerId > 0) {
        $winPlayers = $pdo->prepare("SELECT player_id FROM tournament_rosters WHERE tournament_id = :tour_id AND team_id = :tid");
        $winPlayers->execute(['tour_id' => $m['tournament_id'], 'tid' => $winnerId]);
        $players = $winPlayers->fetchAll(PDO::FETCH_COLUMN);

        $updPlayer = $pdo->prepare("
            INSERT INTO player_rankings (player_id, game_id, category, points, matches_played, wins, losses, win_rate)
            VALUES (:pid, :gid, :cat, 5, 1, 1, 0, 100.00)
            ON DUPLICATE KEY UPDATE
                points = points + 5,
                matches_played = matches_played + 1,
                wins = wins + 1,
                win_rate = ROUND((wins / matches_played) * 100, 2)
        ");
        foreach ($players as $pid) {
            $updPlayer->execute(['pid' => $pid, 'gid' => $gameId, 'cat' => $winnerCat]);
        }
    }

    // ทีมแพ้ (Loss + 1)
    if ($loserId > 0) {
        $lossPlayers = $pdo->prepare("SELECT player_id FROM tournament_rosters WHERE tournament_id = :tour_id AND team_id = :tid");
        $lossPlayers->execute(['tour_id' => $m['tournament_id'], 'tid' => $loserId]);
        $players = $lossPlayers->fetchAll(PDO::FETCH_COLUMN);

        $updPlayer = $pdo->prepare("
            INSERT INTO player_rankings (player_id, game_id, category, points, matches_played, wins, losses, win_rate)
            VALUES (:pid, :gid, :cat, 0, 1, 0, 1, 0.00)
            ON DUPLICATE KEY UPDATE
                matches_played = matches_played + 1,
                losses = losses + 1,
                win_rate = ROUND((wins / matches_played) * 100, 2)
        ");
        foreach ($players as $pid) {
            $updPlayer->execute(['pid' => $pid, 'gid' => $gameId, 'cat' => $loserCat]);
        }
    }
}
}

if (!function_exists('updateRankingsAfterTournament')) {
/**
 * คำนวณคะแนนอันดับและโบนัสทัวร์นาเมนต์เมื่อการแข่งขันจบลงทั้งรายการ (Tournament Placement Points)
 * โดยใช้กฎกติกาการให้คะแนนจากตาราง game_ranking_rules
 */
function updateRankingsAfterTournament($pdo, $tournamentId)
{
    $tStmt = $pdo->prepare("SELECT * FROM tournaments WHERE tournament_id = :id");
    $tStmt->execute(['id' => $tournamentId]);
    $tour = $tStmt->fetch();

    if (!$tour || empty($tour['game_id'])) {
        return;
    }

    $gameId = (int) $tour['game_id'];

    // 1. ดึงกฎคะแนนของเกมนี้
    $ruleStmt = $pdo->prepare("SELECT rank_position, points_awarded FROM game_ranking_rules WHERE game_id = :gid");
    $ruleStmt->execute(['gid' => $gameId]);
    $rules = $ruleStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $pts1st = (int) ($rules['1'] ?? 100);
    $pts2nd = (int) ($rules['2'] ?? 70);
    $pts3rd = (int) ($rules['3'] ?? 50);
    $pts4th = (int) ($rules['4'] ?? 35);
    $ptsTop8 = (int) ($rules['5-8'] ?? 20);
    $ptsTop16 = (int) ($rules['9-16'] ?? 10);

    // 2. ค้นหาแมตช์รอบชิงชนะเลิศ (Grand Finals)
    $gfStmt = $pdo->prepare("
        SELECT * FROM matches 
        WHERE tournament_id = :tid AND status = 'completed' AND winner_team_id IS NOT NULL 
        ORDER BY round_number DESC, match_id DESC LIMIT 1
    ");
    $gfStmt->execute(['tid' => $tournamentId]);
    $gfMatch = $gfStmt->fetch();

    if (!$gfMatch) {
        return;
    }

    $championTeamId = (int) $gfMatch['winner_team_id'];
    $runnerUpTeamId = ($championTeamId == $gfMatch['team1_id']) ? (int) $gfMatch['team2_id'] : (int) $gfMatch['team1_id'];

    // 3. แจกแต้มอันดับ 1 (Champion)
    if ($championTeamId > 0) {
        awardTournamentPlacementPoints($pdo, $tournamentId, $gameId, $championTeamId, $pts1st, true, 1);
    }

    // 4. แจกแต้มอันดับ 2 (Runner-Up)
    if ($runnerUpTeamId > 0) {
        awardTournamentPlacementPoints($pdo, $tournamentId, $gameId, $runnerUpTeamId, $pts2nd, true, 2);
    }

    // 5. แจกแต้มอันดับ 3 (Bronze Match / Semifinalists)
    $semiStmt = $pdo->prepare("
        SELECT team1_id, team2_id, winner_team_id FROM matches 
        WHERE tournament_id = :tid AND round_number = :rn AND match_id != :gf_id AND status = 'completed'
    ");
    $semiStmt->execute([
        'tid' => $tournamentId,
        'rn' => max(1, $gfMatch['round_number'] - 1),
        'gf_id' => $gfMatch['match_id']
    ]);
    $semiMatches = $semiStmt->fetchAll();

    foreach ($semiMatches as $sm) {
        $semiLoser = ($sm['winner_team_id'] == $sm['team1_id']) ? (int) $sm['team2_id'] : (int) $sm['team1_id'];
        if ($semiLoser > 0 && $semiLoser != $championTeamId && $semiLoser != $runnerUpTeamId) {
            awardTournamentPlacementPoints($pdo, $tournamentId, $gameId, $semiLoser, $pts3rd, true, 3);
        }
    }

    // 6. อัปเดต tournaments_played ให้กับทุกทีมและนักกีฬาทั้งหมดในทัวร์นาเมนต์นี้
    $allTeams = $pdo->prepare("SELECT DISTINCT team_id FROM tournament_registrations WHERE tournament_id = :tid AND status = 'approved' AND team_id IS NOT NULL");
    $allTeams->execute(['tid' => $tournamentId]);
    $teamsList = $allTeams->fetchAll(PDO::FETCH_COLUMN);

    foreach ($teamsList as $tid) {
        $pdo->prepare("
            UPDATE team_rankings 
            SET tournaments_played = tournaments_played + 1 
            WHERE team_id = :tid AND game_id = :gid
        ")->execute(['tid' => $tid, 'gid' => $gameId]);
    }

    $allPlayers = $pdo->prepare("SELECT DISTINCT player_id FROM tournament_rosters WHERE tournament_id = :tid");
    $allPlayers->execute(['tid' => $tournamentId]);
    $playersList = $allPlayers->fetchAll(PDO::FETCH_COLUMN);

    foreach ($playersList as $pid) {
        $pdo->prepare("
            UPDATE player_rankings 
            SET tournaments_played = tournaments_played + 1 
            WHERE player_id = :pid AND game_id = :gid
        ")->execute(['pid' => $pid, 'gid' => $gameId]);
    }
}
}

if (!function_exists('awardTournamentPlacementPoints')) {
/**
 * ฟังก์ชันช่วยแจกคะแนนและเพิ่มสถิติ Podium Finish
 */
function awardTournamentPlacementPoints($pdo, $tournamentId, $gameId, $teamId, $points, $isPodium = false, $rankPosition = 1)
{
    // อัปเดตคะแนนทีม
    $pdo->prepare("
        INSERT INTO team_rankings (team_id, game_id, category, points, podium_finishes)
        VALUES (:tid, :gid, 'open', :pts, :podium)
        ON DUPLICATE KEY UPDATE
            points = points + :pts2,
            podium_finishes = podium_finishes + :podium2
    ")->execute([
        'tid' => $teamId,
        'gid' => $gameId,
        'pts' => $points,
        'podium' => $isPodium ? 1 : 0,
        'pts2' => $points,
        'podium2' => $isPodium ? 1 : 0,
    ]);

    // อัปเดตคะแนนนักกีฬาทุกคนใน Roster
    $rosterStmt = $pdo->prepare("SELECT player_id FROM tournament_rosters WHERE tournament_id = :tour_id AND team_id = :tid");
    $rosterStmt->execute(['tour_id' => $tournamentId, 'tid' => $teamId]);
    $players = $rosterStmt->fetchAll(PDO::FETCH_COLUMN);

    $updPlayer = $pdo->prepare("
        INSERT INTO player_rankings (player_id, game_id, category, points, podium_finishes)
        VALUES (:pid, :gid, 'open', :pts, :podium)
        ON DUPLICATE KEY UPDATE
            points = points + :pts2,
            podium_finishes = podium_finishes + :podium2
    ");
    foreach ($players as $pid) {
        $updPlayer->execute([
            'pid' => $pid,
            'gid' => $gameId,
            'pts' => $points,
            'podium' => $isPodium ? 1 : 0,
            'pts2' => $points,
            'podium2' => $isPodium ? 1 : 0,
        ]);
    }
}
}
