<?php

class nosis
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

    function get($doc)
    {
        $query = self::CONF;
        $query['ConsXML_Doc'] = $doc;
        $url = self::ENDPOINT . '?' . http_build_query($query);

        $request = simplexml_load_file($url);

        if ( isset($request->URL) ) {
            $response = simplexml_load_file($request->URL);
            return $response;
        }
    }
}

?>