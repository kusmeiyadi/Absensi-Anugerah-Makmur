<?php require '../config.php'; need('admin'); require __DIR__.'/_migrasi.php';
$id=(int)($_GET['id']??0);$baru=isset($_GET['baru']);$err='';
$row=['user_id'=>0,'tanggal'=>date('Y-m-d'),'jam_masuk'=>'','jam_pulang'=>'','catatan'=>'','nama'=>''];
if($id){$q=$db->prepare('SELECT a.*,u.nama FROM absensi a JOIN users u ON u.id=a.user_id WHERE a.id=?');$q->execute([$id]);$row=$q->fetch();if(!$row){header('Location: laporan.php');exit;}}
elseif(!$baru){header('Location: laporan.php');exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){cek_csrf();
 try{
  if(isset($_POST['hapus'])&&$id){$db->prepare('DELETE FROM absensi WHERE id=?')->execute([$id]);header('Location: laporan.php?ok=Data+absen+dihapus');exit;}
  $jm=$_POST['jam_masuk']??'';$jp=$_POST['jam_pulang']??'';$cat=trim($_POST['catatan']??'');
  $okT=fn($t)=>preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$t);
  if(!$okT($jm))throw new Exception('Jam masuk wajib diisi.');
  if($jp!==''&&!$okT($jp))throw new Exception('Format jam pulang salah.');
  if($cat==='')throw new Exception('Catatan alasan wajib diisi.');
  $jm.=':00';$jp=$jp===''?null:$jp.':00';
  if($id)$db->prepare('UPDATE absensi SET jam_masuk=?,jam_pulang=?,dikoreksi=1,catatan=? WHERE id=?')->execute([$jm,$jp,$cat,$id]);
  else{$uid=(int)($_POST['user_id']??0);$tgl=$_POST['tanggal']??'';$d=DateTime::createFromFormat('Y-m-d',$tgl);
   if(!$uid||!$d||$d->format('Y-m-d')!==$tgl)throw new Exception('Pegawai dan tanggal wajib diisi dengan benar.');
   $db->prepare("INSERT INTO absensi(user_id,tanggal,jam_masuk,jam_pulang,status,dikoreksi,catatan) VALUES(?,?,?,?,'hadir',1,?)")->execute([$uid,$tgl,$jm,$jp,$cat]);}
  header('Location: laporan.php?ok=Data+tersimpan');exit;
 }catch(PDOException $x){$err=$x->getCode()=='23000'?'Pegawai sudah punya absen di tanggal itu. Gunakan tombol Koreksi pada baris yang sudah ada.':'Gagal menyimpan data.';}
 catch(Exception $x){$err=$x->getMessage();}
 foreach(['jam_masuk','jam_pulang','catatan'] as $k)$row[$k]=$_POST[$k]??'';
 if($baru){$row['user_id']=(int)($_POST['user_id']??0);$row['tanggal']=$_POST['tanggal']??$row['tanggal'];}
}
$us=$baru?$db->query('SELECT id,nama FROM users WHERE aktif=1 ORDER BY nama')->fetchAll():[];
head($baru?'Tambah Absen Manual':'Koreksi Absen');?>
<div class="max-w-lg mx-auto">
<h1 class="text-xl font-bold text-yellow-900 mb-3"><?=$baru?'Tambah Absen Manual':'Koreksi Absen'?></h1>
<?php if($err):?><div class="mb-3 p-2 rounded-lg bg-red-100 text-red-700 text-sm"><?=e($err)?></div><?php endif;?>
<form method="post" class="<?=CARD?> space-y-3"><?=csrf()?>
<?php if($baru):?>
 <label class="block text-sm">Pegawai<select name="user_id" class="<?=INP?>" required><option value="">- pilih -</option><?php foreach($us as $x)echo '<option value="'.$x['id'].'" '.($x['id']==$row['user_id']?'selected':'').'>'.e($x['nama']).'</option>';?></select></label>
 <label class="block text-sm">Tanggal masuk<input type="date" name="tanggal" value="<?=e($row['tanggal'])?>" class="<?=INP?>" required></label>
<?php else:?>
 <p><b class="block text-base"><?=e($row['nama'])?></b><span class="text-sm text-stone-500"><?=date('d-m-Y',strtotime($row['tanggal']))?></span></p>
<?php endif;?>
 <div class="grid grid-cols-2 gap-3">
  <label class="block text-sm">Jam masuk<input type="time" name="jam_masuk" value="<?=e(substr($row['jam_masuk'],0,5))?>" class="<?=INP?>" required></label>
  <label class="block text-sm">Jam pulang<input type="time" name="jam_pulang" value="<?=e(substr((string)$row['jam_pulang'],0,5))?>" class="<?=INP?>"></label></div>
 <p class="text-xs text-stone-400">Shift malam: isi jam pulang yang lebih kecil dari jam masuk (misal masuk 22:00, pulang 06:00). Kosongkan jam pulang jika memang belum pulang.</p>
 <label class="block text-sm">Catatan alasan (wajib)<textarea name="catatan" rows="2" maxlength="255" class="<?=INP?>" placeholder="Contoh: lupa absen pulang, dikonfirmasi lewat WhatsApp" required><?=e($row['catatan'])?></textarea></label>
 <div class="flex gap-2"><button class="<?=BTN?>">Simpan</button><a href="laporan.php" class="px-4 py-2.5 rounded-xl border border-amber-200 hover:bg-amber-100">Batal</a></div>
</form>
<?php if($id):?><form method="post" class="mt-3 text-right" onsubmit="return confirm('Hapus data absen ini permanen?')"><?=csrf()?><button name="hapus" value="1" class="text-sm text-red-600 underline">Hapus data absen ini</button></form><?php endif;?>
</div><?php foot();
