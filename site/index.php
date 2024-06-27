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
        <div class="default-images" class="d-flex flex-column align-items-center justify-content-center">
          <img src="./total_genera.png" alt="Total Genera Image" class="img-fluid ml-2">
          <img src="./new_genera.png" alt="New Genera Image" class="img-fluid ml-2">
          <img src="./extinct_genera.png" alt="Extinct Genera Image" class="img-fluid ml-2">
        </div> <?php
      } ?>
    </div>
</body>
</html>