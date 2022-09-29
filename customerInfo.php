<?php

class customerInfo
{
    public $doc;
    // Nombre de la columna donde guardar todos los datos
    public $raw_address = 'raw_address';
    public $info = array();
    public $addresses = array();

    function __construct(string $doc=null)
    {
        $this->doc = $doc;
    }

    function getInfo(): array
    {
        return $this->info;
    }

    function addInfo(array $info): array
    {
        $new = array();

        foreach ($info as $field => $value) {
            if (!isset($this->info[$field]) && !empty($value)) {
                $this->info[$field] = $value;
                $new[] = $field;
            }
        }

        return $new;
    }

    function getAddress(bool $skip_reindex=false): array
    {
        return ($skip_reindex ? $this->addresses : array_values($this->addresses));
    }

    function addAddress(array $address, int $service_id=0): array
    {
        $new = array();
        $hash = $this->getHash($address);

        if (!isset($this->addresses[$hash]) || strlen($address[$this->raw_address]) > strlen($this->addresses[$hash][$this->raw_address])) {
            $this->addresses[$hash] = $address;
            $new[$hash] = $service_id;
        }

        return $new;
    }

    function getHash(array $address): string
    {
        // Datos que hace que una direccion se unica
        $uniques = array(
            'street',
            'street_number',
            'apt_floor',
            'apt_number',
            'postcode',
        );
        $values = array_filter(
            $address,
            function ($k) use ($uniques) {
                return in_array($k, $uniques);
            },
            ARRAY_FILTER_USE_KEY
        );
        array_walk($values, array($this, 'sanitize'));

        return md5(implode(':', $values));
    }

    function sanitize(string &$val): void
    {
        $val = strtolower($val);
    }
}

?>