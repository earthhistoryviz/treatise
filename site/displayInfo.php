<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fossil Information</title>
  <link rel="stylesheet" type="text/css" href="style.css"/>
  <style>
    .small-header{
      font-size: 1.4em;
      font-weight: 600;
    }

    .green-text{
      color: green;
      font-weight: 500;
    }

    .blue-text {
      color: blue;
    }

    .left-align{
      text-align: left;
    }

    .horiz {
      display: flex;
      flex-direction: row;
    }

    </style>
</head>
<body>

  <div class="main-container">
  <?php
  session_start();
  $auth = $_SESSION["loggedIn"];
  include_once("generalSearchBar.php");
  $genera = $_GET["genera"];
  $url = "http://localhost/searchAPI.php?searchquery=".urlencode($genera);
  $raw = file_get_contents($url);
  $fossilData = json_decode($raw, true)[urldecode($genera)];

  if ($fossilData) { ?>
    <div class="formation-container mt-5">
      <?php
      if ($auth) { ?>
        <div class="edit-buttons">
        <button class="btn btn-primary mr-2">Edit Entry</button>
        <button class="btn btn-success mr-2" disabled>Save Entry</button>
        <button class="btn btn-danger">Delete Entry</button>
        </div> <?php
      } ?>
      <div class="card">
        <div class="card-body">
          <h2 class="card-title"><?php echo htmlspecialchars(urldecode($genera));?></h5>
          <?php
          displayImage("Fossil_Image", $genera);
      if ($auth) { ?>
            <form id="imageUploadForm" action="uploadImage.php" method="post" enctype="multipart/form-data">
            <input type="file" name="image" class="fileInput" id="fileInput<?php echo $uniqueId; ?>" accept="image/*" style="display: none;">
            <input type="hidden" name="genera" value="<?php echo htmlspecialchars($genera); ?>">
            <input type="hidden" name="type" value="Fossil_Image">
            <button type="button" onclick="document.getElementById('fileInput<?php echo $uniqueId; ?>').click();">Choose File</button>
            <button type="submit" class="btn btn-success">Upload Fossil Image</button>
            </form> <?php
      }
      if ($auth) {
          $uniqueId = htmlspecialchars($key); ?>
            <form id="formImageUpload<?php echo $uniqueId; ?>" action="uploadImage.php" method="post" enctype="multipart/form-data">
              <input type="file" name="image" class="fileInput" id="fileInput<?php echo $uniqueId; ?>" accept="image/*" style="display: none;">
              <input type="hidden" name="genera" value="<?php echo htmlspecialchars($genera); ?>">
              <input type="hidden" name="type" value="<?php echo htmlspecialchars($key); ?>">
              <input type="hidden" name="redirect" value="true">
              <button type="button" onclick="document.getElementById('fileInput<?php echo $uniqueId; ?>').click();">Choose File</button>
              <button type="submit" class="btn btn-success">Upload <?= $replaced_key ?> Image</button>
            </form> <?php
      } ?>
          <div class="card-body">
            <h2 class="small-header">Classification</h2>
            <div id="Phylum" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Phylum: &nbsp;</b>
              <div id="Phylum">
                <?= eliminateParagraphs($fossilData["Phylum"]) ?>
              </div>
            </div>
            <div id="Class" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Class: &nbsp;</b>
              <div id="Class">
                <?= eliminateParagraphs($fossilData["Class"]) ?>
              </div>
            </div>
            <div id="Order" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Order: &nbsp;</b>
              <div id="Order">
                <?= eliminateParagraphs($fossilData["Order"]) ?>
              </div>
            </div>
            <div id="Superfamily" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Superfamily: &nbsp;</b>
              <div id="Superfamily">
                <?= eliminateParagraphs($fossilData["Superfamily"]) ?>
              </div>
            </div>
            <div id="Family" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Family: &nbsp;</b>
              <div id="Family">
                <?= eliminateParagraphs($fossilData["Family"]) ?>
              </div>
            </div>
            <div id="Genus" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Genus: &nbsp;</b>
              <div id="Genus">
                <?= eliminateParagraphs($fossilData["Genus"]) ?>
              </div>
              <br><br>
            </div>
            <div id="Type_Species" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Type Species: &nbsp;</b>
              <div id="Type_Species">
                <?= eliminateParagraphs($fossilData["Type_species"]) ?>
              </div>
              <br><br>
            </div>
            <h2 class="small-header">Images</h2>
            <div id="Figure_Caption" class="horiz" style="text-align: left;">
              <div id="Figure_Caption">
                <?= displayImage("Figure_Caption", $genera)?>
              </div>
              <b class="green-text">(Click to enlarge in a new window)</b>
            </div>
            <div id="Figure_Caption" class="horiz" style="text-align: left;">
                <div id="Figure_Caption">
                  <?= eliminateParagraphs($fossilData["Figure_Caption"]) ?>
                </div>
            </div>
            <h2 class="small-header">Geographic Distribution</h2>
            <div id="Geography" class="horiz" style="text-align: left;">
              <div id="Geography">
                <?= eliminateParagraphs($fossilData["Geography"]) ?>
              </div>
              <br><br>
            </div>
            <h2 class="small-header">Age Range</h2>
            <div id="beginning_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Beginning Stage in Treatise Usage: &nbsp;</b>
              <div id="beginning_stage">
                <?= eliminateParagraphs($fossilData["First_Occurrence"]) ?>
              </div>
            </div>
            <div id="beginning_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Beginning International Stage: &nbsp;</b>
              <div id="beginning_stage">
                <?= eliminateParagraphs($fossilData["beginning_stage"]) ?>
              </div>
            </div>
            <div id="fraction_up_beginning_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Fraction Up In Beginning Stage: &nbsp;</b>
              <div id="fraction_up_beginning_stage">
                <?= eliminateParagraphs($fossilData["fraction_up_beginning_stage"]) ?>
              </div>
            </div>
            <div id="beginning_date" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Beginning Date: &nbsp;</b>
              <div id="beginning_date">
                <?= eliminateParagraphs($fossilData["beginning_date"]) ?>
              </div>
            </div>
            <div id="beginning_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Ending Stage in Treatise Usage: &nbsp;</b>
              <div id="beginning_stage">
                <?= eliminateParagraphs($fossilData["Last_Occurence"]) ?>
              </div>
            </div>
            <div id="ending_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Ending International Stage: &nbsp;</b>
              <div id="ending_stage">
                <?= eliminateParagraphs($fossilData["ending_stage"]) ?>
              </div>
            </div>
            <div id="fraction_up_ending_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Fraction Up In Ending Stage: &nbsp;</b>
              <div id="fraction_up_ending_stage">
                <?= eliminateParagraphs($fossilData["fraction_up_ending_stage"]) ?>
              </div>
            </div>
            <div id="ending_date" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Ending Date: &nbsp;</b>
              <div id="ending_date">
                <?= eliminateParagraphs($fossilData["ending_date"]) ?>
              </div>
              <br><br>
            </div>
            <h2 class="small-header">Description</h2>
            <div id="Diagnosis" class="horiz" style="text-align: left;">
              <div id="Diagnosis">
                <?= eliminateParagraphs($fossilData["Diagnosis"]) ?>
              </div>
              <br><br>
            </div>
            <h2 class="small-header">References</h2>
            <div id="Link_to_Treatise_Volume_Genus_Appears_in" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Link To Treatise Volume Genus Appears In: &nbsp;</b>
              <div id="Link_to_Treatise_Volume_Genus_Appears_in">
                <?= eliminateParagraphs($fossilData["Link_to_Treatise_Volume_Genus_Appears_in"]) ?>
              </div>
            </div>
            <div id="Volume_of_Treatise_Appears_in" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Volume Of Treatise Appears In: &nbsp;</b>
              <div id="Volume_of_Treatise_Appears_in">
                <?= eliminateParagraphs($fossilData["Volume_of_Treatise_Appears_in"]) ?>
              </div>
            </div>
            <div id="Page_in_Treatise_it_is_on" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Page in Treatise It Is On: &nbsp;</b>
              <div id="Page_in_Treatise_it_is_on">
                <?= eliminateParagraphs($fossilData["Page_in_Treatise_it_is_on"]) ?>
              </div>
            </div>
            <div id="Author" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Author: &nbsp;</b>
              <div id="Author">
                <?= eliminateParagraphs($fossilData["Author"]) ?>
              </div>
            </div>
            <div id="Author_Citation" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Author Citation: &nbsp;</b>
              <div id="Author_Citation">
                <?= eliminateParagraphs($fossilData["Author_Citation"]) ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div> <?php
  } ?>
