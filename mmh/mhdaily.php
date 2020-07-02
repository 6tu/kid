<?php
/**
 * Get-MMH-Daily -- Get the PHP proxy script of Minghui.org Daily News.
 * 
 * PHP Version 5.5+
 * php extension library : zip,curl,openssl
 * 
 * $date 是文件发布日期，$time 是系统当前时间戳
 * date()接受 年-月-日 和 月/日/年 这种格式
 * 数组以 $array_ 开头, 文件名以 $fn_ 开头, 网址以 $url_ 开头,
 * 不带文件名的路径以 $path_ 开头,带文件名的路径以 $file_ 开头,
 * 
 * 文件修改日期遵循 GMT时间
 * 
 */


// highlight_file("caiji-mmh.php");
ignore_user_abort();
set_time_limit(0);
error_reporting(1);
date_default_timezone_set('America/New_York');
// echo date("Y-n-j", time());

$ext = array_flip(get_loaded_extensions());
if(empty($ext['curl']))    die("不支持 curl <br>\r\n");
if(empty($ext['openssl'])) die("不支持 openssl <br>\r\n");
if(empty($ext['zip']))     die("不支持 zip <br>\r\n");

# 用浏览器访问
if(empty($_GET['mhdaily']) and !strstr($_SERVER['HTTP_USER_AGENT'], 'Wget')) die(form_html());

# 上传到七牛对象存储
$up2qiniu = true; # true为发送,false为不发送

$host = base64_decode('aHR0cDovL20ubWluZ2h1aS5vcmc=');
$url_base = $host . '/mh/articles/';

$mhdata = 'mhdata/';
$cwd = getcwd();
if(!is_dir($cwd.'/'.$mhdata)) mkdir($cwd.'/'.$mhdata, 0777, true);
$url_loc = $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) .'/';

ob_end_flush();//关闭清空缓存

// echo "<br>正在检测远程文件 <br><pre>\r\n";

# 由GET变量传递的文件名和URL
if(isset($_GET['mhdaily'])){
    $fn = $_GET['mhdaily'];
    $regex="'\d{4}-\d{1,2}-\d{1,2}-t.zip'is";
    preg_match_all($regex, $fn, $matches);
    // print_r($matches[0]);
    if(empty($matches[0])) die("<br>文件名格式不匹配 . <br>\r\n");

    $date = substr($fn, 0, -6); # 文件名除去后缀
    if(!strtotime($date)) die("<br>日期格式不匹配 . <br>\r\n");
}else{
    $date = date("Y-n-j", time());
}
$year =  date("Y", strtotime($date));
$month = date("n", strtotime($date));
$day =   date("j", strtotime($date));
$monthday = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$path_date = $year .'/'. $month .'/'. $day .'/';
$fn_src = $date .'-t.zip';
$fn_src_info = $fn_src .'.log';
$url_src = $url_base . $path_date . $fn_src;
$file_src = 'mh/articles/' . $path_date . $fn_src;
$fn_out = substr(md5($date), 8, 16) . '.zip';
$url_out = $url_loc . $mhdata . $fn_out;

# 控制文件更新频率
$time = time();
if(file_exists($mhdata . $fn_src_info)){
    $src_info_time = filemtime($mhdata . $fn_src_info);
    $time_diff = round(($time - $src_info_time)/60, 1);
    if(($time_diff) < 31){
        die("<br><b>".$time_diff. "</b> 分钟前更新 " .$url_out. "<br>\r\n");
    }
    $old_src_info = file_get_contents($mhdata . $fn_src_info);
    $old_src_info = str_replace(array('[', '] => ', "\n",), array("'", "' => '", "',\n",), $old_src_info);
    $old_src_info = str_replace(array("Array',\n(',", ")',"), array("Array(", ")"),$old_src_info);
    // eval("\$arr = ".$s.'; ');
    $array_info = eval("return $old_src_info;");
    $old_modified = trim($array_info['Last-Modified']);
}else{
    $old_src_info = '';
    $old_modified = '';
}

