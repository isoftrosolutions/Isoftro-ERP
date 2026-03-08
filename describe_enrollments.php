<?php
require_once __DIR__ . '/config/config.php';
$db = getDBConnection();
$stmt = $db->query("DESCRIBE enrollments");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
