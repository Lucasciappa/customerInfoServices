<?php
require_once __DIR__ . '/../models/dbInfoService.php';

class nosisDbInfoService extends dbInfoService
{
    protected $service_id = 1;

    function getPerson(string $doc)
    {
        $key_params = json_encode(['doc' => $doc]);
        $res = $this->getDbResponse($key_params, 'GET', true);

        if (is_null($res)) {
            $req_id = $this->saveDbRequest(array(
                'params' => $key_params,
                'body' => nosisService::requestHandler($doc),
                'method' => 'GET'
            ));

            try {
                $res = $this->service->getPerson($doc);

                $raw = $this->service->getFullLastResponse();
                $this->saveDbResponse($req_id, $raw);
            } catch (Throwable $e) {
                $res = false;

                $this->saveDbResponse($req_id, [
                    'status' => 500,
                    'status_detail' => $e->getMessage()
                ]);
            }
        } else {
            $res = $this->service->responseHandler($res);
        }

        return $res;
    }
}