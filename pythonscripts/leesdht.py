# Complete Project Details: 
# https://RandomNerdTutorials.com/raspberry-pi-dht11-dht22-python/
# Based on Adafruit_CircuitPython_DHT Library Example
# Aangepast J. Moeskops 03/12/2024

import time
import board
import adafruit_dht
from datetime import datetime  # Toegevoegd om de huidige tijd te krijgen

sensor = adafruit_dht.DHT22(board.D22)  #DHT22 op Digit. Pin 22

while True:
    try:
        # Print the values to the serial port
        temperature_c = sensor.temperature
        humidity = sensor.humidity
        current_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")  # Formatteer de huidige tijd
        print("[{}] Temp={:0.1f}ºC, Humidity={:0.1f}%".format(current_time, temperature_c, humidity))

    except RuntimeError as error:
        # Errors happen fairly often, DHT's are hard to read, just keep going
        print(error.args[0])
        time.sleep(2.0)
        continue
    except Exception as error:
        sensor.exit()
        raise error

    time.sleep(3.0)
