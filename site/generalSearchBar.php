<?php
include_once("navBar.php");
include_once("TimescaleLib.php");
include_once("SqlConnection.php");
$stages = parseTimescale("./uploads/default_timescale.xlsx");
$periods = [];
foreach ($stages as $stage) {
    $period = $stage['period'];
    $stageName = $stage['stage'];
    $base = round($stage['base'], 2);
    $top = round($stage['top'], 2);
    if (!isset($periods[$period])) {
        $periods[$period] = [
            "stages" => [],
            "begDate" => PHP_INT_MIN,
            "endDate" => PHP_INT_MAX,
            "date_range" => ""
        ];
    }
    $periods[$period]["stages"][$stageName] = [
        "base" => $base,
        "top" => $top,
        "date_range" => " ({$base}-{$top})"
    ];

    if ($base > $periods[$period]["begDate"]) {
        $periods[$period]["begDate"] = $base;
    }
    if ($top < $periods[$period]["endDate"]) {
        $periods[$period]["endDate"] = $top;
    }
    $periods[$period]["date_range"] = " (" . round($periods[$period]["begDate"], 2) . "-" . round($periods[$period]["endDate"], 2) . ")";
}

$classesWithOrders = [];
$allOrders = [];
$sqlClassesOrders = "SELECT DISTINCT Class, `Order` FROM fossil WHERE `Order` IS NOT NULL AND `Order` != '' AND `Order` != 'None'";
$resultClassesOrders = $conn->query($sqlClassesOrders);
while ($row = $resultClassesOrders->fetch_assoc()) {
    $class = $row["Class"];
    $order = $row["Order"];
    if (!isset($classesWithOrders[$class])) {
        $classesWithOrders[$class] = [];
    }
    $classesWithOrders[$class][] = $order;
    if (!in_array($order, $allOrders)) {
        $allOrders[] = $order;
    }
}
sort($allOrders);

?>
<div class="container mt-5">
	<div class="row justify-content-center">
    	<div class="col-md-8">
		  <div class="title-section text-center mb-4">
        <h2>Welcome to the Treatise on Invertebrate Paleontology!</h2>
        <p>Please enter a genera name to retrieve more information.</p>
      </div>
			<form id='form' action="index.php" method="GET" class="form-inline">
  				<div class="searchbar-container d-flex align-items-center justify-content-center">
					<input
						id="searchbar"
						type="text"
						class="form-control mb-2 mr-sm-2"
						name="search"
						placeholder="Search Genus Name..."
						value="<?php if (isset($_GET['search'])) {
						    echo $_GET['search'];
						} ?>">

					<button id="submitbtn" value="filter" type="submit" class="btn btn-primary mb-2">Submit</button>
				</div>
				<div class ="filter-container d-flex align-items-center justify-content-center w-100">
					<h6 style="white-space: nowrap;">Search By:</h6>

					<div class="form-group mx-sm-3 mb-2">
						<select id="searchtype-select" class="form-select" name="searchtype" onchange="changeFilter()">
							<option value="Period" <?php echo (isset($_REQUEST['searchtype']) && $_REQUEST['searchtype'] == 'Period') ? 'selected' : ''; ?>>Period</option>
							<option value="Date" <?php echo (isset($_GET['searchtype']) && $_GET['searchtype'] == 'Date') ? 'selected' : ''; ?>>Date</option>
							<option value="Date Range" <?php echo (isset($_GET['searchtype']) && $_GET['searchtype'] == 'Date Range') ? 'selected' : ''; ?>>Date Range</option>
						</select>
					</div>
					
					<div class="form-group mx-sm-3 mb-2">
						<div id="selected-filter"></div>
					</div>
					
					<h6 style="white-space: nowrap;">and Class</h6>

					<div class="form-group mx-sm-3 mb-2 ml-0">
						<select id="classSearch" class="form-select" name="classSearch" onchange="setOrder()">
							<!-- The logic makes it so that if no option picked, or if all is chosen(looking at URL via $_GET), no filtering is done
							Otherwise the next set of lines after starts filtering everything except selected classtype -->
							<option value="All" <?php echo (!isset($_GET['classSearch']) || $_GET['classSearch'] == 'All') ? 'selected' : ''; ?>>All</option>
							<?php
						    if (isset($classesWithOrders)) {
						        foreach ($classesWithOrders as $class => $orders) {
						            $selected = (isset($_GET['classSearch']) && $_GET['classSearch'] == $class) ? 'selected' : '';
						            echo "<option value='$class' $selected>$class</option>";
						        }
						    }
