<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "myDB";
$output = '';
$columnNames = array();

global $conn;
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
} else {
  //echo '<pre>'.'successfully linked to Database'.'</pre>';
}

$query = "SHOW COLUMNS FROM fossil";
$result = $conn->query($query);

if ($result) {
  while ($row = $result->fetch_assoc()) {
    if ($row['Field'] == 'amount_of_extra_columns' || $row['Field'] == 'ID') {
      $amountOfColumns = (int)$row['Default'];
      continue;
    }
    $allColumnNames[] = $row['Field'];
  }

  if ($amountOfColumns > 0) {
    $excelColumnNames = array_slice($allColumnNames, 0, -(int)$amountOfColumns);
  }
  $excelColumnNamesWithIndexes = [];

  foreach ($excelColumnNames as $index => $columnName) {
    $excelColumnNamesWithIndexes[$columnName] = $index;
  }
}
