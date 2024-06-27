<?php
include_once("SqlConnection.php");
$data = json_decode(file_get_contents('php://input'), true);
$genus = array_shift($data)['content'];

foreach ($data as $item) {
  $columnName = $item['columnName'];
  $content = mysqli_real_escape_string($conn, $item['content']);
  if (in_array($columnName, $allColumnNames)) {
    $query = "UPDATE fossil SET `$columnName` = ? WHERE Genus = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, "ss", $content, $genus);
      $result = mysqli_stmt_execute($stmt);

      if ($result) {
        echo "Record with columnName $columnName updated successfully.\n";
      } else {
        echo "Error updating record with columnName $columnName: " . mysqli_error($conn) . "\n";
      }

      mysqli_stmt_close($stmt);
    } else {
      echo "Error preparing statement: " . mysqli_error($conn) . "\n";
    }
  } else {
    echo "Invalid column name: $columnName\n";
  }
}

mysqli_close($conn);
echo "Data updated successfully";
?>
