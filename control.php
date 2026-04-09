<?php
require_once 'db.php';
requireLogin();

$pageTitle  = 'System Control';
$activePage = 'control';

// ============================================================
//  BLYNK CONFIGURATION  (from arduino_esp32.ino)
// ============================================================
define('BLYNK_TOKEN',    'R6muMD4BNBuePhlxqGyYhVwsLdzCvOD-');
define('BLYNK_BASE_URL', 'https://blynk.cloud/external/api');

// Virtual Pins matching the Arduino code:
//   V0 = moistureValue   (read  — analogRead pin 34)
//   V1 = lightValue      (read  — analogRead pin 35)
//   V2 = sensorsEnabled  (write — 1=ON, 0=OFF)
//   V3 = servoAngle      (write — 90=retract, 0=extend)  ← added in updated .ino

// ============================================================
//  BLYNK HELPER FUNCTIONS
// ============================================================
function blynkWrite(int $vPin, $value): bool {
    $url = BLYNK_BASE_URL . '/update?token=' . BLYNK_TOKEN . '&v' . $vPin . '=' . $value;
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
    $res = @file_get_contents($url, false, $ctx);
    return ($res !== false);
}

function blynkRead(int $vPin): ?string {
    $url = BLYNK_BASE_URL . '/get?token=' . BLYNK_TOKEN . '&v' . $vPin;
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
    $res = @file_get_contents($url, false, $ctx);
    if ($res === false) return null;
    $arr = json_decode($res, true);
    return is_array($arr) ? ($arr[0] ?? null) : trim($res);
}

function blynkIsOnline(): bool {
    $url = BLYNK_BASE_URL . '/isHardwareConnected?token=' . BLYNK_TOKEN;
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
    $res = @file_get_contents($url, false, $ctx);
    return (trim($res ?? '') === 'true');
}

// ============================================================
//  HANDLE FORM SUBMISSION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command'])) {
    $cmd    = $_POST['command'];
    $userId = $_SESSION['user_id'];

    if ($cmd === 'retract' || $cmd === 'extend') {

        if ($cmd === 'retract') {
            $servoTarget  = 90;    // 90° = sheltered  (matches Arduino logic: isWet→servo.write(90))
            $sensorsState = 0;     // Disable auto-sensors (V2=0)
            $newPosition  = 'Inside (Sheltered)';
        } else {
            $servoTarget  = 0;     // 0°  = outside    (matches Arduino logic: dry→servo.write(0))
            $sensorsState = 1;     // Re-enable auto-sensors (V2=1)
            $newPosition  = 'Outside (Drying)';
        }

        // Send to Blynk device
        $v3ok = blynkWrite(3, $servoTarget);   // V3 → servo angle
        $v2ok = blynkWrite(2, $sensorsState);  // V2 → sensors on/off

        // Save to local DB
        $conn        = getDB();
        $blynkStatus = ($v3ok && $v2ok) ? 'sent to Blynk successfully' : 'saved (Blynk offline)';
        $stmt        = $conn->prepare("INSERT INTO commands (user_id, command_type, command_status) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $cmd, $blynkStatus);
        $stmt->execute();
        $esc = $conn->real_escape_string($newPosition);
        $conn->query("UPDATE system_status SET rainline_position='$esc', last_updated=NOW() ORDER BY id DESC LIMIT 1");
        $conn->close();

        $msg = ($v3ok && $v2ok)
            ? 'Command "' . $cmd . '" sent to Blynk — Servo: ' . $servoTarget . '°'
            : 'Command "' . $cmd . '" saved locally (device may be offline)';

        header('Location: control.php?msg=' . urlencode($msg));
        exit();
    }
}

// ============================================================
//  READ LIVE DATA FROM BLYNK
// ============================================================
$moisture     = blynkRead(0);
$light        = blynkRead(1);
$sensorsOnRaw = blynkRead(2);
$servoRaw     = blynkRead(3);
$deviceOnline = blynkIsOnline();

