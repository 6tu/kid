<?php
$str = file_get_contents('20290282.html');
$dom = new DOMDocument();
$dom -> loadHTML($str);
$chap = $dom -> getElementsByTagName('h1') -> item(0); # HTML标签
$content = $dom -> getElementById('content'); # HTML标签中的id属性
$bottem2 = getElementsByClassName($dom, $ClassName = 'bottem2', $tagName = null); # 返回数组，$bottem2[0]为对象
$dom -> appendChild($chap); # appendChild() [追加]参数为对象
$dom -> appendChild($content);
$dom -> appendChild($bottem2[0]);
$html = $dom -> saveHTML();
$html_array = explode('</html>', $html);
$html = trim($html_array[1]);
echo $html . "\r\n<br><br><br>\r\n";

function getElementsByClassName($dom, $ClassName, $tagName = null){
    if($tagName) $Elements = $dom -> getElementsByTagName($tagName);
    else $Elements = $dom -> getElementsByTagName("*");
    $Matched = array();
    for($i = 0; $i < $Elements -> length; $i++){
        if($Elements -> item($i) -> attributes -> getNamedItem('class')){
            if($Elements -> item($i) -> attributes -> getNamedItem('class') -> nodeValue == $ClassName){
                $Matched[] = $Elements -> item($i);
            }
        }
    }
    return $Matched;
}

function innerHTML($element){
    $dom = newDOMDocument();
    # 在新建的DOMDocument追加对象时会自动HTML-ENTITIES
    $dom -> substituteEntities = false;
    # importNode()导入到别的DOM档案，当前档案无需导入
    $dom -> appendChild($dom -> importNode($element, TRUE));
    $html = trim($dom -> saveHTML());
    $tag = $element -> nodeName;
    return $html;
    // return preg_replace('@^<' . $tag . '[^>]*>|</' . $tag . '>$@', '', $html);
}

function htmlentities_decode($str){
    $str = "<?W3S?h????>hello world! 世界你好！";
    $str = mb_convert_encoding($str, 'HTML-ENTITIES', 'gb2312');
    $str = mb_convert_encoding($str, "UTF-8", "HTML-ENTITIES");
    echo htmlentities($str);
    # 在网页头部加上如下三句可转换整个页面
    mb_internal_encoding('你网站的编码');
    mb_http_output('HTML-ENTITIES');
    ob_start('mb_output_handler');
}
