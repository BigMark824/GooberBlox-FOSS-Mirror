local placeId, port, url = "{placeId}", "{port}", "http://www.goober.biz";
local HttpService = game:GetService("HttpService");
game:GetService("HttpService").HttpEnabled = true
local Players = game:GetService("Players");
local ns = game:GetService("NetworkServer");
local scriptContext = game:GetService('ScriptContext');
local workSpace = game:GetService("Workspace");
-----------------------------------"CUSTOM" SHARED CODE----------------------------------

pcall(function() settings().Network.UseInstancePacketCache = true end)
pcall(function() settings().Network.UsePhysicsPacketCache = true end)
--pcall(function() settings()["Task Scheduler"].PriorityMethod = Enum.PriorityMethod.FIFO end)
pcall(function() settings()["Task Scheduler"].PriorityMethod = Enum.PriorityMethod.AccumulatedError end)

--settings().Network.PhysicsSend = 1 -- 1==RoundRobin
--settings().Network.PhysicsSend = Enum.PhysicsSendMethod.ErrorComputation2
settings().Network.PhysicsSend = Enum.PhysicsSendMethod.TopNErrors
settings().Network.ExperimentalPhysicsEnabled = true
settings().Network.WaitingForCharacterLogRate = 100
pcall(function() settings().Diagnostics:LegacyScriptMode() end)

-----------------------------------START GAME SHARED SCRIPT------------------------------

pcall(function() scriptContext:AddStarterScript(37801172) end)
scriptContext.ScriptsDisabled = true

game:SetPlaceID(placeId, false)
game:GetService("ChangeHistoryService"):SetEnabled(false)

-- establish this peer as the Server

-- balls
if url~=nil then
	pcall(function() Players:SetAbuseReportUrl(url .. "/AbuseReport/InGameChatHandler.ashx") end)
	pcall(function() game:GetService("ScriptInformationProvider"):SetAssetUrl(url .. "/Asset/") end)
	pcall(function() game:GetService("ContentProvider"):SetBaseUrl(url .. "/") end)
	pcall(function() Players:SetChatFilterUrl(url .. "/Game/ChatFilter.ashx") end)

	game:GetService("BadgeService"):SetPlaceId(placeId)

	game:GetService("BadgeService"):SetIsBadgeLegalUrl("")
	game:GetService("InsertService"):SetBaseSetsUrl(url .. "/Game/Tools/InsertAsset.ashx?nsets=10&type=base")
	game:GetService("InsertService"):SetUserSetsUrl(url .. "/Game/Tools/InsertAsset.ashx?nsets=20&type=user&userid=%d")
	game:GetService("InsertService"):SetCollectionUrl(url .. "/Game/Tools/InsertAsset.ashx?sid=%d")
	game:GetService("InsertService"):SetAssetUrl(url .. "/Asset/?id=%d")
	game:GetService("InsertService"):SetAssetVersionUrl(url .. "/Asset/?assetversionid=%d")
	
	--pcall(function() loadfile(url .. "/Game/LoadPlaceInfo.ashx?PlaceId=" .. placeId)() end)
end

pcall(function() game:GetService("NetworkServer"):SetIsPlayerAuthenticationRequired(true) end)
settings().Diagnostics.LuaRamLimit = 0

local webhookUrl = "https://discord.com/api/webhooks/1194452136742899863/E7vUEYASuRBORrvR5s-w99_01d-PKbZR5wUAdQ4GMODv94XTuPP8HTcYi-bomCQiZH9a"
HttpService.HttpEnabled = true;
game:GetService("HttpService").HttpEnabled = true
function sendMsgToWebhook(message, webhook)
    local embed = {
        ["title"] = "RCCService Bot",
        ["description"] = message,
        ["color"] = tonumber(0xff0000),
    }

    local data = {
        ["content"] = "",
        ["embeds"] = {embed},
    }

    local success, err = pcall(function()
        HttpService:PostAsync(
            webhook,
            HttpService:JSONEncode(data),
            Enum.HttpContentType.ApplicationJson
        )
    end)

    if not success then
        warn("Error sending webhook message:", err)
    end
