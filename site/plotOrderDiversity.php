<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diversity Curve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" type="text/css" href="style.css"/>
</head>
<body>
  <div class="main-container">
    <?php
      session_start();
      $auth = $_SESSION["loggedIn"];
      include_once("navBar.php");
      include_once("SqlConnection.php"); 

      // Fetching orders from database
      $allOrders = [];
      $sqlOrders = "SELECT DISTINCT `Order` FROM fossil";
      $resultOrders = $conn->query($sqlOrders);
      while ($row = $resultOrders->fetch_assoc()) {
          $allOrders[] = $row["Order"];
      }
       $conn->close();

       // Get selected orders from the form submission
      $selectedOrders = isset($_GET['orders']) ? $_GET['orders'] : [];
    ?>

    <div class="container mt-5">
      <h1 style="text-align: center">Plot Order Diversity</h1>
      <form method="GET">
        <div class="d-flex flex-column justify-content-center align-items-center">
            <div class="mb-3 mt-3 d-flex flex-row align-items-center justify-content-center gap-3">
                <label for="orderSelect" class="form-label">Select Fossil Orders:</label>
                <select class="form-select" id="orderSelect" name="orders[]" multiple size="6" style="width: 200px;">
                    <option value="All" <?php echo in_array("All", $selectedOrders) ? 'selected' : ''; ?>>All</option>
                    <?php foreach ($allOrders as $order): ?>
                        <option value="<?php echo htmlspecialchars($order); ?>" <?php echo in_array($order, $selectedOrders) ? 'selected' : ''; ?>><?php echo htmlspecialchars($order); ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Hold down the Ctrl (Windows) or Command (Mac) button to select multiple options.</small>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 150px;">Submit</button>
        </div>
    </form>
    </div>
    
    <?php
    // fetch genus from database

    // Key time bins in 5 years
    // how many fossils for class/order
    /*
    time
    array[order1:genus, order2:genus, order3:genus]
    */
    $timeBins = [];


    ?>

  </div> 
</body>
</html>
