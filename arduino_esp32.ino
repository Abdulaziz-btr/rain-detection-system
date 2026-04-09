/*
 * =============================================
 * Rain Detection and Control System for Outdoor Laundry
 * Case Study: MKU Hostels
 * Student: Butera Abdulaziz 
 * Mount Kigali University - March 2026
 * =============================================
 *
 * Hardware:
 *   - ESP32 Microcontroller
 *   - Rain Sensor (Digital pin)
 *   - DHT11 Temperature & Humidity Sensor
 *   - SG90 Servo Motor (clothesline control)
 *   - 16x2 LCD with I2C interface
 *   - LED indicators (Green = clear, Red = rain)
 *   - Buzzer (alarm on rain detection)
 *
 * Libraries required (install via Arduino Library Manager):
 *   - DHT sensor library by Adafruit
 *   - ESP32Servo
 *   - LiquidCrystal_I2C
 *   - WiFi (built-in with ESP32)
 *   - HTTPClient (built-in with ESP32)
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>
#include <ESP32Servo.h>
#include <LiquidCrystal_I2C.h>

// ---- WiFi Configuration ----
const char* ssid     = "YOUR_WIFI_NAME";       // Change to your WiFi SSID
const char* password = "YOUR_WIFI_PASSWORD";   // Change to your WiFi password

// ---- Server Configuration ----
// Change to your server IP/domain where rain_system is hosted
const String serverURL = "http://192.168.1.100/rain_system/api/sensor_data.php";

// ---- Pin Configuration ----
#define RAIN_SENSOR_PIN   34    // Rain sensor digital output (D34)
#define DHT_PIN           4     // DHT11 data pin (D4)
#define SERVO_PIN         18    // Servo motor signal pin (D18)
#define LED_GREEN_PIN     26    // Green LED - No Rain
#define LED_RED_PIN       27    // Red LED - Rain Detected
#define BUZZER_PIN        25    // Buzzer pin

// ---- DHT11 Setup ----
#define DHT_TYPE DHT11
DHT dht(DHT_PIN, DHT_TYPE);

// ---- Servo Setup ----
Servo clothesServo;
#define SERVO_EXTEND  180   // Clothes outside (drying)
#define SERVO_RETRACT 0     // Clothes inside (sheltered)

// ---- LCD Setup (I2C: SDA=21, SCL=22) ----
LiquidCrystal_I2C lcd(0x27, 16, 2);

// ---- State Variables ----
bool isRaining       = false;
bool wasRaining      = false;
bool autoMode        = true;
int  servoPosition   = SERVO_EXTEND;
unsigned long lastSendTime = 0;
const unsigned long SEND_INTERVAL = 5000; // Send data every 5 seconds

// ---- Setup ----
void setup() {
    Serial.begin(115200);
    Serial.println("\n\n=== Rain Detection System Starting ===");
    Serial.println("MKU Hostel - RUGAMBA Saidi");

    // Init pins
    pinMode(RAIN_SENSOR_PIN, INPUT);
    pinMode(LED_GREEN_PIN, OUTPUT);
    pinMode(LED_RED_PIN, OUTPUT);
    pinMode(BUZZER_PIN, OUTPUT);

    // Start DHT
    dht.begin();

    // Start LCD
    lcd.init();
    lcd.backlight();
    lcd.setCursor(0, 0);
    lcd.print("Rain Detection");
    lcd.setCursor(0, 1);
    lcd.print("System Starting");
    delay(2000);

    // Init Servo - start extended (outside)
    clothesServo.attach(SERVO_PIN);
    clothesServo.write(SERVO_EXTEND);
    servoPosition = SERVO_EXTEND;

    // Connect to WiFi
    connectWiFi();

    // Initial display
    updateLCD("System Ready", "Clothes: Outside");
    Serial.println("=== System Ready ===\n");
}

// ---- Main Loop ----
void loop() {
    // Read sensors
    int rainRaw  = digitalRead(RAIN_SENSOR_PIN);
    isRaining    = (rainRaw == LOW);  // LOW = rain detected (active low)

    float temperature = dht.readTemperature();
    float humidity    = dht.readHumidity();

    // Validate DHT readings
    if (isnan(temperature)) temperature = 0.0;
    if (isnan(humidity))    humidity    = 0.0;

    // Print to Serial Monitor
    Serial.print("Rain: ");
    Serial.print(isRaining ? "RAINING" : "DRY");
    Serial.print(" | Temp: ");
    Serial.print(temperature);
    Serial.print("°C | Humidity: ");
    Serial.print(humidity);
    Serial.println("%");

    // ----- Auto Control Logic -----
    if (autoMode) {
        if (isRaining && !wasRaining) {
            // Rain just started - retract clothes
            Serial.println(">> RAIN DETECTED - Retracting clothes...");
            retractClothes();
            triggerAlarm();
        } else if (!isRaining && wasRaining) {
            // Rain stopped - extend clothes
            Serial.println(">> Rain stopped - Extending clothes...");
            extendClothes();
        }
    }

    wasRaining = isRaining;

    // Update indicators
    updateLEDs();
    updateLCD(temperature, humidity);

    // Send data to server every SEND_INTERVAL milliseconds
    unsigned long now = millis();
    if (now - lastSendTime >= SEND_INTERVAL) {
        lastSendTime = now;
        if (WiFi.status() == WL_CONNECTED) {
            sendToServer(isRaining, temperature, humidity);
        } else {
            Serial.println("WiFi disconnected - attempting reconnect...");
            connectWiFi();
        }
    }

    delay(500);
}

// ---- Retract Clothes (inside/sheltered) ----
void retractClothes() {
    clothesServo.write(SERVO_RETRACT);
    servoPosition = SERVO_RETRACT;
    Serial.println("Servo: Retracted (0°)");
}

// ---- Extend Clothes (outside/drying) ----
void extendClothes() {
    clothesServo.write(SERVO_EXTEND);
    servoPosition = SERVO_EXTEND;
    Serial.println("Servo: Extended (180°)");
}

// ---- Alarm (buzzer beep on rain) ----
void triggerAlarm() {
    for (int i = 0; i < 3; i++) {
        digitalWrite(BUZZER_PIN, HIGH);
        delay(300);
        digitalWrite(BUZZER_PIN, LOW);
        delay(200);
    }
}

// ---- Update LED Indicators ----
void updateLEDs() {
    if (isRaining) {
        digitalWrite(LED_GREEN_PIN, LOW);
        digitalWrite(LED_RED_PIN, HIGH);
    } else {
        digitalWrite(LED_GREEN_PIN, HIGH);
        digitalWrite(LED_RED_PIN, LOW);
    }
}

// ---- Update LCD Display ----
void updateLCD(float temp, float humi) {
    lcd.clear();
    lcd.setCursor(0, 0);
    if (isRaining) {
        lcd.print("RAIN! Retracting");
    } else {
        lcd.print("T:");
        lcd.print(temp, 1);
        lcd.print("C H:");
        lcd.print(humi, 0);
        lcd.print("%");
    }
    lcd.setCursor(0, 1);
    lcd.print(servoPosition == SERVO_RETRACT ? "Pos: Inside    " : "Pos: Outside   ");
}

void updateLCD(String line1, String line2) {
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print(line1);
    lcd.setCursor(0, 1);
    lcd.print(line2);
}

// ---- Send Data to PHP Server ----
void sendToServer(bool rain, float temp, float humi) {
    HTTPClient http;
    String rainStr = rain ? "RAINING" : "DRY";
    String url = serverURL + "?rain=" + rainStr
                           + "&temp=" + String(temp, 1)
                           + "&humi=" + String(humi, 1);

    Serial.print("Sending: ");
    Serial.println(url);

    http.begin(url);
    int httpCode = http.GET();

    if (httpCode > 0) {
        String response = http.getString();
        Serial.print("Server response: ");
        Serial.println(response);

        // Check if server sent a command back
        if (response.indexOf("\"command\":\"retract\"") >= 0) {
            Serial.println("Manual command: RETRACT");
            retractClothes();
        } else if (response.indexOf("\"command\":\"extend\"") >= 0) {
            Serial.println("Manual command: EXTEND");
            extendClothes();
        }
    } else {
        Serial.print("HTTP error: ");
        Serial.println(http.errorToString(httpCode));
    }

    http.end();
}

// ---- WiFi Connection ----
void connectWiFi() {
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Connecting WiFi");

    WiFi.begin(ssid, password);
    Serial.print("Connecting to WiFi");

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500);
        Serial.print(".");
        attempts++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\nWiFi Connected!");
        Serial.print("IP Address: ");
        Serial.println(WiFi.localIP());

        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("WiFi Connected");
        lcd.setCursor(0, 1);
        lcd.print(WiFi.localIP().toString());
        delay(2000);
    } else {
        Serial.println("\nWiFi Failed! Running offline.");
        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("WiFi Failed");
        lcd.setCursor(0, 1);
        lcd.print("Offline Mode");
        delay(2000);
    }
}
