<?php
require_once 'db.php';
require_once 'blynk.php';
requireLogin();

$pageTitle  = 'Dashboard Overview';
$activePage = 'dashboard';

$conn = getDB();

// ── Fetch live data from Blynk (V0–V3) ──────────────────────
$b = blynkGetAll();

// ── Save Blynk reading into history ─────────────────────────
blynkSaveToDB($b, $conn, $_SESSION['user_id']);

// ── DHT11 temperature & humidity come from MySQL ─────────────
// (ESP32 sends DHT11 via HTTP API or Blynk custom events)
$dhtQ  = $conn->query("SELECT temperature, humidity FROM sensor_readings ORDER BY reading_time DESC LIMIT 1");
$dht   = $dhtQ->fetch_assoc();
$temp  = $dht['temperature'] ?? 27.5;
$humi  = $dht['humidity']    ?? 61.0;

// ── Clothesline position: prefer Blynk live, fallback DB ─────
if ($b['position'] !== null) {
    $position = $b['position'];
} else {
    $posQ     = $conn->query("SELECT rainline_position FROM system_status ORDER BY id DESC LIMIT 1");
    $posRow   = $posQ->fetch_assoc();
    $position = $posRow['rainline_position'] ?? 'Outside (Drying)';
}

// ── Recent alerts from DB ────────────────────────────────────
$alertsQ = $conn->query("SELECT command_type, command_status, command_time FROM commands ORDER BY command_time DESC LIMIT 8");
$alerts  = $alertsQ->fetch_all(MYSQLI_ASSOC);

// ── Chart data (last 10 readings from MySQL) ─────────────────
$chartQ    = $conn->query("SELECT temperature, humidity, reading_time FROM sensor_readings ORDER BY reading_time DESC LIMIT 10");
$chartRows = array_reverse($chartQ->fetch_all(MYSQLI_ASSOC));
$chartLabels = array_map(fn($r) => date('g:i A', strtotime($r['reading_time'])), $chartRows);
$chartTemps  = array_map(fn($r) => round($r['temperature'], 1), $chartRows);
$chartHumis  = array_map(fn($r) => round($r['humidity'], 1), $chartRows);

$conn->close();

include 'layout_top.php';
?>

<!-- ── Metric Cards ─────────────────────────────────────────── -->
<div class="cards-row cards-4">

    <!-- Temperature (DHT11 via MySQL) -->
    <div class="card metric-card">
        <div class="metric-icon blue">
            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        </div>
        <div class="metric-info">
            <div class="label">Temperature</div>
            <div class="value"><?= number_format($temp, 1) ?> °C</div>
        </div>
    </div>

    <!-- Humidity (DHT11 via MySQL) -->
    <div class="card metric-card">
        <div class="metric-icon purple">
            <svg viewBox="0 0 24 24"><path d="M12 2c-.5 0-.9.4-.9.9v.2C8.2 3.6 6 6.1 6 9.1c0 3.3 2.7 6 6 6s6-2.7 6-6c0-3-2.2-5.5-5.1-5.9V2.9c0-.5-.4-.9-.9-.9z"/></svg>
        </div>
        <div class="metric-info">
            <div class="label">Humidity</div>
            <div class="value"><?= number_format($humi, 1) ?> %</div>
        </div>
    </div>

    <!-- Rain Status (Blynk V0 moisture sensor) -->
    <div class="card metric-card">
        <div class="metric-icon green">
            <svg viewBox="0 0 24 24"><path d="M12 2C10.3 2 8.8 2.9 8 4.3 6.3 4.7 5 6.2 5 8c0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-1.8-1.2-3.3-3-3.7C15.2 2.9 13.7 2 12 2zM9 18l-4 4h14l-4-4H9z"/></svg>
        </div>
        <div class="metric-info">
            <div class="label">Rain Status</div>
            <div class="value">
                <span class="badge <?= $b['rain_class'] ?>"><?= $b['rain_label'] ?></span>
                <?php if ($b['moisture_raw'] !== null): ?>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Moisture: <?= $b['moisture_raw'] ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Clothesline Position (Blynk V3 servo) -->
    <div class="card metric-card">
        <div class="metric-icon gray">
            <svg viewBox="0 0 24 24"><path d="M21 3L3 10.53v.98l6.84 2.65L12.48 21h.98L21 3z"/></svg>
        </div>
        <div class="metric-info">
            <div class="label">Clothes Position</div>
            <div class="value" style="font-size:15px"><?= htmlspecialchars($position) ?></div>
            <?php if (!$b['online']): ?>
            <div style="font-size:11px;color:#f59e0b;margin-top:4px;">⚠️ Device offline</div>
            <?php else: ?>
            <div style="font-size:11px;color:#10b981;margin-top:4px;">🔵 Blynk live</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Chart + Alerts ─────────────────────────────────────────── -->
<div class="chart-section">
    <div class="card" style="grid-column:1">
        <div class="card-title">
            <svg viewBox="0 0 24 24"><path d="M5 9.2h3V19H5V9.2zM10.6 5h2.8v14h-2.8V5zm5.6 8H19v6h-2.8v-6z"/></svg>
            Temperature &amp; Humidity Trends
        </div>
        <canvas id="tempHumiChart" height="140"></canvas>
    </div>

    <div class="card">
        <div class="card-title">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            Recent Alerts
        </div>
        <ul class="alerts-list">
            <?php foreach ($alerts as $alert): ?>
            <li class="alert-item">
                <div class="alert-check">
                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </div>
                <div>
                    <div>Command "<?= htmlspecialchars($alert['command_type']) ?>" <?= htmlspecialchars($alert['command_status']) ?></div>
                    <div class="alert-time"><?= date('g:i A', strtotime($alert['command_time'])) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
            <?php if (empty($alerts)): ?>
            <li class="alert-item"><div>No recent alerts</div></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode($chartLabels) ?>;
const temps  = <?= json_encode($chartTemps)  ?>;
const humis  = <?= json_encode($chartHumis)  ?>;

new Chart(document.getElementById('tempHumiChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            { label: 'Temperature (°C)', data: temps, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.15)', fill: true, tension: 0.4, pointRadius: 3 },
            { label: 'Humidity (%)',      data: humis, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,0.08)',  fill: true, tension: 0.4, pointRadius: 3 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 12 } } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 } } }
        }
    }
});

setTimeout(() => location.reload(), 10000);
</script>

<?php include 'layout_bottom.php'; ?>
