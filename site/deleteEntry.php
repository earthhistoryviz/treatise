<?php
include_once("SqlConnection.php");
$genus = file_get_contents("php://input");
$query = "DELETE FROM fossil WHERE Genus = ?";
$stmt = mysqli_prepare($conn, $query); 
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "s", $genus);
  $result = mysqli_stmt_execute($stmt);
  if ($result) {
    http_response_code(200);
  } else {
    http_response_code(500);
    echo "Error deleting $genus: " . mysqli_error($conn) . "\n";
  }

  mysqli_stmt_close($stmt);
} else {
  http_response_code(500);
  echo "Error preparing statement: " . mysqli_error($conn) . "\n";
}
?>
