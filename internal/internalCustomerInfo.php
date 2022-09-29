<?php
require_once __DIR__ . '/../models/customerInfoAdder.php';
require_once __DIR__ . '/../dbCustomerInfo.php';

class internalCustomerInfo extends customerInfoAdder
{
    protected $customer;

    function __construct(customerInfo &$customer)
    {
        if (!$customer instanceof dbCustomerInfo) {
            $customer = new dbCustomerInfo($customer);
        }

        $this->customer = $customer;
    }

    function addInfo(int $index = null): bool
    {
        $rs = $info = false;

        if (is_null($index)) {
            if (count($this->info) == 1) {
                $info = $this->info[0];
            }
        } else {
            $info = $this->info[$index]??false;
        }

        if ($info) {
            $fields = $this->customer::$customer_filters;

            foreach ($fields as $k) {
                if(empty($this->customer->$k) && !empty($info[$k]))
                    $this->customer->$k = $info[$k];
            }
            $addresses = $info['shipping_address'];
            unset($info['id'], $info['shipping_address']);

            $rs = (bool) $this->customer->setInfo($info);

            foreach ($addresses as $address) {
                $rs = ( $this->customer->setAddress($address) || $rs );
            }
        }

        return $rs;
    }

    function getInfo(): array
    {
        if ($this->info) {
            return $this->info;
        }

        $customer_filters = $this->customer::$customer_filters;
        $info = array();

        //Info personal
        reset($customer_filters);
        while ( ($field = current($customer_filters)) && !$info ) {
            $info = $this->customer->getCustomerBy($field);
            next($customer_filters);
        }

        //Direcciones
        foreach ($info as &$row) {
            $addresses = $this->customer->getCustomerAddresses($row['id']);
            $row['shipping_address'] = $addresses;
        }

        $this->info = $info;
        return $info;
    }
}
