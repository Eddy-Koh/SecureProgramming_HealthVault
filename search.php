<?php
// search.php - Patient & Medical Record Search Proxy
require_once 'db_config.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

/* OLD VULNERABLE CODE (Flaw A: SQL Injection)
   $sql = "SELECT id, name, illness_history FROM patient_records WHERE name LIKE '%" . $keyword . "%'";
   $result = $conn->query($sql);
    
   // $keyword was concatenated directly into the SQL string, letting user input reshape the query.
*/

// FIX: query is compiled first, user value bound separately as data only.
$sql = "SELECT id, name, illness_history FROM patient_records WHERE name LIKE :keyword";
$stmt = $pdo->prepare($sql);
$likeParam = '%' . $keyword . '%';
$stmt->bindParam(':keyword', $likeParam, PDO::PARAM_STR);
$stmt->execute();

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {
    foreach ($rows as $row) {

        /* OLD VULNERABLE CODE (Flaw B: Reflected XSS)
           echo "<div>Result found for keyword: " . $keyword . "<br>";
           echo "Patient: " . $row['name'] . " | History: " . $row['illness_history'] . "</div><hr>";
           // Raw output let the browser parse injected HTML/script tags.
        */

        // FIX: htmlspecialchars() encodes HTML-structural characters before output.
        echo "<div>Result found for keyword: "
            . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') . "<br>";
        echo "Patient: " . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8')
            . " | History: " . htmlspecialchars($row['illness_history'], ENT_QUOTES, 'UTF-8')
            . "</div><hr>";
    }
} else {

    /* OLD VULNERABLE CODE (Flaw C: Reflected XSS in error path)
       echo "No records found for: " . $keyword;
       // Same unencoded echo problem in the "no results" branch.
    */

    // FIX: same encoding applied consistently here too.
    echo "No records found for: " . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');
}
?>