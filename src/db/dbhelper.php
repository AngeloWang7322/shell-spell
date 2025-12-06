<?php

declare(strict_types=1);

class DBHelper
{
    public PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function updateUserMap()
    {
        $query = $this->pdo->prepare(
            "UPDATE map_json FROM user_maps VALUES( :mapjson) 
            WHERE user_id = :userid"
        );

        $response = $query->execute([
            ":userid" => $_SESSION["user"]["id"],
            ":mapjson" => json_encode($_SESSION["map"])
        ]);
    }
    public function loginUser($password, $email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        echo "input hash: " . $password;
        echo "response: " . json_encode($user);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user']["name"] = $user['username'];
            $_SESSION["user"]["id"] = $user["id"];
        }
        else{
            throw new Exception("Email oder Passwort falsch");
        }
    }
    public function createUserRows($password, $email, $name)
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userInsert = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash)
                VALUES (:username, :email, :password_hash)
            ");
        $userInsert->execute([
            ':username' => $name,
            ':email' => $email,
            ':password_hash' => $passwordHash
        ]);

        self::loginUser($password, $email);

        $mapInsert = $this->pdo->prepare("
                INSERT INTO user_maps (user_id, map_json) 
                VALUES (:userid, :map_json)
            ");
        $mapInsert->execute(params: [
            "userid" => $_SESSION["user"]["id"],
            "map_json" => json_encode($_SESSION["map"])
        ]);

        $statsInsert = $this->pdo->prepare("
                INSERT INTO user_stats (user_id, curMana, xp) 
                VALUES (:userid, :curMana, :xp)
            ");
        echo "user id: " . json_encode($_SESSION["user"]["id"]);
        $statsInsert->execute([
            "userid" => $_SESSION["user"]["id"],
            "curMana" => 100,
            "xp" => 0,
        ]);
        self::loadUserData();
    }
    public function loadUserData()
    {
        $userId = $_SESSION["user"]["id"];

        $fetchUserStats = $this->pdo->prepare("
        SELECT * FROM user_stats us 
            WHERE us.id = :userId
        ");
        $fetchUserStats->execute([
            "userId" => $userId
        ]);
        $userStats = $fetchUserStats->fetch();

        $fetchUserMap = $this->pdo->prepare(" 
            SELECT map_json
            FROM user_maps
            WHERE user_id = :userId
        ");
        $fetchUserMap->execute([
            "userId" => $userId
        ]);

        $userMap = $fetchUserMap->fetch();
        $_SESSION["map"] = json_decode($userMap["map_json"]);
        $_SESSION["curMana"] = $userStats["curMana"];
        $_SESSION["user"]["role"] = "Wanderer";
        $_SESSION["maxMana"] = $userStats["xp"] / 10 + 1;
    }
    public function loadDefaultSession()
    {
        $_SESSION["history"] = [];
        $_SESSION["map"] = new Room("hall");
        $_SESSION["curRoom"] = &$_SESSION["map"];
        $_SESSION["map"]->path = ["hall"];
        $_SESSION["map"]->doors["library"] = new Room("library", requiredRole: ROLE::APPRENTICE);
        $_SESSION["map"]->doors["armory"] = new Room("armory", requiredRole: ROLE::ARCHIVIST);
        $_SESSION["map"]->doors["passage"] = new Room("passage", requiredRole: ROLE::WANDERER);
        $_SESSION["map"]->doors["passage"]->doors["staircase"] = new Room(name: "staircase", path: $_SESSION["map"]->doors["passage"]->path, requiredRole: ROLE::ROOT);

        $_SESSION["map"]->items["manaPotion.exe"] = new Item(
            "manaPotion",
            ItemType::SPELL,
            ActionType::MANA,
            Rarity::COMMON
        );
        $_SESSION["map"]->items["grimoire.txt"] = new Item(
            "grimoire",
            ItemType::SCROLL,
            ActionType::OPEN_SCROLL,
            Rarity::COMMON,
            "OPEN SCROLL: <br>'cat [scroll name]'<br>"
        );
        $_SESSION["map"]->items["testScroll.txt"] = new Item(
            "testScroll",
            ItemType::SCROLL,
            ActionType::OPEN_SCROLL,
            Rarity::COMMON,
            "This is a test scroll content. It is used to demonstrate the scroll functionality in the" .
            " game. You can read this scroll to gain knowledge and power."
        );
        $_SESSION["maxMana"] = 100;
        $_SESSION["curMana"] = 100;
        $_SESSION["openedScroll"] = new Scroll("", "");
        $_SESSION["user"]["role"] = ROLE::WANDERER;
    }
}