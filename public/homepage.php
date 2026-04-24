<?php
session_start();
if (isset($_SESSION["user_id"])) {?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
  </head>
  <body>
    <h1>Welcome to the homepage!</h1>
    <div>
      <a href="">play 5 min</a><br>
      <a href="">play 10 min</a><br>
      <a href="">play 2 min</a><br>
      <a href="">play bot</a><br>
      <a href="">analyze</a><br>
      <a href="../backend/logout.php">logout</a>
    </div>
    <br>

    
  </body>
  </html>
<?php
} else {
  header("location: index.php");
  exit;
}


