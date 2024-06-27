<?php
 if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
  $file = $_FILES["image"];
  $type = $_POST["type"];
  $genera = $_POST["genera"];
  $redirect = $_POST["redirect"];
  switch ($file["error"]) {
    case UPLOAD_ERR_OK:
      break;
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
      echo "File is too large.";
      break;
    case UPLOAD_ERR_PARTIAL:
      echo "File was only partially uploaded.";
      break;
    case UPLOAD_ERR_NO_FILE:
      echo "No file was uploaded.";
      break;
    case UPLOAD_ERR_NO_TMP_DIR:
      echo "Missing a temporary folder.";
      break;
    case UPLOAD_ERR_CANT_WRITE:
      echo "Failed to write file to disk.";
      break;
    case UPLOAD_ERR_EXTENSION:
      echo "File upload stopped by extension.";
      break;
    default:
      echo "An unknown error occurred.";
  }
  if ($file["error"] !== UPLOAD_ERR_OK) {
    exit;
  }
  // Check if the file is an image
  if (getimagesize($file["tmp_name"]) === false) {
    echo "The file is not an image.";
    exit;
  }
  $baseUploadDirectory = "/app/uploads/";
  $uploadDirectory = $baseUploadDirectory . $genera . "/" . $type . "/";
  if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true); 
    $chownCommand = "chown www-data:www-data " . escapeshellarg($uploadDirectory);
    shell_exec($chownCommand);
  }
  $fileName = $file["name"];
  $destination = $uploadDirectory . $fileName;
  if (move_uploaded_file($file["tmp_name"], $destination)) {
    if ($redirect == "true") {
      header('location:displayInfo.php?genera=' . $_POST["genera"]);
    } else {
      echo "$fileName uploaded to $destination";
    }
  } else {
    echo "An error occurred during the file upload.";
  }
}
?>