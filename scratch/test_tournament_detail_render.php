<?php
chdir(__DIR__ . '/../pages');

// Test Male Bracket
$_GET['id'] = 23;
$_GET['category'] = 'male';
ob_start();
require 'tournament-detail.php';
$htmlMale = ob_get_clean();

echo "=== MALE BRACKET ===\n";
echo "Total Rounds rendered: " . substr_count($htmlMale, 'class="bracket-round"') . "\n";
echo "Total Matches rendered: " . substr_count($htmlMale, 'class="glass-card rounded-2xl') . "\n";

// Test Female Bracket
$_GET['id'] = 23;
$_GET['category'] = 'female';
ob_start();
require 'tournament-detail.php';
$htmlFemale = ob_get_clean();

echo "\n=== FEMALE BRACKET ===\n";
echo "Total Rounds rendered: " . substr_count($htmlFemale, 'class="bracket-round"') . "\n";
echo "Total Matches rendered: " . substr_count($htmlFemale, 'class="glass-card rounded-2xl') . "\n";
