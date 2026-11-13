import mysql.connector
from dotenv import load_dotenv
import os
import csv

load_dotenv()

DB_HOST = os.getenv("DB_HOST")
DB_PORT = int(os.getenv("DB_PORT", 3306))
DB_NAME = os.getenv("DB_NAME")
DB_USER = os.getenv("DB_USER")
DB_PASSWORD = os.getenv("DB_PASSWORD")

yhteys = None

try:
    yhteys = mysql.connector.connect(
        host=DB_HOST,
        port=DB_PORT,
        database=DB_NAME,
        user=DB_USER,
        password=DB_PASSWORD
    )

    print("Yhteys MySQL-tietokantaan onnistui!")

    kursori = yhteys.cursor()

    kysely = """
        SELECT *
        FROM rawdata
    """

    kursori.execute(kysely)

    sarakkeet = [sarake[0] for sarake in kursori.description]

    rivit = kursori.fetchall()

    with open(
        "kiihtyvyysdata_mysql.csv",
        "w",
        newline="",
        encoding="utf-8-sig"
    ) as tiedosto:

        kirjoittaja = csv.writer(tiedosto)

        kirjoittaja.writerow(sarakkeet)
        kirjoittaja.writerows(rivit)

    print("Mittaukset haettu onnistuneesti")
    print("Haettuja rivejä:", len(rivit))
    print("Tallennettu tiedostoon kiihtyvyysdata_mysql.csv")

except mysql.connector.Error as virhe:
    print("MySQL-yhteydessä tapahtui virhe:", virhe)

except Exception as virhe:
    print("Virhe:", virhe)

finally:
    if yhteys is not None and yhteys.is_connected():
        kursori.close()
        yhteys.close()
        print("Tietokantayhteys suljettu.")