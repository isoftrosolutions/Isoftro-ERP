<?php
require_once 'config/config.php';
$db = getDBConnection();
$stmt = $db->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