?>
						</select>
					</div>

					<script>
						function setOrder() {
							var box = document.getElementById("classSearch");
							if (!box) {
								return;
							}
							var chosen = box.options[box.selectedIndex].value;
							var orderSearch = document.getElementById("orderSearch");
							orderSearch.disabled = false;
							if (chosen == "All") {
								var allOrdersHTML = "<option value='All'>All</option>";
								<?php
								foreach ($allOrders as $order) {
									$selected = (isset($_GET['orderSearch']) && $_GET['orderSearch'] == $order) ? 'selected' : '';
									echo "allOrdersHTML += \"<option value='$order' $selected>$order</option>\";";
								}
								?>
								orderSearch.innerHTML = allOrdersHTML;
							} else {
								orderSearch.disabled = false;
								<?php
    if (isset($classesWithOrders)) {
        $selectedAll = (isset($_GET['orderSearch']) && $_GET['orderSearch'] == 'All') ? 'selected' : '';
        echo "orderSearch.innerHTML = \"<option value='All' $selectedAll>All</option>\";";
        foreach ($classesWithOrders as $class => $orders) {
            echo "if (chosen == '$class') {";
            foreach ($orders as $order) {
                $selected = (isset($_GET['orderSearch']) && $_GET['orderSearch'] == $order) ? 'selected' : '';
                echo "orderSearch.innerHTML += \"<option value='$order' $selected>$order</option>\";";
            }
            echo "}";
        }
    }
?>
							}
						}
					</script>

					<h6 style="white-space: nowrap;">and Order</h6>

					<div class="form-group mx-sm-3 mb-2 ml-0">
						<select id="orderSearch" class="form-select" name="orderSearch">
						</select>
					</div>
				</div>
			</form>
		</div>
	</div>

	<!-- Form to toggle sorting -->
	<?php if (isset($_GET["search"])): ?>
		<div style="text-align: center; margin-top: 10px;">
			<form method="post">
				<input type="submit" name="sortAlphabetically" value="Sort Alphabetically">
				<input type="submit" name="sortChronologically" value="Sort Chronologically">
			</form>
		Note: The above sorting does not sort Class/Order, only fossils within them
		</div>
	<?php endif; ?>

	<div id="image-container" style="text-align: center; margin-top: 5px;">
		<img src="./logo.png" style="width: 300px;">
	</div>

	<script>
		function changeFilter() {
			var box = document.getElementById("searchtype-select");
			if (!box) {
				return;
			}
			var chosen = box.options[box.selectedIndex].value;
			var searchForm = document.getElementById("selected-filter");
			if (chosen == "Date Range") {
				var rangeHTML = 
				"Beginning Date: <input id='begDate' type='number' step='0.01' style='width: 90px' class='form-control' name='agefilterstart' min='0' value='<?php if (isset($_GET['agefilterstart'])) {
				    echo $_GET['agefilterstart'];
				} ?>'> \
				Ending Date: <input id='endDate' type='number' style='width: 90px' class='form-control' name='agefilterend' min='0' value='<?php if (isset($_GET['agefilterend'])) {
				    echo $_GET['agefilterend'];
				} ?>'> \
				<input id='selectPeriod' name='filterperiod' type='hidden' value='All'>";
				searchForm.innerHTML = rangeHTML;
			} else if (chosen == "Date") {
				var dateHTML = 
				"Enter Date: <input id='begDate' type='number' step='0.01' class='form-control' style='width: 90px' name='agefilterstart' min='0' value='<?php if (isset($_GET['agefilterstart'])) {
				    echo $_GET['agefilterstart'];
				} ?>'> \
				<input id='selectPeriod' name='filterperiod' type='hidden' value='All'>";
				searchForm.innerHTML = dateHTML;
			} else {
				var periodHTML = 
					`<div class="d-flex flex-row align-items-center gap-1 justify-content-center">
					<select id='selectPeriod' name='filterperiod' class="form-select" onchange='changePeriod()'>
						<option value='All' <?php echo (isset($_REQUEST['filterperiod']) && $_REQUEST['filterperiod'] == 'All') ? 'selected' : ''; ?>>All</option>
					<?php
				    foreach ($periods as $p => $d) {
				        if ($p) { ?>
							<option value='<?=$p?>' <?php echo (isset($_REQUEST['filterperiod']) && $_REQUEST['filterperiod'] == $p) ? 'selected' : ''; ?>><?=$p?> <?=$d["date_range"]?></option> <?php
				        }
				    } ?>
					</select>
					<h6 style="white-space: nowrap;" class="m-0">and Stage</h6>
					<select id='filterstage' name='filterstage' disabled class="form-select" onchange=stageToDate()>
						<option value='All'>--Select Period First--</option>
					</select>
					<input id='begDate' name='agefilterstart' type='hidden' value=''>
					<input id='endDate' name='agefilterend' type='hidden' value=''>
					</div>`;
				changePeriod();
				searchForm.innerHTML = periodHTML;
			}
		}

		function changePeriod() {
			var box = document.getElementById("selectPeriod");
			if (!box || box.type === "hidden") {
				return;
			}
			var chosen = box.options[box.selectedIndex].value;
			var stageSelect = document.getElementById("filterstage");

			/* Period Array */
			var periods = <?php echo json_encode($periods); ?>;

			/* When Period = All, Stage has nothing */
			if (chosen == "All") {
				var AllHTML = "<option value='All'>--Select Period First--</option>";
				stageSelect.innerHTML = AllHTML;
				stageSelect.disabled = true;
				/* Since Stage filter is not used, both Dates are set to empty. */
				var begDate = document.getElementById("begDate");
				begDate.value = '';
				var endDate = document.getElementById("endDate");
				endDate.value = '';
			} else { // When a Period is selected
				var stageHTML = "";
				var rowIdx;
				stageSelect.disabled = false;
				stageHTML = stageHTML + "<option value='All'>All</option>";
				let stages = periods[chosen]["stages"];         
				for (const [key, value] of Object.entries(stages)) {
					var cur_stage = key;                                           
					const stageSelected = !!(cur_stage.toLowerCase().trim() === "<?=$_GET["filterstage"]?>".toLowerCase().trim());
					stageHTML += "<option value='" + cur_stage + "'"
					+ (stageSelected ? ' selected' : '') 
					+ ">" 
					+ cur_stage 
					+ value["date_range"]
					+ "</option>";
				}
				stageSelect.innerHTML = stageHTML;

				// To make sure that the initial selection of "All" takes effect in URL as well
				stageToDate();
			}
		}

		function stageToDate() {
			var input = document.getElementById("filterstage").value;
			/* Period Date lookup table */
			var periods = <?php echo json_encode($periods); ?>;
			var periodChosen = document.getElementById("selectPeriod").value;
			/* If user selected option All for stage, we use the begDate and endDate of the period selected */
			if (input === "All") {
				var begDate = document.getElementById("begDate");
				begDate.value = periods[periodChosen]["begDate"];
				var endDate = document.getElementById("endDate");
				endDate.value = periods[periodChosen]["endDate"];
				return;
			}
			var begDate = document.getElementById("begDate");
			begDate.value = periods[periodChosen]["stages"][input]["base"];
			var endDate = document.getElementById("endDate");
			endDate.value = periods[periodChosen]["stages"][input]["top"];
		}
		
		window.onload = function() {
			changeFilter();
			changePeriod();
			setOrder();
		};
	</script>
</div>