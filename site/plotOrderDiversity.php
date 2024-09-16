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

        // Initialize variables to store the time range and counts
        $min_date = PHP_INT_MAX;
        $max_date = PHP_INT_MIN;
        $counts = [];
        foreach ($selectedOrders as $currentOrder) {
            $apiUrl = "https://{$_SERVER['HTTP_HOST']}.treatise.geolex.org/searchAPI.php?orderfilter=" . urlencode($currentOrder);
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
        }

        ksort($counts);

        // Always calculate total Genus data
        foreach ($counts as $time => $orders) {
            foreach ($orders as $order => $values) {
                if (!isset($counts[$time]['All-Selections'])) {
                    $counts[$time]['All-Selections'] = ['Total' => 0, 'New' => 0, 'Extinct' => 0];
                }
                $counts[$time]['All-Selections']['Total'] += $values['Total'];
                $counts[$time]['All-Selections']['New'] += $values['New'];
                $counts[$time]['All-Selections']['Extinct'] += $values['Extinct'];
            }
        }

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
        ?>
    
    <!-- Divs for Plotly Charts -->
    <div id="plot-total" class="mb-5 w-75"></div>
    <div id="plot-new" class="mb-5 w-75"></div>
    <div id="plot-extinct" class="mb-5 w-75"></div>

    <script>
        const data = <?php echo json_encode($jsonOutput, JSON_PRETTY_PRINT); ?>;
        const timeBins = data.TimeBlocks.map(block => block.TimeBlock);

        const totalTraces = [];
        const newTraces = [];
        const extinctTraces = [];

        data.TimeBlocks.forEach(block => {
            block.Orders.forEach(orderData => {
                const orderName = orderData.Order;
                // For Total Genera Plot
                let traceTotal = totalTraces.find(trace => trace.name === orderName);
                if (!traceTotal) {
                    traceTotal = {
                    x: [],
                    y: [],
                    mode: 'lines',
                    name: orderName,
                    // fill: 'tonexty',
                    };
                    totalTraces.push(traceTotal);
                }
                traceTotal.x.push(block.TimeBlock);
                traceTotal.y.push(orderData.Total);

                // For New Genera Plot
                let traceNew = newTraces.find(trace => trace.name === orderName);
                if (!traceNew) {
                    traceNew = {
                    x: [],
                    y: [],
                    mode: 'lines',
                    name: orderName,
                    // fill: 'tonexty',
                    };
                    newTraces.push(traceNew);
                }
                traceNew.x.push(block.TimeBlock);
                traceNew.y.push(orderData.New);

                // For Extinct Genera Plot
                let traceExtinct = extinctTraces.find(trace => trace.name === orderName);
                if (!traceExtinct) {
                    traceExtinct = {
                    x: [],
                    y: [],
                    mode: 'lines',
                    name: orderName,
                    // fill: 'tonexty',
                    };
                    extinctTraces.push(traceExtinct);
                }
                traceExtinct.x.push(block.TimeBlock);
                traceExtinct.y.push(orderData.Extinct);
            });
        });

        const stage_ranges = {
            'Cambrian': [[541, 485.37], [153/255, 181/255, 117/255]],
            'Ordovician': [[485.37, 443.83], [51/255, 169/255, 126/255]],
            'Silurian': [[443.83, 419.2], [166/255, 220/255, 181/255]],
            'Devonian': [[419.2, 358.94], [229/255, 183/255, 90/255]],
            'Carboniferous': [[358.94, 298.88], [140/255, 176/255, 108/255]],
            'Permian': [[298.88, 251.9], [227/255, 99/255, 80/255]],
            'Triassic': [[251.9, 201.36], [164/255, 70/255, 159/255]],
            'Jurassic': [[201.36, 145.73], [78/255, 179/255, 211/255]],
            'Cretaceous': [[145.73, 66.04], [140/255, 205/255, 96/255]],
            'Paleogene': [[66.04, 23.04], [253/255, 108/255, 98/255]],
            'Neogene': [[23.04, 2.58], [255/255, 255/255, 51/255]],
            'Quaternary': [[2.58, -50], [255/255, 237/255, 179/255]]
        };

        const shapes = Object.keys(stage_ranges).map(stage => {
            const [range, color] = stage_ranges[stage];
            return {
                type: 'rect',
                xref: 'x',
                yref: 'y',
                x0: range[0],
                x1: range[1],
                y0: 0,
                y1: -1,
                fillcolor: `rgba(${color[0] * 255}, ${color[1] * 255}, ${color[2] * 255}, 0.5)`,
                line: { width: 0 }
            };
        });

        const annotations = Object.keys(stage_ranges).map(stage => {
            const [range] = stage_ranges[stage];
            return {
                xref: 'x',
                yref: 'y',
                x: (range[0] + range[1]) / 2,
                y: -0.5,
                text: stage,
                showarrow: false,
                font: {
                    size: stage === "Neogene" ? 10 : 12,
                    color: 'black'
                },
                align: 'center'
            };
        });

        // Plotting Total Genera
        Plotly.newPlot('plot-total', totalTraces, {
            title: 'Total Genera Over Time',
            xaxis: { title: 'Time (Million Years Ago)', autorange: 'reversed' },
            yaxis: { title: 'Number of Genera'},
            height: 800,
            shapes: shapes,
            annotations: annotations
        });

        // Plotting New Genera
        Plotly.newPlot('plot-new', newTraces, {
            title: 'New Genera Over Time',
            xaxis: { title: 'Time (Million Years Ago)', autorange: 'reversed' },
            yaxis: { title: 'Number of New Genera' },
            height: 800,
            shapes: shapes,
            annotations: annotations
        });

        // Plotting Extinct Genera
        Plotly.newPlot('plot-extinct', extinctTraces, {
            title: 'Extinct Genera Over Time',
            xaxis: { title: 'Time (Million Years Ago)', autorange: 'reversed' },
            yaxis: { title: 'Number of Extinct Genera' },
            height: 800,
            shapes: shapes,
            annotations: annotations
        });
    </script>
    <?php
    } // End if for selected orders
    ?>

  </div> 
</body>
</html>
