import initializeDumpHelper as dh
import subprocess
from threading import Thread

if __name__ == '__main__':

	tableCur = dh.connection.cursor(buffered=True)
	tableSQL = "SHOW TABLES"
	tableCur.execute(tableSQL)

	mysqlDumpProcessList = []

	for (tableName,) in tableCur:
		mysqlDumpArgs = [
			"mysqldump", 
			"--tab={}".format(dh.dumpConf['dump']['location']),
			"wp", 
			tableName
		]
		mysqlDumpProcessList.append(subprocess.Popen(mysqlDumpArgs, shell=True))

	for prcs in mysqlDumpProcessList:
		prcs.wait()

	tableCur.close()

