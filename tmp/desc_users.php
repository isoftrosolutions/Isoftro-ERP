<?php
require_once __DIR__ . '/../config/config.php';
$db = getDBConnection();
$cols = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
echo implode(", ", $cols);
