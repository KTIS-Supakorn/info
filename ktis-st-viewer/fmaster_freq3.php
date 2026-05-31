<?php
header('Content-Type: text/plain; charset=utf-8');
$pdo = new PDO('mysql:host=localhost;dbname=db_farmer;charset=utf8', 'root', 'P@ssw0rd');

function freq($pdo, $table, $col, $label, $top=8) {
    try {
        $r = $pdo->query("SELECT `$col`, COUNT(*) c FROM `$table` WHERE `$col`!='' AND `$col`!='00' AND `$col`!='0' GROUP BY `$col` ORDER BY c DESC LIMIT $top")->fetchAll(PDO::FETCH_ASSOC);
        echo "[$label] top from $table.$col:\n";
        foreach ($r as $row) echo "  ".$row[$col]." => ".$row['c']."\n";
    } catch (Exception $e) { echo "Error $table.$col: ".$e->getMessage()."\n"; }
}

// ST04 (datprt22) - บำรุงหลังฝน
echo "=== ST04 datprt22 (300K rows) ===\n";
freq($pdo, 'datprt22', 'CODETP11', 'วิธีการใส่ปุ๋ย [11]');
freq($pdo, 'datprt22', 'CODE11_01', 'สูตรปุ๋ย 1 [21]');
freq($pdo, 'datprt22', 'CODE11_02', 'สูตรปุ๋ย 2 [21]');
freq($pdo, 'datprt22', 'CODETP26', 'สภาพวัชพืช [26]');
freq($pdo, 'datprt22', 'CODETP14', 'วิธีการพ่น [14]');
freq($pdo, 'datprt22', 'CODE10_01', 'ยาฆ่าวัชพืช 1 [10]');
freq($pdo, 'datprt22', 'CODE10_02', 'ยาฆ่าวัชพืช 2 [10]');
freq($pdo, 'datprt22', 'CODETP29', 'วิธีให้น้ำ [29]');

// ST03 (datprt21) - บำรุงตอหลังตัด
echo "\n=== ST03 datprt21 (142K rows) ===\n";
freq($pdo, 'datprt21', 'CODE11_01', 'วิธีการใส่ปุ๋ย [11]');
freq($pdo, 'datprt21', 'CODE21_01', 'สูตรปุ๋ย 1 [21]');
freq($pdo, 'datprt21', 'CODE21_02', 'สูตรปุ๋ย 2 [21]');
freq($pdo, 'datprt21', 'CODETP29', 'วิธีให้น้ำ [29]');
freq($pdo, 'datprt21', 'CODETP25', 'การจัดการใบ [25]');

// ST07 (datprt07) - บำรุงผ่านแล้ง
echo "\n=== ST07 datprt07 (27K rows) ===\n";
freq($pdo, 'datprt07', 'CODETP11', 'วิธีการใส่ปุ๋ย [11]');
freq($pdo, 'datprt07', 'CODE11_01', 'สูตรปุ๋ย 1 [21]');
freq($pdo, 'datprt07', 'CODETP29', 'วิธีให้น้ำ [29]');

// ST05 (datprt23) - โรคแมลง
echo "\n=== ST05 datprt23 (17K rows) ===\n";
freq($pdo, 'datprt23', 'CODETP12', 'แมลงศัตรู [12]');
freq($pdo, 'datprt23', 'CODETP13', 'โรคอ้อย [13]');

// ST06 (datprt30) - เก็บเกี่ยว
echo "\n=== ST06 datprt30 (271K rows) ===\n";
freq($pdo, 'datprt30', 'CODETP17', 'วิธีตัด [17]');
freq($pdo, 'datprt30', 'CODETP18', 'แรงงานตัด [18]');
freq($pdo, 'datprt30', 'CODETP19', 'วิธีขึ้นอ้อย [19]');
freq($pdo, 'datprt30', 'CODETP31', 'การรวมผล [31]');
