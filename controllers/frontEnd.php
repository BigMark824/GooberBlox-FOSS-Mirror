<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
ini_set('upload_max_filesize', '25M');
ini_set('post_max_size', '25M');
use core\route;
use core\modules\twig;
use dbController\databaseController;
use soapController\soapController;
use ecoController\EconomySystem;
use renderController\renderController;
use PHPGangsta_GoogleAuthenticator;
use core\conf;
@require('miscAPIs.php');
// todo: kill myself
@require('databaseController.php');
@require('gridController.php');
use Roblox\Grid\Rcc\RCCServiceSoap;
$db = new databaseController("gooberblox");
$query = "SELECT * FROM users WHERE token = :userToken";
$statement = $db->prepare($query);
$statement->bindParam(':userToken', $token);
$statement->execute();

$user = $statement->fetch(PDO::FETCH_ASSOC);
 
if ($user) {
    if ($user['moderated'] == 1) {
        header("Location: /banned");
        exit(); 
    }
}

// probs will classify this :3
function getUserByToken($token)
{
    $db = new databaseController("gooberblox");
    $query = "SELECT * FROM users WHERE token = :userToken";
    $statement = $db->prepare($query);
    $statement->bindParam(':userToken', $token);
    $statement->execute();
    
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['moderated'] == 1) {
        header("Location: /banned");
        exit(); 
    }

    return $user;
}

route::addcategory("gooberblox", function () {
    $maintenance = conf::get()['project']['maintenance']['value'];
    if (__IP__ != "206.83.122.39" && __IP__ != "127.0.0.1" && __IP__ != "86.183.131.141" && $maintenance == true) {
        $reason = conf::get()['project']['maintenance']['reason'];
        $time = conf::get()['project']['maintenance']['time'];
        echo twig::view("gooberblox/pages/maint.twig", ["eta" => $time, "reason" => $reason]);
        exit();
    }
});
route::addcategory("admin", function () { 
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user['admin']) { 
        nxss($username = $user['name']);
        header("Location: /");
    }

});
route::addcategory("owner", function () { 
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user['id'] == 1 || !$user['id'] == 320) { 
        nxss($username = $user['name']);
        header("Location: /");
    }

});
route::addcategory("isloggedin", function () {
if(!isset(getUserByToken($_COOKIE['user_token'])['name'])) {
    return false;
}

});

route::get("/", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $isIPhone = strpos($userAgent, 'iPhone') !== false;
    
    if ($user) {
        $username = $user['name'];
        $db = new databaseController("gooberblox");

        $query = 'SELECT name, player_count, id, thumbnail FROM games LIMIT 10';
        $statement = $db->prepare($query);
        $statement->execute();
        $games = $statement->fetchAll(PDO::FETCH_ASSOC);

        (int)$gameId = (int)$_GET['game_id'] ?? null;
        $gameDetails = null;
        if ($gameId !== null) {
            $query = 'SELECT name, player_count, id, thumbnail FROM games WHERE id = :id LIMIT 10';
            $statement = $db->prepare($query);
            $statement->bindParam(':id', $gameId, PDO::PARAM_INT);
            $statement->execute();
            $gameDetails = $statement->fetch(PDO::FETCH_ASSOC);
        }
        $robux = $user['robux'];
        return twig::view("gooberblox/loggedin/index.twig", [
            'username' => $username,
            'pagename' => "Home",
            'games' => $games,
            'gameDetails' => $gameDetails,
            'robux' => $robux,
            'user' => $user,
            'isIPhone' => $isIPhone
        ]);
    }

    header("Location: /login");
}, [], ["category" => ["gooberblox"]]);

// gameserver test
route::get("/testserver", function () {
    $soapScript = str_replace(["{placeId}", "{port}", "{jobId}"], [1, 2004, "balls"], file_get_contents(__FWDIR__ . "/files/scripts/gameserver.json"));
    $RCCServiceSoap = new Roblox\Grid\Rcc\RCCServiceSoap("127.0.0.1", 10734);
    $url = "http://127.0.0.1:64989";
    $job = new Roblox\Grid\Rcc\Job("balls", 900000);
    $script = new Roblox\Grid\Rcc\ScriptExecution("GameServer-Script", $soapScript);
    $RCCServiceSoap->OpenJobEx($job, $script);
}, [], ["category" => ["gooberblox"]]);


route::get("/sign-out/v1", function () {
    setcookie('user_token', '', time() - 1, "/");
    header("Location: /");
}, [], ["category" => ["gooberblox"]]);


route::get("/facts", function () {
    $facts = ['Thugshaker is cool!', "Mssky is a nerd", "neva is cool", "qKit is cool", "Also play ROBLOX", "Bloom is cool", "Aep is cool", "hey letter E ;)"];
    $factsArray = $facts[array_rand($facts)];

    return '<b> - ' . $factsArray . '</b>';
});
route::get("/news", function () {
    $news = ['2017 Release', 'Road to 2k'];   

    foreach ($news as $item) {
        $newsArray .= '<b> - ' . $item . '</b><br>';
    }

    return $newsArray;
});

route::get("/client", function (){



});


route::get("/games", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'pc';
    $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    switch ($filter) {
        case 'pc':
            $order = 'player_count DESC';
            break;
        case 'gv':
            $order = 'visits ASC';
            break;
        case 'ao':
            $order = 'name DESC';
            break;
        default:
            $order = 'player_count DESC';
            break;
    }
    
    if ($user) {
        $username = $user['name'];
        $db = new databaseController("gooberblox");

        $query = "SELECT name, player_count, id, thumbnail FROM games WHERE LOWER(name) LIKE LOWER(:search) ORDER BY $order";
        $statement = $db->prepare($query);
        $statement->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $statement->execute();
        $games = $statement->fetchAll(PDO::FETCH_ASSOC);

        (int)$gameId = (int)$_GET['game_id'] ?? null;
        $gameDetails = null;
        if ($gameId !== null) {
            $query = 'SELECT name, player_count, id, thumbnail FROM games WHERE id = :id';
            $statement = $db->prepare($query);
            $statement->bindParam(':id', $gameId, PDO::PARAM_INT);
            $statement->execute();
            $gameDetails = $statement->fetch(PDO::FETCH_ASSOC);
        }
        $robux = $user['robux'];
        return twig::view("gooberblox/pages/games.twig", [
            'username' => $username,
            'pagename' => "Games",
            'games' => $games,
            'gameDetails' => $gameDetails,
            'robux' => $robux,
            'user' => $user
        ]);
    }

    header("Location: /login");
}, [], ["category" => ["gooberblox"]]);


