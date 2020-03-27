<?php
set_time_limit(0);
date_default_timezone_set('Asia/Hong_Kong');
$t = date('Y-m-d', time());
$id_array = array('51687', '51367');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/book/';
$base_url = 'http://pub.6tu.me/book/';

$refer = $base_url . $_SERVER['PHP_SELF'];
$option = array(
                'http' => array('header' => "Referer:$refer"),
                'ssl' => array('verify_peer' => false, 'verify_peer_name' => false,),
                );
file_put_contents($base_path . 'newurl.txt', '');
if(!file_exists($base_path . 'log'))mkdir($base_path . 'log');
foreach($id_array as $k => $id){
    if(!file_exists($base_path . $id))mkdir($base_path . $id);
    echo "=============开始更新<b> $id</b>=============<br>\r\n";

    # 本地log中的日期和当期日期对比
    $fn = 'log/' . $id . 'uf.log';
    if(!file_exists($base_path . $fn))file_put_contents($base_path . $fn, '');
    $uf = file_get_contents($base_path . $fn);
    if(strpos($uf, $t) !== false){
        echo $id . " 无需更新<br>\r\n";
        echo "如果更新有误，<a href=?dellog=$id>刷新更新记录</a>再次更新<br><br>\r\n";
        if(!empty($_GET['dellog'])and$_GET['dellog'] == $id){
            unlink($fn);
            header("Location:" . $_SERVER['PHP_SELF']);
        }
        continue;
    }
    # 远端文件列表. 覆盖该书首页,这里可修改提取的数据
    $index = file_get_contents($base_url . $id, false, stream_context_create($option));
    file_put_contents($base_path . $id . '/index.html', $index);
    preg_match_all('/<a[^<]+href="(.+?)"/is', $index, $m);
    $remote_full = join($m[1]);
    $remote = str_replace('.html', ".html\r\n", $remote_full);
    echo "远端文件列表提取完成<br>\r\n";

    # 与本地文件比对出剩余链接, 远端文件列表中没有index.html
    $array_local = scandir($base_path . $id);
    $local = '';
    foreach($array_local as $v){
        if($v != '.' && $v != '..'){
            $remote = str_replace($v, '', $remote);
            $local .= $v . "\r\n";
        }
    }
    echo "本地文件统计完毕<br>\r\n";
    if(empty(trim($remote))){
        echo "没有更新的文件.<br>\r\n";
        continue;
    }
    $n = substr_count($remote, ".html");
    $num = $n + 1;
    $remote_new = str_replace('.html', ".html ", $remote);
    $remote_new = chunk_split($remote_new, 140);
    echo "更新的<b>$num条</b>链接是:<p><pre>$remote_new</pre></p>\r\n";
    
    # 更新该书章节，这里可修改提取的数据
    $array_new = explode("\r\n", $remote);
    $newurl = '';
    foreach($array_new as $key => $value){
        $value = preg_replace("/\s(?=\s)/", "\\1", $value);
        $value = trim($value);
        if(empty($value))continue;
        $new_url = $base_url . $id . '/' . $value;
        $new_fn = $base_path . $id . '/' . $value;
        $content = file_get_contents($new_url, false, stream_context_create($option));
        file_put_contents($new_fn, $content);
        $newurl .= $new_url . "\r\n";
        if($key == $n)break;
    }
    file_put_contents($base_path . 'newurl.txt', $newurl, FILE_APPEND);
    echo "=============<b>$id</b> 更新完毕=============<br><br><br>\r\n";
    $ufn = file_get_contents($base_url . $fn);
    file_put_contents($base_path . $fn, $ufn);
}
echo "<br>=============<b> 全部更新完毕 =============</b><br><br>\r\n";

# DOMDocument 针对UTF-8处理
# $element_type 只能是 tag 、 id 和 class
# DIV标签中的 class
// $element = 'bottem2';
// $element_type = 'class';
function dom_import_html($str, $element, $element_type){
    $dom = new DOMDocument("1.0","UTF-8");

    $dom->formatOutput=false;
    $dom->preserveWhiteSpace=true;
    
    $dom->validateOnParse=false;
    $dom->standalone=true;
    $dom->strictErrorChecking=false;
    $dom->recover=true;

    $dom -> loadHTML($str);
    $tr = $dom->createElement('br');

    # HTML标签
    # appendChild() [追加]参数为对象，且只能是一个(Object(DOMNodeList)
    // $object = $dom -> getElementsByTagName($element) -> item(2); # 指定第2个标签
    if(strtolower($element_type) == 'tag'){
        $object = $dom -> getElementsByTagName($element);
        foreach ($object as $node) {
            // $dom -> appendChild($tr);
            $dom -> appendChild($node);
        }
    }
    if(strtolower($element_type) == 'id'){
        $object = $dom -> getElementById($element);
        $dom -> appendChild($object);
    }
    if(strtolower($element_type) == 'class'){
        $class = getElementsByClassName($dom, $ClassName = $element, $tagName = null);
        $object = $class[0];
        $object = $dom -> getElementById($element);
        $dom -> appendChild($object);
    }
    // echo @$object->length;
    $html = $dom -> saveHTML();
    $html_array = explode('</html>', $html);
    $html = trim($html_array[1]);
    // echo $html . "\r\n<br><br><br>\r\n";
    return $html;
}

# 返回数组，$Matched[0]为对象
function getElementsByClassName($dom, $ClassName, $tagName = null){
    if($tagName) $Elements = $dom -> getElementsByTagName($tagName);
    else $Elements = $dom -> getElementsByTagName("*");
    $Matched = array();
    for($i = 0; $i < $Elements -> length; $i++){
        if($Elements -> item($i) -> attributes -> getNamedItem('class')){
            if($Elements -> item($i) -> attributes -> getNamedItem('class') -> nodeValue == $ClassName){
                $Matched[] = $Elements -> item($i);
            }
        }
    }
    return $Matched;
}