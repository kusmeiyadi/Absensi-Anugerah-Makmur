<?php require '../config.php'; need('admin');
$o="-- Backup Absensi ".date('Y-m-d H:i:s')."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
foreach($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t){
 $o.="DROP TABLE IF EXISTS `$t`;\n".$db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1].";\n\n";
 $rs=$db->query("SELECT * FROM `$t`");$buf=[];
 $flush=function()use(&$o,&$buf,$t){if($buf){$o.="INSERT INTO `$t` VALUES\n".implode(",\n",$buf).";\n";$buf=[];}};
 while($r=$rs->fetch(PDO::FETCH_NUM)){$buf[]='('.implode(',',array_map(fn($v)=>$v===null?'NULL':$db->quote((string)$v),$r)).')';if(count($buf)>=100)$flush();}
 $flush();$o.="\n";}
$o.="SET FOREIGN_KEY_CHECKS=1;\n";$nm='backup-absensi-'.date('Ymd-His');
if(isset($_GET['zip'])&&class_exists('ZipArchive')){
 $f=tempnam(sys_get_temp_dir(),'bk');$z=new ZipArchive;$z->open($f,ZipArchive::OVERWRITE);$z->addFromString('database.sql',$o);
 foreach(glob(__DIR__.'/../uploads/*.{jpg,png,webp}',GLOB_BRACE)?:[] as $p)$z->addFile($p,'uploads/'.basename($p));
 $z->close();header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.$nm.'.zip"');header('Content-Length: '.filesize($f));readfile($f);unlink($f);exit;}
header('Content-Type: application/sql; charset=utf-8');header('Content-Disposition: attachment; filename="'.$nm.'.sql"');echo $o;