route::get("/GamesRCCRenders/{filename}", function ($filename) {
    return file_get_contents("https://" . __baseurl__ . "/GamesRCCRenders/$filename");

}, ["Content-Type" => "image/png"], []);
Route::get("/games/{id}/{name}", function ($gameId, $name) {
    // im gonna refactor this lol
    $pdo = new databaseController("gooberblox");
    if ($pdo) {
        $query = 'SELECT name, creator, creatorid, description, joindate, updated, thumb1, thumb2, player_count, visits, year, id FROM games WHERE id = :id';
        $clientAuth = $_COOKIE['user_token'];
        $statement = $pdo->prepare($query);
        $statement->bindParam(':id', $gameId, PDO::PARAM_INT);
        $statement->execute();
        $gameDetails = $statement->fetch(PDO::FETCH_ASSOC);

        if ($gameDetails) {
            $userToken = $_COOKIE['user_token'] ?? null;
            $user = getUserByToken($userToken);

            if ($user)
                $username = nxss($user['name']);
            else
                return header("Location: /login");
            $robux = $user['robux'];

            $name = $gameDetails['name'];
            $creator = nxss($gameDetails['creator']);
            $creatorid = intval($gameDetails['creatorid']);
            $description = strval($gameDetails['description']);
            $creation = nxss($gameDetails['joindate']);
            $updated = nxss($gameDetails['updated']);
            $thumb1 = nxss($gameDetails['thumb1']);
            $thumb2 = nxss($gameDetails['thumb2']);
            $count = nxss($gameDetails['player_count']) ?? 0;
            $placeId = nxss($gameDetails['id']);
            $year = nxss($gameDetails['year']);
            $visits = nxss($gameDetails['visits'] ?? 0);
            return Twig::view("gooberblox/pages/game.twig", [
                "user" => $user,
                "name" => $username ?? "",
                "gamename" => $name,
                "creator" => $creator,
                "creatorid" => $creatorid,
                "description" => $description,
                "creation" => $creation,
                "updated" => $updated,
                "thumb1" => $thumb1,
                "thumb2" => $thumb2,
                "count" => $count,
                "pagename" => $name,
                "placeId" => $placeId,
                "gameAuth" => $clientAuth,
                "gamePort" => "2005",
                "robux" => $robux,
                "gameYear" => $year,
                "visits" => $visits ?? 0
            ]);
        } else {
            http_response_code(404);
            return Twig::view("gooberblox/responses/404.twig");
        }
    }
});


route::get("/v1/users", function () {

    $goober = new gb();
    return $goober->getUser();
});
// test
route::get("/v1/admins/", function () {
    if ( __IP__ !== __SERVER_IP__) {
        http_response_code(403);
        die('Forbidden');
    }
    $goober = new gb();
    return $goober->admins();
});

route::get("/login", function () {


    $cutenames = ['ugh1849', 'qzip', 'XlXi', 'dimitri', 'mathmark825'];
    $randomcutenames = $cutenames[array_rand($cutenames)];

    return twig::view("gooberblox/pages/login.twig", ["cutename" => $randomcutenames]);
});
route::get("/home", function () {
    header("Location: /");
});

route::post("/login", function () {
    // todo: finish
    $username = nxss($_POST['usr']);
    $password = nxss($_POST['pwd']);
    try {
        // probably not the best code tbh
        $db = new databaseController($db = "gooberblox");

        $stmt = $db->prepare("SELECT * FROM users WHERE name = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $userData = $stmt->fetch();
            $_SESSION['name'] = $username;
            if (password_verify($password, $userData['password'])) {
                // qzip: please check if it's not a duplicate
                // mathmark: no
                $token = generateToken();
                $updateToken = $db->prepare("UPDATE users SET token = :token WHERE id = :userId");
                $updateToken->bindParam(':token', $token, PDO::PARAM_STR);
                $updateToken->bindParam(':userId', $userData['id'], PDO::PARAM_INT);
                $updateToken->execute();
                setcookie('user_token', $token, time() + (86400 * 30), "/");

                $_SESSION['token'] = $token;
                $_SESSION['name'] = $username; // ??? use the above with a user controller, mathmarK: alright will do
                header("Location: /");
                exit();
            } else
                return twig::view("gooberblox/pages/login.twig", ["cutename" => $username, "error" => "Incorrect details, please try again!"]);
        } else
            return twig::view("gooberblox/pages/login.twig", ["cutename" => $username, "error" => "Incorrect details, please try again!"]);
    } catch (PDOException $e) {
        return "Database Error: " . $e->getMessage();
    } catch (RandomException $e) {
        return "Token failed to genreate: " . $e->getMessage();
    }
});

route::get("/register", function () {
    $cutenames = ['ugh1849', 'qzip', 'XlXi', 'dimitri', 'mathmark825'];
    $randomcutenames = $cutenames[array_rand($cutenames)];

    return twig::view("gooberblox/pages/register.twig", ["cutename" => $randomcutenames]);
});
route::get("/develop", function () {
    $db = new databaseController($db = "gooberblox");
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $robux = $user['robux'];
    
    if ($user) {
        $username = nxss($user['name']);
        
        $userId = $user['id'];
        $query = "SELECT * FROM games WHERE creatorid = :userId";
        $statement = $db->prepare($query);
        $statement->bindParam(':userId', $userId, PDO::PARAM_INT);
        $statement->execute();
        $games = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        return twig::view("gooberblox/pages/develop.twig", [
            'name' => $username,
            'robux' => $robux,
            'user' => $user,
            'games' => $games 
        ]);
    } else {
        header("Location: /login");
    }
});

route::get("/develop/edit-game/{gameId}", function($gameId) {
    $db = new databaseController($db = "gooberblox");
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $robux = $user['robux'];
    
    if ($user) {
        $username = nxss($user['name']);

        $query = "SELECT * FROM games WHERE id = :gameId";
        $statement = $db->prepare($query);
        $statement->bindParam(':gameId', $gameId, PDO::PARAM_INT);
        $statement->execute();
        $game = $statement->fetch(PDO::FETCH_ASSOC);

        $userId = $user['id'];
        return twig::view("gooberblox/pages/editGame.twig", [
            'name' => $username,
            'robux' => $robux,
            'user' => $user,
            'game' => $game 
        ]);
    } else {
        header("Location: /login");
    }
});
route::post("/develop/edit-game/details/{gameId}", function($gameId) {
    $db = new databaseController($db = "gooberblox");
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    
    if ($user) {
        $username = nxss($user['name']);
        $userId = $user['id'];

        $query = "SELECT * FROM games WHERE id = :gameId";
        $statement = $db->prepare($query);
        $statement->bindParam(':gameId', $gameId, PDO::PARAM_INT);
        $statement->execute();
        $game = $statement->fetch(PDO::FETCH_ASSOC);
        $currentDate = date('m-d-Y');
        if ($game && $game['creatorid'] == $userId) {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $version = $_POST['version'] ?? '';

            $updateQuery = "UPDATE games SET name = :name, description = :description, updated = :updated, year = :version WHERE id = :gameId";
            $updateStatement = $db->prepare($updateQuery);
            $updateStatement->bindParam(':name', $name);
            $updateStatement->bindParam(':description', $description);
            $updateStatement->bindParam(':version', $version);
            $updateStatement->bindParam(':gameId', $gameId, PDO::PARAM_INT);
            $updateStatement->bindParam(':updated', $currentDate, PDO::PARAM_INT);
            $updateStatement->execute();

            header("Location: /develop");
            exit(); 
        } else {
            header('content-type: application/json');
            return json_encode([
                "success" => 0,
                "message" => "You do not have permission"
            ]);
        }
    } else {
        header("Location: /login");
        exit();
    }
});
route::post("/develop/edit-game/file/{gameId}", function($gameId) {
    $db = new databaseController($db = "gooberblox");
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    
    if ($user) {
        $username = nxss($user['name']);
        $userId = $user['id'];

        $query = "SELECT * FROM games WHERE id = :gameId";
        $statement = $db->prepare($query);
        $statement->bindParam(':gameId', $gameId, PDO::PARAM_INT);
        $statement->execute();
        $game = $statement->fetch(PDO::FETCH_ASSOC);

        if ($game && $game['creatorid'] == $userId) {
            $file = $_FILES['file'];
            $fileTmpName = $file['tmp_name'];
            $fileError = $file['error'];

            if ($fileError === UPLOAD_ERR_OK) {
                $destination = __FWDIR__ . "/v1/asset/{$gameId}";
                move_uploaded_file($fileTmpName, $destination);

                $currentDate = date('Y-m-d');
                $updateQuery = "UPDATE games SET updated = :updated WHERE id = :gameId";
                $updateStatement = $db->prepare($updateQuery);
                $updateStatement->bindParam(':updated', $currentDate, PDO::PARAM_STR);
                $updateStatement->bindParam(':gameId', $gameId, PDO::PARAM_INT);
                $updateStatement->execute();

                header("Location: /develop");
                exit(); 
            } else {
                header('content-type: application/json');
                return json_encode([
                    "success" => 0,
                    "message" => "File Upload Failed"
                ]);
            }
        } else {
            header('content-type: application/json');
            return json_encode([
                "success" => 0,
                "message" => "You do not have permission"
            ]);
        }
    } else {
        header("Location: /login");
        exit();
    }
});
route::get("/develop/edit-game/getfile/{gameId}", function($gameId) {
    $db = new databaseController($db = "gooberblox");
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    
    if ($user) {
        $username = nxss($user['name']);
        $userId = $user['id'];

        $query = "SELECT * FROM games WHERE id = :gameId";
        $statement = $db->prepare($query);
        $statement->bindParam(':gameId', $gameId, PDO::PARAM_INT);
        $statement->execute();
        $game = $statement->fetch(PDO::FETCH_ASSOC);
        if ($game && $game['creatorid'] == $userId) {
            header('content-type: application/octet-stream');
            $file = file_get_contents(__FWDIR__ . "/v1/asset/$gameId");
            $uuid = uuidv4();
            header('Content-Disposition: attachment; filename="' . $uuid . '.rbxl');

            return $file;
        } else {
            header('content-type: application/json');
            return json_encode([
                "success" => 0,
                "message" => "You do not have permission"
            ]);
        }
    } else {
        header("Location: /login");
        exit();
    }
});

