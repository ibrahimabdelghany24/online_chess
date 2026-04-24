<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
  <form action="../backend/signup.php" method="POST">

    <label>
      Username: <input type="text" name="username" id="username" required>
    </label>

    <label>
      Password: <input type="password" name="password" id="password" required>
    </label>

    <label>
      Password Again: <input type="password" name="password2" id="password2" required>
    </label>

    <a href="./index.php">Have An Account?</a>

    <label>
      <input type="submit" value="signup">
    </label>

  </form>
  <script src="./js/backend.js"></script>
</body>

</html>