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
    <div class="controls">
      <div class="buttons">
        <button id="resign" onclick="resign()" disabled>Resign</button>
        <button id="offer-draw" onclick="offerDraw()" disabled>Offer Draw</button>
        <button id="new-game1" onclick="newGame()" disabled>New Game</button>
        <button id="cancel" onclick="cancel()">Cancel</button>
      </div>
    </div>
  </div>

  <div id="gameOverModal" class="modal">
    <div class="box">

      <div class="exit" onclick=""><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.-->
          <path d="M504.6 148.5C515.9 134.9 514.1 114.7 500.5 103.4C486.9 92.1 466.7 93.9 455.4 107.5L320 270L184.6 107.5C173.3 93.9 153.1 92.1 139.5 103.4C125.9 114.7 124.1 134.9 135.4 148.5L278.3 320L135.4 491.5C124.1 505.1 125.9 525.3 139.5 536.6C153.1 547.9 173.3 546.1 184.6 532.5L320 370L455.4 532.5C466.7 546.1 486.9 547.9 500.5 536.6C514.1 525.3 515.9 505.1 504.6 491.5L361.7 320L504.6 148.5z" />
        </svg></div>

      <div id="loserText" class="loser"></div>

      <div id="resultText" class="result"></div>

      <div class="message"></div>

      <div class="buttons">
        <button id="rematch" onclick="rematch()">Rematch</button>
        <button id="new-game" onclick="newGame()">New Game</button>
        <button id="accept">Accept</button>
        <button id="decline">Decline</button>
      </div>

    </div>
  </div>

  <script>
    const ROOM_ID = <?= $row["id"] ?>;
    const PLAYER_ID = <?= $_SESSION["user_id"] ?>;
    const PLAYER_COLOR = "<?= $you ?>";
    const PLAYER_USERNAME = "<?= $_SESSION["username"] ?>";
    const TYPE = "<?= $row["type"] ?>";
    let TIME = <?= $row["time"] ?>;
    let INC = <?= $row["inc"] ?>;
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.10.3/chess.min.js" referrerpolicy="no-referrer"></script>
  <script src="../public/js/board.js"></script>
  <script src="../public/js/socket.js"></script>
</body>

</html>