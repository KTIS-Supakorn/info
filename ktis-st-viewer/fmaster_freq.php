<?php
header('Content-Type: text/plain; charset=utf-8');
$pdo = new PDO('mysql:host=localhost;dbname=db_farmer;charset=utf8', 'root', 'P@ssw0rd');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Show all tables to understand the DB
echo "=== TABLES ===\n";
$tbls = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tbls as $t) echo $t."\n";

// Frequency from dattable for fields we know
echo "\n=== CODETP02 frequency (top 10) ===\n";
$r = $pdo->query("SELECT CODETP02, COUNT(*) c FROM dattable WHERE CODETP02!='' GROUP BY CODETP02 ORDER BY c DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) echo $row['CODETP02']." => ".$row['c']."\n";

echo "\n=== CODETP22 frequency (top 15) ===\n";
$r = $pdo->query("SELECT CODETP22, COUNT(*) c FROM dattable WHERE CODETP22!='' AND CODETP22!='00' GROUP BY CODETP22 ORDER BY c DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) echo $row['CODETP22']." => ".$row['c']."\n";

echo "\n=== CODETP03 frequency ===\n";
$r = $pdo->query("SELECT CODETP03, COUNT(*) c FROM dattable WHERE CODETP03!='' GROUP BY CODETP03 ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) echo $row['CODETP03']." => ".$row['c']."\n";