</div> 
<?php

function eliminateParagraphs($str)
{
    while (preg_match("/<p>.*<\/p>/", $str)) {
        $str = preg_replace("/<p>(.*)<\/p>/", "\\1", $str);
    }
    return $str;
}

  function displayImage($type, $genera)
  {
      $directoryPath = "/app/uploads/$genera/$type";
      $imagePattern = $directoryPath . "/*.{jpg,png,gif}";
      $images = glob($imagePattern, GLOB_BRACE);
      if (!empty($images)) {
          echo '<br>';
          foreach ($images as $imagePath) {
              $imageName = basename($imagePath);
              $imageUrl = "./uploads/" . htmlspecialchars($genera) . '/' . htmlspecialchars($type) . '/' . htmlspecialchars($imageName);
              echo '<a href="' . $imageUrl . '" target="_blank"><img style="max-width: 200px; max-height: 200px;" src="' . $imageUrl . '" alt="Fossil Image"></a>';
          }
      }
  }



  if ($auth && $fossilData) { ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var editButton = document.querySelector('.edit-buttons .btn-primary');
        var saveButton = document.querySelector('.edit-buttons .btn-success');
        var deleteButton = document.querySelector('.edit-buttons .btn-danger');
        var addImageButtons = document.querySelectorAll('.fileInput');
        var textElements = document.querySelectorAll('.editable-text');
        var params = new URLSearchParams(window.location.search);
        var genera = params.get('genera');
        var deleteImageButton = document.getElementById('deleteImage');
        editButton.addEventListener('click', function() {
          saveButton.removeAttribute('disabled');
          textElements.forEach(function(element) {
          element.setAttribute('contenteditable', 'true');
          });
          if (textElements.length > 0) {
          textElements[0].focus();
          }  
          addImageButtons.forEach(function(button) {
          button.style.display = 'block';
          });
          if (deleteImageButton) {
          deleteImageButton.style.display = 'block';
          deleteImageButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this image?')) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'deleteImage.php', true);
            xhr.setRequestHeader('Content-Type', 'text/xml');
            xhr.send(genera);
            xhr.onload = function() {
              if (xhr.status === 200) {
              console.log("Image deleted successfully");
              var imageElement = document.getElementById('image');
              if (imageElement) {
                imageElement.remove();
              }
              } else {
              console.error("Error deleting image");
              }
            };
            }
          });
          }
        });
        saveButton.addEventListener('click', function() {
          var editedData = [];
          editedData.unshift({
            columnName: 'Genus',
            content: genera
          });
          textElements.forEach(function(element) {
            editedData.push({
              columnName: element.getAttribute('data-id'),
              content: element.innerText
            });
            element.setAttribute('contenteditable', 'false');
          });
          console.log(editedData);
          var xhr = new XMLHttpRequest();
          xhr.open('POST', 'saveNewText.php', true);
          xhr.setRequestHeader('Content-Type', 'application/json');
          xhr.send(JSON.stringify(editedData));
          xhr.onload = function() {
            if (xhr.status === 200) {
              console.log("Response from server: \n" + xhr.responseText);
              addImageButtons.forEach(function(button) {
                button.style.display = 'block';
              });
            } else {
              console.error("Error saving data");
            }
          };
        });
        deleteButton.addEventListener('click', function() {
          var userConfirmed = confirm("Are you sure you want to delete this entry?");
          if (userConfirmed) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'deleteEntry.php', true);
            xhr.setRequestHeader('Content-Type', 'text/xml');
            xhr.send(genera);
            xhr.onload = function() {
              if (xhr.status === 200) {
                window.location.href = 'index.php';
              } else {
                console.error("Error deleting: " + xhr.responseText);
              }
            };
          }
        });
      });
    </script> <?php
  } ?>
</body>
</html>