route::post("/develop", function () {
    // TODO: MATHMARK REFACTOR THIS HEAVILY, THIS IS NOT PRODUCTION WORTHY. USE THIS AS A TESTTESTTEST
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $db = new databaseController($db = "gooberblox");
    if (!$user) {
        header("Location: /login");
        exit();
    }
    (string)$name = (string)$_POST['name'] ?? '';
    (string)$description = (string)$_POST['description'] ?? '';
    (int)$version = intval($_POST['version']) ?? 2016;
    if ($version !== 2016 && $version !== 2017 && $version !== 2019)
        $version = 2016;

    
    $buildersClub = $user['buildersclub'] ?? null;
    $uploadLimit = 2;
    if ($buildersClub === "BuildersClub") {
        $uploadLimit = 4;
    } elseif ($buildersClub === "TurboBuildersClub") {
        $uploadLimit = 6;
    } elseif ($buildersClub === "OutrageousBuildersClub") {
        $uploadLimit = 12;
    }
    $query = "SELECT COUNT(*) AS game_count FROM games WHERE creatorid = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user['id'], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $gameCount = $row['game_count'];

    if ($gameCount >= $uploadLimit) {
        header('content-type: application/json');
        return json_encode([
            "success" => false,
            "message" => "Upload limit exhausted"
        ]);
    }
    $username = nxss($user['name']);

    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = array('rbxl');
        if (!in_array($fileExtension, $allowedExtensions)) {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Invalid file format"
            ]);
            exit();
        }
        if ($_FILES['file']['size'] > 25 * 1024 * 1024) {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "File size exceeds the limit."
            ]);
            exit();
        }
        

        $query = "SELECT MAX(id) AS max_id FROM games";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextId = $row['max_id'] + 1;

        $filename = $nextId;

        $uploadDir = __FWDIR__ . "/v1/asset/";
        $destination = $uploadDir . $filename;
        move_uploaded_file($_FILES['file']['tmp_name'], $destination);
        $currentDate = date('m-d-Y');
        $query = "INSERT INTO games (id, name, creator, creatorid, description, joindate, thumb1, thumb2, thumbnail, player_count, updated, year) VALUES (?, ?, ?, ?, ?, ?, '/siteAssets/content_deleted.webp', '/siteAssets/content_deleted.webp', '/siteAssets/baseplate.png', 0, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $nextId, PDO::PARAM_INT);
        $stmt->bindParam(2, $name, PDO::PARAM_STR);
        $stmt->bindParam(3, $username, PDO::PARAM_STR);
        $stmt->bindParam(4, $user['id'], PDO::PARAM_INT);
        $stmt->bindParam(5, $description, PDO::PARAM_STR);
        $stmt->bindParam(6, $currentDate, PDO::PARAM_STR);
        $stmt->bindParam(7, $currentDate, PDO::PARAM_STR);
        $stmt->bindParam(8, $version, PDO::PARAM_STR);
        $stmt->execute();
        header("Location: /games");
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => false,
            "message" => "File upload failed."
        ]);
        exit();
    }
});






route::post("/develop/item-upload/{uniqueType}", function ($uniqueType) {
    // TODO: MATHMARK REFACTOR THIS HEAVILY, THIS IS NOT PRODUCTION WORTHY. USE THIS AS A TESTTESTTEST
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $db = new databaseController($db = "gooberblox");
    if (!$user) {
        header("Location: /login");
        exit();
    }
    (string)$name = (string)$_POST['name'] ?? '';
    (string)$description = (string)$_POST['description'] ?? '';
    
    $buildersClub = $user['buildersclub'] ?? null;
    $uploadLimit = 2;
    if ($buildersClub === "BuildersClub") {
        $uploadLimit = 4;
    } elseif ($buildersClub === "TurboBuildersClub") {
        $uploadLimit = 6;
    } elseif ($buildersClub === "OutrageousBuildersClub") {
        $uploadLimit = 12;
    }
    $query = "SELECT COUNT(*) AS game_count FROM shirts WHERE creatorid = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user['id'], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $gameCount = $row['game_count'];

    if ($gameCount >= $uploadLimit) {
        header('content-type: application/json');
        return json_encode([
            "success" => false,
            "message" => "Upload limit exhausted"
        ]);
    }
    $username = nxss($user['name']);

    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = array('rbxl');
        if (!in_array($fileExtension, $allowedExtensions)) {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Invalid file format"
            ]);
            exit();
        }
        if ($_FILES['file']['size'] > 25 * 1024 * 1024) {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "File size exceeds the limit."
            ]);
            exit();
        }
        

        $query = "SELECT MAX(id) AS max_id FROM games";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextId = $row['max_id'] + 1;
        switch($uniqueType)
        {
            case 'shirt':
                $type = '09090';
            case 'pants':
                $type = '09000';
            default:
                $type = NULL;
        }
        $filename = $type . $nextId;
        $uploadDir = __FWDIR__ . "/v1/asset/";
        $destination = $uploadDir . $filename;
        move_uploaded_file($_FILES['file']['tmp_name'], $destination);
        $currentDate = date('m-d-Y');
        $query = "INSERT INTO shirts (id, name, creator, creatorid, description, joindate, thumb1, thumb2, thumbnail, player_count, updated, year) VALUES (?, ?, ?, ?, ?, ?, '/siteAssets/content_deleted.webp', '/siteAssets/content_deleted.webp', '/siteAssets/baseplate.png', 0, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $nextId, PDO::PARAM_INT);
        $stmt->bindParam(2, $name, PDO::PARAM_STR);
        $stmt->bindParam(3, $username, PDO::PARAM_STR);
        $stmt->bindParam(4, $user['id'], PDO::PARAM_INT);
        $stmt->bindParam(5, $description, PDO::PARAM_STR);
        $stmt->bindParam(6, $currentDate, PDO::PARAM_STR);
        $stmt->bindParam(7, $currentDate, PDO::PARAM_STR);
        $stmt->execute();
        header("Location: /games");
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => false,
            "message" => "File upload failed."
        ]);
        exit();
    }
});