stream_context_set_default(
    array(
        'http' => array(
            'method' => 'GET',
            'timeout' => (float)0.5,
        ),
        'ssl' => array(
            'verify_host' => false,
            'verify_peer' => false,
            'verify_peer_name' => false,
        )
    )
);
$src_headers = get_headers($url_src, 1);
if(!strpos($src_headers[0], '200')) die("<br> $src_headers[0] 未找到或者尚未发布 . <br>\r\n");
// print_r($url_headers);
# 如果[Last-Modified]不匹配，则需要更新
if(!empty($src_headers['Content-Length'])) $length = $src_headers['Content-Length'];
else $length = '';
// https://www.php.net/manual/zh/datetime.createfromformat.php
if(!empty($src_headers['Last-Modified'])){
    $modified = $src_headers['Last-Modified'];
    $fmt = 'D, d M Y H:i:s O+';
    $datetime = DateTime::createFromFormat($fmt, $modified);
    $modi_ts = $datetime -> getTimestamp();
    $modi_td = $datetime -> format('Y-m-d H:i:s');
}else $modified = '';

if($modified === $old_modified){
    touch($mhdata . $fn_out);
    touch($mhdata . $fn_src_info);
    die("<br>文件已是最新 " . $url_out . "<br>\r\n");
}
if(file_exists($mhdata . $fn_out) and ($time - filemtime($mhdata . $fn_out)) < 1800){
    die('<br>1小时内的更新文件 ' . $url_out . "<br>\r\n");
}

echo "\r\n</pre><br>正在离线下载 <br><pre>\r\n";

$file_index = '/mmh/articles/' .$path_date. 'index.html';
$url = $host . $file_index;
$file = make_path($url);
$array_res = getResponse($url, $data = [], $cookie_file = '', $progress=true);
$html = $array_res['body'];
file_put_contents($file, $html);
$header_all = $array_res['header'];
$array_header = headers_string2array($header_all);
$modi_ts = header_last_modified($array_header);
touch($file, $modi_ts);
$array_url_one = array_unique(preg_htmllink($html));

$array_url_two = array();
foreach($array_url_one as $url_1){
    if(empty($url_1)) continue;
    if(empty(parse_url($url_1, PHP_URL_SCHEME)) or empty(parse_url($url_1, PHP_URL_HOST))){
        $url_1 = parse_url($url_1, PHP_URL_PATH);
        $url_1 = $host . $url_1;
    }else echo $url_1 . "<br>\r\n";
    $url_1 = trim($url_1);
    $file = make_path($url_1);
    $array_res = getResponse($url_1, $data = [], $cookie_file = '', $progress=true);
    $html = $array_res['body'];
    file_put_contents($file, $html);
    $header_all = $array_res['header'];
    $array_header = headers_string2array($header_all);
    $modi_ts = header_last_modified($array_header);
    touch($file, $modi_ts);
++$i;
echo '.';
flush();
clearstatcache();
    if(strpos($url_1, '.html') !== false and strpos($url_1, '/mh/articles/') !== false){
        $daily = $url_1;
        $array_url_x = array_unique(preg_htmllink($html));

        # 只提取图片链接,所有图片在 /mh 目录
        foreach($array_url_x as $url_2){
            if(strpos($url_2, '/mh') !== false) $array_url_two[] .= $url_2;
        }
    }
    unset($array_url_x);
}
echo " $i <br>\r\n";

foreach($array_url_two as $url_2){
    if(empty($url_2)) continue;
    if(empty(parse_url($url_2, PHP_URL_SCHEME)) or empty(parse_url($url_2, PHP_URL_HOST))){
        $url_2 = parse_url($url_2, PHP_URL_PATH);
        $url_2 = $host . $url_2;
    }else echo "<br>" . $url_2 . " <b> 外部链接</b><br>\r\n";
    $url_2 = trim($url_2);
    $file = make_path($url_2);
    $array_res = getResponse($url_2, $data = [], $cookie_file = '', $progress=true);
    $html = $array_res['body'];
    file_put_contents($file, $html);
    $header_all = $array_res['header'];
    $array_header = headers_string2array($header_all);
    $modi_ts = header_last_modified($array_header);
    touch($file, $modi_ts);
++$ii;
echo '.';
flush();
}
echo " $ii <br>\r\n";

