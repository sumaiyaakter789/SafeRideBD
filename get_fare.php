<?php
include_once 'db_config.php';
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$from = isset($_POST['from']) ? trim($_POST['from']) : '';
$to = isset($_POST['to']) ? trim($_POST['to']) : '';

if (empty($from) || empty($to)) {
    echo json_encode(['success' => false, 'message' => 'Please provide both starting point and destination.']);
    exit;
}

if (strtolower($from) === strtolower($to)) {
    echo json_encode(['success' => false, 'message' => 'Starting point and destination cannot be the same.']);
    exit;
}

// Check if result exists in cache (5 minutes cache)
$cacheKey = 'route_' . md5($from . '_' . $to);
$cachedResult = getCachedResult($cacheKey);
if ($cachedResult) {
    echo $cachedResult;
    exit;
}

// Optimized direct route query with indexing
$query = "SELECT * FROM fare_chart WHERE 
          (`from` = ? AND `to` = ?) 
          LIMIT 1";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    $regularFare = $row['fare'];
    $studentFare = max(floor($regularFare / 2), 10);
    
    $response = [
        'success' => true,
        'route_type' => 'direct',
        'from' => $from,
        'to' => $to,
        'fare' => $regularFare,
        'student_fare' => $studentFare,
        'distance_km' => $row['distance_km'],
        'operating_bus' => $row['operating_bus'],
        'route_breakdown' => [
            [
                'from' => $from,
                'to' => $to,
                'fare' => $regularFare,
                'student_fare' => $studentFare,
                'distance_km' => $row['distance_km'],
                'operating_bus' => $row['operating_bus']
            ]
        ]
    ];
    
    $jsonResponse = json_encode($response);
    cacheResult($cacheKey, $jsonResponse, 300); // Cache for 5 minutes
    echo $jsonResponse;
    exit;
}

// Check reverse route
$query = "SELECT * FROM fare_chart WHERE 
          (`from` = ? AND `to` = ?) 
          LIMIT 1";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $to, $from);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    $regularFare = $row['fare'];
    $studentFare = max(floor($regularFare / 2), 10);
    
    $response = [
        'success' => true,
        'route_type' => 'direct',
        'from' => $from,
        'to' => $to,
        'fare' => $regularFare,
        'student_fare' => $studentFare,
        'distance_km' => $row['distance_km'],
        'operating_bus' => $row['operating_bus'],
        'route_breakdown' => [
            [
                'from' => $from,
                'to' => $to,
                'fare' => $regularFare,
                'student_fare' => $studentFare,
                'distance_km' => $row['distance_km'],
                'operating_bus' => $row['operating_bus']
            ]
        ]
    ];
    
    $jsonResponse = json_encode($response);
    cacheResult($cacheKey, $jsonResponse, 300);
    echo $jsonResponse;
    exit;
}

// Optimized route finding with pre-loaded graph
$routes = findConnectingRoutesOptimized($from, $to, $conn);

if (!empty($routes)) {
    $bestRoute = selectBestRoute($routes);
    
    if ($bestRoute) {
        $totalFare = 0;
        $totalStudentFare = 0;
        $totalDistance = 0;
        $allBuses = [];
        $routeBreakdown = [];
        
        foreach ($bestRoute as $index => $segment) {
            $studentFareSegment = max(floor($segment['fare'] / 2), 10);
            
            $routeBreakdown[] = [
                'segment' => $index + 1,
                'from' => $segment['from'],
                'to' => $segment['to'],
                'fare' => $segment['fare'],
                'student_fare' => $studentFareSegment,
                'distance_km' => $segment['distance_km'],
                'operating_bus' => $segment['operating_bus']
            ];
            
            $totalFare += $segment['fare'];
            $totalStudentFare += $studentFareSegment;
            $totalDistance += $segment['distance_km'];
            
            if ($segment['operating_bus']) {
                $allBuses[] = $segment['operating_bus'];
            }
        }
        
        $response = [
            'success' => true,
            'route_type' => 'connecting',
            'from' => $from,
            'to' => $to,
            'fare' => $totalFare,
            'student_fare' => $totalStudentFare,
            'distance_km' => round($totalDistance, 2),
            'operating_bus' => implode(', ', array_unique($allBuses)),
            'route_breakdown' => $routeBreakdown,
            'transfers' => count($bestRoute) - 1,
            'segments' => count($bestRoute)
        ];
        
        $jsonResponse = json_encode($response);
        cacheResult($cacheKey, $jsonResponse, 300);
        echo $jsonResponse;
        exit;
    }
}