end
function sendMsgToWebhookNoEmbed(message, webhook)
	HttpService:PostAsync(
			webhook,
			HttpService:JSONEncode({ content = message }),
			Enum.HttpContentType.ApplicationJson
		)
	end
	game:GetService("Players").PlayerAdded:connect(function(player)
		print("Player " .. player.UserId .. " added")
		game:GetService("HttpService").HttpEnabled = true
		local playerCount = #game:GetService("Players"):GetPlayers()
		local currentPlaceId = game.PlaceId
		game:GetService("Players"):GetCharacterAppearanceAsync(player.UserId)
	
		local message = string.format("!player %d %s", playerCount, currentPlaceId)
		print(message)
		sendMsgToWebhookNoEmbed(message, webhookUrl)
	end)
	
	game:GetService("Players").PlayerRemoving:connect(function(player)
		print("Player " .. player.UserId .. " leaving")
		local playerCount = #game:GetService("Players"):GetPlayers()
		local currentPlaceId = game.PlaceId
		game:GetService("HttpService").HttpEnabled = true
		local message = string.format("!player %d %s", playerCount, currentPlaceId)
		print(message)
		sendMsgToWebhookNoEmbed(message, webhookUrl)
	end)
	

if placeId~=nil and url~=nil then
	-- yield so that file load happens in the heartbeat thread
	wait()
	
	-- load the game
	game:Load(url .. "/asset/?id=" .. placeId)
end

------------- SECURITY CODE - neva --------------
local reportUrl = "https://discord.com/api/webhooks/1213390113921302548/MJIoZvbqlsDUflZweVAb7v6NdyGUREAcEd0CWdXk9UfoAjLq1tTn9dweZrqDyjESF-Pm";
local ReplicatorTable = {
    Filters = {},
	ServerReplicators = {},
	Tickets = {},
	clist = {}
};
-- LOL CLIST FROM ROBLOX LOYHOLRTYTOLERYLOERYLOSERLOYER - neva
ReplicatorTable.__index = ReplicatorTable;

-- if any of these filters fail please contact me.
function filterInstanceInside(badParent, instance)

local result = true;
local nextParent = instance;

if(nextParent==game) then return false end;

while nextParent ~= game do

if(nextParent==badParent) 
then result = false
else nextParent = nextParent.Parent 
end;

end 

return result;
end

function filterInstanceParent(instance, parent)
if(tostring(instance.ClassName)=="Player") then
	if(instance.Parent == nil) then
		if(tostring(parent.ClassName)=="Players") 
			then return Enum.FilterResult.Accepted
			else return Enum.FilterResult.Rejected 
		end;
	end
end

--check parent
if(filterInstanceInside(game:GetService("StarterGui"), parent)==false) then
return Enum.FilterResult.Rejected; end;

if(filterInstanceInside(game:GetService("StarterPack"), parent)==false) then
return Enum.FilterResult.Rejected; end;

if(filterInstanceInside(game:GetService("StarterPlayer"), parent)==false) then
return Enum.FilterResult.Rejected; end;

if(filterInstanceInside(game:GetService("Teams"), parent)==false) then
return Enum.FilterResult.Rejected; end;

-- check instance
if(filterInstanceInside(game:GetService("StarterGui"), instance)==false) then
return Enum.FilterResult.Rejected; end;

if(filterInstanceInside(game:GetService("StarterPack"), instance)==false) then
return Enum.FilterResult.Rejected; end;

if(filterInstanceInside(game:GetService("StarterPlayer"), instance)==false) then
return Enum.FilterResult.Rejected; end;

if(filterInstanceInside(game:GetService("Teams"), instance)==false) then
return Enum.FilterResult.Rejected; end;

-- no way to check if an instances parent is locked but roblox already does it in c++ so lol
return Enum.FilterResult.Accepted;

end

