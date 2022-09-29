<?php
require_once __DIR__ . '/../models/infoWebService.php';

class nosisService extends infoWebService
{
    public const ENDPOINT = 'https://sac.nosis.com/SAC_ServicioSF_SSL/Consulta.asp' ;
    public const CONF = array(
        'Usuario' => 391633,
        'Password' => 248608,
        'NroConsulta' => 2234,
        'Cons_CDA' => 2,
        'Cons_SoloPorDoc' => 'Si',
        'ConsXML_Doc' => null,
        'ConsXML_Filtro' => 'VI',
        'ConsXML_Setup' => '',
        'ConsXML_MaxResp' => 100,
        'ConsHTML_Filtro' => 'VI',
        'ConsHTML_MaxResp' => 100
    );

    function getPerson(string $doc)
    {
        $url = self::requestHandler($doc);

        $request = simplexml_load_file($url);

        if ( isset($request->URL) ) {
            $this->resetLastResponse();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $request->URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'curlHeaderHandler']);
            $rs = curl_exec($ch);

            if ($rs) {
                $this->setLastResponse($rs);
                $response = $this->responseHandler($rs);

                return $response;
            }
        }
    }

    static function requestHandler(string $doc)
    {
        $query = self::CONF;
        $query['ConsXML_Doc'] = $doc;
        $url = self::ENDPOINT . '?' . http_build_query($query);

        return $url;
    }

    function responseHandler(string $response)
    {
        return simplexml_load_string($response);
    }
}
?>