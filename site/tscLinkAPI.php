<?php

function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        throw new Exception("Environment file not found: $filePath");
    }

    $envVars = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $envVars[trim($key)] = trim($value);
    }

    return $envVars;
}

function fetchDataFromApi($url)
{
    $response = file_get_contents($url);
    if ($response === false) {
        throw new Exception("Error fetching data from URL: $url");
    }
    return $response;
}

function sendDatapackToTsconline($datapack, $url, $token, $siteUrlTreatise) {
    $datapackHash = hash('sha256', $datapack);
    $ch = curl_init();
    $postFields = [];
    $tempFilePath = tempnam(sys_get_temp_dir(), 'datapack_');
    file_put_contents($tempFilePath, $datapack);
    $cfile = new CURLFile($tempFilePath, 'text/plain', $datapackHash . '.txt');
    $postFields['datapack'] = $cfile;
    $postFields['title'] = $siteUrlTreatise;
    $postFields['description'] = 'Datapack generated via Treatise API';
    $postFields['authoredBy'] = 'Treatise';
    $postFields['isPublic'] = 'true';
    $postFields['type'] = 'official';
    $postFields['uuid'] = 'official';
    $postFields['references'] = json_encode([]);
    $postFields['tags'] = json_encode(["Treatise"]);
    $postFields['hasFiles'] = 'false';

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "datapacktitle: $siteUrlTreatise",
        "datapackHash: $datapackHash"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    unlink($tempFilePath);

    if ($response === false || $httpCode >= 400) {
        throw new Exception("Error communicating with TSConline. HTTP Code: $httpCode. Response: $response");
    }

    // $logFilePath = __DIR__ . '/datapack.log';
    // file_put_contents($logFilePath, $datapack, FILE_APPEND);

    return $response;
}

try {
    $siteUrlTreatise = ucfirst(strtolower($_SERVER['SERVER_NAME']));
    $url = "https://$siteUrlTreatise.treatise.geolex.org/searchAPI.php";
    $fetchDataAPIResponse = fetchDataFromApi($url);

    // Decode JSON response
    $data = json_decode($fetchDataAPIResponse, true)["data"];
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
    $datapack .= "date:\t" . "4/16/25" . "\n\n";

    $datapack .= "$siteUrlTreatise Total-Genera\tpoint\t200\t255/255/255\n";
    $datapack .= "rect\tline\tfill\t$min_total\t$max_total\tsmoothed\n";
    foreach ($timeBlocks as $time) {
        $datapack .= "\t$time\t" . $counts[$time]['Total'] . "\n";
    }
    $datapack .= "\n";
    $datapack .= "$siteUrlTreatise New-Genera\tpoint\t200\t255/255/255\n";
    $datapack .= "rect\tline\tfill\t$min_new\t$max_new\tsmoothed\n";
    foreach ($timeBlocks as $time) {
        $datapack .= "\t$time\t" . $counts[$time]['New'] . "\n";
    }
    $datapack .= "\n";

    $datapack .= "$siteUrlTreatise Extinct-Genera\tpoint\t200\t255/255/255\n";
    $datapack .= "rect\tline\tfill\t$min_extinct\t$max_extinct\tsmoothed\n";
    foreach ($timeBlocks as $time) {
        $datapack .= "\t$time\t" . $counts[$time]['Extinct'] . "\n";
    }

    $tsconlineUrl = "https://tsconline.timescalecreator.org/external-chart";
    // $tsconlineUrl = "https://dev.timescalecreator.org/external-chart";

    try {
        $token = getenv("BEARER_TOKEN");
        if (!$token) {
            throw new Exception("BEARER_TOKEN not set in the .env file.");
        }
    } catch (Exception $e) {
        throw new Exception("Error loading environment variables: " . $e->getMessage());
    }
    if (!$token) {
        throw new Exception("Bearer token is not set in the environment.");
    }

    $oldestTime = ceil($max_date / 5) * 5; // Round up to the nearest 5 for MA
    $recentTime = null;
    foreach ($timeBlocks as $time) {
        if (isset($counts[$time]) && $counts[$time]['Total'] > 0) {
            $recentTime = $time;
            break;
        }
    }

    if ($recentTime === null) {
        throw new Exception("No valid fossil counts found for the most recent time.");
    }

    $response = sendDatapackToTsconline($datapack, $tsconlineUrl, $token, $siteUrlTreatise);
    $responseDecoded = json_decode($response, true);
    if (!isset($responseDecoded['datapackTitle'])) {
        throw new Exception("Invalid response from TSConline: $response");
    }

    try {
        $datapackPhylum = $responseDecoded['datapackTitle'];
        $tsconlineUrl = "https://tsconline.timescalecreator.org/generate-external-chart?"
        . "datapackTitle=" . urlencode($datapackPhylum)
        . "&chartConfig=Internal"
        . "&baseVal=" . urlencode($oldestTime)
        . "&topVal=" . urlencode($recentTime)
        . "&unitStep=0.1"
        . "&unitType=Ma"
        . "&minMaxPlot=" . urlencode("$min_total-$max_total-$min_new-$max_new-$min_extinct-$max_extinct");

        header("Location: $tsconlineUrl");
        exit;
    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