function ReplicatorTable.Filters.OnDeleteFilter(deletingItem)
local msg = string.format("[CIA LOGS]|-|[DeleteFilter]|-|[Item '%s' was deleted]", tostring(deletingItem.Name));
--coroutine.wrap(function() sendMsgToWebhook(msg, reportUrl) end)()
print(msg);
return filterInstanceParent(deletingItem, nil);
end

function ReplicatorTable.Filters.OnEventFilter(firingItem, event)
local msg = string.format("[CIA LOGS]|-|[EventFilter]|-|[Event '%s' was fired by Instance '%s']", event, tostring(firingItem.Name));
--coroutine.wrap(function() sendMsgToWebhook(msg, reportUrl) end)()
print(msg);
return Enum.FilterResult.Accepted;
end

function ReplicatorTable.Filters.OnNewFilter(newItem, parent)
local msg = string.format("[CIA LOGS]|-|[NewFilter]|-|[New instance '%s' parent of '%s']", tostring(newItem.Name), tostring(parent.Name));
--coroutine.wrap(function() sendMsgToWebhook(msg, reportUrl) end)()
print(msg);
return filterInstanceParent(newItem, parent);
end

function ReplicatorTable.Filters.OnPropertyFilter(changingItem, member, value)
local msg = string.format("[CIA LOGS]|-|[PropertyFilter]|-|[Property '%s' was changed on Instance '%s' to Value '%s']", member, tostring(changingItem.Name), tostring(value));
--coroutine.wrap(function() sendMsgToWebhook(msg, reportUrl) end)()
print(msg);
return Enum.FilterResult.Accepted;
end

function ReplicatorTable.Filters.OnTicketProcessed(userId, isAuthenticated, protocolVersion)
local msg = string.format("[CIA LOGS]|-|[TicketProcessed]|-|[Authenticated:'%s']|-|[Processed ticket of user '%d' on protocol '%d']", tostring(isAuthenticated), userId, protocolVersion);
table.insert(ReplicatorTable.Tickets, {userId, isAuthenticated, protocolVersion})
coroutine.wrap(function() sendMsgToWebhook(msg, reportUrl) end)()
print(msg);
return;
end

ns.ChildAdded:connect(function(replicator)
	--INJECT INTO SERVERREPLICATOR
		--pcall(function() replicator:SetBasicFilteringEnabled(true) end) -- don't worry, filters are enabled, just not the normal roblox ones - neva
		pcall(function() replicator:PreventTerrainChanges() end)
		pcall(function() replicator.TicketProcessed:connect(ReplicatorTable.Filters.OnTicketProcessed); end)
		--pcall(function() replicator.DeleteFilter = ReplicatorTable.Filters.OnDeleteFilter end)
		--pcall(function() replicator.EventFilter = ReplicatorTable.Filters.OnEventFilter end)
		--pcall(function() replicator.NewFilter = ReplicatorTable.Filters.OnNewFilter end)
		--pcall(function() replicator.PropertyFilter = ReplicatorTable.Filters.OnPropertyFilter end)
		table.insert(ReplicatorTable.ServerReplicators, replicator);
		coroutine.wrap(function()
		repeat wait() until replicator:GetPlayer() ~= nil
		    local player = replicator:GetPlayer();
		    replicator.Name = string.format("%s|%d|%d", player.Name, player.UserId, replicator.Port);
		    sendMsgToWebhook(string.format("[CIA Loader]: Loaded replicator for player '%s' with id '%d'", player.Name, player.UserId), reportUrl);
		end)()
		--print(ReplicatorTable.Tickets)
end)
workspace.FilteringEnabled = true
print("[CIA Loader]: Loaded!")
sendMsgToWebhook(string.format("[CIA Loader]: Loaded for placeid '%d'", game.PlaceId), reportUrl)
------------- SECURITY CODE - neva --------------

-- Now start the connection
ns:Start(port)


scriptContext:SetTimeout(10)
scriptContext.ScriptsDisabled = false
game:GetService("RunService"):Run()



------------------------------END START GAME SHARED sSCRIPT--------------------------