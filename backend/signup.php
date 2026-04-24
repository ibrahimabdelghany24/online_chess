<?php
include "./db.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST["username"];
  $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

  $stmt = $con->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->execute([$username]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if ($rows) {
    echo "Username already exists!";
  } else {
    $stmt = $con->prepare("INSERT INTO users (username, password, date) VALUES (?, ?, NOW())");
    $stmt->execute([$username, $password]);
    $stmt2 = $con->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt2->execute([$username]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);
    session_start();
    $_SESSION["user_id"] = $row["id"];
    $_SESSION["username"] = $username;
    header("location: ../public/index.php");
    exit;
  }
}
