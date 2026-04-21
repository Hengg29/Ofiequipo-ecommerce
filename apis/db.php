<?php
$host = "localhost";
$user = "root"; 
$pass = "Csnu88334";    
$dbname = "ofiequipo2";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>