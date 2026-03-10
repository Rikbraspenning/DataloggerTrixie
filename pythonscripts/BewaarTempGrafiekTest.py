#!/usr/bin/python3

#  Jan Moeskops - 22 september 2021
#  MatplotlibTest2 : test toegang tot de database
#   indien 4 keer per uur temperatuur gelogd wordt,
#   dan zijn er 96 meetresultaten per dag
#   toon de laatste dag,  zet foto in /var/www/html als sudo

import mysql.connector
import matplotlib.pyplot as plt
import time
import shutil

# Zoek datum vandaag
vandaag=time.strftime("%d-%m-%Y, %H:%M")
# vandaag=time.strftime("%Y-%m-%d")
print(vandaag)

# connect to MySQL database
conn = mysql.connector.connect(host="localhost", user="logger", passwd="paswoord", db="temperatures")

# prepare a cursor
cur = conn.cursor()

# in deze query selecteren we de 96 laatste metingen
query = """
SELECT dateandtime,temperature FROM temperaturedata
ORDER BY dateandtime DESC LIMIT 96;
"""

# execute the query
cur.execute(query)

# retrieve the whole result set
data = cur.fetchall()

# close cursor and connection
cur.close()
conn.close()

# unpack data in TimeStamp (x axis) and Pac (y axis)
dateandtime, temperature = zip(*data)

##print(temperature, end='\n')

# graph code, plot lijntjes,  scatter puntjes
plt.plot(dateandtime, temperature)

# set title, X/Y labels
plt.title("Temperatuur"+" Raspi15 " + vandaag)
plt.xlabel("Time of Day")
plt.ylabel("graden Celsius")
fig = plt.gcf()

# plt.xticks(TimeStamp, (hour))
fig.set_size_inches(10,7)
plt.grid(True)
plt.draw()

# plt.show()
fig.savefig('/home/pi15/Raspi15Temperatuur.png', dpi=100)   #OPGELET: Path aanpassen pi15
#shutil.move('/home/pi15/Raspi15Temperatuur.png', '/var/www/html/Raspi15Temperatuur.png')
