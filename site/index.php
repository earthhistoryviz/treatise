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
    $isGrouped = false;  // Default to ungroup

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
        $rawResponse = json_decode($raw, true);
        $response = $rawResponse["data"];
        $isSynonym = $rawResponse["isSynonym"];

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
          if ($isSynonym) { ?>
              <h4 style='text-align: center;'>No genera with name "<?= htmlspecialchars($searchTerm) ?>"" was found. However, "<?= htmlspecialchars($searchTerm) ?>" was found in the Synonyms field.</h4>
              <br><br>
            <?php }

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
              } ?>
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
        <div>
          <button class="btn btn-primary mt-1" id="downloadBtn">Download Diversity Curves</button>
          <button class="btn btn-primary mt-1" id="goToSiteBtn"
            onclick="window.location.href='tscLinkAPI.php'">
            View Curves on TSC Online
          </button>
        </div>
        <script>
          async function fetchData() {
            const url = "https://<?= $_SERVER['HTTP_HOST'] ?>.treatise.geolex.org/searchAPI.php";
            try {
              const response = await fetch(url);
              const data = (await response.json())["data"];

              const processedData = [];
              let min_new = Number.MAX_SAFE_INTEGER;
              let max_new = Number.MIN_SAFE_INTEGER;
              let min_extinct = Number.MAX_SAFE_INTEGER;
              let max_extinct = Number.MIN_SAFE_INTEGER;
              let min_total = Number.MAX_SAFE_INTEGER;
              let max_total = Number.MIN_SAFE_INTEGER;
              let min_count = Number.MAX_SAFE_INTEGER;
              let max_count = Number.MIN_SAFE_INTEGER;
              let max_date = Number.MIN_SAFE_INTEGER;

              Object.entries(data).forEach(([genus, entry]) => {
                const beginning_date = parseFloat(entry['beginning_date']);
                const ending_date = parseFloat(entry['ending_date']);
                processedData.push({
                  beginning_date: beginning_date,
                  ending_date: ending_date
                });
                max_date = Math.max(max_date, beginning_date, ending_date);
              });

              const counts = {};
              const timeBlocks = Array.from({ length: Math.ceil(max_date / 5) + 1 }, (_, i) => i * 5);

              processedData.forEach(entry => {
                const beginning_date = entry.beginning_date;
                const ending_date = entry.ending_date;

                timeBlocks.forEach(time => {
                  if (!counts[time]) {
                    counts[time] = { Total: 0, New: 0, Extinct: 0 };
                  }
                  // Count Total Genera Active in the Time Block
                  if (beginning_date >= time && (ending_date <= time || ending_date === 0)) {
                    counts[time].Total++;
                  }
                  // Count New Genera in the Time Block
                  if (beginning_date >= time && beginning_date < time + 5) {
                    counts[time].New++;
                  }
                  // Count Extinct Genera in the Time Block
                  if (ending_date > 0 && ending_date >= time && ending_date < time + 5) {
                    counts[time].Extinct++;
                  }
                });
              });

              Object.values(counts).forEach(({ Total, New, Extinct }) => {
                min_total = Math.min(min_total, Total);
                max_total = Math.max(max_total, Total);

                min_new = Math.min(min_new, New);
                max_new = Math.max(max_new, New);

                min_extinct = Math.min(min_extinct, Extinct);
                max_extinct = Math.max(max_extinct, Extinct);
              });

              const sortedCounts = Object.keys(counts).sort((a, b) => b - a).reduce((acc, key) => {
                acc[key] = counts[key];
                return acc;
              }, {});

              let datapack = "format version:\t1.3\n";
              datapack += "date:\t" + new Date().toLocaleDateString("en-GB") + "\n\n";

              const siteUrlTreatise = "<?= ucfirst(strtolower($_SERVER['SERVER_NAME'])) ?>";

              datapack += `${siteUrlTreatise} Total-Genera\tpoint\t200\t255/255/255\n`;
              datapack += `rect\tline\tnofill\t${min_total}\t${max_total}\tsmoothed\n`;
              for (let i = 0; i < timeBlocks.length; i++) {
                  datapack += `\t${timeBlocks[i]}\t${sortedCounts[timeBlocks[i]].Total}\n`;
              }
              datapack += "\n";

              datapack += `${siteUrlTreatise} New-Genera\tpoint\t200\t255/255/255\n`;
              datapack += `rect\tline\tnofill\t${min_new}\t${max_new}\tsmoothed\n`;
              for (let i = 0; i < timeBlocks.length; i++) {
                datapack += `\t${timeBlocks[i]}\t${sortedCounts[timeBlocks[i]].New}\n`;
              }
              datapack += "\n";

              datapack += `${siteUrlTreatise} Extinct-Genera\tpoint\t200\t255/255/255\n`;
              datapack += `rect\tline\tnofill\t${min_extinct}\t${max_extinct}\tsmoothed\n`;
              for (let i = 0; i < timeBlocks.length; i++) {
                  datapack += `\t${timeBlocks[i]}\t${sortedCounts[timeBlocks[i]].Extinct}\n`;
              }

              console.log(datapack);
              
              const blob = new Blob([datapack], { type: 'text/plain' });
              const link = document.createElement('a');
              link.href = URL.createObjectURL(blob);
              link.download = 'datapack.txt';
              link.click();
            } catch (error) {
              console.error('Error fetching data:', error);
            }
          }
          document.getElementById('downloadBtn').addEventListener('click', fetchData);
        </script>
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