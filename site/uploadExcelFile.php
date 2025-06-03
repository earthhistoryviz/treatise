<link rel="stylesheet" type="text/css" href="style.css"/>
<?php
include_once("adminDash.php");
include_once("TimescaleLib.php");
include_once("uploadImage.php");
echo "<div style='width: 100%; margin: 10px;'>";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["upfile"])) {
    $file = $_FILES["upfile"];
    if ($file["error"] === UPLOAD_ERR_OK) {
        $file_name = str_replace(' ', '_', $file["name"]);
        $file_tmp_name = $file["tmp_name"];
        $file_size = $file["size"];
        $file_type = $file["type"];
        $upload_directory = "/app/uploads/";
        $destination = $upload_directory . $file_name;
        if (move_uploaded_file($file_tmp_name, $destination)) {
            echo "File uploaded successfully. File name: " . $file_name;
            $xlsx = SimpleXLSX::parse($destination);
            $geologicalStages = parseGeologicalStages("./uploads/MasterRegionalStagewBrach-subdivisions2June2025.xlsx");
            $columns = [];
            if ($xlsx === false) {
                echo "<br>Can't open excel file.";
            } else {
                echo "<br>Opened excel file.";
                $rows = $xlsx->rows(0);
                $first_row = array_filter(array_shift($rows)); //Get first row and filter out empty elements
                $first_row = array_map(function ($item) { //Replace spaces with underscores
                    return str_replace(' ', '_', trim($item));
                }, $first_row);
                //$excelColumnNames and $conn from SqlConnection.php
                include_once("SqlConnection.php");
                $previousPhylum = "'Unknown'";
                $previousClass = "'Unknown'";
                $previousOrder = "'Unknown'";
                $previousSuperfamily = "'Unknown'";
                $previousFamily = "'Unknown'";
                if ($first_row === $excelColumnNames) {
                    echo "<br>Columns in correct format, parsing...<br>";
                    $missingStages = [];
                    $unrecognizedStages = [];
                    $amountOfGeneraUploaded = 0;
                    $amountOfImagesFound = 0;
                    $amountOfNewImagesAdded = 0;
                    $amountOfRowsSkipped = 0;
                    foreach($rows as $row) {
                        //Replace empty strings with NULL and escape strings
                        $escaped_row = array_map(function ($value) use ($conn) {
                            return empty($value) ? 'NULL' : "'" . trim(ltrim(mysqli_real_escape_string($conn, $value), "?")) . "'";
                        }, $row);
                        //Cut out columns that don't fit the size of columns (most likely empty columns)
                        $escaped_row = array_slice($escaped_row, 0, count($excelColumnNames));
                        $genera = trim(ltrim(trim(trim($escaped_row[$excelColumnNamesWithIndexes['Genus']], "'\""), "?")));
                        if ($genera == null || $genera === "NULL") {
                            echo "Skipping row with empty genera.<br>";
                            $amountOfRowsSkipped++;
                            continue;
                        }
                        //Get base and top from row parsed in excel file, remove whitespace and double or single quotes
                        // In the excel file, change the beginning and ending stages to First_Occurrence and Last_Occurrence
                        $baseStage = ucwords(trim(trim($escaped_row[$excelColumnNamesWithIndexes['First_Occurrence']], "'\"")));
                        if (!$baseStage || $baseStage === "NULL") {
                            echo "Skipping $genera with empty First Occurrence.<br>";
                            $missingStages[] = $genera;
                            $amountOfRowsSkipped++;
                            continue;
                        }
                        $topStage = ucwords(trim(trim($escaped_row[$excelColumnNamesWithIndexes['Last_Occurrence']], "'\"")));
                        if (!$topStage || $topStage === "NULL") {
                            echo "Skipping $genera with empty Last Occurrence.<br>";
                            $missingStages[] = $genera;
                            $amountOfRowsSkipped++;
                            continue;
                        }
                        // Now do calculations about age here, geological stages in $geologicalStages
                        // First check that stages parsed from treatise file exists in $geologicalStages
                        //base stage
                        echo "<br>Parsing <b>$genera</b>:";
                        $conversionFailed = false;
                        $convertedStage = standardizeGeologicalStage($baseStage, $geologicalStages);
                        if (!$convertedStage) {
                            echo "<br>Could not find $baseStage within International Stage";
                            $unrecognizedStages[$baseStage] = "Did not find Base $baseStage for $genera";
                            $conversionFailed = true;
                        } else {
                            echo "<br>Converted First Occurrence $baseStage to $convertedStage";
                            $baseStage = $convertedStage;
                        }
                        $convertedStage = standardizeGeologicalStage($topStage, $geologicalStages);
                        if (!$convertedStage) {
                            echo "<br>Could not find $topStage within International Stage";
                            $unrecognizedStages[$topStage] = "Did not find Top $topStage for $genera";
                            $conversionFailed = true;
                        } else {
                            echo "<br>Converted Last Occurrence $topStage to $convertedStage";
                            $topStage = $convertedStage;
                        }
                        if ($conversionFailed) {
                            $amountOfRowsSkipped++;
                            continue;
                        }
                        // Now that they exist we can access values from $geologicalStages, you can see these values in TimescaleLib.php
                        $baseData = $geologicalStages[$baseStage];
                        $baseDate = round($baseData["base"], 2);
                        $baseFractionUp = (float)$baseData["percent_up"];
                        $beginningStage = $baseData["stage"];
                        $internationalBase = $baseData["international_base"];
                        echo "<br>Computed base age of <b>$genera</b> as $baseDate.";
                        echo "<br>Found First Occurrence <b>$beginningStage</b> within International Stage as <b>$internationalBase</b>.";
                        $topData = $geologicalStages[$topStage];
                        $topDate = round($topData["top"], 2);
                        $topFractionUp = (float)$topData["percent_up"];
                        $endingStage = $topData["stage"];
                        $internationalTop = $topData["international_top"];
                        echo "<br>Computed top age of <b>$genera</b> as $topDate.";
                        echo "<br>Found Last Occurrence <b>$endingStage</b> within International Stage as <b>$internationalTop</b>.";
                        // Use previous row values if no values are provided
                        $phylumIndex = $excelColumnNamesWithIndexes['Phylum'];
                        $invalidPhylum = isInvalidEscapeRow($phylumIndex, $escaped_row);
                        if ($invalidPhylum == 1) {
                            echo "<br>No valid phylum provided, using previous value: $previousPhylum";
                            $escaped_row[$phylumIndex] = $previousPhylum; // if no phylum is provided, use the previous phylum
                        } elseif ($invalidPhylum == 0) {
                            $previousPhylum = $escaped_row[0]; // if phylum is provided, update the previous phylum
                        }
                        $classIndex = $excelColumnNamesWithIndexes['Class'];
                        $invalidClass = isInvalidEscapeRow($classIndex, $escaped_row);
                        if ($invalidClass == 1) {
                            echo "<br>No valid class provided, using previous value: $previousClass";
                            $escaped_row[$classIndex] = $previousClass;
                        } elseif ($invalidClass == 0) {
                            $previousClass = $escaped_row[$classIndex];
                        }
                        $orderIndex = $excelColumnNamesWithIndexes['Order'];
                        $invalidOrder = isInvalidEscapeRow($orderIndex, $escaped_row);
                        if ($invalidOrder == 1) {
                            echo "<br>No valid order provided, using previous value: $previousOrder";
                            $escaped_row[$orderIndex] = $previousOrder;
                        } elseif ($invalidOrder == 0) {
                            $previousOrder = $escaped_row[$orderIndex];
                        }
                        $superFamilyIndex = $excelColumnNamesWithIndexes['Superfamily'];
                        $invalidSuperfamily = isInvalidEscapeRow($superFamilyIndex, $escaped_row);
                        if ($invalidSuperfamily == 1) {
                            echo "<br>No valid superfamily provided, using previous value: $previousSuperfamily";
                            $escaped_row[$superFamilyIndex] = $previousSuperfamily;
                        } elseif ($invalidSuperfamily == 0) {
                            $previousSuperfamily = $escaped_row[$superFamilyIndex];
                        }
                        $familyIndex = $excelColumnNamesWithIndexes['Family'];
                        $invalidFamily = isInvalidEscapeRow($familyIndex, $escaped_row);
                        if ($invalidFamily == 1) {
                            echo "<br>No valid family provided, using previous value: $previousFamily";
                            $escaped_row[$familyIndex] = $previousFamily;
                        } elseif ($invalidFamily == 0) {
                            $previousFamily = $escaped_row[$familyIndex];
                        }
                        // On DUPLICATE KEY UPDATE is needed in the case we're tring to update a row that already exists in the table
                        $sqlInsert = "INSERT INTO fossil (`" . implode("`,`", $excelColumnNames) . "`, `beginning_date`, `fraction_up_beginning_stage`, `beginning_stage`, `ending_date`, `fraction_up_ending_stage`, `ending_stage`) 
            VALUES (" . implode(",", $escaped_row) . ", '$baseDate', '$baseFractionUp', '$internationalBase', '$topDate', '$topFractionUp', '$internationalTop') ON DUPLICATE KEY UPDATE ";
                        // First add columns that are defined in the excel file
                        foreach($excelColumnNames as $name) {
                            $sqlInsert .= "`$name` = CASE WHEN VALUES(`$name`) <> '' THEN VALUES(`$name`) ELSE `$name` END,";
                        }
                        // Now add manually defined columns
                        $sqlInsert .= "`beginning_date` = CASE WHEN VALUES(`beginning_date`) <> '' THEN VALUES(`beginning_date`) ELSE `beginning_date` END,";
                        $sqlInsert .= "`fraction_up_beginning_stage` = CASE WHEN VALUES(`fraction_up_beginning_stage`) <> '' THEN VALUES(`fraction_up_beginning_stage`) ELSE `fraction_up_beginning_stage` END,";
                        $sqlInsert .= "`beginning_stage` = CASE WHEN VALUES(`beginning_stage`) <> '' THEN VALUES(`beginning_stage`) ELSE `beginning_stage` END,";
                        $sqlInsert .= "`ending_date` = CASE WHEN VALUES(`ending_date`) <> '' THEN VALUES(`ending_date`) ELSE `ending_date` END,";
                        $sqlInsert .= "`fraction_up_ending_stage` = CASE WHEN VALUES(`fraction_up_ending_stage`) <> '' THEN VALUES(`fraction_up_ending_stage`) ELSE `fraction_up_ending_stage` END,";
                        $sqlInsert .= "`ending_stage` = CASE WHEN VALUES(`ending_stage`) <> '' THEN VALUES(`ending_stage`) ELSE `ending_stage` END;";
                        if ($conn->query($sqlInsert) !== true) {
                            echo "<br>Error inserting data into fossil database: " . $conn->error . ". Ignoring this row and continuing.<br>";
                        } else {
                            echo "<br>Inserted genera <b>$genera</b> into database.";
                        }

                        // Add images to upload directory based on links in URL
                        $allImageLinks = $escaped_row[$excelColumnNamesWithIndexes['figure_link']];
                        $allFigureCaptionsRaw = $escaped_row[$excelColumnNamesWithIndexes['figure_index']];
                        // split the figure captions by new line
                        $allFigureCaptions = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $allFigureCaptionsRaw);
                        $figureCaptions = preg_split("/\r\n|\n|\r/", trim($allFigureCaptions));
                        // Regular expression to capture URLs inside parentheses
                        preg_match_all('/\((https?:\/\/[^\)]+)\)/', $allImageLinks, $matches);
                        $imageLinks = $matches[1];
                        // Extracted URLs will be in $matches[1], $matches[0] will contain the full URL with parentheses (don't want)
                        $imageFolderPath = $upload_directory . $genera . "/Figure_Caption/";
                        if (!makeUploadDir($imageFolderPath)) {
                            echo "<br>Failed to create upload directory for images.";
                        } else {
                            for ($i = 0; $i < count($imageLinks); $i++) {
                                $amountOfImagesFound++;
                                $caption = $figureCaptions[$i] ?? "figure_$i";
                                
                                $cleanCaption = normalizeCaption($caption);
                                $imageFileName = $genera . "_" . $cleanCaption . ".jpg";
                                $imagePath = $imageFolderPath . $imageFileName;

                                if (file_exists($imagePath)) {
                                    echo "<br>Image already exists for $genera: $imageFileName — skipping download.";
                                    continue;
                                }

                                $image = file_get_contents($imageLinks[$i]);
                                if ($image === false) {
                                    echo "<br>Failed to fetch image from $imageLinks[$i]";
                                    continue;
                                }

                                if (file_put_contents($imagePath, $image)) {
                                    echo "<br>Downloaded image for $genera: $imageFileName";
                                    $amountOfNewImagesAdded++;
                                } else {
                                    echo "<br>Failed to save image for $genera";
                                }
                            }
                        }
                        echo "<br>";
                        $amountOfGeneraUploaded++;
                    }
                    echo "<br>Done parsing.<br>";
                    echo "<br>These are the genera we could not find base or top stage for:<br>";
                    foreach($missingStages as $genera) {
                        echo "$genera<br>";
                    }
                    echo "<br>These are the stages we could not convert:<br>";
                    foreach($unrecognizedStages as $stage => $value) {
                        echo "$value<br>";
                    }
                    var_dump($unrecognizedStages);
                    echo "<br>Amount of genera uploaded: $amountOfGeneraUploaded<br>";
                    echo "Amount of images found: $amountOfImagesFound<br>";
                    echo "Amount of new images added: $amountOfNewImagesAdded<br>";
                    echo "Total rows parsed: " . count($rows) . "<br>";
                    echo "Rows skipped: $amountOfRowsSkipped<br>";
                    echo "- Skipped due to missing First or Last Occurrence: " . count($missingStages) . "<br>";
                    echo "- Skipped due to unrecognized stage names: " . count($unrecognizedStages) . "<br>";
                } else {
                    //Excel file does not contain same columns
                    echo "<br>Excel file does not contain same columns as database. The first row of the excel file should have the exact same column names (case-sensitive) in the same order.";
                    echo "<br><br>Mismatched or Missing Columns:";

                    $missingInExcel = array_diff($excelColumnNames, $first_row);
                    $missingInDB = array_diff($first_row, $excelColumnNames);
                    if (!empty($missingInExcel)) {
                        echo "<br>Missing in Excel file:";
                        foreach ($missingInExcel as $missing) {
                            echo "<br> - " . str_replace('_', ' ', $missing);
                        }
                    } elseif (!empty($missingInDB)) {
                        echo "<br>Your Excel file contains extra columns not present in database. Missing in Databse:";
                        foreach ($missingInDB as $missing) {
                            echo "<br> - " . str_replace('_', ' ', $missing);
                        }
                    } else {
                        echo "<br>Not sure what is missing/wrong. Double check column names in excel file.";
                    }
                    echo "<br><br>Columns in database:";
                    foreach ($excelColumnNames as $name) {
                        $name = str_replace('_', ' ', $name);
                        echo "<br>$name";
                    }
                    echo "<br><br>Columns in provided excel file:";
                    foreach ($first_row as $name) {
                        $name = str_replace('_', ' ', $name);
                        echo "<br>$name";
                    }
                }
            }
            if (!unlink($destination)) {
                echo "<br>Images file could not be deleted from server.";
            }
        } else {
            // Error while moving the file
            echo "<br>Error uploading file. Please try again.";
        }
    } else {
        // Handle upload errors
        echo "<br>Upload failed with error code: " . $file["error"];
    }
}
echo "</div>";

