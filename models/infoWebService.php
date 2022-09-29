<?php

/**
 * @var $last_response string Se debe guardar la respuesta sin tratar para luego guardar en la db.
 * @method getPerson
 * Debe ser el metodo que realiza la peticion al servicio.
 * @method responseHandler
 * Funcion que transforma la respuesta en un array/objeto usable por php.
 * Esta misma funcion deberia usarse por el objeto del servicio y por la instancia de dbInfoService.
 */
abstract class infoWebService
{
    public $last_response = array(
        'body' => null, 'headers' => null,
        'status' => null, 'status_detail' => null,
    );

    abstract public function responseHandler(string $response);

    public function getLastResponse(string $key = 'body'): ?string
    {
        return $this->last_response[$key];
    }

    public function getFullLastResponse(): array
    {
        return $this->last_response;
    }

    public function setLastResponse(?string $data, string $key = 'body'): void
    {
        if (empty($data)) $data = null;

        $this->last_response[$key] = $data;
    }

    public function resetLastResponse(): void
    {
        $this->last_response = array(
            'body' => null, 'headers' => null,
            'status' => null, 'status_detail' => null,
        );
    }

    public function curlHeaderHandler($ch, $header): int
    {
        if ( !$this->last_response['headers'] ) {
            if (strpos($header, 'HTTP') === 0) {
                $status = explode(' ', $header);
                $this->last_response['status'] = $status[1];
                $this->last_response['status_detail'] = (
                    !empty($status[2]) ? $status[2]
                    : ($status[1]==200 ? 'OK' : null)
                );
            }
        }
        $this->last_response['headers'] .= $header;

        return strlen($header);
    }
}

?>