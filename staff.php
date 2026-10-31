<?php require 'config.php';
need();
require __DIR__ . '/toko.php';
$u = user();
$tk = $db->query('SELECT id,nama,lat,lng,radius FROM toko WHERE aktif=1 AND lat IS NOT NULL AND lng IS NOT NULL')->fetchAll();
function simpanFoto($b64)
{
  if (!$b64) return null;
  $raw = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $b64), true);
  if ($raw === false || strlen($raw) > 4 * 1048576) throw new Exception('Foto gagal diunggah (maks 4MB).');
  $i = @getimagesizefromstring($raw);
  $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$i[2] ?? 0] ?? null;
  if (!$ext) throw new Exception('Format foto harus JPG/PNG/WEBP.');
  $n = bin2hex(random_bytes(8)) . '.' . $ext;
  file_put_contents(__DIR__ . '/uploads/' . $n, $raw);
  return $n;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  try {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) throw new Exception('Sesi tidak valid, muat ulang halaman.');
    $lat = filter_var($_POST['lat'] ?? '', FILTER_VALIDATE_FLOAT);
    $lng = filter_var($_POST['lng'] ?? '', FILTER_VALIDATE_FLOAT);
    if ($lat === false || $lng === false) throw new Exception('Lokasi GPS belum terbaca.');
    $t = toko_terdekat($db, $lat, $lng);
    if (!$t) throw new Exception('Belum ada toko yang diaktifkan. Hubungi admin.');
    $j = (int)round($t['d']);
    if ($j > $t['radius']) throw new Exception("Anda di luar area toko. Toko terdekat: {$t['nama']} ($j m, maksimal {$t['radius']} m).");
    $tgl = date('Y-m-d');
    $now = date('H:i:s');
    $q = $db->prepare('SELECT * FROM absensi WHERE user_id=? AND tanggal=?');
    $q->execute([$u['id'], $tgl]);
    $a = $q->fetch();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'masuk') {
      if ($a) throw new Exception('Anda sudah absen masuk hari ini.');
      $f = simpanFoto($_POST['foto'] ?? '');
      $st = 'hadir';
      $db->prepare('INSERT INTO absensi(user_id,tanggal,jam_masuk,lat_masuk,lng_masuk,jarak_masuk,foto_masuk,status,toko_masuk) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$u['id'], $tgl, $now, $lat, $lng, $j, $f, $st, $t['id']]);
      $m = "Absen masuk berhasil di {$t['nama']} pukul " . substr($now, 0, 5) . '.';
    } elseif ($aksi === 'pulang') {
      $ada = (bool)$a;
      $q2 = $db->prepare('SELECT * FROM absensi WHERE user_id=? AND jam_pulang IS NULL AND tanggal>=? ORDER BY tanggal DESC LIMIT 1');
      $q2->execute([$u['id'], date('Y-m-d', strtotime('-1 day'))]);
      $a = $q2->fetch();
      if (!$a) throw new Exception($ada ? 'Anda sudah absen pulang.' : 'Anda belum absen masuk.');
      if (time() - strtotime($a['tanggal'] . ' ' . $a['jam_masuk']) > 86400) throw new Exception('Absen masuk sudah lebih dari 24 jam. Hubungi admin.');
      $f = simpanFoto($_POST['foto'] ?? '');
      $db->prepare('UPDATE absensi SET jam_pulang=?,lat_pulang=?,lng_pulang=?,jarak_pulang=?,foto_pulang=?,toko_pulang=? WHERE id=?')->execute([$now, $lat, $lng, $j, $f, $t['id'], $a['id']]);
      $m = "Absen pulang berhasil di {$t['nama']} pukul " . substr($now, 0, 5) . '.';
    } else throw new Exception('Aksi tidak dikenal.');
    echo json_encode(['ok' => 1, 'msg' => $m]);
  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => 0, 'msg' => $e->getMessage()]);
  }
  exit;
}
$q = $db->prepare('SELECT * FROM absensi WHERE user_id=? AND tanggal=?');
$q->execute([$u['id'], date('Y-m-d')]);
$a = $q->fetch();
$q = $db->prepare('SELECT * FROM absensi WHERE user_id=? AND jam_pulang IS NULL AND tanggal>=? ORDER BY tanggal DESC LIMIT 1');
$q->execute([$u['id'], date('Y-m-d', strtotime('-1 day'))]);
$o = $q->fetch();
$h = $db->prepare('SELECT * FROM absensi WHERE user_id=? ORDER BY tanggal DESC LIMIT 10');
$h->execute([$u['id']]);
head('Absen Saya'); ?>
<div class="max-w-xl mx-auto space-y-4">
  <div class="<?= CARD ?> text-center">
    <p class="text-sm text-stone-500">Absen otomatis di toko terdekat</p><span class="inline-block mt-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-medium"><?= count($tk) ?> toko aktif</span>
    <p id="jam" class="text-4xl font-bold text-yellow-800 my-1">--:--:--</p>
    <p class="text-sm"><?= date('d-m-Y') ?></p>
    <div id="gps" class="mt-3 text-sm rounded-lg bg-yellow-100 p-2">Membaca lokasi GPS…</div>
  </div>
  <div class="<?= CARD ?> space-y-3">
    <p class="text-sm font-medium">Status hari ini</p>
    <?php $r0 = $o ?: $a;
    $cp = 'inline-block px-2.5 py-1 rounded-full text-xs font-medium '; ?>
    <div class="flex flex-wrap gap-1.5">
      <?php if (!$r0): ?><span class="<?= $cp ?>bg-stone-100 text-stone-500">Belum absen</span>
      <?php else: ?><span class="<?= $cp ?>bg-green-100 text-green-800">Masuk <?= substr($r0['jam_masuk'], 0, 5) ?></span>
        <?php if ($r0['jam_pulang']): ?><span class="<?= $cp ?>bg-amber-100 text-amber-900">Pulang <?= substr($r0['jam_pulang'], 0, 5) ?></span><span class="<?= $cp ?>bg-yellow-200 text-yellow-900">Total <?= hm(detik($r0['jam_masuk'], $r0['jam_pulang'])) ?></span>
        <?php else: ?><span class="<?= $cp ?>bg-red-100 text-red-700">Belum pulang</span><?php endif; ?>
      <?php endif; ?></div>
    <label class="block text-sm">Foto selfie <span class="text-stone-400">(opsional)</span>
      <input id="foto" type="file" accept="image/*" capture="user" class="<?= INP ?> mt-1 text-sm"></label>
    <div class="grid grid-cols-2 gap-3">
      <button onclick="absen('masuk')" class="<?= BTN ?> disabled:opacity-40" <?= $a ? 'disabled' : '' ?>>Absen Masuk</button>
      <button onclick="absen('pulang')" class="<?= BTN ?> disabled:opacity-40" <?= !$o ? 'disabled' : '' ?>>Absen Pulang</button>
    </div>
    <div id="msg" class="hidden text-sm p-2 rounded-lg"></div>
  </div>
  <div class="<?= CARD ?>">
    <h2 class="font-semibold mb-2">Riwayat 10 hari terakhir</h2>
    <table class="w-full text-sm">
      <tr class="text-left text-stone-500">
        <th>Tanggal</th>
        <th>Masuk</th>
        <th>Pulang</th>
        <th>Total</th>
      </tr>
      <?php foreach ($h as $r): ?><tr class="border-t border-yellow-100">
          <td class="py-1"><?= date('d-m-Y', strtotime($r['tanggal'])) ?></td>
          <td><?= substr($r['jam_masuk'], 0, 5) ?></td>
          <td><?= $r['jam_pulang'] ? substr($r['jam_pulang'], 0, 5) : '-' ?></td>
          <td><?= hm(detik($r['jam_masuk'], $r['jam_pulang'])) ?></td>
        </tr><?php endforeach; ?>
    </table>
  </div>
