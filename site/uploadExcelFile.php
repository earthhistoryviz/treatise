<link rel="stylesheet" type="text/css" href="style.css"/>
<?php
include_once("adminDash.php");
include_once("TimescaleLib.php");
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
      $geologicalStages = parseGeologicalStages("./uploads/Master_RegionalStage_Data_wTatarian_3June2024.xlsx");
      $columns = [];
      if ($xlsx === false) {
        echo "<br>Can't open excel file.";
      } else {
        echo "<br>Opened excel file.";
        $rows = $xlsx->rows(0);
        $first_row = array_filter(array_shift($rows)); //Get first row and filter out empty elements
        $first_row = array_map(function($item) { //Replace spaces with underscores
          return str_replace(' ', '_', $item);
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
          $unrecognizedStages = [];
          foreach($rows as $row) {
            //Replace empty strings with NULL and escape strings
            $escaped_row = array_map(function($value) use ($conn) {
              return empty($value) ? 'NULL' : "'" . trim(ltrim(mysqli_real_escape_string($conn, $value), "?")) . "'";
            }, $row);
            //Cut out columns that don't fit the size of columns (most likely empty columns)
            $escaped_row = array_slice($escaped_row, 0, count($excelColumnNames));
            //Get base and top from row parsed in excel file, remove whitespace and double or single quotes
            $baseStage = ucwords(trim(trim($escaped_row[$excelColumnNamesWithIndexes['First_Occurrence']], "'\"")));
            $topStage = ucwords(trim(trim($escaped_row[$excelColumnNamesWithIndexes['Last_Occurrence']], "'\"")));
            $genera = trim(ltrim(trim(trim($escaped_row[$excelColumnNamesWithIndexes['Genus']], "'\""), "?")));
            if ($genera == null || $genera === "NULL") continue;
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
            if ($conversionFailed) continue;
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
            if ($escaped_row[0] == "NULL" || empty($escaped_row[0]) || str_starts_with($escaped_row[0], "'pg.") || str_starts_with($escaped_row[0], "'p.") || $escaped_row[0] == "' '") {
              echo "<br>No valid phylum provided, using previous value: $previousPhylum";
              $escaped_row[0] = $previousPhylum;
            } else {
              $previousPhylum = $escaped_row[0];
            }
            if ($escaped_row[1] == "NULL" || empty($escaped_row[1]) || str_starts_with($escaped_row[1], "'pg.") || str_starts_with($escaped_row[1], "'p.")) {
              echo "<br>No valid class provided, using previous value: $previousClass";
              $escaped_row[1] = $previousClass;
            } else {
              $previousClass = $escaped_row[1];
            }
            
            if ($escaped_row[2] == "NULL" || empty($escaped_row[2]) || str_starts_with($escaped_row[2], "'pg.") || str_starts_with($escaped_row[2], "'p.")) {
              echo "<br>No valid order provided, using previous value: $previousOrder";
              $escaped_row[2] = $previousOrder;
            } else {
              $previousOrder = $escaped_row[2];
            }
            
            if ($escaped_row[3] == "NULL" || empty($escaped_row[3]) || str_starts_with($escaped_row[3], "'pg.") || str_starts_with($escaped_row[3], "'p.")) {
              echo "<br>No valid superfamily provided, using previous value: $previousSuperfamily";
              $escaped_row[3] = $previousSuperfamily;
            } else {
              $previousSuperfamily = $escaped_row[3];
            }
            
            if ($escaped_row[4] == "NULL" || empty($escaped_row[4]) || str_starts_with($escaped_row[4], "'pg.") || str_starts_with($escaped_row[4], "'p.")) {
              echo "<br>No valid family provided, using previous value: $previousFamily";
              $escaped_row[4] = $previousFamily;
            } else {
              $previousFamily = $escaped_row[4];
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
            if ($conn->query($sqlInsert) !== TRUE) {
              echo "<br>Error inserting data into fossil database: " . $conn->error . ". Ignoring this row and continuing.<br>";
            } else {
              echo "<br>Inserted genera <b>$genera</b> into database.<br>";
            }
          }
          echo "<br>Done parsing.<br>";
          echo "<br>These are the stages we could not convert:<br>";
          foreach($unrecognizedStages as $stage => $value) {
            echo "$value<br>";
          }
          var_dump($unrecognizedStages);
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
          } else if (!empty($missingInDB)) {
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

function standardizeGeologicalStage($stage, $geologicalStages) {
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
?>