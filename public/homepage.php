<?php
session_start();
if (isset($_SESSION["user_id"])) {
  include "./layout/header.php"; ?>
  <div class="container">
    <h1>Welcome to the homepage! <?= htmlspecialchars($_SESSION["username"]) ?></h1>
    <a href="../backend/logout.php">logout</a>
    <div class="wrapper">
      <div class="game-options">
        <a href="#" data-time="1" data-inc="0">1m + 0s <span>Bullet</span></a><br>
        <a href="#" data-time="2" data-inc="1">2m + 1s <span>Bullet</span></a><br>
        <a href="#" data-time="3" data-inc="0">3m + 0s <span>Blitz</span></a><br>
        <a href="#" data-time="3" data-inc="2">3m + 2s <span>Blitz</span></a><br>
        <a href="#" data-time="5" data-inc="0">5m + 0s <span>Blitz</span></a><br>
        <a href="#" data-time="5" data-inc="3">5m + 3s <span>Blitz</span></a><br>
        <a href="#" data-time="10" data-inc="0">10m + 0s <span>Rapid</span></a><br>
        <a href="#" data-time="10" data-inc="5">10m + 5s <span>Rapid</span></a><br>
        <a href="#" data-time="15" data-inc="10">15m + 10s <span>Rapid</span></a><br>
        <a href="#" data-time="30" data-inc="0">30m + 0s <span>Classic</span></a><br>
        <a href="#" data-time="30" data-inc="20">30m + 20s <span>Classic</span></a><br>
        <a href="#"><span>Custome</span></a><br>
        <a href="#" class="custome-a"><span>play bot</span></a><br>
        <a href="#" class="custome-a"><span>analyze</span></a><br>
        <div class="msg">Waiting for opponent...</div>
      </div>
    </div>
    <br>
  </div>
  <script src="./js/socket.js"></script>
  <?php
  include "./layout/footer.php";
  ?>
<?php
} else {
  header("location: index.php");
  exit;
}