$n = $i + $ii;
$index_fn = basename($daily);
echo "<b>$index_fn 中 $n 个文件下载完毕</b>  \r\n\r\n";

$src_md5 = md5_file($file_src);
// echo $file_src .' '. $src_md5;
$filename = '[File-Name] => '. $fn_src;
$modified = '[Last-Modified] => '. $modified;
$length   = '[Content-Length] => '. $length;
$filemd5  = '[File-MD5] => '. $src_md5;
$src_info = "Array\n(\n" . $filename ."\n". $modified ."\n". $length ."\n" . $filemd5 ."\n" . ")\n";
file_put_contents($mhdata . $fn_src_info, $src_info);

sleep(1);
# 打包压缩,加密，压缩
$basename = strstr($index_fn, '.', true);
$fn_zip = $basename . '.zip';
$fn_p7m = $fn_zip . '.p7m';
if(file_exists($fn_zip)) unlink($fn_zip);

$array_url_all = array_unique(array_merge($array_url_two, $array_url_one));
$array_url_all[] .= '/pub/mobile.css';
$array_url_all[] .= $file_index;
// file_put_contents('url.log', print_r($array_url_all, true));
// print_r($array_url_one);  print_r($array_url_two);

foreach($array_url_all as $url){
    $file = trim(parse_url($url, PHP_URL_PATH));
    $file = ltrim($file, '/');
    zip_file($file, $fn_zip);
    if(!strpos($file, '.css')) unlink($file);
    $dir = './'.dirname($file);
    if(is_dir($dir) and count(scandir($dir))==2) rmdir($dir);
}
echo "$fn_zip 打包完成, 源文件被释放 <br>\r\n";

sleep(1);
echo pkcs7_encrypt($fn_zip, $fn_p7m);
echo zip_file($fn_p7m, $mhdata . $fn_out);

echo "压缩包地址 " . $url_out ."<br>\r\n";

if($up2qiniu) upload2qiniu($mhdata . $fn_out, $fn_out);

echo "</pre><br>\r\n";

unlink($fn_zip);
unlink($fn_p7m);
delDirAndFile('mh');
delDirAndFile('mmh');















/** =========函数区========= */

function form_html(){
    $time = date("Y-n-j", time());
    $fn = $time . '-t.zip';
    $html = "<body><br><center>\r\n";
    $html .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="GET" />' . "\r\n";
    $html .= "  <b>?mhdaily= </b>\r\n";
    $html .= '  <input type="text" name="mhdaily" size=20 value="'.$fn.'" />'."\r\n";
    $html .= '  <input type="submit" value="Send" />'."\r\n";
    $html .= "</form>\r\n</center></body>";
    echo $html;
}

function make_path($url){
    $cwd = getcwd();
    $url_info = parse_url($url);
    $path_info = pathinfo($url_info['path']);
    $path = $cwd . $path_info['dirname'];
    $fn = $path_info['basename'];
    if(!is_dir($path))mkdir($path, 0777, true);
    return $path . '/' . $fn;
}

# 循环删除目录和文件函数
function delDirAndFile($dirName){
    if($handle = opendir($dirName)){
        while(false !== ($item = readdir($handle))){
            if($item != "." && $item != ".."){
                if(is_dir("$dirName/$item")){
                    delDirAndFile("$dirName/$item");
                }else{
                    if(unlink("$dirName/$item")) return "成功删除文件：$dirName/$item <br/>\r\n";
                }
            }
        }
        closedir($handle);
        if(rmdir($dirName)) return "成功删除目录：$dirName <br/>\r\n";
    }
}

