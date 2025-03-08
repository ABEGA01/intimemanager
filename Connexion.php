<?php
try {
    $con = new PDO("mysql:host=localhost;dbname=gestion_stock_bd", "root", "");
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?> 