route::any("/admin/database-management", function () {
    if(file_exists(__FWDIR__."/plugins/adminer-4.8.1.php")) {
      @require(__FWDIR__."/plugins/adminer-4.8.1.php");
      return;
    } else {
      return "Not here";
    }
  }, ["Content-Type" => "text/html"], ["category" => ["owner"]]);

route::post("/register", function () {
    
    $username = $_POST['usr'];
    $password = $_POST['pwd'];
    $cfPassword = $_POST['confirm_pwd'];
    $agent = $_SERVER['HTTP_USER_AGENT'];

    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
    if (strlen($username) < 2) {
        return twig::view("gooberblox/pages/register.twig", ["error" => "Username must be at least 2 characters long!"]);
    }
    $badWords = file(__FWDIR__."/files/misc/badwords.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($badWords as $badWord) {
        if (stripos($username, $badWord) !== false) {
            return twig::view("gooberblox/pages/register.twig", ["error" => "Username is not appropriate for GooberBlox"]);
            exit();
        }
    }
    
    $recapResponse = $_POST['g-recaptcha-response'];
    $recapScrt = '6LdE9H4pAAAAAPbays0IoC-5QD-XlHgzI6btJmJK';
    $recapResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recapScrt&response=$recapResponse");
    $recapKeys = json_decode($recapResponse, true);

        
        
      if ($recapKeys["success"]) {
        try {
          $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['usr']);
          $password = $_POST['pwd'];
        $cfPassword = $_POST['confirm_pwd'];
        $agent = $_SERVER['HTTP_USER_AGENT'];;
        $db = new databaseController($db = "gooberblox");
        $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(name) = LOWER(:username)");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        }
        catch(PDOException) {

        }

        if ($password !== $cfPassword) {
            return twig::view("gooberblox/pages/register.twig", ["error" => "Passwords do not match!"]);
        }

        if ($stmt->rowCount() > 0) {
            return twig::view("gooberblox/pages/register.twig", ["error" => "That username already exists!"]);
        }

        
        $hashedip = hash('sha256', $_SERVER['REMOTE_ADDR']);
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE ip = :hashedip");
        $stmt->bindParam(':hashedip', $hashedip);
        $stmt->execute();
        $altDetected = $stmt->fetchColumn() > 1;
        
    
        $token = generateToken();
        $bcrypt = password_hash($password, PASSWORD_BCRYPT);
        $insert = $db->prepare("INSERT INTO users (name, password, ip, agent, token, joindate) VALUES (:username, :password, :ip, :agent, :token, CURRENT_DATE)");
        $insert->bindParam(':username', $username);
        $insert->bindParam(':password', $bcrypt);
        $insert->bindParam(':ip', $hashedip);
        $insert->bindParam(':agent', $agent);
        $insert->bindParam(':token', $token);
        $insert->execute();

        setcookie('user_token', $token, time() + (86398 * 30), "/");
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE ip = :ip");
        $stmt->bindParam(':ip', $hashedip);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        $altDetected = ($count > 1); 
        
        $discordWebhook = 'https://discord.com/api/webhooks/1218260726670426212/4TmBjE9FoSSpRlP1JadgmmbqXJWREzpIIyYQSzjW_bmRZrQEbRpfoEvJYkNKFwW7SriN';
        $discordData = [
            'content' => 'New registration:',
            'embeds' => [
                [
                    'title' => 'New User Registration',
                    'fields' => [
                        ['name' => 'Username', 'value' => $username, 'inline' => true],
                        ['name' => 'Alt Detected', 'value' => $altDetected ? 'Yes' : 'No', 'inline' => true],
                    ]
                ]
            ]
        ];

        $discordContext = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($discordData)
            ]
        ]);

        $response = file_get_contents($discordWebhook, false, $discordContext);
            header("Location: /");
            exit();
      /*catch (PDOException) {
        return twig::view("gooberblox/pages/register.twig", ["error" => "Error!"]);
    } catch (RandomException) {
        return twig::view("gooberblox/pages/register.twig", ["error" => "Error! "]);
    }*/
}
      else {
            return twig::view("gooberblox/pages/register.twig", ["error" => "reCAPTCHA verification failed!"]);
        }
    
});



route::get("/admin/pending-assets", function () {
    return twig::view("gooberblox/pages/admin/queue.twig");
}, [], ["category" => ["admin"]]);

route::get("/admin", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    return twig::view("gooberblox/pages/admin/admin.twig", ['user' => $user]);
}, [], ["category" => ["admin"]]);


route::get("/discord", function () {
    header("location: https://discord.gg/XQhMy47kqp");
});



route::get("/maintenance", function () {
    $reason = conf::get()['project']['maintenance']['reason'];
    $time = conf::get()['project']['maintenance']['time'];
    return twig::view("gooberblox/pages/maint.twig", ["eta" => $time, "reason" => $reason]);
});

route::get("/legal/tos", function () {
    return twig::view("gooberblox/pages/legal/tos.twig");
});
route::get("/legal/privacy", function () {
    return twig::view("gooberblox/pages/legal/privacy.twig");
});
route::get("/download", function () {
    // todo: add real download page lol
    $path = __FWDIR__."/files/setup/GooberLauncher.exe";
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    return file_get_contents($path);
});

route::get("/{filename}", function ($filename) {
        
    if(!file_exists(__FWDIR__."/files/setup/$filename"))
    {
        http_response_code(404);
    }
    else
    {
        $path = __FWDIR__."/files/setup/$filename";
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        header('Accept-Ranges: none'); 
        return readfile($path); 
    }

});
route::get("/profile", function () {

    return twig::view("gooberblox/pages/profile.twig");
});

// catalog code



