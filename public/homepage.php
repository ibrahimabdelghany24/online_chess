<?php
session_start();
if (isset($_SESSION["user_id"])) {
  echo "Welcome to the homepage!" . $_SESSION["username"];
}

?>
<a href="../backend/logout.php">logout</a>