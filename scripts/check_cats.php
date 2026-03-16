<?php
$pdo = new PDO('mysql:host=localhost;dbname=evulery_pro;charset=utf8mb4', 'root', '');
$cats = $pdo->query('SELECT * FROM meal_categories ORDER BY sort_order')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cats as $c) {
    echo $c['name'] . ' | ' . $c['display_name'] . ' | ' . $c['start_time'] . '-' . $c['end_time'] . ' | active:' . $c['is_active'] . PHP_EOL;
}