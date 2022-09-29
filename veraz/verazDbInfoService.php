<?php
require_once __DIR__ . '/../models/dbInfoService.php';

class verazDbInfoService extends dbInfoService
{
    protected $service_id = 3;

    function getPerson(string $doc, string $gender)
    {
        $key_params = json_encode(['doc' => $doc, 'gender' => $gender]);
        $res = $this->getDbResponse($key_params, 'GET', true);

        if (is_null($res)) {
            $req = array('params' => $key_params, 'method' => 'GET');

            try {
                $res = $this->service->getPerson($doc, $gender);

                $req['headers'] = $this->service->request_headers;
                $req['body'] = $this->service->request_body;
                $raw = $this->service->getFullLastResponse();
                $this->saveDbTransaction($req, $raw);
            } catch (Throwable $e) {
                $res = false;

                $this->saveDbTransaction($req, [
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
