<?php
// Migrate local MySQL (unilearn_pi) to Railway MySQL

$localPdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=unilearn_pi", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
echo "Connected to local MySQL\n";

$remotePdo = new PDO("mysql:host=trolley.proxy.rlwy.net;port=10587;dbname=railway", 'root', 'SzlKYsijlipaJsDfARXvudgKXDAYMKAa', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
echo "Connected to Railway MySQL\n";

$remotePdo->exec("SET FOREIGN_KEY_CHECKS = 0");

$tables = $localPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Found " . count($tables) . " tables\n\n";

foreach ($tables as $table) {
    echo "Table: $table ... ";
    try {
        // Get CREATE TABLE from local
        $createInfo = $localPdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $createSQL = $createInfo['Create Table'];

        // Drop and recreate on Railway
        $remotePdo->exec("DROP TABLE IF EXISTS `$table`");
        $remotePdo->exec($createSQL);

        // Copy data
        $count = $localPdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        if ($count > 0) {
            $rows = $localPdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                $columns = array_keys($rows[0]);
                $colList = implode('`, `', $columns);
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $stmt = $remotePdo->prepare("INSERT INTO `$table` (`$colList`) VALUES ($placeholders)");
                $inserted = 0;
                foreach ($rows as $row) {
                    $stmt->execute(array_values($row));
                    $inserted++;
                }
                echo "OK ($inserted rows)\n";
            } else {
                echo "OK (empty)\n";
            }
        } else {
            echo "OK (empty)\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . substr($e->getMessage(), 0, 150) . "\n";
    }
}

$remotePdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "\nMIGRATION COMPLETE!\n";
