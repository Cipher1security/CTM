<?php
$db_host = '';
$db_username = '';
$db_password = '';
$db_name = '';
$dsn = "mysql:host=$db_host;dbname=$db_name";
$conn = new PDO($dsn, $db_username, $db_password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>