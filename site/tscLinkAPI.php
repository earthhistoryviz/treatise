<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        throw new Exception("Environment file not found: $filePath");
    }

    $envVars = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Split key and value
        list($key, $value) = explode('=', $line, 2);
        $envVars[trim($key)] = trim($value);
    }

    return $envVars;
}


function fetchDataFromApi($url) {
    $response = file_get_contents($url);
    if ($response === false) {
        throw new Exception("Error fetching data from URL: $url");
    }
    return $response;
}

function sendDatapackToTsconline($datapack, $url, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datapack);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $token"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode >= 400) {
        throw new Exception("Error communicating with TSConline. HTTP Code: $httpCode. Response: $response");
    }

    return $response;
}

try {
    $siteUrlTreatise = $_SERVER['SERVER_NAME'];
    $url = "https://$siteUrlTreatise.treatise.geolex.org/searchAPI.php";
    $fetchDataAPIResponse = fetchDataFromApi($url);

    // Decode JSON response
    $data = json_decode($fetchDataAPIResponse, true);
    if ($data === null) {
        throw new Exception("Error decoding JSON from API response.");
    }

    // Create datapack
    $processedData = [];
    $min_new = PHP_INT_MAX;
    $max_new = PHP_INT_MIN;

    $min_extinct = PHP_INT_MAX;
    $max_extinct = PHP_INT_MIN;

    $min_total = PHP_INT_MAX;
    $max_total = PHP_INT_MIN;

    $max_date = PHP_INT_MIN;

    // Process each entry
    foreach ($data as $genus => $entry) {
        $beginning_date = floatval($entry['beginning_date']);
        $ending_date = floatval($entry['ending_date']);
        $processedData[] = [
            'beginning_date' => $beginning_date,
            'ending_date' => $ending_date
        ];
        $max_date = max($max_date, $beginning_date, $ending_date);
    }

    // Initialize counts
    $counts = [];
    $timeBlocks = range(0, ceil($max_date / 5) * 5, 5);

    // Process time blocks
    foreach ($processedData as $entry) {
        $beginning_date = $entry['beginning_date'];
        $ending_date = $entry['ending_date'];

        foreach ($timeBlocks as $time) {
            if (!isset($counts[$time])) {
                $counts[$time] = ['Total' => 0, 'New' => 0, 'Extinct' => 0];
            }
            
            if ($beginning_date >= $time && ($ending_date <= $time || $ending_date === 0)) {
                $counts[$time]['Total']++;
            }

            if ($beginning_date >= $time && $beginning_date < $time + 5) {
                $counts[$time]['New']++;
            }

            if ($ending_date > 0 && $ending_date >= $time && $ending_date < $time + 5) {
                $counts[$time]['Extinct']++;
            }
        }
    }

    // Calculate min and max values for Total, New, and Extinct
    foreach ($counts as $count) {
        $min_total = min($min_total, $count['Total']);
        $max_total = max($max_total, $count['Total']);
        
        $min_new = min($min_new, $count['New']);
        $max_new = max($max_new, $count['New']);
        
        $min_extinct = min($min_extinct, $count['Extinct']);
        $max_extinct = max($max_extinct, $count['Extinct']);
    }

    // Sort counts by time in descending order
    krsort($counts);

    // Prepare the data pack format
    $datapack = "format version:\t1.3\n";
    $datapack .= "date:\t" . "11/20/24" . "\n\n";

    $datapack .= "Total-Genera\tpoint\t200\t24/156/243\n";
    $datapack .= "rect\tline\tnofill\t$min_total\t$max_total\tsmoothed\n";
    foreach ($timeBlocks as $time) {
        $datapack .= "\t$time\t" . $counts[$time]['Total'] . "\n";
    }
    $datapack .= "\n";

    $datapack .= "New-Genera\tpoint\t200\t161/205/103\n";
    $datapack .= "rect\tline\tnofill\t$min_new\t$max_new\tsmoothed\n";
    foreach ($timeBlocks as $time) {
        $datapack .= "\t$time\t" . $counts[$time]['New'] . "\n";
    }
    $datapack .= "\n";

    $datapack .= "Extinct-Genera\tpoint\t200\t255/0/0\n";
    $datapack .= "rect\tline\tnofill\t$min_extinct\t$max_extinct\tsmoothed\n";
    foreach ($timeBlocks as $time) {
        $datapack .= "\t$time\t" . $counts[$time]['Extinct'] . "\n";
    }

    // Output the data as JSON with the text document embedded in the JSON response
    header('Content-Type: application/json');
    $datapacktxt = json_encode([
        "datapack" => $datapack,
    ]);

    // Prepare to send datapack to TSConline
    $tsconlineUrl = "http://localhost:5173/externalChart";
    // http://localhost:5173/externalChart // local
    // https://dev.timescalecreator.org/externalChart // dev
    // https://tsconline.timescalecreator.org//externalChart // prod
    try {
        $envFilePath = __DIR__ . '/.env';
        $envVars = loadEnv($envFilePath);    
        $token = $envVars['BEARER_TOKEN'] ?? null;
        if (!$token) {
            throw new Exception("BEARER_TOKEN not set in the .env file.");
        }
    } catch (Exception $e) {
        throw new Exception("Error loading environment variables: " . $e->getMessage());
    }
    if (!$token) {
        throw new Exception("Bearer token is not set in the environment.");
    }
    $response = sendDatapackToTsconline($datapack, $tsconlineUrl, $token);
    $responseDecoded = json_decode($response, true);
    if (!isset($responseDecoded['id'])) {
        throw new Exception("Invalid response from TSConline: $response");
    }
    echo json_encode([
        "status" => "success",
        "id" => $responseDecoded['id']
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
