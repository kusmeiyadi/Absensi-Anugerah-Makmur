<?php require '../config.php'; need('admin'); require __DIR__.'/../toko.php'; require_once __DIR__.'/../version.php';
$t=date('Y-m-d');
$tot=$db->query("SELECT COUNT(*) FROM users WHERE aktif=1 AND role='staff'")->fetchColumn();
$q=$db->prepare("SELECT status,COUNT(*) c FROM absensi WHERE tanggal=? GROUP BY status");$q->execute([$t]);$c=array_column($q->fetchAll(),'c','status');
$hadir=($c['hadir']??0)+($c['terlambat']??0);
$q2=$db->prepare('SELECT COUNT(*) FROM absensi WHERE jam_pulang IS NULL AND tanggal>=?');$q2->execute([date('Y-m-d',strtotime('-1 day'))]);$bp=$q2->fetchColumn();
$q=$db->prepare('SELECT a.*,u.nama,tm.nama tm FROM absensi a JOIN users u ON u.id=a.user_id LEFT JOIN toko tm ON tm.id=a.toko_masuk WHERE tanggal=? ORDER BY jam_masuk');$q->execute([$t]);
head('Dashboard');?>
<h1 class="text-xl font-bold text-yellow-900 mb-3">Dashboard<span class="block text-sm font-normal text-stone-500"><?=date('d-m-Y')?></span></h1>
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
<?php foreach([['Pegawai aktif',$tot],['Hadir',$hadir],['Belum pulang',$bp],['Belum absen',max(0,$tot-$hadir)]] as [$l,$v]):?>
 <div class="<?=CARD?> text-center"><p class="text-3xl font-bold text-yellow-700"><?=$v?></p><p class="text-sm text-stone-500"><?=$l?></p></div><?php endforeach;?></div>
<div class="<?=CARD?> overflow-x-auto"><h2 class="font-semibold mb-2">Absensi hari ini</h2>
<table class="w-full text-sm"><tr class="text-left text-stone-500"><th>Nama</th><th>Toko</th><th>Masuk</th><th>Pulang</th><th>Total Jam</th><th>Foto</th></tr>
<?php foreach($q as $r):?><tr class="border-t border-yellow-100"><td class="py-1"><?=e($r['nama'])?></td><td><?=e($r['tm']?:'-')?></td><td><?=substr($r['jam_masuk'],0,5)?></td><td><?=$r['jam_pulang']?substr($r['jam_pulang'],0,5):'-'?></td><td class="font-semibold"><?=hm(detik($r['jam_masuk'],$r['jam_pulang']))?></td>
<td><?php foreach(['foto_masuk','foto_pulang'] as $k)if($r[$k])echo '<a class="text-yellow-700 underline mr-1" target="_blank" href="'.BASE.'/uploads/'.e($r[$k]).'">'.($k==='foto_masuk'?'masuk':'pulang').'</a>';?></td></tr><?php endforeach;?></table></div>
<?=versi_html()?>
<?php foot();
