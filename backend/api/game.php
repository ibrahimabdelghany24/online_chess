<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("location: ../../public/index.php");
  exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<style>
.board {
  display: grid;
  grid-template-columns: repeat(8, 50px);
}
.cell {
  width: 50px;
  height: 50px;
  border: 1px solid #333;
  display: flex;
  align-items: center;
  justify-content: center;
}
.info {
  margin: 10px;
}
</style>
</head>
<body>

<!-- Opponent -->
<div class="info">
  <h3><?= $_SESSION["username"] ?></h3>
  <p></p>
  <p></p>
</div>


<!-- Player -->
<div class="info">
  <h3><?= $_SESSION["username"] ?></h3>
  <p></p>
  <p></p>
</div>
<script>

</script>
</body>
</html>