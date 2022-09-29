<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Nueva parent class para los objetos que hacen peticiones de infomacion a un servicio externo.
 * Sirve para guarda las transacciones y usarlas como caché para ahorrar consultas.
 * 
 * @param infoWebService $service Objeto que requiere extender de esta clase para usar sus metodos.
 * @var int $service_id ID de la tabla services, redefinir en la child class del webservice.
 * @var string $newer_than Tiempo en _SQL Interval_ en que la respuesta aun se considera valida.
 * @method getPerson
 * Extiende a la funcion del objeto que se pasa por el constructor. Por defecto se extiende sin mas.  
 * Se debe redeclarar en las instancias concretas para interceptar la peticion y usar las funciones de consulta y guardado en la db.
 * ```
 *  // Ejemplo de redeclaracion de getPerson y uso de los db methods:
 * function getPerson($doc)
 * {
 *  $res = $this->getDbResponse($doc);
 *  if (!$res) {
 *      $req_id = $this->saveDbRequest(['data' => $doc]);
 *      try {
 *          $res = $this->service->getPerson($doc);
 *          $this->saveDbResponse($req_id, ['data' => $res]);
 *      } catch (Throwable $e) {
 *          $res = false;
 *          $this->saveDbResponse($req_id, [
 *              'data' => $res,
 *              'status' => 500,
 *              'status_datail' => $e->getMessage()
 *          ]);
 *      }
 *  }
 *  return $res;
 * }
 * ```
 * 
 * @author Jasu - Junio/2021
 */
abstract class dbInfoService
{
    protected $service_id = 0;
    protected $service;
    public $newer_than = '3 month';
    public $dbConn;

    function __construct(infoWebService $service)
    {
        global $webServiceConn;
        $this->dbConn = $webServiceConn;
        $this->service = $service;
    }

    /**
     * #### Busca informacion guardada en la db ####
     * @param mixed $data Por defecto se espera un array con los parametros clave para la peticion.
     *  El tercer parametro de la funcion cambia el valor esperado en esta variable.
     * @param string $method Metodo utilizado en la peticion, debe ser constante con las mayúsculas.
     *  Ej.: "GET", "POST", etc.
     * @param boolean $key_match Opcional. Indica como se va a buscar la peticion. El valor por defecto es false.
     * - Si se pasa FALSE se se espera un string con toda la info en la variable $data con la que se haria la peticion, para comparar con el campo request_body de la db.
     * - Si se pasa TRUE se se espera los campos clave en la variable $data para comparar con el campo request_key_params de la db.
     * @param array $extra_params Campos adicionales para filtar el resultado de la consulta. Por defecto filtra por *status = 200*.
     */
    function getDbResponse($data, string $method = null, bool $key_match = false, array $extra_params = ['status' => 200]): ?string
    {
        $search_field = ( $key_match
            ? 'request_key_params = CAST(? AS JSON)'
            : 'request_body = ?'
        );
        if (is_array($data)) $data = json_encode($data);
        $find = array($this->service_id, $method, $data);

        // Filtros extra
        foreach ($extra_params as $colum => $value) {
            if (!preg_match('/^response_|id$/', $colum)) {
                $colum = 'response_' . $colum;
            }
            $search_field .= " AND $colum = ?";
            array_push($find, $value);
        }

        // query info en db
        $rs = $this->dbConn->prepare("SELECT response_body FROM requests
            LEFT JOIN responses USING(request_id)
            WHERE request_date + INTERVAL $this->newer_than > NOW() AND
                service_id = ? AND request_method = ? AND $search_field
            ORDER BY LENGTH(response_body) DESC LIMIT 1");
        $info = $rs->execute($find);

        if ($info) $info = $rs->fetchObject();

        return ( $info ? $info->response_body : null );
    }

    /**
     * #### Guarda la peticion en la db ####
     * @param array $request_data Datos de la peticion. La estructura del array es inportante:  
     * array( 'params' => $params, 'data' => $data, 'method' => $method, 'headers' => $headers );  
     * No todos los datos son obligatorios, se debe usar al menos la posicion **params** o **data**.
     * 
     * En detalle representan:
     * - **params**: Un array con los campos clave para hacer la peticion.
     * - **data**: Un string con toda la data que se manda en la peticion.
     * - **method**: Un string con el metodo de la peticion. Posibles: 'GET', 'POST', 'SOAP', ect.
     * - **headers**: Un string con el header que se manda en la peticion.
     */
    function saveDbRequest(array $request_data)
    {
        @list(
            'params' => $params, 'body' => $body, 'method' => $method, 'headers' => $headers
        ) = $request_data;
        // insert info en db
        $rs = $this->dbConn->prepare("INSERT INTO requests(service_id, request_method, request_key_params, request_body, request_headers)
            VALUES(?, ?, ?, ?, ?)");
        $rs = $rs->execute(array($this->service_id, $method, $params, $body, $headers));

        if ($rs) {
            return $this->dbConn->lastInsertID();
        }
    }

    /**
     * #### Guarda la respuesta en la db ####
     * @param int $request_id ID de la peticion en db, devuelta por la funcion ***saveDbRequest( )***.
     * @param array $response_data Datos de la peticion. La estructura del array es inportante:  
     * array( 'data' => $data, 'headers' => $headers, 'status' => $status, 'status_detail' => $status_detail );  
     * No todos los datos son obligatorios, se debe usar al menos la posicion **data**.
     * 
     * En detalle representan:
     * - **data**: Un string con toda la data que se recibe en la respuesta.
     * - **headers**: Un string con el header que se recibe en la respuesta.
     * - **status**: Un integer con el codigo de estado de la respuesta. Puede se un http status code o custom.
     * - **status_detail**: Un string con el detalle del status de la respuesta o mensaje de error.
     */
    function saveDbResponse(int $request_id, array $response_data)
    {
        @list(
            'body' => $body, 'headers' => $headers, 'status' => $status, 'status_detail' => $status_detail
        ) = $response_data;

        // insert info en db
        $rs = $this->dbConn->prepare("INSERT INTO responses(request_id, response_body, response_headers, response_status, response_status_detail)
            VALUES(?, ?, ?, IFNULL(?, DEFAULT(response_status)), ?)");
        $rs = $rs->execute(array($request_id, $body, $headers, $status, $status_detail));

        if ($rs) {
            return $this->dbConn->lastInsertID();
        }
    }

    /** Se explica sola */
    function saveDbTransaction(array $request_data, array $response_data)
    {
        $req_id = $this->saveDbRequest($request_data);

        return $this->saveDbResponse($req_id, $response_data);
    }
}

?>