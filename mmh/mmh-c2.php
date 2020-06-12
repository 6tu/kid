<?php

// highlight_file("caiji-mmh.php");
ignore_user_abort();
set_time_limit(0);
error_reporting(1);
date_default_timezone_set('America/New_York');

$loc = $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) .'/';

$mhdata = 'mmh-daily/';
$host = 'http://pub.6tu.me/caiji/' . $mhdata;

if(!is_dir($mhdata))mkdir($mhdata, 0777, true);
$cwd = getcwd();
$time = date("Y-n-j", time());
$file_zip = $time.'.zip';
$file_p7m = $file_zip . '.p7m';
if(!file_exists($mhdata . $file_p7m)){
    $url = $host . $file_p7m;
    $res_array = get_html($url);
    $html = $res_array;
    file_put_contents($mhdata . $file_p7m, $html);
}
$smime = pkcs7_decrypt($mhdata . $file_p7m);
echo $smime;
$log = unzip_file($mhdata . $file_zip, $mhdata);
echo $log;











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

function unzip_file($file, $destination){
    $zip = new ZipArchive();
    if($zip -> open($file) !== TRUE) die('Could not open archive');
    $zip -> extractTo($destination);
    $zip -> close();
    return " Archive extracted to directory <br><br>\r\n";
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
	// echo $infile_p7m . "\r\n" . $outfile . "\r\n";
    if(openssl_pkcs7_decrypt($infile_p7m, $outfile, $cert, array($key, $pw))){
        return ".p7m decrypted successfully <br><br>\r\n";
    }else die("failed to decrypt! <br>\r\n");
}