route::get("/catalog", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $cat = isset($_GET['cat']) ? htmlspecialchars($_GET['cat']) : "lhats";
    $category = '';

    $db = new databaseController($db = "gooberblox");
    $stmt = $db->prepare("SELECT * FROM catalog WHERE type = :type"); 
    $stmt->bindParam(':type', $cat, PDO::PARAM_STR);
    
    $robux = $user['robux'];
    $items = [];
    switch ($cat) {
        case 'lhats':
            $category = "Limited Hats";
            break;
        case 'lgears':
            $category = "Limited Gears";
            break;
        case 'lfaces':
            $category = "Limited Faces";
            break;
    }

    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $whitelistedUsers = [
        1188,
        764,
        1,
        4,
        672
    ];
    if ($user && in_array($user["id"], $whitelistedUsers)) {
        $username = nxss($user['name']);
        return twig::view("gooberblox/pages/catalog.twig", ['name' => $username, 'category' => $category, 'items' => $items, "robux" => $robux, 'user' => $user]);
    } else {
        header("Location: /login");
        exit(); 
    }
});
route::get("/catalog/item/{id}/{name}", function ($id, $name) {
   // ini_set("display_errors", 1);
   //  ini_set('display_startup_errors', 1);
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $cat = isset($_GET['cat']) ? htmlspecialchars($_GET['cat']) : "lhats";
    $category = '';

    $db = new databaseController($db = "gooberblox");
    $stmt = $db->prepare("SELECT * FROM catalog WHERE type = :type AND id = :id"); 
    $stmt->bindParam(':type', $cat, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: /login");
        exit();
    }

    $stmt = $db->prepare("SELECT * FROM owneditems WHERE userId = :userId AND assetId = :assetId");
    $stmt->bindParam(':userId', $user['id'], PDO::PARAM_INT);
    $stmt->bindParam(':assetId', $id, PDO::PARAM_INT);
    $stmt->execute();
    $ownedItem = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT COUNT(*) AS quantity FROM copies WHERE itemId = :itemId");
    $stmt->bindParam(':itemId', $id, PDO::PARAM_INT);
    $stmt->execute();
    $copiesData = $stmt->fetch(PDO::FETCH_ASSOC);
    $quantity = $copiesData ? $copiesData['quantity'] : 0;

    if($quantity > 0)
    {
        $OffSale = false;
    }
    else {
        $OffSale = true;
    }

    $stmt = $db->prepare("SELECT uaid FROM copies WHERE itemId = :itemId AND serial = 1 LIMIT 1");
    $stmt->bindParam(':itemId', $id, PDO::PARAM_INT);
    $stmt->execute();
    $firstCopy = $stmt->fetch(PDO::FETCH_ASSOC);
    $uaid = $firstCopy ? $firstCopy['uaid'] : NULL;
    switch ($cat) {
        case 'lhats':
            $category = "Limited Hats";
            break;
        case 'lgears':
            $category = "Limited Gears";
            break;
        case 'lfaces':
            $category = "Limited Faces";
            break;
    }

    $username = nxss($user['name']);
    $insufficientRobux = $user['robux'] < $item['price'];
    $alreadyOwned = $ownedItem !== false;

    return twig::view("gooberblox/pages/catalogItem.twig", ['name' => $username, 'category' => $category, 'itemName' => $item['name'], 'creator' => $item['creator'], "robux" => $user['robux'], "render" => $item['render'], "itemId" => $item['id'], "insufficientRobux" => $insufficientRobux, "alreadyOwned" => $alreadyOwned, "itemDescription" => $item['description'], "assetId" => $item['assetId'], 'user' => $user, 'Quantity' => $quantity, 'uaid' => $uaid, 'OffSale' => $OffSale]);
});


route::post("/catalog/purchase-item", function () {
    $economySystem = new EconomySystem();

    $id = (int) ($_POST["id"] ?? 0);
    $assetId = (int) ($_POST["assetId"] ?? 0);

    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        header("Location: /login");
        exit();
    }

    if ($economySystem->purchaseItem($assetId, $id, $userToken)) {
        header("Location: /catalog");
        exit();
    } else {
        http_response_code(405);
        exit();
    }
});




route::get("/admin/catalog", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    if (strpos($user['roles'], 'asset') !== false) {
        return twig::view("gooberblox/pages/admin/catalog.twig");
    }
    else
    {
        header("Location: /admin");
        exit();
    }
}, [], ["category" => ["admin"]]);


route::post("/admin/catalog/upload-item", function () {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $db = new databaseController("gooberblox");

    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $assetId = $_POST['assetId'] ?? '';
    $price = $_POST['price'] ?? '';
    $render = $_POST['render'] ?? '';
    $type = $_POST['type'] ?? '';
    if (strpos($user['roles'], 'asset') !== false) {
    $stmt = $db->prepare("INSERT INTO catalog (name, description, assetId, price, render, type) VALUES (:name, :description, :assetid, :price, :render, :type)");
    
    $stmt->bindParam(':name', $name, PDO::PARAM_INT);
    $stmt->bindParam(':description', $description, PDO::PARAM_INT);
    $stmt->bindParam(':assetid', $assetId, PDO::PARAM_INT);
    $stmt->bindParam(':price', $price, PDO::PARAM_INT);
    $stmt->bindParam(':render', $render, PDO::PARAM_INT);
    $stmt->bindParam(':type', $type, PDO::PARAM_INT);
    $stmt->execute();
    
    header("Location: /admin/catalog");
    exit();
} 
else
{
    http_response_code(403);
    header("Location: /admin");
    exit();
}
}, [], ["category" => ["admin"]]);

route::get("/banned", function () {
    $token = $_COOKIE['user_token'] ?? null;
    $db = new databaseController("gooberblox");
    $query = "SELECT * FROM users WHERE token = :userToken";
    $statement = $db->prepare($query);
    $statement->bindParam(':userToken', $token);
    $statement->execute();
    
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    $reason = $user['moderationnote'];
    if($user) {
        return twig::view("gooberblox/pages/banned.twig", ['moderation' => "ban", "reason" => $reason]);
    }
});
route::get("/warned", function () {
    $token = $_COOKIE['user_token'] ?? null;
    $db = new databaseController("gooberblox");
    $query = "SELECT * FROM users WHERE token = :userToken";
    $statement = $db->prepare($query);
    $statement->bindParam(':userToken', $token);
    $statement->execute();
    
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    $reason = $user['moderationnote'];
    if($user) {
        return twig::view("gooberblox/pages/banned.twig", ['moderation' => "warn", "reason" => $reason, 'userToken' => $token]);
    }
});
route::post("/warned/recover/{token}", function ($token) {
    $db = new databaseController("gooberblox");
    $query = "UPDATE users SET warned = 0 WHERE token = :userToken";
    $statement = $db->prepare($query);
    $statement->bindParam(':userToken', $token);
    $statement->execute();
    header("Location: /");
});
route::get("/admin/user-management", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $username = $user['name'];
    if ($user) {
        $db = new databaseController("gooberblox");

        $page = $_GET['page'] ?? 1;
        $itemsPerPage = 50;
        $offset = ($page - 1) * $itemsPerPage;

        $searchQuery = isset($_GET['search']) ? $_GET['search'] : null;
        $params = [
            'limit' => $itemsPerPage,
            'offset' => $offset,
        ];

        if ($searchQuery !== null) {
            $query = "SELECT * FROM users WHERE name LIKE :search ORDER BY id ASC LIMIT :limit OFFSET :offset";
            $params['search'] = '%' . $searchQuery . '%';
        } else {
            $query = "SELECT * FROM users ORDER BY id ASC LIMIT :limit OFFSET :offset";
        }

        $statement = $db->prepare($query);
        foreach ($params as $key => &$value) {
            $statement->bindParam(':' . $key, $value);
        }
        $statement->execute();
        $users = $statement->fetchAll(PDO::FETCH_ASSOC);

        $startIndex = ($page - 2) * $itemsPerPage + 1;

        $totalUsersQuery = ($searchQuery !== null) ? 
            "SELECT COUNT(*) as count FROM users WHERE name LIKE :search" :
            "SELECT COUNT(*) as count FROM users";
        $totalUsersStatement = $db->prepare($totalUsersQuery);
        if ($searchQuery !== null) {
            $totalUsersStatement->bindParam(':search', $searchQuery);
        }
        $totalUsersStatement->execute();
        $totalUsers = $totalUsersStatement->fetch(PDO::FETCH_ASSOC);
        $totalPages = ceil($totalUsers['count'] / $itemsPerPage);

        return twig::view("gooberblox/pages/admin/users.twig", [
            'username' => $username,
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'itemsPerPage' => $itemsPerPage,
            'startIndex' => $startIndex
        ]);
    }
}, [], ["category" => ["admin"]]);

