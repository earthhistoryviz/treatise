<?php
if ($_SESSION["loggedIn"]) {
    include("adminDash.php");
} else { ?>
    <div class="topnav">
      <div class="country-logo">
        <img src="/noun_Earth_2199992.svg" alt="Logo">
        <h5 class="region-name">Treatise</h5>
      </div>
      <a href="index.php">Home</a>
      <a href="plotOrderDiversity.php">Plot Diversity Curves</a>
      <a href="tree.php"> Tree of Life</a>
      <a href="aboutPageTreatise.php">About</a>
      <a style="margin-left: auto; padding-right: 10px;" href="/login.php">Admin Login</a>
    </div>
  <?php
} ?>
