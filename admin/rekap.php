<?php require '../config.php';
need('admin');
if (!class_exists('ZipArchive')) die('Ekstensi PHP zip belum aktif.');
$b = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $b)) $b = date('Y-m');
$t0 = $b . '-01';
$n = (int)date('t', strtotime($t0));
$t1 = $b . '-' . $n;
$q = $db->prepare("SELECT id,nama FROM users WHERE aktif=1 AND (role='staff' OR id IN (SELECT user_id FROM absensi WHERE tanggal BETWEEN ? AND ?)) ORDER BY nama");
$q->execute([$t0, $t1]);
$us = $q->fetchAll();
if (!$us) die('Belum ada pegawai.');
$q = $db->prepare('SELECT user_id,tanggal,jam_masuk,jam_pulang FROM absensi WHERE tanggal BETWEEN ? AND ?');
$q->execute([$t0, $t1]);
function sd($t)
{
    [$h, $i, $z] = array_map('intval', explode(':', $t));
    return $h * 3600 + $i * 60 + $z;
}
$m = [];
foreach ($q as $r) {
    $m[$r['user_id']][(int)substr($r['tanggal'], 8, 2)] = $r;
}
function col($i)
{
    $s = '';
    for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s;
    return $s;
}
function tx($r, $s, $v)
{
    return '<c r="' . $r . '" s="' . $s . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
}
$bln = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$N = count($us);
$last = col(3 * $N);
$rows = '<row r="1">' . tx('A1', 1, 'REKAP JUMLAH JAM KERJA') . '</row><row r="2">' . tx('A2', 0, 'Periode: ' . $bln[(int)substr($b, 5, 2) - 1] . ' ' . substr($b, 0, 4)) . '</row>';
$h4 = tx('A4', 2, 'Tanggal');
$h5 = '<c r="A5" s="2"/>';
$mg = '<mergeCell ref="A1:' . $last . '1"/><mergeCell ref="A4:A5"/>';
foreach ($us as $i => $x) {
    $ca = col(1 + 3 * $i);
    $cb = col(2 + 3 * $i);
    $cc = col(3 + 3 * $i);
    $h4 .= tx($ca . '4', 2, $x['nama']) . '<c r="' . $cb . '4" s="2"/><c r="' . $cc . '4" s="2"/>';
    $h5 .= tx($ca . '5', 2, 'Masuk') . tx($cb . '5', 2, 'Keluar') . tx($cc . '5', 2, 'Total');
    $mg .= '<mergeCell ref="' . $ca . '4:' . $cc . '4"/>';
}
$rows .= '<row r="4">' . $h4 . '</row><row r="5">' . $h5 . '</row>';
$tot = [];
for ($d = 1; $d <= $n; $d++) {
    $r = $d + 5;
    $c = '<c r="A' . $r . '" s="4"><v>' . $d . '</v></c>';
    foreach ($us as $i => $x) {
        $w = $m[$x['id']][$d] ?? null;
        $L3 = [col(1 + 3 * $i), col(2 + 3 * $i), col(3 + 3 * $i)];
        $v = ['', '', ''];
        if ($w) {
            $v[0] = sd($w['jam_masuk']) / 86400;
            if ($w['jam_pulang']) {
                $v[1] = sd($w['jam_pulang']) / 86400;
                $t = detik($w['jam_masuk'], $w['jam_pulang']);
                $v[2] = $t / 86400;
                $tot[$i] = ($tot[$i] ?? 0) + $t;
            }
        }
        foreach ($L3 as $k => $L) $c .= $v[$k] === '' ? '<c r="' . $L . $r . '" s="3"/>' : '<c r="' . $L . $r . '" s="3"><v>' . $v[$k] . '</v></c>';
    }
    $rows .= '<row r="' . $r . '">' . $c . '</row>';
}
$tr = $n + 6;
$c = tx('A' . $tr, 2, 'Total');
foreach ($us as $i => $x) {
    $ca = col(1 + 3 * $i);
    $cb = col(2 + 3 * $i);
    $L = col(3 + 3 * $i);
    $c .= '<c r="' . $ca . $tr . '" s="2"/><c r="' . $cb . $tr . '" s="2"/><c r="' . $L . $tr . '" s="5"><f>SUM(' . $L . '6:' . $L . ($tr - 1) . ')</f><v>' . (($tot[$i] ?? 0) / 86400) . '</v></c>';
}
$rows .= '<row r="' . $tr . '">' . $c . '</row>';
$X = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
$NS = 'xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
$sheet = $X . '<worksheet ' . $NS . '><sheetViews><sheetView workbookViewId="0"><pane xSplit="1" ySplit="5" topLeftCell="B6" activePane="bottomRight" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="1" width="12" customWidth="1"/><col min="2" max="' . (3 * $N + 1) . '" width="9.5" customWidth="1"/></cols><sheetData>' . $rows . '</sheetData><mergeCells count="' . ($N + 2) . '">' . $mg . '</mergeCells></worksheet>';
$bd = '<border><left style="thin"><color rgb="FFD9C98A"/></left><right style="thin"><color rgb="FFD9C98A"/></right><top style="thin"><color rgb="FFD9C98A"/></top><bottom style="thin"><color rgb="FFD9C98A"/></bottom><diagonal/></border>';
$ctr = '<alignment horizontal="center" vertical="center"/>';
$styles = $X . '<styleSheet ' . $NS . '><numFmts count="2"><numFmt numFmtId="164" formatCode="hh:mm"/><numFmt numFmtId="165" formatCode="[h]:mm"/></numFmts><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="14"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFDE9A9"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>' . $bd . '</borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="6"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">' . $ctr . '</xf><xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1">' . $ctr . '</xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">' . $ctr . '</xf><xf numFmtId="165" fontId="2" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">' . $ctr . '</xf></cellXfs></styleSheet>';
$R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
$f = tempnam(sys_get_temp_dir(), 'rk');
$z = new ZipArchive;
$z->open($f, ZipArchive::OVERWRITE);
$z->addFromString('[Content_Types].xml', $X . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
$z->addFromString('_rels/.rels', $X . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="' . $R . '/officeDocument" Target="xl/workbook.xml"/></Relationships>');
$z->addFromString('xl/workbook.xml', $X . '<workbook ' . $NS . ' xmlns:r="' . $R . '"><sheets><sheet name="Rekap" sheetId="1" r:id="rId1"/></sheets><calcPr fullCalcOnLoad="1"/></workbook>');
$z->addFromString('xl/_rels/workbook.xml.rels', $X . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="' . $R . '/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="' . $R . '/styles" Target="styles.xml"/></Relationships>');
$z->addFromString('xl/styles.xml', $styles);
$z->addFromString('xl/worksheets/sheet1.xml', $sheet);
$z->close();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="rekap-jam-kerja-' . $b . '.xlsx"');
header('Content-Length: ' . filesize($f));
readfile($f);
unlink($f);
