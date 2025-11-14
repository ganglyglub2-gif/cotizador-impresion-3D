<?php
$db_name = 'pm012020db';
try {
    $pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=".$db_name, "usrpm012020", 'Pr0y3ct0s2020#');
} catch (PDOException $e) {
    echo "Error de conexión! ";
    print_r($e->getMessage());
    exit();
}
unset($db_name);
?>
