<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database Operations</title>
  <link rel="stylesheet" type="text/css" href="style.css"/>
  <script>
    function confirmAction(action) {
      return confirm('Are you sure you want to ' + action + '? This action is irreversible.');
    }
  </script>
</head>
<body>
  <?php
    session_start();
    include_once("adminDash.php");
  ?>
  <h2>Database Operations</h2>
  <br>
  <h5>This option will clear all the fossil data in the database. All information about fossils will be lost. Will not delete the geography table.</h5>
  <form action="" method="post">
    <button type="submit" name="clearDatabase" class="btn btn-warning" onclick="return confirmAction('clear the database');">Clear Database</button>
  </form>
  <br>
  <h5>This option will drop the fossil table losing all fossil data as well as table data. To reformat the database you need to run create_db.php from within the container.</h5>
  <form action="" method="post">
    <button type="submit" name="dropTable" class="btn btn-danger" onclick="return confirmAction('drop the table');">Drop Table</button>
  </form>
</body>
</html>

<?php
$auth = $_SESSION["loggedIn"]; 
if ($_SERVER["REQUEST_METHOD"] == "POST" && $auth) {
  include_once("SqlConnection.php");
  if (isset($_POST['clearDatabase'])) {
    $sqlClear = "DELETE FROM fossil";
    if ($conn->query($sqlClear) !== TRUE) {
      echo "<script>alert('Error clearing database: $conn->error');</script>";
    } else {
      echo "<script>alert('Database cleared!'); window.location.href = 'index.php';</script>";
    }
  }

  if (isset($_POST['dropTable'])) {
    $sqlDrop = "DROP TABLE fossil";
    if ($conn->query($sqlDrop) !== TRUE) {
      echo "<script>alert('Error dropping fossil table: $conn->error');</script>";
    } else {
      echo "<script>alert('Table dropped!'); window.location.href = 'index.php'</script>";
    }
  }
}
?>
