<?php require '../config.php'; need('admin');
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){cek_csrf();
 try{
  if(isset($_POST['hapus'])){if((int)$_POST['hapus']===user()['id'])throw new Exception('Tidak bisa menghapus akun sendiri.');$db->prepare('DELETE FROM users WHERE id=?')->execute([$_POST['hapus']]);$msg='Pegawai dihapus.';}
  else{$id=(int)$_POST['id'];$n=trim($_POST['nama']);$un=trim($_POST['username']);$role=$_POST['role']==='admin'?'admin':'staff';$ak=isset($_POST['aktif'])?1:0;$pw=$_POST['password'];
   if($n===''||$un==='')throw new Exception('Nama dan username wajib diisi.');
   if($id){$db->prepare('UPDATE users SET nama=?,username=?,role=?,aktif=? WHERE id=?')->execute([$n,$un,$role,$ak,$id]);
    if($pw!=='')$db->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($pw,PASSWORD_DEFAULT),$id]);}
   else{if(strlen($pw)<6)throw new Exception('Password minimal 6 karakter.');$db->prepare('INSERT INTO users(nama,username,password,role,aktif) VALUES(?,?,?,?,?)')->execute([$n,$un,password_hash($pw,PASSWORD_DEFAULT),$role,$ak]);}
   $msg='Data tersimpan.';}
 }catch(PDOException $x){$msg='Username sudah dipakai.';}catch(Exception $x){$msg=$x->getMessage();}}
$ed=['id'=>0,'nama'=>'','username'=>'','role'=>'staff','aktif'=>1];
if(isset($_GET['edit'])){$q=$db->prepare('SELECT * FROM users WHERE id=?');$q->execute([$_GET['edit']]);$ed=$q->fetch()?:$ed;}
$list=$db->query('SELECT * FROM users ORDER BY role,nama')->fetchAll();
head('Pegawai');?>
<h1 class="text-xl font-bold text-yellow-900 mb-3">Data Pegawai</h1>
<?php if($msg):?><div class="mb-3 p-2 rounded-lg bg-yellow-100 text-sm"><?=e($msg)?></div><?php endif;?>
<div class="grid md:grid-cols-3 gap-4">
<form method="post" class="<?=CARD?> space-y-2 h-fit"><?=csrf()?><input type="hidden" name="id" value="<?=$ed['id']?>">
 <h2 class="font-semibold"><?=$ed['id']?'Edit':'Tambah'?> pegawai</h2>
 <input name="nama" value="<?=e($ed['nama'])?>" placeholder="Nama lengkap" class="<?=INP?>" required>
 <input name="username" value="<?=e($ed['username'])?>" placeholder="Username" class="<?=INP?>" required>
 <input name="password" type="password" placeholder="<?=$ed['id']?'Password baru (kosongkan jika tidak diubah)':'Password'?>" class="<?=INP?>">
 <select name="role" class="<?=INP?>"><option value="staff">Staf</option><option value="admin" <?=$ed['role']==='admin'?'selected':''?>>Admin</option></select>
 <label class="text-sm flex gap-2"><input type="checkbox" name="aktif" <?=$ed['aktif']?'checked':''?>> Aktif</label>
 <button class="<?=BTN?> w-full">Simpan</button></form>
<div class="<?=CARD?> md:col-span-2 overflow-x-auto"><table class="w-full text-sm"><tr class="text-left text-stone-500"><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th></th></tr>
<?php foreach($list as $r):?><tr class="border-t border-yellow-100"><td class="py-1"><?=e($r['nama'])?></td><td><?=e($r['username'])?></td><td><?=$r['role']?></td><td><?=$r['aktif']?'Aktif':'Nonaktif'?></td>
<td class="text-right whitespace-nowrap"><a class="text-yellow-700 underline" href="?edit=<?=$r['id']?>">Edit</a>
<form method="post" class="inline" onsubmit="return confirm('Hapus pegawai ini beserta absensinya?')"><?=csrf()?><button name="hapus" value="<?=$r['id']?>" class="text-red-600 underline ml-2">Hapus</button></form></td></tr><?php endforeach;?></table></div></div>
<?php foot();
