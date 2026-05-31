<?php
$pdo = new PDO('mysql:host=localhost;dbname=db_farmer;charset=utf8', 'root', 'P@ssw0rd');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all columns
$cols = $pdo->query("DESCRIBE fmaster")->fetchAll(PDO::FETCH_ASSOC);
echo "=== COLUMNS ===\n";
foreach ($cols as $c) echo $c['Field'].' ('.$c['Type'].")\n";

// Get distinct TYPE_CODEs with sample
echo "\n=== TYPE_CODES ===\n";
$rows = $pdo->query("SELECT TYPE_CODE, COUNT(*) as cnt, MIN(NAME) as sample FROM fmaster GROUP BY TYPE_CODE ORDER BY TYPE_CODE")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "TYPE_CODE={$r['TYPE_CODE']} cnt={$r['cnt']} sample={$r['sample']}\n";
