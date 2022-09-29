<?php
require_once __DIR__ . '/../models/addressParser.php';

class nosisAddressParser extends addressParser
{
    protected $fields = array(
        // db_columns => pattern
        'street' => '(.+?)',
        'street_number' => '(?: (?:Nro:)?(\d+))',
        'apt_floor' => '(?: Piso (\w+))?',
        'apt_number' => '(?: - Dto (\w+))?',
        'postcode' => '(?: - \((\d+)\))',
        'city' => '(?: - ([^-]+))?',
        'zone' => '(?: - ([^-]+))'
    );
}