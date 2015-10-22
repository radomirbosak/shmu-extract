#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from bs4 import BeautifulSoup
import mysql.connector

import requests
import re
import json
from subprocess import call
import sys

FILENAME = 'shmu.html'
ERRORBACK = 'chyba.html'

atributy = ["Čas merania:", "Mesto:", "Teplota:", "Oblačnosť:",
	"Počasie:", "Rýchlosť vetra:", "Smer vetra:"]

enum_atributy = ["Mesto:", "Oblačnosť:", "Počasie:", "Smer vetra:"]


db_config = {
  'user': '***',
  'password': '***',
  'host': 'localhost',
  'database': '***',
  'charset': 'utf8',
  'raise_on_warnings': True,
  'use_unicode': True,
}

def download_page():
	url = 'http://www.shmu.sk'
	try:
		r = requests.get(url)
	except requests.exceptions.ConnectionError:
		raise Exception("My Connection error")
	cont = r.content

	f = open(FILENAME, 'wb')
	f.write(cont)
	f.close()
	return cont


def read_file():
	f = open(FILENAME, 'rb')
	cont = f.read()
	f.close()
	return cont

def extract_data(html):
	"""
	format:

	Mesto:  Bratislava
	Teplota:  5 °C
	Oblačnosť:  Zamračené
	Počasie:  Dymno
	Rýchlosť vetra:  2 m/s
	Smer vetra:  PREM
	Čas merania:  01.11.2014 - 15:00
	"""
	soup = BeautifulSoup(html)
	d = dict()
	scripts = soup.findAll('script')

	match = 'HINTS_ITEMS'

	for script in scripts:
		text = script.text
		#print(len(text),text)
		p = re.compile("HINTS_ITEMS = (\{.*?\})", flags=re.DOTALL)

		daco = p.search(str(text))
		if daco:
			g1 = daco.group(1)
			# change single quotes to double
			g1 = re.sub("'", '"', g1)
			# fix trailing comma
			g1 = re.sub(",[ \t\r\n]+}", "}", g1)
			#print(g1)
			try:
				j = json.loads(g1)
			except Exception:
				call(["cp", FILENAME, ERRORBACK])
				raise Exception(("Error while loading json. Output saved to {}").format(ERRORBACK))
			try:
				ba = BeautifulSoup(j['BA'])
				for x in ba.findAll('p'):
					one = x.contents[0].string.strip()
					two = x.strong.string.strip()
					d[one] = two
				return d
			except KeyError:
				raise Exception("No data for bratislava")
	else:
		call(["cp", FILENAME, ERRORBACK])
		raise Exception("No suitable <script> tag found, errorfile saved as %s" % ERRORBACK)

def record_data(d):
	cnx = mysql.connector.connect(**db_config)
	cur = cnx.cursor()

	""" fetch all enums """
	forw_dict, back_dict = fetch_enums(cur)

	" insert missing enums"
	for atrname in enum_atributy:
		enumname = d[atrname]
		if enumname not in back_dict:
			try:
				cur.execute("INSERT INTO texty (obsah) VALUES (%s);", (str(enumname),))
			except mysql.connector.IntegrityError:
				pass

	cnx.commit()
	"</ >"

	""" fetch all enums 2-nd pass """
	forw_dict, back_dict = fetch_enums(cur)

	" parse and insert attributes"
	tds = [d[atribut] for atribut in atributy]

	newtds = normalize_items(tds, back_dict)

	add_record(cur, *newtds)

	"</ >"

	cnx.commit()
	cnx.close()
	
def normalize_items(tds, back_dict):
	cas = tds[0].split(' - ')
	cas[0] = cas[0].split('.')
	cas[1] = cas[1].split(':')
	cas = "{yy}-{mm}-{dd} {h}:{m}:{s}".format(
		yy=cas[0][2], mm=cas[0][1], dd=cas[0][0],
		h=cas[1][0], m=cas[1][1], s="00")

	mesto 		= back_dict[tds[1]]
	teplota 	= int(tds[2].split()[0])
	oblacnost 	= back_dict[tds[3]]
	pocasie 	= back_dict[tds[4]]
	rychlost_vetra = int(tds[5].split()[0])
	smer_vetra 	= back_dict[tds[6]]
	return [cas, mesto, teplota, oblacnost, pocasie, rychlost_vetra, smer_vetra]

def fetch_enums(cur):
	cur.execute("SELECT * FROM texty;")
	c = cur.fetchall()

	forw_dict = {tid: text for tid, text in c}
	back_dict = {text: tid for tid, text in c}
	return forw_dict, back_dict

def add_record(cur, cas, mesto, teplota, oblacnost, pocasie, rychlost_vetra, smer_vetra):
	cur.execute("SELECT * FROM pocko WHERE cas=%s AND mesto=%s;",
		(cas, mesto))

	if not cur.fetchall():
		cur.execute("INSERT INTO pocko " + 
			"(cas, mesto, teplota, oblacnost, pocasie, rychlostvetra, smervetra)" +
			" VALUES (%s, %s, %s, %s, %s, %s, %s);", 
			(cas,mesto,teplota,oblacnost,pocasie,rychlost_vetra,smer_vetra))

def extract_from_html():
	cnx = mysql.connector.connect(**db_config)
	cur = cnx.cursor()

	forw_dict, back_dict = fetch_enums(cur)

	with open("data.txt", "r") as f:
		soup = BeautifulSoup(f.read())


	rozne_hodnoty = [ set() for _ in atributy]

	for tr in soup.findAll('tr'):
		d = {atr:td.string for td, atr in zip(tr.findAll('td'), atributy)}
		record_data(d)
		print("extracted {}: {}".format(d[atributy[0]], d[atributy[2]]))
		
	cnx.close()


def main():
	#cont = read_file()
	if len(sys.argv) >= 2 and sys.argv[1] == 'dataex':
		extract_from_html()
	else:
		cont = download_page()

		d = extract_data(cont)
		record_data(d)

if __name__=='__main__':
	main()

