import requests
import smbus
import time

apikey = "123456789"
SLAVE_ADDRESS = 0x04  # Ugyanaz, mint az Arduino kódban
bus = smbus.SMBus(1)  # I2C bus 1 a Pi-n

def send_data_to_api(temperature, humidity):
    url = f'http://192.168.0.181/sensor/insert_data_apikey.php?api_key={apikey}&temperature={temperature}&humidity={humidity}'
    print(f"Küldési URL: {url}")
    try:
        response = requests.get(url, timeout=5) # 5 másodperc időtúllépés
        if response.status_code == 200:
            print(f"Sikeres API küldés! Válasz: {response.text}")
        else:
            print(f"API hiba: {response.status_code}")
    except Exception as e:
        print(f"Hálózati hiba a küldés során: {e}")

print("Adatgyűjtés indítása...")

while True:
    try:
        # 20 bájt beolvasása az Arduinótól
        data = bus.read_i2c_block_data(SLAVE_ADDRESS, 0, 20)
        
        # Byte-ok tisztítása: kiszűrjük a 0 (null) és 255 (üres I2C) értékeket
        line = "".join(chr(byte) for byte in data if 32 <= byte <= 126).strip()
        
        print(f"Nyers adat: {line}")

        if "," in line:
            # Szétválasztjuk a vessző mentén (pl. "24.50,55.00")
            parts = line.split(",")
            
            # Megpróbáljuk számmá alakítani
            temperature = float(parts[0])
            humidity = float(parts[1])
            
            print(f"Feldolgozva: Temp: {temperature} C, Hum: {humidity} %")
            
            # Adatok küldése az API-nak
            send_data_to_api(temperature, humidity)
            
        else:
            print("Hiba: Nem érkezett érvényes adat (vessző hiányzik)")

    except Exception as e:
        print(f"I2C hiba: {e}")
    
    # 10 másodperc várakozás a következő mérésig
    time.sleep(10)
