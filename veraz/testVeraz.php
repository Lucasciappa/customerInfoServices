<?php
require './verazService.php';

$veraz = new verazService;

//Mando directamente contenido de un archivo xml
// $body = array( 'par_xml' => file_get_contents('request.xml') );
// $res = $veraz->post($veraz::TEST_URL, $body);

$res = $veraz->getPerson(92088024, 'M');

if (isset($res['respuesta']['integrante']['variables']))

echo '<pre style="margin: 10px 10%;padding: 15px;border: solid;">';
var_dump( $res );
echo '</pre>';