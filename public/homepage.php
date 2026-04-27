<?php
session_start();
if (isset($_SESSION["user_id"])) {
  include "./layout/header.php"; ?>
  <div class="container">
    <h1>Welcome to the homepage! <?= $_SESSION["username"] ?></h1>
    <a href="../backend/logout.php">logout</a>
    <div class="wraper">
      <div class="game-options">
        <a href="">1m + 0s <span>Bullet</span></a><br>
        <a href="">2m + 1s <span>Bullet</span></a><br>
        <a href="">3m + 0s <span>Blitz</span></a><br>
        <a href="">3m + 2s <span>Blitz</span></a><br>
        <a href="">5m + 0s <span>Blitz</span></a><br>
        <a href="">5m + 3s <span>Blitz</span></a><br>
        <a href="">10m + 0s <span>Rapid</span></a><br>
        <a href="">10m + 5s <span>Rapid</span></a><br>
        <a href="">15m + 10s <span>Rapid</span></a><br>
        <a href="">30m + 0s <span>Classic</span></a><br>
        <a href="">30m + 20s <span>Classic</span></a><br>
        <a href=""><span>Custome</span></a><br>
        <a href="" class="custome-a"><span>play bot</span></a><br>
        <a href="" class="custome-a"><span>analyze</span></a><br>
      </div>
    </div>
    <br>
  </div>
  <?php
  include "./layout/footer.php";
  ?>
<?php
} else {
  header("location: index.php");
  exit;
}
