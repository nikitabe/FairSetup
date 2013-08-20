Call LogEntry()   

Sub LogEntry()   
	On Error Resume Next   
	
	Dim objRequest 
	Dim URL   
	Set objRequest = CreateObject("Microsoft.XMLHTTP") 
	URL = "http://portal.fairsetup.com/auto_trigger/auto_trigger.stp?a=1"   
	objRequest.open "GET", URL , false   
	objRequest.Send   
	Set objRequest = Nothing   
End Sub