# 获取网页中超链接的两种方法
function preg_htmllink($html){
    $html = preg_replace('/\s{2,}|\n/i', '', $html); # 过滤掉换行和2个以上的空格
    preg_match_all('/(?:img|a|source|link|script)[^>]*(?:href|src)=[\'"]?([^>\'"\s]*)[\'"]?[^>]*>/i', $html, $out);
    return($out[1]);
}

function zip_file($txtname, $zipname){
    if(false !== function_exists("zip_open")){
        $zip = new ZipArchive();
        if($zip -> open($zipname, ZIPARCHIVE :: CREATE) !== TRUE){
            exit("can not open <$zipname>\n");
        }
        $zip -> addFile($txtname);
        $zip -> close();
    }else{
        # include('zip.class.php');
        $test = new zip_file($zipname);
        $test -> add_files(array($txtname));
        $test -> create_archive();
    }
    return ".zip successfully created<br>\r\n";
}

# 加密函数 win则须绝对路径
function pkcs7_encrypt($infile, $outfile){
    $domain = randkey($len=6) . '.com';
    # $domain 按证书类型需要特别指定而非任意赋值
    $headers = array("To" => "info@" . $domain,
        "From" => "webmaster <postmaster@" . $domain,
        "Reply-to" => "support@" . $domain,
        "Subject" => "Daily News ",
        "Date" => date("r"),
        "X-Mailer" => "By news (PHP/" . phpversion() . ")");
    $cert = '
-----BEGIN CERTIFICATE-----
MIIFczCCA1ugAwIBAgIBAjANBgkqhkiG9w0BAQsFADA/MQswCQYDVQQGEwJDTjEX
MBUGA1UECgwOTGl1eXVuIE5ldHdvcmsxFzAVBgNVBAMMDkxpdXl1biBSb290IENB
MB4XDTIwMDYyNDE4MDYzOFoXDTMwMDYyNTE4MDYzOFowQTELMAkGA1UEBhMCQ04x
EjAQBgNVBAMMCXNtaW1lLnA3bTEeMBwGCSqGSIb3DQEJARYPZGFpbHlAc21pbWUu
cDdtMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAvI2hpjyWgfVXYtOu
H2X9LqMfRQ75TMN7YTPv8VKgrCgGDTw6Za0pHvErcJgUOS6os3lOXasZb5dk4WG2
9vHzKUgDehxzManALGClL14HploFkshvEPwfOKN7kLe2KkEwNc0eFHaOLx6+AN/H
uk8wUfnjwY/A07CCx+T+4L2KzV4r1dN8CYPQUPK2uM+5XreF/taZYDMJw7SWDSZf
Oh497iRW8ofFKN0SMUTNljEa6CeOgoUbzCdFiv/qh0lTdC++zwA3evmVq7VJynna
7aWVBoQiClJLNkMvPoLucklJylYmUasA5QxknekWa5vReUc1zxuke/ONh/52yQ1W
iKvvfgAkGMeLw+boNX5QBDmbPPxeBQDzcktf6a9zGAS2KLwstwa/wsIZ7sq+dmIA
a9nCvQHMNk3JMbSVEDRkjEnTYDmWMjbYQ7ifyj+RO0wmY+23EXqYeuktqJKb7zmo
muHT6Cng36YGPOl8wuSJlbSnb85V+iN3MaYR3ZMbKh80XqZ1oWgmm2MHwSHsHNox
gHxEWl7ZhAnetAvIRCTfO/nEs+w39PSY+4JNpQW+Msdjl+gQ59kmYuek/nkvjHUs
KYv/9STQ1+Cd+YL7qxJpmh1n0wPrYDumd/MNV1IhKLU5c4IO4LfbNADbueUXSAZu
UzAQfOs2t012DmJF4n0iKi422TUCAwEAAaN4MHYwCQYDVR0TBAIwADALBgNVHQ8E
BAMCBeAwOwYDVR0lBDQwMgYIKwYBBQUHAwEGCCsGAQUFBwMCBggrBgEFBQcDAwYI
KwYBBQUHAwQGCCsGAQUFCAICMB8GA1UdEQQYMBaCCXNtaW1lLnA3bYIJZGFpbHku
cGhwMA0GCSqGSIb3DQEBCwUAA4ICAQBJjtCmIdV88nB2JgGTmHXbNO36FoGw2ZZg
pd+am1braBM9goKPiIPxn5UbGKSuTStq6mh9qnAgbbKgpQOXgCHu6PRnxcvSP64o
6w2h1GIgzAAexXTBg7tgUhWTe7sL41D7NWjd+W6CkvMTZUX52zNgRXYhH0Oc6+lt
xyM1yW7Ve0SEeL1Ho3bdDVHlGzUBbBceem34qK3DOw6ZOOaIzmaWR3q1rhAzyCXW
vbnf0EHuBCZue3rOdSYStzp9/3Zx6hdm3+WcFc9jOVJxLa1gqNkLyd/0hGLyX4XN
vOV8uLVJNAMlpzQoE2IyZkbANejlFDAG4CfusjK0t2TLchzqn2h9uvnmcks3Ab0b
JccsJZloiKcmDGHGJYJmvrDWQjoZ1EjSoVMaq+qJx6dR4wGI77aIF5YW3NDsOjnC
rRjHdCP6QU9mZ2SYJB9vMP2YR0eaIqH+VbMPqy3oD9ZXPqLcJ7MpYTYYqZXDM2nk
P3SjevfqxUNeNjhEm+8lmLPrnoA1ADJ8RND10TaCzlmEnetKKzQw4EkJRpOzraNc
3F6o7OZmIG03QPWPxl4WDzn6tN3tZcv0R71s/lcfbSgeqRi9qjKIOlyrujhzN3DP
No00f8AsulitJezWuObMZniFI2/OnlgcfJ73fexZqM51ucTaY+p5Eu0X0/PpTIlk
9TPpqtc1pQ==
-----END CERTIFICATE-----
';

    $cwd = getcwd();
    if(openssl_pkcs7_encrypt($infile, $outfile, $cert, $headers, PKCS7_BINARY)){
        return ".p7m successfully created <br>\r\n";
    }else return "Encryption failed <br>\r\n";
    # unlink($enc);
}

