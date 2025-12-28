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
        // echo "input hash: " . $password;
        // echo "response: " . json_encode($user);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user']["name"] = $user['username'];
            $_SESSION["user"]["id"] = $user["id"];
        } else {
            throw new Exception("Email oder Passwort falsch");
        }
    }
    public function registerUser($password, $email, $name)
    {
        self::loadDefaultSession();

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
    }
    public function loadGameState($stateId)
    {
        $fetchGameState = $this->pdo->prepare("
            SELECT * FROM game_states 
                WHERE id = :id
        ");
        $fetchGameState->execute([
            "id" => $stateId
        ]);
        $gameState = $fetchGameState->fetch();
        //assign userRole and maxMana by xp
        for ($i = 1; $i <= count(Role::cases()); $i++) {
            if ($gameState["xp"] <= $i * 100) {
                $_SESSION["maxMana"] = $i * 100;
                foreach (Role::cases() as $role) {
                    if ($i == 1) {
                        $_SESSION["user"]["role"] = $role;
                        break 2;
                    }
                    $i--;
                }
            }
        }        

        $_SESSION["map"] = Room::fromArray(json_decode($gameState["map_json"]));
        $_SESSION["curMana"] = $gameState["curMana"];
        $_SESSION["curRoom"] =& $_SESSION["map"];
        $_SESSION["history"] = [];
    }
    public function getGameStates()
    {
        $fetchStatesData = $this->pdo->prepare("
            SELECT id, name, xp FROM game_states 
                WHERE user_id = :userId 
        ");
        $fetchStatesData->execute([
            "userId" => $_SESSION["user"]["id"]
        ]);
        $statesData = [];
        $response = (array) $fetchStatesData->fetchAll();
        foreach ($response as $data) {
            $statesData[$data["id"]]["name"] = $data["name"];
            $statesData[$data["id"]]["rank"] = getRankFromXp($data["xp"])->value;
        }
        echo "<br>states: " . json_encode($statesData);
        return $statesData;
    }
    public function createGameState($name)
    {
        $gameStateInsert = $this->pdo->prepare("
            INSERT INTO game_states ( user_id, name, map_json)
            VALUES (:userId, :stateName, :mapJson)
        ");
        $gameStateInsert->execute([
            "userId" => $_SESSION["user"]["id"],
            "stateName" => $name,
            "mapJson" => json_encode(self::getDefaultMap())
        ]);
        $this->loadGameState($this->pdo->lastInsertId());
    }
    public function deleteGameState($stateId){
        $deleteGameState = $this->pdo->prepare("
            DELETE FROM game_states
            WHERE id = :stateId
        ");
        $deleteGameState->execute([
            "stateId" => $stateId
        ]);
    }
    public static function loadDefaultSession()
    {
        session_unset();
        $_SESSION["history"] = [];
        $_SESSION["map"] = self::getDefaultMap();
        $_SESSION["curRoom"] = &$_SESSION["map"];
        $_SESSION["maxMana"] = 100;
        $_SESSION["curMana"] = 100;
        $_SESSION["openedScroll"] = new Scroll(
            "",
            "",
            ItemType::SCROLL,
            content: ""
        );
        $_SESSION["user"]["role"] = ROLE::WANDERER;
        $_SESSION["user"]["username"] = "guest";
        $_SESSION["lastPath"] = [];
    }
    public static function getDefaultMap(): Room
    {
        $tempMap = new Room("hall");
        $_SESSION["curRoom"] = &$tempMap;
        $tempMap->path = ["hall"];
        $tempMap->doors["library"] = new Room(name: "library", requiredRole: ROLE::APPRENTICE);
        $tempMap->doors["armory"] = new Room(name: "armory", requiredRole: ROLE::CONJURER);
        $tempMap->doors["passage"] = new Room(name: "passage", requiredRole: ROLE::WANDERER);
        $tempMap->doors["passage"]->doors["staircase"] = new Room(name: "staircase", path: $tempMap->doors["passage"]->path, requiredRole: ROLE::ROOT);

        $tempMap->items["manaRune.sh"] = new Spell(
            name: "",
            baseName: "manaRune",
            type: ItemType::SPELL,
            action: ActionType::GET_MANA,
            requiredRole: ROLE::WANDERER
        );
        $tempMap->items["grimoire.txt"] = new Scroll(
            "",
            "grimoire",
            ItemType::SCROLL,
            Role::CONJURER,
            "OPEN SCROLL: <br>'cat [scroll name]'<br>"
        );
        $tempMap->items["oldDiary.txt"] = new Scroll(
            "",
            "oldDiary",
            ItemType::SCROLL,
            Role::WANDERER,
            "some old diary text about hunting boar"
        );
        $tempMap->items["ancientAlter.exe"] = new Alter(
            "",
            "ancientAlter",
            ItemType::ALTER,
            Role::ROOT,
            true,
            ["dusty_key.txt"],
            new Room("rewardRoom"),
        );
        $tempMap->doors["passage"]->items["dusty_key.txt"] = new Scroll(
            "",
            "dusty_key",
            ItemType::SCROLL,
            Role::ROOT,
            "Tak pease and wassh hem clene, and ley hem in watre over nyght, that they may swelle and waxe tendre. On the morwe, set hem on the fyre in a fayre pot with clene watre, and let hem boyle softly til they breke.  Then tak an oynoun and hew it smal, and put it therinne with salt ynowe. Add herbes, as perselye or saverey, if thou hast, and let al seeth togider.  Whan the potage is thikke and smothe, tak it fro the fyre and serve it hote, with brede y-toasted or a crust therof. This potage is good for the body and may serve pore and riche.",
        );
        echo "<br>map after creation: " . json_encode($tempMap);
        return $tempMap;
    }
}
function getRankFromXp($xp): Role
{
    for ($i = 1; $i <= count(Role::cases()); $i++) {
        if ($xp <= $i * 100) {
            foreach (Role::cases() as $role) {
                if ($i == 1) {
                    return $role;
                }
                $i--;
            }
        }
    }
    throw new Exception("role not found?");
}