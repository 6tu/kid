<?php
$str = file_get_contents('20290282.html');
$str = file_get_contents('index.txt');
$str = mb_convert_encoding($str, "GBK", "UTF-8");

# 标签 tag
$element = 'p';
$element_type = 'tag';

# DIV标签中的 id
// $element = 'content';
// $element_type = 'id';
 
# DIV标签中的 class
// $element = 'bottem2';
// $element_type = 'class';

$html = dom_import_html($str, $element, $element_type);
// $html = mb_convert_encoding($html, "UTF-8", "GBK");
echo $html;

# DOMDocument 针对UTF-8处理
# $element_type 只能是 tag 、 id 和 class
function dom_import_html($str, $element, $element_type){
    $dom = new DOMDocument("1.0","UTF-8");

    $dom->formatOutput=false;
    $dom->preserveWhiteSpace=true;
    
    $dom->validateOnParse=false;
    $dom->standalone=true;
    $dom->strictErrorChecking=false;
    $dom->recover=true;

    $dom -> loadHTML($str);
    $tr = $dom->createElement('br');

    # HTML标签
    # appendChild() [追加]参数为对象，且只能是一个(Object(DOMNodeList)
    // $object = $dom -> getElementsByTagName($element) -> item(2); # 指定第2个标签
    if(strtolower($element_type) == 'tag'){
        $object = $dom -> getElementsByTagName($element);
        foreach ($object as $node) {
            // $dom -> appendChild($tr);
            $dom -> appendChild($node);
        }
    }
    if(strtolower($element_type) == 'id'){
        $object = $dom -> getElementById($element);
        $dom -> appendChild($object);
    }
    if(strtolower($element_type) == 'class'){
        $class = getElementsByClassName($dom, $ClassName = $element, $tagName = null);
        $object = $class[0];
        $object = $dom -> getElementById($element);
        $dom -> appendChild($object);
    }
    // echo @$object->length;
    $html = $dom -> saveHTML();
    $html_array = explode('</html>', $html);
    $html = trim($html_array[1]);
    // echo $html . "\r\n<br><br><br>\r\n";
    // flush(); # 刷新缓存（直接发送到浏览器)
    ob_start();
    ob_end_flush(); # 关闭缓存
    return $html;
}

# 返回数组，$Matched[0]为对象
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
    $dom = new DOMDocument();
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
