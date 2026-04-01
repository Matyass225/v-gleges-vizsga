#include <Wire.h>
#include <DHT.h>

#define SLAVE_ADDRESS 0x04     // I2C slave cím (Raspberry-n: i2cdetect -y 1)
#define DHTPIN 4               // DHT szenzor kimenete az Arduino 4-es pinjén
#define DHTTYPE DHT11          // DHT11-et használsz (DHT22-höz írd át DHT22-t)

DHT dht(DHTPIN, DHTTYPE);

// Vezérlő flag: 1 = mindig random adatokat generál (teszteléshez, szenzor nélkül)
//               0 = normál működés (DHT olvasás + fallback random hibánál)
#define SIMULATE_SENSOR 0

// Globális változók az adatok tárolására
volatile float lastTemp = 0.0;
volatile float lastHum = 0.0;
char dataBuffer[32]; // Elég nagy a "25.50,60.10" formátumhoz

void setup() {
  Serial.begin(9600);

  // I2C inicializálása
  Wire.begin(SLAVE_ADDRESS);
  Wire.onRequest(sendData); // Akkor fut le, ha a Pi adatot kér

  dht.begin();

  // Random generátor inicializálása (csak ha használjuk)
  randomSeed(analogRead(0)); // Analóg bemenet zajából seed

  Serial.println("I2C DHT11 Szenzor elindult...");
  if (SIMULATE_SENSOR==1) {
    Serial.println("SIMULATE_SENSOR aktív: mindig random adatokat küldök!");
  }
}

void loop() {
  if (SIMULATE_SENSOR==1) {
    // Mindig random adatok (teszt mód)
    lastTemp = random(1800, 3201) / 100.0;  // 18.00 .. 32.00 °C
    lastHum  = random(3500, 8501) / 100.0;  // 35.00 .. 85.00 %
    
    Serial.print("Szimulált adatok: ");
    Serial.print(lastTemp, 2);
    Serial.print(" C, ");
    Serial.print(lastHum, 2);
    Serial.println(" %");
  } 
  else {
    // Normál DHT olvasás
    float h = dht.readHumidity();
    float t = dht.readTemperature();

    // Csak akkor frissítünk, ha érvényes az adat (nem NaN)
    if (!isnan(h) && !isnan(t)) {
      lastTemp = t;
      lastHum  = h;

      // Debug kiírás a soros monitorra
      Serial.print("Szenzor: ");
      Serial.print(lastTemp, 2);
      Serial.print(" C, ");
      Serial.print(lastHum, 2);
      Serial.println(" %");
    } 
    else {
      // Fallback: ha hibás az olvasás (szenzor kihúzva, hibás csatlakozás stb.)
      lastTemp = random(1800, 3201) / 100.0;  // 18.00 .. 32.00 °C
      lastHum  = random(3500, 8501) / 100.0;  // 35.00 .. 85.00 %
      
      Serial.println("Hiba: Nem sikerült olvasni a DHT szenzort! → Fallback random adatok:");
      Serial.print("Temp: ");
      Serial.print(lastTemp, 2);
      Serial.print(" C, Hum: ");
      Serial.print(lastHum, 2);
      Serial.println(" %");
    }
  }

  delay(2000); // DHT lassú, 2 mp-enként olvassunk
}

// Ez a függvény fut le, amikor a Raspberry Pi kéri az adatot
void sendData() {
  // Ha még sosem volt érvényes adat (pl. első hívás előtt)
  if (lastTemp == 0.0 && lastHum == 0.0) {
    Wire.write("0.00,0.00");
    return;
  }

  // Float → string konvertálás (5 szélesség, 2 tizedes)
  char tStr[8];
  char hStr[8];
  dtostrf(lastTemp, 5, 2, tStr);
  dtostrf(lastHum, 5, 2, hStr);

  // Összefűzés: "TEMP,HUM" formátum (pl: "24.50,55.00")
  memset(dataBuffer, 0, sizeof(dataBuffer)); // Puffer ürítése
  sprintf(dataBuffer, "%s,%s", tStr, hStr);

  Wire.write(dataBuffer);
}
