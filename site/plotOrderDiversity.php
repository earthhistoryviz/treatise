<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diversity Curve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.plot.ly/plotly-2.35.2.min.js" charset="utf-8"></script>
    <link rel="stylesheet" type="text/css" href="style.css"/>
</head>
<body>
  <div class="main-container">
    <?php
      session_start();
    $auth = $_SESSION["loggedIn"];
    include_once("navBar.php");
    include_once("SqlConnection.php");

    // Fetching orders from database
    $allOrders = [];
    $sqlOrders = "SELECT DISTINCT `Order` FROM fossil";
    $resultOrders = $conn->query($sqlOrders);
    while ($row = $resultOrders->fetch_assoc()) {
        $allOrders[] = $row["Order"];
    }
    $conn->close();

    // Get selected orders from the form submission
    $selectedOrders = isset($_GET['orders']) ? $_GET['orders'] : [];
    ?>

    <div class="container mt-5">
      <h1 style="text-align: center">Plot Diversity Curves</h1>
      <form method="GET">
        <div class="d-flex flex-column justify-content-center align-items-center">
            <div class="mb-3 mt-3 d-flex flex-row align-items-center justify-content-center gap-3">
                <label for="orderSelect" class="form-label">Select Fossil Orders:</label>
                <select class="form-select" id="orderSelect" name="orders[]" multiple size="6" style="width: 200px;">
                    <option value="All" <?php echo in_array("All", $selectedOrders) ? 'selected' : ''; ?>>All</option>
                    <?php foreach ($allOrders as $order): ?>
                        <option value="<?php echo htmlspecialchars($order); ?>" <?php echo in_array($order, $selectedOrders) ? 'selected' : ''; ?>><?php echo htmlspecialchars($order); ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Hold down the Ctrl (Windows) or Command (Mac) button to select multiple options.</small>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 150px;">Submit</button>
        </div>
    </form>
    </div>
    <?php
    if (!empty($selectedOrders)) {
        if (in_array('All', $selectedOrders)) {
            $selectedOrders = $allOrders;
        }

        // for each order in selected orders,fetch the fossils along with their
        $site_name = $_SERVER['HTTP_HOST'];
        // Initialize variables to store the time range and counts
        $min_date = PHP_INT_MAX;
        $max_date = PHP_INT_MIN;
        $counts = [];
        foreach ($selectedOrders as $currentOrder) {
            $apiUrl = "https://{$site_name}.treatise.geolex.org/searchAPI.php?orderfilter=" . urlencode($currentOrder);
            $response = file_get_contents($apiUrl);

            $data = json_decode($response, true); // Decode the JSON response into an associative array

            // Process the data to determine min and max dates
            $processedData = [];
            foreach ($data as $entry) {
                $order = $entry['Order'];
                $genus = $entry['Genus'];
                $beginning_date = (int)$entry['beginning_date'];
                $ending_date = (int)$entry['ending_date'];

                // Update min and max dates
                $min_date = min($min_date, $beginning_date);
                $max_date = max($max_date, $beginning_date);

                $processedData[] = [
                    'Order' => $order,
                    'Genus' => $genus,
                    'beginning_date' => $beginning_date,
                    'ending_date' => $ending_date
                ];
            }
            // Populate counts for each time block and order
            foreach ($processedData as $entry) {
                $order = $entry['Order'];
                $beginning_date = $entry['beginning_date'];
                $ending_date = $entry['ending_date'];

                // Create time blocks dynamically based on data range
                $timeBlocks = range(ceil($max_date / 5) * 5, 0, -5);

                foreach ($timeBlocks as $time) {
                    // Initialize counts array if not already set
                    if (!isset($counts[$time][$order])) {
                        $counts[$time][$order] = ['Total' => 0, 'New' => 0, 'Extinct' => 0];
                    }
                    // Count Total Genera Active in the Time Block
                    if ($beginning_date >= $time && ($ending_date <= $time || $ending_date == 0)) {
                        $counts[$time][$order]['Total']++;
                    }
                    // Count New Genera in the Time Block
                    if ($beginning_date >= $time && $beginning_date < $time + 5) {
                        $counts[$time][$order]['New']++;
                    }
                    // Count Extinct Genera in the Time Block
                    if ($ending_date > 0 && $ending_date >= $time && $ending_date < $time + 5) {
                        $counts[$time][$order]['Extinct']++;
                    }
                }
            }
        } // end for each

        // Format the counts into the desired JSON object
        $jsonOutput = ['TimeBlocks' => []];
        foreach ($counts as $time => $orders) {
            $blockData = ['TimeBlock' => $time, 'Orders' => []];
            foreach ($orders as $order => $values) {
                $blockData['Orders'][] = [
                    'Order' => $order,
                    'Total' => $values['Total'],
                    'New' => $values['New'],
                    'Extinct' => $values['Extinct']
                ];
            }
            $jsonOutput['TimeBlocks'][] = $blockData;
        }
        /*
        Json object format:
        Timeblocks is every 5 million years
        TimeBlocks : [Order1{Total, New, Extinct}, Order2{Total, New, Extinct}, Order3{Total, New, Extinct}]
        */
        // Output the JSON object
        header('Content-Type: application/json');
        echo json_encode($jsonOutput, JSON_PRETTY_PRINT);

    } // end if
    ?>

  </div> 
</body>
</html>
