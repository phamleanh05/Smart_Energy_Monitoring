#include <PZEM004Tv30.h>
#include <WiFi.h>
#include <LiquidCrystal_I2C.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <PubSubClient.h>

// ThingsBoard settings
const char* mqtt_server = "demo.thingsboard.io";
const int mqtt_port = 1883;
const char* mqtt_user = "35m99q89riqc6s5ks49q";
const char* mqtt_password = "35m99q89riqc6s5ks49q";

// MQTT timing variables
unsigned long lastSend = 0;
const unsigned long sendInterval = 500; // Reduced to 500ms to ensure we get at least 1 reading per second

//======================================== SSID and Password of your WiFi router.
const char* ssid = "ATPHometech";
const char* password = "Fptsmarthome";

// const char* ssid = "iPhone ATP";
// const char* password = "12345678";
//========================================

//======================================== Variables for HTTP POST request data.
String postData = ""; //--> Variables sent for HTTP POST request data.
String payload = "";  //--> Variable for receiving response from HTTP POST.
//======================================== 


//======================================== PZEM Serial pins
#define PZEM_RX_PIN 16
#define PZEM_TX_PIN 17
//========================================

//======================================== Initialize PZEM sensor
PZEM004Tv30 pzem(Serial2, PZEM_RX_PIN, PZEM_TX_PIN);
//========================================

//======================================== Initialize lcd
LiquidCrystal_I2C lcd(0x27, 16, 2);
//========================================

//======================================== Global variables to store sensor data
float voltage = 0.0;
float current = 0.0;
float power = 0.0;
float energy = 0.0;
String send_Status_Read_pzem = "";
unsigned long lastMillis = 0;  // For energy calculation
//========================================

// Initialize MQTT client
WiFiClient espClient;
PubSubClient client(espClient);

// Callback function for MQTT messages
void callback(char* topic, byte* payload, unsigned int length) {
  // Handle incoming messages if needed
}

//======================================== Read and get data from pzem.
void get_pzem_data(){
  
  Serial.println();
  Serial.println("-------------get_pzem_data()");
    // Read voltage and current values
  float v = pzem.voltage();
  float c = pzem.current();
 
  // Update global variables atomically
  voltage = v;
  current = c;
  
  // Calculate power (P = V × I)
  power = voltage * current;
  
  // Calculate energy (kWh = P × time)
  unsigned long currentMillis = millis();
  if (lastMillis > 0) {  // Skip first reading
    float hoursPassed = (currentMillis - lastMillis) / 3600000.0;  // Convert milliseconds to hours
    energy += (power * hoursPassed) / 1000.0;  // Convert watt-hours to kilowatt-hours
  }
  lastMillis = currentMillis;

  // Check if any reads failed.
  if (isnan(voltage) || isnan(current)) {
    Serial.println("Failed to read from Pzem sensor!");
    voltage = 0.0;
    current = 0.0;
    power = 0.0;
    send_Status_Read_pzem = "FAILED";
  } else {
    send_Status_Read_pzem = "SUCCEED";
  }

  // Serial output
  Serial.print("Voltage: "); Serial.print(voltage); Serial.println("V");
  Serial.print("Current: "); Serial.print(current); Serial.println("A");
  Serial.print("Power: "); Serial.print(power); Serial.println("W");
  Serial.print("Energy: "); Serial.print(energy,3); Serial.println("kWh");
  Serial.println("-------------------");
}
//========================================

void setup() {
  Serial.begin(115200);
  
  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nConnected to WiFi");

  // Initialize MQTT
  client.setServer(mqtt_server, mqtt_port);
  client.setCallback(callback);

  // Connect to MQTT Broker
  while (!client.connected()) {
    if (client.connect("ESP32Client", mqtt_user, mqtt_password)) {
      Serial.println("Connected to MQTT");
    } else {
      Serial.print("Failed to connect to MQTT, retrying in 1 second...");
      delay(1000);
    }
  }

  // Start LCD
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("PZEM Monitor");
  delay(1000);
}

void reconnectMQTT() {
  while (!client.connected()) {
    if (client.connect("ESP32Client", mqtt_user, mqtt_password)) {
      Serial.println("Reconnected to MQTT");
    } else {
      Serial.print("Failed to reconnect to MQTT, retrying in 1 second...");
      delay(1000);  // Reduced from 5000 to 1000
    }
  }
}

void sendDataToThingsBoard() {
  if (!client.connected()) {
    reconnectMQTT();
  }

  // Create payload string with proper formatting for all values
  String payload = "{";
  payload += "\"voltage\":" + String(voltage, 2) + ",";      // 2 decimal places
  payload += "\"current\":" + String(current, 3) + ",";      // 3 decimal places for more precision
  payload += "\"power\":" + String(power, 2) + ",";         // 2 decimal places
  payload += "\"energy_consumption\":" + String(energy, 3);  // 3 decimal places
  payload += "}";
  
  // Debug print
  Serial.println("Sending to ThingsBoard: " + payload);
                  
  // Send data to ThingsBoard
  client.publish("v1/devices/me/telemetry", (char*) payload.c_str());
}

void loop() {
  unsigned long currentMillis = millis();
  
  if (!client.connected()) {
    reconnectMQTT();
  }
  client.loop();

  // Calls the get_pzem_data().
  get_pzem_data();

  // Send data to ThingsBoard
  if (currentMillis - lastSend >= sendInterval) {
    sendDataToThingsBoard();
    lastSend = currentMillis;
  }

  //---------------------------------------- Check WiFi connection status.
  if(WiFi.status()== WL_CONNECTED) {
    HTTPClient http;
    int httpCode;

    //........................................ The process of sending the DHT11 sensor data to the database.
    String postData = "{";
    postData += "\"device_name\":\"esp32_01\",";
    postData += "\"voltage\":" + String(voltage) + ",";
    postData += "\"current\":" + String(current) + ",";
    postData += "\"power\":" + String(power) + ",";
    postData += "\"energy_consumed\":" + String(energy) + ",";
    postData += "\"status_read_sensor_pzem\":\"" + String(send_Status_Read_pzem) + "\"";
    postData += "}";
    
    payload = "";
  
    Serial.println();
    Serial.println("---------------update_data.php");
    http.begin("http://192.168.1.85/ESP32_Mysql/update_data.php");  //--> in home wifi
    //http.begin("http://172.20.10.2/ESP32_Mysql/update_data.php"); //--> host post
    http.addHeader("Content-Type", "application/json");  //--> Specify content-type header
   
    httpCode = http.POST(postData); //--> Send the request
    payload = http.getString();  //--> Get the response payload
  
    Serial.print("httpCode : ");
    Serial.println(httpCode); //--> Print HTTP return code
    Serial.print("payload  : ");
    Serial.println(payload);  //--> Print request response payload
    Serial.println("POST data:");
    Serial.println(postData);  // In dữ liệu đang gửi

    http.end();  //Close connection
    Serial.println("---------------");
    //........................................ 
    
    delay(1000);  // Reduced to 100ms to make the loop more responsive
  }
  
  // LCD output
  lcd.setCursor(0, 0);
  lcd.print("V:");
  lcd.print(voltage);

  lcd.setCursor(0, 1);
  lcd.print("I:");
  lcd.print(current);

  lcd.setCursor(9, 0);
  lcd.print("P:");
  lcd.print(power);

  lcd.setCursor(9, 1);
  lcd.print("E:");
  lcd.print(energy);

  delay(1000);  // Reduced to 100ms to make the loop more responsive
}