</div>
<script>
  const TK = <?= json_encode(array_map(fn($t) => ['n' => $t['nama'], 'lat' => (float)$t['lat'], 'lng' => (float)$t['lng'], 'r' => (int)$t['radius']], $tk)) ?>;
  const hav = (a, b, c, d) => {
    const p = Math.PI / 180,
      x = Math.sin((c - a) * p / 2) ** 2 + Math.cos(a * p) * Math.cos(c * p) * Math.sin((d - b) * p / 2) ** 2;
    return 2 * 6371000 * Math.asin(Math.sqrt(x))
  };
  const S0 = <?= time() * 1000 ?>,
    P0 = performance.now(); // jam mengikuti server, bukan jam HP
  setInterval(() => jam.textContent = new Date(S0 + performance.now() - P0).toLocaleTimeString('en-GB', {
    timeZone: 'Asia/Pontianak',
    hour12: false
  }), 1000);
  const gpsErr = () => gps.textContent = 'GPS tidak aktif / izin lokasi ditolak. Aktifkan lokasi lalu muat ulang.';
  const near = (la, ln) => TK.map(t => ({
    ...t,
    d: Math.round(hav(la, ln, t.lat, t.lng))
  })).sort((a, b) => a.d - b.d)[0];
  if (navigator.geolocation && TK.length) navigator.geolocation.watchPosition(p => {
    const n = near(p.coords.latitude, p.coords.longitude),
      ok = n.d <= n.r;
    gps.className = 'mt-3 text-sm rounded-lg p-2 ' + (ok ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700');
    const E = x => String(x).replace(/[&<>"]/g, c => '&#' + c.charCodeAt(0) + ';');
    gps.innerHTML = '<div class="font-semibold">' + (ok ? '✅ Dalam area ' + E(n.n) : '❌ Di luar area toko') + '</div><div>' + (ok ? '' : 'Terdekat: ' + E(n.n) + ', ') + n.d + ' m dari titik toko</div><div class="text-xs opacity-70">Akurasi GPS ±' + Math.round(p.coords.accuracy) + ' m</div>';
  }, gpsErr, {
    enableHighAccuracy: true
  });
  else gps.textContent = TK.length ? 'GPS tidak tersedia di perangkat ini.' : 'Belum ada toko aktif. Hubungi admin.';

  function show(t, ok) {
    msg.className = 'text-sm p-2 rounded-lg ' + (ok ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700');
    msg.textContent = t
  }

  function kompres(f, max = 800, q = .7) {
    return new Promise(res => {
      const img = new Image(),
        u = URL.createObjectURL(f);
      img.onload = () => {
        const k = Math.min(1, max / Math.max(img.width, img.height)),
          c = document.createElement('canvas');
        c.width = Math.round(img.width * k);
        c.height = Math.round(img.height * k);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(u);
        c.toBlob(b => res(b || f), 'image/jpeg', q)
      };
      img.onerror = () => res(f);
      img.src = u
    })
  }

  const b64 = b => new Promise(r => {
    const f = new FileReader();
    f.onload = () => r(f.result);
    f.readAsDataURL(b)
  });

  function absen(aksi) {
    show('Memproses…', true);
    navigator.geolocation.getCurrentPosition(async p => {
      const fd = new URLSearchParams({
        aksi: aksi,
        csrf: '<?= $_SESSION['csrf'] ?>',
        lat: p.coords.latitude,
        lng: p.coords.longitude
      });
      if (foto.files[0]) {
        show('Mengompres foto…', true);
        fd.append('foto', await b64(await kompres(foto.files[0])));
      }
      try {
        const r = await fetch('staff.php', {
            method: 'POST',
            body: fd
          }),
          tx = await r.text();
        let d;
        try {
          d = JSON.parse(tx)
        } catch (_) {
          throw new Error('Server membalas ' + r.status)
        }
        show(d.msg, d.ok);
        if (d.ok) setTimeout(() => location.reload(), 1200)
      } catch (e) {
        show(e.message || 'Gagal menghubungi server.', false)
      }
    }, () => show('Gagal membaca GPS. Izinkan akses lokasi.', false), {
      enableHighAccuracy: true,
      timeout: 15000
    });
  }
</script><?php foot();
