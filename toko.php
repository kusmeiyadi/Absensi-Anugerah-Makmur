<?php
// Menyiapkan tabel toko (otomatis, sekali) + fungsi mencari toko terdekat
try{
 if(!$db->query("SHOW TABLES LIKE 'toko'")->fetch()){
  $db->exec("CREATE TABLE toko(id INT AUTO_INCREMENT PRIMARY KEY,nama VARCHAR(100) NOT NULL,lat DECIMAL(10,7) NULL,lng DECIMAL(10,7) NULL,radius INT NOT NULL DEFAULT 100,aktif TINYINT(1) NOT NULL DEFAULT 0)");
  $s=$db->query('SELECT * FROM settings WHERE id=1')->fetch()?:[];
  $ins=$db->prepare('INSERT INTO toko(nama,lat,lng,radius,aktif) VALUES(?,?,?,?,?)');
  $ins->execute(['Toko A',$s['lat']??null,$s['lng']??null,$s['radius']??100,isset($s['lat'])?1:0]);
  $ins->execute(['Toko B',null,null,100,0]);$ins->execute(['Toko C',null,null,100,0]);}
 if(!$db->query("SHOW COLUMNS FROM absensi LIKE 'toko_masuk'")->fetch())$db->exec("ALTER TABLE absensi ADD toko_masuk INT NULL, ADD toko_pulang INT NULL");
if(!$db->query("SHOW COLUMNS FROM users LIKE 'gaji_per_jam'")->fetch())$db->exec("ALTER TABLE users ADD gaji_per_jam INT NOT NULL DEFAULT 0, ADD uang_makan INT NOT NULL DEFAULT 0");
}catch(PDOException $e){die('Gagal menyiapkan data toko: '.htmlspecialchars($e->getMessage()));}
function toko_terdekat($db,$lat,$lng){$b=null;
 foreach($db->query('SELECT * FROM toko WHERE aktif=1 AND lat IS NOT NULL AND lng IS NOT NULL')->fetchAll() as $t){
  $d=jarak($lat,$lng,(float)$t['lat'],(float)$t['lng']);if(!$b||$d<$b['d'])$b=$t+['d'=>$d];}
 return $b;}
function rp($n){return 'Rp '.number_format((float)$n,0,',','.');}
