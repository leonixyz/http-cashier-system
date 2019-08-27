<?php
    try {
        $file_db = new PDO('sqlite:app.sqlite3');
        $file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $file_db->exec("CREATE TABLE IF NOT EXISTS products (
                        id TEXT PRIMARY KEY, 
                        name TEXT, 
                        price REAL)");

        $products = $file_db->query('SELECT * FROM products');
        $columns = array();
 
        echo "<h1>Admin</h1><h2>Products</h2><table><thead><th>id</th><th>name</th><th>price</th></thead>";
        foreach ($products as $p) {
            echo "<tr><td>{$p['id']}</td><td>{$p['name']}</td><td>{$p['price']}</td></tr>";
            array_push($columns, '"' . $p['id'] . '"');
        }
        echo "</table>";

        $columns = implode($columns, ' INTEGER,');
        $file_db->exec("CREATE TABLE IF NOT EXISTS orders (
                        timestamp TEXT,
                        useragent TEXT,
                        total REAL,
                        $columns)");
        
        $orders = $file_db->query('SELECT * FROM orders');

        echo "<h2>Orders</h2><table><thead>";
        foreach($orders->fetch(PDO::FETCH_ASSOC) as $k => $field) {
            if (!is_int($k)) {
                echo "<th>{$k}</th>";
            }
        }
        echo "</thead>";

        $orders = $file_db->query('SELECT * FROM orders');
        $total = 0;

        foreach ($orders as $order) {
            echo "<tr>";
            foreach ($order as $k => $item) {
                if (!is_int($k)) {
                    echo "<td>{$item}</td>";
                    if($k == "total") {
                        $total += $item;
                    }
                }
            }

            echo "</tr>";
        }
        echo "</table>";
        echo "<strong>GRAND TOTAL $total</strong>";

        $file_db = null;
    } catch(PDOException $e) {
        // Print PDOException message
        echo $e->getMessage();
    }
?>
