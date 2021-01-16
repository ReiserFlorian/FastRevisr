import initializeDumpHelper as dh
import mysql.connector
import subprocess
import os
import io
import sys
from threading import Thread

if __name__ == '__main__':

	mysqlProcessList = []
	mysqlResultList = []
	mysqlArgs= [
		"mysql", 
		dh.database
	]
	threadList = []

	restoreDumpCur = dh.connection.cursor()

	def executeSQL(sql, splitSql=False):
		if sql is None or sql == "":
			return
		if splitSql:
			for sqlCommand in filter(
				lambda line: line != "" and not line.startswith("--"),
				map(lambda line: line.strip(), sql.split(";"))
			):
				result = restoreDumpCur.execute(sqlCommand, multi=True)
				if result is not None:
					result.send(None)
		else:
			result = restoreDumpCur.execute(sql, multi=True)
			if result is not None:
				result.send(None)

	def dropObsoletTablesSQL():
		tables = {os.path.splitext(sqlFileName)[0] for sqlFileName in filter(
			lambda dirEntry : os.path.splitext(dirEntry)[1].lower() == ".sql",
			os.listdir(path=dh.dumpConf['dump']['location'])
		)}
		showTablesCursor = dh.connection.cursor(buffered=True)
		showTablesCursor.execute("SHOW TABLES")
		existingTables = {table for (table,) in showTablesCursor}
		showTablesCursor.close()
		
		return "\n".join( ("DROP TABLE IF EXISTS `{}`;".format(table) for table in existingTables.difference(tables)) )

	try:
		executeSQL("SET FOREIGN_KEY_CHECKS = 0")
		executeSQL(dropObsoletTablesSQL(), True)
		dh.connection.commit()
	except mysql.connector.Error as e:
		print (
			"Unable to drop old tables from database {}: {}".format(dh.database, e),
			file=sys.stderr
		)
		dh.connection.rollback()
		exit(1)
	except:
		dh.connection.rollback()
		raise

	def loadDump(*, sqlFile, txtFile, tableName):
		result = {
			"returncode" : 0,
			"sqlFile" : sqlFile,
			"txtFile" : txtFile,
			"tableName" : tableName,
			"stderr" : "",
			"stdout" : ""
		}
		try:
			with io.open(sqlFile, mode="r", encode="utf-8") as sqlFileHandler:
				executeSQL(sqlFileHandler.readlines(), True)

			executeSQL(
				"LOAD DATA INFILE '{}' INTO TABLE {} CHARACTER SET utf8mb4 FIELDS TERMINATED BY '\t'".format(
					txtFile, tableName
				)
			)

		except mysql.connector.Error as e:
			result["returncode"] = 1
			result["stderr"] = "Unable to load dump into database {}: {}".format(dh.database, e)
			dh.connection.rollback()
			return
		except:
			result["returncode"] = 99
			result["stderr"] = "Unknown error"
			dh.connection.rollback()
			return

		mysqlResultList.append(result)


	for sqlFile in filter(
		lambda dirEntry : os.path.splitext(dirEntry)[1].lower() == ".sql",
		map(
			lambda dirEntry : R"{}\\{}".format(
				dh.dumpConf['dump']['location'], 
				dirEntry
			),
			os.listdir(path=dh.dumpConf['dump']['location'])
		)
	):
		loadDump(
			sqlFile = sqlFile, 
			txtFile = "{}.txt".format(os.path.splitext(sqlFile)[0]), 
			tableName = os.path.splitext(os.path.basename(sqlFile))[0]
		)
		#t = Thread(target = loadDump, 
		#	 args=(
		#		 sqlFile, 
		#		 "{}.txt".format(os.path.splitext(sqlFile)[0]), 
		#		 os.path.splitext(os.path.basename(sqlFile))[0]
		#	)
		#)
		#t.start()
		#threadList.append(t)


	#for t in threadList:
	#	t.join()

	errors = list(filter(lambda result: result["returnCode"] > 0, mysqlResultList))
	if (errors):
		errorCode = 0;
		for error in errors:
			print(
				"Error during manipulating of the database {} (Table creation file: {}, Contentfile: {}): {}".format(
					dh.database,
					error["sqlFile"],
					error["txtFile"],
					error["stderr"]
				), 
				file=sys.stderr
			)
			errorCode = (errorCode, error["returncode"])
		exit(errorCode)

	try:
		executeSQL("SET FOREIGN_KEY_CHECKS = 1")
		dh.connection.commit()
	except mysql.connector.Error as e:
		print (
			"Error during manipulating of the database {}: {}".format(dh.database, e),
			file=sys.stderr
		)
		dh.connection.rollback()
		exit(2)
	except:
		dh.connection.rollback()
		raise

	restoreDumpCur.close()