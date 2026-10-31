<?php require 'config.php'; need(); require __DIR__.'/toko.php'; require_once __DIR__.'/version.php';
$u=user();$tk=$db->query('SELECT id,nama,lat,lng,radius FROM toko WHERE aktif=1 AND lat IS NOT NULL AND lng IS NOT NULL')->fetchAll();
$q=$db->prepare('SELECT * FROM absensi WHERE user_id=? AND tanggal=?');$q->execute([$u['id'],date('Y-m-d')]);$a=$q->fetch();
$q=$db->prepare('SELECT * FROM absensi WHERE user_id=? AND jam_pulang IS NULL AND tanggal>=? ORDER BY tanggal DESC LIMIT 1');$q->execute([$u['id'],date('Y-m-d',strtotime('-1 day'))]);$o=$q->fetch();
$bl=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$bn=max(1,min(12,(int)($_GET['bln']??date('n'))));$th=(int)($_GET['thn']??date('Y'));if($th<2020||$th>2100)$th=(int)date('Y');
$t0=sprintf('%04d-%02d-01',$th,$bn);$t1=date('Y-m-t',strtotime($t0));
$h=$db->prepare('SELECT a.*,tm.nama tm FROM absensi a LEFT JOIN toko tm ON tm.id=a.toko_masuk WHERE a.user_id=? AND a.tanggal BETWEEN ? AND ? ORDER BY a.tanggal DESC');$h->execute([$u['id'],$t0,$t1]);$hist=$h->fetchAll();
$sumJam=0;foreach($hist as $r0){$sumJam+=detik($r0['jam_masuk'],$r0['jam_pulang'])??0;}
head('Absen Saya');?>
<div class="max-w-xl mx-auto space-y-4">
 <div class="<?=CARD?> text-center">
  <p class="text-sm text-stone-500">Absen otomatis di toko terdekat</p><span class="inline-block mt-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-medium"><?=count($tk)?> toko aktif</span>
  <p id="jam" class="text-4xl font-bold text-yellow-800 my-1">--:--:--</p>
  <p class="text-sm"><?=date('d-m-Y')?></p>
  <div id="gps" class="mt-3 text-sm rounded-lg bg-yellow-100 p-2">Membaca lokasi GPS…</div>
 </div>
 <div class="<?=CARD?> space-y-3">
  <p class="text-sm font-medium">Status hari ini</p>
  <?php $r0=$o?:$a;$cp='inline-block px-2.5 py-1 rounded-full text-xs font-medium ';?>
  <div class="flex flex-wrap gap-1.5">
  <?php if(!$r0):?><span class="<?=$cp?>bg-stone-100 text-stone-500">Belum absen</span>
  <?php else:?><span class="<?=$cp?>bg-green-100 text-green-800">Masuk <?=substr($r0['jam_masuk'],0,5)?></span>
   <?php if($r0['jam_pulang']):?><span class="<?=$cp?>bg-amber-100 text-amber-900">Pulang <?=substr($r0['jam_pulang'],0,5)?></span><span class="<?=$cp?>bg-yellow-200 text-yellow-900">Total <?=hm(detik($r0['jam_masuk'],$r0['jam_pulang']))?></span>
   <?php else:?><span class="<?=$cp?>bg-red-100 text-red-700">Belum pulang</span><?php endif;?>
  <?php endif;?></div>
  <label class="block text-sm">Foto selfie <span class="text-stone-400">(opsional)</span>
   <input id="foto" type="file" accept="image/*" capture="user" class="<?=INP?> mt-1 text-sm"></label>
  <div class="grid grid-cols-2 gap-3">
   <button onclick="absen('masuk')" class="<?=BTN?> disabled:opacity-40" <?=$a?'disabled':''?>>Absen Masuk</button>
   <button onclick="absen('pulang')" class="<?=BTN?> disabled:opacity-40" <?=!$o?'disabled':''?>>Absen Pulang</button>
  </div>
  <div id="msg" class="hidden text-sm p-2 rounded-lg"></div>
 </div>
 <div class="<?=CARD?>">
  <h2 class="font-semibold mb-3">Riwayat absensi</h2>
  <form method="get" class="flex flex-wrap gap-2 items-end mb-3">
   <select name="bln" class="<?=INP?> !w-auto"><?php foreach($bl as $i=>$nm)echo '<option value="'.($i+1).'" '.($i+1==$bn?'selected':'').'>'.$nm.'</option>';?></select>
   <select name="thn" class="<?=INP?> !w-auto"><?php for($y=(int)date('Y');$y>=(int)date('Y')-3;$y--)echo '<option '.($y==$th?'selected':'').'>'.$y.'</option>';?></select>
   <button class="<?=BTN?> !py-2">Tampilkan</button></form>
  <div class="flex flex-wrap gap-1.5 mb-3 text-xs font-medium"><span class="px-2.5 py-1 rounded-full bg-green-100 text-green-800">Hadir <?=count($hist)?> hari</span><span class="px-2.5 py-1 rounded-full bg-yellow-200 text-yellow-900">Total jam <?=hm($sumJam)?></span></div>
  <div class="overflow-x-auto"><table class="w-full text-sm"><tr class="text-left text-stone-500"><th>Tanggal</th><th>Toko</th><th>Masuk</th><th>Pulang</th><th>Total</th></tr>
  <?php foreach($hist as $r):?><tr class="border-t border-yellow-100"><td class="py-1"><?=date('d-m-Y',strtotime($r['tanggal']))?></td><td><?=e($r['tm']?:'-')?></td><td><?=substr($r['jam_masuk'],0,5)?></td><td><?=$r['jam_pulang']?substr($r['jam_pulang'],0,5):'-'?></td><td class="font-semibold"><?=hm(detik($r['jam_masuk'],$r['jam_pulang']))?></td></tr><?php endforeach;?>
  <?php if(!$hist)echo '<tr><td colspan="5" class="py-4 text-center text-stone-400">Belum ada absensi di bulan ini.</td></tr>';?></table></div></div>
