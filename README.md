# Rain Detection and Control System

---

## 📁 Project Files

```
rain_system/
├── login.php            ← Login page
├── register.php         ← Create account page
├── dashboard.php        ← Main dashboard (temperature, humidity, charts, alerts)
├── analytics.php        ← Analytics page (historical data, stats)
├── control.php          ← Manual control page (retract/extend clothes)
├── settings.php         ← User settings & system config
├── logout.php           ← Logout
├── db.php               ← Database configuration
├── layout_top.php       ← Shared sidebar layout (top)
├── layout_bottom.php    ← Shared sidebar layout (bottom)
├── database.sql         ← MySQL database schema
├── arduino_esp32.ino    ← ESP32 Arduino firmware
└── api/
    ├── sensor_data.php  ← ESP32 sends data here (HTTP endpoint)
    └── get_status.php   ← Returns latest status as JSON
```

---

## 🖥️ Web Dashboard Setup (XAMPP/WAMP/LAMP)

### Step 1 — Install XAMPP
Download from: https://www.apachefriends.org/
Start **Apache** and **MySQL** from the XAMPP Control Panel.

### Step 2 — Copy Project Files
Copy the entire `rain_system/` folder to:
- **Windows:** `C:\xampp\htdocs\rain_system\`
- **Linux/Mac:** `/opt/lampp/htdocs/rain_system/`

### Step 3 — Create Database
1. Open your browser → go to `http://localhost/phpmyadmin`
2. Click **New** → create database named `rain_system`
3. Click the `rain_system` database → click **Import** tab
4. Select `database.sql` → click **Go**

### Step 4 — Configure Database Connection
Open `db.php` and update if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Your MySQL username
define('DB_PASS', '');         // Your MySQL password (empty by default in XAMPP)
define('DB_NAME', 'rain_system');
```

### Step 5 — Open Dashboard
Go to: `http://localhost/rain_system/login.php`

**Default login credentials:**
- Username: `Admin`
- Password: `password`

---

## ⚡ ESP32 Arduino Setup

### Required Libraries (install via Arduino IDE → Library Manager)
- `DHT sensor library` by Adafruit
- `Adafruit Unified Sensor`
- `ESP32Servo`
- `LiquidCrystal_I2C`

### Configuration (in `arduino_esp32.ino`)
```cpp
const char* ssid     = "YOUR_WIFI_NAME";      // ← Change this
const char* password = "YOUR_WIFI_PASSWORD";  // ← Change this

// Set your computer's local IP (shown in XAMPP or run ipconfig)
const String serverURL = "http://192.168.1.100/rain_system/api/sensor_data.php";
//                                  ↑ Change this to your computer's IP
```

### Find Your Computer's IP
- **Windows:** Open Command Prompt → type `ipconfig` → look for IPv4 Address
- **Linux/Mac:** Open Terminal → type `ifconfig` → look for inet address

### Hardware Wiring
| Component        | ESP32 Pin |
|-----------------|-----------|
| Rain Sensor OUT | D34       |
| DHT11 DATA      | D4        |
| Servo Signal    | D18       |
| Green LED (+)   | D26       |
| Red LED (+)     | D27       |
| Buzzer (+)      | D25       |
| LCD SDA         | D21       |
| LCD SCL         | D22       |

---

## 🔗 ESP32 → Server API

The ESP32 sends data to:
```
GET http://YOUR_IP/rain_system/api/sensor_data.php?rain=DRY&temp=27.5&humi=61.0
```

Parameters:
- `rain` = `RAINING` or `DRY`
- `temp` = temperature in Celsius (e.g., `27.5`)
- `humi` = humidity in % (e.g., `61.0`)

---

## 📊 Database Tables (from ERD)

| Table | Description |
|-------|-------------|
| `users` | User accounts (id, name, email, password, created_at) |
| `sensor_readings` | Sensor data from ESP32 (rain_status, temperature, humidity) |
| `commands` | User commands and system events |
| `system_status` | Current clothesline position |

---

## 🌐 Dashboard Pages

| Page | URL |
|------|-----|
| Login | `/login.php` |
| Register | `/register.php` |
| Dashboard | `/dashboard.php` |
| Analytics | `/analytics.php` |
| Control | `/control.php` |
| Settings | `/settings.php` |
