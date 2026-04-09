<?php
// api/get_status.php - Returns latest system data as JSON
header('Content-Type: application/json');
require_once '../db.php';

$conn = getDB();

$latestQ = $conn->query("SELECT temperature, humidity, rain_status, reading_time FROM sensor_readings ORDER BY reading_time DESC LIMIT 1");
$latest  = $latestQ->fetch_assoc();

$posQ    = $conn->query("SELECT rainline_position FROM system_status ORDER BY id DESC LIMIT 1");
$pos     = $posQ->fetch_assoc();

$conn->close();

echo json_encode([
    'temperature' => round($latest['temperature'] ?? 27.5, 1),
    'humidity'    => round($latest['humidity'] ?? 61.0, 1),
    'rain_status' => $latest['rain_status'] ?? 0,
    'rain_label'  => ($latest['rain_status'] ?? 0) ? 'Raining' : 'Dry',
    'position'    => $pos['rainline_position'] ?? 'Outside',
    'updated_at'  => $latest['reading_time'] ?? date('Y-m-d H:i:s')
]);
?>
