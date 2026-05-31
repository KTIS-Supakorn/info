<?php
header('Content-Type: application/json; charset=utf-8');
$pdo = new PDO('mysql:host=localhost;dbname=db_farmer;charset=utf8', 'root', 'P@ssw0rd');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$rows = $pdo->query("SELECT TYPE_CODE, CODE, NAME, PRICE_KT, UNIT_KT FROM fmaster ORDER BY TYPE_CODE, CODE")->fetchAll(PDO::FETCH_ASSOC);

$out = [];
foreach ($rows as $r) {
    $t = $r['TYPE_CODE'];
    if (!isset($out[$t])) $out[$t] = [];
    $out[$t][] = ['code'=>$r['CODE'], 'name'=>$r['NAME'], 'price'=>$r['PRICE_KT'], 'unit'=>$r['UNIT_KT']];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
