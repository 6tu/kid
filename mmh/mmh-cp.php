<?php
/**
 *
 * 整理归档 MMH-Daily.
 *
 * date()接受年-月-日和月/日/年这种格式
 * 数组以$array_开头,文件名以$fn_开头,网址以$url_开头,
 * 不带文件名的路径以$path_开头,带文件名的路径以$file_开头,
 *
 */

ignore_user_abort();
set_time_limit(0);
error_reporting(1);
date_default_timezone_set("Etc/GMT+6"); # 比林威治标准时间慢6小时
date_default_timezone_set('America/New_York');
$current_date = date("Y-n-j", time());

$path = 'mhdata/';
$path_archives = $path . 'archives/';
$cwd = getcwd();
if(!file_exists($cwd . '/' . $path)) mkdir($cwd . '/' . $path, 0777, true);

$array_file = scandir($path);
foreach($array_file as $file){
    if(!is_file($file) == false) continue;
    $fn = $path . $file;
    if(strpos($fn, '.log')){
        $basename = strstr($fn, '.', true);
        $fn_log = $fn;
        $fn_zip = substr(md5($basename), 8, 16) . '.zip';
        if($basename === $current_date) continue;
        $array_fn = explode('-', $basename);
        if(strlen($array_fn[1]) == 1) $array_fn[1] = '0' . $array_fn[1];
        $date = $array_fn[0] . $array_fn[1];
        $path_date = $path_archives . $date;
        if(is_file($path_date)) unlink($path_date);
        if(!file_exists($path_date)) mkdir(iconv("UTF-8", "GBK", $path_date), 0755, true);
        copy($path . $fn_zip, $path_date . '/' . $fn_zip);
        copy($path . $fn_log, $path_date . '/' . $fn_log);
        unlink($path . $fn_zip);
        unlink($path . $fn_log);
    }
}
echo "\r\n<br>文件整理完毕<br>\r\n";

