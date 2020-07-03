<?php
# 计算圆珠穿孔前后重量
# π值为 M_PI 常量或者 pi() 函数
# 圆珠直径为必须变量 $d = $_GET['bd'];
# 孔径 $e = $_GET['hd'];
# 都是正数，且 $a 大于 $e

$log = '';
$bead_weight = array();
$hole_weight = array();
if(empty($_GET['bd'])){
    echo output_html($bead_weight, $hole_weight, $log);
    exit;
}
$d = $_GET['bd']; 
if(empty($_GET['hd'])) $e = '';
else $e = $_GET['hd'];
if(!is_numeric($d) or $d < 0){
    $log = '圆珠直径非数字或为负值';
    $d = '';
    $e = '';
}
if(!is_numeric($e) or $e < 0 or $e > $d){
    $log = '孔径非数字或为负值或大于圆珠直径';
    $e = '';
}
$a = array("min"=>"1.050", "max"=>"1.100", "ave"=>"1.075",);
foreach($a as $key => $value){
    $bead_weight[$key] = M_PI * $a[$key] * pow($d,3)/6 ;
    if(empty($e)) $hole_weight[$key] = 'NULL';
    else $hole_weight[$key] = M_PI * $a[$key] * pow($d,3)/6 - M_PI * $a[$key] * $d * pow($e,2)/4 ;
}
echo output_html($bead_weight, $hole_weight, $log);
echo "\r\n<br><hr><br>\r\n";

function output_html($bead_weight, $hole_weight, $log){
    if(empty($log)) $log = '';
    if(empty($bead_weight)){
        $bead_weight['min'] = '';
        $bead_weight['max'] = '';
        $bead_weight['ave'] = '';
    }else{
        $bead_weight['min'] = round($bead_weight['min'], 3);
        $bead_weight['max'] = round($bead_weight['max'], 3);
        $bead_weight['ave'] = round($bead_weight['ave'], 3);
    }
    if(empty($hole_weight)){
        $hole_weight['min'] = '';
        $hole_weight['max'] = '';
        $hole_weight['ave'] = '';
    }else{
        $hole_weight['min'] = round($hole_weight['min'], 3);
        $hole_weight['max'] = round($hole_weight['max'], 3);
        $hole_weight['ave'] = round($hole_weight['ave'], 3);
    }
	$html = '
<html>
<head>
    <meta charset="UTF-8">
    <title>计算圆珠穿孔重量</title>
</head>
<body>
<br><center><h2>计算圆珠穿孔重量</h2></center>
<form action="" method="get">
<table border="1" width="700" cellspacing="0" cellpadding="5" align="center">
    <tr>
    	<td colspan="2">正圆珠直径mm: <input type="text" name="bd"/></td>
    	<td colspan="2">穿孔直径mm: <input type="text" name="hd"/></td>
    </tr>
    <tr>
    	<td align="left" colspan="3" style="color:red;border-right-style:none"><pre> '. $log . '</pre></td>
    	<td align="center" colspan="1" style="border-left-style:none"><input type="submit" value="Submit"/></td>
    </tr>
    <tr>
    	<td>密度(g/cm<sup>3</sup>)常量 </td>
    	<td>最小密度 <span style="float:right"><b>1.050 </b></span></td>
    	<td>最大密度 <span style="float:right"><b>1.100 </b></span></td>
    	<td>平均密度 <span style="float:right"><b>1.075 </b></span></td>
    </tr>
    <tr>
    	<td>穿孔前重量(g) </td>
    	<td>最小重量 <span style="color:red;float:right"><b>'. @$bead_weight['min'] .' </b></span></td>
    	<td>最大重量 <span style="color:red;float:right"><b>'. @$bead_weight['max'] .' </b></span></td>
    	<td>平均重量 <span style="color:red;float:right"><b>'. @$bead_weight['ave'] .' </b></span></td>
    </tr>
    <tr>
    	<td>穿孔后重量(g) </td>
    	<td>最小重量 <span style="color:red;float:right"><b>'. @$hole_weight['min'] .' </b></span></td>
    	<td>最大重量 <span style="color:red;float:right"><b>'. @$hole_weight['max'] .' </b></span></td>
    	<td>平均重量 <span style="color:red;float:right"><b>'. @$hole_weight['ave'] .' </b></span></td>
    </tr>
    <tr>
    	<td align="left" colspan="2"><pre>JS 示例: http://www.bofeng.org/sphere/ </pre></td>
    	<td align="left" colspan="2"><pre>PHP示例: http://1rmb.net/zl.php </pre></td>
    </tr>
</table>
</form>
</body>
</html>
';
    return $html;
}

show_source(__FILE__); # 显示当前PHP源码
