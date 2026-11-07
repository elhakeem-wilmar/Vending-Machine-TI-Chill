/*
  ========================================================================
  SMART VENDING MACHINE - ESP32 CONTROLLER
  ========================================================================
  Board: ESP32 Dev Module
  Library yang diperlukan (install via Arduino IDE Library Manager):
  1. ESP32Servo (oleh Kevin Harrington)
  2. ArduinoJson (oleh Benoit Blanchon - versi 6 atau 7)
  
  Mekanisme:
  - ESP32 terhubung ke WiFi.
  - Melakukan polling ke server PHP (AnymHost) tiap 2.5 detik.
  - Jika ada order yang sudah dibayar (PAID), server merespon dengan slot_number & order_id.
  - ESP32 memutar servo slot terkait (0° -> 180° -> 0°) untuk mengeluarkan minuman.
  - ESP32 mengirim konfirmasi ke server bahwa minuman selesai dikeluarkan.
  ========================================================================
*/

#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <ESP32Servo.h>
#include <ArduinoJson.h>

// ----------------- KONFIGURASI WIFI & SERVER -----------------
const char* WIFI_SSID     = "NAMA_WIFI_ANDA";
const char* WIFI_PASSWORD = "PASSWORD_WIFI_ANDA";

// URL Host Server Anda di AnymHost (Gunakan http:// atau https://)
// Contoh: "https://vending.domainanda.com"
const String SERVER_BASE_URL = "http://YOUR_ANYMHOST_DOMAIN_OR_IP";

// API Key yang sama dengan MACHINE_API_KEY di config.php
const String MACHINE_API_KEY = "VM_SECRET_TOKEN_2026_XYZ";

// ----------------- PIN SERVO -----------------
// Sambungkan kabel sinyal (kuning/oranye) tiap servo ke GPIO berikut:
#define PIN_SERVO_SLOT_1 13
#define PIN_SERVO_SLOT_2 12
#define PIN_SERVO_SLOT_3 14
#define PIN_SERVO_SLOT_4 27

// LED Indikator status (Bawaan ESP32 biasanya GPIO 2)
#define PIN_LED_STATUS   2

// Objek Servo
Servo servo1;
Servo servo2;
Servo servo3;
Servo servo4;

// Interval Polling (2500 milidetik = 2.5 detik)
const unsigned long POLL_INTERVAL = 2500;
unsigned long lastPollTime = 0;

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n[SYSTEM] Memulai Smart Vending Machine ESP32...");

  pinMode(PIN_LED_STATUS, OUTPUT);
  digitalWrite(PIN_LED_STATUS, LOW);

  // Inisialisasi Servo (Frekuensi standar 50Hz, pulse 500us - 2400us untuk SG90/MG995)
  ESP32PWM::allocateTimer(0);
  ESP32PWM::allocateTimer(1);
  ESP32PWM::allocateTimer(2);
  ESP32PWM::allocateTimer(3);

  servo1.setPeriodHertz(50);
  servo2.setPeriodHertz(50);
  servo3.setPeriodHertz(50);
  servo4.setPeriodHertz(50);

  servo1.attach(PIN_SERVO_SLOT_1, 500, 2400);
  servo2.attach(PIN_SERVO_SLOT_2, 500, 2400);
  servo3.attach(PIN_SERVO_SLOT_3, 500, 2400);
  servo4.attach(PIN_SERVO_SLOT_4, 500, 2400);

  // Set posisi awal servo ke 0 derajat (menutup pintu/stopper)
  servo1.write(0);
  servo2.write(0);
  servo3.write(0);
  servo4.write(0);

  // Hubungkan ke WiFi
  connectWiFi();
}

void loop() {
  // Pastikan koneksi WiFi tetap aktif
  if (WiFi.status() != WL_CONNECTED) {
    digitalWrite(PIN_LED_STATUS, LOW);
    connectWiFi();
    return;
  }

  // Polling server berkala tanpa blocking millis
  unsigned long currentMillis = millis();
  if (currentMillis - lastPollTime >= POLL_INTERVAL) {
    lastPollTime = currentMillis;
    checkDispenseQueue();
  }
}

// Fungsi koneksi WiFi
void connectWiFi() {
  Serial.printf("[WIFI] Menghubungkan ke: %s", WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    digitalWrite(PIN_LED_STATUS, !digitalRead(PIN_LED_STATUS));
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n[WIFI] Berhasil Terhubung!");
    Serial.printf("[WIFI] IP Address: %s\n", WiFi.localIP().toString().c_str());
    digitalWrite(PIN_LED_STATUS, HIGH); // LED menyala tanda standby & online
  } else {
    Serial.println("\n[WIFI] Gagal terhubung, akan mencoba kembali...");
  }
}

