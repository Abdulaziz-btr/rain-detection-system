<?php
// ============================================================
//  api/sensor_data.php
//  Receives DHT11 (temperature + humidity) from ESP32 via HTTP.
//  Moisture & LDR rain data comes from Blynk V0/V1 directly.
//
//  ESP32 calls:
//    GET /api/sensor_data.php?rain=DRY&temp=27.5&humi=61.0
//
//  Blynk virtual pins (from Arduino code):
//    V0 = moistureValue  → rain detection  (< 2000 = raining)
//    V1 = lightValue     → LDR light level (< 2000 = dark)
//    V2 = sensorsEnabled → auto mode on/off
//    V3 = servoAngle     → current servo position
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db.php';

$rain        = $_GET['rain']  ?? $_POST['rain']  ?? null;
$temperature = isset($_GET['temp'])  ? floatval($_GET['temp'])  :
               (isset($_POST['temp']) ? floatval($_POST['temp']) : null);
$humidity    = isset($_GET['humi'])  ? floatval($_GET['humi'])  :
               (isset($_POST['humi']) ? floatval($_POST['humi']) : null);

if ($rain === null && $temperature === null) {
    echo json_encode(['status' => 'error', 'message' => 'No parameters received']);
    exit();
}

$rainBool = ($rain === 'RAINING' || $rain === '1' || $rain === 'true') ? 1 : 0;
$userId   = 1;

$conn = getDB();

// Save reading
$stmt = $conn->prepare(
    "INSERT INTO sensor_readings (user_id, rain_status, temperature, humidity) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("iidd", $userId, $rainBool, $temperature, $humidity);
$stmt->execute();

// Auto-update system position based on rain flag
if ($rainBool) {
    $conn->query("UPDATE system_status SET rainline_position='Inside (Sheltered)', last_updated=NOW()");
    $s = $conn->prepare("INSERT INTO commands (user_id, command_type, command_status) VALUES (?, 'retract', 'auto - rain detected')");
    $s->bind_param("i", $userId);
    $s->execute();
} else {
    // Only extend if it was previously raining
    $prev = $conn->query("SELECT rain_status FROM sensor_readings ORDER BY id DESC LIMIT 2");
    $rows = $prev->fetch_all(MYSQLI_ASSOC);
    if (count($rows) >= 2 && $rows[1]['rain_status'] == 1 && $rainBool == 0) {
        $conn->query("UPDATE system_status SET rainline_position='Outside (Drying)', last_updated=NOW()");
        $s = $conn->prepare("INSERT INTO commands (user_id, command_type, command_status) VALUES (?, 'extend', 'auto - rain stopped')");
        $s->bind_param("i", $userId);
        $s->execute();
    }
}

// Check for pending manual command
$cmdQ  = $conn->query("SELECT command_type FROM commands WHERE command_status='pending' ORDER BY command_time ASC LIMIT 1");
$pendCmd = $cmdQ->fetch_assoc();

if ($pendCmd) {
    $conn->query("UPDATE commands SET command_status='sent successfully' WHERE command_status='pending' LIMIT 1");
}

$conn->close();

echo json_encode([
    'status'   => 'ok',
    'rain'     => $rainBool ? 'RAINING' : 'DRY',
    'temp'     => $temperature,
    'humi'     => $humidity,
    'command'  => $pendCmd['command_type'] ?? 'none',
    'note'     => 'Moisture/LDR handled by Blynk V0/V1'
]);
