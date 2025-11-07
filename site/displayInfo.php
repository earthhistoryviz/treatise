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
  $fossilData = json_decode($raw, true)["data"][urldecode($genera)];

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
          <h2 class="card-title editable-text" id="Genus"><?php echo htmlspecialchars(urldecode($genera));?></h5>
          <div class="card-body">
            <h2 class="small-header">Classification</h2>
            <div id="Phylum" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Phylum: &nbsp;</b>
              <div id="Phylum" class="editable-text" 
                   data-taxonomy-level="Phylum" 
                   data-taxonomy-name="<?= htmlspecialchars($fossilData["Phylum"]) ?>">
                <?= htmlspecialchars(eliminateParagraphs($fossilData["Phylum"])) ?>
              </div>
            </div>
            <?php if (!empty($fossilData["Subphylum"]) && $fossilData["Subphylum"] !== 'None'): ?>
            <div id="Subphylum" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Subphylum: &nbsp;</b>
              <div id="Subphylum" class="editable-text"
                  data-taxonomy-level="Subphylum"
                  data-taxonomy-name="<?= htmlspecialchars($fossilData["Subphylum"]) ?>">
                <?= htmlspecialchars(eliminateParagraphs($fossilData["Subphylum"])) ?>
              </div>
            </div>
            <?php endif; ?>
            <div id="Class" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Class: &nbsp;</b>
              <div id="Class" class="editable-text"
                   data-taxonomy-level="Class"
                   data-taxonomy-name="<?= htmlspecialchars($fossilData["Class"]) ?>">
                <?= htmlspecialchars(eliminateParagraphs($fossilData["Class"])) ?>
              </div>
            </div>
            <?php if (!empty($fossilData["Subclass"]) && $fossilData["Subclass"] !== 'None'): ?>
            <div id="Subclass" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Subclass: &nbsp;</b>
              <div id="Subclass" class="editable-text"
                  data-taxonomy-level="Subclass"
                  data-taxonomy-name="<?= htmlspecialchars($fossilData["Subclass"]) ?>">
                <?= htmlspecialchars(eliminateParagraphs($fossilData["Subclass"])) ?>
              </div>
            </div>
            <?php endif; ?>
            <div id="Order" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Order: &nbsp;</b>
              <div id="Order" class="editable-text"
                   data-taxonomy-level="Order"
                   data-taxonomy-name="<?= htmlspecialchars($fossilData["Order"]) ?>">
                <?= htmlspecialchars(eliminateParagraphs($fossilData["Order"])) ?>
              </div>
            </div>
            <?php if (!empty($fossilData["Suborder"]) && $fossilData["Suborder"] !== 'None'): ?>
            <div id="Suborder" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Suborder: &nbsp;</b>
              <div id="Suborder" class="editable-text"
                  data-taxonomy-level="Suborder"
                  data-taxonomy-name="<?= htmlspecialchars($fossilData["Suborder"]) ?>">
                <?= htmlspecialchars(eliminateParagraphs($fossilData["Suborder"])) ?>
              </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($fossilData["Infraorder"]) && $fossilData["Infraorder"] !== 'None'): ?>
            <div id="Infraorder" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Infraorder: &nbsp;</b>
              <div id="Infraorder" class="editable-text"
                  data-taxonomy-level="Infraorder"
                  data-taxonomy-name="<?= htmlspecialchars($fossilData["Infraorder"]) ?>">
                <?= htmlspecialchars(eliminateParagraphs($fossilData["Infraorder"])) ?>
              </div>
            </div>
            <?php endif; ?>
            <div id="Superfamily" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Superfamily: &nbsp;</b>
              <div id="Superfamily" class="editable-text"
                   data-taxonomy-level="Superfamily"
                   data-taxonomy-name="<?= htmlspecialchars($fossilData["Superfamily"]) ?>">
                <?= htmlspecialchars(eliminateParagraphs($fossilData["Superfamily"])) ?>
              </div>
            </div>
            <div id="Family" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Family: &nbsp;</b>
              <div id="Family" class="editable-text"
                   data-taxonomy-level="Family"
                   data-taxonomy-name="<?= htmlspecialchars($fossilData["Family"]) ?>">
                <?= htmlspecialchars(eliminateParagraphs($fossilData["Family"])) ?>
              </div>
            </div>
            <?php if (!empty($fossilData["Subfamily"]) && $fossilData["Subfamily"] !== 'None'): ?>
            <div id="Subfamily" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Subfamily: &nbsp;</b>
              <div id="Subfamily" class="editable-text"
                  data-taxonomy-level="Subfamily"
                  data-taxonomy-name="<?= htmlspecialchars($fossilData["Subfamily"]) ?>">
                <?= htmlspecialchars(eliminateParagraphs($fossilData["Subfamily"])) ?>
              </div>
            </div>
            <?php endif; ?>
            <div id="Genus" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Formal Genus Name and Reference: &nbsp;</b>
              <div id="Genus" class="editable-text">
                <?= eliminateParagraphs($fossilData["Formal_genus_name_and_reference"]) ?>
              </div>
            </div>
            <div id="Type_species" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Type Species: &nbsp;</b>
              <div id="Type_species" class="editable-text">
                <?= eliminateParagraphs($fossilData["Type_species"]) ?>
              </div>
              <br><br>
            </div>
            <h2 class="small-header">Images</h2>
            <?php
            if ($auth) { ?>
              <form id="imageUploadForm" action="uploadImage.php" method="post" enctype="multipart/form-data" style="display: none;">
                <input type="file" name="image" class="fileInput" id="fileInput<?php echo $uniqueId; ?>" accept="image/*">
                <input type="hidden" name="genera" value="<?php echo htmlspecialchars($genera); ?>">
                <input type="hidden" name="type" value="Figure_Caption">
                <input type="hidden" name="redirect" value="true">
                <button type="submit" class="btn btn-success">Upload Fossil Image</button>
              </form> <?php
            }?>
            <b class="green-text">(Click to enlarge in a new window)</b>
            <div id="Figure_Image" class="horiz">
              <div id="Figure_Image"> 
                <?= displayImage("Figure_Caption", $genera)?>
              </div>
            </div>
            <div id="Figure_captions" class="horiz" style="text-align: left;">
                <div id="Figure_captions" class="editable-text">
                  <?= eliminateParagraphs($fossilData["Figure_captions"]) ?>
                </div>
            </div>
            <br><br>
            <h2 class="small-header">Synonyms</h2>
            <div id="Synonyms" class="horiz" style="text-align: left;">
              <div id="Synonyms" class="editable-text">
                <?= eliminateParagraphs($fossilData["Synonyms"]) ?>
              </div>
            </div>
            <br><br>
            <h2 class="small-header">Geographic Distribution</h2>
            <div id="Geography" class="horiz" style="text-align: left;">
              <div id="Geography" class="editable-text">
                <?= eliminateParagraphs($fossilData["Geography"]) ?>
              </div>
              <br><br>
            </div>
            <h2 class="small-header">Age Range</h2>
            <div id="beginning_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Beginning Stage in Treatise Usage: &nbsp;</b>
              <div id="beginning_stage" class="editable-text">
                <?= eliminateParagraphs($fossilData["Beginning_age"]) ?>
              </div>
            </div>
            <div id="beginning_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Beginning International Stage: &nbsp;</b>
              <div id="beginning_stage" class="editable-text">
                <?= eliminateParagraphs($fossilData["beginning_stage"]) ?>
              </div>
            </div>
            <div id="fraction_up_beginning_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Fraction Up In Beginning Stage: &nbsp;</b>
              <div id="fraction_up_beginning_stage" class="editable-text">
                <?= eliminateParagraphs($fossilData["fraction_up_beginning_stage"]) ?>
              </div>
            </div>
            <div id="beginning_date" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Beginning Date: &nbsp;</b>
              <div id="beginning_date" class="editable-text">
                <?= eliminateParagraphs($fossilData["beginning_date"]) ?>
              </div>
            </div>
            <div id="beginning_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Ending Stage in Treatise Usage: &nbsp;</b>
              <div id="beginning_stage" class="editable-text">
                <?= eliminateParagraphs($fossilData["Ending_age"]) ?>
              </div>
            </div>
            <div id="ending_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Ending International Stage: &nbsp;</b>
              <div id="ending_stage" class="editable-text">
                <?= eliminateParagraphs($fossilData["ending_stage"]) ?>
              </div>
            </div>
            <div id="fraction_up_ending_stage" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Fraction Up In Ending Stage: &nbsp;</b>
              <div id="fraction_up_ending_stage" class="editable-text">
                <?= eliminateParagraphs($fossilData["fraction_up_ending_stage"]) ?>
              </div>
            </div>
            <div id="ending_date" class="horiz" style="text-align: left;">
              <b>&nbsp;&nbsp;&nbsp;&nbsp;Ending Date: &nbsp;</b>
              <div id="ending_date" class="editable-text">
                <?= eliminateParagraphs($fossilData["ending_date"]) ?>
              </div>
              <br><br>
            </div>
            <h2 class="small-header">Description</h2>
            <div id="Distinguishing_characteristics" class="horiz" style="text-align: left;">
              <div id="Distinguishing_characteristics" class="editable-text">
                <?= eliminateParagraphs($fossilData["Distinguishing_characteristics"]) ?>
              </div>
              <br><br>
            </div>
            <br><br>
            <h2 class="small-header">References</h2>
            <div id="Reference_information" class="horiz" style="text-align: left;">
              <div id="Reference_information" class="editable-text">
                <?= eliminateParagraphs($fossilData["Reference_information"]) ?>
              </div>
            </div>
            <br><br>
            <h2 class="small-header">Museum or Author Information</h2>
            <div id="Museum_or_author_information" class="horiz" style="text-align: left;">
              <div id="Museum_or_author_information" class="editable-text">
                <?= eliminateParagraphs($fossilData["Museum_or_author_information"]) ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // For all elements with data-taxonomy-level 
        const taxonomyElements = document.querySelectorAll('[data-taxonomy-level]');
        
        // Substitute all plain text with links
        taxonomyElements.forEach(function(element) {
          const level = element.getAttribute('data-taxonomy-level');
          const name = element.getAttribute('data-taxonomy-name');
          
          // Skip empty ones
          if (!name || name.trim() === '') {
            return;
          }
          
          // Get PDF link
          fetch(`getTaxonomyLink.php?level=${encodeURIComponent(level)}&name=${encodeURIComponent(name)}`)
            .then(response => response.json())
            .then(data => {
              if (data.link) {
                // Substitute plain text with links
                const currentText = element.textContent;
                element.innerHTML = `<a href="${data.link}" target="_blank">${currentText}</a>`;
              }
              // Link not found. Remain plain text.
            })
            .catch(error => {
              console.error('Error fetching taxonomy link:', error);
            });
        });
      });
    </script>
    <?php
  } ?>
