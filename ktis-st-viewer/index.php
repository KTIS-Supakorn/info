<?php
// ─── DB Config ───────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'P@ssw0rd');
define('DB_NAME', 'db_farmer');

$pdo = new PDO(
    'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ─── Lookup tables (fmaster) ─────────────────────────────────────────────────
$lookup = [];
$stmt = $pdo->query("SELECT TYPE_CODE, CODE, NAME FROM fmaster ORDER BY TYPE_CODE, CODE");
foreach ($stmt as $row) {
    $lookup[$row['TYPE_CODE']][$row['CODE']] = $row['NAME'];
}

function lk(array $lookup, string $type, string $code, string $default = ''): string {
    return $lookup[$type][$code] ?? ($default ?: $code);
}

function caneGroup(string $code): string {
    if (in_array($code, ['11','12','13','14','15'])) return 'ปลูกใหม่';
    if ($code >= '21' && $code <= '29') return 'ตอ';
    return '';
}

// ─── Filter params ───────────────────────────────────────────────────────────
$q        = trim($_GET['q']     ?? '');
$amphoe   = trim($_GET['am']    ?? '');
$cane     = trim($_GET['ct']    ?? '');
$contract = trim($_GET['pr']    ?? '');
$survy    = trim($_GET['sc']    ?? '');
$year     = trim($_GET['yr']    ?? '');
$page     = max(1, (int)($_GET['pg']  ?? 1));
$per      = in_array((int)($_GET['pp'] ?? 30), [20,30,50,100]) ? (int)$_GET['pp'] : 30;
$sort     = preg_replace('/[^a-zA-Z_]/', '', $_GET['sb'] ?? 'Land_id');
$dir      = strtolower($_GET['sd'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$is_ajax  = !empty($_GET['json']);

$allowed_sort = ['Land_id','Farmr_name','Land_qty1','Land_amphr','Land_tumbn','LAND_PROMI',
                 'Survy_Code','DISTN_LONG','Survy_date','Prodt_year','CODETP02'];
if (!in_array($sort, $allowed_sort)) $sort = 'Land_id';

// ─── Build WHERE clause ───────────────────────────────────────────────────────
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(d.Land_id LIKE :q OR d.Farmr_name LIKE :q2 OR d.Farmr_code LIKE :q3)';
    $params[':q']  = '%'.$q.'%';
    $params[':q2'] = '%'.$q.'%';
    $params[':q3'] = '%'.$q.'%';
}
if ($amphoe   !== '') { $where[] = 'd.Land_amphr = :am'; $params[':am'] = $amphoe; }
if ($cane     !== '') { $where[] = 'd.CODETP02 = :ct';   $params[':ct'] = $cane; }
if ($contract !== '') { $where[] = 'd.LAND_PROMI = :pr';  $params[':pr'] = $contract; }
if ($survy    !== '') { $where[] = 'd.Survy_Code = :sc';  $params[':sc'] = $survy; }
if ($year     !== '') { $where[] = 'd.Prodt_year = :yr';  $params[':yr'] = $year; }

$whereSQL = implode(' AND ', $where);

// ─── Count total matching rows ────────────────────────────────────────────────
$cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM dattable d WHERE $whereSQL");
$cnt_stmt->execute($params);
$total = (int)$cnt_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per;

// ─── Fetch page rows ─────────────────────────────────────────────────────────
$sql = "SELECT d.Land_id, d.Prodt_year, d.Farmr_code, d.Farmr_name, d.LAND_PROMI,
               d.Land_moo, d.LAND_BAN, d.Land_tumbn, d.Land_amphr, d.Land_provn,
               d.Land_qty1, d.CODETP01, d.CODETP02, d.CODETP03, d.DISTN_LONG,
               d.CODETP22, d.Survy_Code, d.line_CODE, d.Survy_date,
               d.Land_cor_x, d.Land_cor_y, d.CENT_POINT,
               d.LAND_NORTH, d.LAND_SOUTH, d.LAND_EAST, d.LAND_WEST,
               d.CANE_TUNAV, d.CANE_QTYS,
               ft2.NAME  AS cane_type_name,
               ft3.NAME  AS water_source,
               ft22.NAME AS variety
        FROM dattable d
        LEFT JOIN fmaster ft2  ON ft2.TYPE_CODE='02'  AND ft2.CODE=d.CODETP02
        LEFT JOIN fmaster ft3  ON ft3.TYPE_CODE='03'  AND ft3.CODE=d.CODETP03
        LEFT JOIN fmaster ft22 ON ft22.TYPE_CODE='22' AND ft22.CODE=d.CODETP22
        WHERE $whereSQL
        ORDER BY d.`$sort` $dir
        LIMIT :lim OFFSET :off";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $per, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// ─── AJAX: return JSON ────────────────────────────────────────────────────────
if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'   => $r['Land_id'],
            'yr'   => $r['Prodt_year'],
            'fn'   => trim($r['Farmr_name']),
            'fc'   => $r['Farmr_code'],
            'pr'   => trim($r['LAND_PROMI']),
            'tb'   => trim($r['Land_tumbn']),
            'am'   => trim($r['Land_amphr']),
            'pv'   => trim($r['Land_provn']),
            'mu'   => $r['Land_moo'],
            'bn'   => trim($r['LAND_BAN']),
            'ar'   => $r['Land_qty1'],
            'ct'   => $r['CODETP02'],
            'ctn'  => $r['cane_type_name'] ?? '',
            'cg'   => caneGroup($r['CODETP02']),
            'vr'   => $r['variety'] ?? '-',
            'ws'   => $r['water_source'] ?? '',
            'dt'   => (float)$r['DISTN_LONG'],
            'sc'   => $r['Survy_Code'],
            'lc'   => $r['line_CODE'],
            'sd'   => $r['Survy_date'],
            'cx'   => $r['Land_cor_x'],
            'cy'   => $r['Land_cor_y'],
            'cp'   => trim($r['CENT_POINT']),
            'tn'   => (float)$r['CANE_TUNAV'],
            'tq'   => (float)$r['CANE_QTYS'],
            'no'   => trim($r['LAND_NORTH']),
            'so'   => trim($r['LAND_SOUTH']),
            'ea'   => trim($r['LAND_EAST']),
            'we'   => trim($r['LAND_WEST']),
        ];
    }
    echo json_encode(['total'=>$total,'page'=>$page,'total_pages'=>$total_pages,'per'=>$per,'rows'=>$out], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Stats (run only on initial page load, no filters) ───────────────────────
$stats_sql = "SELECT COUNT(*) as total,
  SUM(CASE WHEN Land_qty1 REGEXP '^[0-9]+$' THEN CAST(Land_qty1 AS UNSIGNED) ELSE 0 END) as total_rai,
  SUM(CODETP02 IN ('11','12','13','14','15')) as plant_count,
  SUM(CODETP02 IN ('21','22','23','24')) as ratoon_count
  FROM dattable";
$st = $pdo->query($stats_sql)->fetch();

// Top amphoes
$top_am = $pdo->query("SELECT Land_amphr, COUNT(*) as cnt FROM dattable WHERE Land_amphr!='' GROUP BY Land_amphr ORDER BY cnt DESC LIMIT 5")->fetchAll();

// Filter dropdown values
function getDistinct(PDO $pdo, string $col): array {
    return $pdo->query("SELECT DISTINCT `$col` FROM dattable WHERE `$col`!='' ORDER BY `$col`")->fetchAll(PDO::FETCH_COLUMN);
}
$amphoe_list   = getDistinct($pdo, 'Land_amphr');
$year_list     = array_reverse(getDistinct($pdo, 'Prodt_year'));
$contract_list = getDistinct($pdo, 'LAND_PROMI');
$survy_list    = getDistinct($pdo, 'Survy_Code');

// Cane types from fmaster type 02
$cane_list = $pdo->query("SELECT CODE, NAME FROM fmaster WHERE TYPE_CODE='02' ORDER BY CODE")->fetchAll();

// ─── Helper: build URL with overridden params ──────────────────────────────
function qurl(array $over = []): string {
    $base = ['q'=>$GLOBALS['q'],'am'=>$GLOBALS['amphoe'],'ct'=>$GLOBALS['cane'],
             'pr'=>$GLOBALS['contract'],'sc'=>$GLOBALS['survy'],'yr'=>$GLOBALS['year'],
             'pg'=>$GLOBALS['page'],'pp'=>$GLOBALS['per'],'sb'=>$GLOBALS['sort'],'sd'=>$GLOBALS['dir']];
    $merged = array_merge($base, $over);
    return '?'.http_build_query(array_filter($merged, function($v){ return $v!==''&&$v!==null; }));
}

function sortUrl(string $col): string {
    global $sort, $dir;
    $newdir = ($sort === $col && $dir === 'ASC') ? 'desc' : 'asc';
    return qurl(['sb'=>$col,'sd'=>$newdir,'pg'=>1]);
}

function sortIcon(string $col): string {
    global $sort, $dir;
    if ($sort !== $col) return '<span class="si si-none"></span>';
    return $dir === 'ASC' ? '<span class="si si-asc"></span>' : '<span class="si si-desc"></span>';
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function caneBadge(string $ct, string $ctn): string {
    if (!$ct) return '<span class="tag tag-other">-</span>';
    if (in_array($ct, ['11','12','13','14','15'])) return '<span class="tag tag-plant">'.e($ctn).'</span>';
    if ($ct >= '21' && $ct <= '29') return '<span class="tag tag-ratoon">'.e($ctn).'</span>';
    return '<span class="tag tag-other">'.e($ctn).'</span>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>KTIS — ข้อมูลแปลงอ้อยทั้งหมด</title>
<style>
:root{
  --green:#16a34a;--gl:#dcfce7;--gd:#14532d;
  --blue:#2563eb;--bl:#dbeafe;
  --gray:#64748b;--bg:#f1f5f9;--wh:#fff;--bd:#e2e8f0;
  --sha:0 1px 3px rgba(0,0,0,.1);--shm:0 4px 16px rgba(0,0,0,.15);
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Sarabun',sans-serif;font-size:14px;background:var(--bg);color:#1e293b}
.topbar{background:linear-gradient(135deg,#14532d,#166534);color:#fff;padding:12px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 2px 8px rgba(0,0,0,.2)}
.topbar-icon{font-size:28px}
.topbar h1{font-size:18px;font-weight:800;line-height:1.3}
.topbar small{font-size:11px;opacity:.75;display:block}
.stats{display:flex;flex-wrap:wrap;gap:10px;padding:14px 20px 0}
.sc{background:var(--wh);border:1px solid var(--bd);border-radius:12px;padding:12px 16px;flex:1;min-width:110px;box-shadow:var(--sha)}
.sc .slb{font-size:10px;color:var(--gray);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.sc .svl{font-size:24px;font-weight:800;color:var(--gd);line-height:1}
.sc .ssb{font-size:11px;color:var(--gray);margin-top:3px}
.fb{background:var(--wh);border:1px solid var(--bd);border-radius:12px;padding:14px 16px;margin:12px 20px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;box-shadow:var(--sha)}
.fg label{font-size:11px;color:var(--gray);display:block;margin-bottom:4px;font-weight:700;text-transform:uppercase;letter-spacing:.4px}
input[type=text],select{border:1px solid var(--bd);border-radius:8px;padding:7px 12px;font-size:13px;font-family:inherit;color:#1e293b;outline:none;background:var(--wh)}
input[type=text]{width:230px}
select{min-width:140px}
input[type=text]:focus,select:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(22,163,74,.15)}
.btn{border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-family:inherit;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block;transition:all .15s}
.btn-green{background:var(--green);color:#fff}.btn-green:hover{background:#15803d}
.btn-gray{background:#f1f5f9;color:#475569;border:1px solid var(--bd)}.btn-gray:hover{background:#e2e8f0}
.sbar{display:flex;align-items:center;gap:10px;padding:4px 20px 8px;font-size:13px;color:var(--gray);flex-wrap:wrap}
.sbar b{color:#1e293b}
.pss{border:1px solid var(--bd);border-radius:6px;padding:5px 8px;font-size:13px;font-family:inherit}
.tw{margin:0 20px;overflow-x:auto;border-radius:12px;box-shadow:var(--sha)}
.dt{width:100%;border-collapse:collapse;background:var(--wh)}
.dt thead tr{background:var(--gd);color:#fff;font-size:12px}
.dt th{padding:10px 12px;text-align:left;white-space:nowrap;font-weight:700}
.dt th a{color:#fff;text-decoration:none;display:flex;align-items:center;gap:4px;white-space:nowrap}
.dt th a:hover{opacity:.85}
.si::after{font-size:10px;opacity:.6}
.si-none::after{content:"⇅"}
.si-asc::after{content:"▲";opacity:1;color:#86efac}
.si-desc::after{content:"▼";opacity:1;color:#86efac}
.dt tbody tr{border-top:1px solid #f1f5f9;cursor:pointer;transition:background .1s}
.dt tbody tr:hover{background:#f0fdf4}
.dt td{padding:9px 12px;font-size:13px;vertical-align:middle}
.lid{font-family:monospace;font-weight:700;color:var(--gd);font-size:13px}
.tag{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.tag-plant{background:#dcfce7;color:#14532d}
.tag-ratoon{background:#dbeafe;color:#1e3a8a}
.tag-other{background:#f1f5f9;color:#475569}
.mu{font-size:12px;color:var(--gray)}
.tr{max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* pagination */
.pg{display:flex;align-items:center;gap:5px;padding:14px 20px;justify-content:center;flex-wrap:wrap}
.pg a,.pg span{border:1px solid var(--bd);background:var(--wh);color:#374151;border-radius:7px;padding:5px 13px;font-size:13px;text-decoration:none;transition:all .1s;font-family:inherit}
.pg a:hover{background:#f0fdf4;border-color:var(--green)}
.pg .cur{background:var(--gd);color:#fff;border-color:var(--gd)}
.pg .dis{opacity:.4;cursor:default;pointer-events:none}
.pg .pginfo{background:none;border:none;color:var(--gray)}
/* modal */
.mo{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:none;align-items:center;justify-content:center;padding:16px}
.mo.open{display:flex}
.mb{background:var(--wh);border-radius:16px;width:100%;max-width:680px;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,.25)}
.mh{background:linear-gradient(135deg,#14532d,#166534);color:#fff;padding:16px 20px;border-radius:16px 16px 0 0;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0}
.mh h3{font-size:16px;font-weight:800}
.mc{background:none;border:none;color:#fff;font-size:24px;cursor:pointer;line-height:1;opacity:.8}
.mc:hover{opacity:1}
.mbody{padding:20px}
.stitle{font-size:12px;font-weight:800;color:var(--gd);text-transform:uppercase;letter-spacing:.6px;margin:16px 0 8px;padding-bottom:4px;border-bottom:2px solid var(--gl)}
.dg{display:grid;grid-template-columns:1fr 1fr;gap:8px 12px}
.di{background:#f8fafc;border:1px solid var(--bd);border-radius:8px;padding:9px 12px}
.di small{display:block;font-size:10px;color:var(--gray);margin-bottom:3px;text-transform:uppercase;letter-spacing:.4px}
.di b{font-size:14px}
.di.full{grid-column:1/-1}
.mfoot{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;padding-top:14px;border-top:1px solid var(--bd)}
.nr{text-align:center;padding:50px;color:var(--gray);font-size:15px}
@media(max-width:640px){.stats{padding:10px}.fb{margin:8px 10px}.tw{margin:0 10px}.dg{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-icon">🌿</div>
  <div>
    <h1>KTIS — ข้อมูลแปลงอ้อยทั้งหมด</h1>
    <small>ระบบบันทึกกิจกรรมแปลงอ้อย | db_farmer | ข้อมูล Live จากฐานข้อมูล</small>
  </div>
</div>

<!-- Stats -->
<div class="stats">
  <div class="sc"><div class="slb">แปลงทั้งหมด</div><div class="svl"><?= number_format($st['total']) ?></div><div class="ssb">แปลงในระบบ</div></div>
  <div class="sc"><div class="slb">พื้นที่รวม</div><div class="svl"><?= number_format($st['total_rai']) ?></div><div class="ssb">ไร่</div></div>
  <div class="sc"><div class="slb">ปลูกใหม่</div><div class="svl"><?= number_format($st['plant_count']) ?></div><div class="ssb">แปลง</div></div>
  <div class="sc"><div class="slb">อ้อยตอ</div><div class="svl"><?= number_format($st['ratoon_count']) ?></div><div class="ssb">แปลง</div></div>
  <?php foreach ($top_am as $a): ?>
  <div class="sc"><div class="slb">อ.<?= e($a['Land_amphr']) ?></div><div class="svl"><?= number_format($a['cnt']) ?></div><div class="ssb">แปลง</div></div>
  <?php endforeach; ?>
</div>

<!-- Filter form -->
<form class="fb" method="get" id="filterForm">
  <div class="fg">
    <label>ค้นหา (รหัสแปลง / ชื่อ / สัญญา)</label>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="เช่น 7210174 หรือ สมหวัง" id="searchInput">
  </div>
  <div class="fg">
    <label>อำเภอ</label>
    <select name="am">
      <option value="">— ทั้งหมด —</option>
      <?php foreach ($amphoe_list as $a): ?>
      <option value="<?= e($a) ?>" <?= $amphoe===$a?'selected':'' ?>><?= e($a) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="fg">
    <label>ชนิดอ้อย</label>
    <select name="ct">
      <option value="">— ทั้งหมด —</option>
      <?php foreach ($cane_list as $c): ?>
      <option value="<?= e($c['CODE']) ?>" <?= $cane===$c['CODE']?'selected':'' ?>><?= e($c['NAME']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="fg">
    <label>ประเภทสัญญา</label>
    <select name="pr">
      <option value="">— ทั้งหมด —</option>
      <?php foreach ($contract_list as $c): ?>
      <option value="<?= e($c) ?>" <?= $contract===$c?'selected':'' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="fg">
    <label>รหัส นสส.</label>
    <select name="sc">
      <option value="">— ทั้งหมด —</option>
      <?php foreach ($survy_list as $s): ?>
      <option value="<?= e($s) ?>" <?= $survy===$s?'selected':'' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="fg">
    <label>ปีการผลิต</label>
    <select name="yr">
      <option value="">— ทั้งหมด —</option>
      <?php foreach ($year_list as $y): ?>
      <option value="<?= e($y) ?>" <?= $year===$y?'selected':'' ?>><?= e($y) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <input type="hidden" name="pp" value="<?= $per ?>">
  <input type="hidden" name="sb" value="<?= e($sort) ?>">
  <input type="hidden" name="sd" value="<?= strtolower($dir) ?>">
  <div class="fg" style="align-self:flex-end;display:flex;gap:6px">
    <button type="submit" class="btn btn-green">🔍 ค้นหา</button>
    <a href="?" class="btn btn-gray">↺ ล้าง</a>
  </div>
</form>

<!-- Summary + page size -->
<div class="sbar">
  <span>แสดง <b><?= number_format($total) ?></b> แปลง จากทั้งหมด <b><?= number_format($st['total']) ?></b> แปลง
    (หน้า <b><?= $page ?></b> / <b><?= $total_pages ?></b>)
  </span>
  <span style="margin-left:auto;display:flex;align-items:center;gap:8px">
    แสดงหน้าละ
    <select class="pss" onchange="changePP(this.value)">
      <?php foreach ([20,30,50,100] as $pp): ?>
      <option value="<?= $pp ?>" <?= $per==$pp?'selected':'' ?>><?= $pp ?></option>
      <?php endforeach; ?>
    </select>
    แถว
  </span>
</div>

<!-- Table -->
<div class="tw">
<table class="dt">
  <thead>
    <tr>
      <th><a href="<?= sortUrl('Land_id') ?>">รหัสแปลง <?= sortIcon('Land_id') ?></a></th>
      <th><a href="<?= sortUrl('Farmr_name') ?>">ชื่อชาวไร่ <?= sortIcon('Farmr_name') ?></a></th>
      <th><a href="<?= sortUrl('Land_qty1') ?>">ไร่ <?= sortIcon('Land_qty1') ?></a></th>
      <th><a href="<?= sortUrl('CODETP02') ?>">ชนิดอ้อย <?= sortIcon('CODETP02') ?></a></th>
      <th>พันธุ์</th>
      <th><a href="<?= sortUrl('Land_amphr') ?>">อำเภอ <?= sortIcon('Land_amphr') ?></a></th>
      <th>ตำบล</th>
      <th><a href="<?= sortUrl('LAND_PROMI') ?>">ประเภทสัญญา <?= sortIcon('LAND_PROMI') ?></a></th>
      <th><a href="<?= sortUrl('Survy_Code') ?>">นสส. <?= sortIcon('Survy_Code') ?></a></th>
      <th>แหล่งน้ำ</th>
      <th><a href="<?= sortUrl('DISTN_LONG') ?>">ระยะ <?= sortIcon('DISTN_LONG') ?></a></th>
      <th><a href="<?= sortUrl('Survy_date') ?>">วันสำรวจ <?= sortIcon('Survy_date') ?></a></th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
    <tr><td colspan="12" class="nr">🔍 ไม่พบแปลงที่ตรงกับเงื่อนไข</td></tr>
    <?php else: ?>
    <?php foreach ($rows as $i => $r): ?>
    <?php $n = $offset + $i + 1; ?>
    <tr onclick="showDetail(<?= htmlspecialchars(json_encode([
        'id'=>$r['Land_id'],'yr'=>$r['Prodt_year'],'fn'=>trim($r['Farmr_name']),
        'fc'=>$r['Farmr_code'],'pr'=>trim($r['LAND_PROMI']),'tb'=>trim($r['Land_tumbn']),
        'am'=>trim($r['Land_amphr']),'pv'=>trim($r['Land_provn']),'mu'=>$r['Land_moo'],
        'bn'=>trim($r['LAND_BAN']),'ar'=>$r['Land_qty1'],'ct'=>$r['CODETP02'],
        'ctn'=>$r['cane_type_name']??'','ws'=>$r['water_source']??'',
        'vr'=>$r['variety']??'-','dt'=>$r['DISTN_LONG'],'sc'=>$r['Survy_Code'],
        'lc'=>$r['line_CODE'],'sd'=>$r['Survy_date'],'cx'=>$r['Land_cor_x'],
        'cy'=>$r['Land_cor_y'],'cp'=>trim($r['CENT_POINT']),'tn'=>$r['CANE_TUNAV'],
        'tq'=>$r['CANE_QTYS'],'no'=>trim($r['LAND_NORTH']),'so'=>trim($r['LAND_SOUTH']),
        'ea'=>trim($r['LAND_EAST']),'we'=>trim($r['LAND_WEST'])
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)">
      <td class="lid"><span class="mu" style="margin-right:4px"><?= $n ?>.</span><?= e($r['Land_id']) ?></td>
      <td class="tr"><?= e(trim($r['Farmr_name'])) ?: '-' ?></td>
      <td style="text-align:right"><?= e($r['Land_qty1']) ?></td>
      <td><?= caneBadge($r['CODETP02'], $r['cane_type_name'] ?? '') ?></td>
      <td class="mu"><?= e($r['variety'] ?? '-') ?></td>
      <td><?= e(trim($r['Land_amphr'])) ?></td>
      <td class="mu"><?= e(trim($r['Land_tumbn'])) ?></td>
      <td class="mu tr"><?= e(trim($r['LAND_PROMI'])) ?></td>
      <td style="font-family:monospace;font-size:12px"><?= e($r['Survy_Code']) ?></td>
      <td class="mu"><?= e($r['water_source'] ?? '') ?></td>
      <td style="text-align:right" class="mu"><?= $r['DISTN_LONG'] > 0 ? $r['DISTN_LONG'].' กม.' : '-' ?></td>
      <td class="mu"><?= e($r['Survy_date']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
</div>

<!-- Pagination -->
<div class="pg">
  <?php
  $base = qurl(['pg'=>1]);
  $prev = qurl(['pg'=>max(1,$page-1)]);
  $next = qurl(['pg'=>min($total_pages,$page+1)]);
  echo $page<=1
    ? '<span class="dis">‹ ก่อนหน้า</span>'
    : '<a href="'.$prev.'">‹ ก่อนหน้า</a>';

  // Page numbers with ellipsis
  $range = [];
  if ($total_pages <= 9) {
      $range = range(1, $total_pages);
  } else {
      $range[] = 1;
      if ($page > 4) $range[] = '…';
      for ($p = max(2,$page-2); $p <= min($total_pages-1,$page+2); $p++) $range[] = $p;
      if ($page < $total_pages-3) $range[] = '…';
      $range[] = $total_pages;
  }
  foreach ($range as $p) {
      if ($p === '…') { echo '<span class="pginfo">…</span>'; continue; }
      $cls = $p == $page ? ' class="cur"' : '';
      $url = qurl(['pg'=>$p]);
      echo "<a href=\"$url\"$cls>$p</a>";
  }

  echo $page>=$total_pages
    ? '<span class="dis">ถัดไป ›</span>'
    : '<a href="'.$next.'">ถัดไป ›</a>';

  echo '<span class="pginfo">หน้า '.$page.' / '.$total_pages.'</span>';
  ?>
</div>

<!-- Detail Modal -->
<div class="mo" id="modal" onclick="if(event.target===this)closeModal()">
  <div class="mb">
    <div class="mh">
      <h3 id="mTitle">รายละเอียดแปลง</h3>
      <button class="mc" onclick="closeModal()">✕</button>
    </div>
    <div class="mbody" id="mBody"></div>
  </div>
</div>

<script>
function showDetail(r) {
  document.getElementById('mTitle').textContent = 'รายละเอียดแปลง ' + r.id;
  let dir = '';
  if (r.no || r.so || r.ea || r.we) {
    dir = `<div class="stitle">ทิศทางแปลง</div><div class="dg">
      ${r.no ? `<div class="di"><small>ทิศเหนือ</small><b>${r.no}</b></div>` : ''}
      ${r.so ? `<div class="di"><small>ทิศใต้</small><b>${r.so}</b></div>` : ''}
      ${r.ea ? `<div class="di"><small>ทิศตะวันออก</small><b>${r.ea}</b></div>` : ''}
      ${r.we ? `<div class="di"><small>ทิศตะวันตก</small><b>${r.we}</b></div>` : ''}
    </div>`;
  }
  document.getElementById('mBody').innerHTML = `
    <div class="stitle">ข้อมูลพื้นฐาน</div>
    <div class="dg">
      <div class="di"><small>รหัสแปลง</small><b style="font-family:monospace;font-size:18px;color:var(--gd)">${r.id}</b></div>
      <div class="di"><small>ปีการผลิต</small><b>${r.yr||'-'}</b></div>
      <div class="di"><small>ชาวไร่</small><b>${r.fn||'-'}</b></div>
      <div class="di"><small>เลขสัญญา</small><b style="font-family:monospace">${r.fc||'-'}</b></div>
      <div class="di full"><small>ประเภทสัญญา</small><b>${r.pr||'-'}</b></div>
    </div>
    <div class="stitle">ที่ตั้งแปลง</div>
    <div class="dg">
      ${r.mu ? `<div class="di"><small>หมู่ที่</small><b>${r.mu}</b></div>` : ''}
      ${r.bn ? `<div class="di"><small>หมู่บ้าน</small><b>${r.bn}</b></div>` : ''}
      <div class="di"><small>ตำบล</small><b>${r.tb||'-'}</b></div>
      <div class="di"><small>อำเภอ</small><b>${r.am||'-'}</b></div>
      <div class="di"><small>จังหวัด</small><b>${r.pv||'-'}</b></div>
      ${r.cp ? `<div class="di full"><small>จุดสังเกต</small><b>${r.cp}</b></div>` : ''}
      ${(r.cx&&r.cx!='0') ? `<div class="di"><small>พิกัด X</small><b style="font-family:monospace">${r.cx}</b></div>` : ''}
      ${(r.cy&&r.cy!='0') ? `<div class="di"><small>พิกัด Y</small><b style="font-family:monospace">${r.cy}</b></div>` : ''}
    </div>
    ${dir}
    <div class="stitle">ข้อมูลอ้อย</div>
    <div class="dg">
      <div class="di"><small>พื้นที่</small><b style="font-size:22px;color:var(--gd)">${r.ar||'-'} <span style="font-size:13px;font-weight:400">ไร่</span></b></div>
      <div class="di"><small>ชนิดอ้อย</small><b>${r.ctn||'-'}</b></div>
      <div class="di"><small>พันธุ์อ้อย</small><b>${r.vr&&r.vr!=='-'?r.vr:'-'}</b></div>
      <div class="di"><small>แหล่งน้ำ</small><b>${r.ws||'-'}</b></div>
      ${r.dt>0 ? `<div class="di"><small>ระยะทาง</small><b>${r.dt} กม.</b></div>` : ''}
      ${r.tn>0 ? `<div class="di"><small>ตันเฉลี่ย</small><b>${r.tn} ตัน/ไร่</b></div>` : ''}
      ${r.tq>0 ? `<div class="di"><small>ตันรวม</small><b>${r.tq} ตัน</b></div>` : ''}
    </div>
    <div class="stitle">ข้อมูลระบบ</div>
    <div class="dg">
      <div class="di"><small>รหัส นสส.</small><b style="font-family:monospace">${r.sc||'-'}</b></div>
      <div class="di"><small>สาย</small><b style="font-family:monospace">${r.lc||'-'}</b></div>
      <div class="di"><small>วันที่สำรวจ</small><b>${r.sd||'-'}</b></div>
    </div>
    <div class="mfoot">
      <a class="btn btn-green" href="/ktis-st-viewer/activity.php?land_id=${r.id}">📝 บันทึกกิจกรรม</a>
      <button class="btn btn-gray" onclick="closeModal()">ปิด</button>
    </div>
  `;
  document.getElementById('modal').classList.add('open');
}
function closeModal() { document.getElementById('modal').classList.remove('open'); }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });

function changePP(v) {
  const url = new URL(location.href);
  url.searchParams.set('pp', v);
  url.searchParams.set('pg', 1);
  location.href = url.toString();
}
</script>
</body>
</html>
