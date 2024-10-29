<?php
// the beginning of all hell -- real
use core\route;
use core\modules\twig;
use dbController\databaseController;
use core\conf;
use Virtubrick\Grid\GridService;
use renderController\renderController;
use Virtubrick\Grid\Rcc\{Job, LuaScript};
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
use Roblox\Grid\Rcc\RCCServiceSoap;
$maintenance = conf::get()['project']['maintenance']['value'];
if (__IP__ != "206.83.122.39" && __IP__ != "127.0.0.1" && __IP__ != "86.183.131.141" && $maintenance == true) {
    $reason = conf::get()['project']['maintenance']['reason'];
    $time = conf::get()['project']['maintenance']['time'];
    echo twig::view("gooberblox/pages/maint.twig", ["eta" => $time, "reason" => $reason]);
    exit();
}
function uuidv4()
{
    /// i literally just ripped this code from stackoverflow and no i dont give a fuck stfu
    $data = random_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function generateToken(): string
{
    return bin2hex(random_bytes(128)); // you should up this to like 128 even just to ensure fully that it ain't ever going to be the same one day
    // neva: done
    // valid lol

}
class rbx
{
    public function __construct()
    {
    }
    //rbxsig2: --rbxsig2%Tci6E2L9vD9imagwqM6eVnwaHTyw4zABCA0vpSvzNRaxw+jQt28fpglXjjDAJm6PkYfs+906oB6SM6r4ZPzzvg7BkxX9MNSzV/P4D9rx+pCSt922XOnurcG6yF5tkKLSLSc/1Rl9Jxv8oD7P/IdtNjdZQiKzMgFPUeDUlYVZm2Cdqxs6SbIVu1P8GrlDdC3gegH5G2N6J8xzpQ+4kjDUr70futmkSLHjX+vrBIT0Gy1glJXl3ePwcvFu3sZ3FSHnGBma4xaiM+qvgaKAho/WY3wwShp18O6bXesHp3ommNPWHqgdX8xr7EEp48YO+m3JV4SMiqOzVkIGQYErkxmRYw==%

    public function sign(string $contents, int $rbxsigType = 1, bool $useRbxSig = true): string
    {
        $filename = "privKey1.pem";
        if (__UA__ == "Roblox/2016Debug" || __UA__ == "Roblox/Win2017") {
            $filename = "debugKey.pem";
        }
        $signature = null;
        (string) $rbxsig1key = (string) file_get_contents(__FWDIR__ . "/files/keys/" . $filename);
        switch ($rbxsigType) {
            default:
                if (openssl_sign("\r\n$contents", $signature, $rbxsig1key, OPENSSL_ALGO_SHA1))
                    return $useRbxSig ? "--rbxsig%" . base64_encode($signature) . "%\r\n$contents" : "%" . base64_encode($signature) . "%\r\n$contents";
        }
        return "";
    }

    public function genTicket(int $userId, string $userName, string $jobId, string $charApp): string {
        $time = time();
        $ticket1 = "$userId\n$userName\n$charApp\n$jobId\n$time";
        $ticket2 = "$userId\n$jobId\n$time";

        $privKey = (string)file_get_contents(__FWDIR__."/files/keys/debugKey.pem");
        if(!openssl_sign($ticket1, $signature1, $privKey))
            return "$time;0;0";
        if(!openssl_sign($ticket2, $signature2, $privKey))
            return "$time;0;0";
        $b64_signature1 = base64_encode($signature1);
        $b64_signature2 = base64_encode($signature2);
        return "$time;$b64_signature1;$b64_signature2";
        }

    public function rbxsign($script): string
    {
        $filename = "privKey1.pem";
        if (strpos(__UA__, 'Roblox/Win2017') !== false) {
            $filename = "privKey3.pem";
        }
        
        $rbxsig1key = file_get_contents(__FWDIR__ . "/files/keys/debugKey.pem");
        openssl_sign($script, $signature, $rbxsig1key, OPENSSL_ALGO_SHA1);
        return "--rbxsig" . sprintf("%%%s%%%s", base64_encode($signature), $script);
    }

}

route::get("/game/validate-place-join", function () {
    return "true"; // must strictly return true
    // or else youll do what? kill me? if you could kill me, id already be dead...
    // or else the client will MURDER YOU, not me...
    // you cant kill me otherwise jesse wont cook for you
});

route::any("/Game/Join.ashx", function () {
    $db = new databaseController("gooberblox");
    $token = htmlspecialchars($_GET['auth']); // TODO: neva add proper negotiate security, this is a temp solution :3
    (string)$jobid = (string)$_GET['job'];
    (int)$year = (int)$_GET['year'];
    $stmt = $db->prepare("SELECT * FROM users WHERE token = :token");
    $stmt->bindParam(':token', $token, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    if (empty($user['token']) || $user['moderated'] == 1) {
        http_response_code(405);
        exit; 
    }
    // todo: decode user data
    $placeid = (int)$_GET['placeId'] ?? 1818;
    $rbx = new rbx();
    $userId = $user['id'];
    $username = $user['name'];
    $membership = $user['buildersclub'];
    
    $stmt2 = $db->prepare("SELECT * FROM games WHERE id = :id");
    $stmt2->bindParam(':id', $placeid, PDO::PARAM_STR);
    $stmt2->execute();
    $game = $stmt2->fetch(PDO::FETCH_ASSOC);
    if (__UA__ === "Roblox/Win2017" || __UA__ === "Roblox/Win2019") {
        $charfetch = "http://api.goober.biz/v1.1/avatar-fetch/?userId=" . $userId;

    } else {
        $charfetch = "http://api.goober.biz/Asset/CharacterFetch.ashx/?userId=" . $userId;
    }
    $joinScriptInfo = [
      "UserId" => $userId,
      "UserName" => $username,
      "CharacterAppearance" => $charfetch,
      "CharacterAppearanceId" => $userId,
      "jobId" => $jobid,
      "Membership" => $membership,
      "CreatorId" => $game['creatorid'],
      "RbxSecurity" => $game['RbxPlace']
    ];
    return $rbx->sign(json_encode([
        "ClientPort" => 0,
        "MachineAddress" => "75.164.2.255",
        "ServerPort" => 2001 + $placeid,
        "UserName" => $joinScriptInfo["UserName"] ?? "1",
        "PingUrl" => "",
        "PingInterval" => 0,
        "SeleniumTestMode" => false,
        "UserId" => $joinScriptInfo["UserId"] ?? 1,
        "RobloxLocale" => "en_us",
        "GameLocale" => "en_us",
        "SuperSafeChat" => false,
        "CharacterAppearance" => $joinScriptInfo["CharacterAppearance"],
        "ClientTicket" => $rbx->genTicket($joinScriptInfo["UserId"], $joinScriptInfo["UserName"], $joinScriptInfo["jobId"], $joinScriptInfo["CharacterAppearance"]),
        "GameId" => $joinScriptInfo["jobId"],
        "PlaceId" => $placeid,
        "MeasurementUrl" => "",
        "WaitingForCharacterGuid" => "2228a26f-5158-4d50-acbe-c9053997673e",
        "BaseUrl" => "http://www.goober.biz/",
        "ChatStyle" => "ClassicAndBubble",
        "VendorId" => 0,
        "ScreenShotInfo" => "",
        "VideoInfo" => "",
        "CreatorId" => $joinScriptInfo["CreatorId"],
        "CreatorTypeEnum" => "User",
        "MembershipType" => $joinScriptInfo["Membership"] ?? "None",
        "AccountAge" => 365,
        "CookieStoreFirstTimePlayKey" => "rbx_evt_ftp",
        "CookieStoreFiveMinutePlayKey" => "rbx_evt_fmp",
        "CookieStoreEnabled" => true,
        "IsRobloxPlace" => $joinScriptInfo["RbxSecurity"] ?? "false",
        "GenerateTeleportJoin" => false,
        "IsUnknownOrUnder13" => false,
        "GameChatType" => "AllUsers",
        "SessionId" => "",
        "AnalyticsSessionId" => "67914290-6cf1-4339-8464-68d816626608",
        "DataCenterId" => 0,
        "UniverseId" => 0,
        "BrowserTrackerId" => 0,
        "UsePortraitMode" => false,
        "FollowUserId" => 0,
        "characterAppearanceId" => $joinScriptInfo["CharacterAppearanceId"],
        "CountryCode" => "US"
    ], JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
}, ["Content-Type" => "application/json"], []);


/* DATASTORE ENTRY */
// :troller: HAHAHAHHAHAHDHAAHEFAFGEAHFGAEHGFAHEGFGAEFHAHGFESGFAIFEGIAFGEGFAGF AEEFUYEFUEYAUYFAEUYFAUEYFUAF
// ae.....ae.........a.eeeeeeee.aeeeeeeeeee??????????????????????????????????????????????? -mathmark: neva you have autism lmao
route::post("/persistence/getV2", function () {
    $placeId = isset($_GET['placeId']) ? (int)$_GET['placeId'] : null;
    $type = isset($_GET['type']) ? (string)$_GET['type'] : null;
    $scope = isset($_GET['scope']) ? (string)$_GET['scope'] : null;
    $post_scope = isset($_POST['qkeys'][0]['scope']) ? (string)$_POST['qkeys'][0]['scope'] : null;
    $post_target = isset($_POST['qkeys'][0]['target']) ? (string)$_POST['qkeys'][0]['target'] : null;
    $post_key = isset($_POST['qkeys'][0]['key']) ? (string)$_POST['qkeys'][0]['key'] : null;

    if (!$placeId || !$type || !$scope) {
        return json_encode(["data" => []]);
    }
    $isLegacy = !$post_target;
    $db = new databaseController("gooberblox");

    $query = "SELECT * FROM data_store_service WHERE place_id = :placeId AND type = :type AND scope = :scope";
    $extraConditions = "";
    $params = [':placeId' => $placeId, ':type' => $type, ':scope' => $scope];

    if (!$isLegacy && $post_scope && $post_target && $post_key) {
        $extraConditions = " AND target = :target AND key = :key";
        $params[':target'] = $post_target;
        $params[':key'] = $post_key;
    }

    $statement = $db->prepare($query . $extraConditions);
    $statement->execute($params);

    $datalist = [];
    while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
        $datalist[] = ["Value" => $data['value'], "Key" => $data['key'], "Target" => $data['target'], "Scope" => $data['scope']];
    }

    $finishedResult = ["data" => $datalist];

    return json_encode($finishedResult);
});

route::post("/persistence/set", function () {
    //todo - neva: add gameserver ip and acesskey check here too
    (int) $placeId = (int) $_GET['placeId'];
    (string) $key = (string) $_GET['key'];
    (string) $type = (string) $_GET['type'];
    (string) $scope = (string) $_GET['scope'];
    (string) $target = (string) $_GET['target'];
    (int) $valueLength = (int) $_GET['valueLength'];
    if (true) {
        $db = new databaseController($db = "gooberblox");
        //todo - neva: nxss this (idk what type to do it for)
        (string) $value = $_POST['value'];

        $query = "SELECT * FROM data_store_service WHERE place_id = :placeId AND key = :key AND target = :target";
        $statement = $db->prepare($query);
        $statement->bindParam(':placeId', $placeId);
        $statement->bindParam(':key', $key);
        $statement->bindParam(':target', $target);
        $statement->execute();

        // no data? not sure untested.
        if ($statement->fetch(PDO::FETCH_ASSOC) == NULL) {
            $query = "INSERT INTO data_store_service (place_id, key, type, scope, target, value, time) VALUES (:placeId, :key, :type, :scope, :target, :value, :time)";
            $statement = $db->prepare($query);
            $statement->bindParam(':placeId', $placeId);
            $statement->bindParam(':key', $key);
            $statement->bindParam(':type', $type);
            $statement->bindParam(':scope', $scope);
            $statement->bindParam(':target', $target);
            $statement->bindParam(':value', $value);
            $time = time();
            $statement->bindParam(':time', $time);
        } else {
            $query = "UPDATE data_store_service set type = :newtype, value = :newvalue WHERE place_id = :placeId AND key = :key AND target = :target";
            $statement = $db->prepare($query);
            $statement->bindParam(':newtype', $type);
            $statement->bindParam(':newvalue', $value);
            $statement->bindParam(':placeId', $placeId);
            $statement->bindParam(':key', $key);
            $statement->bindParam(':target', $target);
        }
        $statement->execute();

        return json_encode(["data" => ["Value" => $value, "Scope" => $scope, "Key" => $key, "Target" => $target]]);
    }
});
route::post("/persistence/getSortedValues/", function () {
    return json_encode([
        "data" => [
            "Entries" => [],
            "ExclusiveStartKey" => ""
        ]
    ]);
});
route::post("/persistence/getSortedValues", function () {
    return json_encode([
        "data" => [
            "Entries" => [],
            "ExclusiveStartKey" => ""
        ]
    ]);
});
/* DATSTORE ENDING */


if (__SUBDOMAIN__ == "assetgema" || "assetgame") {
    route::any("/Game/PlaceLauncher.ashx", function () {
        (string) $RequestType = (string) $_GET['request'] ?? "RequestGame";
        (int) $placeId = (int) $_GET['placeId'];
        (string) $auth = (string) $_GET['auth'];
        $gender = null;
        $linkCode = null;
        $RCCServiceSoap = new Roblox\Grid\Rcc\RCCServiceSoap("127.0.0.1", 64989);
        // reconstruct args while not getting the static ones UPDATE: the "static" ones are actually really important to get
        switch ($RequestType) {
            case "RequestGameJob":
                (string) $gameId = (string) $_GET['gameId'];
                (bool) $isPartyLeader = (bool) $_GET['isPartyLeader'];
                (bool) $isTeleport = (bool) $_GET['isTeleport'];
                if (true) {
                    // test ahead relax grrr
                    $url = "75.164.10.32";
                    $port = "10735";
                    $jobId = "00000000-0000-0000-0000-000000000000$placeId";
                    $soapClient = new \gridController\GooberSoapClient($url, $port, $jobId, $placeId);

                    $soapScript = file_get_contents(__FWDIR__ . "/files/scripts/gameserver.lua");
                    $soapClient->scriptService($soapScript);

                    return json_encode([
                        "status" => 2,
                        "authenticationUrl" => "http://auth.goober.biz/Login/Negotiate.ashx",
                        "authenticationTicket" => "1",
                        "joinScriptUrl" => "http://assetgema.goober.biz/Game/Join.ashx?placeId={$placeId}&ticket={$auth}",
                        "jobId" => "$placeId",
                    ], JSON_UNESCAPED_SLASHES);
                }
            case "RequestGame":
                $isPartyLeader = isset ($_GET['isPartyLeader']) ? (bool) $_GET['isPartyLeader'] : false;
                $isTeleport = isset ($_GET['isTeleport']) ? (bool) $_GET['isTeleport'] : true;
                $joinTicket = base64_encode(__IP__ . time());
                header("content-type: application/json");

                $placeId = (int) $_GET['placeId'];
                $db = new databaseController("gooberblox");
                $query1 = "SELECT jobid FROM jobs WHERE placeid = :placeid";
                $statement1 = $db->prepare($query1);
                $statement1->bindParam(':placeid', $placeId);
                $statement1->execute();
                $result = $statement1->fetch(PDO::FETCH_ASSOC);
                $existingJobId = $result['jobid'];

                if ($existingJobId) {
                    return json_encode([
                        "status" => 2,
                        "authenticationUrl" => "http://auth.goober.biz/Login/Negotiate.ashx",
                        "authenticationTicket" => "1",
                        "joinScriptUrl" => "http://assetgema.goober.biz/Game/Join.ashx?placeId={$placeId}&auth={$auth}&job={$existingJobId}",
                        "jobId" => $existingJobId,
                        "message" => "Success",
                    ], JSON_UNESCAPED_SLASHES);
                } else {
                    if (true) {
                        $query = "SELECT * FROM games WHERE id = :placeId";
                        $placeId = (int) $_GET['placeId'];
                        $statement = $db->prepare($query);
                        $statement->bindParam(':placeId', $placeId);
                        $statement->execute();
                        $game = $statement->fetch(PDO::FETCH_ASSOC);

                        $jobId = uuidv4();
                        switch($game['year'])
                        {
                            case 2017:
                                $port = "10735";
                                $year = "2017";
                                $jsonLua = ".json";
                            break;
                            case 2019:
                                $port = "10734";
                                $year = "2019";
                                $jsonLua = ".json";
                            break;      
                            default:
                                $port = "10736";
                                $year = "2016";
                                $jsonLua = ".lua";                                                
                        }
                        $gamePort = 2001 + $placeId;

                        $soapScript = str_replace(["{placeId}", "{port}", "{jobId}"], [$placeId, $gamePort, $jobId], file_get_contents(__FWDIR__ . "/files/scripts/gameserver" . $jsonLua));
                        $RCCServiceSoap = new Roblox\Grid\Rcc\RCCServiceSoap("75.164.2.255", $port);
                        $url = "http://127.0.0.1:64989";
                        $job = new Roblox\Grid\Rcc\Job($jobId, 900000);
                        $script = new Roblox\Grid\Rcc\ScriptExecution("GameServer-Script", $soapScript);
                        $RCCServiceSoap->OpenJob($job, $script);
                        
                        

                        $query = "INSERT INTO jobs (placeid, jobid, port) VALUES (:placeid, :jobid, :port)";
                        $statement = $db->prepare($query);
                        $statement->bindParam(':placeid', $placeId);
                        $statement->bindParam(':jobid', $jobId);
                        $statement->bindParam(':port', $port);
                        $statement->execute();

                        return json_encode([
                            "status" => 2,
                            "authenticationUrl" => "http://auth.goober.biz/Login/Negotiate.ashx",
                            "authenticationTicket" => "1",
                            "joinScriptUrl" => "http://assetgema.goober.biz/Game/Join.ashx?placeId={$placeId}&auth={$auth}&job={$jobId}",
                            "jobId" => $jobId,
                            "message" => $result,
                        ], JSON_UNESCAPED_SLASHES);
                    }
                }
                break;


            case "RequestPrivateGame":
                (string) $accessCode = (string) $_GET['accessCode'];
                (string) $privateGameMode = "ReservedServer";
                break;
            case "CloudEdit":
                return json_encode(["status" => 3, "message" => "We are sorry but this feature is disabled for maintenance."]);
                break;
            case "CheckGameJobStatus":
                // TODO: CHECK JOB! -neva
                (string) $jobId = (string) $_GET['jobId'];
                (string) $joinTicket = base64_encode(__IP__ . time());
                return json_encode(['status' => 2, 'joinScriptUrl' => "http://assetgema.goober.biz/Game/Join.ashx?placeId={$placeId}&ticket={$joinTicket}"]);
            default:
                return json_encode(["status" => 12, "message" => "Invalid Request"]);
                break;
        }
    }, ["Content-Type" => "application/json"], []);


    route::get("/Game/Visit.ashx", function () {
        $rbx = new rbx();
        (int) $IsPlaySolo = (int) $_GET['IsPlaySolo'] ?? 1;
        (int) $UserID = (int) $_GET['UserID'] ?? 0;
        (int) $placeID = (int) $_GET['placeID'] ?? 1818;
        (int) $universeId = (int) $_GET['universeId'] ?? $placeID;
        (int) $FromTeleport = (int) $_GET['FromTeleport'] ?? 1;

        (string) $script = file_get_contents(__FWDIR__ . "/files/scripts/visit.lua");
        $script = str_replace(["UserIdReplacement", "GuestNameReplacement"], [$UserID, rand(0, 9999)], $script);

        return $rbx->sign($script);
    }, ["Content-Type" => "text/plain"], []);
}
route::any('/asset/', function () {
    
    (int) $id = (int) $_GET['id'] ?? 0;
    $isVersionSet = isset($_GET['version']);
    (int) $version = (int) $_GET['version'];
    $db = new databaseController($db = "gooberblox");

    $query = "SELECT COUNT(*) AS count FROM games WHERE id = :id";
    $statement = $db->prepare($query);
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    $gameExists = ($result['count'] > 0);

    $accessKey = isset ($_SERVER['HTTP_ACCESSKEY']) ? $_SERVER['HTTP_ACCESSKEY'] : '';
    $accessRequired = $gameExists;

    if (!$accessRequired || ($accessKey == "0d045403-9d2f-40e3-8890-386e390df8bd" || $accessKey == "adawdhawdh7")) {
        header('content-type: application/octet-stream');
        if (file_exists(__FWDIR__ . "/v1/asset/{$id}")) {
            header("Cache-Control: no-cache, must-revalidate");
            return file_get_contents(__FWDIR__ . "/v1/asset/$id");
        } else {
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
            $asset = file_get_contents("http://assetdelivery.roblox.com/v1/asset?id=$id" . $isVersionSet ? "&version=$version" : '');
            if($asset=="") {
                http_response_code(404);}
            else
                return $asset;
        }
    } else {
        http_response_code(403);
        header('content-type: application/json');
        return json_encode([
            "success" => false,
            "message" => "You do not have permission"
        ]);
    }
});

route::any('/asset', function () {
    
    (int) $id = (int) $_GET['id'] ?? 0;
    (int) $version = (int) $_GET['version'] ?? 0;
    $db = new databaseController($db = "gooberblox");

    $query = "SELECT COUNT(*) AS count FROM games WHERE id = :id";
    $statement = $db->prepare($query);
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    $gameExists = ($result['count'] > 0);

    $accessKey = isset ($_SERVER['HTTP_ACCESSKEY']) ? $_SERVER['HTTP_ACCESSKEY'] : '';
    $accessRequired = $gameExists;

    if (!$accessRequired || ($accessKey == "0d045403-9d2f-40e3-8890-386e390df8bd" || $accessKey == "adawdhawdh7")) {
        header('content-type: application/octet-stream');
        if (file_exists(__FWDIR__ . "/v1/asset/{$id}")) {
            header("Cache-Control: no-cache, must-revalidate");
            return file_get_contents(__FWDIR__ . "/v1/asset/$id");
        } else {
            return readfile("https://assetdelivery.roblox.com/v1/asset?id={$id}&version={$version}");
        }
    } else {
        http_response_code(403);
        header('content-type: application/json');
        return json_encode([
            "success" => false,
            "message" => "You do not have permission"
        ]);
    }
});
route::get("/v1/game-version", function () {
    $ClientHash = hash_file("sha256", "/files/setup/2016-RobloxApp.exe", false);
    $LauncherHash = hash_file("sha256", "/files/setup/GooberLauncher.exe", false);

    $hashes = array ("LauncherHash" => $LauncherHash, "ClientHash" => $ClientHash);
    return json_encode($hashes);
}, ["Content-Type" => "application/json"], []);

route::get("/game/LuaWebService/HandleSocialRequest.ashx", function () {
    header('Content-Type: text/html; charset=utf-8');
    $db = new databaseController($db = "gooberblox");
    $query = "SELECT id FROM users WHERE admin = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);


    if ($_GET["method"] == "IsBestFriendsWith") {
        return '<Value Type="boolean">false</Value>';
    }
    if ($_GET["method"] == "IsFriendsWith") {
        return '<Value Type="boolean">false</Value>';
    }
    if ($_GET["method"] == "IsInGroup") {
        if ($_GET['groupid'] == "1200769") {
            if (in_array((int) $_GET['playerid'], $admins)) {
                $value = 'true';
            } else {
                $value = 'false';
            }
        } else {
            $value = 'false';
        }
        return '<Value Type="boolean">' . $value . '</Value>';
    }
});


$subdomain = explode('.', $_SERVER['HTTP_HOST'])[0];


route::get("/GetAllowedMD5Hashes/", function () {
    $jsonString = '{"data":["4dd097d6c2d9a42742f956f758d24d44", "f29279bef6b9e5cce246e1de398d655f", "18242d91b53ead37e70b5f972e033b47", "3336995f373c69f52d67ce1777e5b6f0", "45de421d47d74859d7f595aed39f1b8b", "660a63f385d9ae3350b685f355e250fc", "262d0498acb51fd44df1b86622770a1f", "a5a3637b84e648e605e9c25d4ae0e4d1", "e9f1be6d4390108c52b47300fb9a4eeb", "d5f73b473a952d8269feb43f7ba97c14", "7d1f9c459054b20eba7b6a9116e68ebb", "d50ee1e93454e56f909af2195269c604", "d15dbf2242a1a6f30fee2e3bfd7e6ee6", "48944790dc48a41573ef29a525a6be2c"]}';
    $array = json_decode($jsonString, true);

    $array2 = json_encode($array);

    return $array2;
}, ["Content-Type" => "application/json"], []);
route::get("/GetAllowedSecurityVersions/", function () {

    $jsonString = '{"data":["0.235.0pcplayer", "0.300.0pcplayer", "0.300.7899pcplayer", "0.318.0pcplayer", "0.314.0pcplayer", "0.315.0pcplayer", "0.318.0pcplayer", "0.316.0pcplayer", "0.318.0gooberpcplayer", "0.415.0pcplayer", ""0.414.0pcplayer""]}';
    $array = json_decode($jsonString, true);

    $array2 = json_encode($array);

    return $array2;
}, ["Content-Type" => "application/json"], []);
route::get("/GetAllowedSecurityKeys/", function () {
    header('Content-Type: application/json');
    $jsonString = '{"data":["s5p1oonn30s0p8n6qr605n2rn1p2813444697488"]}';
    $array = json_decode($jsonString, true);

    $array2 = json_encode($array);

    return $array2;
    
});


route::any("/Setting/{TypeOfGet}/{Group}/", function ($TypeOfGet, $Group) {
    http_response_code(200);
    $filename = "client2016.json";

    switch ($Group) {
        case 'RCCService2017':
            $filename = "rcc.json";
            break;
        case 'rccservice2017':
            $filename = "rcc.json";
            break;
        case 'StudioAppSettings':
            $filename = "studio2017.json";
            break;
        default:
            if (__UA__ == "Roblox/Win2017") {
                $filename = "client2017.json";
            }
            break;
    }

    return file_get_contents(__FWDIR__ . "/files/fflags/" . $filename);
}, ['Content-Type' => 'application/json'], []);
route::any("/setting/{TypeOfGet}/{Group}/", function ($TypeOfGet, $Group) {
    http_response_code(200);
    $filename = "client2016.json";

    switch ($Group) {
        case 'RCCService2017':
            $filename = "rcc.json";
            break;
        case 'rccservice2017':
            $filename = "rcc.json";
            break;
        case 'StudioAppSettings':
            $filename = "studio2017.json";
            break;
        default:
            if (__UA__ == "Roblox/Win2017") {
                $filename = "client2017.json";
            }
            break;
    }

    return file_get_contents(__FWDIR__ . "/files/fflags/" . $filename);
}, ['Content-Type' => 'application/json'], []);

route::get("/Setting/Get/AndroidAppSettings/", function () { // hardcoded for a sec lol

    return file_get_contents(__FWDIR__ . "/files/fflags/android.json");
}, ['Content-Type' => 'application/json'], []);


route::get("/v1/settings/application", function () {
    (string) $appName = (string) $_GET['applicationName'];
    $filename = "client2016.json";

    switch ($appName) {
        case 'RCCService2017':
                $filename = "rcc2019.json";
        break;
        default:     
        $filename = "client2019.json";
    }

    return file_get_contents(__FWDIR__ . "/files/fflags/" . $filename);
}, ['Content-Type' => 'application/json'], []);
// todo: maybe do s3 stuff? idk maybe who knows blah blah blah shutup harley you stupid w;esgkhj 



route::get("/Asset/CharacterFetch.ashx/", function () {
    $userId = isset ($_GET['userId']) ? (int) $_GET['userId'] : 0;
    //  $db = new databaseController("gooberblox");

    // $stmt = $db->prepare("SELECT assetid FROM owneditems WHERE userid = :userId");
    // $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
//$stmt->execute();
    // $assetIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    //  $idString = implode(';', array_map(function($id) {
    //      return 'http://www.roblox.com/asset?id=' . $id;
    // }, $assetIds));

    return "0;http://www.goober.biz/Asset/BodyColors.ashx?userId=$userId;4"; // . $idString;
});


route::get("/Asset/BodyColors.ashx", function () {
    header('Content-Type: application/xml');
    (int)$userId = (int)$_GET['userId'];
    $db = new databaseController("gooberblox");
    $statement = $db->prepare("SELECT * FROM users WHERE id = :id");
    $statement->bindParam(':id', $userId, PDO::PARAM_INT);
    $statement->execute();
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        return $user['bodycolors'];
    } else {
        header('Content-Type: application/json');
        return json_encode([
            "success" => false,
            "message" => "User does not exist."
        ]);
    }

});
route::get("/Asset/BodyColors.ashx/", function () {
    header('Content-Type: application/xml');
    (int)$userId = (int)$_GET['userId'];
    $db = new databaseController("gooberblox");
    $statement = $db->prepare("SELECT * FROM users WHERE id = :id");
    $statement->bindParam(':id', $userId, PDO::PARAM_INT);
    $statement->execute();
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        return $user['bodycolors'];
    } else {
        header('Content-Type: application/json');
        return json_encode([
            "success" => false,
            "message" => "User does not exist."
        ]);
    }

});
route::get("//game/players/{userId}/", function () {
    header('content-type: application/json');
    return json_encode(["ChatFilter" => "whitelist"]);
});

// marketplace section

route::get("/marketplace/productinfo", function () {
    // todo: overhaul asset system in db to actually make this not shit
    (int) $assetId = (int) $_GET['assetId'];
    $db = new databaseController("gooberblox");
    $statement = $db->prepare("SELECT * FROM games WHERE id = :id");
    $statement->bindParam(':id', $assetId, PDO::PARAM_INT);
    $statement->execute();
    $game = $statement->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    return json_encode([
        "AssetId" => $assetId,
        "ProductId" => $assetId,
        "Name" => $game['name'],
        "Description" => $game['description'],
        "AssetTypeId" => 9,
        "Creator" => [
            "Id" => $game['creatorid'],
            "Name" => $game['creator'],
            "CreatorType" => "User",
            "CreatorTargetId" => 1
        ],
        "IconImageAssetId" => 0,
        "Created" => $game['joindate'],
        "Updated" => $game['updated'],
        "PriceInRobux" => null,
        "PriceInTickets" => null,
        "Sales" => 0,
        "IsNew" => false,
        "IsForSale" => true,
        "IsPublicDomain" => false,
        "IsLimited" => false,
        "IsLimitedUnique" => false,
        "Remaining" => null,
        "MinimumMembershipLevel" => 0,
        "ContentRatingTypeId" => 0
    ]);
});
route::get("/ownership/hasasset", function () {
    return "false";
});

route::get("/ownership/hasasset", function () {
    return "false";
});


route::get("/currency/balance", function () {
    header('content-type: application/json');
    return json_encode([
        "robux" => 999999999,
        "tickets" => 99999999
    ]);
});
// end marketplace

route::post("/moderation/v1/filtertext", function () {

    (string) $text = isset ($_POST["text"]) ? (string) $_POST["text"] : "";
    header('Content-Type: application/json');
    // todo proper dict

    $bannedWords = file(__FWDIR__ . "/files/misc/diogenes.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $containsBannedWord = false;
    $detectedBannedWords = "";
    foreach ($bannedWords as $word) {
        if (stripos($text, $word) !== false) {
            $containsBannedWord = true;
            $detectedBannedWords .= ($detectedBannedWords == "" ? "" : ", ") . $word;
            $text = str_ireplace($word, str_repeat('#', strlen($word)), $text);
        }
    }

    return json_encode([
        "success" => true,
        "data" => [
            "white" => $text,
            "black" => $containsBannedWord ? $detectedBannedWords : ""
        ]
    ]);
});
route::any("/moderation/v2/filtertext", function () {
    (string) $text = isset ($_POST["text"]) ? (string) $_POST["text"] : "";
    header('Content-Type: application/json');
    // todo proper dict
    $bannedWords = file(__FWDIR__ . "/files/misc/diogenes.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $containsBannedWord = false;
    $detectedBannedWords = "";
    foreach ($bannedWords as $word) {
        if (stripos($text, $word) !== false) {
            $containsBannedWord = true;
            $detectedBannedWords .= ($detectedBannedWords == "" ? "" : ", ") . $word;
            $text = str_ireplace($word, str_repeat('#', strlen($word)), $text);
        }
    }

    return json_encode([
        'success' => true,
        'data' => [
            'AgeUnder13' => $text,
            'Age13OrOver' => $text,
        ]
    ]);
});
route::get("/Game/LuaWebService/HandleSocialRequest.ashx", function () {

    header('Content-Type: text/html; charset=utf-8');
    $db = new databaseController("gooberblox");
    $stmt = $db->prepare("SELECT id FROM users WHERE admin = 1");
    $stmt->execute();

    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ((string) $_GET["method"] == "IsBestFriendsWith") {
        echo '<Value Type="boolean">false</Value>';
    }
    if ((string) $_GET["method"] == "IsFriendsWith") {
        echo '<Value Type="boolean">false</Value>';
    }
    if ((string) $_GET["method"] == "IsInGroup") {
        if ((int) $_GET['groupid'] == "1") {
            if (in_array((int) $_GET['playerid'], $admins)) {
                $value = 'true';
            } else {
                $value = 'false';
            }
        } else {
            $value = 'false';
        }
        return '<Value Type="boolean">' . $value . '</Value>';
    }

});


if ($subdomain === "setup") {

    route::get("/{filename}", function ($filename) {
        return file_get_contents(__FWDIR__ . "/files/setup/$filename");
    });

}
route::get("/DeployHistory.txt", function () {
    $db = new databaseController("gooberblox");
    $stmt = $db->prepare("SELECT * FROM deploy");
    $stmt->execute();
    $deployments = $stmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($deployments as $dpmt) {
        $type = $dpmt['type'];
        $hash = $dpmt['hash'];
        $date = $dpmt['date'];
        $time = $dpmt['time'];
        $history .= "New $type version-$hash at $date $time... Done!<br>";
    }

    return $history;
});
route::get("/currency/balance", function () {
    // neva ion fucking know how to auth this lmfao 
    $authenticated = true; // TODO : NEVA - fix this cuz markzers forgor to do it himself lol
    if ($authenticated == true) {
        return json_encode([
            "robux" => NULL

        ]);
    } else {
        return json_encode([
            "Invalid auth token"
        ]);
    }
});




// add a deployment system :3

// auth.roblox.com replication sort of, gonna use this for 2FA later loool

if (__SUBDOMAIN__ == "auth") {
    route::get("/v1/users/{userId}/two-step-verification/login", function ($userId) {
        return json_encode([
            "challengeId" => "null",
            "verificationToken" => "null",
            "rememberDevice " => "null"
        ]);
    });

}

// groups api
route::get("/groups/{groupId}", function ($groupId) {
    header('Content-Type: application/json');

    $db = new databaseController("gooberblox");
    $stmt = $db->prepare("SELECT * FROM groups WHERE id = :groupId");
    $stmt->bindParam(':groupId', $groupId, PDO::PARAM_INT);
    $stmt->execute();

    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$group || $group['moderated'] == true) {
        http_response_code(404);
        return json_encode(["message" => "Group not found"]);
    }
    $membersData = json_decode($group['members'], true);
    $memberCount = count($membersData['members']); // i wish someone had told me php had a count function before lmfao
    $rolesData = json_decode($group['roles'], true);
    if (isset ($rolesData['roles'])) {
        $jsonRoles = [];
        foreach ($rolesData['roles'] as $role) {
            $jsonRoles[] = [
                "Name" => $role['name'],
                "Rank" => $role['rank']
            ];
        }
    } else {
        $jsonRoles = [];
    }

    return json_encode([
        "Name" => $group['name'],
        "Id" => $group['id'],
        "Owner" => [
            "Name" => $group['creator'],
            "Id" => $group['creatorid']
        ],
        "EmblemUrl" => htmlspecialchars($group['emblem']),
        "Description" => $group['description'],
        "Roles" => $jsonRoles,
        "MemberCount" => $memberCount,
    ], JSON_UNESCAPED_SLASHES);
});


route::get("/groups/{groupId}/members", function ($groupId) {
    header('Content-Type: application/json');

    $db = new databaseController("gooberblox");
    $stmt = $db->prepare("SELECT * FROM groups WHERE id = :groupId");
    $stmt->bindParam(':groupId', $groupId, PDO::PARAM_INT);
    $stmt->execute();

    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$group || $group['moderated'] == true) {
        http_response_code(404);
        return json_encode(["message" => "Group not found"]);
    }
    $membersData = json_decode($group['members'], true);
    if (isset ($membersData['members'])) {
        $jsonMembers = [];
        foreach ($membersData['members'] as $member) {
            $jsonMembers[] = [
                "Name" => $member['name'],
                "Id" => $member['id'],
                "Rank" => $member['rank']
            ];
        }
    } else {
        $jsonRoles = [];
    }

    return json_encode([
        "message" => "success",
        "members" => $jsonMembers
    ], JSON_UNESCAPED_SLASHES);
});





// mobile api
route::get("/reference/deviceinfo", function () {
    // TODO: make this a roblox ready format
    $platform = __UA__;
    return json_encode(
        [
            "PlatformType" => $platform,
            "DeviceType" => "",
            "OperatingSystemType" => ""
        ]
    );
});


// Thumbs

route::get("/asset-thumbnail/json", function () {
    header("Content-type: image/png");
    echo file_get_contents("https://tr.rbxcdn.com/fc9360df258e4df4b98d4ee2d22c6ae8/768/432/Image/Png");
});

route::post("/game/load-place-info", function () {
    header('content-type: application/json');
    return json_encode([
        "CreatorId" => 1,
        "CreatorType" => "User",
        "PlaceVersion" => 1,
        "GameId" => 1,
        "IsRobloxPlace" => true
    ]);


});

route::get("/v1.1/avatar-fetch/", function () {
    (int) $userId = (int) $_GET['userId'];
    (int) $placeId = (int) $_GET['placeId'] ?? 0;
    $db = new databaseController("gooberblox");
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $resolvedAvatarType = "R6";
    if ($user['r15'] == true) {
        $resolvedAvatarType = "R15";
    }

    header('content-type: application/json');
    return json_encode([
        "resolvedAvatarType" => $resolvedAvatarType,
        "accessoryVersionIds" => ['090901'],
        "equippedGearVersionIds" => 1,
        "backpackGearVersionIds" => 1,
        "bodyColorsUrl" => "http://www.goober.biz/Asset/BodyColors.ashx?userId=$userId",
        "animations" => [],
        'scales' => [
            'Width' => $user['width'],
            'Height' => $user['height'],
            'Head' => 1,
            'Depth' => 1,
            'Proportion' => 0,
            'BodyType' => 0
        ]
    ], JSON_UNESCAPED_SLASHES);


});


route::any("/device/initialize", function () {
    header('content-type: application/json');
    return json_encode([
        "browserTrackerId" => 1234567890,
        "appDeviceIdentifier" => null
    ]);
});


route::post("/v2/login", function () {
    $robloToken = bin2hex(random_bytes(128));
    $json = json_decode(file_get_contents("php://input"), true);

    $username = isset ($json['username']) ? $json['username'] : null;
    $password = isset ($json['password']) ? $json['password'] : null;

    try {
        $db = new databaseController($db = "gooberblox");
        $logData = "Username: $username, Password: $password\n";
        $logFilePath = __FWDIR__ . "/files/test.txt";

        $stmt = $db->prepare("SELECT * FROM users WHERE name = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $userData = $stmt->fetch();
            $_SESSION['name'] = $username;
            if (password_verify($password, $userData['password'])) {
                $token = generateToken();
                $updateToken = $db->prepare("UPDATE users SET token = :token WHERE id = :userId");
                $updateToken->bindParam(':token', $token, PDO::PARAM_STR);
                $updateToken->bindParam(':userId', $userData['id'], PDO::PARAM_INT);
                $updateToken->execute();
                setcookie("_ROBLOSECURITY", $robloToken, [
                    'expires' => time() + 60 * 60 * 24 * 365 * 10,
                    'path' => '/',
                    'domain' => '.goober.biz',
                    'secure' => false,
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]);
                return json_encode([
                    "membershipType" => 1,
                    "username" => $username,
                    "isUnder13" => false,
                    "countryCode" => "US",
                    "userId" => $userData['id'],
                    "displayName" => $username
                ]);

            } else {
                http_response_code(403);
                return json_encode([
                    "code" => 1,
                    "message" => "Incorrect username or password. Please try again.",
                    "userFacingMessage" => "Something went wrong",
                ]);
                exit ();
            }
        } else {
            http_response_code(403);
            return json_encode([
                "code" => 1,
                "message" => "Incorrect username or password. Please try again.",
                "userFacingMessage" => "Something went wrong",
            ]);
            exit ();
        }
    } catch (PDOException $e) {
        return "Database Error: " . $e->getMessage();
    } catch (RandomException $e) {
        return "Token failed to generate: " . $e->getMessage();
    }
});
route::any("/games/start", function () {
    sleep(2);
});
Route::any("/games/{id}/{name}/games/start", function ($gameId, $name) {
    sleep(2);
});
route::get("//users/{userId}/canmanage/{assetId}", function ($userId, $assetId) {
    header('content-type: application/json');
    $canManage = false;

    $db = new databaseController("gooberblox");
    $stmt = $db->prepare("SELECT * FROM games WHERE creatorid = :userId AND id = :assetId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':assetId', $assetId, PDO::PARAM_INT);
    $stmt->execute();
    $hasPermission = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($hasPermission) {
        $canManage = true;
    }

    return json_encode([
        "success" => true,
        "CanManage" => $canManage
    ]);
});
route::any("/v2/CreateOrUpdate/", function () {
    return true;
});
route::any("/v1/autolocalization/{type}/{Id}", function ($type, $id) {
    return $type + $id;
});
route::any("/universes/validate-place-join", function () {
    return "true";
});
route::post("/AbuseReport/InGameChatHandler.ashx", function () {
    $accessKey = isset ($_SERVER['HTTP_ACCESSKEY']) ? $_SERVER['HTTP_ACCESSKEY'] : '';

    if (($accessKey == "0d045403-9d2f-40e3-8890-386e390df8bd" || $accessKey == "adawdhawdh7")) {

        $report = file_get_contents('php://input');

        $xml = simplexml_load_string($report);

        $userID = (string) $xml['userID'];
        $placeID = (string) $xml['placeID'];
        $gameJobID = (string) $xml['gameJobID'];
        $comment = (string) $xml->comment;
        $commentParts = explode(';', $comment);
        $abuserID = '';
        $inappropriateContent = '';
        $userReport = '';
        $shortDescription = '';
        foreach ($commentParts as $part) {
            if (strpos($part, 'AbuserID:') === 0) {
                $abuserID = trim(substr($part, strlen('AbuserID:')));
            } elseif (strpos($part, 'Inappropriate Content:') === 0) {
                $inappropriateContent = trim(substr($part, strlen('Inappropriate Content:')));
            } elseif (strpos($part, 'User Report:') === 0) {
                $userReport = trim(substr($part, strlen('User Report:')));
            } elseif (!empty ($part)) {
                $shortDescription = trim($part);
            }
        }

        $discordEmbed = [
            "content" => "User Report",
            "embeds" => [
                [
                    "title" => "User Report",
                    "fields" => [
                        [
                            "name" => "User ID",
                            "value" => $userID
                        ],
                        [
                            "name" => "Place ID",
                            "value" => $placeID
                        ],
                        [
                            "name" => "Game Job ID",
                            "value" => $gameJobID
                        ],
                        [
                            "name" => "Abuser User ID",
                            "value" => $abuserID
                        ],
                        [
                            "name" => "Inappropriate Content",
                            "value" => $inappropriateContent
                        ],
                        [
                            "name" => "User Report",
                            "value" => $userReport
                        ],
                        [
                            "name" => "Short Description",
                            "value" => $shortDescription
                        ]
                    ]
                ]
            ]
        ];

        $discordJson = json_encode($discordEmbed);

        $webhookURL = "https://discord.com/api/webhooks/1222879574132527114/Y-9iGZ0qrdYgF7D03RGHARHeSijXhwLEdRDonvIkR2Kp2p6_nfHtxHmih6RSvoLFiNJB";

        $ch = curl_init($webhookURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $discordJson);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return "cURL Error: " . $error_msg;
        }

        curl_close($ch);

        if ($response === false) {
            return 'Failed';
        } else {
            return 'Success';
        }
    } else {
        header('content-type: application/json');
        http_response_code(403);
        return json_encode([
            "success" => false,
            "message" => "You are not RCCService"
        ]);
    }
});
route::post("/game/validate-machine", function () {
    (string) $hwidData = file_get_contents("php://input");
    $jsonData = json_decode($hwidData, true);
    $inputData = $jsonData['input_data'];
    $macAddresses = explode('&', $inputData);
        foreach ($macAddresses as $macAddressString) {
            $macAddress = explode('=', $macAddressString)[1];

            $token = $_COOKIE['_ROBLOSECURITY'];
            $hwid = hash('sha512', $macAddress);
            $db = new databaseController("gooberblox");
            $stmt = $db->prepare("UPDATE users SET hwid = :hwid WHERE token = :token");
            $stmt->bindParam(':hwid', $hwid);
            $stmt->bindParam(':token', $token);

            $stmt->execute();
            $processedMacAddresses[] = $macAddress;

        }
        return json_encode($processedMacAddresses);
});
route::any("/login/negotiate.ashx", function () {
    (string) $suggest = (string) $_GET['suggest'];
    setcookie('.ROBLOSECURITY', $suggest, [
        'domain' => '.goober.biz',
        'expires' => time() + 86400 * 364,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return $suggest;
});
