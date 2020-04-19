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
$bead_weight = array(); 
$hole_weight = array(); 
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
    $bead_weight[$key] = M_PI * $a[$key] * pow($d,3)/6 ; 
    $bead_weight[$key] = round($bead_weight[$key], 3); 
    if(empty($e)) $hole_weight[$key] = "NULL"; 
    else{
        $hole_weight[$key] = M_PI * $a[$key] * pow($d,3)/6 - M_PI * $a[$key] * $d * pow($e,2)/4 ;
        $hole_weight[$key] = round($hole_weight[$key], 3);
    }
} 
$output = " 正圆珠直径mm: " . $d .  "                   | 穿孔直径mm: " . $e . PHP_EOL;
$output .= " 密度(g/cm³)常量 | 最小密度  1.050 | 最大密度  1.100 | 平均密度  1.075" . PHP_EOL;
$output .= " 穿孔前重量(g)   | 最小重量 " . @$bead_weight['min'] . " | 最大重量 " . @$bead_weight['max'] . " | 平均重量 " . @$bead_weight['ave'] . PHP_EOL;
$output .= " 穿孔后重量(g)   | 最小重量 " . @$hole_weight['min'] . " | 最大重量 " . @$hole_weight['max'] . " | 平均重量 " . @$hole_weight['ave'] . PHP_EOL;
echo $output;