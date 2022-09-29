<style>
    body>div{
        display: flex;
    }
    div>div{
        width: calc(50% - 55px);
        float: left;
        border: 2.5px solid;
        border-radius: 10px;
        padding: 15px;
        margin: 10px;
        background-color: aliceblue;
        overflow: auto;
    }
</style>

<?php

$doc=($_GET['doc']??22430621);

// Test Nosis
require '../../nosis/nosisService.php';

$nosis = new nosisService();

$response = $nosis->getPerson($doc);

echo '
    <div><div style="width: calc(100% - 55px);">
        <h2><u>Nosis Complete</u>:</h2>
        <pre>
';
var_dump($response);
echo '
        </pre>
    </div></div>
    ';

$data = $response->ParteXML->Dato;
$addresses = array();

if (isset($data->Clave)) {
    $pattern = '/^(.+?)(?: (?:Nro:)?(\d+))(?: Piso (\w+))?(?: - Dto (\w+))?$/';
    $parsed = array();
    $fields = array('raw_address', 'street', 'street_number', 'apt_floor', 'apt_number');

    if (isset($data->DomFiscal)) {
        $dom = $data->DomFiscal;
        if (preg_match($pattern, $dom->Dom, $dom_info)) {
            foreach ($dom_info as $i => $val) {
                $parsed[$fields[$i]] = $val;
            }
            $parsed['raw_address'] .= " - ({$dom->CP->__toString()}) - {$dom->Loc->__toString()} - {$dom->Prov->__toString()}";
        }

        $addresses[] = array(
            'name' => $data->Nombre->__toString(),
            'lastname' => $data->Apellido->__toString(),
            'gender' => $data->Sexo->__toString(),
            'shipping_address' => $parsed + array(
                'postcode' => $dom->CP->__toString(),
                'city' => $dom->Loc->__toString(),
                'zone' => $dom->Prov->__toString(),
                'street' => $dom->Dom->__toString(),
            )
        );
    }

    $alt_addrs = $data->DomAlternativos->Doms->Dom;
}

echo '<div>';

echo '
    <div>
        <h2><u>Nosis</u>:</h2>
        <pre>
';
var_dump($addresses);
echo '
        </pre>
    </div>
';

// Test Padron
require '../../ws_sr_padron/ws_sr_padron_a5.php';

$padron = new ws_sr_padron_a5();

$response = $padron->getPerson($doc);
$addresses = array();

foreach ($response as $row) {
    if (isset($row->personaReturn->datosGenerales)) {
        $data = $row->personaReturn->datosGenerales;
        $dom = $data->domicilioFiscal;
        $addresses[] = array(
            'name' => $data->nombre,
            'lastname' => $data->apellido,
            'shipping_address' => array(
                'postcod' => $dom->codPostal,
                'city' => $dom->descripcionProvincia,
                'address' => $dom->direccion,
            )
        );
    } elseif (isset($row->personaReturn->errorConstancia)) {
        $data = $row->personaReturn->errorConstancia;
        if (isset($data->nombre)) {
            $addresses[] = array(
                'name' => $data->nombre,
                'lastname' => $data->apellido
            );
        } else {
            $addresses[] = array( 'fullname' => $data->apellido );
        }
    }
}

echo '
    <div>
        <h2><u>Padron</u>:</h2>
        <pre>
';
var_dump($addresses);
echo '
        </pre>
    </div>
    ';

echo '</div><div>';

// Alternative Doms
$alt_addrs;

$pattern = '/^(.+?)(?: (?:Nro:)?(\d+))(?: Piso (\w+))?(?: - Dto (\w+))?(?: - \((\d+)\))(?: - ([^-]+))?(?: - ([^-]+))$/';
$addresses = array();
$parsed = array();
foreach ($alt_addrs as $dom){
    $addresses[] = $dom->__toString();
    preg_match($pattern, $dom, $dom_info);
    $parsed[] = $dom_info;
}

echo '
    <div>
        <h2><u>Alternative Doms</u>:</h2>
        <pre>
';
var_dump($addresses);
echo '
        </pre>
    </div>
';

// Parsed Doms
$fields = array('raw_address', 'street', 'street_number', 'apt_floor', 'apt_number', 'postcode', 'city', 'zone');

$addresses = array();
foreach ($parsed as $i => $dom) {
    foreach ($dom as $j => $val) {
        $addresses[$i][$fields[$j]] = $val;
    }
}

echo '
    <div>
        <h2><u>Parsed Doms</u>:</h2>
        <pre>
';
var_dump($addresses);
echo '
        </pre>
    </div>
';

echo '</div>';

?>