# 支持GET和POST,返回数组含['header']['status']['mime_type']['charset']['body']
# $progress=true 则表示不显示下载进度
function getResponse($url, $data = [], $cookie_file = '', $progress=true){

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
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progress');
    curl_setopt($ch, CURLOPT_NOPROGRESS, $progress); //false表示用进度条

    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
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
    if(empty($res_array[0])) echo("<br>'.$url.'<b> 连接超时</b><br>\r\n");
    # 如果$headers状态码为404，则自定义输出页面。
    if($status[1] == '404') echo("<br>$url <b>Not Found</b><br>\r\n");
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
    if(empty($mime_type)) $mime_type = '';
    if(strstr($mime_type, 'text/html') and $charset !== 'utf-8'){
        $body = mb_convert_encoding ($body, 'utf-8', $charset);
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

function progress($resource, $dl_size, $dl, $up_size, $up){
    # 最好是开启session，让其记忆次数 
    if(!defined('tmp_log')){
        @define("tmp_log", randkey($len=8) . '.log');
    }
    $tmp = tmp_log;

    if($dl_size > 0){
        if(!file_exists($tmp)){
            $i = 0;
            file_put_contents($tmp, 1);
        }else{
            $i = file_get_contents($tmp);
            $i = intval($i) + 1;
            file_put_contents($tmp, $i);
        }
        $decimal = $dl / $dl_size * 100;
        $decimal = round($decimal, 2);
        $per = $decimal . "%";
        echo '.';
        if(is_int($i/100) and ($i/100) > 0) echo " $per <br>\r\n";
        if($decimal == 100) unlink($tmp);
    }
    ob_flush();
    flush();
    //sleep(1);
}

function headers_string2array($res_header){
    $headers = array();
    $res_header = trim($res_header);
    if(strpos($res_header, "\r\n\r\n")){
        // $header_text = substr($res_header, 0, strpos($res_header, "\r\n\r\n"));
        $header_text = substr($res_header, strpos($res_header, "\r\n\r\n"));
    }else{
        $header_text = $res_header;
    }
    $header_text = trim($header_text);

    foreach(explode("\r\n", $header_text) as $i => $line){
        if($i !== 0 and !strpos($line, ":")) continue;
        if($i === 0) $headers['http_code'] = $line;
        else{
            list($key, $value) = explode(':', $line, 2);
            $headers[$key] = $value;
        }
    }
    return $headers;
}

function header_last_modified($array_header){
    if(!empty($array_header['Last-Modified'])){
        $modified = trim($array_header['Last-Modified']);
        $fmt = 'D, d M Y H:i:s O+';
        $datetime = DateTime::createFromFormat($fmt, $modified);
        $modi_ts = $datetime -> getTimestamp();
        $modi_td = $datetime -> format('Y-m-d H:i:s');
    }else{
        $modified = '';
        $modi_ts = time();
        $modi_td = time();
    }
    // echo $modified;
    return $modi_ts;
}

function randkey($len){
    $str = "abcdefghijklmnopqrstuvwxyz1234567890";
    $key = substr(str_shuffle($str), 6, $len);
    return $key;
}

# 上传文件到七牛云对象存储服务器
function upload2qiniu($filePath, $key){
    $qiniupara = array(
        'qiniupath' => __DIR__ . '/qiniu/upload_to_qiniu.php',
        'qiniuurl' => 'http://80luir.s3-cn-south-1.qiniucs.com/',
        'accessKey' => 'KzBWtGa-Qsxd2zA_SbYkcxi9Evw0fRNgQY5ax9T6',
        'secretKey' => 'F8JQ4riqVfQmgCyDWya9Oi5TYBtNpOKBToYUxEyh',
        'bucket' => 'statics',
        );

    $reqpath   = $qiniupara['qiniupath'];
    $puburl    = $qiniupara['qiniuurl'];
    $accessKey = $qiniupara['accessKey'];
    $secretKey = $qiniupara['secretKey'];
    $bucket    = $qiniupara['bucket'];

    require_once $reqpath;
    // $filePath                              #本地文件
    // $key = $p7m_zip;                       #上传到七牛后保存的文件名

    if($err !== null){
        var_dump($err);
    }else{
        // var_dump($ret);
        echo 'Successfully uploaded to <a href="' . $puburl . $ret['key'] . '">qiniu</a>';
    }
}





















/* * 废弃的备用函数* */

# 获取网页中超链接
# PHP DOM XPath获取HTML节点方法大全
# https://www.awaimai.com/2113.html
function dom_htmllink($html){
    $dom = new DOMDocument();
    @$dom -> loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    # 获取 css 链接
    $nodeList = $xpath -> query("//link");
    $css = [];
    foreach ($nodeList as $node){
        $css[] = $node -> attributes -> getNamedItem('href') -> nodeValue;
    }
    # 获取 js 链接
    $nodeList = $xpath -> query("//script");
    $js = [];
    foreach ($nodeList as $node){
        $js[] = @$node -> attributes -> getNamedItem('src') -> nodeValue;
    }
    # 获取 mp4 链接
    $nodeList = $xpath -> query("//source");
    $mp4 = [];
    foreach ($nodeList as $node){
        $mp4[] = $node -> attributes -> getNamedItem('src') -> nodeValue;
    }
    # 获取 img 链接
    $nodeList = $xpath -> query("//img");
    $img = [];
    foreach ($nodeList as $node){
        $img[] = $node -> attributes -> getNamedItem('src') -> nodeValue;
    }
    # 获取 htm 链接
    $nodeList = $xpath -> query("/html/body//a");
    // for ($i = 0; $i < $hrefs -> length; $i++) {
    //     $href = $hrefs -> item($i);
    //     $url = $href -> getAttribute('href');
    //     echo $url . "\r\n";
    // }
    $htm = [];
    foreach ($nodeList as $node){
        $htm[] = $node -> attributes -> getNamedItem('href') -> nodeValue;
    }
    
    $link = array_merge($css, $js, $mp4, $img, $htm);
    shuffle($link);
    foreach ($link as $key => $val){
        if (empty($val)) continue;
        $links[] = $val;
    }
    print_r($links);
}

function getlink($html){
    $array_url = array();
    $dom = new DOMDocument();
    @$dom -> loadHTML($html);
    $xpath = new DOMXPath($dom);
    $hrefs = $xpath -> evaluate("/html/body//a");
    for($i = 0;$i < $hrefs -> length;$i++){
        $href = $hrefs -> item($i);
        $url = $href -> getAttribute('href');
        $array_url[] .= $url;
        // echo $url . "<br/>\r\n";
    }
    return array_unique($array_url);
}

function unzip_file($file, $destination){
    $zip = new ZipArchive();
    if($zip -> open($file) !== TRUE) die('Could not open archive');
    $zip -> extractTo($destination);
    $zip -> close();
    return " Archive extracted to directory <br><br>\r\n";
}

# 加密函数
# https://www.php.net/manual/zh/openssl.pkcs7.flags.php
# https://www.php.net/manual/zh/openssl.ciphers.php
# https://www.php.net/manual/zh/function.openssl-pkcs7-encrypt.php
## $path_type = 1 为相对路径，0 为绝对路径
function pkcs7_decrypt($infile_p7m, $path_type = '1'){
    $pw = '';
    $key = '-----BEGIN RSA PRIVATE KEY-----
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
-----END RSA PRIVATE KEY-----
';
    $cert = '-----BEGIN CERTIFICATE-----
MIIFczCCA1ugAwIBAgIBAjANBgkqhkiG9w0BAQsFADA/MQswCQYDVQQGEwJDTjEX
MBUGA1UECgwOTGl1eXVuIE5ldHdvcmsxFzAVBgNVBAMMDkxpdXl1biBSb290IENB
MB4XDTIwMDYyNDE4MDYzOFoXDTMwMDYyNTE4MDYzOFowQTELMAkGA1UEBhMCQ04x
EjAQBgNVBAMMCXNtaW1lLnA3bTEeMBwGCSqGSIb3DQEJARYPZGFpbHlAc21pbWUu
cDdtMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAvI2hpjyWgfVXYtOu
H2X9LqMfRQ75TMN7YTPv8VKgrCgGDTw6Za0pHvErcJgUOS6os3lOXasZb5dk4WG2
9vHzKUgDehxzManALGClL14HploFkshvEPwfOKN7kLe2KkEwNc0eFHaOLx6+AN/H
uk8wUfnjwY/A07CCx+T+4L2KzV4r1dN8CYPQUPK2uM+5XreF/taZYDMJw7SWDSZf
Oh497iRW8ofFKN0SMUTNljEa6CeOgoUbzCdFiv/qh0lTdC++zwA3evmVq7VJynna
7aWVBoQiClJLNkMvPoLucklJylYmUasA5QxknekWa5vReUc1zxuke/ONh/52yQ1W
iKvvfgAkGMeLw+boNX5QBDmbPPxeBQDzcktf6a9zGAS2KLwstwa/wsIZ7sq+dmIA
a9nCvQHMNk3JMbSVEDRkjEnTYDmWMjbYQ7ifyj+RO0wmY+23EXqYeuktqJKb7zmo
muHT6Cng36YGPOl8wuSJlbSnb85V+iN3MaYR3ZMbKh80XqZ1oWgmm2MHwSHsHNox
gHxEWl7ZhAnetAvIRCTfO/nEs+w39PSY+4JNpQW+Msdjl+gQ59kmYuek/nkvjHUs
KYv/9STQ1+Cd+YL7qxJpmh1n0wPrYDumd/MNV1IhKLU5c4IO4LfbNADbueUXSAZu
UzAQfOs2t012DmJF4n0iKi422TUCAwEAAaN4MHYwCQYDVR0TBAIwADALBgNVHQ8E
BAMCBeAwOwYDVR0lBDQwMgYIKwYBBQUHAwEGCCsGAQUFBwMCBggrBgEFBQcDAwYI
KwYBBQUHAwQGCCsGAQUFCAICMB8GA1UdEQQYMBaCCXNtaW1lLnA3bYIJZGFpbHku
cGhwMA0GCSqGSIb3DQEBCwUAA4ICAQBJjtCmIdV88nB2JgGTmHXbNO36FoGw2ZZg
pd+am1braBM9goKPiIPxn5UbGKSuTStq6mh9qnAgbbKgpQOXgCHu6PRnxcvSP64o
6w2h1GIgzAAexXTBg7tgUhWTe7sL41D7NWjd+W6CkvMTZUX52zNgRXYhH0Oc6+lt
xyM1yW7Ve0SEeL1Ho3bdDVHlGzUBbBceem34qK3DOw6ZOOaIzmaWR3q1rhAzyCXW
vbnf0EHuBCZue3rOdSYStzp9/3Zx6hdm3+WcFc9jOVJxLa1gqNkLyd/0hGLyX4XN
vOV8uLVJNAMlpzQoE2IyZkbANejlFDAG4CfusjK0t2TLchzqn2h9uvnmcks3Ab0b
JccsJZloiKcmDGHGJYJmvrDWQjoZ1EjSoVMaq+qJx6dR4wGI77aIF5YW3NDsOjnC
rRjHdCP6QU9mZ2SYJB9vMP2YR0eaIqH+VbMPqy3oD9ZXPqLcJ7MpYTYYqZXDM2nk
P3SjevfqxUNeNjhEm+8lmLPrnoA1ADJ8RND10TaCzlmEnetKKzQw4EkJRpOzraNc
3F6o7OZmIG03QPWPxl4WDzn6tN3tZcv0R71s/lcfbSgeqRi9qjKIOlyrujhzN3DP
No00f8AsulitJezWuObMZniFI2/OnlgcfJ73fexZqM51ucTaY+p5Eu0X0/PpTIlk
9TPpqtc1pQ==
-----END CERTIFICATE-----
';
    $cwd = getcwd();
    if($path_type == 1) $infile_p7m = $cwd . '/' . $infile_p7m;
    $outfile = substr($infile_p7m, 0, strrpos($infile_p7m, '.'));
    if(openssl_pkcs7_decrypt($infile_p7m, $outfile, $cert, array($key, $pw))){
        return ".p7m decrypted successfully <br><br>\r\n";
    }else die("failed to decrypt! <br>\r\n");
}

function get_html($url){
    $path_parts = pathinfo($url);
    $refer = $path_parts['dirname'] . '/' . $_SERVER['PHP_SELF'];
    $option = array('http' => array(
                            'header' => "Referer: $refer",
                            'method' => "GET",
                            'timeout' => 10,
                            ), 
                    'ssl' => array('verify_peer' => false, 'verify_peer_name' => false,),
                    );
    $html = @file_get_contents($url, false, stream_context_create($option));
    if($html === false) die('Failed ' . $http_response_header[0]);
    else{
        echo $http_response_header[0] . " <br><br>\r\n";
        return $html;
    }
}

function get_file_progress($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    //curl_setopt($ch, CURLOPT_BUFFERSIZE,128);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progress');
    curl_setopt($ch, CURLOPT_NOPROGRESS, false); //false表示用进度条
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

# 遍历当前目录
// print_r(listDir('./'));
function listDir($dir){
    $dir .= substr($dir, -1) == '/'?'':'/';
    $dirInfo = array();
    foreach(glob($dir . '*') as $v){
        $dirInfo[] = $v;
        if(is_dir($v)){
            $dirInfo = array_merge($dirInfo, listDir($v));
        }
    }
    return $dirInfo;
}


