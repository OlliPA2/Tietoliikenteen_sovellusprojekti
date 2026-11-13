import argparse
import asyncio
import struct
import time
import os
import mysql.connector
from dotenv import load_dotenv

from bleak import BleakClient, BleakScanner

load_dotenv()

DEVICE_NAME = "MY_LBS2OLLI"
ACCEL_CHAR_UUID = "00001526-1212-efde-1523-785feabcd123"
DATA_FORMAT = "<HHHH"
SCALE = 1 / 1000.0
SAMPLE_SIZE = struct.calcsize(DATA_FORMAT)

db = mysql.connector.connect(
    host=os.getenv("BLE_DB_HOST"),
    port=int(os.getenv("BLE_DB_PORT", 3306)),
    user=os.getenv("BLE_DB_USER"),
    password=os.getenv("BLE_DB_PASSWORD"),
    database=os.getenv("BLE_DB_NAME")
)

cursor = db.cursor()

sql = """
INSERT INTO rawdata
(groupid,
 sensorvalue_a, sensorvalue_b, sensorvalue_c,
 sensorvalue_d)
VALUES (%s, %s, %s, %s, %s)
"""

async def scan():
    print("skannataan 5 s...")
    devices = await BleakScanner.discover(timeout=5.0, return_adv=True)
    for addr, (dev, adv) in devices.items():
        print(f"{addr}  RSSI={adv.rssi:4d}  nimi={dev.name}")


async def find_device():
    print(f"etsitään laitetta '{DEVICE_NAME}'...")
    device = await BleakScanner.find_device_by_name(DEVICE_NAME, timeout=10.0)
    if device is None:
        raise SystemExit("Laitetta ei löytynyt")
    print(f"Löytyi: {device.address}")
    return device


async def list_services():
    device = await find_device()
    async with BleakClient(device) as client:
        for service in client.services:
            print(f"[Palvelu] {service.uuid}  {service.description}")
            for char in service.characteristics:
                print(f"    [Kar.] {char.uuid}  {','.join(char.properties)}")


def handle_notification(_sender, data: bytearray):
    if len(data) % SAMPLE_SIZE != 0:
        print(f"väärä pituus {len(data)} tavua: {data.hex()}")
        return
    for x, y, z, suunta in struct.iter_unpack(DATA_FORMAT, data):
        values = (25, x, y, z, suunta)
        cursor.execute(sql, values)
        db.commit()
        print(f"{time.time():.3f}  x={x}  y={y}  z={z}  suunta={suunta}")


async def receive():
    device = await find_device()
    disconnected = asyncio.Event()

    def on_disconnect(_client):
        print("Yhteys katkesi.")
        disconnected.set()

    async with BleakClient(device, disconnected_callback=on_disconnect) as client:
        print("Yhdistetty")
        await client.start_notify(ACCEL_CHAR_UUID, handle_notification)
        try:
            await disconnected.wait()
        finally:
            if client.is_connected:
                await client.stop_notify(ACCEL_CHAR_UUID)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--scan", action="store_true", help="listaa BLE-laitteet")
    parser.add_argument("--services", action="store_true", help="listaa palvelut")
    args = parser.parse_args()

    if args.scan:
        coro = scan()
    elif args.services:
        coro = list_services()
    else:
        coro = receive()

    try:
        asyncio.run(coro)
    except KeyboardInterrupt:
        print("\nLopetettu.")


if __name__ == "__main__":
    main()