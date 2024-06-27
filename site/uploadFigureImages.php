<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Parse Image File</title>
  <link rel="stylesheet" type="text/css" href="style.css"/>
</head>
<body>
  <?php include_once("adminDash.php"); ?>
  <div style="margin: 10px;"> <?php
  
    function organizeCaptions() {
      include_once("SqlConnection.php"); 
      $sql = "SELECT GENUS, Figure_Caption FROM fossil";
      $result = $conn->query($sql);
      $captions = [];
      while ($row = $result->fetch_assoc()) {
        $caption = $row["Figure_Caption"];
        $genera = $row["GENUS"];
        if (preg_match("/FIG\.\s*(\d+)/i", $caption , $matches)) {
          $formattedCaption = "FIG. " . $matches[1];
          $captions[$formattedCaption][] = $genera;
        } else {
          echo "<br>Figure Caption <b>$caption</b> associated with <b>$genera</b> does not follow recognized format. Format is FIG. XX with anything else after being fine.<br>";
        }
      }
      return $captions;
    }

    function extractAndOrganizeFile($zip, $captions, $uploadDirectory) {
      for ($i = 0; $i < $zip->numFiles; $i++) {
        $fileName = $zip->getNameIndex($i);
        $filePath = $uploadDirectory . $fileName;
        $fileinfo = pathinfo($fileName);
        if (isset($fileinfo["extension"]) && in_array(strtolower($fileinfo["extension"]), ["jpg", "jpeg", "png", "gif"])) {
          if (preg_match("/FIG\.\s*(\d+)/i", $fileName , $matches)) {
            $formattedFileName = "FIG. " . $matches[1];
            if (array_key_exists($formattedFileName, $captions)) {
              if ($zip->extractTo($uploadDirectory, $fileName)) {
                $genus = $captions[$formattedFileName];
                foreach ($genus as $genera) {
                  $targetDirectory = $uploadDirectory . $genera . "/Figure_Caption/";
                  if (!is_dir($targetDirectory)) {
                    mkdir($targetDirectory, 0755, true); 
                    $chownCommand = "chown www-data:www-data " . escapeshellarg($targetDirectory);
                    shell_exec($chownCommand);
                  }
                  $targetFilePath = $targetDirectory . $fileName;
                  if (copy($filePath, $targetFilePath)) {
                    echo "<br>$fileName has been matched to $genera and uploaded to $targetFilePath";
                  } else {
                    echo "<br>An error occurred during the file upload.<br>";
                    var_dump(error_get_last());
                  }
                }
                unlink($filePath);
              } else {
                echo "<br>Failed to extract $fileName for upload";
              }
            } else {
              echo "<br>Found no matches in database for $formattedFileName from $fileName";
            }
          } else {
            echo "<br>Filename <b>$fileName</b> does not follow recognized format. Format is FIG. XX with anything else after being fine.";
          }
        } else {
          // Found a non-image file
          echo "<br>Error: ZIP file contains a non-image file. Skipping this file";
        }
      }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["upfile"])) {
      $file = $_FILES["upfile"];
      if ($file["error"] === UPLOAD_ERR_OK) {
        $file_name = str_replace(' ', '_', $file["name"]);
        $file_tmp_name = $file["tmp_name"];
        $file_type = $file["type"];
        $upload_directory = "/app/uploads/";
        $destination = $upload_directory . $file_name;
        if (move_uploaded_file($file_tmp_name, $destination)) {
          echo "<br>File uploaded successfully. File name: " . $file_name;
          echo "<br>";
          if ($file_type === "application/zip" || $file_type === "application/x-zip-compressed") {
            $zip = new ZipArchive;
            if ($zip->open($destination) === TRUE) {
              $captions = organizeCaptions();
              extractAndOrganizeFile($zip, $captions, $upload_directory);
              $zip->close();
            } else {
              echo "<br>Cannot open ZIP file.";
            }
          } else {
            echo "<br>Uploaded file is not a recognized ZIP file. File type is $file_type";
          }
          if (!unlink($destination)) { 
            echo "<br>Images file could not be deleted from server."; 
          }
        } else {
          // Error while moving the file
          echo "<br>Error uploading file. Please try again.";
        } 
      } else {
      // Handle upload errors
        switch ($file["error"]) {
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
      }
    } ?>
  </div>
</body>
</html>