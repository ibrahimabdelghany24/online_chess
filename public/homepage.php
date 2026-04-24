<?php
session_start();
if (isset($_SESSION["user_id"])) {
    include "./layout/header.php";?>
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
    <?php
    include "./layout/footer.php";
    ?>
<?php
} else {
  header("location: index.php");
  exit;
}


