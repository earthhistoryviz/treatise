<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "myDB";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database successfully\n";

$sql_usemyDB = "USE myDB";
if ($conn->query($sql_usemyDB) === true) {
    echo "Using database myDB";
} else {
    die("\nDatabase myDB does not exist, create database using create_db.php first. Error: " . $conn->error);
}

if (!isset($argv[1])) {
    die("Provide excel filepath. Usage: php import_taxonomy_links.php <excel_file_path> <pdf_url>\n");
} elseif (!isset($argv[2])) {
    die("Provide PDF file base link. Usage: php import_taxonomy_links.php <excel_file_path> <pdf_url>\n");
}

$sql_drop = "DROP TABLE IF EXISTS taxonomy_pdf_links";
if ($conn->query($sql_drop) === true) {
    echo "\nTable taxonomy_pdf_links dropped successfully.";
} else {
    echo "\nNo table taxonomy_pdf_links present, creating new table.";
}

/* Create the table for toxonomy names and pdf links correspondence here
 * taxonomy_level -- 'Phylum', 'Class', 'Order', etc.
 * taxonomy_name -- 'Charophyta', 'Charales', etc.
 * pdf_url -- e.g. https://charophyte.treatise.geolex.org/Treatise-Charophytavolume.pdf
 * page_number -- page number in PDF file
 */
$sql_create = "CREATE TABLE taxonomy_pdf_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taxonomy_level VARCHAR(50) NOT NULL,
    taxonomy_name VARCHAR(255) NOT NULL,
    pdf_url VARCHAR(500) NOT NULL,
    page_number INT NOT NULL,
    UNIQUE KEY unique_taxonomy (taxonomy_level, taxonomy_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"; // sets Storage Engine and Character Set used

if ($conn->query($sql_create) === true) {
    echo "\nTable created successfully!";
} else {
    die("\nError creating table: " . $conn->error);
}

$pdf_base_url = isset($argv[2]) ? $argv[2] : "";

include_once("SimpleXLSX.php");
$xlsx = SimpleXLSX::parse($argv[1]);

if ($xlsx === false) {
    die("\nCan't open excel file.");
}

$rows = $xlsx->rows(0);

// define all columns that will be linked to PDF pages
// NOTE: excel file should follow standard structure, i.e. Phylum,Phy-page,Subhylum,?,Class,Class-page
$taxonomyColumns = [
    0 => 'Phylum',
    2 => 'Subphylum',
    4 => 'Class',
    6 => 'Subclass',
    8 => 'Order',
    10 => 'Suborder',
    12 => 'Infraorder',
    14 => 'Superfamily',
    16 => 'Family',
    18 => 'Subfamily',
];

// Keep track of previous entries to ensure inheritance
$previousValues = [];
foreach ($taxonomyColumns as $colIndex => $level) {
    $previousValues[$level] = [
        'name' => null,
        'page' => null
    ];
}

$stmt = $conn->prepare("
    INSERT INTO taxonomy_pdf_links (taxonomy_level, taxonomy_name, pdf_url, page_number)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        pdf_url = VALUES(pdf_url),
        page_number = VALUES(page_number)
");

$imported_count = 0;

// Skip headers, start from 2nd line
for ($rowIndex = 1; $rowIndex < count($rows); $rowIndex++) {
    $row = $rows[$rowIndex];
    
    echo "\n=== Processing Row " . ($rowIndex + 1) . " ===\n";
    
    
    foreach ($taxonomyColumns as $nameColIndex => $taxonomyLevel) {
        $pageColIndex = $nameColIndex + 1;
        
        $taxonomyName = isset($row[$nameColIndex]) ? trim($row[$nameColIndex]) : '';
        $pageNumber = isset($row[$pageColIndex]) ? trim($row[$pageColIndex]) : '';
        
        
        $isInvalid = empty($taxonomyName) || empty($pageNumber) || !is_numeric($pageNumber);
        
        if (empty($taxonomyName) || empty($pageNumber)) {
            // Empty column, just skip, do not need to do anything
        } else if (!is_numeric($pageNumber)) {
            echo("\nPage error for $taxonomyName, input should be a valid page number. Skipping.");
        } else {
            // Record correspondence to PDF page
            $pageNum = intval($pageNumber);
            $previousValues[$taxonomyLevel] = [
                'name' => $taxonomyName,
                'page' => $pageNum
            ];
            
            $stmt->bind_param("sssi", $taxonomyLevel, $taxonomyName, $pdf_base_url, $pageNum);
            
            // Insert entry into database table
            if ($stmt->execute()) {
                echo "\nImported: $taxonomyLevel = $taxonomyName (Page $pageNum)";
                $imported_count++;
            } else {
                echo "\nError: " . $stmt->error;
            }
        }
    }
}

$stmt->close();
$conn->close();

echo "\n=== Import Summary ===\n";
echo "Successfully imported: $imported_count unique records\n";

?>