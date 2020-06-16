<?php
// highlight_file("caiji-mmh.php");
ignore_user_abort();
set_time_limit(0);
error_reporting(1);
date_default_timezone_set('America/New_York');
// echo date("Y-n-j", time());

# $time 是文件发布日期，$time0 是系统当前日期

# 上传到对象服务器
$up2qiniu = false;                     # true为发送,false为不发送

$ext = array_flip(get_loaded_extensions());
if(empty($ext['curl']))    die("不支持 curl <br>\r\n");
if(empty($ext['openssl'])) die("不支持 openssl <br>\r\n");
if(empty($ext['zip']))     die("不支持 zip <br>\r\n");

$host = base64_decode('aHR0cDovL20ubWluZ2h1aS5vcmc=');
$url_base = $host . '/mh/articles/';

$mhdata = 'mhdata/';
$cwd = getcwd();
if(!is_dir($cwd.'/'.$mhdata)) mkdir($cwd.'/'.$mhdata, 0777, true);
$loc = $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) .'/';

# 用浏览器访问
if(empty($_GET['mhdaily']) and !strstr($_SERVER['HTTP_USER_AGENT'], 'Wget')){
    form_html();
    exit(0);
}

ob_end_flush();//关闭清空缓存

echo "<br>正在检测远程文件 <br><pre>\r\n";

# 由GET变量传递的文件名和URL
if(isset($_GET['mhdaily'])){
    $fn = $_GET['mhdaily'];
    $regex="'\d{4}-\d{1,2}-\d{1,2}-t.zip'is";
    preg_match_all($regex,$fn,$matches);
    // print_r($matches[0]);
    if(empty($matches[0])) die("<br>文件名格式不匹配 . <br>\r\n");

    $time = substr($fn, 0, -6); #文件名除去后缀
    if(!strtotime($time)) die("<br>日期格式不匹配 . <br>\r\n");
    $year =  date("Y", strtotime($time));
    $month = date("n", strtotime($time));
    $day =   date("j", strtotime($time));
    $monthday = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    $url_path_date = $year .'/'. $month .'/'. $day .'/';
    $fn_date_zip = $time .'-t.zip';
    $fn_date_md5 = $time .'.md5';
    $url_t_zip = $url_base . $url_path_date . $fn_date_zip;
}else{
    $time = date("Y-n-j", time());
    $year =  date("Y", time());
    $month = date("n", time());
    $day =   date("j", time());
    $monthday = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $url_path_date = $year .'/'. $month .'/'. $day .'/';
    $fn_date_zip = $year .'-'. $month .'-'. $day .'-t.zip';
    $fn_date_md5 = $year .'-'. $month .'-'. $day .'.md5';
    $url_t_zip = $url_base . $url_path_date . $fn_date_zip;
}
$file_gz = substr(md5($time), 8, 16) . '.gz';
$file_gz_link = $loc . $mhdata . $file_gz;

# md5 控制文件是否更新
$time0 = time(); # 当前日期
if(file_exists($mhdata . $fn_date_md5)){
    $old_md5 = file_get_contents($mhdata . $fn_date_md5);
    $md5_time = filemtime($mhdata . $fn_date_md5);
    $time_diff = round(($time0 - $md5_time)/60, 1);
    if(($time0 - $md5_time) < 3600){
        die("<br><b>".$time_diff. "</b> 分钟前更新,相关的文件 " .$file_gz_link. "<br>\r\n");
    }
}else $old_md5 = '';

$url_headers = get_headers($url_t_zip);
if(!strpos($url_headers[0], '200')) die("<br>未找到或者尚未发布 . <br>\r\n");
// print_r($url_headers); # 根据ETag和Last-modified规则更新文件

$zip_tmp = getResponse($url_t_zip, $data = [], $cookie_file = '', $progress=false);
file_put_contents($mhdata . $fn_date_zip, $zip_tmp['body']);
unset($zip_tmp);
$last_md5 = md5_file($mhdata . $fn_date_zip);
// $last_md5 = md5($zip_tmp['body']);
unlink($mhdata . $fn_date_zip);
file_put_contents($mhdata . $fn_date_md5, $last_md5);
if($last_md5 === $old_md5){
    die("<br>无需更新,已有的文件 " . $file_gz_link . "<br>\r\n");
}

if(file_exists($mhdata . $file_gz) and ($time0 - filemtime($mhdata . $file_gz)) < 3600){
    die('<br>1小时内的更新文件 ' . $file_gz_link . "<br>\r\n");
}


