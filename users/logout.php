<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the correct home page URL
// Make sure this matches your actual project folder name
header("Location: /MealMate-online-food-ordering-system/index.php"); 
exit();
?>