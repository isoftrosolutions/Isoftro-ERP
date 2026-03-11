<?php
include 'config/config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
$stmt = $pdo->query('SHOW TABLES');
while($row = $stmt->fetch()) {
    echo $row[0] . PHP_EOL;
}
