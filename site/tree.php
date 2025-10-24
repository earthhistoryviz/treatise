<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fossil Search</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" type="text/css" href="style.css"/>
  <style>
    .highlight {
      position: absolute;
      border: 4px solid gold;
      pointer-events: none;
      display: none;
    }
    .map-container {
      position: relative;
      display: inline-block;
    }
  </style>
</head>
<body>
  <div class="main-container text-center">
    <?php
    session_start();
    $auth = $_SESSION["loggedIn"];
    include_once("navBar.php");
    ?>

    <div class="map-container">
      <img src="treatise_tree_10_24_2025.png" alt="" width="1302" height="1642" usemap="#Map" id="treeImage"/>
      <div id="hoverBox" class="highlight"></div>
    </div>

    <map name="Map">
      <area shape="rect" coords="848,104,1017,140" href="https://charophyte.treatise.geolex.org/" data-coords="848,104,1017,140"> 
      <area shape="rect" coords="445,455,937,492" href="https://porifera.treatise.geolex.org/index.php" data-coords="445,455,937,492">
      <!-- bryozoa -->
      <area shape="rect" coords="443,594,577,633" href="https://treatise.geolex.org/" data-coords="443,594,577,633">
      <area shape="rect" coords="447,665,622,704" href="https://brachiopod.treatise.geolex.org/" data-coords="447,665,622,704">
      <area shape="rect" coords="852,868,1024,909" href="https://ammonoid.treatise.geolex.org/" data-coords="852,868,1024,909">
      <!-- bivalves -->
      <area shape="rect" coords="854,944,980,981" href="https://treatise.geolex.org/" data-coords="854,944,980,981">
      <area shape="rect" coords="850,1034,1010,1071" href="https://trilobite.treatise.geolex.org/" data-coords="850,1034,1010,1071">
      <area shape="rect" coords="448,1365,661,1403" href="https://echinoderm.treatise.geolex.org/" data-coords="448,1365,661,1403">
      <area shape="rect" coords="448,1498,621,1537" href="https://graptolite.treatise.geolex.org/" data-coords="448,1498,621,1537">
      <area shape="rect" coords="849,1570,1001,1608" href="https://conodonts.treatise.geolex.org/" data-coords="849,1570,1001,1608">
      <area shape="rect" coords="31,804,197,842" href="https://treatise.geolex.org/" data-coords="31,804,197,842">
    </map>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const hoverBox = document.getElementById("hoverBox");
        const areas = document.querySelectorAll("area");

        areas.forEach(area => {
          area.addEventListener("mouseenter", function () {
            const coords = this.getAttribute("data-coords").split(",");
            hoverBox.style.left = `${coords[0]}px`;
            hoverBox.style.top = `${coords[1]}px`;
            hoverBox.style.width = `${coords[2] - coords[0]}px`;
            hoverBox.style.height = `${coords[3] - coords[1]}px`;
            hoverBox.style.display = "block";
          });

          area.addEventListener("mouseleave", function () {
            hoverBox.style.display = "none";
          });
        });
      });
    </script>
  </div>
  <br><br>
</body>
</html>
