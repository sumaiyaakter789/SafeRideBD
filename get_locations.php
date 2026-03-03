<?php
include_once 'db_config.php';
header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Optimized query with indexing and better matching
$searchTerm = '%' . $conn->real_escape_string($query) . '%';
$sql = "SELECT DISTINCT location, 
        CASE 
            WHEN location = ? THEN 1
            WHEN location LIKE ? THEN 2
            ELSE 3
        END as relevance
        FROM (
            SELECT `from` as location FROM fare_chart WHERE `from` LIKE ?
            UNION
            SELECT `to` as location FROM fare_chart WHERE `to` LIKE ?
        ) as locations 
        ORDER BY relevance, location 
        LIMIT 10";

$exactMatch = $query;
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $exactMatch, $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = $row['location'];
}

// If no results found, try with Bengali normalization
if (empty($locations)) {
    // Remove Bengali diacritics and normalize
    $normalized = preg_replace('/[\u0981-\u09E3]/u', '', $query);
    if ($normalized !== $query) {
        $searchTerm = '%' . $conn->real_escape_string($normalized) . '%';
        $sql = "SELECT DISTINCT location FROM (
                    SELECT `from` as location FROM fare_chart WHERE `from` LIKE ?
                    UNION
                    SELECT `to` as location FROM fare_chart WHERE `to` LIKE ?
                ) as locations 
                ORDER BY location 
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $locations[] = $row['location'];
        }
    }
}

echo json_encode($locations);
$conn->close();
?>