<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  session_start();
  header("Content-Type: application/json");
  include "./db.php";
  $errors = [];
  $username = $_POST["username"];
  $password_input = $_POST["password"];

  if (empty($username) || empty($password_input)) {
    $errors[] = "Please fill in all fields.";
  }

  if (empty($errors)) {
    $stmt = $con->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password_input, $user["password"])) {
      session_regenerate_id(true);
      $_SESSION["user_id"] = $user["id"];
      $_SESSION["username"] = $user["username"];
      echo json_encode(["success" => true, "redirect" => "../public/homepage.php"]);
      exit;
    } else {
      echo json_encode(["success" => false, "errors" => ["Invalid username or password."]]);
      exit;
    }
  } else {
    echo json_encode(["success" => false, "errors" => $errors]);
    exit;
  }
}