route::get("/admin/totp", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $username = $user['name'];
    if ($user) {
        $db = new databaseController("gooberblox");


        $userId = $user['id'];
        $query = "SELECT totp_enabled FROM users WHERE id = :userId";
        $statement = $db->prepare($query);
        $statement->bindParam(':userId', $userId);
        $statement->execute();
        $totpEnabled = $statement->fetchColumn();
        if (!$totpEnabled) {
            $ga = new PHPGangsta_GoogleAuthenticator();
            $secret = $ga->createSecret();
            $qrCodeUrl = $ga->getQRCodeGoogleUrl('GooberBlox', $secret);
        } else {
            $secret = null;
            $qrCodeUrl = null;
        }

        return twig::view("gooberblox/pages/admin/twofactor.twig", [
            'username' => $username,
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => $secret,
            'totpEnabled' => $totpEnabled,
        ]);
    }
}, [], ["category" => ["admin"]]);

route::post("/admin/totp", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    
    if ($user && isset($_POST['totpCode']) && isset($_POST['secret'])) {
        $ga = new PHPGangsta_GoogleAuthenticator();
        $secret = $_POST['secret'];
        $code = $_POST['totpCode'];
        
        if ($ga->verifyCode($secret, $code)) {
            $db = new databaseController("gooberblox");
            $userId = $user['id'];

            $query = "UPDATE users SET totp_enabled = true, totp_secret = :secret WHERE id = :userId";
            $statement = $db->prepare($query);
            $statement->bindParam(':secret', $secret);
            $statement->bindParam(':userId', $userId);
            $statement->execute();
            
            header("Location: /admin/totp");
            exit();
        } else {
            header("Location: /admin/totp?error=invalid_code");
            exit();
        }
    }
    
    header("Location: /admin/totp");
    exit();
});

route::get("/admin/user-management/user/{id}", function ($id) {


    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    if (!$user) {
        header("Location: /login");
    }

    $db = new databaseController("gooberblox");
    $query = "SELECT * FROM users WHERE id = :id";
    $statement = $db->prepare($query);
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();

    $userData = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        http_response_code(404);
        header("Location: /user-management/user/$id/");
    }

    return twig::view("gooberblox/pages/admin/profile.twig", ['userData' => $userData, 'user' => $user, 'robux' => $user['robux'], 'pagename' => $userData['name'],]);
}, [], ["category" => ["admin"]]);



route::post("/admin/user-management/ban-user", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $ga = new PHPGangsta_GoogleAuthenticator();
    $totpCode = $_POST['totp_code'] ?? null;
    $user = getUserByToken($userToken);
    
    if (!$totpCode) {
        header('content-type: application/json');
        http_response_code(403);
        return json_encode([
            "success" => 0,
            "message" => "No Mobile Auth Provided"
        ]);
    }

    $isTotpValid = $ga->verifyCode($user['totp_secret'], $totpCode);
    if (!$isTotpValid) {
        header('content-type: application/json');
        http_response_code(403);
        return json_encode([
            "success" => 0,
            "message" => "Mobile Auth Invalid"
        ]);
    }
    
    if ($user) {
        $userId = $_POST['user_id'] ?? null;
        $moderationNote = $_POST['moderation_note'] ?? '';

        if ($userId !== null) {
            $db = new databaseController("gooberblox");

            $queryFetchUserData = "SELECT admin FROM users WHERE id = :user_id";
            $stmtFetchData = $db->prepare($queryFetchUserData);
            $stmtFetchData->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtFetchData->execute();
            $userData = $stmtFetchData->fetch(PDO::FETCH_ASSOC);
            if ($userData && $userData['admin'] != 1) {
                $query = "UPDATE users SET moderated = 1, moderationnote = :moderation_note WHERE id = :user_id";
                $statement = $db->prepare($query);
                $statement->bindParam(':moderation_note', $moderationNote, PDO::PARAM_STR);
                $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $statement->execute();

                $discordWebhook = 'https://discord.com/api/webhooks/1218260726670426212/4TmBjE9FoSSpRlP1JadgmmbqXJWREzpIIyYQSzjW_bmRZrQEbRpfoEvJYkNKFwW7SriN';
                $message = $user['name'] . " banned User ID - $userId, Moderation Note - $moderationNote";
                $data = [
                    'content' => $message
                ];
                $options = [
                    'http' => [
                        'header' => "Content-Type: application/json\r\n",
                        'method' => 'POST',
                        'content' => json_encode($data)
                    ]
                ];
                $context = stream_context_create($options);
                $result = file_get_contents($discordWebhook, false, $context);
                if ($result === FALSE) {
                    error_log("Failed to send Discord webhook");
                }

                header("Location: /admin/user-management");
                exit();
            } else {
                exit();
            }
        }
    }
}, [], ["category" => ["admin"]]);

route::post("/admin/user-management/unban-user", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    if ($user) {
        $userId = $_POST['user_id'] ?? null;

        if ($userId !== null) {
            $db = new databaseController("gooberblox");
            $query = "UPDATE users SET moderated = 0, moderationnote = NULL WHERE id = :user_id";
            $statement = $db->prepare($query);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();

            header("Location: /admin/user-management");
            exit();
        }
    }
}, [], ["category" => ["admin"]]);

route::get("/profile/{id}", function ($id) {


    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    if (!$user) {
        header("Location: /login");
        exit();
    }

    $db = new databaseController("gooberblox");
    $query = "SELECT * FROM users WHERE id = :id";
    $statement = $db->prepare($query);
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();

    $userData = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$userData || $userData['moderated'] == 1) {
        http_response_code(404);
        header("Location: /profile/$id/");
    }

    return twig::view("gooberblox/pages/avatar.twig", ['userData' => $userData, 'user' => $user, 'robux' => $user['robux'], 'pagename' => $userData['name'],]);
});

route::get("/profile/{id}/friends", function ($id) {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        header("Location: /login");
        exit();
    }

    $db = new databaseController("gooberblox");
    $query = "SELECT * FROM users WHERE id = :id";
    $statement = $db->prepare($query);
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();

    $userData = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$userData || $userData['moderated'] == 1) {
        http_response_code(404);
        header("Location: /profile/$id/friends/");
    }

    $name = $userData['name'];
    return twig::view('gooberblox/pages/userFriends.twig', ['userData' => $userData, 'user' => $user, 'robux' => $user['robux'], 'pagename' => "$name's Friends",]);
});

route::get("/library", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    $cat = isset($_GET['cat']) ? htmlspecialchars($_GET['cat']) : "lhats";
    $category = '';

    $db = new databaseController($db = "gooberblox");
    $stmt = $db->prepare("SELECT * FROM assets WHERE type = :type"); 
    $stmt->bindParam(':type', $cat, PDO::PARAM_STR);
    
    $robux = $user['robux'];
    $items = [];
    switch ($cat) {
        case 'image':
            $category = "Images";
            break;
        case 'audio':
            $category = "Audio";
            break;
    }

    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($user['admin'] == 1) {
        $username = nxss($user['name']);
        return twig::view("gooberblox/pages/library.twig", ['name' => $username, 'category' => $category, 'items' => $items, "robux" => $robux, 'user' => $user]);
    } else {
        header("Location: /");
        exit(); 
    }
});

