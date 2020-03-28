<?php
set_time_limit(0);
date_default_timezone_set('Asia/Hong_Kong');
$t = date('Y-m-d', time());

$id_array = array('51687', '51367');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/book/';
$url = 'http://ali.6tu.me/book/';
$refer = $url . $_SERVER['PHP_SELF'];
$option = array(
               'http' => array('header' => "Referer:$refer"),
               'ssl' => array('verify_peer' => false, 'verify_peer_name' => false,),
               );
$c = '';
foreach($id_array as $k => $id){
    $id_str = $id;
    $fn = 'log/' . $id_str . 'uf.log';
    if(!file_exists($base_path . $fn)) file_put_contents($base_path . $fn, '');
    $uf = file_get_contents($base_path . $fn);
    if(strpos($uf, $t) == false){
        file_get_contents($url . 'xbiquge_v0.2.php', false, stream_context_create($option));
    }
    $id = $id . '/';
    $str = file_get_contents($base_path . $id . 'index.html', NULL, NULL, 0, 1024);
    preg_match('/<title>(.*?)<\/title>/is', $str, $f);
    $tt = $f[0];

    $str = str_replace('href="', 'href="' . $id, $str);
    $array = explode("\r\n", $str);
    $n = count($array) - 2;
    foreach($array as $key => $value){
        if($key < 4) continue;
        if($key == $n) break;
        $c .= $value . "\r\n";
    }
    $update = "如果更新有误，<a href=?dellog=$id_str>刷新更新记录</a>再次更新<br><br>\r\n";
    $c = $c . "</dl>\r\n" . $update ;
    if(!empty( $_GET['dellog']) and $_GET['dellog'] == $id_str){
    unlink($fn);
    file_get_contents($url . 'xbiquge_v0.2.php', false, stream_context_create($option));
    header("Location:" . $_SERVER['PHP_SELF']);
    }
}
$head = '<!DOCTYPE html><html lang="zh-CN"><head>' . "\r\n";
$head .= '    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\r\n";
$head .= '    <title>爬小书_爬自笔趣阁</title></head>' . "\r\n";
$head .= '<body>' . "\r\n";

echo $head . $c;
