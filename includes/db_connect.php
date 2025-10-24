<?php

$host = "localhost:3307";         
$user = "root";            
$pass = "";                  
$db   = "online_food_ordering_system";  

// Create connection
$conn = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
