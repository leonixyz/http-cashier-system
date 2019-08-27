<?php

$printer = '/dev/usb/lp0';

$products = [
    "birra" => [
        "name"  => "birra",
        "price" => 3.5
    ],
    "vino" => [
        "name"  => "vino",
        "price" => 1.5
    ],
    "acqua" => [
        "name"  => "acqua",
        "price" => 1
    ],
    "bibite" => [
        "name"  => "bibite",
        "price" => 2
    ],
    "sangria" => [
        "name"  => "sangria",
        "price" => 3
    ],
    "prosecco" => [
        "name"  => "prosecco",
        "price" => 2.5
    ],
    "cocktail-prosecco" => [
        "name"  => "cocktail prosecco",
        "price" => 4
    ],
    "cocktail-gin" => [
        "name"  => "cocktail gin",
        "price" => 5
    ]
];

$receiptHeader =
    "\x1b\x21\x10"                     . // select double height mode
    "    Music Aid for Emergency     " .
    "        & Los Quinchos          " .
    "             2019               " .
    "\x1b\x21\x00"                     . // select font B and disable double height mode
    "\n\n";

function isPrinterReady() {
    global $printer;
    return file_exists($printer) && is_writable($printer);
}

function printReceipt($text) {
    global $printer;
    if (isset($text)) {
        if (!isPrinterReady()) {
            die('Error! Printer was not found or is not writable.');
        }

        if (!$handle = fopen($printer, 'wb')) {
            die('Error! Cannot open printer device file.');
        }

        if (fwrite($handle, $text) === FALSE) {
            die('Error! Cannot write to printer device file.');
        }

        fclose($handle);
    }
}

// TODO: fix vulnerability to SQL injection on `timestamp` and `userAgent`
function storeOrder($order) {
    try {
        $file_db = new PDO('sqlite:app.sqlite3');
        $file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $columns = '"' . implode(array_keys($order), '", "') . '"';
        $values = implode(array_values($order), ', ');
        $timestamp = $_REQUEST['timestamp'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $insert = "INSERT INTO orders (timestamp, useragent, $columns)
                   VALUES ('$timestamp', '$userAgent', $values)";
        $file_db->exec($insert);
        $file_db = null;
    } catch(PDOException $e) {
        // Print PDOException message
        echo $e->getMessage();
    }
}

if(isset($_REQUEST['submit'])) {

    global $products, $receiptHeader;
    $total = 0;
    $receipt = '';
    setlocale(LC_MONETARY, 'it_IT');
    $order = array();

    foreach($_REQUEST as $key => $value) {
        if(in_array($key, array_keys($products)) && is_numeric($value)) {
            if($value > 0) {
                $itemPrice = $products[$key]['price'] * $value;
                $itemPriceFormatted = money_format('%.2n', $itemPrice);
                $total += $itemPrice;
                $receipt .= "$value x {$products[$key]['name']} ({$itemPriceFormatted} Euro)\n";
                $order[$key] = $value;
            }
        }
    }

    if($receipt) {
        $order['total'] = $total;
        $total = money_format('%.2n', $total);

        $receipt = $receiptHeader . $receipt .
            //"\x1b\x21\x00". // select font A
            //"\x1b\x21\x01". // select font B
            "\n" .
            "\n" .
            "\x1b\x21\x10" . // select double height mode
            "TOTALE $total EURO\n" .
            "\x1b\x21\x00" . // select font B and disable double height mode
            "\n" .
            "\n" .
            "\n" .
            "\n" ;

        printReceipt($receipt);
        storeOrder($order);
    }

}

if (!isPrinterReady()) {
    die('Error! Printer was not found or is not writable.');
}

?>

<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Cassa</title>
        <style>
            body, html {
                padding:0;
                margin:0;
            }
            input[type=number] {
                border:0;
                width:1em;
                font-size: 1.5em;
                margin:0 0.5em;
                -webkit-appearance: textfield;
                -moz-appearance: textfield;
                appearance: textfield;
            }
            button {
                font-size: 1.5em;
                width: 1.5em;
                height: 1.5em;
                border:0;
                border-radius:1em;
                color:white;
            }
            button[data-operation="increase"] {
                float:left;
                background:blue;
            }
            button[data-operation="decrease"] {
                float:right;
                background:orange;
            }
            .item {
                padding: 0.5em;
                font-size:1.5em;
            }
            input[type="submit"] { 
                font-size:2em;
                width:100%;
                border:0;
                background:green;
                color:white;
            }
            input[type="submit"][disabled] {
                background:grey;
                color:black;
            }
            input[type="reset"] { 
                font-size:2em;
                width:100%;
                border:0;
                background:red;
                color:white;
            }
            hr {
                border:0;
                background:none;
                height: 1em;
            }
        </style>
    </head>
    <body>
        <form method="POST" action="">
            <input type="reset" name="reset" id="reset" value="Cancella"> 
            <hr>
            <?php foreach($products as $key => $value): ?>
                <div class="item">
                    <button type="button" data-target="<?php echo $key; ?>" data-operation="increase">+</button>
                    <input type="number" min="0" value="0" name="<?php echo $key; ?>" id="<?php echo $key; ?>" data-price="<?php echo $value['price']; ?>">
                    <button type="button" data-target="<?php echo $key; ?>" data-operation="decrease">-</button>
                    <?php echo $value['name']; ?>
                </div>
            <?php endforeach; ?>
            <hr>
            <input type="hidden" name="timestamp" id="timestamp" value="">
            <input type="submit" name="submit" id="submit" value="0.00 Euro">
        </form>
        <script>
            function updateTotal() {
                var inputs = document.getElementsByTagName('input');
                var total = 0;
                for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i];
                    if (input.getAttribute('data-price')) {
                        total += parseFloat(input.getAttribute('data-price')) * parseInt(input.value); 
                    }
                }
                document.getElementById('submit').value = total.toFixed(2) + ' Euro';
                document.getElementById('submit').disabled = total == 0;
                document.getElementById('timestamp').value = (new Date()).toISOString();
            }

            var buttons = document.getElementsByTagName('button');

            for (var i = 0; i < buttons.length; i++) {
                var btn = buttons[i];
                btn.addEventListener('click', function() {
                    var target = this.getAttribute('data-target');
                    var operation = this.getAttribute('data-operation');
                    var input = document.getElementById(target);
                    input.value = operation == 'increase' ? parseInt(input.value)+1 : parseInt(input.value)-1;
                    input.value = input.value < 0 ? 0 : input.value;
                    updateTotal();
                });
            }

            updateTotal();
        </script>
    </body>
</html>
