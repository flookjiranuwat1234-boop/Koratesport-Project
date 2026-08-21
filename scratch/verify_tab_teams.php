<?php
chdir(__DIR__ . '/../pages');

$_GET['id'] = 23;
$_GET['category'] = 'male';
ob_start();
require 'tournament-detail.php';
$htmlMale = ob_get_clean();

echo "Teams shown in Male tab:\n";
foreach (['DRAGON X ESPORT', 'FULL SENSE', 'NEXUS GAMING', 'PSG ESPORTS', 'CYBER KNIGHTS', 'BLACK PEARL ESPORT', 'SHADOW WOLVES', 'PHOENIX FORCE'] as $t) {
    if (strpos($htmlMale, $t) !== false) {
        echo " - Found: $t\n";
    }
}

$_GET['category'] = 'female';
ob_start();
require 'tournament-detail.php';
$htmlFemale = ob_get_clean();

echo "\nTeams shown in Female tab:\n";
foreach (['DRAGON X ESPORT', 'FULL SENSE', 'NEXUS GAMING', 'PSG ESPORTS', 'CYBER KNIGHTS', 'BLACK PEARL ESPORT', 'SHADOW WOLVES', 'PHOENIX FORCE'] as $t) {
    if (strpos($htmlFemale, $t) !== false) {
        echo " - Found: $t\n";
    }
}
