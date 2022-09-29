<?php
# Jasu: Creo una funcion que de una estructura de datos definida pueda generar el regex necesario para parsear una direccion
$pattern = '/^(.+?) (?:Nro:)?(\d+)(?: Piso (\w+))?(?: - Dto (\w+))? - \((\d+)\)(?: - ([^-]+))? - ([^-]+)$/';

# Types: str - char - int - * (solo reservado para street)
$fields = array(
    'street' => array(
        'type' => '*',
        'delimiter' => '',
        'enclosure' => '',
        'prefix' => '',
        'require' => true
    ),
    'street_number' => array(
        'type' => 'int',
        'delimiter' => ' ',
        'prefix' => 'Nro:',
        'require' => true
    ),
    'apt_floor' => array(
        'type' => 'char',
        'delimiter' => ' Piso '
    ),
    'apt_number' => array(
        'type' => 'char',
        'delimiter' => ' - Dto '
    ),
    'postcode' => array(
        'type' => 'int',
        'delimiter' => ' - ',
        'enclosure' => '()',
        'require' => true
    ),
    'city' => array(
        'type' => 'str',
        'delimiter' => ' - '
    ),
    'zone' => array(
        'type' => 'str',
        'delimiter' => ' - ',
        'require' => true
    ),
);

function makePattern(array $fields, bool $partial=false): string
{
    $pattern = '';

    foreach ($fields as &$rules) {
        if (is_array($rules)) {
            $regex = '';

            if (!empty($rules['type'])) {
                switch ($rules['type']) {
                    case 'str': $regex = '([^' . (trim($rules['delimiter'] ?? '-')) . ']+)'; break;
                    case 'char': $regex = '(\w+)'; break;
                    case 'int': $regex = '(\d+)'; break;
                    case '*': $regex = '(.+?)'; break;
                }
            }

            if (!empty($rules['enclosure'])) {
                $enclosure = $rules['enclosure'];
                $split = strlen($enclosure)/2;
                $regex = (
                    floor($split) != $split ? quotemeta($enclosure) . $regex . quotemeta($enclosure)
                    : quotemeta(substr($enclosure, 0, $split)) . $regex . quotemeta(substr($enclosure, $split))
                );
            }

            if (!empty($rules['prefix'])) {
                $regex = '(?:' . $rules['prefix'] . ')?' . $regex;
            }

            if (!empty($rules['delimiter'])) {
                $regex = $rules['delimiter'] . $regex;
            }

            if (empty($rules['require'])) {
                $regex = '(?:' . $regex . ')?';
            }

            $rules = $regex;
        }

        $pattern .= $rules;
    }

    if (!$partial) $pattern = "/^$pattern$/";
    return $pattern;
}

var_dump(makePattern($fields), $pattern);

?>