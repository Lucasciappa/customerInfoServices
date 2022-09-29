<style>
    body>div {
        display: flex;
    }

    div>div {
        width: calc(50% - 55px);
        float: left;
        border: 2.5px solid;
        border-radius: 10px;
        padding: 15px;
        margin: 10px;
        background-color: aliceblue;
        overflow: auto;
    }
</style>

<div>
    <?php
    require '../../autoload.php';
    define('INTDB', true); // Internal DB
    define('NOSIS', true);
    define('VERAZ', false);

    if (isset($_GET['doc'], $_GET['email'])) {
        #5279190
        $customer = new customerInfo();
        $doc = $_GET['doc'];
        $customer->doc = $doc;
        $customer->email = $_GET['email'];
        $gender = ($_GET['gender'] ?? 'M');
    } else {
        $doc = 38356068;
        $doc = 37667036;
        $gender = 'F';
        $customer = new customerInfo($doc);
    }

    $customer = new dbCustomerInfo( $customer );

    // Data Retrievers
    if (INTDB && !empty($doc)) {
        $customerConn = new internalCustomerInfo($customer);
        $info = $customerConn->getInfo();
        if ($info) {
            if (count($info) > 1) {
                $info = $customerConn->filterInfoBy('email', 'jesus.serrano@necxus.com.ar');
            }

            if (count($info) == 1) {
                $customerConn->addInfo();
            }
        }
        $info = $customer->getInfo();
        $address = $customer->getAddress();

        printInfo('BDD', $info, $address);
    } else {
        $info = $address = array();
    }

    if (!$address) {
        if (NOSIS && !empty($doc)) {
            $customerNosis = new nosisCustomerInfo($customer);
            $info = $customerNosis->getInfo();
            if ($info) {
                if (count($info) > 1) {
                    // $info = $customerNosis->filterInfoBy('gender', 'femenino');
                }

                if (count($info) == 1) {
                    $customerNosis->addInfo();
                }
            }
            $info = $customer->getInfo();
            $address = $customer->getAddress();

            // $customer->getAddressFKs($address);

            printInfo('+Nosis', $info, $address);

            //Guardar los datos
            // echo '</div><div><div><pre>';
            // var_dump($customer);
            // $customer->save();
            // var_dump($customer);
            // echo '</pre></div>';
        }

        if (VERAZ && !empty($doc) && !empty($gender)) {
            $customer->gender = $gender;
            $customerVeraz = new verazCustomerInfo($customer);
            echo '<pre>';
            $info = $customerVeraz->getInfo();
            echo '</pre>';
            if ($info) {
                if (count($info) > 1) {
                    // $info = $customerVeraz->filterInfoBy('gender', 'femenino');
                }

                if (count($info) == 1) {
                    // $customerVeraz->addInfo();
                }
            }
            // $info = $customer->getInfo();
            $address = $customer->getAddress();

            printInfo('+Veraz', $info, $address);

            //Guardar los datos
            // echo '</div><div><div><pre>';
            // var_dump($customer);
            // $customer->save();
            // var_dump($customer);
            // echo '</pre></div>';
        }
    }

    function printInfo($tittle, $info, $address=null)
    {
        echo '
        <div>
            <h2><u>Desde ' . $tittle . '</u>:</h2>
            <h3>Info Personal:</h3>
            <pre>';
        var_dump($info);

        if ($address) {
            echo '
            </pre>
            <h3>Direcciones:</h3>
            <pre>';
            var_dump($address);
        }

        echo '
            </pre>
        </div>';
    }
    ?>
</div>