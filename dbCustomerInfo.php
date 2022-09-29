<?php
global $Conn;
require_once __DIR__ . '/customerInfo.php';
require_once __DIR__ . '/../config/config.php';
/**
 * Objeto para uso exclusivo en la web.  
 * Se asume que el repo va a ubicarse en la raiz de la web en la carpeta ```customerInfoServices```,
 * por lo que se tendria acceso al path relativo ```../config/config.php``` para usar la ```glogal $Conn```.
 * @author LucasCiappa 17/06/2022
 */

class dbCustomerInfo extends customerInfo
{
    protected $customer;
    public $addressees;
    public $new_info = array();
    public $new_addresses = array();
    static $customer_filters = array('id', 'email', 'doc');
    static $customer_fields = array(
        // obj_prop => db_column
        'id' => 'customers_id',
        'firstname' => 'customers_firstname',
        'lastname' => 'customers_lastname',
        'fullname' => 'customers_name',
        'doc' => 'customers_dni',
        'gender' => 'customers_gender',
        'email' => 'customers_email_address',
        'phone' => 'customers_telephone'
    );
    static $address_fields = array();
    static $address_zones = array();
    static $address_cities = array();

    function __construct(customerInfo $customer)
    {
        foreach (self::$customer_filters as $prop) {
            unset($this->$prop);
        }

        $this->customer = $customer;
    }

    function getCustomerBy(string $filter): array
    {
        $rs = array();

        if (in_array($filter, self::$customer_filters) && !empty($this->customer->$filter)) {
            global $Conn;
            $value = $this->customer->$filter;
            $filter = self::$customer_fields[$filter];

            // Select fields maker
            $fields = self::$customer_fields;
            $fields = array_map(function($field, $alias) {
                return "$field AS $alias";
            }, $fields, array_keys($fields));
            $fields = implode(', ', $fields);

            $rs = $Conn->query("SELECT $fields FROM customers WHERE $filter = '$value' LIMIT 10;");
            $rs = $rs->fetchAll(PDO::FETCH_ASSOC);
        }

        return $rs;
    }

    function createCustomer()
    {
        if (!isset($this->customer->email, $this->customer->doc)) {
            trigger_error('No hay suficientes datos del cliente', E_USER_WARNING);
            return;
        }

        // Insert customer
        if (empty($this->customer->id)) {
            global $Conn;

            // Insert fields maker
            $customer_fields = self::$customer_fields;
            $required = array('customers_password'); // Campos que no permiten nullos
            foreach ($required as $k) {
                if (!in_array($k, $customer_fields))
                    $customer_fields[str_replace('customers_', '', $k)] = $k;
            }
            $customer_props = array_keys($customer_fields);

            $defaults = array_fill_keys( $customer_props, '' );
            $fields = implode(', ', $customer_fields);
            $values = ':' . implode(', :', $customer_props);

            $query = $Conn->prepare("INSERT INTO customers($fields) VALUES($values)");

            $insert = array();
            foreach ($customer_props as $k) {
                if (isset($this->customer->$k))
                    $insert[$k] = $this->customer->$k;
            }

            $info = $this->customer->getInfo();
            array_walk($info, array($this, 'sanitizeInfo'));

            $insert = array_merge($defaults, $info, $insert);

            try {
                $Conn->beginTransaction();
                $query->execute( $insert );
                $this->customer->id = $Conn->lastInsertId();
                $Conn->commit();
                $this->new_info = array();
            } catch (PDOException $e) {
                $Conn->rollback();
                trigger_error('Error: ' . $e->getMessage(), E_USER_WARNING);
            }
        }
    }

    function saveInfo()
    {
        if (!isset($this->customer->id)) {
            $this->createCustomer();
        } else {
            // Update customers
            $new = $this->new_info;

            if ($new) {
                global $Conn;

                // Update fields maker
                $fields = $values = array();
                $info = $this->customer->getInfo();
                foreach ($new as $k) {
                    $fields[] = self::$customer_fields[$k] . '=?';
                    $this->sanitizeInfo($info[$k], $k);
                    $values[] = $info[$k];
                }
                $fields = implode(', ', $fields);

                $query = $Conn->prepare("UPDATE customers SET $fields WHERE customers_id={$this->customer->id}");

                try {
                    $Conn->beginTransaction();
                    $query->execute( $values );
                    $Conn->commit();
                    $this->new_info = array();
                } catch (PDOException $e) {
                    $Conn->rollback();
                    trigger_error('Error: ' . $e->getMessage(), E_USER_WARNING);
                }
            }
        }
    }

