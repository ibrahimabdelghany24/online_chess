<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
include "./db.php";
$username = $_POST["username"];
$password = password_hash($_POST["password"], PASSWORD_DEFAULT);
if (isset($username) && isset($_POST["password"])) {
  $stmt = $con->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->execute([$username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($user && password_verify($_POST["password"], $user["password"])) {
    session_start();
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["username"] = $user["username"];
    header("Location: ../public/homepage.php");
    exit;
  } else {
    echo "Invalid username or password.";
    header("location: ../public/index.php");
    exit;
  }
} else {
  echo "Please fill in all fields.";
}
