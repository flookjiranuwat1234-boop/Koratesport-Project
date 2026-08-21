<?php
require_once __DIR__ . '/../config/db.php';

try {
    $stmt = $pdo->prepare("INSERT INTO tournament_registrations (tournament_id, player_id, team_id, status) VALUES (24, 515, NULL, 'pending')");
    $stmt->execute();
    $id = $pdo->lastInsertId();
    echo "Solo registration test succeeded! ID: $id\n";
    $pdo->prepare("DELETE FROM tournament_registrations WHERE tournament_registration_id = ?")->execute([$id]);
    echo "Cleaned up test record successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
