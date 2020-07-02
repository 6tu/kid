<?php
/**
 * Minghui.org Daily 的客户端.
 * 
 * PHP Version 5.5+
 * php extension library : zip,curl,openssl
 * 
 * $date 是文件发布日期，$time 是系统当前时间戳
 * date()接受 年-月-日 和 月/日/年 这种格式
 * 数组以 $array_ 开头, 文件名以 $fn_ 开头, 网址以 $url_ 开头,
 * 不带文件名的路径以 $path_ 开头,带文件名的路径以 $file_ 开头,
 * 
 * "Accept-Encoding: gzip, deflate, br\r\n" 会使数据在传输中压缩
 * // highlight_file("self.php");
 * 
 */

ignore_user_abort();
set_time_limit(0);
error_reporting(1);
date_default_timezone_set('America/New_York');
ob_implicit_flush(true);

$ext = array_flip(get_loaded_extensions());
if(empty($ext['curl']))    die("不支持 curl <br>\r\n");
if(empty($ext['openssl'])) die("不支持 openssl <br>\r\n");
if(empty($ext['zip']))     die("不支持 zip <br>\r\n");

# 用浏览器访问
if(empty($_SERVER['HTTP_USER_AGENT'])) $_SERVER['HTTP_USER_AGENT'] = 'Wget';
if(empty($_GET['mhdaily']) and !strstr($_SERVER['HTTP_USER_AGENT'], 'Wget')) die(form_html());

$mhdata = 'mhdata/';
$cwd = getcwd();
if(!is_dir($cwd.'/'.$mhdata)) mkdir($cwd.'/'.$mhdata, 0777, true);

# 自定义 $host, 可为数组, 提交选择
$host = 'http://jianji.me/mmh/' . $mhdata;
$host = 'http://qncdn.popcn.net/';

ob_end_clean();

echo "\r\n正在下载 <br><pre>\r\n";
flush();
# 由GET变量传递的文件名和URL
if(isset($_GET['mhdaily'])){
    $fn = $_GET['mhdaily'];
    $regex="'\d{4}-\d{1,2}-\d{1,2}.zip'is";
    preg_match_all($regex, $fn, $matches);
    // print_r($matches[0]);
    if(empty($matches[0])) die("<br>文件名格式不匹配 . <br>\r\n");
    $date = substr($fn, 0, -4); # 文件名除去后缀
    if(!strtotime($date)) die("<br>日期格式不匹配 . <br>\r\n");
}else{
    $date = date("Y-n-j", time());
}

$year =  date("Y", strtotime($date));
$month = date("n", strtotime($date));
$day =   date("j", strtotime($date));
$monthday = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$path_date = $year .'/'. $month .'/'. $day .'/';

$fn_src = substr(md5($date), 8, 16) . '.zip';
$url_src = $host . $fn_src;

$fn_zip = $date.'.zip';
$fn_p7m = $fn_zip . '.p7m';

$time = time();
if(file_exists($mhdata . $fn_src)){
    $fn_src_time = filemtime($mhdata . $fn_src);
    $time_diff = round(($time - $fn_src_time)/60, 1);
    if(($time_diff) < 1) die("<br><b>半小时前更新 </b><br>\r\n");
}

$html = get_html($url_src);

file_put_contents($mhdata . $fn_src, $html);
echo "\r\n</pre><br>下载完毕 <br><br>\r\n";
flush();
echo unzip_file($mhdata . $fn_src, $mhdata);
echo pkcs7_decrypt($mhdata . $fn_p7m, $mhdata . $fn_zip);
echo unzip_file($mhdata . $fn_zip, './');
echo "\r\n<br><a href=/mmh><b> $date </b></a>";
unlink($mhdata . $fn_p7m);
unlink($mhdata . $fn_src);



/** =========函数区========= */

function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max){
    static $filesize = null;
    switch($notification_code){
    case STREAM_NOTIFY_FILE_SIZE_IS:
        $filesize = $bytes_max;
        break;
    case STREAM_NOTIFY_CONNECT:
        # 发生301等则出现两次
        // echo "Connected ...<br><pre>\r\n";
        break;
    case STREAM_NOTIFY_PROGRESS:
        if($bytes_transferred > 0 && $filesize >= 8192){
            $bytes_transferred += 8192;
            if(!isset($filesize)){
                printf("\r\nUnknown filesize.. %2d kb done..", $bytes_transferred/1024/1024);
            }else{
                $length_f = number_format(($bytes_transferred/$filesize) * 100, 2);
                $length = (int)$length_f;
                $rem = substr($length_f, -2);
                if($length < 6){
                    echo '.';
                    break;
                }
                if($rem > 94 and is_int($length/10)){
                    //printf("\r\n<br>[%-90s] %d%% (%2d/%2d kb)", str_repeat("=", $length) . ">", $length, ($bytes_transferred/1024), $filesize/1024);
                    printf("\r\n[%-100s] %d%% ", str_repeat("=", $length) . ">", $length);
                }
                if($length_f > 99.98 and $length == 100){
                    printf("\r\n[%-100s] %d%% ", str_repeat("=", $length) . ">", $length);
                }
            }
        }
        break;
    case STREAM_NOTIFY_COMPLETED:
        echo "</pre><br>\r\n";
        break;
    }
}

