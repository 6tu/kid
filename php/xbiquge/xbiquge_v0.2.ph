<?php
ignore_user_abort();
set_time_limit(0);
date_default_timezone_set('Asia/Hong_Kong');
$t = date('Y-m-d', time());
$id_array = array('51687', '51367');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/book/';
$base_url = 'https://www.xbiquge.cc/book/';


/** ---------------以下部分可能无须设置--------------- **/

if(!file_exists($base_path . 'log')) mkdir($base_path . 'log', 0755, true);
file_put_contents($base_path . 'newurl.txt', '');

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
    $index = get_html($base_url . $id . '/', $charset='gbk');
    preg_match('/<title>(.*?)<\/title>/is', $index, $found);
    $title = $found[1];

    # 书名,作者及更新数据记录
    $start = '<div id="info">';
    $end = '<div class="clear">';
    $info = div_node($index, $start, $end, $start_num=1, $end_num=0);
    $info = $info . "</div></div>\r\n";
    $index_info = explode("\n", $info);
    foreach($index_info as $u){
        if(strpos($u, '<h1>') !== false) $bookname = trim(strip_tags($u));
        if(strpos($u, 'author') !== false) $author = trim(strip_tags($u));
        if(strpos($u, '2020') !== false){
            // $ut = preg_replace("/\s(?=\s)/","\\1",$u);
            $ut = trim(strip_tags($u));
            break;
        }
    }

    $start = '<div id="list">';
    $end = '</div>';
    $list = div_node($index, $start, $end, $start_num=1, $end_num=0);
    $list = str_replace('</dd>', "</dd>\r\n", $list);
    $index_array_temp1 = explode('</dt>', $list);
    $index_array_temp2 = explode("\n", $index_array_temp1[1]);
    $index_array = array_reverse($index_array_temp2);
    $list = '';
    foreach($index_array as $v){
        if(empty(trim($v))) continue;
        if(strpos($v, '.html')) $list .= trim($v);
        else continue;
    }
    $list = $index_array_temp1[0] . "</dt>\r\n" . $list . "</dl>\r\n</div>";
    $list = str_replace("'", '"', $list);
    $new_index = index_head($title, $bookname, $author, $id, $list);
    file_put_contents($base_path . $id . '/index.html', $new_index);
    echo " index.html 更新完毕.<br>\r\n";
    unset($index_array_temp1);
    unset($index_array_temp2);
    unset($index_array);
    unset($index_info);

    preg_match_all('/<a[^<]+href="(.+?)"/is', $list, $m);
    $remote = join($m[1]);
    echo "远端文件列表提取完成<br>\r\n";

/** ---------------源端index.html数据分析处理完毕---------------**/

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
    $remote = str_replace('.html', ".html ", $remote);
    $remote_new = chunk_split($remote, 140);
    // echo "更新的<b> $num 条</b>链接是:<p><pre>$remote_new</pre></p>\r\n";

/** ---------------本地与远端文件表比对完毕---------------**/

    # 更新该书章节，这里可修改提取的数据
    $array_new = explode(" ", $remote);
    $newurl = '';
    foreach($array_new as $key => $value){
        $value = preg_replace("/\s(?=\s)/", "\\1", $value);
        $value = trim($value);
        if(empty($value))continue;
        $new_url = $base_url . $id . '/' . $value;
        $new_fn = $base_path . $id . '/' . $value;
        $html = get_html($new_url, $charset='gbk');
        preg_match('/<title>(.*?)<\/title>/is', $html, $found);
        $title = $found[1];

        $start = '<div class="content_read">';
        $end = '<div class="footer">';
        $content = div_node($html, $start, $end, $start_num=1, $end_num=0);
        $array1 = explode('<div id="content" name="content">', $content);
        # 变量是否发布
        $array2 = explode('<div class="tjlist"', @$array1[0]);
        $body = @$array2[0] . '<div id="content" name="content">' . @$array1[1];
        $content = content_head($title, $bookname, $author, $id, $body);
        file_put_contents($new_fn, $content);
        
        $newurl .= $new_url . "\r\n";
        if($key == $n)break;
    }
    file_put_contents($base_path . 'newurl.txt', $newurl, FILE_APPEND);
    echo "=============<b>$id</b> 更新完毕=============<br><br><br>\r\n";

    file_put_contents($base_path . $fn, $ut);
}
echo "<br>=============<b> 全部更新完毕 =============</b><br><br>\r\n";


/** ---------------函数区--------------- **/

function get_html($url, $charset='gbk'){
    $path_parts = pathinfo($url);
    $refer = $path_parts['dirname'] . '/' . $_SERVER['PHP_SELF'];
    $option = array(
                    'http' => array('header' => "Referer:$refer"),
                    'ssl' => array('verify_peer' => false, 'verify_peer_name' => false,),
                    );
    $html = file_get_contents($url, false, stream_context_create($option));
    # 删除js
    $preg = "/<script[\s\S]*?<\/script>/i";
    $html = preg_replace($preg, "", $html, -1);
    $html = str_replace("https://www.xbiquge.cc", "", $html);
    if($charset == 'gbk') $html = mb_convert_encoding($html, "utf-8", "gbk");
    return $html;
}

# $start='<div id=??? >'; $end='</div>'; $start_num=1;
function div_node($html, $start, $end, $start_num, $end_num){
    $array_start = explode($start, $html);
    $array_end = explode($end, $array_start[$start_num]);
    $node = '';
    foreach($array_end as $key => $value ){
        $node .= $value . $end;
        if($key == $end_num) break;
    }
    return $start . $node;
}   

function index_head($title, $bookname, $author, $id, $body){
    $title = $bookname . ' &nbsp;_' . $author;
    $head = '<!DOCTYPE html><html lang="zh-CN"><head>' . "\r\n";
    $head .= '  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\r\n";
    $head .= '  <title>' . $title . " 新书_笔趣阁</title></head>\r\n<body>\r\n";
    
    $footer = '<div class="footer_cont">' . "\r\n";
    $footer .= '<p>小说<a href="https://www.xbiquge.cc/book/' . $id . '/">' . $bookname . '</a>';
    $footer .= '所有内容均来自互联网,笔趣阁只为原' . $author . "的小说进行宣传。</p>\r\n</div>";
    
    return $head . "<h3>" . $title . "</h3>\r\n" . $body . "\r\n" . $footer;
}   
  
function content_head($title, $bookname, $author, $id, $body){
    $head = '<!DOCTYPE html><html lang="zh-CN">' . "<head>\r\n";
    $head .= '  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\r\n";
    $head .= '  <link rel="stylesheet" type="text/css" href="/book/biqugecss/common.css" />' . "\r\n";
    $head .= '  <link rel="stylesheet" type="text/css" href="/book/biqugecss/read.css" />' . "\r\n";
    $head .= '  <title>' . $title . '</title>' . "</head>\r\n<body>\r\n";

    $footer = "\r\n" . '<p>小说<a href="https://www.xbiquge.cc/book/' .$id. '/">' . $bookname . '</a>';
    $footer .= '所有内容均来自互联网,笔趣阁只为原' . $author . "的小说进行宣传。</p>\r\n</div>";
    return $head . $body . $footer;
}

