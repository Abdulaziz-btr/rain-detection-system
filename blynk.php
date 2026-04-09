<?php
// blynk.php

// 1. ADD YOUR BLYNK AUTH TOKEN HERE
define('BLYNK_AUTH_TOKEN', 'YOUR_BLYNK_AUTH_TOKEN_HERE'); 

// 2. DEFINE YOUR BLYNK SERVER (Usually blynk.cloud)
define('BLYNK_SERVER', 'https://blynk.cloud'); 

/**
 * Helper function to fetch a single pin value from the Blynk HTTP API
 */
function getBlynkPin($pin) {
    $url = BLYNK_SERVER . '/external/api/get?token=' . BLYNK_AUTH_TOKEN . '&' . $pin;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $result = curl_exec($ch);
    curl_close($ch);
    
    if ($result !== false && !str_contains($result, 'Invalid token') && !str_contains($result, 'error')) {
        return trim($result, '[]"'); 
    }
    return null;
}

/**
 * Fetch all relevant data from Blynk (Moisture, Light, Position)
 */
function blynkGetAll() {
    $moisture_raw = getBlynkPin('V0');
    $light_raw    = getBlynkPin('V1'); // Added Light Sensor
    $position_raw = getBlynkPin('V3'); 
    
    $isOnlineUrl = BLYNK_SERVER . '/external/api/isHardwareConnected?token=' . BLYNK_AUTH_TOKEN;
    $onlineResult = @file_get_contents($isOnlineUrl);
    $online = ($onlineResult === 'true');

    // ── Moisture Logic ─────────────────────────────────────────
    $rain_class = 'gray';
    $rain_label = 'Unknown';
    $moisture_pct = 0;
    $rain_status = false; // true = raining, false = dry
    
    if ($moisture_raw !== null && is_numeric($moisture_raw)) {
        // Calculate percentage (Assuming 4095 is max ESP32 analog read)
        $moisture_pct = round(($moisture_raw / 4095) * 100);
        
        if ($moisture_raw < 1000) { 
            $rain_class = 'wet';
            $rain_label = 'Raining';
            $rain_status = true;
        } else {
            $rain_class = 'green';
            $rain_label = 'Dry / Clear';
        }
    }

    // ── Light (LDR) Logic ──────────────────────────────────────
    $light_class = 'gray';
    $light_label = 'Unknown';
    $light_pct = 0;

    if ($light_raw !== null && is_numeric($light_raw)) {
        $light_pct = round(($light_raw / 4095) * 100);
        
        if ($light_raw < 2000) { // Adjust this threshold if your sensor behaves differently
            $light_class = 'wet'; // Using 'wet' because analytics.php checks for this to set a gray bar
            $light_label = 'Dark / Cloudy';
        } else {
            $light_class = 'orange';
            $light_label = 'Bright / Sunny';
        }
    }

    // ── Clothesline Position Logic ─────────────────────────────
    $position = null;
    if ($position_raw !== null) {
        if ($position_raw == 0) {
            $position = 'Inside (Protected)';
        } else {
            $position = 'Outside (Drying)';
        }
    }

    return [
        'online'       => $online,
        'position'     => $position,
        // Moisture Data
        'moisture_raw' => $moisture_raw,
        'moisture_pct' => $moisture_pct,
        'rain_class'   => $rain_class,
        'rain_label'   => $rain_label,
        'rain_status'  => $rain_status,
        // Light Data
        'light_raw'    => $light_raw,
        'light_pct'    => $light_pct,
        'light_class'  => $light_class,
        'light_label'  => $light_label
    ];
}

/**
 * Save Blynk reading to the database history
 */
function blynkSaveToDB($b, $conn, $user_id) {
    if (!$b['online'] || $b['moisture_raw'] === null) {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO system_status (rainline_position, user_id) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("si", $b['position'], $user_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    return false;
}
?>