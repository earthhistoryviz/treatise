<?php
if ($_GET["type"] == "excel") {
    $type = "excel";
    $action = "uploadExcelFile.php";
    $extension = ".xlsx";
} elseif ($_GET["type"] == "geojson") {
    $type = "geojson";
    $action = "uploadGeojson.php";
    $extension = ".json,application/json";
} else {
    $type = "images";
    $action = "uploadFigureImages.php";
    $extension = "application/zip";
} ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="style.css"/>
  <title>Parse <?php echo ucfirst($type) ?> File</title>
</head>
<body>
  <div class="main-container">
    <?php include_once("adminDash.php"); ?>
    <br><br>
    <h2>Upload <?php echo ucfirst($type) ?> File</h2>
    <?php if ($type === "geojson"): ?>
      <h5>Expects Geojson of a country including state/provinces. You most likely want a level-1 Geojson from <a href=https://gadm.org/download_country.html>here.</a></h5>
      <br>
    <?php endif; ?>
    <form action=<?= $action ?> method="post" enctype="multipart/form-data">
      Select <?php echo ucfirst($type) ?> File to upload:
      <input type="file" name="upfile" id="upfile" accept=<?= $extension ?> required>

      <?php if ($type === "geojson"): ?>
      <br><br>
      <label for="regionName">Name of the Region:</label>
      <input type="text" name="regionName" id="regionName" required>
      <?php endif; ?>

      <input type="submit" value="Upload <?php echo ucfirst($type) ?> File" name="submit">
    </form>
    <br><br>
  </div>
</body>
</html>