echo "\r\n</pre><br><br>正在离线下载 <br><pre>\r\n";

$url = $host . '/mmh/articles/' .$url_path_date. 'index.html';
$path_fn = make_path($url);
$res_array = getResponse($url, $data = [], $cookie_file = '', $progress=true);
$html = $res_array['body'];
file_put_contents($path_fn, $html);
$array_url = array_unique(preg_htmllink($html));

$img_url = array();
foreach($array_url as $url){
    if(empty($url)) continue;
    if(empty(parse_url($url, PHP_URL_SCHEME)) or empty(parse_url($url, PHP_URL_HOST))){
        $url = parse_url($url, PHP_URL_PATH);
        $url = $host . $url;
    }else echo $url . "<br>\r\n";
    $url = trim($url);
    $path_fn = make_path($url);
    $res_array = getResponse($url, $data = [], $cookie_file = '', $progress=true);
    $html = $res_array['body'];
    file_put_contents($path_fn, $html);
++$i;
echo '.';
flush();
clearstatcache();
    if(strpos($url, '.html') !== false and strpos($url, '/mh/articles/') !== false){
        $daily = $url;
        $array_article_url = array_unique(preg_htmllink($html));

        # 只提取图片链接,所有图片在 /mh 目录
        foreach($array_article_url as $article_url){
            if(strpos($article_url, '/mh') !== false) $img_url[] .= $article_url;
        }
    }
    unset($array_article_url);
}
echo " $i <br>\r\n";

foreach($img_url as $url){
    if(empty($url)) continue;
    if(empty(parse_url($url, PHP_URL_SCHEME)) or empty(parse_url($url, PHP_URL_HOST))){
        $url = parse_url($url, PHP_URL_PATH);
        $url = $host . $url;
    }else echo "<br>" . $url . " <b> 外部链接</b><br>\r\n";
    $url = trim($url);
    $path_fn = make_path($url);
    $res_array = getResponse($url, $data = [], $cookie_file = '', $progress=true);
    $html = $res_array['body'];
    file_put_contents($path_fn, $html);
++$ii;
echo '.';
flush();
}
echo " $ii <br>\r\n";

$n = $i + $ii;
$index_fn = basename($daily);
echo "<b>$index_fn 中 $n 个文件下载完毕</b>  \r\n\r\n";

sleep(1);
# 打包压缩
$basename = strstr($index_fn, '.', true);
$txtfn = $basename . '.txt';
$zipfn = $basename . '.zip';
$file_p7m = $zipfn . '.p7m';
if(file_exists($zipfn)) unlink($zipfn);

$all_url = array_unique(array_merge($img_url, $array_url));
$all_url[] .= '/pub/mobile.css';
// file_put_contents('url.log', print_r($all_url, true));
// print_r($array_url);  print_r($img_url);

foreach($all_url as $fn){
    $fn = trim(parse_url($fn, PHP_URL_PATH));
    $fn = ltrim($fn, '/');
    zip_file($fn, $zipfn);
    if(!strpos($fn, '.css')) unlink($fn);
    $dir = './'.dirname($fn);
    if(is_dir($dir) and count(scandir($dir))==2) rmdir($dir);
}
echo "$zipfn 打包完成, 源文件被释放 <br>\r\n";

sleep(1);
echo pkcs7_encrypt($zipfn);

$file_zip = strstr($file_gz, '.', true) . '.zip';
if(extension_Loaded('zlib')){
    file_put_contents($mhdata . $file_gz, gzencode( file_get_contents($file_p7m), 9));
}
echo zip_file($file_p7m, $mhdata . $file_zip);

echo "压缩包地址 " . $file_gz_link;

unlink($zipfn);
unlink($file_p7m);
delDirAndFile('mh');
delDirAndFile('mmh');

if($up2qiniu) upload2qiniu(getcwd() .'/'. $mhdata . $file_gz);

echo "</pre><br>\r\n";



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