route::get("/groups", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        header("Location: /");
        exit();
    }

    $db = new databaseController("gooberblox");
    $stmt = $db->prepare("SELECT * FROM groups"); 
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $username = nxss($user['name']);
    return twig::view("gooberblox/pages/groups.twig", [
        'name' => $username,
        'items' => $items,
        "robux" => $user['robux'],
        'user' => $user
    ]);
});

/*
    PLACEHOLDER ENDPOINT FEEL FREE TO CHANGE ENDPOINT URL WHEN IMPLEMENTING

    When shit goes wrong pls attach message/reason I.E: {"success":0,"reason":"Invalid group icon format!"}, would like to use these errors for a frontend feature.
    (the feature would look very sexy) (its literally just a error message if the group creation fails)

    dawn 17/5/24
*/
route::post('/create-group', function() {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        return json_encode([
            "success" => 0,
            "reason" => "Unable to authenticate."
        ]);
    }

    $groupName = $_POST['groupName'] ?? null;
    $groupDescription = $_POST['groupDescription'] ?? null;
    $groupIcon = $_FILES['groupIcon'] ?? null;

    if (!$groupName) {
        return json_encode([
            "success" => 0,
            "reason" => "Group name missing."
        ]);
    }
    if (!$groupDescription) {
        return json_encode([
            "success" => 0,
            "reason" => "Group description missing."
        ]);
    }
    if (!$groupIcon) {
        return json_encode([
            "success" => 0,
            "reason" => "Group icon missing."
        ]);
    }

    return json_encode([
        "success" => 1,
        "group_id" => 1
    ]);
});

route::get("/group/{id}", function ($id) {


    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);
    if (!$user) {
        header("Location: /login");
    }

    $db = new databaseController("gooberblox");
    $query = "SELECT * FROM groups WHERE id = :id";
    $statement = $db->prepare($query);
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();

    $group = $statement->fetch(PDO::FETCH_ASSOC);
    $membersData = json_decode($group['members'], true);
    if (isset($membersData['members'])) {
        $jsonMembers = [];
        foreach ($membersData['members'] as $member) {
            if (strpos($member['name'], $user['name']) !== false) {
                $isUserMember = true;
                break;
            }
            $jsonMembers[] = [
                "Name" => $member['name'],
                "Id" => $member['id'],
                "Rank" => $member['rank']
            ];
            
        }
    } else {
        $jsonRoles = [];
    }
    
    if (!$group || $group['moderated'] == 1) {
        http_response_code(404);
        header("Location: /group/$id/");
    }
    
    $members = json_decode($group['members']);
    $messages = [];
    return twig::view("gooberblox/pages/group.twig", ['group' => $group, 'user' => $user, 'robux' => $user['robux'], 'pagename' => $group['name'], 'members' => $jsonMembers, 'messages' => $messages, 'isUserMember' => $isUserMember]);
});
route::post("/count", function () {
    $expectedKey = conf::get()['project']['auth']['ApiKey'];
    $getKey = isset($_SERVER['HTTP_APIKEY']) ? $_SERVER['HTTP_APIKEY'] : '';
    if($getKey == $expectedKey) {
        http_response_code(200);
        $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
        $placeId = isset($_GET['placeId']) ? (int)$_GET['placeId'] : 0;
        
        $db = new databaseController("gooberblox");

        $queryPlayerCount = "UPDATE games SET player_count = :count WHERE id = :id";
        $statementPlayerCount = $db->prepare($queryPlayerCount);
        $statementPlayerCount->bindParam(':count', $count, PDO::PARAM_INT);
        $statementPlayerCount->bindParam(':id', $placeId, PDO::PARAM_INT);
        $statementPlayerCount->execute();

        $queryVisits = "UPDATE games SET visits = visits + :count WHERE id = :id";
        $statementVisits = $db->prepare($queryVisits);
        $statementVisits->bindParam(':count', $count, PDO::PARAM_INT);
        $statementVisits->bindParam(':id', $placeId, PDO::PARAM_INT);
        $statementVisits->execute();
        if($count == 0) {
            $queryJobId = "SELECT jobid, port FROM jobs WHERE placeid = :placeId";
            $statementJobId = $db->prepare($queryJobId);
            $statementJobId->bindParam(':placeId', $placeId, PDO::PARAM_INT);
            $statementJobId->execute();
            $job = $statementJobId->fetch(PDO::FETCH_ASSOC);
            
            if ($job) {
                $port = $job['port'];
                $jobId = $job['jobid'];
                $RCCServiceSoap = new Roblox\Grid\Rcc\RCCServiceSoap("75.164.27.22", $port);
                $RCCServiceSoap->CloseJob($jobId);

                // harley: fallback in case the arbiter fails to remove the job from the db
                $queryDeleteJob = "DELETE FROM jobs WHERE jobid = :jobId";
                $statementDeleteJob = $db->prepare($queryDeleteJob);
                $statementDeleteJob->bindParam(':jobId', $jobId, PDO::PARAM_INT);
                $statementDeleteJob->execute();
            }
            else {
                return "failure";
            }
        }
        return "success";
    }
    else {
        header('content-type: application/json');
        http_response_code(403);
        return json_encode([
            "success" => false,
            "message" => "Verification failed"
        ]);
    }
});



route::get("/games.aspx", function () {
    header('Location: /');
});
route::get("/games/", function () {
    header('Location: /');
});


route::get("/avatar", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        header("Location: /");
        exit();
    }

    $db = new databaseController("gooberblox");
    $stmt = $db->prepare("SELECT * FROM groups"); 
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //TODO: Some backend dev make this not use example variables
    //Numbers are the asset types, if you use the newer string asset types, write code for that, and I'll refractor for it
    //asset list: https://create.roblox.com/docs/reference/engine/enums/AssetType
    //Image variable can be renamed to thumbnail if you want or something i dont mind
    $inventory = [
        '11' => [
            [
                'Name' => 'epic shirt',
                'Image' => '/siteAssets/image.png',
                'ID' => '69'
            ],
            [
                'Name' => 'epic shirt 2',
                'Image' => '/siteAssets/image.png',
                'ID' => '420'
            ]
        ],
        '12' => [
            [
                'Name' => 'epic pants',
                'Image' => '/siteAssets/image.png',
                'ID' => '111'
            ],
        ],
        '8' => [],
        '41' => [],
        '18' => [],
        '19' => []
    ];

    $username = nxss($user['name']);
    return twig::view("gooberblox/pages/avatarEditor.twig", [
        'name' => $username,
        "robux" => $user['robux'],
        'inventory' => $inventory,
        'user' => $user
    ]);
});

route::any("/updatebody", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        header("HTTP/1.1 401 Unauthorized");
        exit();
    }

    $bodyType = (string)$_POST['avatarType'] ?? false;
    $height = (double)$_POST['height'];
    $width = (double)$_POST['width'];

    $width = min(max($width, 0.7), 1);
    $height = min(max($height, 0.9), 1.05);

    $db = new databaseController("gooberblox");
    $stmt = $db->prepare("UPDATE users SET r15 = :bodyType, height = :height, width = :width WHERE id = :userId");
    $stmt->bindParam(':bodyType', $bodyType, PDO::PARAM_BOOL);
    $stmt->bindParam(':height', $height, PDO::PARAM_STR);
    $stmt->bindParam(':width', $width, PDO::PARAM_STR);
    $stmt->bindParam(':userId', $user['id'], PDO::PARAM_INT);
    $stmt->execute();
});

