<?php
header('Content-Type: text/plain; charset=utf-8');
$pdo = new PDO('mysql:host=localhost;dbname=db_farmer;charset=utf8', 'root', 'P@ssw0rd');

// Check structure of key ST tables
$tables = ['datprt22','datprt23','datprt30','datprt31','datprt07','datprt21'];
foreach ($tables as $tbl) {
    try {
        $cols = $pdo->query("DESCRIBE `$tbl`")->fetchAll(PDO::FETCH_COLUMN);
        echo "=== $tbl cols: ".implode(', ',$cols)."\n";
        $cnt = $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
        echo "    rows: $cnt\n";
    } catch (Exception $e) { echo "=== $tbl: ".$e->getMessage()."\n"; }
}

// Check activity_combo_value which might have fmaster type usage
echo "\n=== activity_combo_value columns ===\n";
try {
    $cols = $pdo->query("DESCRIBE activity_combo_value")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) echo $c['Field'].' ('.$c['Type'].")\n";
    $cnt = $pdo->query("SELECT COUNT(*) FROM activity_combo_value")->fetchColumn();
    echo "rows: $cnt\n";
    $sample = $pdo->query("SELECT * FROM activity_combo_value LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($sample as $r) echo json_encode($r, JSON_UNESCAPED_UNICODE)."\n";
} catch (Exception $e) { echo $e->getMessage()."\n"; }

// Check datprt22 (likely ST04 บำรุงหลังฝน) frequency for fertilizer
echo "\n=== datprt22 top columns if exists ===\n";
try {
    $sample = $pdo->query("SELECT * FROM datprt22 LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    if ($sample) { foreach (array_keys($sample[0]) as $k) echo $k." "; echo "\n"; }
} catch (Exception $e) { echo $e->getMessage()."\n"; }

// Check dattable_datprt22
echo "\n=== dattable_datprt22 columns ===\n";
try {
    $cols = $pdo->query("DESCRIBE dattable_datprt22")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) echo $c['Field'].' ('.$c['Type'].")\n";
    $cnt = $pdo->query("SELECT COUNT(*) FROM dattable_datprt22")->fetchColumn();
    echo "rows: $cnt\n";
} catch (Exception $e) { echo $e->getMessage()."\n"; }
