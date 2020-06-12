<?php
if(php_sapi_name() !== "cli"){
    echo PHP_EOL . " Execute script in non-cli mode , 非 CLI 模式执行脚本";
    exit;
}
exec("chcp 65001");
echo PHP_EOL . " ====== 计算圆珠穿孔重量 ======" . PHP_EOL . PHP_EOL;
if(count($argv) < 2){
    echo PHP_EOL . " 使用方法 php " . __FILE__ . " bd hd" . PHP_EOL;
    echo PHP_EOL . " 命令参数中为 bd 圆珠直径，hd 为孔径(非必需参数)" . PHP_EOL;
    echo PHP_EOL . " bd hd 皆为正数，且 bd 大于 hd" . PHP_EOL;
    exit;
}
// print_r($argv);
$d = $argv[1];
$e = $argv[2];
$bw = array();
$hw = array();
if(!is_numeric($d) or $d < 0){
    echo PHP_EOL . " 圆珠直径非数字或为负值";
    exit;
}
if(!is_numeric($e) or $e < 0 or $e > $d){
    echo PHP_EOL . " 孔径非数字或为负值或大于圆珠直径";
    $e = "";
}
$a = array("min"=>"1.050", "max"=>"1.100", "ave"=>"1.075",);
foreach($a as $key => $value){
    $bw[$key] = M_PI * $a[$key] * pow($d,3)/6 ;
    $bw[$key] = number_format(round($bw[$key], 3), 3, '.', '');
    if(empty($e)) $hw[$key] = "NULL";
    else{
        $hw[$key] = M_PI * $a[$key] * pow($d,3)/6 - M_PI * $a[$key] * $d * pow($e,2)/4 ;
        $hw[$key] = number_format(round($hw[$key], 3), 3, '.', '');
    }
}
# 输出长度控制
$len_max = strlen($bw['max']);
$dlen = $len_max + 11;
$d = str_pad($d, $dlen);

$alen = $len_max - strlen($a['max']);
$astr = '';
for($i =0; $i < $alen; $i++){
    $astr .= ' ';
}
$hlen = $len_max - strlen($hw['max']);
$hstr = '';
for($i =0; $i < $hlen; $i++){
    $hstr .= ' ';
}
$minstr = " | 最小重量 ";
$maxstr = " | 最大重量 ";
$avestr = " | 平均重量 ";
$output = " 正圆珠直径(mm): " . $d . " | 穿孔直径(mm): " . $e . PHP_EOL;
$output .= " 密度(g/cm³)常量 | 最小密度 " . @$a['min'] . $astr . " | 最大密度 " . @$a['max'] . $astr . " | 平均密度 " . @$a['ave'] . $astr . PHP_EOL;
$output .= " 穿孔前重量(g)  " . $minstr .@$bw['min'] . $maxstr . @$bw['max'] . $avestr . @$bw['ave'] . PHP_EOL;
$output .= " 穿孔后重量(g)  " . $minstr .@$hw['min'] . $hstr . $maxstr . @$hw['max'] . $hstr . $avestr . @$hw['ave'] . PHP_EOL;
echo $output;
