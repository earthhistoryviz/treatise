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
            echo "Using lookup table with file name: MasterRegionalStageBivalveTweaks9Nov2025.xlsx";
            $geologicalStages = parseGeologicalStages("./uploads/MasterRegionalStageBivalveTweaks9Nov2025.xlsx");
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
                // Define taxonomy hierarchy in order (left to right in excel, high level to low level)
                $taxonomyLevels = [
                    'Phylum', 'Subphylum', 'Class', 'Subclass', 'Order',
                    'Suborder', 'Infraorder', 'Superfamily', 'Family', 'Subfamily'
                ];
                // Initialize previous values for taxonomy levels, used for inheritance
                $previous = [];
                foreach ($taxonomyLevels as $level) {
                    $previous[$level] = "'None'";
                }
                if ($first_row === $excelColumnNames) {
                    echo "<br>Columns in correct format, parsing...<br>";
                    $missingFirstOccur = [];
                    $missingLastOccur = [];
                    $unrecognizedStages = [];
                    $amountOfGeneraUploaded = 0;
                    $amountOfImagesFound = 0;
                    $amountOfNewImagesAdded = 0;
                    $amountOfRowsSkipped = 0;
                    foreach($rows as $row) {
                        //Replace empty strings with NULL and escape strings
                        $escaped_row = array_map(function ($value) use ($conn) {
                            if ($value === null || $value === '') {
                                return 'NULL';
                            }
                            $trimmed = trim(ltrim(mysqli_real_escape_string($conn, $value), "?"));
                            if (empty($value) || empty($trimmed)) return 'NULL';
                            else return "'" . $trimmed . "'";
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
                        $baseStage = ucwords(trim(trim($escaped_row[$excelColumnNamesWithIndexes['First_Occurrence']], "'\"")));
                        if (!$baseStage || $baseStage === "NULL") {
                            echo "$genera has empty First Occurrence.<br>";
                            $missingFirstOccur[] = $genera;
                            $baseStage = "'Unknown'";
                        }
                        $topStage = ucwords(trim(trim($escaped_row[$excelColumnNamesWithIndexes['Last_Occurrence']], "'\"")));
                        if (!$topStage || $topStage === "NULL") {
                            echo "$genera has empty Last Occurrence.<br>";
                            $missingLastOccur[] = $genera;
                            $topStage = "'Unknown'";
                        }
                        // Now do calculations about age here, geological stages in $geologicalStages
                        // First check that stages parsed from treatise file exists in $geologicalStages
                        // Base stage
                        echo "<br>Parsing <b>$genera</b>:";
                        $conversionFailed = false;
                        if ($baseStage !== "'Unknown'") {
                            $convertedStage = standardizeGeologicalStage($baseStage, $geologicalStages);
                            if (!$convertedStage) {
                                $conversionFailed = true;
                            } else {
                                echo "<br>Converted First Occurrence $baseStage to $convertedStage";
                                $baseStage = $convertedStage;
                            }
                        } else {
                            $conversionFailed = true;
                        }
                        if ($conversionFailed) {
                            echo "<br>Could not find $baseStage within International Stage";
                            if (!isset($unrecognizedStages[$baseStage])) {
                                $unrecognizedStages[$baseStage] = [];
                            }
                            $unrecognizedStages[$baseStage][] = $genera;
                            $baseStage = "'Unknown'";
                        }
                        // Top stage
                        $conversionFailed = false;
                        if ($topStage !== "'Unknown'") {
                            $convertedStage = standardizeGeologicalStage($topStage, $geologicalStages);
                            if (!$convertedStage) {
                                $conversionFailed = true;
                            } else {
                                echo "<br>Converted Last Occurrence $topStage to $convertedStage";
                                $topStage = $convertedStage;
                            }
                        } else {
                            $conversionFailed = true;
                        }
                        if ($conversionFailed) {
                            echo "<br>Could not find $topStage within International Stage";
                            if (!isset($unrecognizedStages[$topStage])) {
                                $unrecognizedStages[$topStage] = [];
                            }
                            $unrecognizedStages[$topStage][] = $genera;
                            $topStage = "'Unknown'";
                        }
                        // Now use base stage and top stage tp access values from $geologicalStages, you can see these values in TimescaleLib.php
                        // If base or top stage is unknown, set all relevant fields to Unknown
                        if ($baseStage !== "'Unknown'") {
                            $baseData = $geologicalStages[$baseStage];
                            $baseDate = round($baseData["base"], 2);
                            $baseFractionUp = (float)$baseData["begin_percent_up"];
                            $beginningStage = $baseData["stage"];
                            $internationalBase = $baseData["international_base"];
                            echo "<br>Computed base age of <b>$genera</b> as $baseDate.";
                            echo "<br>Found First Occurrence <b>$beginningStage</b> within International Stage as <b>$internationalBase</b>.";
                        } else {
                            $baseData = "'Unknown'";
                            $baseDate = "'Unknown'";
                            $baseFractionUp = "'Unknown'";
                            $beginningStage = "'Unknown'";
                            $internationalBase = "'Unknown'";
                        }
                        if ($topStage !== "'Unknown'") {
                            $topData = $geologicalStages[$topStage];
                            $topDate = round($topData["top"], 2);
                            $topFractionUp = (float)$topData["end_percent_up"];
                            $endingStage = $topData["stage"];
                            $internationalTop = $topData["international_top"];
                            echo "<br>Computed top age of <b>$genera</b> as $topDate.";
                            echo "<br>Found Last Occurrence <b>$endingStage</b> within International Stage as <b>$internationalTop</b>.";
                        } else {
                            $topData = "'Unknown'";
                            $topDate = "'Unknown'";
                            $topFractionUp = "'Unknown'";
                            $endingStage = "'Unknown'";
                            $internationalTop = "'Unknown'";
                        }

                        // Use previous row values if no values are provided
                        // If provided, update value and reset all lower order values
                        // For each taxonomy level (each column):
                        for ($i = 0; $i < count($taxonomyLevels); $i++) {
                            $level = $taxonomyLevels[$i];
                            $columnIndex = $excelColumnNamesWithIndexes[$level];
                            $isInvalid = isInvalidEscapeRow($columnIndex, $escaped_row);
                            if ($isInvalid == 1) {
                                // No valid value - inherit from previous row
                                $previousLevel = $previous[$level];
                                echo "<br>No valid $level provided, using previous value: $previousLevel";
                                $escaped_row[$columnIndex] = $previousLevel;
                            } elseif ($isInvalid == 0) {
                                // Valid value found - update this level and reset all lower levels
                                $previous[$level] = $escaped_row[$columnIndex];
                                for ($lowerIndex = $i + 1; $lowerIndex < count($taxonomyLevels); $lowerIndex++) {
                                    $previous[$taxonomyLevels[$lowerIndex]] = "'None'";
                                }
                            }
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
                        // preg_match_all('/\((https?:\/\/[^\)]+)\)/', $allImageLinks, $matches); // old version
                        preg_match_all('/[\(\{](https?:\/\/[^\)\}]+)[\)\}]/', $allImageLinks, $matches);

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

                    // Summary section
                    echo "<br>Done parsing.<br>";
                    echo "<br>These are the genera we could not find base stage (first occurrence) for:<br>";
                    foreach($missingFirstOccur as $genera) {
                        echo "$genera<br>";
                    }
                    echo "<br>These are the genera we could not find top stage (last occurrence) for:<br>";
                    foreach($missingLastOccur as $genera) {
                        echo "$genera<br>";
                    }
                    echo "<br>These are the stages we could not convert:<br>";
                    foreach($unrecognizedStages as $stage => $generaList) {
                        // Only display stages where conversion failed, skip those where no stage is provided
                        if ($stage !== "'Unknown'") {
                           $generaCount = count($generaList);
                            $generaNames = implode(", ", $generaList);
                            echo "<br><b>$stage</b> (found in $generaCount genera): $generaNames<br>"; 
                        }
                    }
                    echo "<br>Amount of genera uploaded: $amountOfGeneraUploaded<br>";
                    echo "Amount of images found: $amountOfImagesFound<br>";
                    echo "Amount of new images added: $amountOfNewImagesAdded<br>";
                    echo "Total rows parsed: " . count($rows) . "<br>";
                    echo "Rows skipped due to no genus provided: $amountOfRowsSkipped<br>";
                    echo "Amount of rows with missing First Occurrence: " . count($missingFirstOccur) . "<br>";
                    echo "Amount of rows with missing Last Occurrence: " . count($missingLastOccur) . "<br>";
                    // This count of unrecognized stages includes cases when no first or last occurrence is provided
                    $totalUnrecognizedRows = array_sum(array_map('count', $unrecognizedStages));
                    echo "Amount of rows with unrecognized stage names: " . $totalUnrecognizedRows . "<br>";
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