function get_html($url){
    $path_parts = pathinfo($url);
    $refer = $path_parts['dirname'] . '/'; //  . $_SERVER['PHP_SELF']
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36';
    $accept = 'text/html,application/xhtml+xml,application/json,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';

        $option = array(
            'http' => array(
                //"method" => "GET",
                //"timeout" => (float)0.5,
                "header" =>
                    "Referer: $refer\r\n" .
                    "User-Agent: $ua\r\n" .
                    "Accept: $accept\r\n" .
                    "Accept-Language: zh-cn,zh;q=0.9,q=0.8,en-us;q=0.5,en;q=0.3\r\n" 
                    // "Accept-Encoding: gzip, deflate, br\r\n" .
                    // "Connection: keep-alive\r\n"
                ), 
            'ssl' => array(
                "verify_host" => false,
                "verify_peer" => false,
                "verify_peer_name" => false,
                ),
            );
    $context = stream_context_create($option);
    stream_context_set_params($context, array("notification" => "stream_notification_callback"));

    $html = file_get_contents($url, false, $context);
    if($html === false) die($http_response_header[0]);
    else{
        return $html;
    }
}

function form_html(){
    $time = date("Y-n-j", time());
    $fn = $time . '.zip';
    $html = "<body><br><center>\r\n";
    $html .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="GET" />' . "\r\n";
    $html .= "  <b>?mhdaily= </b>\r\n";
    $html .= '  <input type="text" name="mhdaily" size=20 value="'.$fn.'" />'."\r\n";
    $html .= '  <input type="submit" value="Send" />'."\r\n";
    $html .= "</form>\r\n</center></body>";
    echo $html;
}

function unzip_file($file, $destination){
    $zip = new ZipArchive();
    if($zip -> open($file) !== TRUE) die('Could not open archive');
    $zip -> extractTo($destination);
    $zip -> close();
    return "解压完毕<br>\r\n";
}

