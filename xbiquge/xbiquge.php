<?php
# 注释代码用 // ,注释说明用 #

set_time_limit(0);
date_default_timezone_set('Asia/Hong_Kong');

$id_str = '51888';

if(!empty($_GET["key"])) $id_str = $_GET["key"];
if(!is_numeric($id_str)) die($id_str . "只能是有效的x位数字");
$id = $id_str . '/';
$url = 'https://www.xbiquge.cc/book/' . $id;
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/book/';
$log = $base_path . 'log/';

if(!file_exists($base_path . $id)) mkdir($base_path . $id, 0755, true);
if(!file_exists($log)) mkdir($log, 0755, true);

/** ---------------以下部分可能无须设置--------------- **/

$charset = 'gbk';                   # 源站点编码
$div_list = 'div#list';             # 目录
$div_maininfo = 'div#maininfo';     # 书名,作者以及更新记录
$div_content = 'div.content_read';  # 章节内容

# 保存更新记录的文件名
$fn = $id_str . 'uf.log';
$bak_ufn = $log . '_' . $fn;
$ufn = $log . $fn;
if(!file_exists($ufn)) file_put_contents($ufn, '');
$uf = file_get_contents($ufn);

# 有前导 0
$t = date('Y-m-d', time());
$h = date('H', time());
if(strpos($uf, $t)){
    // $tt = file_get_contents($base_path . $id . 'index.html',NULL, NULL, 128, 54);
    $head_str = file_get_contents($base_path . $id . 'index.html', NULL, NULL, 0, 210);
    preg_match('/<title>(.*?)<\/title>/is', $head_str, $f);
    echo $head_str . "<h2>" . $f[1] . "</h2> >>>>>> 今天已经更新完毕 <<<<<< <br>\r\n";
    exit;
}
// if($h > 18) echo " >>>>>> 今天更新的很慢! <<<<<< <br>\r\n";

if(isset($_SERVER['HTTP_REFERER']) and (strpos($_SERVER['HTTP_REFERER'], 'cron.php') !== false)){
    echo "来自于 " . $_SERVER['PHP_SELF'] . "<br>\r\n";
}

/** ---------------设置部分完毕--------------- **/

# 获取源端index.html并重新排序后写入本地index.html
$res_array = getResponse($url);
// $charset = $res_array['charset'];
$html = $res_array['body'];

/** ---------------获取源端index.html数据完毕--------------- **/

# 书名,作者及更新数据记录
$main = getmaininfo($html, $div_maininfo, $charset);
if($charset == 'gbk') $main = mb_convert_encoding($main, "utf-8", "gbk");
$main_arr = explode("\n", $main);
foreach($main_arr as $u){
    if(strpos($u, '<h1>') !== false) $bookname = trim(strip_tags($u));
    if(strpos($u, 'author') !== false) $author = trim(strip_tags($u));
    if(strpos($u, '2020') !== false){
        // $ut = preg_replace("/\s(?=\s)/","\\1",$u);
        $ut = trim(strip_tags($u));
        break;
    }
}
unset($main_arr);
unset($res_array);

preg_match('/<title>(.*?)<\/title>/is', $html, $found);
$title = $found[1];
if($charset == 'gbk') $title = mb_convert_encoding($title, "utf-8", "gbk");
$title = $bookname . ' &nbsp;_' . $author . '新书_笔趣阁';

$head = '<!DOCTYPE html><html lang="zh-CN"><head>' . "\r\n";
$head .= '  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\r\n";
$head .= '  <title>' . $title . "</title></head>\r\n<body>";

$foot = '<div class="footer_cont">' . "\r\n";
$foot .= '<p>小说<a href="https://www.xbiquge.cc/book/51687/">' . $bookname . '</a>';
$foot .= '所有内容均来自互联网,笔趣阁只为原' . $author . "的小说进行宣传。</p>\r\n</div>";

