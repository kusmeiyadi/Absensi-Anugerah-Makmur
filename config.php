<?php
session_start();
date_default_timezone_set('Asia/Pontianak');
const BASE = '/absensi'; // sesuaikan dengan nama folder di htdocs
const INP = 'w-full rounded-xl border border-amber-200/80 bg-amber-50/40 px-3.5 py-2.5 text-sm placeholder-stone-400 focus:outline-none focus:ring-4 focus:ring-amber-200/60 focus:border-amber-300 transition';
const BTN = 'rounded-xl bg-amber-300 hover:bg-amber-400 active:scale-[.98] text-amber-950 font-semibold px-4 py-2.5 shadow-sm shadow-amber-200 transition';
const CARD = 'bg-white/90 rounded-3xl border border-amber-100 shadow-[0_6px_24px_-10px_rgba(217,162,50,.35)] p-5';
try {
  $db = new PDO('mysql:host=127.0.0.1;dbname=absensi;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
  die('Koneksi database gagal: ' . $e->getMessage());
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function e($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function user()
{
  return $_SESSION['user'] ?? null;
}
function need($role = null)
{
  if (!user()) {
    header('Location: ' . BASE . '/index.php');
    exit;
  }
  if ($role && user()['role'] !== $role) {
    header('Location: ' . BASE . '/staff.php');
    exit;
  }
}
function csrf()
{
  return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function cek_csrf()
{
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die('Sesi tidak valid, muat ulang halaman.');
}
function jarak($a, $b, $c, $d)
{
  $r = 6371000;
  $p = M_PI / 180;
  $x = sin(($c - $a) * $p / 2) ** 2 + cos($a * $p) * cos($c * $p) * sin(($d - $b) * $p / 2) ** 2;
  return 2 * $r * asin(sqrt($x));
}
function head($t)
{
  $u = user();
  $nav = [];
  if ($u) {
    $nav = ['staff.php' => ['Absen', '✅']];
    if ($u['role'] === 'admin') $nav = ['admin/index.php' => ['Beranda', '🏠'], 'admin/pegawai.php' => ['Pegawai', '👥'], 'admin/lokasi.php' => ['Lokasi', '📍'], 'admin/laporan.php' => ['Laporan', '📊']] + $nav;
  }
  $cur = $_SERVER['SCRIPT_NAME'];
  echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#fffaf0"><link rel="icon" type="image/png" sizes="32x32" href="' . BASE . '/assets/favicon-32.png"><link rel="shortcut icon" href="' . BASE . '/favicon.ico"><link rel="apple-touch-icon" href="' . BASE . '/assets/apple-touch-icon.png"><title>' . e($t) . ' - Absensi</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;background:linear-gradient(180deg,#fffbeb 0%,#fff7e6 100%) fixed}
table{border-collapse:collapse}th,td{padding:.55rem .5rem;white-space:nowrap}th{font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em}tr+tr:hover td{background:#fffbeb}</style>
</head><body class="text-stone-700 min-h-screen">';
  if ($u) {
    echo '<header class="sticky top-0 z-30 backdrop-blur bg-amber-50/80 border-b border-amber-100"><div class="max-w-5xl mx-auto px-4 py-3 flex items-center gap-3">
   <span class="flex items-center gap-2 font-bold text-amber-900"><img src="' . BASE . '/assets/logo.png" alt="Logo" class="h-9 w-auto"><span class="hidden sm:block leading-tight">Anugerah Makmur<br><span class="text-[11px] font-medium text-stone-500">Absensi Pegawai</span></span></span>
   <nav class="hidden md:flex gap-1 text-sm flex-1 ml-4">';
    foreach ($nav as $h => [$l, $i]) {
      $on = str_ends_with($cur, '/' . $h);
      echo '<a href="' . BASE . '/' . $h . '" class="px-3.5 py-2 rounded-xl font-medium transition ' . ($on ? 'bg-amber-200/80 text-amber-950' : 'hover:bg-amber-100') . '">' . $l . '</a>';
    }
    echo '</nav><span class="flex-1 md:flex-none"></span>
   <span class="text-sm text-right leading-tight"><b class="font-semibold">' . e($u['nama']) . '</b><br><span class="text-xs text-stone-400">' . ($u['role'] === 'admin' ? 'Admin' : 'Staf') . '</span></span>
   <a href="' . BASE . '/logout.php" class="text-sm px-3 py-2 rounded-xl bg-white border border-amber-200 hover:bg-amber-100 transition">Keluar</a></div></header>';
  }
  echo '<main class="max-w-5xl mx-auto p-4 md:p-6 pb-28 md:pb-10">';
  if ($u) {
    echo '<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-amber-100 shadow-[0_-6px_24px_-10px_rgba(217,162,50,.4)]" style="padding-bottom:env(safe-area-inset-bottom)"><div class="grid" style="grid-template-columns:repeat(' . count($nav) . ',1fr)">';
    foreach ($nav as $h => [$l, $i]) {
      $on = str_ends_with($cur, '/' . $h);
      echo '<a href="' . BASE . '/' . $h . '" class="flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium ' . ($on ? 'text-amber-900' : 'text-stone-400') . '"><span class="text-xl px-4 py-0.5 rounded-full transition ' . ($on ? 'bg-amber-200' : '') . '">' . $i . '</span>' . $l . '</a>';
    }
    echo '</div></nav>';
  }
}
function foot()
{
  echo '</main></body></html>';
}
function detik($m, $p)
{
  if (!$m || !$p) return null;
  $d = strtotime("1970-01-01 $p") - strtotime("1970-01-01 $m");
  return $d < 0 ? $d + 86400 : $d;
}
function hm($s)
{
  return $s === null ? '-' : sprintf('%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60));
}
