
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users</title>
  <link rel="stylesheet" type="text/css" href="style.css"/>
</head>
<body>
  <?php
    include_once("adminDash.php");
  ?>
  <table style = margin-right:10px;>
    <tr>
      <th>UserName</th>
      <th>Admin</th>
    </tr>
    <?php
      include_once("SqlConnection.php");
  $sql = "SELECT * FROM user_info ";
  $result = mysqli_query($conn, $sql);
  while($row = mysqli_fetch_array($result)) {
      $user = $row['uname'];
      $adm = $row['admin'];
      echo "<tr>";
      echo"               ";
      echo "<td>" . $user ."</td>";
      echo "               ";
      echo "<td>" . $adm . "</td>";
      echo "</tr>";

  }
  ?>
  </table>
  <button onclick = "window.location.href = '/Signup.php'">Add a user </button>
</body>
</html>
