<?php
/**
 * @param array $fields En esta variable se define la estructura que se va a usar para el parseo.  
 * Ver __nosisAddressParser.php__ para un ejemplo usando el parser con expresiones regulares.  
 * Ver __verazAddressParser.php__ para un ejemplo usando el parser con explodes recursivos.  
 */
abstract class addressParser
{
    protected $fields;

    function parseAddress(string $address, array $fields = []): array
    {
        $parsed = array();

        if (!empty($this->fields)){
            if (empty($fields)) {
                $fields = array_keys($this->fields);
            }
            
            $pattern = $this->getPattern($fields);
            if (preg_match($pattern, $address, $dom_info)) {
                array_shift($dom_info);
                foreach ($fields as $i => $k) {
                    $parsed[$k] = ($dom_info[$i] ?? '');
                }
            }
        }

        return $parsed;
    }

    function getPattern(array $fields): string
    {
        $patterns = array_intersect_key(
            $this->fields,
            array_flip($fields)
        );

        return '/^' . implode($patterns) . '$/';
    }

    function explodeAddress(string $address, array $fields = []): array
    {
        $parsed = array();

        if (empty($fields)) {
            $fields = $this->fields;
        }

        if (!empty($fields)) {
            $parsed = $this->explodeAssocRecursive($fields, $address);
        }

        return $parsed;
    }

    function explodeAssocRecursive(array $sequence, string $string): array
    {
        $parsed = array();

        foreach ($sequence as $sep => $fields) {
            $exploded = explode($sep, $string);

            // Normalizo el tamaño de los arrays
            $diff = count($exploded) - count($fields);
            if ($diff > 0) {
                $exploded = array_merge(
                    array(implode($sep, array_slice($exploded, 0, ++$diff))),
                    array_slice($exploded, $diff)
                );
            }

            $i = 0;
            foreach ($fields as $pos => $field) {
                $string = ($exploded[$i] ?? '');

                if ($field && is_string($field)) {
                    $parsed[$field] = $string;
                } elseif (is_array($field) && !empty($string)) {
                    $parsed += $this->explodeAssocRecursive([$pos => $field], $string);
                }

                $i++;
            }
        }

        return $parsed;
    }
}
?>