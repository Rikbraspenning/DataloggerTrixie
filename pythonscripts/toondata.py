#!/usr/bin/python3
# Jan Moeskops - 09/10/2024

import mysql.connector

# Variables for MySQL
db = mysql.connector.connect(
        host="localhost",
        user="logger",
        passwd="paswoord",
        db="temperatures"
)

cursor = db.cursor()

# Voer een SQL-query uit om alle gegevens uit de tabel 'temperaturedata' op te halen
cursor.execute("SELECT * FROM temperaturedata")

# Haal alle resultaten op van de query
resultaten = cursor.fetchall()

# Toon de resultaten in een mooi tabelletje
print("-"*63)
print("|datum               | sensor | temperatuur | luchtvochtigheid|")
print("-"*63)
for rij in resultaten:
    print(f"|{rij[0]} | {rij[1]}  | {rij[2]}        | {rij[3]}            | ")
print("-"*63)

# Sluit de cursor en de verbinding
cursor.close()
db.close()

