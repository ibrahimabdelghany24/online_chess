<?php
session_start();
if (isset($_SESSION["user_id"])) {
  include "./layout/header.php"; ?>
  <div class="container">
    <h1>Welcome to the homepage! <?= htmlspecialchars($_SESSION["username"]) ?></h1>
    <a href="../backend/logout.php">logout</a>
    <div class="wrapper">
      <div class="game-options">
        <a href="#" data-time="1" data-inc="0" data-elo="bullet">1m + 0s <span>Bullet</span></a><br>
        <a href="#" data-time="2" data-inc="1" data-elo="bullet">2m + 1s <span>Bullet</span></a><br>
        <a href="#" data-time="3" data-inc="0" data-elo="blitz">3m + 0s <span>Blitz</span></a><br>
        <a href="#" data-time="3" data-inc="2" data-elo="blitz">3m + 2s <span>Blitz</span></a><br>
        <a href="#" data-time="5" data-inc="0" data-elo="blitz">5m + 0s <span>Blitz</span></a><br>
        <a href="#" data-time="5" data-inc="3" data-elo="blitz">5m + 3s <span>Blitz</span></a><br>
        <a href="#" data-time="10" data-inc="0" data-elo="rapid">10m + 0s <span>Rapid</span></a><br>
        <a href="#" data-time="10" data-inc="5" data-elo="rapid">10m + 5s <span>Rapid</span></a><br>
        <a href="#" data-time="15" data-inc="10" data-elo="rapid">15m + 10s <span>Rapid</span></a><br>
        <a href="#" data-time="30" data-inc="0" data-elo="classic">30m + 0s <span>Classic</span></a><br>
        <a href="#" data-time="30" data-inc="20" data-elo="classic">30m + 20s <span>Classic</span></a><br>
        <a href="#"><span>Custome</span></a><br>
        <a href="#" class="custome-a"><span>play bot</span></a><br>
        <a href="#" class="custome-a"><span>analyze</span></a><br>
        <div class="msg">Waiting for opponent...</div>
      </div>
    </div>
    <br>
  </div>
  <?php
  include "./layout/footer.php";
  ?>
  <script>
    const username = "<?= $_SESSION["username"] ?>";
  </script>
  <script src="./js/socket.js"></script>
<?php
} else {
  header("location: index.php");
  exit;
}