    function sanitizeInfo(&$value, string $field)
    {
        switch (self::$customer_fields[$field]) {
            case 'customers_gender':
                $value = strtolower(substr($value, 0, 1));
                break;
        }
    }

    function getCustomerAddresses(int $customer_id)
    {
        global $Conn;

        $rs = $Conn->query("SELECT shipping_address_id AS id, street, street_number, apt_floor, apt_number, postcode, city_id,
                provincia AS city, zone_id, localidad AS zone, raw_address, address_comment, info_verification_rate
            FROM shipping_addresses JOIN countries USING(countries_id) JOIN info_verification_type USING(info_verification_type_id)
            LEFT JOIN provincias ON zone_id=provincias.id LEFT JOIN localidades ON city_id=localidades.id
            WHERE customers_id=$customer_id");
        $rs = $rs->fetchAll(PDO::FETCH_ASSOC);

        return $rs;
    }

    function saveAddress()
    {
        // Update shipping_addresses
        $new = $this->new_addresses;
        if (isset($this->customer->id) && $new) {
            global $Conn;

            // Insert fields maker
            $fields = self::getAddressFields();
            $values = implode(', ', $fields);
            $fields = array_keys($fields);
            $defaults = array_fill_keys($fields, null);
            $fields = implode(', ', $fields);

            $addresses = $this->customer->getAddress(true);
            $inserts = array_intersect_key($addresses, $new);

            $query = $Conn->prepare("INSERT INTO shipping_addresses($fields) VALUES($values)");

            try {
                $hash_map = array(); //Todavia no esta en uso
                $this->getAddressFKs($inserts);
                foreach ($inserts as $h => $insert) {
                    // Agrego campos necesarios
                    $insert = array_merge(
                        $defaults,
                        $insert,
                        array(
                            'customers_id' => $this->customer->id,
                            'info_verification_type_id' => $new[$h]
                        )
                    );

                    $Conn->beginTransaction();
                    $query->execute( $insert );
                    $hash_map[$h] = $Conn->lastInsertId();
                    $Conn->commit();
                    unset($this->new_addresses[$h]);
                }
            } catch (PDOException $e) {
                $Conn->rollback();
                trigger_error('Error: ' . $e->getMessage(), E_USER_WARNING);
            }
        }
    }

    /** 
     * Reemplaza zone y city por sus respectivos IDs segun la db de la web.
     * Solo funciona con el decorador ```dbCustomerInfo()```.
     * @param array &$addresses Lista de las direciones, como se devuelve con ```customerInfo->getAddress()```.
     */
    function getAddressFKs(array &$addresses)
    {
        global $Conn;

        // Provincias a partir de "zone"
        $search = array_unique(array_column($addresses, 'zone'));
        $search = array_diff($search, array_keys(self::$address_zones));
        if ($search) {
            $search = implode(" UNION ", array_map(function($z) {
                return "SELECT '$z' AS `zone`";
            }, $search));

            $rs = $Conn->query("SELECT `zone`, id FROM provincias JOIN ($search) AS zones 
                                ON provincia LIKE `zone` COLLATE utf8_general_ci
                                OR provincias_tp LIKE `zone` COLLATE utf8_general_ci");
            $zones = $rs->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);
            self::$address_zones += $zones;
        }

        // Localidades a partir de "city"
        $cities = self::$address_cities;
        $search = array();
        foreach ($addresses as $address) {
            if (!empty($address['city']) && !isset($cities[$address['city']])) {
                foreach (($zones[$address['zone']]??[0]) as $zone_id) {
                    $search[] = array($address['city'], $zone_id);
                }
            }
        }

        if ($search) {
            $search = implode(" UNION ", array_map(function ($c) {
                return "SELECT '$c[0]' AS city, $c[1] AS zone_id";
            }, $search));

            $rs = $Conn->query("SELECT city, id AS city_id, zone_id
                FROM localidades JOIN (
                    SELECT city, MAX(zone_id) AS zone_id
                    FROM ( $search ) AS cities
                    WHERE EXISTS (
                        SELECT NULL FROM localidades
                        WHERE zone_id IN(0, id_provincia)
                            AND localidad LIKE CONCAT(city, '%')
                            COLLATE utf8_general_ci
                    )
                    GROUP BY city
                ) AS cities ON localidad LIKE CONCAT(city, '%')
                    COLLATE utf8_general_ci
                WHERE zone_id IN(0, id_provincia)");
            $cities = $rs->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
            self::$address_cities += $cities;
        }

        // Cargo IDs
        foreach ($addresses as $h => &$address) {
            $zones = (self::$address_zones[$address['zone']] ?? [0]);

            $city = (self::$address_cities[$address['city']] ?? false);
            if ($city && in_array($city['zone_id'], $zones)) {
                $address += array(
                    'city_id' => $city['city_id'],
                    'zone_id' => $city['zone_id']
                );
            } else {
                $address += array(
                    'city_id' => null,
                    'zone_id' => ( count($zones) == 1 ? $zones[0] : null)
                );
            }

            unset($address['zone'], $address['city']);
        }
    }

    function getCustomerAddressees(int $customer_id, int $addressee_id = null): array
    {
        global $Conn;

        $rs = $Conn->query("SELECT addressee_id AS id, doc_type_name AS doc_type,
                doc_number, name, lastname, phone, checked
            FROM addressees
            JOIN doc_types USING(doc_type_id)
            WHERE customers_id=$customer_id");
        $rs = $rs->fetchAll(PDO::FETCH_ASSOC);

        if (!$rs && is_null($addressee_id)) {
            $rs = $this->saveCustomerAsAddressee();
        }

        return $rs;
    }

    function saveCustomerAsAddressee(): array
    {
        $addressee = array();

        if ( isset($this->customer->id) ) {
            global $Conn;
            $info = $this->customer->getInfo();
            $addressee = array(
                $info['doc'], // doc_number
                $info['firstname'], // name
                $info['lastname'], // lastname
                ($info['phone']??null), // phone
            );

            $query = $Conn->prepare("INSERT INTO addressees(customers_id, doc_number, name, lastname, phone) VALUES(?, ?, ?, ?, ?)");

            try {
                $Conn->beginTransaction();
                $query->execute(array_merge(array($this->customer->id), $addressee));
                $addressee = array_merge(array('id' => $Conn->lastInsertId(), 'doc_type' => 'DNI'), $addressee);
                $Conn->commit();
            } catch (PDOException $e) {
                $Conn->rollback();
                trigger_error('Error: ' . $e->getMessage(), E_USER_WARNING);
            }
        }

        return $addressee;
    }

    function saveAddressees()
    {
        if (isset($this->customer->id)) {
            $this->getCustomerAddressees($this->customer->id);
        }
    }

    function save()
    {
        $this->saveInfo();
        $this->saveAddress();
        $this->saveAddressees();
    }

    static function getAddressFields(): array
    {
        global $Conn;

        if (!self::$address_fields) {
            $rs = $Conn->query("SHOW COLUMNS FROM shipping_addresses;");
            $rs = $rs->fetchAll();

            $fields = array();
            foreach ($rs as $row){
                // Agarro los campos que no son autoincrementales
                if ($row['Extra'] <> 'auto_increment') {
                    $fields[$row['Field']] = (
                        is_null($row['Default'])
                        ? ':' . $row['Field']
                        : "IFNULL(:{$row['Field']}, DEFAULT({$row['Field']}))"
                    );
                }
            }
            self::$address_fields = $fields;
        }

        return self::$address_fields;
    }

    function __get(string $prop)
    {
        return $this->customer->$prop;
    }

    function __set(string $prop, $value)
    {
        $this->customer->$prop = $value;
    }

    function __isset(string $prop)
    {
        return isset($this->customer->$prop);
    }

    function getInfo(): array
    {
        return $this->customer->getInfo();
    }

    function setInfo(array $info): array
    {
        return $this->customer->addInfo($info);
    }

    function addInfo(array $info): array
    {
        $new = $this->customer->addInfo($info);
        $this->new_info += $new;

        return $new;
    }

    function getAddress(bool $skip_reindex=false): array
    {
        return $this->customer->getAddress($skip_reindex);
    }

    function setAddress(array $address, int $service_id=0): array
    {
        return $this->customer->addAddress($address, $service_id);
    }

    function addAddress(array $address, int $service_id=0): array
    {
        $new = $this->customer->addAddress($address, $service_id);
        $this->new_addresses += $new;

        return $new;
    }
}
