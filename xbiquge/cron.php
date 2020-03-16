<?php
$id1 = '51888'; # 主角 哥哥
$id2 = '51999'; # 主角 妹妹
$url = 'http://localhost/path/xbiquge.php';

$base_path = __DIR__ . '/';
$log = $base_path . 'log/cron.log';
$today = getdate(); # 该函数显得十分繁琐
date_default_timezone_set('Asia/Hong_Kong');
$time_key = date("Y/m/d-H:i:s") . ' , ';
$hour = date("G");
$refer = $url . $_SERVER['PHP_SELF'];
$option = array(
               'http' => array('header' => "Referer:$refer"),
               'ssl' => array('verify_peer' => false, 'verify_peer_name' => false,),
               );
$out_str = '';
if(8 < $hour and $hour < 17){
    $c = file_get_contents($url . '?key=51888', false, stream_context_create($option));
    $arr = explode("\r\n", $c);
    if(strlen($arr[0]) > 39) $out_str = "<br> $id1 数据可能已经被更新<br><br>\r\n";
    else $out_str = "<br>数据尚未更新 => " . trim($arr[0]) . "<br>\r\n";
    file_put_contents($log, $time_key, FILE_APPEND);
}
if(20 < $hour and $hour < 25){
    $c = file_get_contents($url . '?key=51999', false, stream_context_create($option));
    $arr = explode("\r\n", $c);
    if(strlen($arr[0]) > 39) $out_str = "<br> $id2 数据可能已经被更新<br><br>\r\n";
    else $out_str = "<br>数据尚未更新 => " . trim($arr[0]) . "<br>\r\n";
    file_put_contents($log, $time_key, FILE_APPEND);
}
if(empty($out_str)) echo 'NULL';
else echo $out_str . "It is all done<br>\r\n";

if($hour == 6 or $hour == 15){
    $str = file_get_contents($log);
    $str = str_replace("\r\n", "", $str);
    $str = chunk_split($str, 110, "\r\n");
    file_put_contents($log, $str);
}
if(!file_exists($log)) exit;
if(filesize($log) > 10240) file_put_contents($log, '');
