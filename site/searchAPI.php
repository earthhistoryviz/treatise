<?php

include_once("SqlConnection.php");
$rawSearch = $_GET['searchquery'];
$classfilter = $_GET['classfilter'];
$orderfilter = $_GET['orderfilter'];
$geographyfilter = $_GET['geographyfilter'];
$stagefilter = $_GET['stagefilter'];
$agefilterstart = $_GET['agefilterstart'];
$agefilterend = $_GET['agefilterend'];
$periodfilter = $_GET['periodfilter'];
$genusOnly = $_GET['genusOnly'];
$addSynonyms = $_GET['addSynonyms'];

if (!isset($_GET['agefilterend']) || $agefilterend == "" || $agefilterstart < $agefilterend) {
    $agefilterend = $agefilterstart;
}
if (!$geographyfilter || $geographyfilter == "All") {
    $geographyfilter = "";
}
if (!$stagefilter || $stagefilter == "All") {
    $stagefilter = "";
}
if (!$classfilter || $classfilter == "All") {
    $classfilter = "";
}
if (!$orderfilter || $orderfilter == "All") {
    $orderfilter = "";
}

$searchquery = '%' . $rawSearch . '%';
$geographyfilter = '%' . $geographyfilter . '%';
$stagefilter = '%' . $stagefilter . '%';
$classfilter = '%' . $classfilter . '%';
$orderfilter = '%' . $orderfilter . '%';

// POTENTIAL OPTIMIZATION: Instead of selecting all columns, we could select only the columns we need and echo early
// allColumnNames comes from SqlConnection.php, it is an array of all the column names in the fossil table
// including columns manually defined in create_db.php and the excel file
// we use it in searchAPI.php to define all columns that will be returned in the JSON response
if ($genusOnly == "true") {
    $allColumnNames = ["Genus"];
}

if ($addSynonyms == "true") {
    $allColumnNames = array_merge($allColumnNames, ["Synonyms"]);
}

$sql = "SELECT * FROM fossil WHERE Genus LIKE ? AND (geography LIKE ? OR geography IS NULL) AND beginning_stage LIKE ?";
if ($classfilter == "%%") {
    $sql .= " AND (Class LIKE ? OR Class IS NULL)";
} else {
    $sql .= " AND Class LIKE ?";
}
if ($orderfilter == "%%") {
    $sql .= " AND (`Order` LIKE ? OR `Order` IS NULL)";
} else {
    $sql .= " AND `Order` LIKE ?";
}
$params = ["sssss", &$searchquery, &$geographyfilter, &$stagefilter, &$classfilter, &$orderfilter];

if ($agefilterstart != "") {
    $sql .= " AND NOT (beginning_date < ? OR ending_date > ?) "
         . "AND beginning_date != '' "
         . "AND ending_date != '' ";
    $params[0] .= "dd";
    $agefilterstart_float = (float)$agefilterstart;
    $agefilterend_float = (float)$agefilterend;
    $params[] = &$agefilterend_float;
    $params[] = &$agefilterstart_float;
}

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

call_user_func_array([$stmt, 'bind_param'], $params);

if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();

$isSynonym = false;
if (!empty($rawSearch) && ($result == false || mysqli_num_rows($result) == 0)) {
    $sql = preg_replace('/SELECT \* FROM fossil WHERE Genus LIKE/', 'SELECT * FROM fossil WHERE Synonyms LIKE', $sql);

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    call_user_func_array([$stmt, 'bind_param'], $params);
    if (!$stmt->execute()) {
        die("Error executing query: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $isSynonym = true;
}

$arr = [];
while ($row = $result->fetch_assoc()) {
    $rowData = [];
    foreach ($allColumnNames as $columnName) {
        $rowData[$columnName] = $row[$columnName];
    }
    $genus = $row["Genus"];
    if (empty($genus)) {
        continue;
    }
    $arr[$genus] = $rowData;
}
$conn->close();
header("Content-Type: application/json");
echo json_encode([
    "data" => $arr,
    "isSynonym" => $isSynonym
]);
