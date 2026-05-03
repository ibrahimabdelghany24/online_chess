<?php
header('Content-Type: application/json');
include "../db.php";
$stmt = $con->prepare(
  "SELECT id, username, bullet_rating, blitz_rating, rapid_rating, classic_rating 
  FROM users WHERE username = ? LIMIT 1"
);
$stmt->execute([$_GET["username"]]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode([
  "exists" => $rows ? true : false,
  "username" => $rows ? $rows[0]["username"] : null,
  "id" => $rows ? $rows[0]["id"] : null,
  "bullet_rating" => $rows ? $rows[0]["bullet_rating"] : null,
  "blitz_rating" => $rows ? $rows[0]["blitz_rating"] : null,
  "rapid_rating" => $rows ? $rows[0]["rapid_rating"] : null,
  "classic_rating" => $rows ? $rows[0]["classic_rating"] : null
]);
