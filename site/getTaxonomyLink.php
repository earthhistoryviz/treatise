<?php
include_once("SqlConnection.php");

$level = $_GET['level'];      // e.g. 'Phylum'
$name = $_GET['name'];        // e.g. 'Charophyta'

if (empty($level) || empty($name)) {
    header("Content-Type: application/json");
    echo json_encode(["link" => null]);
    exit;
}

$stmt = $conn->prepare("
    SELECT pdf_url, page_number 
    FROM taxonomy_pdf_links 
    WHERE taxonomy_level = ? AND taxonomy_name = ?
");

$stmt->bind_param("ss", $level, $name);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $link = $row['pdf_url'] . '#page=' . $row['page_number'];
    echo json_encode([
        "link" => $link,
        "url" => $row['pdf_url'],
        "page" => $row['page_number']
    ]);
} else {
    echo json_encode(["link" => null]);
}

$stmt->close();
$conn->close();
header("Content-Type: application/json");
?>