<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  session_start();
  header("Content-Type: application/json");
  include "./db.php";
  $levelMap = [
    "1" => 250,
    "2" => 800,
    "3" => 1400,
    "4" => 2000
  ];
  $username = $_POST["username"];
  $password_input = $_POST["password"];
  $password2_input = $_POST["password2"];
  $level = $levelMap[$_POST["level"]] ?? 250;
  $errors = [];

  // Validation
  if (empty($username)) {
    $errors[] = "Username is required!";
  }

  if (empty($password_input)) {
    $errors[] = "Password is required!";
  }

  if ($password_input !== $password2_input) {
    $errors[] = "Passwords do not match!";
  }

  if (strlen($password_input) < 8) {
    $errors[] = "Password must be at least 6 characters long!";
  }

  if (empty($errors)) {
    // Check if username already exists
    $stmt = $con->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
    $errors[] = "Username already exists!";
    }
  }
  // end validation

  // Errors if any
  if (!empty($errors)) {
    echo json_encode(["success" => false, "errors" => $errors]);
    exit;
  }

  $password = password_hash($password_input, PASSWORD_DEFAULT);
  $stmt = $con->prepare(
    "INSERT INTO users (username, password, date, rapid_rating, blitz_rating, bullet_rating)
    VALUES (?, ?, NOW(), ?, ?, ?)"
  );
  $stmt->execute([$username, $password, $level, $level, $level]);
  $_SESSION["user_id"] = $con->lastInsertId();
  $_SESSION["username"] = $username;
  echo json_encode([
    "success" => true,
    "redirect" => "../public/index.php"
  ]);
} else {
  header("location: ../public/signup.php");
  exit;
}
