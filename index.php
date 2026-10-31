<?php require 'config.php'; require_once __DIR__.'/version.php';
if(!$db->query('SELECT COUNT(*) FROM users')->fetchColumn())
 $db->prepare("INSERT INTO users(nama,username,password,role) VALUES('Administrator','admin',?,'admin')")->execute([password_hash('admin123',PASSWORD_DEFAULT)]);
if(user()){header('Location: '.(user()['role']==='admin'?'admin/index.php':'staff.php'));exit;}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){cek_csrf();
 $q=$db->prepare('SELECT * FROM users WHERE username=? AND aktif=1');$q->execute([trim($_POST['username'])]);$u=$q->fetch();
 if($u&&password_verify($_POST['password'],$u['password'])){session_regenerate_id(true);unset($u['password']);$_SESSION['user']=$u;header('Location: index.php');exit;}
 $err='Username atau password salah.';}
head('Masuk');?>
<div class="max-w-sm mx-auto mt-16 <?=CARD?>">
 <img src="assets/logo.png" alt="Logo Anugerah Makmur" class="h-20 w-auto mx-auto mb-2"><h1 class="text-2xl font-bold text-yellow-900 text-center">Anugerah Makmur</h1>
 <p class="text-center text-sm text-stone-500 mb-5">Absensi Pegawai<br><span class="text-xs text-stone-400">Silakan masuk untuk melanjutkan</span></p>
 <?php if($err):?><div class="mb-3 p-2 rounded-lg bg-red-100 text-red-700 text-sm"><?=e($err)?></div><?php endif;?>
 <form method="post" class="space-y-3"><?=csrf()?>
  <input name="username" placeholder="Username" class="<?=INP?>" required autofocus>
  <input name="password" type="password" placeholder="Password" class="<?=INP?>" required>
  <button class="<?=BTN?> w-full">Masuk</button></form>
</div>
<?=versi_html()?><?php foot();
