<?php require '../config.php'; need('admin'); require __DIR__.'/_migrasi.php'; require __DIR__.'/../toko.php';
$d1=$_GET['dari']??date('Y-m-01');$d2=$_GET['sampai']??date('Y-m-d');$uid=(int)($_GET['u']??0);
$sql='SELECT a.*,u.nama,u.gaji_per_jam,u.uang_makan,tm.nama tm,tp.nama tp FROM absensi a JOIN users u ON u.id=a.user_id LEFT JOIN toko tm ON tm.id=a.toko_masuk LEFT JOIN toko tp ON tp.id=a.toko_pulang WHERE a.tanggal BETWEEN ? AND ?'.($uid?' AND a.user_id=?':'').' ORDER BY a.tanggal DESC,u.nama';
$q=$db->prepare($sql);$q->execute($uid?[$d1,$d2,$uid]:[$d1,$d2]);$rows=$q->fetchAll();
$tJam=$tGaji=$tMakan=0;
foreach($rows as &$r){$dt=detik($r['jam_masuk'],$r['jam_pulang']);$r['_gaji']=$dt===null?0:(int)round($dt/3600*$r['gaji_per_jam']);$r['_makan']=(int)$r['uang_makan'];$tJam+=$dt??0;$tGaji+=$r['_gaji'];$tMakan+=$r['_makan'];}
unset($r);
if(isset($_GET['csv'])){header('Content-Type: text/csv');header('Content-Disposition: attachment; filename=laporan-absensi.csv');$o=fopen('php://output','w');
 fputcsv($o,['Tanggal','Nama','Toko Masuk','Toko Pulang','Masuk','Pulang','Total Jam','Gaji Pokok','Uang Makan']);
 foreach($rows as $r)fputcsv($o,[$r['tanggal'],$r['nama'],$r['tm'],$r['tp'],$r['jam_masuk'],$r['jam_pulang'],hm(detik($r['jam_masuk'],$r['jam_pulang'])),$r['_gaji'],$r['_makan']]);exit;}
$us=$db->query('SELECT id,nama FROM users ORDER BY nama')->fetchAll();
$tokoAll=$db->query('SELECT nama,lat,lng,radius FROM toko WHERE aktif=1 AND lat IS NOT NULL AND lng IS NOT NULL ORDER BY id')->fetchAll();
head('Laporan');?>
<h1 class="text-xl font-bold text-yellow-900 mb-3">Laporan Absensi</h1>
<?php if(!empty($_GET['ok']))echo '<div class="mb-3 p-2 rounded-lg bg-yellow-100 text-sm">'.e($_GET['ok']).'</div>';?>
<div class="flex flex-wrap gap-2 mb-4 text-sm"><a href="koreksi.php?baru=1" class="<?=BTN?>">+ Tambah absen manual</a><a href="backup.php" class="px-4 py-2.5 rounded-xl border border-amber-300 hover:bg-amber-100">💾 Backup database (.sql)</a><a href="backup.php?zip=1" class="px-4 py-2.5 rounded-xl border border-amber-300 hover:bg-amber-100">📦 Backup + foto (.zip)</a></div>
<form action="rekap.php" class="<?=CARD?> flex flex-wrap gap-3 items-end mb-4"><label class="text-sm">Rekap bulanan (Excel)<select name="bln" class="<?=INP?>"><?php foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i=>$nm)echo '<option value="'.($i+1).'" '.($i+1==date('n')?'selected':'').'>'.$nm.'</option>';?></select></label><label class="text-sm">Tahun<select name="thn" class="<?=INP?>"><?php for($y=(int)date('Y');$y>=date('Y')-3;$y--)echo '<option>'.$y.'</option>';?></select></label><button class="<?=BTN?>">📥 Unduh Rekap Jam Kerja (.xlsx)</button></form>
<form class="<?=CARD?> flex flex-wrap gap-3 items-end mb-4">
 <label class="text-sm">Dari<input type="date" name="dari" value="<?=e($d1)?>" class="<?=INP?>"></label>
 <label class="text-sm">Sampai<input type="date" name="sampai" value="<?=e($d2)?>" class="<?=INP?>"></label>
 <label class="text-sm">Pegawai<select name="u" class="<?=INP?>"><option value="0">Semua</option><?php foreach($us as $x)echo '<option value="'.$x['id'].'" '.($x['id']==$uid?'selected':'').'>'.e($x['nama']).'</option>';?></select></label>
 <button class="<?=BTN?>">Tampilkan</button>
 <button name="csv" value="1" class="rounded-lg border border-yellow-400 px-4 py-2 hover:bg-yellow-100">Unduh CSV</button></form>
