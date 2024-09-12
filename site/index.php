<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fossil Search</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

          // Group fossils by Class and then by Order
          $groupedFossils = [];
          foreach ($response as $item) {
            $class = $item['Class'] ?? 'Unknown Class'; // Use 'Unknown Class' if 'Class' is not set
            $order = $item['Order'] ?? 'Unknown Order'; // Use 'Unknown Order' if 'Order' is not set

            if (!isset($groupedFossils[$class])) {
              $groupedFossils[$class] = [];
            }
            if (!isset($groupedFossils[$class][$order])) {
              $groupedFossils[$class][$order] = [];
            }
            $groupedFossils[$class][$order][] = $item;
          }

          $colors = [];
          foreach($stages as $stage) {
            $colors[$stage["stage"]] = $stage["color"];
          } 

          // Display fossils grouped by Class and Order using Bootstrap Accordion
          echo '<div class="expand-collapse-buttons">';
          echo '<button class="btn btn-primary mx-2" id="expand-all">Expand All</button>';
          echo '<button class="btn btn-secondary mx-2" id="collapse-all">Collapse All</button>';
          echo '</div>';

          echo '<div class="accordion" id="classAccordion">';
          foreach ($groupedFossils as $class => $orders) { ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="heading-<?= htmlspecialchars($class) ?>">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= htmlspecialchars($class) ?>" aria-expanded="true" aria-controls="collapse-<?= htmlspecialchars($class) ?>">
                  <?= htmlspecialchars($class) ?>
                </button>
              </h2>
              <div id="collapse-<?= htmlspecialchars($class) ?>" class="accordion-collapse collapse show" aria-labelledby="heading-<?= htmlspecialchars($class) ?>">
                <div class="accordion-body">
                  <div class="accordion" id="orderAccordion-<?= htmlspecialchars($class) ?>">
                    <?php foreach ($orders as $order => $fossils) { ?>
                      <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-<?= htmlspecialchars($order) ?>">
                          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= htmlspecialchars($order) ?>" aria-expanded="false" aria-controls="collapse-<?= htmlspecialchars($order) ?>">
                            <?= htmlspecialchars($order) ?>
                          </button>
                        </h2>
                        <div id="collapse-<?= htmlspecialchars($order) ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= htmlspecialchars($order) ?>">
                          <div class="accordion-body">
                            <div class="cards w-100">
                              <?php foreach ($fossils as $item) { 
                                $backgroundColor = implode(",", explode("/", $colors[$item['beginning_stage']]));
                                $generaName = htmlspecialchars($item['Genus']); ?>
                                <div class="card">
                                  <div style="background-color: rgba(<?= $backgroundColor ?>, 1.0);" class="card-body">
                                    <a href="displayInfo.php?genera=<?php echo urlencode($generaName); ?>" style="color: black;"><?= $generaName ?></a>
                                  </div> 
                                </div> 
                              <?php } ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          <?php } 
          echo '</div>';
          ?>
        <?php } else { ?>
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
    <script>
      document.getElementById('expand-all').addEventListener('click', function() {
        let accordions = document.querySelectorAll('.accordion-collapse');
        accordions.forEach(accordion => {
          if (!accordion.classList.contains('show')) {
            new bootstrap.Collapse(accordion, {
              show: true
            });
          }
        });
      });

      document.getElementById('collapse-all').addEventListener('click', function() {
        let accordions = document.querySelectorAll('.accordion-collapse.show');
        accordions.forEach(accordion => {
          new bootstrap.Collapse(accordion, {
            hide: true
          });
        });
      });
    </script>
</body>
</html>