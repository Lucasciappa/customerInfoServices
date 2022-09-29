<?php

require_once __DIR__ . '/../models/infoWebService.php';
require_once __DIR__ . '/verazSDK.php';
require_once __DIR__ . '/xmlFunctions.php';

class verazService extends infoWebService
{
    const PROD_URL = 'https://online.org.veraz.com.ar/pls/consulta817/wserv';
    const TEST_URL = 'https://prepro.online.org.veraz.com.ar/pls/consulta817/wserv';
    public $sandbox = false;
    public $data_field = 'par_xml';
    public $request_model_file = 'request_model.xml';
    public $request_model;
    public $request_headers;
    public $request_body;

    function __construct(string $format = null){
        if(file_exists(__DIR__ . '/' . $this->request_model_file)){
            $this->request_model = new SimpleXMLElement(__DIR__ . '/' . $this->request_model_file, LIBXML_NOCDATA, true);
            if ($format == 'T') {
                $this->request_model->identificador->formatoInforme = $format;
            }
        }
        if (!$this->request_model){
            $this->request_model = new SimpleXMLElement('<?xml version="1.0"?><mensaje></mensaje>');
        }
    }

    /** Ejecuto _getAllPersons( )_ para una persona */
    function getPerson(string $doc, string $gender)
    {
        return $this->getAllPersons(array(
            array('doc' => $doc, 'gender' => $gender)
        ));
    }

    /**
     * @param array $persons Debe ser una matriz donde cada posicion es un array que representa una persona a consultar
     *  y debe contener los valores para las posiciones 'doc' y 'gender'  
     * Ej.: ```array( array('doc' => 123, 'gender' => 'm'), array('doc' => 456, 'gender' => 'f'), ...)```
     */
    function getAllPersons(array $persons)
    {
        $this->resetLastResponse();
        $this->request_headers = null;
        $this->request_body = null;


        $body = $this->requestHandler($persons);
        $body = $this->getPostXML($body);
        $this->request_body = $body;

        $res = $this->post('/', $body);
        $this->setLastResponse($res);

        $res = $this->responseFormat($res);

        // Reviso errores
        $status = ($res['estado'] ?? false);
        if ($status) {
            if ($status['codigoError'] <> 0) {
                $this->setLastResponse($status['codigoError'], 'status');
                $this->setLastResponse($status['mensajeError'], 'status_detail');
            } elseif (isset($res['respuesta'])) {
                $this->setLastResponse('OK', 'status_detail');
                $res = $res['respuesta'];
            }
        }

        return $res;
    }

    function requestHandler(array $data): array
    {
        $body = array();

        if ($data) {
            $body['integrantes'] = count($data);

            foreach ($data as $i => $row) {
                $body['integrante valor="' . ($i+1) . '"'] = array(
                    'documento' => $row['doc'] ?? $row[0],
                    'sexo' => $row['gender'] ?? $row[1],
                );
            }
        }

        return $body;
    }

    function responseHandler($data): array
    {
        $res = $this->responseFormat($data);

        return ($res['respuesta'] ?? $res);
    }

    function responseFormat($data): array
    {
        if (!$data) {
            return array();
        }

        $xml = simplexml_load_string($data, NULL, LIBXML_NOCDATA);
        $res = simpleXML_to_array($xml);

        if (isset($res['respuesta']['integrantes'])) {
            if ($res['respuesta']['integrantes'] == 1) {
                $res['respuesta']['integrante'] = [$res['respuesta']['integrante']];
            }

            foreach ($res['respuesta']['integrante'] as &$person) {
                foreach ($person['variables']['variable'] as $var) {
                    if (isset($person[$var['nombre']])) {
                        $person[$var['nombre']] = trim($person[$var['nombre']]);
                    } else {
                        if (in_array($var['nombre'], ['domicilios', 'telefonos'])) {
                            $person[$var['nombre']] = explode('|', $var['valor']);
                        } else {
                            $person[$var['nombre']] = $var['valor'];
                        }
                    }
                }
                unset($person['variables']['variable']);
            }
        }

        return $res;
    }

    function getPostXML($body){
        $xml = clone $this->request_model;

        if(isset($xml->consulta)){
            array_to_xml($body, $xml->consulta);
        }else{
            array_to_xml($body, $xml);
        }

        return http_build_query(array(
            $this->data_field => $xml->asXML()
        ));
    }

    function get($uri, $params = null)
    {
        $result = $this->exec($uri, $params);
        return $result;
    }

    function post($uri, $body = null, $params = null)
    {
        $opts = array(CURLOPT_POST => true);
        if (!is_null($body)) {
            $opts += array(CURLOPT_POSTFIELDS => $body);
        }
        $result = $this->exec($uri, $params, $opts);
        return $result;
    }

    function exec($path, $params, $opts = null)
    {
        $path = $this->endpoint($path, $params);

        $ch = curl_init($path);
        curl_setopt_array($ch, array(
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADERFUNCTION => [$this, 'curlHeaderHandler'],
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
            )
        ));
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        if (!empty($opts))
        curl_setopt_array($ch, $opts);

        $res = curl_exec($ch);
        if ($res) {
            $this->request_headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        } else {
            $this->setLastResponse(curl_error($ch), 'status_detail');
        }

        curl_close($ch);
        return $res;
    }

    function endpoint($path, $params = array())
    {
        if (!preg_match("/^(https?:\/\/)?(([-\w]+)\.)?(?2)(?3)/", $path)) {
            $url = (!$this->sandbox ? self::PROD_URL : self::TEST_URL);
            $uri = $url;
            if ($path && $path <> '/') {
                if (!(preg_match("/^\//", $path) xor preg_match("/\/$/", $url))) {
                    if (preg_match("/^\//", $path)) $path = substr($path, 1);
                    else $path = '/' . $path;
                }
                $uri .= $path;
            }
        } else {
            $uri = $path;
        }

        if (!empty($params)) {
            $params = (!strpos($uri, '?') ? '?' : '&') . http_build_query($params);
            $uri = $uri . $params;
        }

        return $uri;
    }
}