# 加密函数 $path_type = 1 为相对路径，0 为绝对路径
function pkcs7_encrypt($infile, $path_type = "1"){
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
MIIENDCCA52gAwIBAgICAUMwDQYJKoZIhvcNAQEFBQAwgdUxNDAyBgNVBAMTK1Jh
bmdlcnMgUGVyc29uYWwgRnJlZSBDZXJ0aWZpY2F0ZSBBdXRob3JpdHkxGjAYBgkq
hkiG9w0BCQEWC2NlcnRAUlBGLkNBMSAwHgYDVQQKExdSYW5nZXJzIE5ldHdvcmtz
IENvLkx0ZDEXMBUGA1UECxMOUEhQIExhYm9yYXRvcnkxETAPBgNVBAcTCFlpbmNo
dWFuMSYwJAYDVQQIEx1OaW5neGlhIEh1aSBBdXRvbm9tb3VzIFJlZ2lvbjELMAkG
A1UEBhMCQ04wHhcNMTAwNzAzMDQwOTMyWhcNMTEwNzAzMDQwOTMyWjCBrTEiMCAG
CSqGSIb3DQEJARYTaW5mb0B5b3Vyc2hlbGwuaW5mbzENMAsGA1UEAxMEd2FsazEL
MAkGA1UEBhMCQ04xJjAkBgNVBAgTHU5pbmd4aWEgSHVpIEF1dG9ub21vdXMgUmVn
aW9uMREwDwYDVQQHEwhZaW5jaHVhbjEgMB4GA1UEChMXUmFuZ2VycyBOZXR3b3Jr
cyBDby5MdGQxDjAMBgNVBAsTBVN0YWZmMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCB
iQKBgQC1z3t8kIIjLYoYlIma0qPJ8sFJZXRdwUo6qATQilkXuUrQEttRJ/EgzDIn
8L7IcfUjxHYAEJPyckEaKosGlNvo3FjJ+XEtVFCjLFqN0FrE7kpq+6FA5bFXLMuq
B5i8FOzVPMnVIr+6n/WGeE+rRGIUTUuNcELJRT9SBbjtsXPQtQIDAQABo4IBNzCC
ATMwHQYDVR0OBBYEFAQI/LaNrga160Q+aNeMeLgDR6oyMIIBAgYDVR0jBIH6MIH3
gBQgFsVoaeBCqmN69Y61GkI4MgbgdqGB26SB2DCB1TE0MDIGA1UEAxMrUmFuZ2Vy
cyBQZXJzb25hbCBGcmVlIENlcnRpZmljYXRlIEF1dGhvcml0eTEaMBgGCSqGSIb3
DQEJARYLY2VydEBSUEYuQ0ExIDAeBgNVBAoTF1JhbmdlcnMgTmV0d29ya3MgQ28u
THRkMRcwFQYDVQQLEw5QSFAgTGFib3JhdG9yeTERMA8GA1UEBxMIWWluY2h1YW4x
JjAkBgNVBAgTHU5pbmd4aWEgSHVpIEF1dG9ub21vdXMgUmVnaW9uMQswCQYDVQQG
EwJDToIBATAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBAKMKq9zCJ2Qx
vJ0KAe2yogzFJLCy3GCK0URcHBedGi3Emfwngm7LQoHF/PQg8BmJ3entJDMvYPAs
Rwin+2biKiz/kcrGIsOs2rfgl1ubxZi/fFf+aNbJvhDKKBvXozUZMQQp7+kPbC8u
x7W+ZnmjO8yJXTdYfwmQxv2SOulDZtoe
-----END CERTIFICATE-----';

    $cwd = getcwd();
    # $cwd = $_SERVER['DOCUMENT_ROOT'];
    if($path_type == 1) $infile = $cwd . '/' . $infile;
    $outfile_p7m = $infile . '.p7m';
    if(openssl_pkcs7_encrypt($infile, $outfile_p7m, $cert, $headers, PKCS7_BINARY)){
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
MIICXgIBAAKBgQC1z3t8kIIjLYoYlIma0qPJ8sFJZXRdwUo6qATQilkXuUrQEttR
J/EgzDIn8L7IcfUjxHYAEJPyckEaKosGlNvo3FjJ+XEtVFCjLFqN0FrE7kpq+6FA
5bFXLMuqB5i8FOzVPMnVIr+6n/WGeE+rRGIUTUuNcELJRT9SBbjtsXPQtQIDAQAB
AoGBAJS3q2MxMcJktdl1Zznlo2TF1aWb/5vRSE7CsW2EPWxSfQfG5O91pKAXZ8+T
9fswfD1NrthOtzZSjz5AHoi7q0VkyKg+aHZyj0w5CQpSmJLkMJpveSQV/8ehiSWz
Z9e7chq+1hSg5A2ICjxNz29F29e8uDOSny8B5a9ZzdwmB4nhAkEA7H/Q4mDAoVDj
jlAmMDnun69iV2Q19+ERtW7aGupvksTwZ/mABIfhhZIZm36kq5Q4b0TKyTiMgTZv
uhyeu8epewJBAMTNQkD4QchTw2XbUoUa0CIyXQg8y+XPhMNEyTeb8kGyu66RgoIn
lLzn+EQZqgFQOEc9C7jKlT1E+MLk+xW+348CQQDdCFxupySB4Dq9MFVwr0RBREZy
DPuPj2/glRkNHNxoXN2fH4WxNlnlX3XFaSh4H9Ba1f188PgIb5seY09Li0DvAkEA
msdv/xcA7aPrPnWS3fprjSmc/3iJSDHAka7Mrj6o9kCy2SW5xdGJalTqbezdRvEn
geeiC3DQlQJkvytFyiF3QwJAVidp6GnuoFqUScSXzB2xXV5R+9T0hPlqiSJAiXPN
20epQSumQ3NRork2e2FH6rXK+5DEbHacQeoXouFViWYr3g==
-----END RSA PRIVATE KEY-----';
    $cert = '-----BEGIN CERTIFICATE-----
MIIENDCCA52gAwIBAgICAUMwDQYJKoZIhvcNAQEFBQAwgdUxNDAyBgNVBAMTK1Jh
bmdlcnMgUGVyc29uYWwgRnJlZSBDZXJ0aWZpY2F0ZSBBdXRob3JpdHkxGjAYBgkq
hkiG9w0BCQEWC2NlcnRAUlBGLkNBMSAwHgYDVQQKExdSYW5nZXJzIE5ldHdvcmtz
IENvLkx0ZDEXMBUGA1UECxMOUEhQIExhYm9yYXRvcnkxETAPBgNVBAcTCFlpbmNo
dWFuMSYwJAYDVQQIEx1OaW5neGlhIEh1aSBBdXRvbm9tb3VzIFJlZ2lvbjELMAkG
A1UEBhMCQ04wHhcNMTAwNzAzMDQwOTMyWhcNMTEwNzAzMDQwOTMyWjCBrTEiMCAG
CSqGSIb3DQEJARYTaW5mb0B5b3Vyc2hlbGwuaW5mbzENMAsGA1UEAxMEd2FsazEL
MAkGA1UEBhMCQ04xJjAkBgNVBAgTHU5pbmd4aWEgSHVpIEF1dG9ub21vdXMgUmVn
aW9uMREwDwYDVQQHEwhZaW5jaHVhbjEgMB4GA1UEChMXUmFuZ2VycyBOZXR3b3Jr
cyBDby5MdGQxDjAMBgNVBAsTBVN0YWZmMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCB
iQKBgQC1z3t8kIIjLYoYlIma0qPJ8sFJZXRdwUo6qATQilkXuUrQEttRJ/EgzDIn
8L7IcfUjxHYAEJPyckEaKosGlNvo3FjJ+XEtVFCjLFqN0FrE7kpq+6FA5bFXLMuq
B5i8FOzVPMnVIr+6n/WGeE+rRGIUTUuNcELJRT9SBbjtsXPQtQIDAQABo4IBNzCC
ATMwHQYDVR0OBBYEFAQI/LaNrga160Q+aNeMeLgDR6oyMIIBAgYDVR0jBIH6MIH3
gBQgFsVoaeBCqmN69Y61GkI4MgbgdqGB26SB2DCB1TE0MDIGA1UEAxMrUmFuZ2Vy
cyBQZXJzb25hbCBGcmVlIENlcnRpZmljYXRlIEF1dGhvcml0eTEaMBgGCSqGSIb3
DQEJARYLY2VydEBSUEYuQ0ExIDAeBgNVBAoTF1JhbmdlcnMgTmV0d29ya3MgQ28u
THRkMRcwFQYDVQQLEw5QSFAgTGFib3JhdG9yeTERMA8GA1UEBxMIWWluY2h1YW4x
JjAkBgNVBAgTHU5pbmd4aWEgSHVpIEF1dG9ub21vdXMgUmVnaW9uMQswCQYDVQQG
EwJDToIBATAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBAKMKq9zCJ2Qx
vJ0KAe2yogzFJLCy3GCK0URcHBedGi3Emfwngm7LQoHF/PQg8BmJ3entJDMvYPAs
Rwin+2biKiz/kcrGIsOs2rfgl1ubxZi/fFf+aNbJvhDKKBvXozUZMQQp7+kPbC8u
x7W+ZnmjO8yJXTdYfwmQxv2SOulDZtoe
-----END CERTIFICATE-----';
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