$moistureVal    = ($moisture  !== null) ? intval($moisture)     : null;
$lightVal       = ($light     !== null) ? intval($light)        : null;
$sensorsEnabled = ($sensorsOnRaw !== null) ? intval($sensorsOnRaw) : 1;
$currentServo   = ($servoRaw  !== null) ? intval($servoRaw)     : null;

// Thresholds from Arduino code
$MOISTURE_THRESHOLD = 2000;
$LIGHT_THRESHOLD    = 2000;

// Moisture / rain status
if ($moistureVal !== null) {
    $isWet        = ($moistureVal < $MOISTURE_THRESHOLD);
    $moistureTxt  = $isWet ? 'Raining' : 'Dry';
    $moistureCls  = $isWet ? 'wet'     : 'dry';
    $moistureBar  = round((($MOISTURE_THRESHOLD - max(0, min($moistureVal, $MOISTURE_THRESHOLD))) / $MOISTURE_THRESHOLD) * 100);
} else {
    $isWet = false; $moistureTxt = 'Unknown'; $moistureCls = 'dry'; $moistureBar = 0;
}

// Light / LDR status
if ($lightVal !== null) {
    $isDark   = ($lightVal < $LIGHT_THRESHOLD);
    $lightTxt = $isDark ? 'Dark'  : 'Clear';
    $lightCls = $isDark ? 'wet'   : 'clear';
} else {
    $isDark = false; $lightTxt = 'Unknown'; $lightCls = 'clear';
}

// Storm alert = wet AND dark (same condition as Arduino: isWet || isDark triggers 90°)
$stormAlert = ($isWet && $isDark);
$stormTxt   = $stormAlert ? 'Alert!' : 'Clear';
$stormCls   = $stormAlert ? 'wet'    : 'clear';

// Auto mode badge
$autoModeTxt = $sensorsEnabled ? 'Enabled'  : 'Disabled';
$autoModeCls = $sensorsEnabled ? 'enabled'  : 'wet';

// Connection badge
$connTxt = $deviceOnline ? 'Connected' : 'Offline';
$connCls = $deviceOnline ? 'connected' : 'wet';

// Position — prefer live Blynk servo reading, fallback to DB
$conn   = getDB();
$posQ   = $conn->query("SELECT rainline_position FROM system_status ORDER BY id DESC LIMIT 1");
$posRow = $posQ->fetch_assoc();
$conn->close();
$position = $posRow['rainline_position'] ?? 'Outside (Drying)';

if ($currentServo !== null) {
    $position   = ($currentServo >= 45) ? 'Inside (Sheltered)' : 'Outside (Drying)';
    $servoAngle = $currentServo . '°';
} else {
    $servoAngle = (strpos($position, 'Inside') !== false) ? '90°' : '0°';
}

include 'layout_top.php';
?>

<?php if (isset($_GET['msg'])): ?>
<div style="background:#d1fae5;color:#065f46;border-radius:8px;padding:10px 16px;margin-bottom:18px;font-size:13px;font-weight:600;">
    ✓ <?= htmlspecialchars($_GET['msg']) ?>
</div>
<?php endif; ?>

<?php if (!$deviceOnline): ?>
<div style="background:#fef3c7;color:#92400e;border-radius:8px;padding:10px 16px;margin-bottom:18px;font-size:13px;font-weight:600;">
    ⚠️ ESP32 device is offline. Commands will be saved and applied when the device reconnects.
</div>
<?php endif; ?>