echo $head . "<h3>" . $title . "</h3><pre><font size='4'>";

/** ---------------书名,作者及更新记录处理完毕--------------- **/

# 扫描本地目录中的文件
$local_dir_array = scandir($base_path . $id);
$local_dir_array = array_diff($local_dir_array, [".", "..", "index.html"]);
file_put_contents($log . $id_str . 'all.log', print_r($local_dir_array, true));
echo "<b>File Source: <a href=$url>$url</a></b>.<br>\r\n";
echo "Directory read complete.<br>\r\n";

# 从本地 index.html 中提取所有超链接及最新的一个链接
if(!file_exists($base_path . $id . 'index.html')){
    echo $base_path . $id . "index.html file does not exist.<br>\r\n";
    file_put_contents($base_path . $id . 'index.html', '');
}
$local_index = file_get_contents($base_path . $id . 'index.html');
preg_match_all('/<a[^<]+href="(.+?)"/is', $local_index, $local_link);
#  $local_link[1][0] 是否发布
$local_index_last_link = $url . @$local_link[1][0] . "\r\n";
echo "Latest links in local index.html:<br>\r\n" . trim($local_index_last_link) . "<br>\r\n";
echo "本地" . $uf . "<br>\r\n";
echo "网络" . $ut . "<br>\r\n";

$local_diff = array_diff($local_link[1], $local_dir_array);
if(!empty($local_diff)){
    echo "<b>错误：本地文件与本地index.html中的链接存在差异<b>.<br>\r\n";
    $local_diff_str = join($local_diff);
    $local_diff_str = str_replace('.html', ".html\r\n", $local_diff_str);
    echo $local_diff_str . "<br>";
}

# $local_index_last_link,$local_dir_array,$local_link[1]
/** ---------------本地数据处理完毕--------------- **/

if($ut !== $uf){
    // @rename($ufn, $bak_ufn);
    file_put_contents($ufn, $ut);
}
else die("All files are already up-to-date.无需更新<br>\r\n");;

// $remote_index = caiji_info($html, $chaset, $div);
$remote_index = getmaininfo($html, $div_list, $charset);
if($charset == 'gbk') $remote_index = mb_convert_encoding($remote_index, "utf-8", $charset);
$remote_index = $head . $remote_index;
$remote_index = str_replace("'", '"', $remote_index);
$remote_index_array_temp1 = explode('</dt>', $remote_index);
$remote_index_array_temp2 = explode("\n", $remote_index_array_temp1[1]);
$remote_index_array = array_reverse($remote_index_array_temp2);
$remote_index_str = join($remote_index_array);
$remote_index_str = $remote_index_array_temp1[0] . '</dt>' . $remote_index_str . "\r\n</dl>";
$remote_index_str = str_replace('</dd><dd>', "</dd>\r\n<dd>", $remote_index_str);
$remote_index_str = str_replace('</dt></dl>', "</dt>\r\n", $remote_index_str);
$remote_index_str = str_replace("<dl>\n", "\r\n<dl>", $remote_index_str);
file_put_contents($base_path . $id . 'index.html', $remote_index_str);
echo "The index.html file has been sussessfully updated.<br>\r\n";

# 本地文件和源端index.html中差异的超链接将保存到文件
preg_match_all('/<a[^<]+href="(.+?)"/is', $remote_index_str, $remote_link);
$remote_diff = array_diff($remote_link[1], $local_dir_array);
$diff_link = '';
if(!empty($remote_diff)){
    foreach($remote_diff as $remote_diff_str){
        if(empty($remote_diff_str)) continue;
        $diff_link .= $url . $remote_diff_str . "\r\n";
    }
}
if(empty($diff_link)) die("All files are already up-to-date.无需更新<br>\r\n");
echo "The following links will be UPDATED:<br>\r\n" . $diff_link . "<br>\r\n";
file_put_contents($log . $id_str . 'newurl.log', $local_index_last_link . $diff_link);

