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
        SELECT * FROM user_stats
            WHERE user_id = :userId
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

        //assign userRole and maxMana by xp
        for ($i = 1; $i <= count(Role::cases()); $i++) {
            if ($userStats["xp"] <= $i * 100) {
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

        $_SESSION["map"] = self::fromArray(json_decode($userMap["map_json"]));
        $_SESSION["curMana"] = $userStats["curMana"];
        $_SESSION["curRoom"] =& $_SESSION["map"];
        $_SESSION["history"] = [];
    }

    public static function fromArray($data): Room
    {
        $doors = [];
        foreach ($data->doors as $key => $roomData) {
            $doors[$key] = self::fromArray($roomData);
        }

        $items = [];
        foreach ($data->items as $key => $itemData) {
            $items[$key] = match ($itemData->type) {
                ItemType::SCROLL->value => Scroll::fromArray((array) $itemData),
                ItemType::SPELL->value => Spell::fromArray((array) $itemData),
                ItemType::ALTER->value => Alter::fromArray((array) $itemData),
            };
        }

        $path = $data->path;
        if (count($path) > 1) {
            $path = array_slice($data->path, 0, -1);
        }

        $requiredRole = Role::from((string) $data->requiredRole);

        return new Room(
            $data->name,
            $path,
            $doors,
            $items,
            $requiredRole
        );
    }
    public static function loadDefaultSession()
    {
        session_unset();
        $_SESSION["history"] = [];
        $_SESSION["map"] = new Room("hall");
        $_SESSION["curRoom"] = &$_SESSION["map"];
        $_SESSION["map"]->path = ["hall"];
        $_SESSION["map"]->doors["library"] = new Room(name: "library", requiredRole: ROLE::APPRENTICE);
        $_SESSION["map"]->doors["armory"] = new Room(name: "armory", requiredRole: ROLE::ARCHIVIST);
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
            Role::ROOT,
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
            Role::CONJURER,
            "",
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
    }
}
