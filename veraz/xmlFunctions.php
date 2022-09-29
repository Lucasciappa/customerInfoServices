<?php

/** function defination to convert array to xml
 * 
 * Use example:
 * ```
 * // initializing or creating array
 * $data = array('total' => 500);
 * // agrego la opcion de agregar atributos
 * $data = array('total currency="usd"' => 500);
 *
 * // creating object of SimpleXMLElement
 * $xml_data = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
 *
 * // function call to convert array to xml
 * array_to_xml($data,$xml_data);
 *
 * //saving generated xml file; 
 * $result = $xml_data->asXML('/file/path/name.xml');
 * ```
 */
function array_to_xml( $data, &$xml_data ) {
    foreach( $data as $key => $value ) {
        $attrs = array();

        if( preg_match('/^[^ ]+( +[^=]+=.*)+$/', $key) ) {
            $attrs = explode(' ', $key);
            $key = array_shift($attrs);
        }

        if( is_array($value) ) {
            if( is_numeric($key) ){
                $key = 'item'.$key; //dealing with <0/>..<n/> issues
            }
            $subnode = $xml_data->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $subnode = $xml_data->addChild("$key",htmlspecialchars("$value"));
        }

        if( $attrs ) {
            foreach ($attrs as $attr) {
                if ($attr) {
                    list($k, $v) = explode('=', $attr);
                    $subnode->addAttribute($k, trim($v, ' \'"'));
                }
            }
        }
    }
}

/** function defination to convert simpleXMLobject to array **/
function simpleXML_to_array($xml)
{
    $array = (array)$xml;

    if (count($array) === 0) {
        return (string)$xml;
    }

    foreach ($array as $key => $value) {
        // if (!is_object($value) || strpos(get_class($value), 'SimpleXML') === false) {
        if (!is_array($value) && (!is_object($value) || strpos(get_class($value), 'SimpleXML') === false)) {
            continue;
        }
        $array[$key] = simpleXML_to_array($value);
    }

    return $array;
}
?>