<!-- ── CONTROL GRID — same UI as book ──────────────────────── -->
<div class="control-grid">

    <!-- LEFT: Clothes Movement Control -->
    <div class="card">
        <div class="control-section-title">
            <svg viewBox="0 0 24 24" fill="#3b82f6"><path d="M21 3L3 10.53v.98l6.84 2.65L12.48 21h.98L21 3z"/></svg>
            Clothes Movement Control
        </div>
        <p class="control-subtitle">Manually control the position of the clothes drying system</p>

        <form method="POST">
            <div class="control-buttons">
                <button type="submit" name="command" value="retract" class="ctrl-btn retract">
                    <svg viewBox="0 0 24 24" fill="#dc2626"><path d="M21 3L3 10.53v.98l6.84 2.65L12.48 21h.98L21 3z"/></svg>
                    <span class="btn-label">Retract Clothes</span>
                    <span class="btn-sub">Move clothes inside</span>
                </button>
                <button type="submit" name="command" value="extend" class="ctrl-btn extend">
                    <svg viewBox="0 0 24 24" fill="#059669"><path d="M21 3L3 10.53v.98l6.84 2.65L12.48 21h.98L21 3z" transform="rotate(180 12 12)"/></svg>
                    <span class="btn-label">Extend Clothes</span>
                    <span class="btn-sub">Move clothes outside</span>
                </button>
            </div>
        </form>

        <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;">✓ Current Position</div>
        <div class="position-banner">
            <span class="pos-label"><?= htmlspecialchars($position) ?></span>
            <span class="pos-angle">Servo Angle: <?= htmlspecialchars($servoAngle) ?></span>
        </div>

        <!-- Live sensor bar (extra info, same card, no new cards) -->
        <?php if ($moistureVal !== null || $lightVal !== null): ?>
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f3f4f6;">
            <div style="font-size:11px;font-weight:700;color:#9ca3af;letter-spacing:.5px;margin-bottom:10px;">LIVE SENSOR READINGS (Blynk)</div>
            <?php if ($moistureVal !== null): ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <span style="font-size:12px;color:#374151;min-width:68px;">Moisture</span>
                <div style="flex:1;background:#f3f4f6;border-radius:6px;height:7px;">
                    <div style="width:<?= $moistureBar ?>%;background:<?= $isWet ? '#ef4444' : '#10b981' ?>;height:7px;border-radius:6px;"></div>
                </div>
                <span style="font-size:12px;font-weight:700;min-width:60px;text-align:right;color:<?= $isWet ? '#dc2626' : '#059669' ?>;"><?= $moistureVal ?> <?= $isWet ? '🌧️' : '☀️' ?></span>
            </div>
            <?php endif; ?>
            <?php if ($lightVal !== null): ?>
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:12px;color:#374151;min-width:68px;">Light (LDR)</span>
                <div style="flex:1;background:#f3f4f6;border-radius:6px;height:7px;">
                    <div style="width:<?= min(100, round($lightVal/4095*100)) ?>%;background:<?= $isDark ? '#6b7280' : '#f59e0b' ?>;height:7px;border-radius:6px;"></div>
                </div>
                <span style="font-size:12px;font-weight:700;min-width:60px;text-align:right;color:<?= $isDark ? '#6b7280' : '#d97706' ?>;"><?= $lightVal ?> <?= $isDark ? '🌑' : '💡' ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: System Status — same rows, live from Blynk -->
    <div class="card">
        <div class="control-section-title">
            <svg viewBox="0 0 24 24" fill="#6b7280"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            System Status
        </div>

        <div class="status-row">
            <span class="status-label">Auto Mode</span>
            <span class="badge <?= $autoModeCls ?>"><?= $autoModeTxt ?></span>
        </div>
        <div class="status-row">
            <span class="status-label">Rain Detection</span>
            <span class="badge <?= $moistureCls ?>"><?= $moistureTxt ?></span>
        </div>
        <div class="status-row">
            <span class="status-label">Storm Alert</span>
            <span class="badge <?= $stormCls ?>"><?= $stormTxt ?></span>
        </div>
        <div class="status-row">
            <span class="status-label">Connection</span>
            <span class="badge <?= $connCls ?>"><?= $connTxt ?></span>
        </div>
        <div class="status-row">
            <span class="status-label">Light (LDR)</span>
            <span class="badge <?= $lightCls ?>"><?= $lightTxt ?></span>
        </div>

        <?php if ($deviceOnline): ?>
        <div style="margin-top:14px;padding-top:12px;border-top:1px solid #f3f4f6;font-size:11px;color:#9ca3af;text-align:center;">
            🔵 Live via Blynk &nbsp;·&nbsp; Template: raining project<br>
            Moisture &lt; 2000 = Wet &nbsp;·&nbsp; LDR &lt; 2000 = Dark
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
setTimeout(() => location.reload(), 8000);
</script>

<?php include 'layout_bottom.php'; ?>
