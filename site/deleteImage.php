<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['genera'])) {
        http_response_code(400); // Bad Request
        echo "Missing 'genera' field.";
        exit;
    }
    $genera = ltrim($_POST['genera'], "?");
    $imageName = $_POST["imageName"];

    $imagePath = "/app/uploads/" . $genera . "/Figure_Caption/" . $imageName;
    echo $imagePath;
    if (file_exists($imagePath)) {
        if (unlink($imagePath)) {
            http_response_code(200);
            echo "Image deleted successfully.";
        } else {
            http_response_code(500);
            echo "An error occurred while deleting the image.";
        }
    } else {
        http_response_code(404);
        echo "Image file does not exist.";
    }
} else {
    http_response_code(405);
    echo "Method not allowed.";
}
