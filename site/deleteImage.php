<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $genera = file_get_contents('php://input');
  $genera = ltrim($genera, "?");
  $imagePath = "/app/uploads/" . $genera . ".png";
  if (file_exists($imagePath)) {
    if (unlink($imagePath)) {
      echo "Image deleted successfully.";
    } else {
      echo "An error occurred while deleting the image.";
    }
  } else {
    echo "Image file does not exist.";
  }
}
?>
