<?php
require_once 'db.php';
require_once 'blynk.php';
requireLogin();

$pageTitle  = 'Analytics Overview';
$activePage = 'analytics';

$conn = getDB();

// ── Live snapshot from Blynk ─────────────────────────────────
$b = blynkGetAll();

// ── Aggregated stats from MySQL history ──────────────────────
$statsQ = $conn->query("
    SELECT
        AVG(temperature)  AS avg_temp,
        AVG(humidity)     AS avg_humi,
        MIN(temperature)  AS min_temp,
        MAX(temperature)  AS max_temp,
        COUNT(CASE WHEN rain_status = 1 THEN 1 END) AS rain_events,
        COUNT(*)          AS total_readings
    FROM sensor_readings
");
$stats = $statsQ->fetch_assoc();

$avgTemp       = round($stats['avg_temp']  ?? 27.4, 1);
$avgHumi       = round($stats['avg_humi']  ?? 63.9, 1);
$minTemp       = round($stats['min_temp']  ?? 26.5, 1);
$maxTemp       = round($stats['max_temp']  ?? 28.5, 1);
$rainEvents    = $stats['rain_events']     ?? 0;
$totalReadings = $stats['total_readings']  ?? 0;

// ── Chart data — last 20 readings ────────────────────────────
$chartQ    = $conn->query("SELECT temperature, humidity, reading_time FROM sensor_readings ORDER BY reading_time DESC LIMIT 20");
$chartRows = array_reverse($chartQ->fetch_all(MYSQLI_ASSOC));
$chartLabels = array_map(fn($r) => date('g:i A', strtotime($r['reading_time'])), $chartRows);
$chartTemps  = array_map(fn($r) => round($r['temperature'], 1), $chartRows);
$chartHumis  = array_map(fn($r) => round($r['humidity'], 1), $chartRows);

$conn->close();

include 'layout_top.php';
?>

<!-- ── Metric Cards ─────────────────────────────────────────── -->
<div class="cards-row cards-4">

    <div class="card metric-card">
        <div class="metric-icon blue">
            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
        </div>
        <div class="metric-info">
            <div class="label">Average Temperature</div>
            <div class="value"><?= $avgTemp ?> °C</div>
            <div class="alert-time"><?= $totalReadings ?> readings</div>
        </div>
    </div>

    <div class="card metric-card">
        <div class="metric-icon purple">
            <svg viewBox="0 0 24 24"><path d="M12 2c-.5 0-.9.4-.9.9v.2C8.2 3.6 6 6.1 6 9.1c0 3.3 2.7 6 6 6s6-2.7 6-6c0-3-2.2-5.5-5.1-5.9V2.9c0-.5-.4-.9-.9-.9z"/></svg>
        </div>
        <div class="metric-info">
            <div class="label">Average Humidity</div>
            <div class="value"><?= $avgHumi ?> %</div>
            <div class="alert-time">Optimal: 40–60%</div>
        </div>
    </div>

    <div class="card metric-card">
        <div class="metric-icon green">
            <svg viewBox="0 0 24 24"><path d="M5 9.2h3V19H5V9.2zM10.6 5h2.8v14h-2.8V5zm5.6 8H19v6h-2.8v-6z"/></svg>
        </div>
        <div class="metric-info">
            <div class="label">Temperature Range</div>
            <div class="value" style="font-size:17px"><?= $minTemp ?> – <?= $maxTemp ?> °C</div>
            <div class="alert-time">Min to Max</div>
        </div>
    </div>

    <div class="card metric-card">
        <div class="metric-icon green">
            <svg viewBox="0 0 24 24"><path d="M12 2C10.3 2 8.8 2.9 8 4.3 6.3 4.7 5 6.2 5 8c0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-1.8-1.2-3.3-3-3.7C15.2 2.9 13.7 2 12 2z"/></svg>
        </div>
        <div class="metric-info">
            <div class="label">Rain Events</div>
            <div class="value"><?= $rainEvents ?></div>
            <div class="alert-time">total detections</div>
        </div>
    </div>
</div>

<!-- ── Live Blynk Sensor Row (same card style) ────────────────── -->
<?php if ($b['moisture_raw'] !== null || $b['light_raw'] !== null): ?>
<div class="cards-row cards-2" style="margin-bottom:20px;">

    <!-- Moisture / Rain (Blynk V0) -->
    <div class="card metric-card">
        <div class="metric-icon <?= $b['rain_class'] === 'wet' ? 'green' : 'blue' ?>">
            <svg viewBox="0 0 24 24"><path d="M12 2C10.3 2 8.8 2.9 8 4.3 6.3 4.7 5 6.2 5 8c0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-1.8-1.2-3.3-3-3.7C15.2 2.9 13.7 2 12 2z"/></svg>
        </div>
        <div class="metric-info" style="flex:1">
            <div class="label">Live Moisture (Blynk V0) &nbsp;<span class="badge <?= $b['rain_class'] ?>"><?= $b['rain_label'] ?></span></div>
            <div style="margin-top:8px;background:#f3f4f6;border-radius:6px;height:10px;">
                <div style="width:<?= $b['moisture_pct'] ?>%;background:<?= $b['rain_status'] ? '#ef4444' : '#10b981' ?>;height:10px;border-radius:6px;transition:width .5s;"></div>
            </div>
            <div class="alert-time" style="margin-top:4px;">Raw value: <?= $b['moisture_raw'] ?> / 4095 &nbsp;·&nbsp; Threshold: &lt; <?= MOISTURE_THRESHOLD ?></div>
        </div>
    </div>

    <!-- Light / LDR (Blynk V1) -->
    <div class="card metric-card">
        <div class="metric-icon gray">
            <svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 1 0 0 14A7 7 0 0 0 12 2zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-15a1 1 0 0 1 1 1v1a1 1 0 0 1-2 0V0a1 1 0 0 1 1-1zm0 21a1 1 0 0 1-1-1v-1a1 1 0 0 1 2 0v1a1 1 0 0 1-1 1z"/></svg>
        </div>
        <div class="metric-info" style="flex:1">
            <div class="label">Live Light LDR (Blynk V1) &nbsp;<span class="badge <?= $b['light_class'] ?>"><?= $b['light_label'] ?></span></div>
            <div style="margin-top:8px;background:#f3f4f6;border-radius:6px;height:10px;">
                <div style="width:<?= $b['light_pct'] ?>%;background:<?= $b['light_class'] === 'wet' ? '#6b7280' : '#f59e0b' ?>;height:10px;border-radius:6px;transition:width .5s;"></div>
            </div>
            <div class="alert-time" style="margin-top:4px;">Raw value: <?= $b['light_raw'] ?> / 4095 &nbsp;·&nbsp; Threshold: &lt; <?= LIGHT_THRESHOLD ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Historical Charts ──────────────────────────────────────── -->
<div class="analytics-charts">
    <div class="card">
        <div class="card-title">Historical Temperature</div>
        <canvas id="histTempChart" height="160"></canvas>
    </div>
    <div class="card">
        <div class="card-title">Historical Humidity</div>
        <canvas id="histHumiChart" height="160"></canvas>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode($chartLabels) ?>;
const temps  = <?= json_encode($chartTemps)  ?>;
const humis  = <?= json_encode($chartHumis)  ?>;

const opts = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 } } }
    }
};

new Chart(document.getElementById('histTempChart'), {
    type: 'line',
    data: { labels, datasets: [{ data: temps, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.1)', fill: true, tension: 0.4, pointRadius: 2 }] },
    options: opts
});

new Chart(document.getElementById('histHumiChart'), {
    type: 'line',
    data: { labels, datasets: [{ data: humis, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.08)', fill: true, tension: 0.4, pointRadius: 2 }] },
    options: opts
});

setTimeout(() => location.reload(), 15000);
</script>

<?php include 'layout_bottom.php'; ?>
