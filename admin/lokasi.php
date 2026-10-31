<?php require '../config.php'; need('admin'); require __DIR__.'/../toko.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){cek_csrf();
 $u=$db->prepare('UPDATE toko SET nama=?,lat=?,lng=?,radius=?,aktif=? WHERE id=?');
 foreach($_POST['t']??[] as $id=>$t){
  $nm=trim($t['nama'])?:'Toko';$la=trim($t['lat'])!==''?(float)$t['lat']:null;$ln=trim($t['lng'])!==''?(float)$t['lng']:null;
  $ak=(isset($t['aktif'])&&$la!==null&&$ln!==null)?1:0;
  $u->execute([$nm,$la,$ln,max(10,(int)$t['radius']),$ak,(int)$id]);}
 $msg='Pengaturan toko tersimpan. Toko tanpa koordinat otomatis nonaktif.';}
$list=$db->query('SELECT * FROM toko ORDER BY id')->fetchAll();
head('Toko');?>
<h1 class="text-xl font-bold text-yellow-900 mb-1">Lokasi Toko</h1>
<p class="text-sm text-stone-500 mb-3">Pegawai otomatis absen di toko terdekat yang masih dalam radiusnya. Isi koordinat lalu centang Aktif untuk memakai sebuah toko.</p>
<?php if($msg):?><div class="mb-3 p-2 rounded-lg bg-yellow-100 text-sm"><?=e($msg)?></div><?php endif;?>
<form method="post"><?=csrf()?>
<div class="grid md:grid-cols-3 gap-4">
<?php foreach($list as $t):$k='t['.$t['id'].']';?>
 <div class="tk <?=CARD?> space-y-3">
  <label class="block text-sm">Nama toko<input name="<?=$k?>[nama]" value="<?=e($t['nama'])?>" class="<?=INP?>"></label>
  <div class="grid grid-cols-2 gap-2">
   <label class="block text-sm">Latitude<input name="<?=$k?>[lat]" value="<?=e($t['lat'])?>" class="<?=INP?>"></label>
   <label class="block text-sm">Longitude<input name="<?=$k?>[lng]" value="<?=e($t['lng'])?>" class="<?=INP?>"></label></div>
  <button type="button" onclick="gps(this)" class="text-sm underline text-yellow-800">📍 Gunakan lokasi saya sekarang</button>
  <label class="block text-sm">Radius (meter)<input name="<?=$k?>[radius]" type="number" value="<?=(int)$t['radius']?>" class="<?=INP?>"></label>
  <label class="text-sm flex gap-2 items-center"><input type="checkbox" name="<?=$k?>[aktif]" <?=$t['aktif']?'checked':''?>> Aktif</label>
 </div>
<?php endforeach;?>
</div>
<button class="<?=BTN?> mt-4">Simpan semua toko</button></form>
<script>function gps(b){const c=b.closest('.tk');navigator.geolocation.getCurrentPosition(p=>{c.querySelector('[name$="[lat]"]').value=p.coords.latitude.toFixed(7);c.querySelector('[name$="[lng]"]').value=p.coords.longitude.toFixed(7)},()=>alert('Gagal membaca GPS'),{enableHighAccuracy:true});}</script>
<?php foot();
