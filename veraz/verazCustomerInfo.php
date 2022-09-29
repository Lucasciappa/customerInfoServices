<?php
require_once __DIR__ . '/../models/customerInfoAdder.php';
require_once __DIR__ . '/verazDbInfoService.php';
require_once __DIR__ . '/verazService.php';
require_once __DIR__ . '/verazAddressParser.php';

class verazCustomerInfo extends customerInfoAdder
{
    //ID en la tabla info_verification_type para este servicio
    protected $service_id = 6;
    public $curr_doc;
    public $curr_gender;

    function getInfo(): ?array
    {
        if (!$this->customer->doc || !$this->customer->gender) {
            return null;
        }

        $doc = $this->customer->doc;
        $gender = $this->customer->gender;
        if ($this->info && $this->curr_doc == $doc && $this->curr_gender == $gender) {
            return $this->info;
        }

        $this->info = array();
        $this->curr_doc = $doc;
        $this->curr_gender = $gender;

        $service = new verazService;
        $db_service = new verazDbInfoService($service);
        if ($db_service->dbConn) {
            $service = $db_service;
        }
        $response = $service->getPerson($doc, $gender);
        $parser = new verazAddressParser;

        if (isset($response['integrante'])) {
            foreach ($response['integrante'] as $person) {
                list($lastname, $firstname) = explode(',', $person['nombre']);
                $addresses = array();

                foreach ($person['domicilios'] as $dom) {
                    $address = $parser->parseAddress($dom);
                    $addresses[] = [$this->customer->raw_address => $dom] + $address;
                }

                $this->info[] = array(
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'doc' => $doc,
                    'doc_validation' => ($person['validacion_documento']=='SI'),
                    'gender' => $person['sexo'],
                    'shipping_address' => $addresses,
                    'phone' => $person['telefonos'],
                );
            }
        }

        return $this->info;
    }
}