// Fungsi polling ke server PHP
void checkDispenseQueue() {
  HTTPClient http;
  String url = SERVER_BASE_URL + "/api/dispense_queue.php?api_key=" + MACHINE_API_KEY;

  if (SERVER_BASE_URL.startsWith("https://")) {
    WiFiClientSecure secureClient;
    secureClient.setInsecure(); // Mengabaikan validasi SSL fingerprint cPanel/Let's Encrypt
    http.begin(secureClient, url);
  } else {
    WiFiClient client;
    http.begin(client, url);
  }

  http.addHeader("X-Machine-Key", MACHINE_API_KEY);
  http.setTimeout(4000);

  int httpCode = http.GET();

  if (httpCode == HTTP_CODE_OK) {
    String payload = http.getString();
    // Parse JSON Response
    StaticJsonDocument<512> doc;
    DeserializationError error = deserializeJson(doc, payload);

    if (!error) {
      bool hasJob = doc["has_job"] | false;

      if (hasJob) {
        String orderId = doc["order_id"].as<String>();
        int slotNumber = doc["slot_number"] | 0;

        Serial.printf("[JOB] Ada pesanan siap! Order ID: %s, Slot: %d\n", orderId.c_str(), slotNumber);

        // Putar servo sesuai slot
        dispenseDrink(slotNumber);

        // Kirim konfirmasi penyelesaian ke server
        sendDispenseConfirmation(orderId);
      }
    } else {
      Serial.printf("[ERROR] Gagal parse JSON dari server: %s\n", error.c_str());
    }
  } else if (httpCode > 0) {
    Serial.printf("[HTTP] Server merespon kode: %d\n", httpCode);
  } else {
    Serial.printf("[HTTP] Gagal konek ke server (Error: %s)\n", http.errorToString(httpCode).c_str());
  }

  http.end();
}

// Fungsi menggerakkan motor servo
void dispenseDrink(int slotNumber) {
  Serial.printf("[SERVO] Memutar motor servo pada Slot %d...\n", slotNumber);

  // Kedipkan LED cepat saat memutar servo
  for (int i = 0; i < 4; i++) {
    digitalWrite(PIN_LED_STATUS, LOW);
    delay(50);
    digitalWrite(PIN_LED_STATUS, HIGH);
    delay(50);
  }

  Servo* targetServo = nullptr;
  switch (slotNumber) {
    case 1: targetServo = &servo1; break;
    case 2: targetServo = &servo2; break;
    case 3: targetServo = &servo3; break;
    case 4: targetServo = &servo4; break;
    default:
      Serial.printf("[SERVO ERROR] Nomor slot tidak valid: %d\n", slotNumber);
      return;
  }

  // Putar perlahan dari 0 ke 180 derajat (membuka mekanisme pendorong)
  for (int pos = 0; pos <= 180; pos += 5) {
    targetServo->write(pos);
    delay(15);
  }

  // Tahan sejenak agar minuman jatuh sempurna
  delay(1200);

  // Kembalikan ke posisi semula 0 derajat
  for (int pos = 180; pos >= 0; pos -= 5) {
    targetServo->write(pos);
    delay(15);
  }

  Serial.printf("[SERVO] Minuman di Slot %d selesai dikeluarkan!\n", slotNumber);
}

// Fungsi konfirmasi balik ke server setelah servo selesai berputar
void sendDispenseConfirmation(String orderId) {
  HTTPClient http;
  String url = SERVER_BASE_URL + "/api/dispense_confirm.php";

  if (SERVER_BASE_URL.startsWith("https://")) {
    WiFiClientSecure secureClient;
    secureClient.setInsecure();
    http.begin(secureClient, url);
  } else {
    WiFiClient client;
    http.begin(client, url);
  }

  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Machine-Key", MACHINE_API_KEY);

  // Payload konfirmasi
  StaticJsonDocument<200> reqDoc;
  reqDoc["order_id"] = orderId;
  reqDoc["api_key"] = MACHINE_API_KEY;

  String requestBody;
  serializeJson(reqDoc, requestBody);

  int httpCode = http.POST(requestBody);

  if (httpCode == HTTP_CODE_OK) {
    Serial.printf("[CONFIRM] Konfirmasi berhasil dikirim ke server untuk %s\n", orderId.c_str());
  } else {
    Serial.printf("[CONFIRM ERROR] Gagal mengirim konfirmasi. HTTP Code: %d\n", httpCode);
  }

  http.end();
}

