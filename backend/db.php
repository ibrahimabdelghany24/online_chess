<?php
$db_host = "localhost";
$db_name = "chess_online";
$db_user = "root";
$db_pass = "1234";

try {
  $con = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
  $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Connection failed: " . $e->getMessage());
}