route::any("/updatecolors", function () {
    $userToken = $_POST['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        header("HTTP/1.1 401 Unauthorized");
        exit();
    }

    (int)$headColor = (int)$_POST['headColor'] ?? null;
    (int)$torsoColor = (int)$_POST['torsoColor'] ?? null;
    (int)$leftArmColor = (int)$_POST['leftArmColor'] ?? null;
    (int)$rightArmColor = (int)$_POST['rightArmColor'] ?? null;    
    (int)$leftLegColor = (int)$_POST['leftLegColor'] ?? null;
    (int)$rightLegColor = (int)$_POST['rightLegColor'] ?? null;


    $xmlData = '
    <roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.roblox.com/roblox.xsd" version="4">
  <External>null</External>
  <External>nil</External>
  <Item class="BodyColors">
    <Properties>
      <int name="HeadColor">'. $headColor . '</int>
      <int name="LeftArmColor">'. $leftArmColor . '</int>
      <int name="LeftLegColor">'. $leftLegColor . '</int>
      <string name="Name">Body Colors</string>
      <int name="RightArmColor">'. $rightArmColor . '</int>
      <int name="RightLegColor">'. $rightLegColor . '</int>
      <int name="TorsoColor">'. $torsoColor . '</int>
      <bool name="archivable">true</bool>
    </Properties>
  </Item>
</roblox>;
    ';
    $db = new databaseController("gooberblox");
    $stmt = $db->prepare("UPDATE users SET bodycolors = ? WHERE id = ?");
    $stmt->bindParam(1, $xmlData, PDO::PARAM_STR);
    $stmt->bindParam(2, $user['id'], PDO::PARAM_INT); 
    $stmt->execute();
});



route::get("/redeem", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        header("Location: /");
        exit();
    }

    $username = nxss($user['name']);
    return twig::view("gooberblox/pages/redeem.twig", [
        'name' => $username,
        "robux" => $user['robux'],
        'user' => $user
    ]);
});


route::get("/users", function () {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        header("Location: /");
        exit();
    }

    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'id';
    $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    switch ($filter) {
        case 'pc':
            $order = 'name DESC';
            break;
        case 'id':
            $order = 'id DESC';
            break;
        default:
            $order = 'id DESC';
            break;
    }
    
    $username = $user['name'];
    $db = new databaseController("gooberblox");

    $userId = $_GET['user_id'] ?? 0;
    if ($userId != 0) {
        $query = "SELECT name, joindate, id FROM users WHERE id = :id ORDER BY name DESC";
        $statement = $db->prepare($query);
        $statement->bindParam(':id', $userId, PDO::PARAM_INT);
        $statement->execute();
        $userDetails = $statement->fetchAll(PDO::FETCH_ASSOC);
    }else {
        $query = "SELECT name, joindate, id FROM users WHERE LOWER(name) LIKE LOWER(:search) ORDER BY $order";
        $statement = $db->prepare($query);
        $statement->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $statement->execute();
        $userDetails = $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    $robux = $user['robux'];
    return twig::view("gooberblox/pages/users.twig", [
        'username' => $username,
        'pagename' => "Users",
        'userDetails' => $userDetails,
        'robux' => $robux,
        'user' => $user
    ]);
}, [], ["category" => ["gooberblox"]]);

route::any("/presence/ReportClosure", function () {
    $jobId = $_GET['jobId'] ?? null;
    $apiKey = $_GET['apiKey'] ?? null;
    $expectedApiKey = "_GooberCrypt%scb85-9ca61d85-8ca3c0e8-4e3a7622-b3a71ca5-29ad1209-9fb6c713-v1";
    
    if ($apiKey == $expectedApiKey) {
        $db = new databaseController("gooberblox");

        $query = "DELETE FROM jobs WHERE jobid = :jobId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':jobId', $jobId);
        $stmt->execute();
        
        if ($stmt->execute()) {
            header('content-type: application/json');
            return json_encode([
                "success" => true,
                "message"=> "Job with jobId {$jobId} has been deleted."
            ]);
        } else {
            header('content-type: application/json');
            return json_encode([
                "success" => false,
                "message"=> "Failed to delete job. Please try again later."
            ]);
        }

    } else {
        header('content-type: application/json');
        return json_encode([
            "success" => false,
            "message"=> "You do not have permission to commit this action."
        ]);
    }
});
route::any("/v1/Close/", function () {
    $jobId = $_GET['jobId'] ?? null;
    $apiKey = $_GET['apiKey'] ?? null;
    $expectedApiKey = "0d045403-9d2f-40e3-8890-386e390df8bd";
    
    if ($apiKey == $expectedApiKey) {
        $db = new databaseController("gooberblox");

        $query = "DELETE FROM jobs WHERE jobid = :jobId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':jobId', $jobId);
        $stmt->execute();
        
        if ($stmt->execute()) {
            header('content-type: application/json');
            return json_encode([
                "success" => true,
                "message"=> "Job with jobId {$jobId} has been deleted."
            ]);
        } else {
            header('content-type: application/json');
            return json_encode([
                "success" => false,
                "message"=> "Failed to delete job. Please try again later."
            ]);
        }

    } else {
        header('content-type: application/json');
        return json_encode([
            "success" => false,
            "message"=> "You do not have permission to commit this action."
        ]);
    }
});

// roblox endpoint very gonna make parity roblox or something it'll be cool - dawn 21/5/24
route::post("/v1/users/{friend_id}/request-friendship", function ($friend_id) {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        return json_encode([
            "success" => 0,
            "reason" => "Unable to authenticate."
        ]);
    }
});

// also cencels friend requests on real roblox so thas how im doin shit
route::post("/v1/users/{friend_id}/unfriend", function ($friend_id) {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        return json_encode([
            "success" => 0,
            "reason" => "Unable to authenticate."
        ]);
    }

    $db = new databaseController("gooberblox");

    $query = "DELETE FROM friendships WHERE (userid = :userId AND friendid = :friendId AND status != 'blocked') OR (userid = :friendId AND friendid = :userId AND status != 'blocked')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':userId', $user['id']);
    $stmt->bindParam(':friendId', $friend_id);
    $stmt->execute();
});

route::post("/user-blocking-api/v1/users/{friend_id}/block-user", function ($friend_id) {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        return json_encode([
            "success" => 0,
            "reason" => "Unable to authenticate."
        ]);
    }

    $db = new databaseController("gooberblox");

    $query = "INSERT INTO friendships (userid,friendid,status) Values (:userId,:friendId,'blocked') ON CONFLICT DO NOTHING";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':userId', $user['id']);
    $stmt->bindParam(':friendId', $friend_id);
    $stmt->execute();
});

route::post("/user-blocking-api/v1/users/{friend_id}/unblock-user", function ($friend_id) {
    $userToken = $_COOKIE['user_token'] ?? null;
    $user = getUserByToken($userToken);

    if (!$user) {
        return json_encode([
            "success" => 0,
            "reason" => "Unable to authenticate."
        ]);
    }

    $db = new databaseController("gooberblox");

    $query = "DELETE FROM friendships WHERE userid = :userId AND friendid = :friendId AND status = 'blocked'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':userId', $user['id']);
    $stmt->bindParam(':friendId', $friend_id);
    $stmt->execute();
});
