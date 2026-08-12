<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_permission('sync_settings');

$backupDir = __DIR__ . '/../storage/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) throw new RuntimeException('Backup storage is unavailable.');

function createBackupSql(PDO $pdo): string {
    $tables=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $sql='';
    foreach($tables as $table){
        $quotedTable=str_replace('`','``',(string)$table);
        $create=$pdo->query("SHOW CREATE TABLE `{$quotedTable}`")->fetch(PDO::FETCH_ASSOC);
        if(!$create || !isset($create['Create Table'])) throw new RuntimeException('Unable to read database schema.');
        $sql .= "\n\nDROP TABLE IF EXISTS `{$quotedTable}`;\n".$create['Create Table'].";\n\n";
        $rows=$pdo->query("SELECT * FROM `{$quotedTable}`")->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $row){$values=array_map(static fn($v)=>$v===null?'NULL':$pdo->quote((string)$v),array_values($row));$sql.=sprintf("INSERT INTO `%s` VALUES (%s);\n",$quotedTable,implode(',',$values));}
        $sql.="\n";
    }
    return $sql;
}
function backupSourceLabel(string $filename): string {
    return preg_match('/^talaklase_(online|local)_/i',$filename,$m)===1 ? ucfirst(strtolower($m[1])) : 'Unknown';
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create_backup'])) {
    if (!verify_csrf()) { http_response_code(403); exit('Invalid security token.'); }
    $source=strtolower(trim((string)($_POST['backup_source']??'online')));
    if(!in_array($source,['online','local','both'],true)) $source='online';
    $timestamp=date('Y-m-d_H-i-s');$created=[];
    foreach($source==='both'?['online','local']:[$source] as $backupSource){
        $pdo=$backupSource==='local'?getLocalConnection():getOnlineConnection();
        $sql=createBackupSql($pdo);
        $filename=sprintf('talaklase_%s_%s.sql',$backupSource,$timestamp);
        if(file_put_contents($backupDir.DIRECTORY_SEPARATOR.$filename,$sql,LOCK_EX)===false) throw new RuntimeException('Unable to write backup file.');
        $created[]=$filename;
    }
    $_SESSION['import_success']=count($created)>1?'Backups created successfully.':'Backup created successfully.';
    header('Location: index.php?page=db_backup'); exit;
}
$files=glob($backupDir.'/*.sql') ?: [];
rsort($files);