echo json_encode([
    'success' => false, 
    'message' => 'No fare information available for this route. Please try a different route.'
]);

$conn->close();

// Cache functions using file-based cache (simpler than Redis)
function getCachedResult($key) {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . $key . '.cache';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        return file_get_contents($cacheFile);
    }
    return null;
}

function cacheResult($key, $data, $ttl = 300) {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . $key . '.cache';
    file_put_contents($cacheFile, $data);
}

// Optimized route finding with BFS and pre-built graph
function findConnectingRoutesOptimized($start, $end, $conn) {
    // Build graph once
    static $graph = null;
    static $routeDetails = [];
    
    if ($graph === null) {
        $graph = [];
        $query = "SELECT `from`, `to`, fare, distance_km, operating_bus FROM fare_chart";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Forward direction
                if (!isset($graph[$row['from']])) {
                    $graph[$row['from']] = [];
                }
                $graph[$row['from']][] = $row['to'];
                
                // Reverse direction
                if (!isset($graph[$row['to']])) {
                    $graph[$row['to']] = [];
                }
                $graph[$row['to']][] = $row['from'];
                
                // Store route details for both directions
                $key = $row['from'] . '|' . $row['to'];
                $routeDetails[$key] = $row;
                
                $revKey = $row['to'] . '|' . $row['from'];
                $routeDetails[$revKey] = $row;
            }
        }
    }
    
    if (!isset($graph[$start]) || !isset($graph[$end])) {
        return [];
    }
    
    // BFS to find shortest path (by number of transfers)
    $queue = [[$start]];
    $visited = [$start];
    $allPaths = [];
    $maxDepth = 3; // Limit to 3 transfers max for performance
    
    while (!empty($queue)) {
        $path = array_shift($queue);
        $current = end($path);
        
        if (count($path) > $maxDepth + 1) {
            continue;
        }
        
        if ($current === $end && count($path) > 1) {
            $allPaths[] = $path;
            continue;
        }
        
        if (isset($graph[$current])) {
            foreach ($graph[$current] as $neighbor) {
                if (!in_array($neighbor, $path)) {
                    $newPath = $path;
                    $newPath[] = $neighbor;
                    $queue[] = $newPath;
                }
            }
        }
    }
    
    // Convert paths to route details
    $routes = [];
    foreach ($allPaths as $path) {
        $route = [];
        for ($i = 0; $i < count($path) - 1; $i++) {
            $key = $path[$i] . '|' . $path[$i + 1];
            if (isset($routeDetails[$key])) {
                $route[] = $routeDetails[$key];
            } else {
                continue 2; // Skip this path if any segment missing
            }
        }
        $routes[] = $route;
    }
    
    return $routes;
}

function selectBestRoute($routes) {
    if (empty($routes)) return null;
    
    $bestRoute = null;
    $bestScore = PHP_INT_MAX;
    
    foreach ($routes as $route) {
        $totalDistance = 0;
        $totalFare = 0;
        
        foreach ($route as $segment) {
            $totalDistance += $segment['distance_km'];
            $totalFare += $segment['fare'];
        }
        
        $transfers = count($route) - 1;
        
        // Score: prioritize fewer transfers, then shorter distance, then lower fare
        $score = ($transfers * 1000) + ($totalDistance * 10) + ($totalFare);
        
        if ($score < $bestScore) {
            $bestScore = $score;
            $bestRoute = $route;
        }
    }
    
    return $bestRoute;
}
?>