<p class="text-sm text-stone-500 mb-2">Periode <?=date('d-m-Y',strtotime($d1))?> sampai <?=date('d-m-Y',strtotime($d2))?></p>
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
<?php foreach([['Total Jam Kerja',hm($tJam),count($rows).' hari absen',''],['Total Gaji Pokok',rp($tGaji),'berdasarkan jam kerja',''],['Total Uang Makan',rp($tMakan),'per hari absen',''],['Total Keseluruhan',rp($tGaji+$tMakan),'gaji pokok + uang makan','!bg-amber-100']] as [$l,$v,$sub,$bg]):?>
 <div class="<?=CARD?> <?=$bg?> text-center"><p class="text-xl md:text-2xl font-bold text-yellow-800 break-words"><?=$v?></p><p class="text-sm font-medium"><?=$l?></p><p class="text-xs text-stone-400"><?=$sub?></p></div>
<?php endforeach;?></div>
<div class="<?=CARD?> overflow-x-auto"><table class="w-full text-sm"><tr class="text-left text-stone-500"><th>Tanggal</th><th>Nama</th><th>Toko</th><th>Masuk</th><th>Pulang</th><th>Total Jam</th><th>Gaji Pokok</th><th>Uang Makan</th><th>Foto</th><th>Peta</th><th></th></tr>
<?php foreach($rows as $r):?><tr class="border-t border-yellow-100"><td class="py-1"><?=date('d-m-Y',strtotime($r['tanggal']))?></td><td><?=e($r['nama'])?></td><td><?=e($r['tm']?:'-')?><?=($r['tp']&&$r['tp']!==$r['tm'])?' → '.e($r['tp']):''?></td><td><?=substr($r['jam_masuk'],0,5)?></td><td><?=$r['jam_pulang']?substr($r['jam_pulang'],0,5):'<span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700">Belum pulang</span>'?></td><td class="font-semibold"><?=hm(detik($r['jam_masuk'],$r['jam_pulang']))?><?php if(!empty($r['dikoreksi']))echo ' <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-medium">dikoreksi</span><div class="text-[11px] font-normal text-stone-400 max-w-[200px] whitespace-normal">'.e($r['catatan']).'</div>';?></td>
<td><?=$r['_gaji']?rp($r['_gaji']):'-'?></td><td><?=$r['_makan']?rp($r['_makan']):'-'?></td>
<td><?php foreach(['foto_masuk'=>'masuk','foto_pulang'=>'pulang'] as $k=>$l)if($r[$k])echo '<a class="text-yellow-700 underline mr-1" target="_blank" href="'.BASE.'/uploads/'.e($r[$k]).'">'.$l.'</a>';?></td>
<td><?php if($r['lat_masuk']||$r['lat_pulang']):?><button type="button" onclick="peta(this)" class="text-yellow-700 underline" data-nama="<?=e($r['nama'])?>" data-tgl="<?=date('d-m-Y',strtotime($r['tanggal']))?>" data-mjam="<?=substr($r['jam_masuk'],0,5)?>" data-mlat="<?=e($r['lat_masuk'])?>" data-mlng="<?=e($r['lng_masuk'])?>" data-pjam="<?=substr((string)$r['jam_pulang'],0,5)?>" data-plat="<?=e($r['lat_pulang'])?>" data-plng="<?=e($r['lng_pulang'])?>" data-tm="<?=e($r['tm'])?>" data-tp="<?=e($r['tp'])?>">Lihat peta</button><?php else:?>-<?php endif;?></td><td><a class="text-yellow-700 underline" href="koreksi.php?id=<?=$r['id']?>">Koreksi</a></td></tr><?php endforeach;?>
<?php if(!$rows)echo '<tr><td colspan="11" class="py-4 text-center text-stone-400">Tidak ada data.</td></tr>';?></table></div>
<div id="pm" class="hidden fixed inset-0 z-50 bg-black/40 p-3 grid place-items-center" onclick="if(event.target===this)this.classList.add('hidden')">
 <div class="<?=CARD?> w-full max-w-2xl !p-4">
  <div class="flex justify-between items-center mb-2"><div><b id="pj" class="block text-sm"></b><span id="pt" class="text-xs text-stone-500"></span></div><button type="button" onclick="document.getElementById('pm').classList.add('hidden')" class="text-xl px-2">✕</button></div>
  <div class="flex flex-wrap gap-3 text-xs mb-2"><span>🟢 Lokasi masuk</span><span>🔴 Lokasi pulang</span><span>🟠 Area toko</span></div>
  <div id="pmap" style="height:55vh;min-height:300px" class="rounded-xl overflow-hidden"></div>
  <div id="pl" class="flex flex-wrap gap-4 text-sm mt-2"></div>
 </div></div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
