<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("location: ../public/index.php");
  exit;
}
include __DIR__ . '/db.php';
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
  <link rel="stylesheet" href="../public/css/board.css">
</head>

<body>

  <div class="board-wrap">

    <div>
      <div class="board-out">
        <div class="rank-labels" id="rank-labels"></div>
        <div>
          <!-- Opponent -->
          <div class="<?= $opp . " info" ?> opp">
            <h3><?= $row[$opp . "_username"] ?> (<?= $row[$opp . "_elo"] ?>)</h3>
            <span class="<?= $opp . " timer" ?>"><?= $row["time"] . ":00" ?></span>
          </div>
          <div class="board" id="board"></div>
          <div class="file-labels" id="file-labels"></div>
          <!-- Player -->
          <div class="<?= $you . " info" ?> you">
            <h3><?= $_SESSION["username"] ?> (<?= $row[$you . "_elo"] ?>)</h3>
            <span class="<?= $you . " timer" ?>"><?= $row["time"] . ":00" ?></span>
          </div>
        </div>
      </div>
      <div class="status" id="status">White to move</div>
    </div>

  </div>
  <script>
    const ROOM_ID = <?= $row["id"] ?>;
    const PLAYER_ID = <?= $_SESSION["user_id"] ?>;
    const PLAYER_COLOR = "<?= $you ?>";
    let TIME = <?= $row["time"] ?>;
    let INC = <?= $row["inc"] ?>;
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.10.3/chess.min.js" referrerpolicy="no-referrer"></script>
  <script src="../public/js/board.js"></script>
  <script src="../public/js/socket.js"></script>
</body>

</html>