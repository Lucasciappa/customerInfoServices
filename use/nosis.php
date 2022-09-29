<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Address Parser Test</title>
    <link rel="stylesheet" href="css/div_flex.css">
    <link rel="stylesheet" href="css/table_layout.css">
    <link rel="stylesheet" href="css/table_responsive.css">
    <style>
        table {
            width: 100%
        }

        td[colspan] {
            text-align: center;
        }
    </style>
</head>

<body>

    <div>
        <div>
            <form>
                <div>
                    <div>
                        <label for="doc">DNI:</label>
                        <input id="doc" name="doc" type="number" min="999999" max="99999999" value="<?= ($_GET['doc'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="gender">Genero:</label>
                        <select name="gender" id="gender">
                            <option value="">Sin especificar</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                        <script>
                            document.getElementById('gender').value = '<?= ($_GET['gender'] ?? '') ?>';
                        </script>
                    </div>
                    <div>
                        <input type="submit" value="Ver Info">
                    </div>
                </div>
            </form>
        </div>
        <?= render() ?>
    </div>

</body>

</html>

<?php

function render(): string
{
    $html = '';

    if (!empty($_GET['doc'])) {
        $info = getInfo($_GET['doc'], ($_GET['gender'] ?? ''));

        $html .= '<div>';
        if ($info) {
            foreach ($info as $i => $row) {
                // Separador
                if ($i > 0) {
                    $html .= '<br><hr>';
                }

                //Info gral
                $html .= '<h3>Info cliente:</h3>';
                $html .= '<table class="labeled">';
                $head = $body = '';
                foreach ($row as $field => $value) {
                    if (is_string($value)) {
                        $head .= "<th>$field</th>";
                        $body .= "<td data-label=\"$field\">$value</td>";
                    }
                }
                $html .= "<thead><tr>$head</tr></thead><tbody><tr>$body</tr></tbody>";
                $html .= '</table>';

                //Direcciones
                $html .= '<h3>Direcciones:</h3>';
                foreach ($row['shipping_address'] as $i => $address) {
                    $html .= '<p>#' . ($i + 1) . '</p>';
                    $html .= '<table class="labeled">';
                    $raw_address = array_shift($address);
                    $head = $body = '';
                    $cols = count($address);
                    foreach ($address as $field => $value) {
                        $head .= "<th>$field</th>";
                        $body .= "<td data-label=\"$field\">$value</td>";
                    }
                    $html .= "<thead><tr>$head</tr></thead><tbody><tr>$body</tr></tbody>";
                    $html .= "<tfoot><tr><td colspan=$cols>$raw_address</td></tr></tfoot>";
                    $html .= '</table>';
                }
            }
        } else {
            $html .= '<h4>No se encontraron resultados</h4>';
        }
        $html .= '</div>';
    }

    return $html;
}

function getInfo(int $doc, string $gender): array
{
    require '../autoload.php';

    // Test Nosis
    $nosis = new nosisCustomerInfo( new customerInfo($doc) );
    $response = $nosis->getInfo();

    if ($gender) {
        $response = $nosis->filterInfoBy('gender', $gender);
    }

    return $response;
}

?>