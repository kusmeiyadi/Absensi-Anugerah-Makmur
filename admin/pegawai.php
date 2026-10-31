<?php require '../config.php';
need('admin');
require __DIR__ . '/../toko.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cek_csrf();
  try {
    if (isset($_POST['hapus'])) {
      if ((int)$_POST['hapus'] === user()['id']) throw new Exception('Tidak bisa menghapus akun sendiri.');
      $db->prepare('DELETE FROM users WHERE id=?')->execute([$_POST['hapus']]);
      $msg = 'Pegawai dihapus.';
    } else {
      $id = (int)$_POST['id'];
      $n = trim($_POST['nama']);
      $un = trim($_POST['username']);
      $role = $_POST['role'] === 'admin' ? 'admin' : 'staff';
      $ak = isset($_POST['aktif']) ? 1 : 0;
      $pw = $_POST['password'];
      $gj = (int)preg_replace('/\D/', '', $_POST['gaji_per_jam'] ?? '');
      $um = (int)preg_replace('/\D/', '', $_POST['uang_makan'] ?? '');
      if ($n === '' || $un === '') throw new Exception('Nama dan username wajib diisi.');
      if ($id) {
        $db->prepare('UPDATE users SET nama=?,username=?,role=?,aktif=?,gaji_per_jam=?,uang_makan=? WHERE id=?')->execute([$n, $un, $role, $ak, $gj, $um, $id]);
        if ($pw !== '') $db->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
      } else {
        if (strlen($pw) < 6) throw new Exception('Password minimal 6 karakter.');
        $db->prepare('INSERT INTO users(nama,username,password,role,aktif,gaji_per_jam,uang_makan) VALUES(?,?,?,?,?,?,?)')->execute([$n, $un, password_hash($pw, PASSWORD_DEFAULT), $role, $ak, $gj, $um]);
      }
      $msg = 'Data tersimpan.';
    }
  } catch (PDOException $x) {
    $msg = 'Username sudah dipakai.';
  } catch (Exception $x) {
    $msg = $x->getMessage();
  }
}
$ed = ['id' => 0, 'nama' => '', 'username' => '', 'role' => 'staff', 'aktif' => 1, 'gaji_per_jam' => 0, 'uang_makan' => 0];
if (isset($_GET['edit'])) {
  $q = $db->prepare('SELECT * FROM users WHERE id=?');
  $q->execute([$_GET['edit']]);
  $ed = $q->fetch() ?: $ed;
}
$list = $db->query('SELECT * FROM users ORDER BY role,nama')->fetchAll();
head('Pegawai'); ?>
<h1 class="text-xl font-bold text-yellow-900 mb-3">Data Pegawai</h1>
<?php if ($msg): ?><div class="mb-3 p-2 rounded-lg bg-yellow-100 text-sm"><?= e($msg) ?></div><?php endif; ?>
<div class="grid md:grid-cols-3 gap-4">
  <form method="post" class="<?= CARD ?> space-y-2 h-fit"><?= csrf() ?><input type="hidden" name="id" value="<?= $ed['id'] ?>">
    <h2 class="font-semibold"><?= $ed['id'] ? 'Edit' : 'Tambah' ?> pegawai</h2>
    <input name="nama" value="<?= e($ed['nama']) ?>" placeholder="Nama lengkap" class="<?= INP ?>" required>
    <input name="username" value="<?= e($ed['username']) ?>" placeholder="Username" class="<?= INP ?>" required>
    <input name="password" type="password" placeholder="<?= $ed['id'] ? 'Password baru (kosongkan jika tidak diubah)' : 'Password' ?>" class="<?= INP ?>">
    <select name="role" class="<?= INP ?>">
      <option value="staff">Staf</option>
      <option value="admin" <?= $ed['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>
    <label class="block text-sm">Gaji pokok per jam (Rp)<div class="relative mt-0"><span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-stone-400">Rp</span><input name="gaji_per_jam" type="text" inputmode="numeric" autocomplete="off" placeholder="0" value="<?= $ed['gaji_per_jam'] ? number_format((int)$ed['gaji_per_jam'], 0, ',', '.') : '' ?>" class="rp <?= INP ?> !pl-10 text-right"></div></label>
    <label class="block text-sm">Uang makan per hari (Rp)<div class="relative mt-0"><span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-stone-400">Rp</span><input name="uang_makan" type="text" inputmode="numeric" autocomplete="off" placeholder="0" value="<?= $ed['uang_makan'] ? number_format((int)$ed['uang_makan'], 0, ',', '.') : '' ?>" class="rp <?= INP ?> !pl-10 text-right"></div></label>
    <label class="text-sm flex gap-2"><input type="checkbox" name="aktif" <?= $ed['aktif'] ? 'checked' : '' ?>> Aktif</label>
    <button class="<?= BTN ?> w-full">Simpan</button>
  </form>
  <div class="<?= CARD ?> md:col-span-2 overflow-x-auto">
    <table class="w-full text-sm">
      <tr class="text-left text-stone-500">
        <th>Nama</th>
        <th>Username</th>
        <th>Role</th>
        <th>Gaji/jam</th>
        <th>Uang makan/hari</th>
        <th>Status</th>
        <th></th>
      </tr>
      <?php foreach ($list as $r): ?><tr class="border-t border-yellow-100">
          <td class="py-1"><?= e($r['nama']) ?></td>
          <td><?= e($r['username']) ?></td>
          <td><?= $r['role'] ?></td>
          <td><?= $r['gaji_per_jam'] ? rp($r['gaji_per_jam']) : '-' ?></td>
          <td><?= $r['uang_makan'] ? rp($r['uang_makan']) : '-' ?></td>
          <td><?= $r['aktif'] ? 'Aktif' : 'Nonaktif' ?></td>
          <td class="text-right whitespace-nowrap"><a class="text-yellow-700 underline" href="?edit=<?= $r['id'] ?>">Edit</a>
            <form method="post" class="inline" onsubmit="return confirm('Hapus pegawai ini beserta absensinya?')"><?= csrf() ?><button name="hapus" value="<?= $r['id'] ?>" class="text-red-600 underline ml-2">Hapus</button></form>
          </td>
        </tr><?php endforeach; ?>
    </table>
  </div>
</div>
<script>
  document.querySelectorAll('.rp').forEach(i => {
    const f = () => {
      const p = i.selectionStart,
        n = i.value.slice(0, p).replace(/\D/g, '').length;
      i.value = i.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      if (document.activeElement === i) {
        let c = 0,
          k = 0;
        while (k < i.value.length && c < n) {
          if (/\d/.test(i.value[k])) c++;
          k++
        }
        i.setSelectionRange(k, k)
      }
    };
    i.addEventListener('input', f);
    f();
  });
</script>
<?php foot();
