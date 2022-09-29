<?php

class ws_sr_padron_a5
{
    public const URL = 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5';
    public const WSDL = 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5?WSDL';
    public const SRC_PATH = __DIR__ . '/';
    public const OWNER_CUIT = 30708477148;
    private const CI = array( 'M' => 20, 'F' => 27 );
    protected $credentials;
    public $client;

    function __construct()
    {
        ini_set("soap.wsdl_cache_enabled", "0");

        $this->setCredentials();

        $this->client = new soapClient(self::WSDL, array(
            'soap_version'=>SOAP_1_1,
            'location'=>self::URL,
            'exceptions'=>0,
            'trace'=>1
        ));
    }

    protected function setCredentials(): void
    {
        //	Leo el TA
        $TA = simplexml_load_file(self::SRC_PATH . "TA_a5.xml");

        //	Verifico si expiró para pedir uno nuevo
        if (strtotime($TA->header->expirationTime) < time()) {
            if ( !class_exists('WSAA') )
                require self::SRC_PATH . 'wsaa-padron.class.php';

            $wsaa = new WSAA('ws_sr_padron_a5');
            if ($wsaa->generar_TA()) {
                $TA=simplexml_load_file(self::SRC_PATH . "TA_a5.xml");
            } else {
                trigger_error('No se pudo generar el nuevo TA', E_USER_WARNING);
                return;
            }
        }

        //	Seteo credenciales
        $this->credentials = (array) $TA->credentials;
        $this->credentials['cuitRepresentada'] = self::OWNER_CUIT;
    }

    function getPerson(int $doc): array
    {
        $params = $this->credentials;
        $cuits = $this->docToCuits($doc);
        $results = array();

        foreach ($cuits as $cuit) {
            $params['idPersona'] = $cuit;
            $response = $this->client->getPersona($params);
            if (isset($response->personaReturn))
                $results[] = $response;
        }

        return $results;
    }

    function docToCuits(int $doc): array
    {
        $cuits = array();

        switch(strlen($doc)){
            case 11: $cuits[] = $doc; break;
            case 7:	$doc='0'.$doc;
            case 8:
                foreach (self::CI as $ci)
                    $cuits[] = $this->getCuit($ci, $doc);
                break;
        }

        return $cuits;
    }

    function getCuit(int $ci, string $dni): int
    {
        $s = 0;
        $cuit = $ci.$dni;
        foreach ([5,4,3,2,7,6,5,4,3,2] as $i => $v)
            $s += $v * $cuit[$i];
        $r = $s%11;
        switch($r){
            case 0: $dv = 0; break;
            case 1: switch($ci){
                        case 20: $dv = 9; $ci = '23'; break;
                        case 27: $dv = 4; $ci = '23'; break;
                        case 30: $dv = 1; break;
                    } break;
            // default: if (!isset($dv)) $dv = 11-$r;
            default: $dv = 11-$r;
            break;
        }
        return $ci.$dni.$dv;
    }

    function dummy(): object
    {
        return $this->client->Dummy()->return;
    }
}

?>