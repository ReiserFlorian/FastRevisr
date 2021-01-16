import mysql.connector
import myloginpath
import sys
from os import path
from configparser import ConfigParser

configFileName = R"C:\Ampps\scripts\mysqlDump.cnf"
database = "wp"

if not path.isfile(configFileName):
	print ("{} config file doesn't exist".format(configFileName), file=sys.stderr)
	exit(1)

dumpConf = ConfigParser()
with open(configFileName) as configFile:
	dumpConf.read_string(configFile.read())

if "dump" not in dumpConf:
	print(
		"Unable to find [dump] section in the {} file".format(configFileName), 
		file=sys.stderr
	)
	exit(2)
if "location" not in dumpConf["dump"]:
	print (
		"Unable to find configuration `location` in the [dump] section of the configuration file {}".format(configFileName),
		file=sys.stderr
	)
	exit(3)

connection = mysql.connector.connect(**myloginpath.parse('client'), database=database)
connection.raise_on_warnings = True