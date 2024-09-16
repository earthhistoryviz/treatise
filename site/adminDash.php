<link rel="stylesheet" type="text/css" href="style.css"/>
<div class="admin-container">
   <div class="item <?php if (preg_match("/displayInfo.php/", $_SERVER["PHP_SELF"]) || preg_match("/index.php/", $_SERVER["PHP_SELF"])) {
       echo "active";
   } ?>" >
    <a class="menu-link" href="displayInfo.php">Manage Database</a>
   </div>
   <div class="item <?php if (preg_match("/fileBrowser.php\?type=excel/", $_SERVER["REQUEST_URI"]) || preg_match("/upload.php/", $_SERVER["PHP_SELF"])) {
       echo "active";
   } ?>" >
    <a class="menu-link" href="fileBrowser.php?type=excel">Parse Excel File</a>
   </div>
   <div class="item <?php if (preg_match("/fileBrowser.php\?type=images/", $_SERVER["REQUEST_URI"]) || preg_match("/uploadFigureImages.php/", $_SERVER["PHP_SELF"])) {
       echo "active";
   } ?>" >
    <a class="menu-link" href="fileBrowser.php?type=images">Upload Figure Images</a>
   </div>
   <!-- <div class="item <?php if (preg_match("/fileBrowser.php\?type=geojson/", $_SERVER["REQUEST_URI"]) || preg_match("/uploadGeojson.php/", $_SERVER["PHP_SELF"])) {
       echo "active";
   } ?>" >
    <a class="menu-link" href="fileBrowser.php?type=geojson">Upload Geojson Data</a>
   </div> -->
   <div class="item <?php if (preg_match("/manageUser.php/", $_SERVER["PHP_SELF"]) || preg_match("/manageUser.php/", $_SERVER["PHP_SELF"])) {
       echo "active";
   } ?>" >
    <a class="menu-link" href="manageUser.php">Manage User information</a>
   </div>
   <div class ="item <?php if (preg_match("/clearDatabase.php/", $_SERVER["PHP_SELF"])) {
       echo "active";
   }?>">
    <a class ="menu-link" href = "clearDatabase.php">Clear Database </a>
   </div>
   <?php
   $currentUrl = $_SERVER['REQUEST_URI'];
   $encodedUrl = urlencode($currentUrl);
   ?>
   <div class = "item">
    <p class="menu-link d-block mb-0">User: <?php session_start();
   echo $_SESSION["username"];?></p>
    <a class ="menu-link d-block" href="logout.php?redirect=<?php echo $encodedUrl; ?>">Logout </a>
   </div>
</div>