</div>
<script>
const TK=<?=json_encode(array_map(fn($t)=>['n'=>$t['nama'],'lat'=>(float)$t['lat'],'lng'=>(float)$t['lng'],'r'=>(int)$t['radius']],$tk))?>;
const hav=(a,b,c,d)=>{const p=Math.PI/180,x=Math.sin((c-a)*p/2)**2+Math.cos(a*p)*Math.cos(c*p)*Math.sin((d-b)*p/2)**2;return 2*6371000*Math.asin(Math.sqrt(x))};
const S0=<?=time()*1000?>,P0=performance.now(); // jam mengikuti server, bukan jam HP
setInterval(()=>jam.textContent=new Date(S0+performance.now()-P0).toLocaleTimeString('en-GB',{timeZone:'Asia/Pontianak',hour12:false}),1000);
const gpsErr=()=>gps.textContent='GPS tidak aktif / izin lokasi ditolak. Aktifkan lokasi lalu muat ulang.';
const near=(la,ln)=>TK.map(t=>({...t,d:Math.round(hav(la,ln,t.lat,t.lng))})).sort((a,b)=>a.d-b.d)[0];
if(navigator.geolocation&&TK.length)navigator.geolocation.watchPosition(p=>{const n=near(p.coords.latitude,p.coords.longitude),ok=n.d<=n.r;
 gps.className='mt-3 text-sm rounded-lg p-2 '+(ok?'bg-green-100 text-green-800':'bg-red-100 text-red-700');
 const E=x=>String(x).replace(/[&<>"]/g,c=>'&#'+c.charCodeAt(0)+';');gps.innerHTML='<div class="font-semibold">'+(ok?'✅ Dalam area '+E(n.n):'❌ Di luar area toko')+'</div><div>'+(ok?'':'Terdekat: '+E(n.n)+', ')+n.d+' m dari titik toko</div><div class="text-xs opacity-70">Akurasi GPS ±'+Math.round(p.coords.accuracy)+' m</div>';},gpsErr,{enableHighAccuracy:true});
else gps.textContent=TK.length?'GPS tidak tersedia di perangkat ini.':'Belum ada toko aktif. Hubungi admin.';
function show(t,ok){msg.className='text-sm p-2 rounded-lg '+(ok?'bg-green-100 text-green-800':'bg-red-100 text-red-700');msg.textContent=t}
function kompres(f,max=800,q=.7){return new Promise(res=>{const img=new Image(),u=URL.createObjectURL(f);img.onload=()=>{const k=Math.min(1,max/Math.max(img.width,img.height)),c=document.createElement('canvas');c.width=Math.round(img.width*k);c.height=Math.round(img.height*k);c.getContext('2d').drawImage(img,0,0,c.width,c.height);URL.revokeObjectURL(u);c.toBlob(b=>res(b||f),'image/jpeg',q)};img.onerror=()=>res(f);img.src=u})}
const b64=b=>new Promise(r=>{const x=new FileReader();x.onload=()=>r(x.result);x.readAsDataURL(b)});
function absen(aksi){show('Memproses…',true);
 navigator.geolocation.getCurrentPosition(async p=>{
  const fd=new URLSearchParams({aksi,csrf:'<?=$_SESSION['csrf']?>',lat:p.coords.latitude,lng:p.coords.longitude});
  if(foto.files[0]){show('Mengompres foto…',true);fd.append('foto64',await b64(await kompres(foto.files[0])));}
  try{const r=await fetch('kirim.php',{method:'POST',body:fd});let d;try{d=await r.json()}catch(_){d={ok:0,msg:'Server menolak permintaan (HTTP '+r.status+'). Hubungi admin.'}}show(d.msg,d.ok);if(d.ok)setTimeout(()=>location.reload(),1200)}catch(e){show('Gagal menghubungi server.',false)}
 },()=>show('Gagal membaca GPS. Izinkan akses lokasi.',false),{enableHighAccuracy:true,timeout:15000});}
</script>
<?=versi_html()?><?php foot();
