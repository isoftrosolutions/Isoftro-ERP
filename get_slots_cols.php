<?php
require_once __DIR__ . '/config/config.php';
$db = getDBConnection();
$stmt = $db->query('DESCRIBE timetable_slots');
while($r = $stmt->fetch()) echo $r['Field'].' ('.$r['Type'].')'."\n";