</div> 
<?php

function eliminateParagraphs($str) {
    while (preg_match("/<p>.*<\/p>/", $str)) {
        $str = preg_replace("/<p>(.*)<\/p>/", "\\1", $str);
    }
    return $str;
}

function displayImage($type, $genera) {
  global $auth;

  $directoryPath = "/app/uploads/$genera/$type";
  $imagePattern = $directoryPath . "/*.{jpg,png,gif}";
  $images = glob($imagePattern, GLOB_BRACE);
  if (!empty($images)) {
    echo '<br>';
    foreach ($images as $imagePath) {
      $imageName = basename($imagePath);
      $imageUrl = "./uploads/" . htmlspecialchars($genera) . '/' . htmlspecialchars($type) . '/' . htmlspecialchars($imageName);
      echo '<a href="' . $imageUrl . '" target="_blank"><img style="max-width: 200px; max-height: 200px;" src="' . $imageUrl . '" alt="Fossil Image"></a>';
      if ($auth) {
        echo '<button class="btn btn-danger delete-image" style="display: none;" data-id="' . htmlspecialchars($imageName) . '">Delete Image</button>';
      }
    }
  }
}

  if ($auth && $fossilData) { ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const editButton = document.querySelector('.edit-buttons .btn-primary');
        const saveButton = document.querySelector('.edit-buttons .btn-success');
        const deleteButton = document.querySelector('.edit-buttons .btn-danger');
        const imageUploadForm = document.getElementById('imageUploadForm');
        const textElements = document.querySelectorAll('.editable-text');
        const params = new URLSearchParams(window.location.search);
        const genera = params.get('genera');
        const deleteImageButtons = document.querySelectorAll('.delete-image');
        editButton.addEventListener('click', function() {
          saveButton.removeAttribute('disabled');
          textElements.forEach(function(element) {
            element.setAttribute('contenteditable', 'true');
          });
          if (textElements.length > 0) {
            textElements[0].focus();
          }  
          if (imageUploadForm) {
            imageUploadForm.style.display = 'block';
          }
          deleteImageButtons.forEach(function(button) {
            button.style.display = 'block';
            button.addEventListener('click', function() {
              if (confirm('Are you sure you want to delete this image?')) {
                const imageName = button.getAttribute('data-id');
                const formData = new FormData();
                formData.append('imageName', imageName);
                formData.append('genera', genera);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'deleteImage.php', true);
                xhr.send(formData);
                xhr.onload = function() {
                  if (xhr.status === 200) {
                    console.log("Image deleted successfully");
                    const imageElement = button.previousElementSibling;
                    imageElement.parentNode.removeChild(imageElement);
                    button.parentNode.removeChild(button);
                    button.style.display = 'none';
                  } else {
                    console.error("Error deleting image");
                  }
                };
              }
            });
          });
        });
        saveButton.addEventListener('click', function() {
          const editedData = [];
          editedData.unshift({
            columnName: 'Genus',
            content: genera
          });
          textElements.forEach(function(element) {
            editedData.push({
              columnName: element.getAttribute('id'),
              content: element.innerText
            });
            element.setAttribute('contenteditable', 'false');
          });
          deleteImageButtons.forEach(function(button) {
            button.style.display = 'none';
          });
          imageUploadForm.style.display = 'none';
          const xhr = new XMLHttpRequest();
          xhr.open('POST', 'saveNewText.php', true);
          xhr.setRequestHeader('Content-Type', 'application/json');
          xhr.send(JSON.stringify(editedData));
          xhr.onload = function() {
            if (xhr.status === 200) {
              console.log("Response from server: \n" + xhr.responseText);
              //Nyah
              const genusElement = document.getElementById('Genus');
              if (genusElement) {
                const newGenus = genusElement.innerText.trim();
                if (newGenus !== genera) {
                  window.location.href = "displayInfo.php?genera=" + encodeURIComponent(newGenus);
                }
              }
            } else {
              console.error("Error saving data");
              console.error("Response from server: " + xhr.responseText);
              alert("Error saving data. Please refresh the page and try again.");
            }
          };
        });
        deleteButton.addEventListener('click', function() {
          const userConfirmed = confirm("Are you sure you want to delete this entry?");
          if (userConfirmed) {
            const xhr = new XMLHttpRequest();
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
