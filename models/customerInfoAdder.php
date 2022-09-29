<?php

abstract class customerInfoAdder
{
    protected $customer;
    public $info = array();

    function __construct(customerInfo $customer)
    {
        $this->customer = $customer;
    }

    abstract function getInfo(): ?array;

    function addInfo(int $index = null): bool
    {
        $rs = $info = false;

        if (is_null($index)) {
            if (count($this->info) == 1) {
                $info = $this->info[0];
            }
        } else {
            $info = $this->info[$index] ?? false;
        }

        if ($info) {
            $addresses = array();

            if (isset($info['shipping_address'])) {
                $addresses = $info['shipping_address'];
                unset($info['shipping_address']);
            }

            $rs = (bool) $this->customer->addInfo($info);

            foreach ($addresses as $address) {
                $rs = ( $this->customer->addAddress($address, $this->service_id) || $rs );
            }
        }
        return $rs;
    }

    function filterInfoBy(string $field, $value)
    {
        $info = array_filter($this->info, function ($v) use ($field, $value) {
            return (
                isset($v[$field]) &&
                strtolower($v[$field]) == strtolower($value)
            );
        });

        if ($info) {
            $this->info = array_values($info);
        }

        return $this->info;
    }
}

?>