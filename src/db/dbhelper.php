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
        } else {
            throw new Exception("Email oder Passwort falsch");
        }
    }
    public function createUserRows($password, $email, $name)
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

        //remaining columns get set via defaults
        $gameStateInsert = $this->pdo->prepare("
            INSERT INTO game_states ( user_id, map_json)
            VALUES (:userId, :mapJson)
        ");
        $gameStateInsert->execute( [
            "userId" => $_SESSION["user"]["id"],
            "mapJson" => json_encode($_SESSION["map"])
        ]);
        echo json_encode($gameStateInsert->fetch());
    }
    public function getGameState($stateName)
    {
        $userId = $_SESSION["user"]["id"];

        $fetchGameState = $this->pdo->prepare("
            SELECT * FROM game_states 
                WHERE user_id = :userId 
                AND state_name = :stateName
        ");
        $fetchGameState->execute([
            "userId" => $userId,
            "stateName" => $stateName
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
    public function getGameStateOptions()
    {
        $fetchStatesData = $this->pdo->prepare("
            SELECT name, xp FROM game_states 
                WHERE user_id = :userId 
        ");
        $fetchStatesData->execute([
            "userId" => $_SESSION["user"]["id"]
        ]);
        $statesData = [];
        echo json_encode($fetchStatesData->fetch());
        foreach ($fetchStatesData->fetch() as $data) {
            $statesData[$data["name"]] = calcRankByXp($data["xp"]);
        }
        return $statesData;
    }
    public static function loadDefaultSession()
    {
        session_unset();
        $_SESSION["history"] = [];
        $_SESSION["map"] = new Room("hall");
        $_SESSION["curRoom"] = &$_SESSION["map"];
        $_SESSION["map"]->path = ["hall"];
        $_SESSION["map"]->doors["library"] = new Room(name: "library", requiredRole: ROLE::APPRENTICE);
        $_SESSION["map"]->doors["armory"] = new Room(name: "armory", requiredRole: ROLE::CONJURER);
        $_SESSION["map"]->doors["passage"] = new Room(name: "passage", requiredRole: ROLE::WANDERER);
        $_SESSION["map"]->doors["passage"]->doors["staircase"] = new Room(name: "staircase", path: $_SESSION["map"]->doors["passage"]->path, requiredRole: ROLE::ROOT);

        $_SESSION["map"]->items["manaRune.sh"] = new Spell(
            name: "",
            baseName: "manaRune",
            type: ItemType::SPELL,
            action: ActionType::GET_MANA,
            requiredRole: ROLE::WANDERER
        );
        $_SESSION["map"]->items["grimoire.txt"] = new Scroll(
            "",
            "grimoire",
            ItemType::SCROLL,
            Role::CONJURER,
            "OPEN SCROLL: <br>'cat [scroll name]'<br>"
        );
        $_SESSION["map"]->items["oldDiary.txt"] = new Scroll(
            "",
            "oldDiary",
            ItemType::SCROLL,
            Role::WANDERER,
            "some old diary text about hunting boar"
        );
        $_SESSION["map"]->items["ancientAlter.exe"] = new Alter(
            "",
            "ancientAlter",
            ItemType::ALTER,
            Role::ROOT,
            true,
            ["dusty_key.txt"],
            new Room("rewardRoom"),
        );
        $_SESSION["map"]->doors["passage"]->items["dusty_key.txt"] = new Scroll(
            "",
            "dusty_key",
            ItemType::SCROLL,
            Role::ROOT,
            "Tak pease and wassh hem clene, and ley hem in watre over nyght, that they may swelle and waxe tendre. On the morwe, set hem on the fyre in a fayre pot with clene watre, and let hem boyle softly til they breke.  Then tak an oynoun and hew it smal, and put it therinne with salt ynowe. Add herbes, as perselye or saverey, if thou hast, and let al seeth togider.  Whan the potage is thikke and smothe, tak it fro the fyre and serve it hote, with brede y-toasted or a crust therof. This potage is good for the body and may serve pore and riche.",
        );
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
}
function calcRankByXp($xp): string
{
    for ($i = 1; $i <= count(Role::cases()); $i++) {
        if ($xp <= $i * 100) {
            foreach (Role::cases() as $role) {
                if ($i == 1) {
                    return $role->value;
                }
                $i--;
            }
        }
    }
    throw new Exception("role not found?");
}