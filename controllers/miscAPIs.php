<?php
// the beginning of all hell
use core\route;
use dbController\databaseController;
use core\modules\twig;
route::get("/v1/users/{id}", function ($id) 
{
    $db = new databaseController("gooberblox");
    $query = "SELECT * FROM users WHERE id = :id";
    $statement = $db->prepare($query);
    $statement->bindParam(':id', $id);
    $statement->execute();
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
        $userinfo = [
        "ID" => $user['id'],
        "Username" => $user['name'],
        "Admin" => $user['admin'],
        "Description" => $user['description'],
        "CreationDate" => $user['joindate'],
        "Online" => 1,
        "Membership" => $user['buildersclub'] ?? "None",
        "Banned" => $user['moderated']];
    return json_encode($userinfo);

});

route::get("/v1/games/{id}", function ($id) 
{
    $db = new databaseController("gooberblox");
    $query = "SELECT * FROM games WHERE id = :id";
    $statement = $db->prepare($query);
    $statement->bindParam(':id', $id);
    $statement->execute();
    $game = $statement->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    $gameinfo = [
        "ID" => $game['id'],
        "name" => $game['name'],
        "Description" => $game['description'],
        "CreationDate" => $game['joindate'],
        "updated" => $game['updated'],
        "visits" => $game['visits'] ?? '0'];
    return json_encode($gameinfo);

});

route::get("/v1/stats", function () {
    $db = new databaseController("gooberblox");

    $queryTotalUsers = "SELECT COUNT(*) AS total_users FROM users";
    $statementTotalUsers = $db->prepare($queryTotalUsers);
    $statementTotalUsers->execute();
    $totalUsers = $statementTotalUsers->fetch(PDO::FETCH_ASSOC)['total_users'];

    $today = date("Y-m-d");
    $queryRegisteredToday = "SELECT COUNT(*) AS registered_today FROM users WHERE joindate = :today";
    $statementRegisteredToday = $db->prepare($queryRegisteredToday);
    $statementRegisteredToday->bindParam(':today', $today);
    $statementRegisteredToday->execute();
    $totalUsersToday = $statementRegisteredToday->fetch(PDO::FETCH_ASSOC)['registered_today'];

    $yesterday = date("Y-m-d", strtotime("-1 day"));
    $queryRegisteredYesterday = "SELECT COUNT(*) AS registered_yesterday FROM users WHERE joindate = :yesterday";
    $statementRegisteredYesterday = $db->prepare($queryRegisteredYesterday);
    $statementRegisteredYesterday->bindParam(':yesterday', $yesterday);
    $statementRegisteredYesterday->execute();
    $totalUsersYesterday = $statementRegisteredYesterday->fetch(PDO::FETCH_ASSOC)['registered_yesterday'];

    header('Content-Type: application/json');
    $stats = [
        'user_count' => $totalUsers,
        'users_reg_today' => $totalUsersToday ?? '0',
        'users_reg_yesterday' => $totalUsersYesterday ?? '0',
        'server_time' => time()
    ];
    return json_encode($stats);
});
route::get("/v1/docs", function () {
    return twig::view("gooberblox/pages/docs.twig");
});