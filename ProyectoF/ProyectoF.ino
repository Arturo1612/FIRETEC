// Incluir librerías necesarias
#include <DHT11.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <TinyGPS++.h>

// Configuración de red WiFi
const char* ssid = "";       // Cambia por el nombre de tu red WiFi
const char* password = "";                // Cambia por la contraseña de tu red WiFi

// Dirección del servidor PHP
const char* serverUrl = "";// Cambia por la IP de tu servidor

// Configuración del sensor DHT11
DHT11 dht11(4); // Pin GPIO del sensor DHT11

// Configuración del sensor MQ-7
const int AOUTpin = 22; // Pin analógico
const int DOUTpin = 23; // Pin digital
int coValue;            // Variable para el valor analógico de CO
int coLimit;            // Variable para el valor digital de CO
const int CO_THRESHOLD = 400; // Umbral de CO para activar GPS y envío de datos (ajustar según necesidades)

// Configuración del GPS
#define RXD2 16
#define TXD2 17
HardwareSerial neogps(1); // UART1 para el GPS
TinyGPSPlus gps;

// Función para enviar datos al servidor
void sendData(float temperature, float humidity, int coValue, int coLimit, float latitude, float longitude) {
  Serial.println("Datos enviados al servidor:");
  Serial.print("Temperatura: ");
  Serial.println(temperature);
  Serial.print("Humedad: ");
  Serial.println(humidity);
  Serial.print("CO Value: ");
  Serial.println(coValue);
  Serial.print("CO Limit: ");
  Serial.println(coLimit);
  Serial.print("Latitud: ");
  Serial.println(latitude, 6);
  Serial.print("Longitud: ");
  Serial.println(longitude, 6);

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    // Crear la cadena con los datos a enviar
    String postData = "temperatura=" + String(temperature) +
                      "&humedad=" + String(humidity) +
                      "&coValue=" + String(coValue) +
                      "&coLimit=" + String(coLimit) +
                      "&latitud=" + String(latitude, 6) +
                      "&longitud=" + String(longitude, 6);

    // Enviar datos al servidor
    int httpResponseCode = http.POST(postData);

    // Manejar respuesta del servidor
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Respuesta del servidor: " + response);
    } else {
      Serial.println("Error en la conexión HTTP: " + String(httpResponseCode));
    }

    http.end();
  } else {
    Serial.println("WiFi no conectado.");
  }
}

// Configuración inicial
void setup() {
  // Iniciar comunicación serial
  Serial.begin(115200);

  // Configurar pines del sensor MQ-7
  pinMode(DOUTpin, INPUT);

  // Configurar comunicación UART con el GPS
  neogps.begin(9600, SERIAL_8N1, RXD2, TXD2);

  // Conectar a la red WiFi
  Serial.println("Conectando a WiFi...");
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.print(".");
  }
  Serial.println("\nConectado a WiFi.");
}

// Bucle principal
void loop() {
  float latitude = 0.0, longitude = 0.0;

  // Leer datos del sensor MQ-7
  coValue = analogRead(AOUTpin);
  coLimit = digitalRead(DOUTpin);

  // Mostrar datos del sensor MQ-7 en el monitor serie
  Serial.print("CO Value: ");
  Serial.println(coValue);
  Serial.print("CO Limit: ");
  Serial.println(coLimit);

  // Si los niveles de CO superan el umbral, activar GPS y enviar datos
  if (coValue == 0) {
    Serial.println("ALERTA: Niveles altos de CO detectados.");

    // Activar GPS
    while (neogps.available()) {
      gps.encode(neogps.read());
    }

    if (gps.location.isValid()) {
      latitude = gps.location.lat();
      longitude = gps.location.lng();
      Serial.println("Datos GPS válidos:");
      Serial.print("Latitud: ");
      Serial.println(latitude, 6);
      Serial.print("Longitud: ");
      Serial.println(longitude, 6);
    } else {
      Serial.println("Sin señal GPS");
    }

    // Leer datos del sensor DHT11
    int temperature = 0, humidity = 0;
    int result = dht11.readTemperatureHumidity(temperature, humidity);

    if (result == 0) { // Si la lectura del DHT11 es válida
      Serial.println("Enviando datos al servidor...");
      sendData(temperature, humidity, coValue, coLimit, latitude, longitude);
    } else {
      Serial.println(DHT11::getErrorString(result));
    }

    Serial.println("---------------------------");
  } else {
    Serial.println("Niveles de CO dentro del rango normal.");
  }

  delay(1000); // Esperar antes de la siguiente iteración
}
