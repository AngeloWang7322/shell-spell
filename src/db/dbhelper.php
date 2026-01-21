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
            "UPDATE game_states SET 
            map_json = :mapJson,
            history_json = :historyJson,
            xp = :xp
            WHERE user_id = :userId"
        );

        $response = $query->execute([
            ":userId" => $_SESSION["user"]["id"],
            ":mapJson" => json_encode($_SESSION["map"]),
            ":historyJson" => json_encode($_SESSION["history"]),
            ":xp" => $_SESSION["gameController"]->xp,
        ]);
    }
    public function loginUser($password, $email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash']))
        {
            $_SESSION['user']["name"] = $user['username'];
            $_SESSION["user"]["id"] = $user["id"];
            $_SESSION["isLoggedIn"] = true;
            $_SESSION["profile_pic"] = $user["profile_pic_path"] ?? null;
        }
        else
        {
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

        $_SESSION["mapName"] = $gameState["name"];
        $_SESSION["user"]["xp"] = $gameState["xp"];
        $_SESSION["gameController"] = new GameController($gameState["xp"]);
        $_SESSION["map"] = Room::fromArray(json_decode($gameState["map_json"]));
        parseHistory($gameState["history_json"]);
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
        foreach ($response as $data)
        {
            $statesData[$data["id"]]["name"] = $data["name"];
            $statesData[$data["id"]]["rank"] = getRankFromXp($data["xp"])->value;
        }
        return $statesData;
    }
    public function createGameState($name)
    {
        $gameStateInsert = $this->pdo->prepare("
            INSERT INTO game_states ( user_id, name, map_json, history_json)
            VALUES (:userId, :stateName, :mapJson, :historyJson)
        ");
        $gameStateInsert->execute([
            "userId" => $_SESSION["user"]["id"],
            "stateName" => $name,
            "mapJson" => json_encode(self::getDefaultMap()),
            "historyJson" => json_encode($_SESSION["history"]),
        ]);
        $this->loadGameState($this->pdo->lastInsertId());
    }
    public function deleteGameState($stateId)
    {
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
        $_SESSION["gameController"] = new GameController();
        $_SESSION["tokens"]["command"] = "";
        $_SESSION["tokens"]["path"] = [];
        $_SESSION["tokens"]["options"] = [];
        $_SESSION["tokens"]["keyValueOptions"] = [];
        $_SESSION["tokens"]["misc"] = [];
        $_SESSION["validCommands"] = ["cd", "cat"];
        $_SESSION["history"] = [];
        $_SESSION["history"][] = [
            "directory" => "",
            "command" => "> Welcome to ShellSpell !",
            "response" => "> Cast <p1>cat [scrollname]</p1> to read the scroll"
        ];
        $_SESSION["pipeCount"] = 0;
        $_SESSION["map"] = self::getDefaultMap();
        $_SESSION["curRoom"] = &$_SESSION["map"];
        $_SESSION["user"]["role"] = ROLE::WANDERER;
        $_SESSION["user"]["username"] = "guest";
        $_SESSION["lastPath"] = [];
        $_SESSION["mapName"] = "dungeon";
    }
    public static function getDefaultMap(): Room
    {
        $tempMap = new Room(
            "hall",
            curDate: false,
        );
        $_SESSION["curRoom"] = &$tempMap;
        $tempMap->path = ["hall"];
        $tempMap->doors["oldDoor"] = new Room(
            name: "oldDoor",
            requiredRole: ROLE::ROOT,
            curDate: false,
        );
        $tempMap->doors["hidden"] = new Room(
            "hidden",
            [],
            [],
            [],
            ROLE::WANDERER,
            false,
            true,
        );
        $tempMap->items["rippedPage.txt"] = new Scroll(
            "",
            "rippedPage",
            ["hall"],
            Role::WANDERER,
            "...so in short, when using the Spell <p1>cd</p1> with a correct destination, you can quickly move around ! ",
            false,
        );
        $tempMap->items["log.txt"] = new Scroll(
            "",
            "log",
            ["hall"],
            Role::APPRENTICE,
            "",
            false,
        );
        $tempMap->items["secret.txt"] = new Scroll(
            "",
            "secret",
            ["hall"],
            Role::ARCHIVIST,
            "",
            false,
        );
        $tempMap->items["bread.txt"] = new Scroll(
            "",
            "bread",
            ["hall"],
            Role::CONJURER,
            "Tak pease and wassh hem clene, and ley hem in watre over nyght, that they may swelle and waxe tendre. On the morwe, set hem on the fyre in a fayre pot with clene watre, and let hem boyle softly til they breke.  Then tak an oynoun and hew it smal, and put it therinne with salt ynowe. Add herbes, as perselye or saverey, if thou hast, and let al seeth togider.  Whan the potage is thikke and smothe, tak it fro the fyre and serve it hote, with brede y-toasted or a crust therof. This potage is good for the body and may serve pore and riche.",
            false,
        );
        $tempMap->doors["oldDoor"]->doors["longHallway"] = new Room(
            name: "longHallway",
            path: ["hall", "oldDoor"],
            requiredRole: ROLE::WANDERER,
            curDate: false,
        );

        $tempMap->doors["oldDoor"]->doors["longHallway"]->doors["sideEntrance"] = new Room(
            name: "sideEntrance",
            path: ["hall", "oldDoor"],
            requiredRole: ROLE::WANDERER,
            curDate: false,
        );

        $tempMap->doors["oldDoor"]->doors["longHallway"]->doors["sideEntrance"]->doors["stairway"] = new Room(
            name: "stairway",
            path: ["hall", "oldDoor", "longHallway"],
            requiredRole: ROLE::APPRENTICE,
            curDate: false,
        );
        $tempMap->doors["oldDoor"]->doors["longHallway"]->items["note.txt"] = new Scroll(
            "",
            "note",
            ["hall", "oldDoor", "longHallway", "sideEntrance"],
            Role::WANDERER,
            "i don't know",
        );

        // $tempMap = new Room(
        //     "hall",
        //     curDate: false,
        // );
        // $_SESSION["curRoom"] = &$tempMap;
        // $tempMap->path = ["hall"];
        // $tempMap->doors["library"] = new Room(
        //     name: "library",
        //     requiredRole: ROLE::APPRENTICE,
        //     curDate: false,
        // );
        // $tempMap->doors["armory"] = new Room(
        //     name: "armory",
        //     requiredRole: ROLE::CONJURER,
        //     curDate: false,
        // );
        // $tempMap->doors["passage"] = new Room(
        //     name: "passage",
        //     requiredRole: ROLE::WANDERER,
        //     curDate: false,
        // );
        // $tempMap->doors["passage"]->doors["staircase"] = new Room(
        //     name: "staircase",
        //     path: $tempMap->doors["passage"]->path,
        //     requiredRole: ROLE::ROOT,
        //     curDate: false,
        // );
        // $tempMap->doors["tavern"] = new Room(
        //     name: "tavern",
        //     path: ["hall"],
        //     requiredRole: ROLE::ARCHIVIST,
        //     curDate: false,
        // );
        // $tempMap->items["manaRune.sh"] = new Spell(
        //     name: "",
        //     baseName: "manaRune",
        //     path: ["hall"],
        //     action: ActionType::GET_MANA,
        //     requiredRole: ROLE::WANDERER,
        //     curDate: false
        // );
        // $tempMap->items["grimoire.txt"] = new Scroll(
        //     "",
        //     "grimoire",
        //     ["hall"],
        //     Role::CONJURER,
        //     "OPEN SCROLL: <br>'cat [scroll name]'<br>",
        //     curDate: false
        // );
        // $tempMap->items["oldDiary.txt"] = new Scroll(
        //     "",
        //     "oldDiary",
        //     ["hall"],
        //     Role::WANDERER,
        //     "some old diary text about hunting boar",
        //     curDate: false
        // );
        // $tempMap->items["ancientAlter.exe"] = new Alter(
        //     "",
        //     "ancientAlter",
        //     ["hall"],
        //     Role::ROOT,
        //     "if you see this, here is a little tip",
        //     true,
        //     ["dusty_key.txt"],
        //     new Room(name: "rewardRoom", curDate: true),
        //     curDate: false
        // );

        // $tempMap->doors["passage"]->items["dusty_key.txt"] = new Scroll(
        //     "",
        //     "dusty_key",
        //     ["hall", "passage"],
        //     Role::ROOT,
        //     "Tak pease and wassh hem clene, and ley hem in watre over nyght, that they may swelle and waxe tendre. On the morwe, set hem on the fyre in a fayre pot with clene watre, and let hem boyle softly til they breke.  Then tak an oynoun and hew it smal, and put it therinne with salt ynowe. Add herbes, as perselye or saverey, if thou hast, and let al seeth togider.  Whan the potage is thikke and smothe, tak it fro the fyre and serve it hote, with brede y-toasted or a crust therof. This potage is good for the body and may serve pore and riche.",
        //     curDate: false
        // );
        // $tempMap->doors["passage"]->items["recipe.txt"] = new Scroll(
        //     "",
        //     "recipe",
        //     ["hall", "passage"],
        //     Role::ROOT,
        //     "Tak pease and wassh hem clene, and ley hem in watre over nyght, that they may swelle and waxe tendre. On the morwe, set hem on the fyre in a fayre pot with clene watre, and let hem boyle softly til they breke.  Then tak an oynoun and hew it smal, and put it therinne with salt ynowe. Add herbes, as perselye or saverey, if thou hast, and let al seeth togider.  Whan the potage is thikke and smothe, tak it fro the fyre and serve it hote, with brede y-toasted or a crust therof. This potage is good for the body and may serve pore and riche.",
        //     curDate: false
        // );
        return $tempMap;
    }
    public function setUserProfilePic(int $userId, string $path): void
    {
        $stmt = $this->pdo->prepare("
        UPDATE users
        SET profile_pic_path = :path
        WHERE id = :id
    ");
        $stmt->execute([
            ":path" => $path,
            ":id" => $userId
        ]);
    }
}
function getRankFromXp($xp): Role
{
    for ($i = 1; $i <= count(Role::cases()); $i++)
    {
        if ($xp <= $i * 100)
        {
            foreach (Role::cases() as $role)
            {
                if ($i == 1)
                {
                    return $role;
                }
                $i--;
            }
        }
    }
    throw new Exception("role not found?");
}
function parseHistory($historyJson)
{
    $_SESSION["history"] = [];
    foreach ((array)json_decode($historyJson) as $element)
    {
        $_SESSION["history"][] = [
            "directory" => $element->directory,
            "command" => $element->command,
            "response" => $element->response
        ];
    }
}
