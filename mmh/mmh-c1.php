<?php

ignore_user_abort();
set_time_limit(0);
error_reporting(1);
// echo date_default_timezone_get() . " <br>\r\n";
// date_default_timezone_set("Asia/Hong_Kong");
date_default_timezone_set('America/New_York');

$remote_url = 'https://shideyun.com/mmh/mhdata';

# 定义文件名及相关目录
$path_mmh = '/mhdata/';
$url_loc = $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . $path_mmh;

$time = date("Y-n-j", time());
if(empty($_GET['mhdaily'])){
    $file_mmh = $time . '-t.zip';
    $html = "<body><br><center>\r\n";
    $html .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="GET" />' . "\r\n";
    $html .= "  <b>?mhdaily= </b>\r\n";
    $html .= '  <input type="text" name="mhdaily" size=20 value="'.$file_mmh.'" />'."\r\n";
    $html .= '  <input type="submit" value="Send" />'."\r\n";
    $html .= "</form>\r\n</center></body>";
    echo $html;
    exit;
}else $file_mmh = $_GET['mhdaily'];

// $file_mmh = '2020-6-6-t.zip';

$cwd = getcwd();
// $cwd = dirname(__FILE__);
$path_mmh = $cwd . $path_mmh;
$daily = substr($file_mmh, 0, -4);
$file_mmh_daily = $path_mmh . $daily;

$sha512 = $file_mmh . '.sha512';
$file_zip = $path_mmh . 'p7m_' . $file_mmh . '.b64.zip';
$file_p7m = $path_mmh . 'p7m_' . $file_mmh . '.b64.eml';
$file_b64 = $path_mmh . $file_mmh . '.b64';

# 本地没有文件则从网络上获取文件。应该比较两个sha512
# 如果文件有效，则建立目录
$url = $remote_url . strrchr($file_zip, '/');
if(!file_exists($file_zip) or filesize($file_zip)/1024 < 50){
    $c = get_html($url);
    file_put_contents($file_zip, $c);
}else{
    // $c = file_get_contents($file_zip);
    // $hashed = hash('sha512', $c);
}

if(!is_dir($file_mmh_daily)) mkdir($file_mmh_daily, 0777, true);

# 由于 header()之前不能有输出，所以放这里
//header('Location: ' . $url_loc . $daily .'/'. $daily .'.html');
header("refresh:5;url=" . $url_loc . $daily .'/'. $daily .'.html');

echo 'Supported GET parameters <b>?mhdaily=</b>' . $file_mmh . " <br><br>\r\n";
# 还原文件
$log = unzip_file($file_zip, $path_mmh);
echo '.zip' . $log;
$array = certification();
if(openssl_pkcs7_decrypt($file_p7m, $file_b64, $array[0], $array[1])){
    echo ".eml decrypted successfully <br><br>\r\n";
}else die("failed to decrypt! <br>\r\n");
$base64_dec = base64_decode(file_get_contents($file_b64));
file_put_contents($path_mmh . $file_mmh, $base64_dec);
$log = unzip_file($path_mmh . $file_mmh, $file_mmh_daily);
echo "File restore complete <br><br>\r\n";

// unlink($file_zip);
unlink($file_p7m);
unlink($file_b64);
unlink($path_mmh . $file_mmh);

exit();


/** ========= 函数区 ========= **/

function get_html($url){
    $path_parts = pathinfo($url);
    $refer = $path_parts['dirname'] . '/' . $_SERVER['PHP_SELF'];
    $option = array('http' => array('header' => "Referer : $refer"), 
                    'ssl' => array('verify_peer' => false, 'verify_peer_name' => false,),
                    );
    $html = @file_get_contents($url, false, stream_context_create($option));
    if($html === false) die('Failed ' . $http_response_header[0]);
    else{
        echo $http_response_header[0] . " <br><br>\r\n";
        return $html;
    }
}

function unzip_file($file, $destination){
    $zip = new ZipArchive();
    if($zip -> open($file) !== TRUE) die('Could not open archive');
    $zip -> extractTo($destination);
    $zip -> close();
    return " Archive extracted to directory <br><br>\r\n";
}

function compress($txtname, $zipname){
    if(file_exists($zipname)) unlink($zipname);
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
    echo " Create a compressed file $zipname success <br>\r\n";
}
function certification(){
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
    return array($cert, array($key, $pw));
}


