<?php
require_once __DIR__ . '/../models/addressParser.php';

class verazAddressParser extends addressParser
{
    protected $fields = array(
        // para explode recursive parser
        // separator => db_columns
        ' - ' => array(
            '#street' => array(
                ' ' => array(
                    'street',
                    'street_number'
                )
            ),
            '#apartment' => array(
                ' ' => array(
                    'apt_floor',
                    'apt_number'
                )
            ),
            'city',
            'postcode',
            'zone'
        )
    );

    function parseAddress(string $address, array $fields = []): array
    {
        return $this->explodeAddress($address, $fields);
    }

    /*
    // Ejemplo usando el regex parser
    protected $fields = array(
        // db_columns => pattern
        'street' => '(.+?)',
        'street_number' => ' (\d+)',
        'apt_floor' => '(?: - ? (\w+)?)',
        'apt_number' => '(?: (\w+))?',
        'city' => ' - ([^-]+)',
        'postcode' => ' - (\d+)',
        'zone' => ' - ([^-]+)'
    );
    */
}