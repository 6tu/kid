<?php

// highlight_file("caiji-mmh.php");
ignore_user_abort();
set_time_limit(0);
error_reporting(1);
date_default_timezone_set('America/New_York');

$loc = $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) .'/';


$mhdata = 'mhdata/';
$cwd = getcwd();
if(!is_dir($cwd.'/'.$mhdata)) mkdir($cwd.'/'.$mhdata, 0777, true);

$time = date("Y-n-j", time());
if(file_exists($mhdata . $time. '.zip.p7m')) die($loc . $mhdata . $time. '.zip.p7m');
$headers = get_headers('https://shideyun.com/mmh/mhdata/' . $time . '-t.zip.sha512');
if(!strpos($headers[0], '200')) echo("error,今日尚未发布");

$host = 'https://mmh.6tu.me';
$url = $host . '/mmh/index.html';
$res_array = getResponse($url, $data = [], $cookie_file = '');
$html = $res_array['body'];
$array_url = getlink($html);
$path_fn = mkpath($url);
file_put_contents($path_fn, $html);
unset($res_array);

$img_url = array();
foreach($array_url as $url){
    $url = parse_url($url, PHP_URL_PATH);
    
    $url = $host . $url;
    $path_fn = mkpath($url);
    $res_array = getResponse($url, $data = [], $cookie_file = '');
    $html = $res_array['body'];
    file_put_contents($path_fn, $html);
    unset($res_array);
    if(strpos($url, '.html') !== false and strpos($url, '/mh/articles/') !== false){
        $daily = $url;
        preg_match_all("/(src)=([\"|']?)([^\"'>]+.(jpg|JPG|jpeg|JPEG|gif|GIF|png|PNG))/i", $html, $src_url, PREG_PATTERN_ORDER);
        $href_url = getlink($html);
        $array_article_url = array_unique(array_merge($src_url[3], $href_url));
        foreach($array_article_url as $article_url){
            if(strpos($article_url, '/mh') !== false) $img_url[] .= $article_url;
        }
    }
    unset($array_article_url);
}
foreach($img_url as $url){
    $url = parse_url($url, PHP_URL_PATH);
    $url = $host . $url;
    $path_fn = mkpath($url);
    $res_array = getResponse($url, $data = [], $cookie_file = '');
    $html = $res_array['body'];
    file_put_contents($path_fn, $html);
}

echo "<h4>相关文件下载完毕</h4> <pre>\r\n";
echo "<b>$daily </b>\r\n\r\n";
//print_r($array_url);
//print_r($img_url);
$all_url = array_unique(array_merge($img_url, $array_url));
$zipfn = substr(strrchr($daily, '/'), 1, -4) . 'zip';
$txtfn = substr(strrchr($daily, '/'), 1, -4) . 'txt';
// file_put_contents($txtfn, print_r($all_url, true));
if(file_exists($zipfn)) unlink($zipfn);
$all_url[] .= '/pub/mobile.css';
$all_url[] .= '/pub/favicon.ico';
foreach($all_url as $fn){
    $fn = str_replace($host . '/', '', $fn);
    $fn = ltrim($fn, '/');
    compress($fn, $zipfn);
}
echo "<b>$zipfn 打包完成 </b>\r\n\r\n";

foreach($all_url as $fn){
    $fn = str_replace($host . '/', '', $fn);
    $fn = ltrim($fn, '/');
    if(strpos($fn, 'pub/') !== false) continue;
    unlink($fn);
}
delDirAndFile('mmh');
delDirAndFile('mh');

$smime = pkcs7_encrypt($zipfn);
$file_p7m = $zipfn . '.p7m';
echo $smime . $loc_zip = $loc . $mhdata . $file_p7m;
rename($file_p7m, $mhdata . $file_p7m);
unlink($zipfn);
unlink($file_p7m);






















/** =========函数区========= */

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

function mkpath($url){
    $cwd = getcwd();
    $url_info = parse_url($url);
    $path_info = pathinfo($url_info['path']);
    $path = $cwd . $path_info['dirname'];
    $fn = $path_info['basename'];
    if(!is_dir($path))mkdir($path, 0777, true);
    return $path . '/' . $fn;
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

function compress($txtname, $zipname){
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
    return " Create a compressed file $zipname success <br>\r\n";
}

#  [, 表示之后为可选
# 加密函数
# https://www.php.net/manual/zh/openssl.pkcs7.flags.php
# https://www.php.net/manual/zh/openssl.ciphers.php
# https://www.php.net/manual/zh/function.openssl-pkcs7-encrypt.php
## $path_type = 1 为相对路径，0 为绝对路径
function pkcs7_encrypt($infile, $path_type = "1"){
    $headers = array("To" => "info@liuyun.org",
        "From" => "webmaster <postmaster@liuyun.org>",
        "Reply-to" => "support@liuyun.org",
        "Subject" => "Daily News for ",
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
        return "smime.p7m successfully created <br>\r\n";
    }else return "Encryption failed <br>\r\n";
    # unlink($enc);
}

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


function get_html($url){
    $path_parts = pathinfo($url);
    $refer = $path_parts['dirname'] . '/' . $_SERVER['PHP_SELF'];
    $option = array('http' => array('header' => "Referer:$refer"), 
                    'ssl' => array('verify_peer' => false, 'verify_peer_name' => false,),
                    );
    $html = @file_get_contents($url, false, stream_context_create($option));
    if($html === false) die('Failed ' . $http_response_header[0]);
    else{
        echo $http_response_header[0] . " <br><br>\r\n";
        return $html;
    }
}