const TK=<?=json_encode(array_map(fn($t)=>['n'=>$t['nama'],'lat'=>(float)$t['lat'],'lng'=>(float)$t['lng'],'r'=>(int)$t['radius']],$tokoAll))?>;const OF=TK[0]||{n:'',lat:-0.0263,lng:109.3425,r:100};
let M,G;
function peta(b){const d=b.dataset;
 document.getElementById('pm').classList.remove('hidden');document.getElementById('pj').textContent=d.nama;document.getElementById('pt').textContent=d.tgl;
 if(!M){M=L.map('pmap').setView([OF.lat,OF.lng],16);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(M);G=L.layerGroup().addTo(M);}
 G.clearLayers();M.invalidateSize();const base=TK.find(t=>t.n===d.tm)||TK.find(t=>t.n===d.tp)||OF;const bd=L.latLng(base.lat,base.lng).toBounds(base.r*2.5);TK.forEach(t=>{if(t.n===d.tm||t.n===d.tp)bd.extend(L.latLng(t.lat,t.lng).toBounds(t.r*2.5));});
 TK.forEach(t=>L.circle([t.lat,t.lng],{radius:t.r,color:'#d97706',weight:2,fillOpacity:.08}).bindTooltip(t.n,{direction:'center'}).addTo(G));
 const pt=(la,ln,jam,lbl,w,dir)=>{if(!la)return;const p=L.latLng(+la,+ln);bd.extend(p);L.circleMarker(p,{radius:9,color:'#fff',weight:2,fillColor:w,fillOpacity:1}).bindTooltip(lbl+' '+jam,{permanent:true,direction:dir}).addTo(G);};
 pt(d.mlat,d.mlng,d.mjam,'Masuk','#16a34a','top');pt(d.plat,d.plng,d.pjam,'Pulang','#dc2626','bottom');
 M.fitBounds(bd.pad(.25));
 document.getElementById('pl').innerHTML=[['mlat','mlng','masuk'],['plat','plng','pulang']].filter(a=>d[a[0]]).map(a=>`<a class="text-yellow-700 underline" target="_blank" href="https://www.google.com/maps?q=${d[a[0]]},${d[a[1]]}">Buka lokasi ${a[2]} di Google Maps</a>`).join('');
}
</script>
<?php foot();
