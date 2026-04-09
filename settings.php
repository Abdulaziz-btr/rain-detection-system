<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Settings';
$activePage = 'settings';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDB();
    $userId = $_SESSION['user_id'];

    if (isset($_POST['save_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $email, $userId);
        $stmt->execute();
        $_SESSION['user_name'] = $name;
        $success = 'Profile updated successfully.';
    }

    if (isset($_POST['save_password'])) {
        $current = $_POST['current_password'];
        $new     = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!password_verify($current, $row['password'])) {
            $error = 'Current password is incorrect.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt2->bind_param("si", $hashed, $userId);
            $stmt2->execute();
            $success = 'Password updated successfully.';
        }
    }
    $conn->close();
}

// Get user info
$conn = getDB();
$userId = $_SESSION['user_id'];
$userQ = $conn->prepare("SELECT name, email FROM users WHERE id=?");
$userQ->bind_param("i", $userId);
$userQ->execute();
$user = $userQ->get_result()->fetch_assoc();
$conn->close();

include 'layout_top.php';
?>

<?php if ($success): ?>
<div style="background:#d1fae5;color:#065f46;border-radius:8px;padding:10px 16px;margin-bottom:18px;font-size:13px;font-weight:600;">✓ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div style="background:#fee2e2;color:#dc2626;border-radius:8px;padding:10px 16px;margin-bottom:18px;font-size:13px;">✗ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="settings-grid">
    <!-- Profile Settings -->
    <div class="card">
        <div class="card-title">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            Profile Settings
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <button type="submit" name="save_profile" class="save-btn">Save Profile</button>
        </form>
    </div>

    <!-- Password Settings -->
    <div class="card">
        <div class="card-title">
            <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
            Change Password
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" name="save_password" class="save-btn">Update Password</button>
        </form>
    </div>

    <!-- System Settings -->
    <div class="card">
        <div class="card-title">
            <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58z"/></svg>
            System Configuration
        </div>
        <div class="toggle-row">
            <span class="toggle-label">Auto Mode</span>
            <label class="toggle"><input type="checkbox" checked><span class="slider"></span></label>
        </div>
        <div class="toggle-row">
            <span class="toggle-label">Rain Detection Alert</span>
            <label class="toggle"><input type="checkbox" checked><span class="slider"></span></label>
        </div>
        <div class="toggle-row">
            <span class="toggle-label">Buzzer Alert</span>
            <label class="toggle"><input type="checkbox" checked><span class="slider"></span></label>
        </div>
        <div class="toggle-row">
            <span class="toggle-label">LCD Display</span>
            <label class="toggle"><input type="checkbox" checked><span class="slider"></span></label>
        </div>
        <br>
        <button class="save-btn" onclick="alert('System settings saved.')">Save Settings</button>
    </div>

    <!-- About -->
    <div class="card">
        <div class="card-title">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            About System
        </div>
        <div style="font-size:13px;color:#374151;line-height:1.8;">
            <p><strong>System:</strong> Rain Detection &amp; Control System</p>
            <p><strong>Case Study:</strong> MKU Hostels</p>
            <p><strong>Student:</strong> Butera Abdulaziz</p>
            <p><strong>University:</strong> Mount Kigali University</p>
            <p><strong>Version:</strong> 1.0.0</p>
            <p><strong>Hardware:</strong> ESP32 + DHT11 + Rain Sensor + Servo SG90</p>
        </div>
    </div>
</div>

<?php include 'layout_bottom.php'; ?>
