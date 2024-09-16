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

    // Always set the default state to false (ungrouped) unless toggled by user action
    $isGrouped = false;  // Default to ungrouped

    // Handle toggle change via POST request
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggleGrouping'])) {
        $isGrouped = $_POST['toggleGrouping'] === 'true';
        $_SESSION['isGrouped'] = $isGrouped; // Update session if needed for consistency in this session
    }

    if (isset($_GET["search"])) {
        $searchTerm = $_GET['search'];
        $url = "http://localhost/searchAPI.php"
        ."?searchquery=".urlencode($_GET["search"])
        ."&classfilter=".$_GET["classSearch"]
        ."&orderfilter=".$_GET["orderSearch"]
        ."&geographyfilter=".$_GET["geographySearch"]
        ."&stagefilter=".$_GET["stageSearch"]
        ."&agefilterstart=".$_GET["agefilterstart"]
        ."&agefilterend=".$_GET["agefilterend"];
        $raw = file_get_contents($url);
        $response = json_decode($raw, true);
        if ($response) {
            // Sorting logic based on button clicked
            if (isset($_POST['sortAlphabetically'])) {
                usort($response, function ($a, $b) {
                    return strcmp($a['Genus'], $b['Genus']);
                });
            } else {
                usort($response, function ($a, $b) {
                    return $a['beginning_date'] - $b['beginning_date'];
                });
            }

            $colors = [];
            foreach($stages as $stage) {
                $colors[$stage["stage"]] = $stage["color"];
            }
            ?>

          <!-- Grouping Toggle Buttons -->
          <div class="d-flex justify-content-center my-3">
            <form method="post" class="d-flex align-items-center" style="margin-right: 8px;">
              <input type="hidden" name="toggleGrouping" value="<?= $isGrouped ? 'false' : 'true' ?>">
              <button type="submit" class="btn btn-outline-primary">
                <?= $isGrouped ? 'Show Non-Grouped View' : 'Show Grouped View' ?>
              </button>
            </form>
            <?php if ($isGrouped): ?>
              <button class="btn btn-primary mx-2" id="expand-all">Expand All</button>
              <button class="btn btn-secondary mx-2" id="collapse-all">Collapse All</button>
            <?php endif; ?>
          </div>

          <?php
            // Grouping logic if toggle is on
            if ($isGrouped) {
                // Group fossils by Class and then by Order
                $groupedFossils = [];
                foreach ($response as $item) {
                    $class = $item['Class'] ?? 'Unknown Class';
                    $order = $item['Order'] ?? 'Unknown Order';

                    if (!isset($groupedFossils[$class])) {
                        $groupedFossils[$class] = [];
                    }
                    if (!isset($groupedFossils[$class][$order])) {
                        $groupedFossils[$class][$order] = [];
                    }
                    $groupedFossils[$class][$order][] = $item;
                }
                ?>

            <div class="accordion" id="classAccordion">
              <?php foreach ($groupedFossils as $class => $orders): ?>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="heading-<?= htmlspecialchars($class) ?>">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= htmlspecialchars($class) ?>" aria-expanded="true" aria-controls="collapse-<?= htmlspecialchars($class) ?>">
                      <?= htmlspecialchars($class) ?>
                    </button>
                  </h2>
                  <div id="collapse-<?= htmlspecialchars($class) ?>" class="accordion-collapse collapse show" aria-labelledby="heading-<?= htmlspecialchars($class) ?>">
                    <div class="accordion-body">
                      <div class="accordion" id="orderAccordion-<?= htmlspecialchars($class) ?>">
                        <?php foreach ($orders as $order => $fossils): ?>
                          <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?= htmlspecialchars($order) ?>">
                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= htmlspecialchars($order) ?>" aria-expanded="false" aria-controls="collapse-<?= htmlspecialchars($order) ?>">
                                <?= htmlspecialchars($order) ?>
                              </button>
                            </h2>
                            <div id="collapse-<?= htmlspecialchars($order) ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= htmlspecialchars($order) ?>">
                              <div class="accordion-body">
                                <div class="cards w-100">
                                  <?php foreach ($fossils as $item):
                                      $backgroundColor = implode(",", explode("/", $colors[$item['beginning_stage']]));
                                      $generaName = htmlspecialchars($item['Genus']); ?>
                                    <div class="card">
                                      <div style="background-color: rgba(<?= $backgroundColor ?>, 1.0);" class="card-body">
                                        <a href="displayInfo.php?genera=<?= urlencode($generaName); ?>" style="color: black;"><?= $generaName ?></a>
                                      </div> 
                                    </div> 
                                  <?php endforeach; ?>
                                </div>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          <?php } else { ?>
            <!-- default ungrouped default display -->
            <div class="cards w-100">
              <?php foreach ($response as $item):
                  $backgroundColor = implode(",", explode("/", $colors[$item['beginning_stage']]));
                  $generaName = htmlspecialchars($item['Genus']); ?>
                <div class="card">
                  <div style="background-color: rgba(<?= $backgroundColor ?>, 1.0);" class="card-body">
                    <a href="displayInfo.php?genera=<?= urlencode($generaName); ?>" style="color: black;"><?= $generaName ?></a>
                  </div> 
                </div>
              <?php endforeach; ?>
            </div>
          <?php }
          } else { ?>
          <br><br>
          <h4 style='text-align: center;'>No genera found.</h4> 
        <?php }
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
             * @param string $filename The name of the file (e.g., 'total_genera.png').
             * @return string The filename appended with its last modification time as a query parameter (e.g., 'total_genera.png?v=1627392000').
             */
            function versioned_image($filename)
            {
                $file_path = __DIR__ . '/' . $filename;
                if (file_exists($file_path)) {
                    return $filename . '?v=' . filemtime($file_path);
                }
                return $filename;
            }
              ?>
        <div class="default-images d-flex flex-column align-items-center justify-content-center">
          <img src="<?= versioned_image('total_genera.png') ?>" alt="Total Genera Image" class="img-fluid ml-2">
          <img src="<?= versioned_image('new_genera.png') ?>" alt="New Genera Image" class="img-fluid ml-2">
          <img src="<?= versioned_image('extinct_genera.png') ?>" alt="Extinct Genera Image" class="img-fluid ml-2">
        </div> 
      <?php } ?>
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