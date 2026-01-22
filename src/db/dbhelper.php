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
        $_SESSION["tokens"]["command"] = "";
        $_SESSION["tokens"]["path"] = [];
        $_SESSION["tokens"]["options"] = [];
        $_SESSION["tokens"]["keyValueOptions"] = [];
        $_SESSION["tokens"]["misc"] = [];
        $_SESSION["validCommands"] = ["cd", "cat"];
        $_SESSION["mapName"] = "dungeon";
        $_SESSION["pipeCount"] = 0;
        $_SESSION["map"] = self::getDefaultMap();
        $_SESSION["curRoom"] = &$_SESSION["map"];
        $_SESSION["user"]["role"] = ROLE::WANDERER;
        $_SESSION["user"]["username"] = "guest";
        $_SESSION["lastPath"] = [];
        $_SESSION["response"] = "";
        $_SESSION["history"] = [];
        $_SESSION["history"][] = [
            "directory" => "",
            "command" => "",
            "response" => "",
        ];
        $_SESSION["gameController"] = new GameController(410);
        $_SESSION["gameController"]->getCurrentMessage();
    }
    public static function getDefaultMap(): Room
    {
        $tempMap = new Room(
            "hall",
            curDate: false,
        );
        $_SESSION["curRoom"] = &$tempMap;
        $tempMap->path = ["hall"];

        $tempMap->doors["entrance"] = new Room(
            name: "entrance",
            path: ["hall"],
            requiredRole: Role::WANDERER,
            curDate: false
        );

        $entrance = $tempMap->doors["entrance"];
        $entrance->doors["stairsdown"] = new Room(
            name: "stairsdown",
            path: $entrance->path,
            requiredRole: Role::WANDERER,
            curDate: false,
        );
        $entrance->doors["arsenal"] = new Room(
            name: "arsenal",
            path: $entrance->path,
            requiredRole: Role::APPRENTICE,
            curDate: false,
        );

        $arsenal = $entrance->doors["arsenal"];
        $stairsDown = $entrance->doors["stairsdown"];
        $stairsDown->doors["catacombs"] = new Room(
            name: "catacombs",
            path: $stairsDown->path,
            requiredRole: Role::WANDERER,
            curDate: false,
        );
        $catacombs = $stairsDown->doors["catacombs"];
        $catacombs->doors["archives"] = new Room(
            name: "archives",
            path: $catacombs->path,
            requiredRole: Role::WANDERER,
            curDate: false,
        );
        $catacombs->doors["cellar"] = new Room(
            name: "cellar",
            path: $catacombs->path,
            requiredRole: Role::WANDERER,
            curDate: false,
        );
        $catacombs->items["crumpledNote.txt"] = new Scroll(
            name: "",
            baseName: "crumpledNote",
            path: $catacombs->path,
            requiredRole: Role::WANDERER,
            curDate: false,
            content: "DONT GO FURTHER IF YOU EVER WANT TO RETURN",
        );
        $archives = $catacombs->doors["archives"];
        $archives->doors["longpassage"] = new Room(
            "longpassage",
            $archives->path,
            requiredRole: Role::WANDERER
        );
        $longpassage = $archives->doors["longpassage"];
        $longpassage->doors["foyer"] = new Room(
            "foyer",
            $longpassage->path,
            requiredRole: Role::WANDERER,
        );
        $foyer = $longpassage->doors["foyer"];
        $foyer->doors["ceremonialroom"] = new Room(
            "ceremonialroom",
            $foyer->path,
           requiredRole: Role::WANDERER
        );
        $foyer->items["execute.sh"] = new Spell(
            "",
            "execute.sh",
            $foyer->path,
            Role::WANDERER,
            "brautkleidbleibtbrautkleidundblaukrautbleibtblaukraut",
            Commands::EXECUTE,
            "abracadabrasimsalabim",
        );
        $foyer->items["mantra.txt"] = new Scroll(
            "",
            "mantra",
            $foyer->path,
            Role::WANDERER,
            "abracadabrasimsalabim",
            Commands::EXECUTE,
        );
        $ceremonialroom = $foyer->doors["ceremonialroom"];
        $ceremonialroom->items["ancientAlter.exe"] = new Alter(
            name: "",
            baseName: "ancientAlter.exe",
            path: $ceremonialroom->path,
            requiredRole: Role::APPRENTICE,
            curDate: false,
            content: ""
        );
        $cellar = $catacombs->doors["cellar"];
        $cellar->items["README.txt"] = new Scroll(
            name: "",
            baseName: "README",
            path: $cellar->path,
            requiredRole: Role::WANDERER,
            content: "To whoever keeps the Cellar now,<br<br> These stores were not meant for idle hands.<br> Every jar, every loaf, every scrap of parchment<br> was placed here with intention.<br> <br> Many believe that knowledge is kept only in manuals,<br> named plainly and shelved neatly.<br> Those people starve first.<br> <br> In this cellar, instructions are hidden among the ordinary.<br> Some words were written to be followed.<br> Others were written to be spoken.<br> <br> Read slowly.<br> Nothing here is accidental.<br> <br> — The Cellar Steward<br>",
        );
        $cellar->items["shepherdspie.txt"] = new Scroll(
            name: "",
            baseName: "shepherdspie",
            path: $cellar->path,
            requiredRole: Role::WANDERER,
            content: "",
        );
        $cellar->items["beefstew.txt"] = new Scroll(
            name: "",
            baseName: "beefstew",
            path: $cellar->path,
            requiredRole: Role::WANDERER,
            content: "beefstew<br> <br> Notes from the Cook: Pour the stew over old bread.<br> The bread takes what it can.<br> What it cannot take is wasted.<br> <br> Choose your vessels wisely.<br>
            ",
        );
        $cellar->items["man.sh"] = new Spell(
            name: "",
            baseName: "man",
            path: $cellar->path,
            requiredRole: Role::WANDERER,
            content: "the answer lies in something sweet...",
            spellReward: Commands::MAN,
            key: "pure-delightfulness"
        );
        $cellar->items["honeyfigcake.txt"] = new Scroll(
            name: "",
            baseName: "honeyfigcake",
            path: $cellar->path,
            requiredRole: Role::WANDERER,
            content: "pure-delightfulness",
        );
        $cellar->items["chickencasserole.txt"] = new Scroll(
            name: "",
            baseName: "chickencasserole",
            path: $cellar->path,
            requiredRole: Role::WANDERER,
            content: "",
        );
        $cellar->items["bread.txt"] = new Scroll(
            "",
            "bread",
            $cellar->path,
            Role::WANDERER,
            "Tak pease and wassh hem clene, and ley hem in watre over nyght, that they may swelle and waxe tendre. On the morwe, set hem on the fyre in a fayre pot with clene watre, and let hem boyle softly til they breke.  Then tak an oynoun and hew it smal, and put it therinne with salt ynowe. Add herbes, as perselye or saverey, if thou hast, and let al seeth togider.  Whan the potage is thikke and smothe, tak it fro the fyre and serve it hote, with brede y-toasted or a crust therof. This potage is good for the body and may serve pore and riche.",
            false
        );

        $tempMap->items["leaflet.txt"] = new Scroll(
            "",
            "leaflet",
            ["hall"],
            Role::WANDERER,
            "Welcome, wanderer.<br>
                    <br>
                    You have entered the realm of ShellSpell<br> 
                    a dungeon shaped like a command line.<br>
                    <br>
                    Here, rooms are directories,<br>
                    scrolls are files,<br>  
                    and knowledge is your only weapon.<br>
                    <br>
                    Listen and read carefully to learn the spells the ancient shell<br> 
                    to explore, solve riddles<br>
                    and uncover the secrets hidden in the depths of this dungeon.<br>
                    You will find alters at the end of each level.<br>
                    <br> 
                    Type carefully — every command matters.<br>
                    <br>
                    Your journey begins here.",
            false
        );

        // $tempMap->items["execute.sh"] = new Spell(
        //     "",
        //     "execute",
        //     ["hall"],
        //     Role::WANDERER,
        //     "",
        //     Commands::EXECUTE,
        //     "",
        //     false
        // );
        $tempMap->items["cd.sh"] = new Spell(
            "",
            "cd",
            ["hall"],
            Role::WANDERER,
            "",
            Commands::CD,
            "",
            false
        );
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
    public function getMapByLvl($role)
    {
        $tempMap = new Room(
            "hall",
            curDate: false,
        );
        $_SESSION["curRoom"] = &$tempMap;
        $tempMap->path = ["hall"];

        switch ($role)
        {
            case Role::WANDERER:
                {
                    $tempMap = new Room(
                        "hall",
                        curDate: false,
                    );
                    $tempMap = new Room(
                        "hall",
                        curDate: false,
                    );
                    $_SESSION["curRoom"] = &$tempMap;
                    $tempMap->path = ["hall"];

                    $tempMap->path = ["hall"];

                    $tempMap->doors["darkHall"] = new Room(
                        name: "darkHall",
                        path: ["hall"],
                        requiredRole: Role::WANDERER,
                        curDate: false
                    );

                    $tempMap->items["bread.txt"] = new Scroll(
                        "",
                        "bread",
                        ["hall"],
                        Role::WANDERER,
                        "Tak pease and wassh hem clene, and ley hem in watre over nyght, that they may swelle and waxe tendre. On the morwe, set hem on the fyre in a fayre pot with clene watre, and let hem boyle softly til they breke.  Then tak an oynoun and hew it smal, and put it therinne with salt ynowe. Add herbes, as perselye or saverey, if thou hast, and let al seeth togider.  Whan the potage is thikke and smothe, tak it fro the fyre and serve it hote, with brede y-toasted or a crust therof. This potage is good for the body and may serve pore and riche.",
                        false
                    );


                    $tempMap->items["mysteriousNote.txt"] = new Scroll(
                        "",
                        "mysteriousNote",
                        ["hall"],
                        Role::WANDERER,
                        "Welcome, wanderer.<br>
                    <br>
                    You have entered the realm of ShellSpell<br> 
                    a dungeon shaped like a command line.<br>
                    <br>
                    Here, rooms are directories,<br>
                    scrolls are files,<br>  
                    and knowledge is your only weapon.<br>
                    <br>
                    Listen and read carefully to learn the spells the ancient shell<br> 
                    to explore, solve riddles<br>
                    and uncover the secrets hidden in the depths of this dungeon.<br>
                    You will find alters at the end of each level.<br>
                    <br> 
                    Type carefully — every command matters.<br>
                    <br>
                    Your journey begins here.",
                        false
                    );

                    $tempMap->items["rippedPage.txt"] = new Scroll(
                        "",
                        "rippedPage",
                        ["hall"],
                        Role::WANDERER,
                        "...so in short, when using the Spell cd with a correct destination, you can quickly move around !",
                        false
                    );

                    $tempMap->doors["darkHall"]->doors["darkPassage"] = new Room(
                        name: "darkPassage",
                        path: ["hall", "darkHall"],
                        requiredRole: ROLE::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["rustyIronDoor"] = new Room(
                        name: "rustyIronDoor",
                        path: ["hall", "darkHall"],
                        requiredRole: ROLE::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["oldDoor"] = new Room(
                        name: "oldDoor",
                        path: ["hall", "darkHall"],
                        requiredRole: ROLE::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["burntDoor"] = new Room(
                        name: "burntDoor",
                        path: ["hall", "darkHall"],
                        requiredRole: ROLE::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["rustyCell"] = new Room(
                        name: "rustyCell",
                        path: ["hall", "darkHall"],
                        requiredRole: ROLE::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["oldDoor"]->items["hint.txt"] = new Scroll(
                        name: "",
                        baseName: "hint",
                        path: ["hall", "darkHall", "oldDoor"],
                        requiredRole: ROLE::WANDERER,
                        curDate: false
                    );

                    $tempMap->doors["darkHall"]->doors["rustyIronDoor"]->doors["crackedDoor"] = new Room(
                        name: "crackedDoor",
                        path: ["hall", "darkHall", "rustyIronDoor"],
                        requiredRole: ROLE::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["rustyIronDoor"]->doors["wornGate"] = new Room(
                        name: "wornGate",
                        path: ["hall", "darkHall", "rustyIronDoor"],
                        requiredRole: ROLE::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["rustyCell"]->doors["hidden"] = new Room(
                        name: "hidden",
                        path: ["hall", "darkHall", "rustyCell"],
                        requiredRole: ROLE::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["burntDoor"]->doors["spellGate"] = new Room(
                        name: "spellGate",
                        path: ["hall", "darkHall", "burntDoor"],
                        requiredRole: ROLE::WANDERER,
                        curDate: false
                    );
                    break;
                }
            case Role::APPRENTICE:
                {
                    $tempMap = new Room("darkRoom", curDate: false);
                    $_SESSION["curRoom"] = &$tempMap;
                    $tempMap->path = ["darkRoom"];

                    $tempMap->doors["oakGate"] = new Room(
                        name: "oakGate",
                        path: ["darkRoom"],
                        requiredRole: Role::APPRENTICE,
                        curDate: false
                    );

                    $tempMap->doors["ironGate"] = new Room(
                        name: "ironGate",
                        path: ["darkRoom"],
                        requiredRole: Role::APPRENTICE,
                        curDate: false
                    );

                    $tempMap->doors["crystalDoor"] = new Room(
                        name: "crystalDoor",
                        path: ["darkRoom"],
                        requiredRole: Role::APPRENTICE,
                        curDate: false
                    );

                    $tempMap->doors["whisperingArch"] = new Room(
                        name: "whisperingArch",
                        path: ["darkRoom"],
                        requiredRole: Role::APPRENTICE,
                        curDate: false
                    );

                    $tempMap->doors["crystalDoor"]->items["listingscroll.txt"] = new Scroll(
                        "",
                        "listingscroll",
                        ["darkRoom", "crystalDoor"],
                        Role::APPRENTICE,
                        "",
                        false
                    );

                    $tempMap->doors["runeDoor"] = new Room(
                        name: "runeDoor",
                        path: ["darkRoom"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["ironGate"] = new Room(
                        name: "ironGate",
                        path: ["darkRoom"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["hiddenDoor"] = new Room(
                        name: "hiddenDoor",
                        path: ["darkRoom"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["arcaneGate"] = new Room(
                        name: "arcaneGate",
                        path: ["darkRoom"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["shadowDoor"] = new Room(
                        name: "shadowDoor",
                        path: ["darkRoom"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["runeDoor"]->doors["whisperGate"] = new Room(
                        name: "whisperGate",
                        path: ["darkRoom", "runeDoor"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["runeDoor"]->doors["sealedDoor"] = new Room(
                        name: "sealedDoor",
                        path: ["darkRoom", "runeDoor"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["ironGate"]->doors["prisonGate"] = new Room(
                        name: "prisonGate",
                        path: ["darkRoom", "ironGate"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["ironGate"]->doors["rustedGate"] = new Room(
                        name: "rustedGate",
                        path: ["darkRoom", "ironGate"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["hiddenDoor"]->doors["falseWall"] = new Room(
                        name: "falseWall",
                        path: ["darkRoom", "hiddenDoor"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["hiddenDoor"]->doors["slidingPanel"] = new Room(
                        name: "slidingPanel",
                        path: ["darkRoom", "hiddenDoor"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["arcaneGate"]->doors["spellDoor"] = new Room(
                        name: "spellDoor",
                        path: ["darkRoom", "arcaneGate"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["arcaneGate"]->doors["sigilGate"] = new Room(
                        name: "sigilGate",
                        path: ["darkRoom", "arcaneGate"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["shadowDoor"]->doors["cursedDoor"] = new Room(
                        name: "cursedDoor",
                        path: ["darkRoom", "shadowDoor"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["shadowDoor"]->doors["bloodGate"] = new Room(
                        name: "bloodGate",
                        path: ["darkRoom", "shadowDoor"],
                        requiredRole: ROLE::APPRENTICE,
                        curDate: false,
                    );
                    break;
                }
            case Role::ARCHIVIST:
                {
                }
            case Role::CONJURER:
                {
                }
            case Role::ROOT:
                {
                }
        }
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
    foreach ((array) json_decode($historyJson) as $element)
    {
        $_SESSION["history"][] = [
            "directory" => $element->directory,
            "command" => $element->command,
            "response" => $element->response
        ];
    }
}
