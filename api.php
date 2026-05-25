<?php
header("Content-Type: application/json");

// DB Connection
$host = "localhost";
$dbname = "shop";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query
    $stmt = $pdo->prepare("SELECT * FROM store_template_history ORDER BY id DESC");
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "message" => "Data fetched successfully",
        "data" => $data
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => $e->getMessage()
    ]);
}
?>