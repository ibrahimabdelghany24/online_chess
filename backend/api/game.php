<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("location: ../../public/index.php");
  exit;
}
include __DIR__ . '/../db.php';
$game_id = $_GET["game_id"];
$stmt = $con->prepare(
  "SELECT m.*,
    w.username AS white_username,
    CASE m.type
      WHEN 'blitz'   THEN w.blitz_rating
      WHEN 'rapid'   THEN w.rapid_rating
      WHEN 'classic' THEN w.classic_rating
      WHEN 'bullet'  THEN w.bullet_rating
    END AS white_elo,
    b.username AS black_username,
    CASE m.type
      WHEN 'blitz'   THEN b.blitz_rating
      WHEN 'rapid'   THEN b.rapid_rating
      WHEN 'classic' THEN b.classic_rating
      WHEN 'bullet'  THEN b.bullet_rating
    END AS black_elo
  FROM matches m
  JOIN users w ON m.white = w.id
  JOIN users b ON m.black = b.id
  WHERE m.id = ?
"
);
$stmt->execute([$game_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$you = ($row["white"] == $_SESSION["user_id"]) ? "white" : "black";
$opp = ($row["white"] == $_SESSION["user_id"]) ? "black" : "white";
?>

<!DOCTYPE html>
<html>

<head>
  <link rel="stylesheet" href="../../public/css/board.css">
</head>

<body>
  <!-- Opponent -->
  <div class="info">
    <h3><?= $row[$opp . "_username"] ?></h3>
    <p>Time: <?= $row["time"] ?></p>
    <p>elo: <?= $row[$opp . "_elo"] ?></p>
    <p>cplor: <?= $opp ?></p>
  </div>

  <div class="board-wrap">
    <div>
      <div style="display:flex; align-items:flex-start;">
        <div class="rank-labels" id="rank-labels"></div>
        <div>
          <div class="board" id="board"></div>
          <div class="file-labels" id="file-labels"></div>
        </div>
      </div>
      <div class="status" id="status">White to move</div>
      <div class="controls">
        <button onclick="flipBoard()">⇅ Flip</button>
      </div>
    </div>
  </div>
  <!-- Player -->
  <div class="info">
    <h3>Name: <?= $_SESSION["username"] ?></h3>
    <p>Time: <?= $row["time"] ?></p>
    <p>elo: <?= $row[$you . "_elo"] ?></p>
    <p>cplor: <?= $you ?></p>
  </div>
  <script>
    const ROOM_ID = <?= $row["id"] ?>;
    const PLAYER_ID = <?= $_SESSION["user_id"] ?>;
    const PLAYER_COLOR = "<?= $you ?>";
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.10.3/chess.min.js" referrerpolicy="no-referrer"></script>
  <script src="../../public/js/board.js"></script>
  <script src="../../public/js/socket.js"></script>
</body>

</html>