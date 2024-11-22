<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "myDB";

//Uncomment this code to completely reset db (all data will be lost)
// // create connection
$conn = new mysqli($servername, $username, $password);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql8 = "USE myDB";
if ($conn->query($sql8) === true) {
    echo "Database Already Exists...Dropping Tables and Database to rebuild them.";
} else {
    echo "Database does not exist, rebuilding from scratch, ignore errors about dropping database " . $conn->error;
}
$sql5 = "DROP TABLE IF EXISTS user_info";
if ($conn->query($sql5) === true) {
    echo "\nTable user_info dropped successfully";
} else {
    echo "\nError dropping table user_info: " . $conn->error;
}

$sql5 = "DROP TABLE IF EXISTS fossil";
if ($conn->query($sql5) === true) {
    echo "\nTable user_info dropped successfully";
} else {
    echo "\nError dropping table user_info: " . $conn->error;
}
// drop database
$sql = "DROP DATABASE IF EXISTS myDB";
if ($conn->query($sql) === true) {
    echo "\nDatabase dropped successfully";
} else {
    echo "\nError dropping database: " . $conn->error;
}

$conn->close();

// Create connection
$conn = new mysqli($servername, $username, $password);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE myDB";
if ($conn->query($sql) === true) {
    echo "\nDatabase created successfully";
} else {
    echo "\nError creating database: " . $conn->error;
}

$sql = "USE myDB";
if ($conn->query($sql) === false) {
    echo "\nFailed to use database";
}

$sql4 = "CREATE TABLE user_info(
  ID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  uname Varchar(255),
  pasw Varchar(255),
  admin Varchar(255)
)";


$rootpasw = password_hash("TSCreator", PASSWORD_DEFAULT);
$sql3 = "INSERT INTO user_info(uname, pasw, admin) VALUES ('root', '$rootpasw','True')";
if ($conn->query($sql4) == true && $conn->query($sql3) === true) {
    echo "\nUser_info table created successfully";
} else {
    echo "\nError creating user_info table: " . $conn->error;
}

include_once("SimpleXLSX.php");
$xlsx = SimpleXLSX::parse("Brachiopod.xlsx");
$columns = [];
if ($xlsx === false) {
    echo "\nCan't open excel file.";
} else {
    $row = $xlsx->rows(0)[0];
    foreach ($row as $cell) {
        if (!empty($cell)) {
            $column_name = str_replace(" ", "_", trim($cell));
            $columns[] = $column_name;
        }
    }
    //Create columns for CREATE TABLE statement
    $sqlCreate = "CREATE TABLE fossil (ID int NOT NULL AUTO_INCREMENT PRIMARY KEY,";
    foreach ($columns as $column) {
        if ($column == "Genus") {
            $sqlCreate .= "`$column` TEXT, UNIQUE(`$column`(255)),";
        } else {
            $sqlCreate .= "`$column` TEXT,";
        }
    }
    // put extra columns here (base conversion, top conversion, caculated age)
    $sqlCreate .= "`beginning_stage` VARCHAR(255) DEFAULT 'Unkown',
    `fraction_up_beginning_stage` FLOAT DEFAULT NULL,
    `beginning_date` FLOAT DEFAULT NULL,
    `ending_stage` VARCHAR(255) DEFAULT 'Unkown',
    `fraction_up_ending_stage` FLOAT DEFAULT NULL,
    `ending_date` FLOAT DEFAULT NULL,
    `geojson` VARCHAR(255) DEFAULT 'Unkown',
    `region` VARCHAR(255) DEFAULT 'Unkown',
    `sub-region` VARCHAR(255) DEFAULT 'Unkown',
    `province` VARCHAR(255) DEFAULT 'Unkown',
    `sub_province` VARCHAR(255) DEFAULT 'Unkown',
    `amount_of_extra_columns` INTEGER DEFAULT 11"; //If adding an extra columns to this command, make sure to update this amount, used later

    $sqlCreate = rtrim($sqlCreate, ',') . ");";
    if ($conn->query($sqlCreate) === true) {
        echo "\nTable fossil created successfully\n";
    } else {
        echo "\nError creating fossil table: " . $conn->error . "\n";
        return;
    }

    $sqlCreate = "CREATE TABLE countries (
    country_id INT AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(255) UNIQUE NOT NULL,
    geojson TEXT NOT NULL
  );";

    if ($conn->query($sqlCreate) === true) {
        echo "\nTable countries created successfully\n";
    } else {
        echo "\nError creating countries table: " . $conn->error . "\n";
        return;
    }

    $sqlCreate = "CREATE TABLE regions (
    region_id INT AUTO_INCREMENT PRIMARY KEY,
    region_name VARCHAR(255) UNIQUE NOT NULL
  );";

    if ($conn->query($sqlCreate) === true) {
        echo "\nTable regions created successfully\n";
    } else {
        echo "\nError creating regions table: " . $conn->error . "\n";
        return;
    }

    $sqlCreate = "CREATE TABLE country_region (
    country_id INT,
    region_id INT,
    PRIMARY KEY (country_id, region_id),
    FOREIGN KEY (country_id) REFERENCES countries(country_id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(region_id) ON DELETE CASCADE
  );";

    if ($conn->query($sqlCreate) === true) {
        echo "\nTable country_region created successfully\n";
    } else {
        echo "\nError creating country_region table: " . $conn->error . "\n";
        return;
    }

}