# 由于首页没有探测到文件名,而且代码处理不一致,故不能将网站首页加入到文件中

/** ---------------源端index.html数据分析处理完毕---------------**/

# 根据index.html中提取的连接更新章节内容
$list = file_get_contents($log . $id_str . 'newurl.log');
if(empty($list)) die("new url is empty,可能无需更新<br>\r\n");
else echo "The following links will be UPDATED:<br>\r\n";

$array = explode("\r\n", $list);
foreach($array as $url){
    echo $url . "\r\n";
    $fn = basename($url);
    $new = $base_path . $id . $fn;
    if(empty($url)) continue;
    $body = caiji_content($url, $charset, $div_content);
    $array1 = explode('<div id="content" name="content">', $body);
    # 变量是否发布
    $array2 = explode('<div class="tjlist"', @$array1[0]);
    $body = @$array2[0] . '<div id="content" name="content">' . @$array1[1];
    # 删除js
    $preg = "/<script[\s\S]*?<\/script>/i";
    $html = preg_replace($preg, "", $body, -1);
    $html = str_replace("https://www.xbiquge.cc", "", $html);
    $html .= $foot;
    file_put_contents($new, $html);
}
echo "Sussessfully updated<br>\r\n";
file_put_contents($log . $id_str . 'newurl.log', '');

/** ---------------章节内容更新完毕--------------- **/

@unlink($log . $id_str . 'all.log');
@unlink($log . $id_str . 'newurl.log');



/** ---------------函数区--------------- **/

function caiji_content($url, $charset, $div){
    if(empty($url)) die('url is empty');
    if(empty($charset)) $charset = 'gbk';
    if(empty($div)) $div = 'div#maininfo';
    require_once('./lib/phpQuery/phpQuery.php');
    phpQuery :: $defaultCharset = $charset;
    $html = phpQuery :: newDocumentFile($url);
    // $html = phpQuery::newDocument($html);
    $title = pq("title") -> text();
    $maininfo = pq($div) -> html();
    if($charset == 'gbk') $maininfo = mb_convert_encoding($maininfo, 'utf-8', 'GBK');
    $head = '<!DOCTYPE html><html lang="zh-CN">' . "<head>\r\n";
    $head .= '  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\r\n";
    $head .= '  <link rel="stylesheet" type="text/css" href="/book/biqugecss/common.css" />' . "\r\n";
    $head .= '  <link rel="stylesheet" type="text/css" href="/book/biqugecss/read.css" />' . "\r\n";
    $head .= '  <title>' . $title . '</title>' . "</head>\r\n<body>\r\n";
    $output = $head . $maininfo;
    return $output;
}

function getmaininfo($html, $div, $charset){
    require_once('./lib/phpQuery/phpQuery.php');
    phpQuery :: $defaultCharset = 'utf-8';
    //phpQuery :: $defaultCharset = $charset;
    $html = phpQuery :: newDocumentHTML($html);
    $title = pq("title") -> text();
    $maininfo = pq($div) -> html();
    return($maininfo);
}

# $res_array = array();
# $res_array['header']    = $header_all;
# $res_array['status']    = $status[1];
# $res_array['mime_type'] = $mime_type;
# $res_array['charset']   = $charset;
# $res_array['body']      = $body;

