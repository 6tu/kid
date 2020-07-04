<?php
/**
 * PHP 对气牛存储桶文件的基本操作
 * 
 * 删除文件每次最多不能超过1000个
 * 
 * 读取文件列举的条目数 $limit = 100;
 * 
 * 七牛云的对象存储 PHP SDK
 *
 * 官方SDK  https://developer.qiniu.com/sdk
 *          https://github.com/qiniu/php-sdk/releases
 *
 * 社区SDK(精简) https://developer.qiniu.com/sdk#community-sdk
 *               https://github.com/zither/simple-qiniu-sdk
 *               https://github.com/guoking/qiniu-php-sdk
 *
 * 官方档案中使用 test-env.sh 给系统中设置了 QINIU_ACCESS_KEY 等变量,
 * 然后使用getenv() 函数调用了这些变量, 对WINDOWS并不适用
 * 
 */

require_once __DIR__ . '/php-sdk-7.2.6/autoload.php';
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

$qiniupara = array(
    'qiniuurl'  => 'http://80luir.s3-cn-south-1.qiniucs.com/',
    'accessKey' => '',
    'secretKey' => '',
    'bucket'    => 'statics',
    'baseurl'   => 'http://qncdn.popcn.net/',
    );


echo "<pre>\n";

file_list_qiniu($qiniupara);

$file_local = 'php-logo.png';
$file_qiniu = 'php-logo.png';

// file_upload_qiniu($file_local, $file_qiniu, $qiniupara);

# $filePath 本地文件,$key 保存到七牛后的文件名, 一般定义为两者相同
function file_upload_qiniu($filePath, $key, $qiniupara){
    $puburl    = $qiniupara['qiniuurl'];
    $accessKey = $qiniupara['accessKey'];
    $secretKey = $qiniupara['secretKey'];
    $bucket    = $qiniupara['bucket'];
    $baseurl = $qiniupara['baseurl'];

    # $key = $filePath;

    $auth = new Auth($accessKey, $secretKey);
    $uploadMgr = new UploadManager();
    # 生成上传Token, 使用 insertOnly 覆盖性上传方法
    echo "\nUploading file to <b>$bucket</b> bucket ====>\n\n";
    echo "$filePath  ==>  $key\n";
    $token = $auth -> uploadToken($bucket, $key, 3600, array('insertOnly' => 0));
    list($ret, $err) = $uploadMgr -> putFile($token, $key, $filePath);
    if($err !== null){
        var_dump($err);
    }else{
        // var_dump($ret);
        echo "\nSuccessfully uploaded to <a href='". $baseurl . $ret['key'] ."'>qiniu</a><br>\r\n";
    }
}

function file_list_qiniu($qiniupara){
    $qiniuurl  = $qiniupara['qiniuurl'];
    $accessKey = $qiniupara['accessKey'];
    $secretKey = $qiniupara['secretKey'];
    $bucket    = $qiniupara['bucket'];
    $baseurl   = $qiniupara['baseurl'];

    $prefix = ''; # 公共前缀即文件目录
    $marker = ''; # 上次列举返回的位置标记，作为本次列举的起点信息。
    $limit = 100; # 本次列举的条目数
    $delimiter = '/'; # 目录定界符号

    $auth = new Auth($accessKey, $secretKey);
    $bucketManager = new BucketManager($auth);
    list($ret, $err) = $bucketManager -> listFiles($bucket, $prefix, $marker, $limit, $delimiter);
     
    if(empty($prefix)) $_prefix = '/';
    else $_prefix = $prefix;

    echo "\nFile list of $_prefix dir in <b>$bucket</b> bucket of Qiniu ====>\n\n";
    
    # 打印所有明细 // print_r($ret); 

    # 目录列表
    if(array_key_exists('commonPrefixes', $ret)) print_r($ret['commonPrefixes']);

    # 文件列表
    $keys = array();
    foreach($ret['items'] as $v){
        $keys[] .= $v['key'];
        $size = bytesize($v['fsize']);
        $puttime = number_format($v['putTime'], 0, '', '');
        $time = substr($puttime, 0, 10);
        $time = date("Y-m-d H:i:s", $time);
        $space = bytecomplement($v['key']); // str_pad($v['key'], 50); 
        echo $v['key'] . $space .'['. $time .'] ['. $size ."]\r\n";
    }

    if($err !== null){
        echo"\n====>list file err:\n";
        var_dump($err);
    }else{
        if(array_key_exists('marker', $ret)) echo "Marker:" . $ret["marker"] . "\n";
    }
    return $keys;
}

# 每次最多不能超过1000个
function file_delete_qiniu($qiniuurl, $keys){
    $qiniuurl  = $qiniupara['qiniuurl'];
    $accessKey = $qiniupara['accessKey'];
    $secretKey = $qiniupara['secretKey'];
    $bucket    = $qiniupara['bucket'];
    
    $auth = new Auth($accessKey, $secretKey);
    $config = new \Qiniu\Config();
    $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);

    echo "\nThe files in the <b>$bucket</b> bucket will be deleted ====>\n\n";

    // $keys = array('readme.rm','php-logo.png');
    $ops = $bucketManager->buildBatchDelete($bucket, $keys);
    list($ret, $err) = $bucketManager->batch($ops);
    if($err) print_r($err);
    else print_r($ret);
}

function bytesize($num){
    $bt = pow(1024, 1);
    $kb = pow(1024, 2);
    $mb = pow(1024, 3);
    $gb = pow(1024, 4);
    if(!is_numeric($num))$size = '非数字类型';
    if($num < 0)$size = '值为负数';
    if($num >= 0and$num < $bt)$size = $num . 'B';
    if($num >= $bt and $num < $kb)$size = floor(($num / $bt) * 100) / 100 .' KB';
    if($num >= $kb and $num < $mb)$size = floor(($num / $kb) * 100) / 100 .' MB';
    if($num >= $mb and $num < $gb)$size = floor(($num / $mb) * 100) / 100 .' GB';
    if($num >= $gb)$size = floor(($num / $gb) * 100) / 100 .'TB';
    return $size;
}

function bytecomplement($str){
    $bv = 40;
    $length = strlen($str);
    if($length < $bv){
        $dv = $bv - $length;
        $space = '';
        for($i = 0;$i < $dv;$i++) $space .= ' ';
    }else $space = '  ';
    return $space;
}