function pkcs7_decrypt($infile, $outfile){
    $pw = '';
    $key = '-----BEGIN RSA PRIVATE KEY-----
MIIJKQIBAAKCAgEAvI2hpjyWgfVXYtOuH2X9LqMfRQ75TMN7YTPv8VKgrCgGDTw6
Za0pHvErcJgUOS6os3lOXasZb5dk4WG29vHzKUgDehxzManALGClL14HploFkshv
EPwfOKN7kLe2KkEwNc0eFHaOLx6+AN/Huk8wUfnjwY/A07CCx+T+4L2KzV4r1dN8
CYPQUPK2uM+5XreF/taZYDMJw7SWDSZfOh497iRW8ofFKN0SMUTNljEa6CeOgoUb
zCdFiv/qh0lTdC++zwA3evmVq7VJynna7aWVBoQiClJLNkMvPoLucklJylYmUasA
5QxknekWa5vReUc1zxuke/ONh/52yQ1WiKvvfgAkGMeLw+boNX5QBDmbPPxeBQDz
cktf6a9zGAS2KLwstwa/wsIZ7sq+dmIAa9nCvQHMNk3JMbSVEDRkjEnTYDmWMjbY
Q7ifyj+RO0wmY+23EXqYeuktqJKb7zmomuHT6Cng36YGPOl8wuSJlbSnb85V+iN3
MaYR3ZMbKh80XqZ1oWgmm2MHwSHsHNoxgHxEWl7ZhAnetAvIRCTfO/nEs+w39PSY
+4JNpQW+Msdjl+gQ59kmYuek/nkvjHUsKYv/9STQ1+Cd+YL7qxJpmh1n0wPrYDum
d/MNV1IhKLU5c4IO4LfbNADbueUXSAZuUzAQfOs2t012DmJF4n0iKi422TUCAwEA
AQKCAgB9fET4vZntI7rkqrxXaSj2wNkuvKhtzitupYIquTL1YC2m4U58HKIhVZ/z
b0MGS2c7CqB89kIXYkphNQbvklaiQqsNuaFwi+i5oBhPTeUJcSAEcCB6zVB6AVNb
HOi/dmL/a2N5eu1lrrAFJOlntQwTgptxVpqeR/rBzkVSjNKzmtjRlc4XBwK83Mt+
c8CKqNkkoO2yeEUnWigC2GbH5xiQRN8YygMNDxPsdj3clxGxL6JIew3k8L33pBoR
r1s7GAuE+D/0N+bEQAK8Hhz1zB1CGO4OsHYdxtjKYZkPtnbFklAuSgBub7EG5vlk
5G6kqB/Hlj4BwDWRLGkhpR7SFptru1GmEoIwhngkFu0ptpr3ocE+Bf7zIFb8dCK6
DezR4CgCCRHfBXZ0B/e83PqJWTBSoEj8AC7coN7ZY4hLRVp8lYD60raLxx7BjK3k
E7XWaiGhOY+SHDaz+8zgCjClhW187abu1meKnVeZG7QB1DT00uQVKwslI13T9jSc
E+tTTFIRnidQNB/Ehm1xawBaZwc3DUN+jNYfDGT7o1QuiBoiapc6wR529QqhZvSq
sZVDZA+jenFA9hh/7ayt8TGfxs5j6ozt8lMd5k0boMQC+JE/kGVsftJ1X4Y9EPnn
xqLVesnSkxg7q2YkJy8AuwOzVSo5CzHAo8Pt/gUp1zRgGt5MAQKCAQEA4z9zTYzk
V6sKmY/ttO3qTtaCT6zfB1IDPB1c6r72HfoUx93BNE0UiDAFOmU4B8kKlwOADMB7
9s40iHioWBJlot4GFd8pClWHxTgxUCRTBQDfUX3bsjKBLGAP0TiQpj9vQPWTrnhd
VANbIRxwB8+rDBOCxEecoRNnV6uBOnQzSOLUDD/rSy8Iqf4vBFJmi5247RBm0ER4
oYVWQVlJ0FnE2hyH+I/02YC+UH7LcHOsY0mqH9HGrpoKEXa+hG3OqdFiFvY2N6MR
aoReO/H7XLUHnEgZcjI8LUetG99LefZBNT+7QsNRRIcS9n+y/SAyxssZjOlIyENP
RQQasps6t5OI3QKCAQEA1GjdPCdswAWCtJEhA53WjCOXT3BP159K3pXh4fRhr1NN
QBSrlS/fVyXMIFTrlMhrIq/A5/FwV9EIvBBtuY/CJgqHq3umDsSrBu9ioGMcLbBO
jL0Ewbqi4IATfJ/XlE7G4lKeb6DWWhwdReKMvZzteEy/yC0Lox8xj1eC51YyLRWJ
pfGvXl+eg0fxcskYNNIHT6Jai+8+x/XgIQkgw2FLE0V3XA00X4p3+myQn5/I/7B4
c+cjORTHquNHGPCXaAPgSjvHap4HH+oMJVqse9ZWwiNjf6WDCfoWQrTaaTdi9aoR
MNnRb1A9rI309D5D84WKHyGZl8QECHv1c/O8GUzgOQKCAQEAhCN1DHcMn92ZQEns
0vQ09rrM8z92QG9z6hS43tdDjZLJWp6bpanccoRZxebteblxKvaiEsgqTQ0ChwiB
+xRXfSjVKQqmdpfdZtSR+CPnElW7hUtF4Ix1iDQjfmkB02m3a3Jg+WaL/jolV0+N
5TzgHRmLRE7PyOnbgaL8ddzzpUIgQy83xnQG+bIP7NdtQWnpChAreJcX/fPmgAFH
ZpuMV7eXaPVsTr0J7QyNh0n7x5AiGHaGrShtClKd3atsLFrQSsHILPnpOqLVmM3Q
d8vcLQfPpBOJ4lp3Umm5HaYPrMLuRJgGPW69nBANKBr865NVvBY6eBiLk3obfBe/
QNfNEQKCAQBN9ruVk8b36E3kZwyeHjYctAea0deMhlxdvNfvLtsYFlRZe/zKwoqQ
m0AEcgcSUkn4rlf5Coa4xPIWzVTo8lpJyj2n9Ler3gYEgqNJyXcTUg5S3CI91Xpp
OrlsWeMv/YZElhyvTnK/GmioORJ9mncTW+Bc4iPOWOnA+lcDAIFkSO6AKWi8FA7D
kwmNn0AySxEB6s6fZE4/6QC+P3J2JGUBCtNXx0T8+H83PNgMrwKAz043BJTKI9Vx
5QA4qD56oEmYFb8JZD2rOiYBI/oBblfj5EdmF+M0K0Y2b9lnGzxIfYbXzmtwFxy8
FFLfv2cDoHVlHdkZeoocbxtV1xWol64JAoIBAQDW1E1qcG19pdGviceHL5IlMQ2S
WZlWQgn8P9wLbusCPVWvUnKl4xA64rLLVrBJQUuvohWxSZa+j8JZ2r0qACvzTTvy
ySoFivdERcX/5jU6M/VpMV1bgeFP9n1gYZA1+sluPVLN31WqmBSGGz/R1udqVyFl
FgR2r3QccSSW1y4QIgW9lSCdI4ieWc4lJcnsL+2kibfCCkZnPEToakSeVo1d93Ql
lrMs4e0ruWUBllE3HUx5iifRyWRyZK9SlNbghzch57Ke6prOyoUo0ysrjW7odLDx
debKNkk7tAj9OmQRNfY8PAnBb/Aj57+I7RIirFOMvY/+4sDhtBe07ui29zpX
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
-----END CERTIFICATE-----';
    $cwd = getcwd();
    if(!file_exists($infile)) die('文件不存在');
    if(strtoupper(substr(PHP_OS,0,3))==='WIN' and substr($infile, 1, 1) !== ':') die('须用绝对路径');
    if(openssl_pkcs7_decrypt($infile, $outfile, $cert, array($key, $pw))){
        return "解密成功 <br>\r\n";
    }else die("文件解密失败! <br>\r\n");
}
