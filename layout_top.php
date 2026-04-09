<?php
// layout.php - Shared layout for all dashboard pages
// Requires $pageTitle and $activePage to be set before including
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Rain Detection System') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f5f7;
            display: flex;
            min-height: 100vh;
            color: #222;
        }

        /* ---- SIDEBAR ---- */
        .sidebar {
            width: 220px;
            background: #1a1d27;
            color: #fff;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 22px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-logo .logo-icon {
            width: 32px; height: 32px;
            background: #3b82f6;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }

        .sidebar-logo .logo-icon svg { width: 18px; height: 18px; fill: white; }

        .sidebar-logo span {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
        }

        .sidebar-logo .sub { font-size: 11px; color: #9ca3af; font-weight: 400; }

        nav {
            flex: 1;
            padding: 16px 0;
        }

        nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.18s;
            border-radius: 0;
        }

        nav a:hover { background: rgba(255,255,255,0.06); color: #fff; }

        nav a.active {
            background: #3b82f6;
            color: #fff;
            border-radius: 0 8px 8px 0;
            margin-right: 12px;
        }

        nav a svg { width: 18px; height: 18px; flex-shrink: 0; }

        .connected-badge {
            margin: 0 16px 20px;
            background: #166534;
            color: #4ade80;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .connected-badge .dot {
            width: 8px; height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* ---- MAIN ---- */
        .main {
            margin-left: 220px;
            flex: 1;
            min-height: 100vh;
        }

        .topbar {
            background: #fff;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar .page-title {
            font-size: 20px;
            font-weight: 700;
            color: #111;
        }

        .topbar .right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .last-update {
            font-size: 12px;
            color: #9ca3af;
        }

        .refresh-btn {
            background: none;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px 10px;
            cursor: pointer;
            color: #555;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .refresh-btn:hover { background: #f3f4f6; }

        .logout-btn {
            background: none;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 7px 14px;
            cursor: pointer;
            color: #555;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.18s;
        }

        .logout-btn:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

        .content {
            padding: 24px 28px;
        }

        /* ---- CARDS ---- */
        .cards-row {
            display: grid;
            gap: 16px;
            margin-bottom: 20px;
        }

        .cards-4 { grid-template-columns: repeat(4, 1fr); }
        .cards-2 { grid-template-columns: repeat(2, 1fr); }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 18px 22px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .metric-card {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .metric-icon {
            width: 46px; height: 46px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .metric-icon svg { width: 24px; height: 24px; }

        .metric-icon.blue { background: #dbeafe; }
        .metric-icon.blue svg { fill: #3b82f6; }
        .metric-icon.purple { background: #ede9fe; }
        .metric-icon.purple svg { fill: #7c3aed; }
        .metric-icon.green { background: #d1fae5; }
        .metric-icon.green svg { fill: #059669; }
        .metric-icon.gray { background: #f3f4f6; }
        .metric-icon.gray svg { fill: #6b7280; }

        .metric-info .label { font-size: 12px; color: #9ca3af; font-weight: 500; margin-bottom: 4px; }
        .metric-info .value { font-size: 22px; font-weight: 700; color: #111; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge.green { background: #d1fae5; color: #065f46; }
        .badge.red { background: #fee2e2; color: #dc2626; }
        .badge.blue { background: #dbeafe; color: #1d4ed8; }
        .badge.enabled { background: #d1fae5; color: #065f46; }
        .badge.dry { background: #d1fae5; color: #065f46; }
        .badge.wet { background: #fee2e2; color: #dc2626; }
        .badge.clear { background: #d1fae5; color: #065f46; }
        .badge.connected { background: #dbeafe; color: #1d4ed8; }

        /* ---- CHART CARD ---- */
        .chart-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .chart-wide { grid-column: 1; }

        .card-title {
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title svg { width: 16px; height: 16px; fill: #6b7280; }

        /* ---- ALERTS ---- */
        .alerts-list { list-style: none; }
        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
            color: #374151;
        }
        .alert-item:last-child { border-bottom: none; }
        .alert-check {
            width: 18px; height: 18px;
            background: #d1fae5;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .alert-check svg { width: 10px; height: 10px; fill: #059669; }
        .alert-time { font-size: 11px; color: #9ca3af; }

        canvas { width: 100% !important; }

        /* ---- CONTROL PAGE ---- */
        .control-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .control-section-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .control-section-title svg { width: 16px; height: 16px; }

        .control-subtitle {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 18px;
        }

        .control-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 18px;
        }

        .ctrl-btn {
            border: 2px solid;
            border-radius: 10px;
            padding: 16px 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            transition: all 0.18s;
            background: none;
        }

        .ctrl-btn svg { width: 24px; height: 24px; }

        .ctrl-btn.retract {
            border-color: #f87171;
            color: #dc2626;
        }

        .ctrl-btn.retract:hover { background: #fee2e2; }

        .ctrl-btn.extend {
            border-color: #34d399;
            color: #059669;
        }

        .ctrl-btn.extend:hover { background: #d1fae5; }

        .ctrl-btn .btn-label { font-size: 13px; font-weight: 700; }
        .ctrl-btn .btn-sub { font-size: 11px; font-weight: 400; color: #9ca3af; }

        .position-banner {
            background: #fef9c3;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #92400e;
        }

        .position-banner .pos-label { font-weight: 700; }
        .position-banner .pos-angle { margin-left: auto; color: #6b7280; font-size: 12px; }

        /* Status table */
        .status-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
        }
        .status-row:last-child { border-bottom: none; }
        .status-label { color: #374151; }

        /* ---- ANALYTICS ---- */
        .analytics-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* ---- SETTINGS ---- */
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.18s;
        }

        .form-group input:focus,
        .form-group select:focus { border-color: #3b82f6; }

        .save-btn {
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.18s;
        }

        .save-btn:hover { opacity: 0.9; }

        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .toggle-label { font-size: 13px; color: #374151; }

        .toggle {
            position: relative;
            width: 42px; height: 24px;
        }

        .toggle input { opacity: 0; width: 0; height: 0; }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #d1d5db;
            border-radius: 24px;
            transition: 0.3s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 3px; bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }

        .toggle input:checked + .slider { background: #3b82f6; }
        .toggle input:checked + .slider:before { transform: translateX(18px); }

        @media (max-width: 900px) {
            .cards-4 { grid-template-columns: repeat(2, 1fr); }
            .chart-section, .control-grid, .analytics-charts, .settings-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2C10.3 2 8.8 2.9 8 4.3 6.3 4.7 5 6.2 5 8c0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-1.8-1.2-3.3-3-3.7C15.2 2.9 13.7 2 12 2zM9 18l-4 4h14l-4-4H9z"/></svg>
            </div>
            <div>
                <span>Rain<br>Monitor</span>
            </div>
        </div>

        <nav>
            <a href="dashboard.php" class="<?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                Dashboard
            </a>
            <a href="analytics.php" class="<?= ($activePage ?? '') === 'analytics' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M5 9.2h3V19H5V9.2zM10.6 5h2.8v14h-2.8V5zm5.6 8H19v6h-2.8v-6z"/></svg>
                Analytics
            </a>
            <a href="control.php" class="<?= ($activePage ?? '') === 'control' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M12 15.5c-1.93 0-3.5-1.57-3.5-3.5S10.07 8.5 12 8.5s3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5zM19.43 12.97c.04-.32.07-.64.07-.97 0-.33-.03-.66-.07-1l2.11-1.63c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64L4.57 11c-.04.34-.07.67-.07 1 0 .33.03.65.07.97l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.58 1.69-.98l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.66z"/></svg>
                Control
            </a>
            <a href="settings.php" class="<?= ($activePage ?? '') === 'settings' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                Settings
            </a>
        </nav>

        <div class="connected-badge">
            <div class="dot"></div>
            Connected
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></div>
            <div class="right">
                <span class="last-update">Last update: <span id="lastUpdateTime">--</span></span>
                <button class="refresh-btn" onclick="location.reload()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="#555"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                    Refresh
                </button>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="content">
