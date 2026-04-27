<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>

<body>
  <form method="POST" id="signup-form" autocomplete="off">

    <label>
      Username: <input type="text" name="username" id="username" required>
    </label>

    <label>
      Password: <input type="password" name="password" id="password" required>
    </label>

    <label>
      Password Again: <input type="password" name="password2" id="password2" required>
    </label>

    <label>
      Your Level:
      <select name="level">
        <option value="1">Beginner</option>
        <option value="2">Intermediate</option>
        <option value="3">Advanced</option>
        <option value="4">Master</option>
      </select>
    </label>


    <a href="./index.php">Have An Account?</a>

    <label>
      <input type="submit" value="signup">
    </label>

  </form>
  <div id="errors"></div>
  <script src="./js/backend.js"></script>
</body>

</html>