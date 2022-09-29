<?php
require_once __DIR__ . '/../models/customerInfoAdder.php';
require_once __DIR__ . '/nosisDbInfoService.php';
require_once __DIR__ . '/nosisService.php';
require_once __DIR__ . '/nosisAddressParser.php';

class nosisCustomerInfo extends customerInfoAdder
{
    //ID en la tabla info_verification_type para este servicio
    protected $service_id = 5;
    public $curr_doc;

    function getInfo(): ?array
    {
        if (!$this->customer->doc) {
            return null;
        }

        $doc = $this->customer->doc;
        if ($this->info && $this->curr_doc == $doc) {
            return $this->info;
        }

        $this->info = array();
        $this->curr_doc = $doc;

        $service = new nosisService;
        $db_service = new nosisDbInfoService($service);
        if ($db_service->dbConn) {
            $service = $db_service;
        }
        $response = $service->getPerson($doc);
        $parser = new nosisAddressParser;

        foreach ($response->ParteXML->Dato as $data) {
            $addresses = array();

            if (isset($data->Clave)) {

                // Domicilio fiscal con minimo el cp
                if (isset($data->DomFiscal, $data->DomFiscal->CP)) {
                    $dom = $data->DomFiscal;
                    $fields = array('street', 'street_number', 'apt_floor', 'apt_number');

                    $address = $parser->parseAddress($dom->Dom, $fields);

                    if (!empty($address)) {
                        // Completo el campo que guarda la direccion completa
                        $raw = $dom->Dom;
                        $raw .= " - ({$dom->CP->__toString()})";
                        if (isset($dom->Loc)) $raw .= " - {$dom->Loc->__toString()}";
                        $raw .= " - {$dom->Prov->__toString()}";
                        $address = [$this->customer->raw_address => $raw] + $address;

                        $address += array(
                            'postcode' => $dom->CP->__toString(),
                            'city' => $dom->Loc->__toString() ?? '',
                            'zone' => $dom->Prov->__toString()
                        );

                        $addresses[] = $address;
                    }
                }

                // Domicilios alternativos
                if (isset($data->DomAlternativos->Doms->Dom)) {
                    foreach ($data->DomAlternativos->Doms->Dom as $dom){
                        $address = $parser->parseAddress($dom);
                        if (!empty($address)) {
                            $addresses[] = [$this->customer->raw_address => $dom->__toString()] + $address;
                        }
                    }
                }

                $this->info[] = array(
                    'firstname' => $data->Nombre->__toString(),
                    'lastname' => $data->Apellido->__toString(),
                    'doc' => $doc,
                    'gender' => substr($data->Sexo->__toString(), 0, 1),
                    'shipping_address' => $addresses
                );
            }
        }

        return $this->info;
    }
}
?>
