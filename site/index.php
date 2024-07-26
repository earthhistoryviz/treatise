<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fossil Search</title>
  <link rel="stylesheet" type="text/css" href="style.css"/>
</head>
<body>
  <div class="main-container">
    <?php
      session_start();
      $auth = $_SESSION["loggedIn"];
      include_once("navBar.php");
      include_once("generalSearchBar.php");

      if (isset($_GET["search"])) {
        $searchTerm = $_GET['search'];
        $url = "http://localhost/searchAPI.php"
        ."?searchquery=".urlencode($_GET["search"])
        ."&classfilter=".$_GET["classSearch"]
        ."&geographyfilter=".$_GET["geographySearch"]
        ."&stagefilter=".$_GET["stageSearch"]
        ."&agefilterstart=".$_GET["agefilterstart"]
        ."&agefilterend=".$_GET["agefilterend"];
        $raw = file_get_contents($url);
        $response = json_decode($raw, true);
        if ($response) {
          // Sorting logic based on button clicked
          if (isset($_POST['sortAlphabetically'])) {
            usort($response, function($a, $b) {
              return strcmp($a['Genus'], $b['Genus']);
            });
          } else {
            usort($response, function($a, $b) {
              return $a['beginning_date'] - $b['beginning_date'];
            });
          }
          $colors = [];
          foreach($stages as $stage) {
            $colors[$stage["stage"]] = $stage["color"];
          } ?>

          <div class="cards w-100"> <?php
            foreach ($response as $item) {
              $backgroundColor = implode(",", explode("/", $colors[$item['beginning_stage']]));
              $generaName = htmlspecialchars($item['Genus']); ?>
              <div class="card">
                <div style="background-color: rgba(<?= $backgroundColor ?>, 1.0);" class="card-body">
                  <a href="displayInfo.php?genera=<?php echo urlencode($generaName); ?>" style="color: black;"><?= $generaName ?></a>
                </div> 
              </div> <?php
            } ?>
          </div> <?php
        } else { ?>
          <br><br>
          <h4 style='text-align: center;'>No genera found.</h4> <?php
        }
      } else { ?>
        <br><br>
        <h3>Diversity Over Time Charts Created from Our Database:</h3>
        <br>
        <?php
        /**
       * This function appends the last modification time of a file to its URL as a query parameter.
       * This technique is known as cache busting, and it ensures that browsers always load the most recent version of the file.
       * 
       * How it works:
       * - The function checks the last modification time of the specified file.
       * - It appends this modification time as a query parameter to the file's URL.
       * - When the file is not modified, the URL might look like 'image.png?v=1627392000'.
       * - If the file is modified, the modification time changes, e.g., to 1627392100.
       * - The new URL will be 'image.png?v=1627392100'.
       * - The browser sees this as a different URL, which doesn't match the cached version, thus it fetches the new image.
       *
       * @param string $filename The name of the file (e.g., 'total_genera.png').
       * @return string The filename appended with its last modification time as a query parameter (e.g., 'total_genera.png?v=1627392000').
       */
        function versioned_image($filename) {
            $file_path = __DIR__ . '/' . $filename;
            if (file_exists($file_path)) {
                // apparent . is concactenation in PHP
                return $filename . '?v=' . filemtime($file_path); // Append the last modified time as a query parameter
            }
            return $filename; // If the file does not exist, return the original filename
        }
        ?>
        <div class="default-images d-flex flex-column align-items-center justify-content-center">
          <img src="<?= versioned_image('total_genera.png') ?>" alt="Total Genera Image" class="img-fluid ml-2">
          <img src="<?= versioned_image('new_genera.png') ?>" alt="New Genera Image" class="img-fluid ml-2">
          <img src="<?= versioned_image('extinct_genera.png') ?>" alt="Extinct Genera Image" class="img-fluid ml-2">
        </div> <?php
      } ?>
    </div>
</body>
</html>