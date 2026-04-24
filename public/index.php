<?php
session_start();
if (isset($_SESSION["user_id"])) {
  header("location: homepage.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>

<body>
  <form action="../backend/login.php" method="POST">

    <label>
      Username: <input class="login" type="text" name="username" id="username" required>
    </label>

    <label>
      Password: <input type="password" name="password" id="password" required>
    </label>

    <a href="./signup.php">Have No Account?</a>

    <label>
      <input type="submit" value="Login">
    </label>
  </form>
  <script src="js/backend.js"></script>
</body>

</html>