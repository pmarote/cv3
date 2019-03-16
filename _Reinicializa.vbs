Set WshShell = WScript.CreateObject("WScript.Shell")
Set WshSysEnv = WshShell.Environment("PROCESS")
WshShell.Run("php-win src\cv3\cv3.php reinicializa")