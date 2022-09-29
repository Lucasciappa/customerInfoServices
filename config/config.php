<?php
if (!defined('USER_DB')) define('USER_DB', "root");
if (!defined('PASS_DB')) define('PASS_DB', "CbIQfc3G");
if (!defined('HOST_DB')) define('HOST_DB', "172.16.3.101");

global $webServiceConn;
try {
    $webServiceConn = new PDO(
        'mysql:host=' . HOST_DB . ';dbname=webservices;charset=utf8',
        USER_DB, PASS_DB, array(PDO::ATTR_PERSISTENT => true)
    );
    $webServiceConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    trigger_error('ERROR: ' . $e->getMessage(), E_USER_WARNING);
}
?>