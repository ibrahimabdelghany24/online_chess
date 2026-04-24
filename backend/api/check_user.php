<?php
header('Content-Type: application/json');
include "../db.php";
$stmt = $con->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$_GET["username"]]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(["exists" => $rows ? true : false]);
