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
            WHERE id = :mapId
            AND user_id = :userId"
        );
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
        session_unset();
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
        $_SESSION["mapId"] = $gameState["id"];
        $_SESSION["gameState"] = new GameState($gameState["xp"]);
        $_SESSION["map"] = Room::fromArray(json_decode($gameState["map_json"]));
        $_SESSION["history"] = [];
        if ($gameState["history_json"] != NULL) parseHistory($gameState["history_json"]);
        $_SESSION["gameState"]->getCurrentMessage();
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
            $statesData[$data["id"]]["rank"] = Rank::getRankFromXp($data["xp"])->value;
        }
        return $statesData;
    }
    public function createGameState($name, $rank)
    {
        $xp = Rank::tryFrom($rank)->rank() * 100;
        if (!isset($_SESSION["isLoggedIn"]))
        {
            self::loadDefaultSession();

            $_SESSION["gameState"] = new GameState($xp);
            $_SESSION["gameState"]->getCurrentMessage();
        }
        else
        {
            $gameStateInsert = $this->pdo->prepare("
            INSERT INTO game_states ( user_id, name, map_json, xp)
            VALUES (:userId, :stateName, :mapJson,  :xp) ");
            $gameStateInsert->execute([
                "userId" => $_SESSION["user"]["id"],
                "stateName" => $name,
                "mapJson" => json_encode(self::getDefaultMap()),
                "xp" => $xp
            ]);
            $this->loadGameState($this->pdo->lastInsertId());
            $_SESSION["gameState"]->getCurrentMessage();
        }
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
        $_SESSION["history"] = [];
        $_SESSION["history"][] = [
            "directory" => "",
            "command" => "",
            "response" => "",
        ];
        $_SESSION["gameState"] = new GameState(0);
        $_SESSION["terminal"] = new Terminal();
        $_SESSION["map"] = self::getDefaultMap();
        $_SESSION["tokens"] = [];
        $_SESSION["curRoom"] = &$_SESSION["map"];
        $_SESSION["user"]["username"] = "guest";
        $_SESSION["lastPath"] = [];
    }
    public static function getDefaultMap(): Room
    {
        $tempMap = new Room(
            "",
            [""],
            curDate: false,
        );
        $_SESSION["curRoom"] = &$tempMap;
        $tempMap->doors["entrance"] = new Room(
            name: "entrance",
            path: [""],
            requiredRank: Rank::WANDERER,
            curDate: false
        );

        $entrance = $tempMap->doors["entrance"];
        $entrance->doors["library"] = new Room(
            name: "library",
            path: $entrance->path,
            requiredRank: Rank::WANDERER,
            curDate: false,
        );
        $library = $entrance->doors["library"];
        $library->items["cat.sh"] = new Spell("cat.sh", "cat", path: $library->path, rewardSpell: Commands::CAT);
        $entrance->doors["stairsdown"] = new Room(
            name: "stairsdown",
            path: $entrance->path,
            requiredRank: Rank::WANDERER,
            curDate: false,
        );
        $entrance->doors["arsenal"] = new Room(
            name: "arsenal",
            path: $entrance->path,
            requiredRank: Rank::APPRENTICE,
            curDate: false,
        );

        $arsenal = $entrance->doors["arsenal"];
        $arsenal->items["promise.txt"] = new Scroll(
            "",
            "promise",
            $arsenal->path,
            content: "coming soon :)"
        );
        $stairsDown = $entrance->doors["stairsdown"];
        $stairsDown->doors["catacombs"] = new Room(
            name: "catacombs",
            path: $stairsDown->path,
            requiredRank: Rank::WANDERER,
            curDate: false,
        );
        $catacombs = $stairsDown->doors["catacombs"];
        $catacombs->doors["archives"] = new Room(
            name: "archives",
            path: $catacombs->path,
            requiredRank: Rank::WANDERER,
            curDate: false,
        );
        $catacombs->doors["cellar"] = new Room(
            name: "cellar",
            path: $catacombs->path,
            requiredRank: Rank::WANDERER,
            curDate: false,
        );
        $catacombs->items["crumpledNote.txt"] = new Scroll(
            name: "",
            baseName: "crumpledNote",
            path: $catacombs->path,
            requiredRank: Rank::WANDERER,
            curDate: false,
            content: "DONT GO FURTHER IF YOU EVER WANT TO RETURN",
        );
        $archives = $catacombs->doors["archives"];
        $archives->doors["longpassage"] = new Room(
            "longpassage",
            $archives->path,
            requiredRank: Rank::WANDERER
        );
        $longpassage = $archives->doors["longpassage"];
        $longpassage->doors["foyer"] = new Room(
            "foyer",
            $longpassage->path,
            requiredRank: Rank::WANDERER,
        );
        $foyer = $longpassage->doors["foyer"];
        $foyer->doors["ceremonialroom"] = new Room(
            "ceremonialroom",
            $foyer->path,
            requiredRank: Rank::WANDERER
        );
        $foyer->items["echo.sh"] = new Spell(
            "",
            "echo",
            $foyer->path,
            Commands::ECHO
        );
        $foyer->items["mantra.txt"] = new Scroll(
            "",
            "mantra",
            $foyer->path,
            Rank::WANDERER,
            "abracadabrasimsalabim",
            false,
        );
        $ceremonialroom = $foyer->doors["ceremonialroom"];
        $ceremonialroom->items["ancientAlter.exe"] = new Alter(
            name: "",
            baseName: "ancientAlter",
            path: $ceremonialroom->path,
            requiredRank: Rank::APPRENTICE,
            curDate: false,
            content: ""
        );
        $cellar = $catacombs->doors["cellar"];
        $cellar->items["README.txt"] = new Scroll(
            name: "",
            baseName: "README",
            path: $cellar->path,
            requiredRank: Rank::WANDERER,
            content: "To whoever keeps the Cellar now,<br<br> These stores were not meant for idle hands.<br> Every jar, every loaf, every scrap of parchment<br> was placed here with intention.<br> <br> Many believe that knowledge is kept only in manuals,<br> named plainly and shelved neatly.<br> Those people starve first.<br> <br> In this cellar, instructions are hidden among the ordinary.<br> Some words were written to be followed.<br> Others were written to be spoken.<br> <br> Read slowly.<br> Nothing here is accidental.<br> <br> — The Cellar Steward<br>",
        );
        $cellar->items["beefstew.txt"] = new Scroll(
            name: "",
            baseName: "beefstew",
            path: $cellar->path,
            requiredRank: Rank::WANDERER,
            content: "beefstew<br> <br> Notes from the Cook: Pour the stew over old bread.<br> The bread takes what it can.<br> What it cannot take is wasted.<br> <br> Choose your vessels wisely.<br>
            ",
        );
        $cellar->items["man.sh"] = new Spell(
            name: "",
            baseName: "man",
            path: $cellar->path,
            requiredRank: Rank::WANDERER,
            content: "the answer lies in something sweet...",
            rewardSpell: Commands::MAN,
        );
        $cellar->items["honeyfigcake.txt"] = new Scroll(
            name: "",
            baseName: "honeyfigcake",
            path: $cellar->path,
            requiredRank: Rank::WANDERER,
            content: "pure-delight",
        );
        $cellar->items["chickencasseRank.txt"] = new Scroll(
            name: "",
            baseName: "chickencasseRank",
            path: $cellar->path,
            requiredRank: Rank::WANDERER,
            content: "",
        );
        $cellar->items["bread.txt"] = new Scroll(
            "",
            "bread",
            $cellar->path,
            Rank::WANDERER,
            "Tak pease and wassh hem clene, and ley hem in watre over nyght, that they may swelle and waxe tendre. On the morwe, set hem on the fyre in a fayre pot with clene watre, and let hem boyle softly til they breke.  Then tak an oynoun and hew it smal, and put it therinne with salt ynowe. Add herbes, as perselye or saverey, if thou hast, and let al seeth togider.  Whan the potage is thikke and smothe, tak it fro the fyre and serve it hote, with brede y-toasted or a crust therof. This potage is good for the body and may serve pore and riche.",
            false
        );

        $tempMap->items["leaflet.txt"] = new Scroll(
            "",
            "leaflet",
            [""],
            Rank::WANDERER,
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
        //     [""],
        //     Rank::WANDERER,
        //     "",
        //     Commands::EXECUTE,
        //     "",
        //     false
        // );
        $tempMap->items["cd.sh"] = new Spell(
            "",
            "cd",
            [""],
            Commands::CD,
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
    public function getMapByLvl($Rank)
    {
        $tempMap = new Room(
            "",
            curDate: false,
        );
        $_SESSION["curRoom"] = &$tempMap;
        $tempMap->path = [""];

        switch ($Rank)
        {
            case Rank::WANDERER:
                {
                    $tempMap = new Room(
                        "",
                        curDate: false,
                    );
                    $tempMap = new Room(
                        "",
                        curDate: false,
                    );
                    $_SESSION["curRoom"] = &$tempMap;
                    $tempMap->path = [""];

                    $tempMap->path = [""];

                    $tempMap->doors["darkHall"] = new Room(
                        name: "darkHall",
                        path: [""],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );

                    $tempMap->items["bread.txt"] = new Scroll(
                        "",
                        "bread",
                        [""],
                        Rank::WANDERER,
                        "Tak pease and wassh hem clene, and ley hem in watre over nyght, that they may swelle and waxe tendre. On the morwe, set hem on the fyre in a fayre pot with clene watre, and let hem boyle softly til they breke.  Then tak an oynoun and hew it smal, and put it therinne with salt ynowe. Add herbes, as perselye or saverey, if thou hast, and let al seeth togider.  Whan the potage is thikke and smothe, tak it fro the fyre and serve it hote, with brede y-toasted or a crust therof. This potage is good for the body and may serve pore and riche.",
                        false
                    );


                    $tempMap->items["mysteriousNote.txt"] = new Scroll(
                        "",
                        "mysteriousNote",
                        [""],
                        Rank::WANDERER,
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
                        [""],
                        Rank::WANDERER,
                        "...so in short, when using the Spell cd with a correct destination, you can quickly move around !",
                        false
                    );

                    $tempMap->doors["darkHall"]->doors["darkPassage"] = new Room(
                        name: "darkPassage",
                        path: ["", "darkHall"],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["rustyIronDoor"] = new Room(
                        name: "rustyIronDoor",
                        path: ["", "darkHall"],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["oldDoor"] = new Room(
                        name: "oldDoor",
                        path: ["", "darkHall"],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["burntDoor"] = new Room(
                        name: "burntDoor",
                        path: ["", "darkHall"],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["rustyCell"] = new Room(
                        name: "rustyCell",
                        path: ["", "darkHall"],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["oldDoor"]->items["hint.txt"] = new Scroll(
                        name: "",
                        baseName: "hint",
                        path: ["", "darkHall", "oldDoor"],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );

                    $tempMap->doors["darkHall"]->doors["rustyIronDoor"]->doors["crackedDoor"] = new Room(
                        name: "crackedDoor",
                        path: ["", "darkHall", "rustyIronDoor"],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["rustyIronDoor"]->doors["wornGate"] = new Room(
                        name: "wornGate",
                        path: ["", "darkHall", "rustyIronDoor"],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["rustyCell"]->doors["hidden"] = new Room(
                        name: "hidden",
                        path: ["", "darkHall", "rustyCell"],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );
                    $tempMap->doors["darkHall"]->doors["burntDoor"]->doors["spellGate"] = new Room(
                        name: "spellGate",
                        path: ["", "darkHall", "burntDoor"],
                        requiredRank: Rank::WANDERER,
                        curDate: false
                    );
                    break;
                }
            case Rank::APPRENTICE:
                {
                    $tempMap = new Room("darkRoom", curDate: false);
                    $_SESSION["curRoom"] = &$tempMap;
                    $tempMap->path = ["darkRoom"];

                    $tempMap->doors["oakGate"] = new Room(
                        name: "oakGate",
                        path: ["darkRoom"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false
                    );

                    $tempMap->doors["ironGate"] = new Room(
                        name: "ironGate",
                        path: ["darkRoom"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false
                    );

                    $tempMap->doors["crystalDoor"] = new Room(
                        name: "crystalDoor",
                        path: ["darkRoom"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false
                    );

                    $tempMap->doors["whisperingArch"] = new Room(
                        name: "whisperingArch",
                        path: ["darkRoom"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false
                    );

                    $tempMap->doors["crystalDoor"]->items["listingscroll.txt"] = new Scroll(
                        "",
                        "listingscroll",
                        ["darkRoom", "crystalDoor"],
                        Rank::APPRENTICE,
                        "",
                        false
                    );

                    $tempMap->doors["runeDoor"] = new Room(
                        name: "runeDoor",
                        path: ["darkRoom"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["ironGate"] = new Room(
                        name: "ironGate",
                        path: ["darkRoom"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["hiddenDoor"] = new Room(
                        name: "hiddenDoor",
                        path: ["darkRoom"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["arcaneGate"] = new Room(
                        name: "arcaneGate",
                        path: ["darkRoom"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["shadowDoor"] = new Room(
                        name: "shadowDoor",
                        path: ["darkRoom"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["runeDoor"]->doors["whisperGate"] = new Room(
                        name: "whisperGate",
                        path: ["darkRoom", "runeDoor"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["runeDoor"]->doors["sealedDoor"] = new Room(
                        name: "sealedDoor",
                        path: ["darkRoom", "runeDoor"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["ironGate"]->doors["prisonGate"] = new Room(
                        name: "prisonGate",
                        path: ["darkRoom", "ironGate"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["ironGate"]->doors["rustedGate"] = new Room(
                        name: "rustedGate",
                        path: ["darkRoom", "ironGate"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["hiddenDoor"]->doors["falseWall"] = new Room(
                        name: "falseWall",
                        path: ["darkRoom", "hiddenDoor"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["hiddenDoor"]->doors["slidingPanel"] = new Room(
                        name: "slidingPanel",
                        path: ["darkRoom", "hiddenDoor"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["arcaneGate"]->doors["spellDoor"] = new Room(
                        name: "spellDoor",
                        path: ["darkRoom", "arcaneGate"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["arcaneGate"]->doors["sigilGate"] = new Room(
                        name: "sigilGate",
                        path: ["darkRoom", "arcaneGate"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["shadowDoor"]->doors["cursedDoor"] = new Room(
                        name: "cursedDoor",
                        path: ["darkRoom", "shadowDoor"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );

                    $tempMap->doors["shadowDoor"]->doors["bloodGate"] = new Room(
                        name: "bloodGate",
                        path: ["darkRoom", "shadowDoor"],
                        requiredRank: Rank::APPRENTICE,
                        curDate: false,
                    );
                    break;
                }
            case Rank::ARCHIVIST:
                {
                }
            case Rank::CONJURER:
                {
                }
            case Rank::ROOT:
                {
                }
        }
    }
}
function parseHistory($historyJson)
{
    unset($_SESSION["history"]);
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
