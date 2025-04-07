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
  <div class="main-container">
    <?php
    session_start();
    $auth = $_SESSION["loggedIn"];
    include_once("navBar.php");
    ?>

    <div class="map-container">
      <img src="treeIMG.png" alt="" width="1078" height="881" usemap="#Map" id="treeImage"/>
      <div id="hoverBox" class="highlight"></div>
    </div>

    <map name="Map">
      <area shape="rect" coords="42,297,181,331" href="https://treatise.geolex.org/" data-coords="42,297,181,331">
      <area shape="rect" coords="852,152,991,184" href="https://porifera.treatise.geolex.org/index.php" data-coords="852,152,991,184">
      <area shape="rect" coords="420,424,599,455" href="https://brachiopod.treatise.geolex.org/" data-coords="420,424,599,455">
      <area shape="rect" coords="850,425,1016,457" href="https://brachiopod.treatise.geolex.org/" data-coords="850,425,1016,457">
      <area shape="rect" coords="846,489,1013,521" href="https://ammonoid.treatise.geolex.org/" data-coords="846,489,1013,521">
      <area shape="rect" coords="841,631,1016,665" href="https://echinoderm.treatise.geolex.org/" data-coords="841,631,1016,665">
      <area shape="rect" coords="585,702,763,735" href="https://graptolite.treatise.geolex.org/" data-coords="585,702,763,735">
      <area shape="rect" coords="839,702,1016,732" href="https://graptolite.treatise.geolex.org/" data-coords="839,702,1016,732">
      <area shape="rect" coords="839,841,1022,873" href="https://charophyte.treatise.geolex.org/" data-coords="839,841,1022,873">
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