# 支持GET和POST,返回值网页内容，报头，状态码，mime类型和编码 charset
function getResponse($url, $data = [], $cookie_file = ''){

    $url_array = parse_url($url);
    $host = $url_array['scheme'] . '://' . $url_array['host'];
    if(!empty($_SERVER['HTTP_REFERER'])) $refer = $_SERVER['HTTP_REFERER'];
    else $refer = $host . '/';
    if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    else $lang = 'zh-CN,zh;q=0.9';
    if(!empty($_SERVER['HTTP_USER_AGENT'])) $agent = $_SERVER['HTTP_USER_AGENT'];
    else $agent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.67 Safari/537.36';
    // $agent = 'Wget/1.18 (mingw32)'; # 'Wget/1.17.1 (linux-gnu)';
    // echo "<pre>\r\n" . $agent . "\r\n" . $refer . "\r\n" . $lang . "\r\n\r\n";
    $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.117 Safari/537.36';
    if(empty($cookie_file)){
        $cookie_file = '.cookie';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_REFERER, $refer);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept-Language: " . $lang));
    if(!empty($data)){
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);   # 302 重定向
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);      # 301 重定向

    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);  # 取cookie的参数是
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); # 发送cookie

    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);

    # try{}catch{}语句
    // try{
    //     $handles = curl_exec($ch);
    //     curl_close($ch);
    //     return $handles;
    // }
    // catch(Exception $e){
    //     echo 'Caught exception:', $e -> getMessage(), "\n";
    // }
    // unlink($cookie_file);

    $res_array = explode("\r\n\r\n", $result, 2);
    $headers = explode("\r\n", $res_array[0]);
    $status = explode(' ', $headers[0]);
    # 如果$headers为空，则连接超时
    if(empty($res_array[0])) die('<br><br><center><b>连接超时</b></center>');
    # 如果$headers状态码为404，则自定义输出页面。
    if($status[1] == '404') die("<pre><b>找不到，The requested URL was not found on this server.</b>\r\n\r\n$res_array[0]</pre>\r\n\r\n");
    # 如果$headers第一行没有200，则连接异常。
    # if($status[1] !== '200') die("<pre><b>连接异常，状态码： $status[1]</b>\r\n\r\n$res_array[0]</pre>\r\n\r\n");\

    if($status[1] !== '200'){
        $body_array = explode("\r\n\r\n", $res_array[1], 2);
        $header_all = $res_array[0] . "\r\n\r\n" . $body_array[0];
        $res_array[0] = $body_array[0];
        $body = $body_array[1];
    }else{
        $header_all = $res_array[0];
        $body = $res_array[1];
    }

    $headers = explode("\r\n", $res_array[0]);
    $status = explode(' ', $headers[0]);
    
    $headers[0] = str_replace('HTTP/1.1', 'HTTP/1.1:', $headers[0]);
    foreach($headers as $header){
        if(stripos(strtolower($header), 'content-type:') !== FALSE){
            $headerParts = explode(' ', $header);
            $mime_type = trim(strtolower($headerParts[1]));
            //if(!empty($headerParts[2])){
            //    $charset_array = explode('=', $headerParts[2]);
            //    $charset = trim(strtolower($charset_array[1]));
            //}
        }
        if(stripos(strtolower($header), 'charset') !== FALSE){
            $charset_array = explode('charset=', $header);
            $charset = trim(strtolower($charset_array[1]));
        }else{
            $charset = preg_match("/<meta.+?charset=[^\w]?([-\w]+)/i", $res_array[1], $temp) ? strtolower($temp[1]):"";
        }
    }
    if(empty($charset)) $charset = 'utf-8';
    if(strstr($charset, ';')){
        $charset_array = '';
        $charset_array = explode(';', $charset);
        $charset = trim($charset_array[0]);
        //$charset = str_replace(';', '', $charset);
    }
    if(strstr($mime_type, 'text/html') and $charset !== 'utf-8'){
        //$body = mb_convert_encoding ($body, 'utf-8', $charset);
    }
    # $body = preg_replace('/(?s)<meta http-equiv="Expires"[^>]*>/i', '', $body);    
    
    # echo "<pre>\r\n$header_all\r\n\r\n" . "$status[1]\r\n$mime_type\r\n$charset\r\n\r\n";
    # header($res_array[0]);

    $res_array = array();
    $res_array['header']    = $header_all;
    $res_array['status']    = $status[1];
    $res_array['mime_type'] = $mime_type;
    $res_array['charset']   = $charset;
    $res_array['body']      = $body;
    return $res_array;
}

?>