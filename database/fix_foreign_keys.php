<?php
require_once __DIR__ . "/../config/db.php";

echo "<h2>TQSEET Database Integrity Sync & Cascade Upgrade</h2>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // 1. We want to update all foreign keys referencing 'users' to have 'ON DELETE CASCADE'
    // Let's query information_schema to find existing constraints referencing users
    $query = "
        SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = 'tqseet_db' 
        AND REFERENCED_TABLE_NAME = 'users'
    ";
    
    $result = $conn->query($query);
    $constraintsToRecreate = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $constraintsToRecreate[] = [
                'table' => $row['TABLE_NAME'],
                'constraint' => $row['CONSTRAINT_NAME'],
                'column' => $row['COLUMN_NAME']
            ];
        }
    }
    
    echo "<h3>Updating User-Dependent Constraints to ON DELETE CASCADE:</h3>";
    foreach ($constraintsToRecreate as $c) {
        $table = $c['table'];
        $oldConstraint = $c['constraint'];
        $column = $c['column'];
        
        echo "Processing table: <strong>$table</strong> (Constraint: $oldConstraint)... ";
        
        // Drop the old constraint
        $conn->query("ALTER TABLE `$table` DROP FOREIGN KEY `$oldConstraint`");
        
        // Add new constraint with ON DELETE CASCADE
        $newConstraintName = "fk_" . $table . "_user_cascade";
        $alterQuery = "
            ALTER TABLE `$table` 
            ADD CONSTRAINT `$newConstraintName` 
            FOREIGN KEY (`$column`) REFERENCES users(id) 
            ON DELETE CASCADE
        ";
        
        if ($conn->query($alterQuery)) {
            echo "<span style='color:green;'>SUCCESS (Constraint updated to $newConstraintName)</span><br>";
        } else {
            echo "<span style='color:red;'>FAILED to add constraint: " . $conn->error . "</span><br>";
        }
    }
    
    // 2. Also update installments referencing orders
    echo "<h3>Updating Installments Constraints to ON DELETE CASCADE:</h3>";
    // Find installments constraint referencing orders
    $queryInstallments = "
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = 'tqseet_db' 
        AND TABLE_NAME = 'installments' 
        AND REFERENCED_TABLE_NAME = 'orders'
    ";
    $resInst = $conn->query($queryInstallments);
    if ($resInst && $rowInst = $resInst->fetch_assoc()) {
        $oldConstraint = $rowInst['CONSTRAINT_NAME'];
        echo "Updating installments constraint: $oldConstraint... ";
        $conn->query("ALTER TABLE `installments` DROP FOREIGN KEY `$oldConstraint`");
        $alterInst = "
            ALTER TABLE `installments` 
            ADD CONSTRAINT `fk_installments_order_cascade` 
            FOREIGN KEY (order_id) REFERENCES orders(id) 
            ON DELETE CASCADE
        ";
        if ($conn->query($alterInst)) {
            echo "<span style='color:green;'>SUCCESS</span><br>";
        } else {
            echo "<span style='color:red;'>FAILED: " . $conn->error . "</span><br>";
        }
    } else {
        echo "Installments constraint already updated or not found.<br>";
    }
    
    // 3. Also update products referencing merchants
    echo "<h3>Updating Products Constraints to ON DELETE CASCADE:</h3>";
    $queryProducts = "
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = 'tqseet_db' 
        AND TABLE_NAME = 'products' 
        AND REFERENCED_TABLE_NAME = 'merchants'
    ";
    $resProd = $conn->query($queryProducts);
    if ($resProd && $rowProd = $resProd->fetch_assoc()) {
        $oldConstraint = $rowProd['CONSTRAINT_NAME'];
        echo "Updating products constraint: $oldConstraint... ";
        $conn->query("ALTER TABLE `products` DROP FOREIGN KEY `$oldConstraint`");
        $alterProd = "
            ALTER TABLE `products` 
            ADD CONSTRAINT `fk_products_merchant_cascade` 
            FOREIGN KEY (merchant_id) REFERENCES merchants(id) 
            ON DELETE CASCADE
        ";
        if ($conn->query($alterProd)) {
            echo "<span style='color:green;'>SUCCESS</span><br>";
        } else {
            echo "<span style='color:red;'>FAILED: " . $conn->error . "</span><br>";
        }
    } else {
        echo "Products constraint already updated or not found.<br>";
    }

    echo "<h3>Constraint updates completed successfully! You can now cleanly delete users from phpMyAdmin.</h3>";
    $conn->close();
} catch (Exception $e) {
    echo "<span style='color:red;'>Database Error: " . $e->getMessage() . "</span>";
}
