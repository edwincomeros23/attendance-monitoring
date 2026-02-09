<?php
require_once '../../db.php';

echo "Curriculum table columns:\n";
$res = $conn->query('DESCRIBE curriculum');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

echo "\n\nSample curriculum data:\n";
$res = $conn->query('SELECT * FROM curriculum LIMIT 3');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
?>
