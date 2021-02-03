#include <sourcemod>
#include <SteamWorks>

#define MAX_URL_SIZE 512
#define MAX_VAR_SIZE 128
#define MAX_PLAYERS 64
#define MAX_STEAMID_SIZE 30
#define MAX_COMMANDS_RAN_AT_ONCE 10
#define MAX_COMMAND_LENGTH 512
#define COMMAND_WAIT_TIME 10.0
#define COMMAND_CHECK_TIME 30.0

public Plugin:myinfo =
{
    name = "SDonate Sourcemod Plugin",
    author = "Webmaster",
    description = "Sourcemod plugin for SDonate donation system",
    version = "1.0",
    url = "https://sdonate.com/"
}

ConVar varSDonateURL;
ConVar varSDonateAPI;
ConVar varServerIP;
ConVar varServerPort;

char sdonateURL[MAX_URL_SIZE];
char sdonateAPI[MAX_VAR_SIZE];
char serverIP[MAX_VAR_SIZE];
char serverPort[MAX_VAR_SIZE];

bool timerStarted = false;

new String:peopleGettingCommands[MAX_PLAYERS][MAX_STEAMID_SIZE];


public void RunCommand(char[] command)
{
    ServerCommand(command);
    Handle httpRequest = SteamWorks_CreateHTTPRequest(k_EHTTPMethodGET, sdonateURL);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequest, "game", "sourcemod");
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequest, "apikey", sdonateAPI);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequest, "ip", serverIP);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequest, "port", serverPort);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequest, "confirmcommand", command);
    SteamWorks_SendHTTPRequest(httpRequest);
}

public int ParseBody(Handle request, bool failure, bool requestSuccessful, EHTTPStatusCode statusCode, Handle data)
{
    int len = 0;
    SteamWorks_GetHTTPResponseBodySize(request, len);
    char[] response = new char[len];
    SteamWorks_GetHTTPResponseBodyData(request, response, len);
    if(StrEqual(response, "No Commands", false) == false && StrEqual(response, "Error", false) == false && StrEqual(response, "", false) == false)
    {
        new String:commands[MAX_COMMANDS_RAN_AT_ONCE][MAX_COMMAND_LENGTH];
        int numCommands = ExplodeString(response, "*|NEXTCOMMAND|*", commands, MAX_COMMANDS_RAN_AT_ONCE, MAX_COMMAND_LENGTH);
        for (int i = 0; i < numCommands; i++)
        {
            RunCommand(commands[i]);
        }
    }
}

public Action RequestCommandsToRunNow(Handle timer)
{
    Handle httpRequestNow = SteamWorks_CreateHTTPRequest(k_EHTTPMethodGET, sdonateURL);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequestNow, "game", "sourcemod");
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequestNow, "apikey", sdonateAPI);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequestNow, "ip", serverIP);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequestNow, "port", serverPort);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequestNow, "checkcommands", "");
    SteamWorks_SetHTTPCallbacks(httpRequestNow, ParseBody, INVALID_FUNCTION, INVALID_FUNCTION, INVALID_HANDLE);
    SteamWorks_SendHTTPRequest(httpRequestNow);
    return Plugin_Continue;
}

public Action RemoveCommandWait(Handle timer, int i)
{
    peopleGettingCommands[i] = "";
}

public void GetClientCommands(char steamid[MAX_STEAMID_SIZE])
{
    for (int i = 0; i < sizeof(peopleGettingCommands); i++)
    {
        if (StrEqual(peopleGettingCommands[i], "", false))
        {
            peopleGettingCommands[i] = steamid;
            CreateTimer(COMMAND_WAIT_TIME, RemoveCommandWait, i);
            break;
        }
    }
    PrintToServer(steamid);
    Handle httpRequest = SteamWorks_CreateHTTPRequest(k_EHTTPMethodGET, sdonateURL);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequest, "game", "sourcemod");
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequest, "apikey", sdonateAPI);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequest, "player", steamid);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequest, "ip", serverIP);
    SteamWorks_SetHTTPRequestGetOrPostParameter(httpRequest, "port", serverPort);
    SteamWorks_SetHTTPCallbacks(httpRequest, ParseBody, INVALID_FUNCTION, INVALID_FUNCTION, INVALID_HANDLE);
    SteamWorks_SendHTTPRequest(httpRequest);
}

public void OnPluginStart()
{
    PrintToServer("SDonate Sourcemod plugin loaded");
    varSDonateURL = CreateConVar("sdonate_url", "http://example.com/folder/pluginapi.php", "Enter the URL to pluginapi.php");
    varSDonateAPI = CreateConVar("sdonate_apikey", "YOURAPIKEY", "Enter your SDonate API key");
    varServerIP = CreateConVar("sdonate_serverip", "123.123.123.123", "Enter the IP of this server")
    varServerPort = CreateConVar("sdonate_serverport", "27015", "Enter the port of this server");
    AutoExecConfig(true, "sdonate");
}

public void OnConfigsExecuted()
{
    GetConVarString(varSDonateURL, sdonateURL, MAX_VAR_SIZE);
    GetConVarString(varSDonateAPI, sdonateAPI, MAX_VAR_SIZE);
    GetConVarString(varServerIP, serverIP, MAX_VAR_SIZE);
    GetConVarString(varServerPort, serverPort, MAX_VAR_SIZE);
    if (timerStarted == false)
    {
        CreateTimer(COMMAND_CHECK_TIME, RequestCommandsToRunNow, _, TIMER_REPEAT);
    }
    timerStarted = true;
}

public void OnClientAuthorized(client)
{
    char steamid[MAX_STEAMID_SIZE];
    GetClientAuthId(client, AuthId_SteamID64, steamid, sizeof(steamid));
    bool justGotCommands = false;
    for(int i = 0; i < sizeof(peopleGettingCommands); i++)
    {
        if (StrEqual(peopleGettingCommands[i], steamid, false))
        {
            justGotCommands = true;
            break;
        }
    }
    if (justGotCommands == false)
    {
        GetClientCommands(steamid);
    }
}
