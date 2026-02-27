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

    // Define group types for selection
    // $groupingTypes = ['Subphylum', 'Class', 'Subclass', 'Order'];
    $allPossibleTypes = ['Subphylum', 'Class', 'Subclass', 'Order'];
    $groupingTypes = [];

    foreach ($allPossibleTypes as $type) {
        $sqlCheck = "SELECT COUNT(*) as cnt FROM fossil 
                    WHERE `$type` IS NOT NULL 
                    AND `$type` != '' 
                    AND `$type` != 'None'";
        $resultCheck = $conn->query($sqlCheck);
        $rowCheck = $resultCheck->fetch_assoc();
        if ($rowCheck['cnt'] > 0) {
            $groupingTypes[] = $type;
        }
    }

    // Get selected grouping type from form submission, default is 'Class'
    $selectedGroupingType = isset($_GET['groupingType']) ? $_GET['groupingType'] : 'Class';
    if (!in_array($selectedGroupingType, $groupingTypes)) {
        $selectedGroupingType = $groupingTypes[0];
    }

    // Fetch available groupings (either Class or Order) from database based on selected grouping type
    $allGroupings = [];
    $sqlGrouping = "SELECT DISTINCT `$selectedGroupingType` FROM fossil 
    WHERE `$selectedGroupingType` IS NOT NULL 
    AND `$selectedGroupingType` != '' 
    AND `$selectedGroupingType` != 'None'";
    $resultGrouping = $conn->query($sqlGrouping);
    while ($row = $resultGrouping->fetch_assoc()) {
        $val = $row[$selectedGroupingType];
        if (!empty($val) && $val !== 'None') {
            $allGroupings[] = $val;
        }
    }
    sort($allGroupings);
    $conn->close();

    // Get selected groupings from the form submission
    // If no selection, default to "All"
    $selectedGroupings = isset($_GET['group']) ? $_GET['group'] : ['All'];
    ?>

    <div class="container mt-5">
      <h1 style="text-align: center">Plot Diversity Curves</h1>
      <form method="GET">
        <div class="d-flex flex-column justify-content-center align-items-center">
            <!-- Dropdown to select the Grouping Type (Class or Order) -->
            <div class="mb-3 mt-3 d-flex flex-row align-items-center justify-content-center gap-3">
                <label for="groupingTypeSelect" class="form-label">Select Grouping Type:</label>
                <select class="form-select" id="groupingTypeSelect" name="groupingType" style="width: 200px;" onchange="this.form.submit()">
                    <?php foreach ($groupingTypes as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $selectedGroupingType == $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Dropdown to select specific Groupings (Class/Order/etc.) -->
            <div class="mb-3 d-flex flex-row align-items-center justify-content-center gap-3">
                <label for="groupSelect" class="form-label">Select Fossil Grouping:</label>
                <select class="form-select" id="groupSelect" name="group[]" multiple size="6" style="width: 200px;">
                    <option value="All" <?php echo in_array("All", $selectedGroupings) ? 'selected' : ''; ?>>All</option>
                    <?php foreach ($allGroupings as $grouping): ?>
                        <option value="<?php echo htmlspecialchars($grouping); ?>" <?php echo in_array($grouping, $selectedGroupings) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($grouping); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Hold down the Ctrl (Windows) or Command (Mac) button to select multiple options.</small>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 150px;">Submit</button>
        </div>
      </form>
    </div>

    <?php

    $fossilsOlderThan750Ma = [];
    if (!empty($selectedGroupings)) {
        // If "All" is selected, include all available groupings
        if (in_array('All', $selectedGroupings)) {
            $selectedGroupings = $allGroupings;
        }

        // Initialize variables to store the time range and counts
        $min_date = PHP_INT_MAX;
        $max_date = PHP_INT_MIN;
        $counts = [];

        foreach ($selectedGroupings as $currentGrouping) {
            // $filterType = ($selectedGroupingType == 'Class') ? 'classfilter' : 'orderfilter';
            $filterMap = [
                'Subphylum' => 'subphylumfilter',
                'Class' => 'classfilter',
                'Subclass' => 'subclassfilter',
                'Order' => 'orderfilter'
            ];
            $filterType = $filterMap[$selectedGroupingType];
            $apiUrl = "https://{$_SERVER['HTTP_HOST']}.treatise.geolex.org/searchAPI.php?$filterType=" . urlencode($currentGrouping);
            $response = file_get_contents($apiUrl);
            $data = json_decode($response, true)["data"];

            // // Dump all genus from data
            // echo "grouping: " . $currentGrouping . " | <br>";
            // foreach ($data as $entry) {
            //     echo $entry['Genus'] . "<br>";
            // }

            // Process the data to determine min and max dates
            $processedData = [];
            foreach ($data as $entry) {
                $grouping = $entry[$selectedGroupingType];
                $genus = $entry['Genus'];
                $beginning_date = floatval($entry['beginning_date']);
                $ending_date = floatval($entry['ending_date']);

                if ($beginning_date >= 750 || $ending_date >= 750) {
                    $fossilsOlderThan750Ma[] = $entry;
                    continue;
                }

                // Update min and max dates
                $min_date = min($min_date, $ending_date);
                $max_date = max($max_date, $beginning_date);
                $processedData[] = [
                    'Grouping' => $grouping,
                    'Genus' => $genus,
                    'beginning_date' => $beginning_date,
                    'ending_date' => $ending_date
                ];
            }

            // Populate counts for each time block and grouping
            foreach ($processedData as $entry) {
                $grouping = $entry['Grouping'];
                $beginning_date = $entry['beginning_date'];
                $ending_date = $entry['ending_date'];

                // Create time blocks dynamically based on data range
                $timeBlocks = range(ceil($max_date / 5) * 5, 0, -5);

                foreach ($timeBlocks as $time) {
                    // Initialize counts array if not already set
                    if (!isset($counts[$time][$grouping])) {
                        $counts[$time][$grouping] = ['Total' => 0, 'New' => 0, 'Extinct' => 0];
                    }
                    // Count Total Genera Active in the Time Block
                    // the genus begin time should be oldeer than current time, and the ending time should be less than current time, meaning not yet dead
                    // or the ending time is 0, meaning it is still alive
                    if ($beginning_date >= $time && ($ending_date <= $time || $ending_date == 0)) {
                        $counts[$time][$grouping]['Total']++;
                    }
                    // Count New Genera in the Time Block
                    if ($beginning_date >= $time && $beginning_date < $time + 5) {
                        $counts[$time][$grouping]['New']++;
                    }
                    // Count Extinct Genera in the Time Block
                    if ($ending_date > 0 && $ending_date >= $time && $ending_date < $time + 5) {
                        $counts[$time][$grouping]['Extinct']++;
                    }
                }
            }
        }

        ksort($counts);
        // Format the counts into the desired JSON object
        $jsonOutput = ['MinDate' => $min_date, 'MaxDate' => $max_date, 'TimeBlocks' => []];
        foreach ($counts as $time => $grouping) {
            $blockData = ['TimeBlock' => $time, 'Groupings' => []];
            foreach ($grouping as $groupingName => $values) {
                $blockData['Groupings'][] = [
                    'Grouping' => $groupingName,
                    'Total' => $values['Total'],
                    'New' => $values['New'],
                    'Extinct' => $values['Extinct']
                ];
            }
            $jsonOutput['TimeBlocks'][] = $blockData;
        }
        ?>
    
    <!-- Divs for Plotly Charts -->
    <?php if (count($fossilsOlderThan750Ma) > 0): ?>
        <div class="container mt-5">
            <h4 style="text-align: center">Note: There are <?php echo count($fossilsOlderThan750Ma); ?> fossils older than 750 Ma not shown on the following charts</h4>
        </div>
    <?php endif; ?>
    <div id="plot-total" class="mb-5 w-75"></div>
    <div id="plot-new" class="mb-5 w-75"></div>
    <div id="plot-extinct" class="mb-5 w-75"></div>

    <script>
        const data = <?php echo json_encode($jsonOutput); ?>;
        const timeBins = data.TimeBlocks.map(block => block.TimeBlock);

        const totalTraces = [];
        const newTraces = [];
        const extinctTraces = [];

        data.TimeBlocks.forEach(block => {
            block.Groupings.forEach(groupData => {
                const groupingName = groupData.Grouping;
                // For Total Genera Plot
                let traceTotal = totalTraces.find(trace => trace.name === groupingName);
                if (!traceTotal) {
                    traceTotal = {
                    x: [],
                    y: [],
                    mode: 'lines',
                    name: groupingName,
                    stackgroup: 'one'
                    };
                    totalTraces.push(traceTotal);
                }
                traceTotal.x.push(block.TimeBlock);
                traceTotal.y.push(groupData.Total);

                // For New Genera Plot
                let traceNew = newTraces.find(trace => trace.name === groupingName);
                if (!traceNew) {
                    traceNew = {
                    x: [],
                    y: [],
                    mode: 'lines',
                    name: groupingName,
                    stackgroup: 'one'
                    };
                    newTraces.push(traceNew);
                }
                traceNew.x.push(block.TimeBlock);
                traceNew.y.push(groupData.New);

                // For Extinct Genera Plot
                let traceExtinct = extinctTraces.find(trace => trace.name === groupingName);
                if (!traceExtinct) {
                    traceExtinct = {
                    x: [],
                    y: [],
                    mode: 'lines',
                    name: groupingName,
                    stackgroup: 'one'
                    };
                    extinctTraces.push(traceExtinct);
                }
                traceExtinct.x.push(block.TimeBlock);
                traceExtinct.y.push(groupData.Extinct);
            });
        });

        const stage_ranges = {
            'Hadean': [[4600, 4000], [174/255, 2/255, 126/255]],
            'Eoarchean': [[4000, 3600], [218/255, 3/255, 127/255]],
            'Paleoarchean': [[3600, 3200], [244/255, 68/255, 159/255]],
            'Mesoarchean': [[3200, 2800], [247/255, 104/255, 169/255]],
            'Neoarchean': [[2800, 2500], [249/255, 155/255, 193/255]],
            'Siderian': [[2500, 2300], [247/255, 79/255, 124/255]],
            'Rhyacian': [[2300, 2050], [247/255, 91/255, 137/255]],
            'Orosirian': [[2050, 1800], [247/255, 104/255, 152/255]],
            'Statherian': [[1800, 1600], [248/255, 117/255, 167/255]],
            'Calymmian': [[1600, 1400], [253/255, 192/255, 122/255]],
            'Ectasian': [[1400, 1200], [253/255, 204/255, 138/255]],
            'Stenian': [[1200, 1000], [254/255, 217/255, 154/255]],
            'Tonian': [[1000, 720], [254/255, 191/255, 78/255]],
            'Cryogenian': [[720, 635], [254/255, 204/255, 92/255]],
            'Ediacaran': [[635, 538.8], [254/255, 217/255, 106/255]],
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
            'Quaternary': [[2.58, -20], [255/255, 237/255, 179/255]]
        };

        const maxDate = data.MaxDate;
        const filteredStages = Object.fromEntries(
            Object.entries(stage_ranges).filter(([stage, [[start, end]]]) => {
                return end < maxDate;
            })
        );

        const shapes = Object.keys(filteredStages).map(stage => {
            const [range, color] = filteredStages[stage];
            return {
                type: 'rect',
                xref: 'x',
                yref: 'paper',
                x0: range[0],
                x1: range[1],
                y0: 0,
                y1: 0.05,
                fillcolor: `rgba(${color[0] * 255}, ${color[1] * 255}, ${color[2] * 255}, 0.75)`,
                line: { width: 0.25, color: 'black' }
            };
        });

        const annotations = Object.keys(filteredStages).map(stage => {
            const [range] = filteredStages[stage];
            return {
                xref: 'x',
                yref: 'paper',
                x: (range[0] + range[1]) / 2,
                y: 0.009,
                text: stage.slice(0, 3),
                showarrow: false,
                font: {
                    size: 15,
                    color: 'black'
                },
                align: 'center'
            };
        });

        const borderShape = {
            type: 'rect',
            xref: 'paper',
            yref: 'paper',
            x0: 0,
            y0: 0,
            x1: 1,
            y1: 1,
            line: {
                color: 'black',
                width: 1
            },
            fillcolor: 'rgba(0,0,0,0)'
        };

        const paddingTrace = {
            x: [Math.min(...timeBins), Math.max(...timeBins)],
            y: [0, 0],
            mode: 'lines',
            line: { color: 'rgba(0,0,0,0)' },
            showlegend: false
        };

        // downloadCSV(totalTraces, 'total_genera.csv');
        // downloadCSV(newTraces, 'new_genera.csv');
        // downloadCSV(extinctTraces, 'extinct_genera.csv');

        totalTraces.unshift(paddingTrace);
        newTraces.unshift(paddingTrace);
        extinctTraces.unshift(paddingTrace);

        // Plotting Total Genera
        Plotly.newPlot('plot-total', totalTraces, {
            title: {
                text: 'Total Genera Over Time',
                font: {
                weight: 'bold'
                },
                y: 0.91,
            },
            xaxis: { title: 'Time (Million Years Ago)', autorange: 'reversed', showgrid: false, zeroline: false, ticks: 'outside', ticklen: 8, tickWidth: 2 },
            yaxis: { title: { text: 'Number of Genera', standoff: 20}, showgrid: false, ticks: 'outside', ticklen: 8, tickWidth: 2 },
            height: 800,
            shapes: [...shapes, borderShape],
            annotations: annotations,
            legend: {traceorder: 'normal'},
        });

        // Plotting New Genera
        Plotly.newPlot('plot-new', newTraces, {
            title: {
                text: 'New Genera Over Time',
                font: {
                    weight: 'bold'
                },
                y: 0.91,
            },
            xaxis: { title: 'Time (Million Years Ago)', autorange: 'reversed', showgrid: false, zeroline: false, ticks: 'outside', ticklen: 8, tickWidth: 2 },
            yaxis: { title: { text: 'Number of New Genera', standoff: 20}, showgrid: false, ticks: 'outside', ticklen: 8, tickWidth: 2 },
            height: 800,
            shapes: [...shapes, borderShape],
            annotations: annotations,
            legend: {traceorder: 'normal'},
        });

        // Plotting Extinct Genera
        Plotly.newPlot('plot-extinct', extinctTraces, {
            title: {
                text: 'Extinct Genera Over Time',
                font: {
                    weight: 'bold'
                },
                y: 0.91,
            },
            xaxis: { title: 'Time (Million Years Ago)', autorange: 'reversed', showgrid: false, zeroline: false, ticks: 'outside', ticklen: 8, tickWidth: 2 },
            yaxis: { title: { text: 'Number of Extinct Genera', standoff: 20}, showgrid: false, ticks: 'outside', ticklen: 8, tickWidth: 2 },
            height: 800,
            shapes: [...shapes, borderShape],
            annotations: annotations,
            legend: {traceorder: 'normal'},
        });

        function downloadCSV(traces, filename) {
            const rows = [];

            // Create headers, including a column for the total sum
            const headers = ['TimeBlock'];
            traces.forEach(trace => headers.push(trace.name));
            headers.push('TotalSum');  // New column for sum of Total, New, and Extinct
            rows.push(headers.join(','));
            
            let maxLength = 0;
            let maxTraceIndex = -1;

            traces.forEach((trace, index) => {
                if (trace.x.length > maxLength) {
                    maxLength = trace.x.length;
                    maxTraceIndex = index;
                }
            });


            for (let i = 0; i < maxLength; i++) {
                const row = [traces[maxTraceIndex].x[i]]; // Time block
                let totalSum = 0;
                // Skip the first trace (paddingTrace) and sum the rest
                traces.slice(0).forEach(trace => {
                    const value = trace.y[i];
                    if (value === undefined) {
                        row.push(0);
                    } else {
                        row.push(value);
                        totalSum += value; 
                    }

                });

                row.push(totalSum);
                rows.push(row.join(','));
            }

            // Trigger download
            const csvContent = rows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', filename);
            a.click();
        }


    </script>
    <?php
    } // End if for selected grouping
    ?>

  </div> 
</body>
</html>
