Set WshShell = WScript.CreateObject("WScript.Shell")
Set WshSysEnv = WshShell.Environment("PROCESS")
WshShell.Run("taskkill /f /im excel.exe")
WScript.Sleep 3000  ' 3 segundos deve ser suficiente para o kill matar processos parados, e não o correto (abaixo)