exec("python3 treatise_plots.py", $output, $error);

function normalizeCaption($caption) {
    // Lowercase, remove parentheses and periods, and convert non-alphanum to underscores
    $caption = strtolower($caption);
    $caption = preg_replace('/[^\w\s]/', '', $caption); // Remove punctuation
    $caption = preg_replace('/\s+/', '_', $caption);    // Replace whitespace with underscores
    $caption = preg_replace('/_+/', '_', $caption);     // Collapse multiple underscores
    return trim($caption, '_');                         // Remove leading/trailing _
}

function standardizeGeologicalStage($stage, $geologicalStages)
{
    if (isset($geologicalStages[$stage])) {
        return $stage;
    }
    $parts = explode(' ', $stage);
    if (count($parts) < 2) {
        return null;
    }
    $reversed = implode(' ', array_reverse($parts));
    if (isset($geologicalStages[$reversed])) {
        return $reversed;
    }
    $permutations = [
      $parts[0] . ' ' . $parts[1] . ' ' . $parts[2],
      $parts[0] . ' ' . $parts[2] . ' ' . $parts[1],
      $parts[1] . ' ' . $parts[0] . ' ' . $parts[2],
      $parts[1] . ' ' . $parts[2] . ' ' . $parts[0],
      $parts[2] . ' ' . $parts[0] . ' ' . $parts[1],
      $parts[2] . ' ' . $parts[1] . ' ' . $parts[0],
    ];
    if (count($parts) > 2) {
        foreach ($permutations as $permutation) {
            if (isset($geologicalStages[$permutation])) {
                return $permutation;
            }
        }
    }
    return null;
}

function isInvalidEscapeRow($colIdx, $escaped_row) {
    if (!isset($escaped_row[$colIdx])) {
        return -1;
    }
    if ($escaped_row[$colIdx] == "NULL" || empty(trim($escaped_row[$colIdx])) || str_starts_with($escaped_row[$colIdx], "'pg.") || str_starts_with($escaped_row[$colIdx], "'p.") || $escaped_row[$colIdx] == "' '") {
        return 1;
    } else {
        return 0;
    }
}

?>