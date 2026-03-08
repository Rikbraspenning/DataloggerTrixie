# Jan Moeskops - 03/12/2024
# Updated voor eenmalige uitlezing met validatie en MySQL-integratie
# indien de gemeten waarden buiten de normen vallen wordt er opnieuw gemeten

import board
import adafruit_dht
import mysql.connector
from datetime import datetime

# Sensor configuratie
sensor = adafruit_dht.DHT22(board.D22)  # DHT22 verbonden met GPIO 22
sensorNr = "DHT22"  # Naam van de sensor

# MySQL configuratie
db_config = {
    "host": "localhost",
    "user": "logger",
    "passwd": "paswoord",
    "db": "temperatures"
}

# Database connectie maken
def connect_to_db():
    try:
        db = mysql.connector.connect(**db_config)
        return db
    except mysql.connector.Error as err:
        print(f"Fout bij verbinden met database: {err}")
        return None

# Sensor uitlezen met validatie (maximaal 5 pogingen)
def read_sensor_with_validation(max_attempts=5):
    attempts = 0
    while attempts < max_attempts:
        try:
            temperature_c = sensor.temperature
            humidity = sensor.humidity

            # Validatie van de waarden
            if temperature_c is not None and humidity is not None:
                if temperature_c >= 0 and 0 <= humidity <= 100:
                    return float("{:.1f}".format(temperature_c)), float("{:.1f}".format(humidity))
                else:
                    print(f"Ongeldige waarden gemeten: Temp={temperature_c}ºC, Humidity={humidity}%")
            else:
                print("Sensor retourneerde geen waarden.")

        except RuntimeError as error:
            print(f"Fout bij uitlezen sensor: {error.args[0]}")

        attempts += 1
        print(f"Opnieuw proberen... ({attempts}/{max_attempts})")
    return None, None

# Data uitlezen en opslaan in database
def log_sensor_data():
    temperature_c, humidity = read_sensor_with_validation()

    if temperature_c is None or humidity is None:
        print("Geen geldige waarden na meerdere pogingen. Geen data opgeslagen.")
        return

    datetime_now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{datetime_now}] Geldige waarden: Temp={temperature_c}ºC, Humidity={humidity}%")

    db = connect_to_db()
    if db is None:
        print("Database connectie mislukt. Probeer later opnieuw.")
        return

    cursor = db.cursor()
    sql = """
    INSERT INTO temperaturedata (dateandtime, sensor, temperature, humidity)
    VALUES (%s, %s, %s, %s)
    """
    values = (datetime_now, sensorNr, temperature_c, humidity)

    try:
        cursor.execute(sql, values)
        db.commit()
        print("Data succesvol opgeslagen in database.")
    except mysql.connector.Error as err:
        db.rollback()
        print(f"Fout bij schrijven naar database: {err}")
    finally:
        cursor.close()
        db.close()

# Script uitvoeren
if __name__ == "__main__":
    log_sensor_data()
