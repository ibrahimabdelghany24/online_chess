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
  <form method="POST" id="login-form" autocomplete="off">

    <label>
      Username: <input class="login" type="text" name="username" id="username" >
    </label>

    <label>
      Password: <input type="password" name="password" id="password" >
    </label>

    <a href="./signup.php">Have No Account?</a>
    <label>
      <input type="submit" value="Login">
    </label>
  </form>
  <div id="errors"></div>
  <script src="js/backend.js"></script>
</body>

</html>