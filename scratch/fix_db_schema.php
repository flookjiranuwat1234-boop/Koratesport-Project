<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("ALTER TABLE tournament_registrations MODIFY COLUMN team_id INT(10) UNSIGNED NULL");
    echo "Successfully altered tournament_registrations.team_id to NULLable!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
