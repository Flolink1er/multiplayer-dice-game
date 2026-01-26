<?php
session_start();
if (isset($_POST["action"]))
    $data = $_POST; // envois de posts
else {
    $data = json_decode(file_get_contents('php://input'), true);
}

if (!isset($data["action"])) {
    echo json_encode(["status" => "error", "message" => "aucune action demandée", "data" => $data]);
    exit;
} elseif ($data["action"] == "logout") {
    // on veut se déconnecter
    session_destroy();
    echo json_encode(["status" => "success", "message" => "Vous êtes déconnecté"]);
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "dementer");


switch ($data["action"]) {
    case "login":
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["email"], $_POST["password"])) {
            $requete = $mysqli->prepare("SELECT * FROM player WHERE Email = ? AND DT_Supp IS NULL");
            $email = $_POST["email"];
            $password = $_POST["password"];
            $requete->bind_param("s", $email);
            $requete->execute();

            $result = $requete->get_result();

            if ($player = $result->fetch_array(MYSQLI_ASSOC)) {
                if (password_verify($password, $player["Mdp"])) {
                    $_SESSION["ID"] = $player["ID_player"];
                    echo json_encode(["status" => "succes", "message" => "Connexion réussie !", "session_id" => $_SESSION["ID"]]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Email ou mot de passe incorrect."]);
                }
                exit;
            } else {
                echo json_encode(["status" => "unknown", "message" => "Email ou mot de passe incorrect"]);
            }
        }
        break;

    case "create_account":
        if (isset($_FILES['profil_picture']) && $_FILES['profil_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profil_picture']['tmp_name'];
            $fileName = $_FILES['profil_picture']['name'];
            $fileType = $_FILES['profil_picture']['type'];

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif']; //restriction des types de fichier autorisés

            if (in_array($fileType, $allowedMimeTypes)) {
                $newFileName = uniqid('\profile_', true) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
                $uploadFileDir = '..\profil-pic';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    if (isset($data["username"], $data["email"], $data["password"])) {
                        $profil_pic = $dest_path;
                        $username = $data['username'];
                        $email = $data["email"];
                        $password = password_hash($data["password"], PASSWORD_DEFAULT);
                    } else {
                        echo json_encode(["status" => "error", "message" => "Tous les champs ne sont pas remplis"]);
                    }
                } else {
                    echo json_encode(["status" => "error", "message" => "Erreur lors du téléchargement du fichier."]);
                    exit;
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Format de fichier non autorisé."]);
                exit;
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur au chargement du fichier"]);
        }

        $new_player = $mysqli->prepare("INSERT INTO player SET 
                                                Email = ?,
                                                Username = ?,
                                                Mdp = ?,
                                                Profil_pic = ?,
                                                DT_Creat = NOW()");

        $params = [$email, $username, $password, $profil_pic];
        $new_player->bind_param("ssss", ...$params);
        if ($new_player->execute()) {
            $new_id = $new_player->insert_id;
            $_SESSION['ID'] = $new_id;
            echo json_encode(["status" => "succes", "message" => "Compte créé !", "session_id" => $new_id]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur DB !"]);
        }

        break;

    case "check_logged":
        $request = $mysqli->prepare("SELECT * FROM player WHERE ID_player = ?");
        $request->bind_param("i", $_SESSION["ID"]);
        $request->execute();

        $result = $request->get_result();

        if ($result->num_rows == 0) {
            echo json_encode(["status" => "error", "message" => "Id de session ou joueur introuvable"]);
        } else {
            echo json_encode(["status" => "succes", "message" => "Joueur connecté", "id" => $_SESSION['ID']]);
        }
        break;

    case "get_profil":
        $requete = $mysqli->prepare("SELECT ID_player, Email, Username, Profil_pic, Eliar, DT_Creat FROM player WHERE ID_player = ?");
        $id_user = $_SESSION['ID'];
        $requete->bind_param("i", $id_user);
        $requete->execute();

        $result = $requete->get_result();

        if ($player = $result->fetch_array(MYSQLI_ASSOC)) {
            echo json_encode(["status" => "succes", "message" => "Profil trouvé", "player" => $player]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur DB"]);
        }
        break;

    case "modify_profil":
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["username"], $_POST["email"])) {
            $id_user = $_SESSION["ID"] ?? null;

            if (!$id_user) {
                echo json_encode(["status" => "error", "message" => "Utilisateur non connecté."]);
                exit;
            }

            $username = $_POST["username"];
            $email = $_POST["email"];
            $password = $_POST["password"] ?? null;

            $fields = [];
            $params = [];
            $types = ""; // pour bind_param

            // Username
            if (!empty($username)) {
                $fields[] = "Username = ?";
                $params[] = $username;
                $types .= "s";
            }

            // Email
            if (!empty($email)) {
                $fields[] = "Email = ?";
                $params[] = $email;
                $types .= "s";
            }

            // Password (optionnel)
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $fields[] = "Mdp = ?";
                $params[] = $hashed_password;
                $types .= "s";
            }

            // Photo de profil (optionnelle)
            if (isset($_FILES['profil_picture']) && $_FILES['profil_picture']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['profil_picture']['tmp_name'];
                $fileName = $_FILES['profil_picture']['name'];
                $fileType = $_FILES['profil_picture']['type'];

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif']; //restriction des types de fichier autorisés

                if (in_array($fileType, $allowedMimeTypes)) {
                    $newFileName = uniqid('\profile_', true) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
                    $uploadFileDir = '..\profil-pic';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0755, true);
                    }
                    $dest_path = $uploadFileDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        $fields[] = "Profil_pic = ?";
                        $params[] = $dest_path;
                        $types .= "s";
                    } else {
                        echo json_encode(["status" => "error", "message" => "Erreur lors du téléchargement du fichier."]);
                        exit;
                    }
                } else {
                    echo json_encode(["status" => "error", "message" => "Format de fichier non autorisé."]);
                    exit;
                }
            }

            if (!empty($fields)) {
                // Construction dynamique de la requête SQL
                $sql = "UPDATE player SET " . implode(", ", $fields) . " WHERE ID_player = ?";
                $params[] = $id_user;
                $types .= "i";

                $requete = $mysqli->prepare($sql);

                $requete->bind_param($types, ...$params);
                if ($requete->execute()) {
                    echo json_encode(["status" => "success", "message" => "Profil mis à jour avec succès."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Erreur lors de la mise à jour."]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Aucune donnée à mettre à jour."]);
            }
        }
        break;

    case "get_history":
        $request = $mysqli->prepare("SELECT g.ID_game, g.Nb_player_max, g.Started, g.Finished, DATE(g.DT_Creat) AS date, l.Ranked FROM game AS g 
                                            LEFT JOIN lobby AS l ON g.ID_game = l.ID_Game 
                                            WHERE l.ID_Player = ?
                                            ORDER BY g.DT_Creat DESC");
        $request->bind_param("i", $_SESSION['ID']);
        if ($request->execute()) {
            $result = $request->get_result();
            if ($result->num_rows == 0) {
                echo json_encode(["status" => "nomatch", "message" => "Aucune partie trouvée"]);
            } else {
                $games = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(["status" => "succes", "message" => "Historique trouvé", "games" => $games]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur DB"]);
        }
        break;

    case "get_ranking":
        $request = $mysqli->prepare("SELECT Username, Eliar from player ORDER BY Eliar DESC LIMIT 6");
        $request->execute();
        $results = $request->get_result();
        if ($results->num_rows == 0) {
            echo json_encode(["status" => "succes", "message" => "Aucun joueur ayant des Eliar trouvé", "noranks" => false]);
        } else {
            $ranks = $results->fetch_all(MYSQLI_ASSOC);
            echo json_encode(["status" => "succes", "message" => "Top Dix joueurs trouvé", "ranks" => $ranks]);
        }
        break;

    case "create_party":
        $request = $mysqli->prepare("INSERT INTO game SET 
                                        Nb_player_max = ?,
                                        Nb_player_still = ?,
                                        DT_Creat = NOW()");
        $fields = [$data['nb_player'], 1];
        $request->bind_param("ii", ...$fields);

        if ($request->execute()) {
            $new_game = $request->insert_id;
            $requete = $mysqli->prepare("INSERT INTO lobby SET
                                            ID_Game = ?,
                                            ID_Player = ?,
                                            Ready = ?,
                                            Queued = ?,
                                            HP = 5");
            $new_fields = [$new_game, $data["id_player"], intval(false), 0];
            $requete->bind_param("iiii", ...$new_fields);

            if ($requete->execute()) {
                echo json_encode(["status" => "succes", "message" => "Partie créée et rejoins !", "id_game" => $new_game]);
            } else {
                echo json_encode(["status" => "error", "message" => "Erreur pour rejoindre la partie"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur lors de la création de partie"]);
        }
        break;
    case "join_party":
        $request = $mysqli->prepare("SELECT Nb_player_max, Nb_player_still FROM game WHERE ID_game = ? AND Started IS NULL AND Finished IS NULL");
        $request->bind_param("i", $data["game_id"]);
        $request->execute();

        $result = $request->get_result();

        if ($result->num_rows == 0) {
            echo json_encode(["status" => "Error", "message" => "Partie introuvable ou déjà lancée"]);
            break;
        }
        $game = $result->fetch_array(MYSQLI_ASSOC);


        if ($game["Nb_player_max"] > $game["Nb_player_still"]) {
            $new_nb_player = $game["Nb_player_still"] + 1;

            $join_req = $mysqli->prepare("INSERT INTO lobby SET 
                                            ID_Game = ?,
                                            ID_Player = ?,
                                            Ready = ?,
                                            Queued = ?,
                                            HP = 5");
            $fields = [$data["game_id"], $data["id_player"], intval(false), $game["Nb_player_still"]];
            $join_req->bind_param("iiii", ...$fields);
            if ($join_req->execute()) {
                $updt_game = $mysqli->prepare("UPDATE game SET Nb_player_still = ? WHERE ID_game = ?");
                $updt_fields = [$new_nb_player, $data["game_id"]];
                $updt_game->bind_param("ii", ...$updt_fields);
                $updt_game->execute();
                echo json_encode(["status" => "succes", "message" => "Partie rejointe !", "id_game" => $data["game_id"]]);
            } else {
                echo json_encode(["status" => "error", "message" => "Erreur DB"]);
            }
        } else {
            echo json_encode(["status" => "nope", "message" => "La partie a déjà commencé ou elle contient déjà le maximum de joueur"]);
        }
        break;

    case "leave_game":
        $request = $mysqli->prepare("DELETE FROM lobby WHERE ID_Game = ? AND ID_Player = ?");
        $fields = [$data["id_game"], $data["id_player"]];
        $request->bind_param("ii", ...$fields);
        if ($request->execute()) {
            $get_info = $mysqli->prepare("SELECT Nb_player_still FROM game WHERE ID_game = ?");
            $get_info->bind_param("i", $data["id_game"]);
            $get_info->execute();
            $result = $get_info->get_result();
            $game = $result->fetch_array(MYSQLI_ASSOC);

            if ($game["Nb_player_still"] == 1) {
                $sql = "DELETE FROM game WHERE ID_game = ?";
                $new_fields = [$data["id_game"]];
                $param = "i";
            } else {
                $sql = "UPDATE game SET Nb_player_still = ? WHERE ID_game = ?";
                $new_fields = [$game["Nb_player_still"] - 1, $data["id_game"]];
                $param = "ii";
            }
            $updt_game = $mysqli->prepare($sql);
            $updt_game->bind_param($param, ...$new_fields);
            $updt_game->execute();

            echo json_encode(["status" => "succes", "message" => "Partie quittée"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur DB"]);
        }
        break;

    case "get_player_game":
        $request = $mysqli->prepare("SELECT p.Username, p.Profil_pic, p.ID_player, l.Ready, l.ID_Player, l.HP, l.Queued, l.Ranked FROM player AS p 
                                                JOIN lobby AS l ON p.ID_player = l.ID_Player WHERE l.ID_Game = ?");

        $id_game = intval($data["id_game"]);
        $request->bind_param("i", $id_game);
        $request->execute();
        $result = $request->get_result();

        if ($players = $result->fetch_all(MYSQLI_ASSOC)) {
            echo json_encode(["status" => "succes", "message" => "Joueur de la partie trouvés !", "players" => $players]);
        } else {
            echo json_encode(["status" => "error", "message" => "Pas de joueurs trouvé"]);
        }
        break;

    case "get_info_game":
        $request = $mysqli->prepare("SELECT * FROM game WHERE ID_game = ?");
        $id_game = intval($data["id_game"]);
        $request->bind_param("i", $id_game);
        $request->execute();
        $result = $request->get_result();

        if ($game = $result->fetch_array(MYSQLI_ASSOC)) {
            echo json_encode(["status" => "succes", "message" => "Infos de la partie trouvés !", "game" => $game]);
        } else {
            echo json_encode(["status" => "error", "message" => "Partie non trouvée"]);
        }
        break;

    case "change_ready":
        $requete = $mysqli->prepare("SELECT Ready FROM lobby WHERE ID_Game = ? AND ID_Player = ?");
        $id_game = intval($data['id_game']);
        $id_player = $data["id_player"];

        $fields = [$id_game, $id_player];
        $requete->bind_param("ii", ...$fields);
        $requete->execute();
        $result = $requete->get_result();

        if ($player = $result->fetch_array()) {
            if ($player['Ready'] == 1) {
                $request = $mysqli->prepare("UPDATE lobby SET Ready = ? WHERE ID_Game = ? AND ID_Player = ?");
                $update = [0, $id_game, $id_player];
                $color = 'red';
                $message = "Le joueur n'est plus prêt";
            } else {
                $request = $mysqli->prepare("UPDATE lobby SET Ready = ? WHERE ID_Game = ? AND ID_Player = ?");
                $update = [1, $id_game, $id_player];
                $color = 'green';
                $message = "Le joueur est prêt";
            }
            $request->bind_param("iii", ...$update);
            if ($request->execute()) {
                echo json_encode(["status" => "succes", "message" => $message, "color" => $color]);
            } else {
                echo json_encode(["status" => "error", "message" => "ERREUR DB"]);
            }

        } else {
            echo json_encode(["status" => "error", "message" => "Partie ou joueur non trouvé"]);
        }

        break;

    case "get_msg":
        $request = $mysqli->prepare("SELECT m.content, p.Username, p.ID_player, m.ID_Message FROM messages AS m 
                                            LEFT JOIN player AS p ON m.ID_Player = p.ID_player 
                                            WHERE m.ID_Game = ? 
                                            ORDER BY m.DT_Creat ASC");

        $request->bind_param("i", $data["id_game"]);
        $request->execute();
        $result = $request->get_result();

        if ($result->num_rows > 0) {
            if ($messages = $result->fetch_all(MYSQLI_ASSOC)) {
                echo json_encode(["status" => "succes", "message" => "Liste des messages récupérée", "msg_list" => $messages]);
            } else {
                echo json_encode(["status" => "error", "message" => "Erreur lors de la récupération des messages"]);
            }

        } else {
            echo json_encode(["status" => "nothing", "message" => "Aucun message trouvé"]);
        }
        break;

    case "send_msg":
        $request = $mysqli->prepare("INSERT INTO messages SET
                                                ID_Player = ?,
                                                ID_Game = ?,
                                                content = ?,
                                                DT_Creat = NOW()");
        $fields = [$data["id_player"], $data["id_game"], $data["msg"]];
        $request->bind_param("iis", ...$fields);
        if ($request->execute()) {
            echo json_encode(["status" => "succes", "message" => "Message envoyé !"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Le message ne s'est pas envoyé"]);
        }
        break;

    case "launch_game":
        $request = $mysqli->prepare("UPDATE game SET Started = NOW() WHERE ID_game = ?");
        $id_game = $data['id_game'];
        $request->bind_param("i", $id_game);
        if ($request->execute()) {
            $get_player = $mysqli->prepare("SELECT ID_Player FROM lobby WHERE ID_Game = ?");
            $get_player->bind_param("i", $id_game);
            $get_player->execute();
            $result = $get_player->get_result();
            $player_list = $result->fetch_all();
            foreach ($player_list as $player) {
                $requete = $mysqli->prepare("UPDATE lobby SET 
                                                    Dice1 = ?, 
                                                    Dice2 = ?, 
                                                    Dice3 = ?, 
                                                    Dice4 = ?, 
                                                    Dice5 = ? 
                                        WHERE ID_Game = ? AND ID_Player = ?");
                $fields = [];
                $count = 0;
                while ($count < 5) {
                    $fields[$count] = rand(1, 6);
                    $count++;
                }
                $fields[$count] = $id_game;
                $count++;
                $fields[$count] = $player[0];

                $requete->bind_param("iiiiiii", ...$fields);

                if ($requete->execute()) {
                    continue;
                } else {
                    echo json_encode(['status' => 'error', 'message' => "Erreur lors du tirage des dés"]);
                }
            }
            echo json_encode(["status" => "succes", "message" => "Partie lancée !", "players" => $player_list]);
        } else {
            echo json_encode(["status" => "error", "message" => "erreur lors du lancement de la partie"]);
        }
        break;
    case "get_guess":
        $requete = $mysqli->prepare("SELECT Current_guess FROM game WHERE ID_game = ?");
        $requete->bind_param("i", $data['id_game']);
        $requete->execute();

        $check = $requete->get_result();
        $checkup = $check->fetch_array();
        if ($checkup[0] == NULL) {
            echo json_encode(["status" => "succes", "pari" => false, "message" => "Aucun pari lancé, le prochain joueur doit obligatoirement en soumettre un nouveau"]);
        } else {
            $request = $mysqli->prepare("SELECT JSON_EXTRACT(current_guess, '$.nb_dice') as nb_dice, 
                                                        JSON_EXTRACT(current_guess, '$.digit_dice') as digit_dice 
                                                        FROM game WHERE ID_game = ?");

            $request->bind_param("i", $data['id_game']);
            $request->execute();
            $result = $request->get_result();
            if ($guess = $result->fetch_array()) {
                echo json_encode(["status" => "succes", "pari" => true, "message" => "Pari actuel trouvé !", "guess" => $guess]);
            } else {
                echo json_encode(["status" => "error", "message" => "Erreur lors de la récupération du pari"]);
            }
        }

        break;

    case "get_turn":
        $request = $mysqli->prepare("SELECT Queued FROM lobby WHERE ID_Game = ? AND ID_Player = ?");
        $fields = [$data["id_game"], $_SESSION['ID']];
        $request->bind_param("ii", ...$fields);
        $request->execute();

        $result = $request->get_result();
        if ($result->num_rows == 1) {
            $queued = $result->fetch_array(MYSQLI_ASSOC);
            if ($queued['Queued'] === NULL) {
                echo json_encode(["status" => "succes", "message" => "Vous êtes éliminé !", "your_turn" => false]);
            } elseif ($queued['Queued'] == 1) {
                echo json_encode(["status" => "succes", "message" => "Vous êtes le prochain !", "your_turn" => false]);
            } elseif ($queued['Queued'] == 0) {
                echo json_encode(["status" => "succes", "message" => "Votre Tour !", "your_turn" => true]);
            } else {
                echo json_encode(["status" => "succes", "message" => "Veuillez patienter !", "your_turn" => false]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur DB"]);
        }
        break;

    case "get_player_dices":
        $request = $mysqli->prepare("SELECT Dice1, Dice2, Dice3, Dice4, Dice5, HP FROM lobby WHERE ID_Game = ? AND ID_Player = ?");
        $fields = [$data["id_game"], $_SESSION['ID']];
        $request->bind_param("ii", ...$fields);
        $request->execute();
        $result = $request->get_result();
        if ($dices_player = $result->fetch_array()) {
            echo json_encode(["status" => "succes", "message" => "Dés récupérés", "dice_list" => $dices_player]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur lors de la récupération des dés"]);
        }
        break;
    case "new_guess":
        $requete = $mysqli->prepare("SELECT Username FROM player WHERE ID_player = ?");
        $requete->bind_param("i", $_SESSION['ID']);
        $requete->execute();
        $result = $requete->get_result();
        $username = $result->fetch_array();
        $request = $mysqli->prepare("UPDATE game SET Current_guess = JSON_OBJECT(?, ?, ?, ?), last_event = ? WHERE ID_game = ?");
        $new_nb_dice = $data['nb_dice'];
        $new_digit_dice = $data['dice_digit'];
        $new_event = "Le joueur " . $username['Username'] . " a lancé un nouveau pari";

        $fields = ["nb_dice", $new_nb_dice, "digit_dice", $new_digit_dice, $new_event, $data['id_game']];

        $request->bind_param("sisisi", ...$fields);
        if ($request->execute()) {
            echo json_encode(["status" => "succes", "message" => "Pari mis à jour"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur DB"]);
        }
        break;

    case "pass_turn":
        $get_player = $mysqli->prepare("SELECT l.ID_Player as ID_Player, l.Queued as Queued, g.Nb_player_still as Nb_player_still 
                                                        FROM lobby AS l
                                                        JOIN game AS g ON g.ID_game = l.ID_Game 
                                                        WHERE l.ID_Game = ? AND l.Ranked IS NULL");

        $get_player->bind_param("i", $data['id_game']);
        $get_player->execute();
        $result = $get_player->get_result();
        $player_list = $result->fetch_all();
        foreach ($player_list as $player) {
            $requete = $mysqli->prepare("UPDATE lobby SET Queued = ?
                                            WHERE ID_Game = ? AND ID_Player = ?");

            if ($player[1] == 0) {
                $new_queue = $player[2] - 1;
            } else {
                $new_queue = $player[1] - 1;
            }
            $fields = [$new_queue, $data['id_game'], $player[0]];
            $requete->bind_param("iii", ...$fields);

            if ($requete->execute()) {
                echo json_encode(["status" => "succes", "message" => "Queue mise à jour"]);
            } else {
                echo json_encode(['status' => 'error', 'message' => "Erreur lors de la mise à jour de la queue"]);
            }
        }
        break;

    case "liar":
        //récupère le dernier pari ainsi que l'id du joueur qui l'a lancé
        $request = $mysqli->prepare("SELECT l.ID_Player, JSON_EXTRACT(g.current_guess, '$.nb_dice') as nb_dice, 
                                                    JSON_EXTRACT(g.current_guess, '$.digit_dice') as digit_dice FROM game AS g 
                                            JOIN lobby AS l ON l.ID_Game = g.ID_game
                                            WHERE g.ID_game = ? AND g.Nb_player_still-1 = l.Queued");
        $request->bind_param("i", $data['id_game']);
        $request->execute();
        $result = $request->get_result();
        if ($player = $result->fetch_array()) {
            $accused = $player['ID_Player'];

            $digit_dice = $player['digit_dice'];
            $nb_dice = $player['nb_dice'];

            //récupère le nombre de dé ayant la valeur pariée
            $check_guess = $mysqli->prepare("SELECT SUM( IF(Dice1 = ?, 1, 0)) 
		                                                    + SUM( IF(Dice2 = ?, 1, 0)) 
                                                            + SUM( IF(Dice3 = ?, 1, 0)) 
                                                            + SUM( IF(Dice4 = ?, 1, 0)) 
                                                            + SUM( IF(Dice5 = ?, 1, 0)) AS nb_check
                                                    FROM lobby WHERE ID_Game = ?");
            $fields = [];
            for ($i = 0; $i < 5; $i++) {
                $fields[$i] = $digit_dice;
            }
            $fields[5] = $data['id_game'];
            $check_guess->bind_param("iiiiii", ...$fields);
            $check_guess->execute();

            $sum = $check_guess->get_result();
            if ($check = $sum->fetch_array()) {
                if ($check['nb_check'] >= $nb_dice) {
                    //le dernier parieur a gagné et l'accuseur perd un dé et un hp
                    echo json_encode(["status" => "succes", "result" => "not_liar", "message" => "Le parieur ne s'est pas trompé"]);
                } else {
                    //le dernier parieur a perdu et perd un dé et un hp
                    echo json_encode(["status" => "succes", "result" => "liar", "message" => "Le parieur s'est trompé", "id_liar" => $accused]);
                }

            } else {
                echo json_encode(["status" => "error", "message" => "*BIP* pas bon, une erreur est survenue lors du comptage des dés"]);
            }

        }
        break;

    case "next_roll":
        $get_name = $mysqli->prepare("SELECT p.Username, p.Eliar, l.HP, l.Queued, g.Nb_player_still, g.Nb_player_max FROM player AS p
                                                    JOIN lobby AS l ON l.ID_Player = p.ID_player
                                                    JOIN game AS g ON l.ID_Game = g.ID_game
                                                    WHERE p.ID_player = ? AND l.ID_Game = ?");

        $get_name->bind_param("ii", $data["id_lost"], $data['id_game']);
        $get_name->execute();
        $result_name = $get_name->get_result();
        $name = $result_name->fetch_array();
        $username = $name['Username'];
        $endgame = false;

        if ($name['HP'] == 1) {
            if ($name['Nb_player_still'] == 2) {
                $endgame = true;
            }
            $del_player = $mysqli->prepare("UPDATE lobby SET
                                                Queued  = NULL, 
                                                Ranked    = ?, 
                                                HP      = 0, 
                                                Dice1   = null
                                                WHERE ID_Player = ? AND ID_Game = ?");
            $ranked = $name['Nb_player_still'];

            $champs = [$ranked, $data["id_lost"], $data['id_game']];
            $del_player->bind_param("iii", ...$champs);
            if ($del_player->execute()) {
                $udpt_game = $mysqli->prepare("UPDATE game SET 
                                                        Nb_player_still = ?
                                                        WHERE ID_Game = ?");
                $new_nb_still = $ranked - 1;
                $infos = [$new_nb_still, intval($data['id_game'])];
                $udpt_game->bind_param("ii", ...$infos);
                if ($udpt_game->execute()) {
                    $eliar_gain = $mysqli->prepare("UPDATE player SET Eliar = ? WHERE ID_player = ?");

                    $max_players = $name['Nb_player_max'];
                    $base_eliar = $name['Eliar'];
                    $add_eliar = (1 + $max_players) * ($max_players - $ranked);
                    /*Formule de calcul des points "Eliar" :
                        Eliar = (1 + Nb_player_max) * (Nb_player_max - Ranked) */
                    $new_eliar = $base_eliar + $add_eliar;
                    $eliar_fields = [$new_eliar, $data['id_lost']];
                    $eliar_gain->bind_param("ii", ...$eliar_fields);
                    if (!$eliar_gain->execute()) {
                        echo json_encode(["status" => "error", "message" => "Echec lors de l'ajout de point Eliar"]);
                    }
                } else {
                    echo json_encode(["status" => "error", "message" => "Echec de la mise à jour de la partie"]);
                }
                $eliminated = true;
            } else {
                echo json_encode(["status" => "error", "message" => "Echec de la mise à jour du joueur perdant"]);
            }
        } else {
            switch ($name['HP']) {
                case 2:
                    $query = "UPDATE lobby SET 
                                            Dice2 = NULL, 
                                            HP = ?,
                                            Queued = 0
                                            WHERE ID_Player = ? AND ID_Game = ?";
                    break;
                case 3:
                    $query = "UPDATE lobby SET 
                                            Dice3 = NULL, 
                                            HP = ?,
                                            Queued = 0
                                            WHERE ID_Player = ? AND ID_Game = ?";

                    break;
                case 4:
                    $query = "UPDATE lobby SET 
                                            Dice4 = NULL, 
                                            HP = ?,
                                            Queued = 0
                                            WHERE ID_Player = ? AND ID_Game = ?";

                    break;
                case 5:
                    $query = "UPDATE lobby SET 
                                            Dice5 = NULL, 
                                            HP = ?,
                                            Queued = 0
                                            WHERE ID_Player = ? AND ID_Game = ?";

                    break;
            }

            $lost = $mysqli->prepare($query);

            $fields = [$name['HP'] - 1, $data['id_lost'], $data['id_game']];
            $lost->bind_param("iii", ...$fields);
            if (!$lost->execute()) {
                echo json_encode(["status" => "error", "message" => "Echec de la mise à jour du joueur perdant"]);
            }
            $eliminated = false;
        }



        $get_player = $mysqli->prepare("SELECT ID_Player, HP, Queued FROM lobby 
                                                        WHERE ID_Game = ? AND Ranked IS NULL
                                                        ORDER BY Queued ASC");

        $get_player->bind_param("i", $data['id_game']);
        $get_player->execute();
        $result = $get_player->get_result();
        $player_list = $result->fetch_all();


        foreach ($player_list as $player) {
            if ($player[0] == $data['id_lost'] && $eliminated) {
                continue;
            }
            $requete = $mysqli->prepare("UPDATE lobby SET 
                                                    Dice1 = ?, 
                                                    Dice2 = ?, 
                                                    Dice3 = ?, 
                                                    Dice4 = ?, 
                                                    Dice5 = ?, 
                                                    Queued = ?
                                        WHERE ID_Game = ? AND ID_Player = ? AND Ranked IS NULL");
            $fields = [];
            for ($i = 0; $i < 5; $i++) { //boucle attribuant un chiffre aléatoire aux dés restant des joueurs
                if ($i < $player[1]) {
                    $fields[$i] = rand(1, 6);
                } else {
                    $fields[$i] = null;
                }
            }

            if ($name['Queued'] == 0) { //si le joueur perdant un HP est celui qui joue actuellement et s'il n'est pas éliminé
                if (!$eliminated) {
                    if ($player[0] == $data['id_lost']) { //l'accusé devient premier dans la file 
                        $fields[$i] = 0;
                        $i++;
                    } else {
                        $fields[$i] = $player[2];
                        $i++;
                    }
                } else {
                    $fields[$i] = $player[2] - 1;
                    $i++;
                }
            } else { //si c'est le joueur accusé qui pert un HP
                if (!$eliminated) {
                    if ($player[0] == $data['id_lost']) { //l'accusé devient premier dans la file 
                        $fields[$i] = 0;
                        $i++;
                    } else {
                        $fields[$i] = $player[2] + 1;
                        $i++;
                    }
                } else {
                    $fields[$i] = $player[2];
                    $i++;
                }

            }

            $fields[$i] = $data['id_game'];
            $i++;
            $fields[$i] = $player[0];

            $requete->bind_param("iiiiiiii", ...$fields);

            if (!$requete->execute()) {
                echo json_encode(['status' => 'error', 'message' => "Erreur lors du tirage des dés"]);
            }
        }

        $last_event = $mysqli->prepare("UPDATE game SET last_event = ?, Current_guess = NULL WHERE ID_game = ? ");

        $new_event = "Le joueur " . $name['Username'] . " a perdu un dé";
        $new_fields = [$new_event, $data['id_game']];

        $last_event->bind_param("si", ...$new_fields);

        if ($last_event->execute()) {
            echo json_encode(["status" => "succes", "message" => "Dé relancés !", "loser" => $name['Username'], "endgame" => $endgame]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur DB"]);
        }

        break;

    case "get_last_event":
        $request = $mysqli->prepare("SELECT last_event FROM game WHERE ID_Game = ?");
        $fields = [$data["id_game"]];
        $request->bind_param("i", ...$fields);
        if (!$request->execute()) {
            echo json_encode(["status" => "error", "message" => "Erreur DB !"]);
        }

        $result = $request->get_result();
        if ($result->num_rows == 1) {
            $event = $result->fetch_array();
            echo json_encode(["status" => "succes", "message" => "Dernier Event trouvé", "event" => $event]);
        } else {
            echo json_encode(["status" => "succes", "message" => "Aucun event encore encodé"]);
        }
        break;

    case "endgame":
        $id_game = $data['id_game'];

        $request = $mysqli->prepare("UPDATE lobby SET Ranked = 1 WHERE Ranked IS NULL AND ID_Game = ?");
        $request->bind_param("i", $id_game);
        if (!$request->execute()) {
            echo json_encode(["status" => "error", "message" => "Erreur lors de l'attribution de la première place"]);
        }

        $catch_winner = $mysqli->prepare("SELECT l.ID_Player, g.Nb_player_max, p.Eliar FROM lobby AS l
                                                        JOIN game AS g ON l.ID_Game = g.ID_game
                                                        JOIN player AS p ON l.ID_Player = p.ID_player
                                                        WHERE l.Ranked = 1 AND l.ID_Game = ?");
        $catch_winner->bind_param("i", $id_game);
        $catch_winner->execute();
        $result = $catch_winner->get_result();
        $winner = $result->fetch_array();

        $max_players = $winner['Nb_player_max'];
        $base_eliar = $winner['Eliar'];
        $add_eliar = (1 + $max_players) * ($max_players - 1);
        $new_eliar = $base_eliar + $add_eliar;

        $eliar_gain = $mysqli->prepare("UPDATE player SET Eliar = ? WHERE ID_player = ?");
        $eliar_fields = [$new_eliar, $winner['ID_Player']];
        $eliar_gain->bind_param("ii", ...$eliar_fields);

        if (!$eliar_gain->execute()) {
            echo json_encode(["status" => "error", "message" => "Erreur lors de l'ajout d'Eliar"]);
        }


        $stop_game = $mysqli->prepare("UPDATE game SET Finished = NOW(), Winner = ? WHERE ID_game = ?");
        $end_fields = [$winner['ID_Player'], $id_game];
        $stop_game->bind_param("ii", ...$end_fields);
        if (!$stop_game->execute()) {
            echo json_encode(["status" => "error", "message" => "Erreur lors de l'arrêt de la partie"]);
        }

        $get_players = $mysqli->prepare("SELECT p.Username, l.Ranked, g.Nb_player_max FROM player AS p 
                                                            JOIN lobby AS l ON l.ID_Player = p.ID_player
                                                            JOIN game As g ON g.ID_game = l.ID_Game
                                                            WHERE l.ID_Game = ?");
        $get_players->bind_param("i", $id_game);
        $get_players->execute();
        $player_result = $get_players->get_result();
        if ($players = $player_result->fetch_all()) {
            echo json_encode(["status" => "succes", "message" => "Partie Finie, voici la liste des joueurs et leurs résultats", "players" => $players]);
        } else {
            echo json_encode(["status" => "erro", "message" => "Erreur lors de l'arrêt de la partie"]);
        }

        break;

    case "check_endgame":
        $id_game = $data['id_game'];
        $check_party = $mysqli->prepare("SELECT Finished FROM game WHERE id_game = ?");
        $check_party->bind_param("i", $id_game);
        $check_party->execute();
        $result = $check_party->get_result();

        if ($finished = $result->fetch_array()) {
            $get_players = $mysqli->prepare("SELECT p.Username, l.Ranked, g.Nb_player_max FROM player AS p 
                                                            JOIN lobby AS l ON l.ID_Player = p.ID_player
                                                            JOIN game As g ON g.ID_game = l.ID_Game
                                                            WHERE l.ID_Game = ?
                                                            ORDER BY l.Ranked ASC");
            $get_players->bind_param("i", $id_game);
            $get_players->execute();
            $player_result = $get_players->get_result();
            if ($players = $player_result->fetch_all()) {
                echo json_encode(["status" => "succes", "message" => "Partie Finie, voici la liste des joueurs et leurs résultats", "players" => $players, "finished" => $finished]);
            } else {
                echo json_encode(["status" => "erro", "message" => "Erreur lors de l'arrêt de la partie"]);
            }
        }

        break;

    case "check_dual":
        $dual = $mysqli->prepare("SELECT COUNT(l.ID_Player) AS one_hp, g.Nb_player_still 
                                                FROM lobby AS l
                                                JOIN game AS g ON l.ID_Game = g.ID_game
                                                WHERE HP = 1 AND l.ID_Game = ?");
        $dual->bind_param("i", $data['id_game']);
        $dual->execute();

        $dual_result = $dual->get_result();
        $isdual = $dual_result->fetch_array();

        if ($isdual['one_hp'] == 2 && $isdual['Nb_player_still'] == 2) {
            echo json_encode(["status" => "succes", "message" => "C'est l'heure du duel !", "dual" => true]);
        } else {
            echo json_encode(["status" => "succes", "message" => "Aucun duel", "dual" => false]);

        }

        break;

    case "dual_time":
        $dual_guess = $mysqli->prepare("UPDATE lobby SET dual_guess = ?, Queued = ? 
                                            WHERE ID_Game = ? AND ID_Player = ?");

        $dual_fields = [intval($data['guess_value']), 1, $data['id_game'], $_SESSION['ID']];
        $dual_guess->bind_param("iiii", ...$dual_fields);

        if ($dual_guess->execute()) {
            $dual_count = $mysqli->prepare("SELECT COUNT(dual_guess) as guess_count FROM lobby 
                                                    WHERE ID_Game = ? AND dual_guess IS NOT NULL");
            $dual_count->bind_param("i", $data['id_game']);
            $dual_count->execute();
            $result = $dual_count->get_result();
            $count = $result->fetch_array();

            if ($count['guess_count'] == 2) {
                $compare_guess = $mysqli->prepare("SELECT ID_Player,
                                                            (SELECT SUM(Dice1) FROM lobby WHERE ID_Game = ? AND Ranked IS NULL) AS guess_win,
                                                            dual_guess
                                                            FROM lobby
                                                            WHERE ID_Game = ? AND Ranked IS NULL");

                $fields = [$data['id_game'], $data['id_game']];
                $compare_guess->bind_param("ii", ...$fields);
                $compare_guess->execute();
                $all_results = $compare_guess->get_result();
                $guesses = $all_results->fetch_all(MYSQLI_ASSOC);
                $guess_win = intval($guesses[0]['guess_win']);
                $dist1 = intval(abs(intval($guesses[0]['dual_guess']) - $guess_win));
                $dist2 = intval(abs(intval($guesses[1]['dual_guess']) - $guess_win));

                if ($dist1 < $dist2) { //joueur 1 gagne
                    echo json_encode(["status" => "succes", "message" => "Duel terminé", "loser" => $guesses[1]['ID_Player']]);
                } elseif ($dist1 > $dist2) { //joueur 2 gagne
                    echo json_encode(["status" => "succes", "message" => "Duel terminé", "loser" => $guesses[0]['ID_Player']]);
                } else { //égalité => on recommence
                    $dual_turn = $mysqli->prepare("UPDATE lobby SET Queued = 0
                                            WHERE ID_Game = ? AND Ranked IS NULL AND ID_Player = ?");
                    if ($guesses[1]['ID_Player'] == $_SESSION['ID']) {
                        $id_next = $guesses[0]['ID_Player'];
                    } else {
                        $id_next = $guesses[1]['ID_Player'];
                    }

                    $turn_fields = [$data['id_game'], $id_next];
                    $dual_turn->bind_param("ii", ...$turn_fields);
                    if ($dual_turn->execute()) {
                        $clear_guess = $mysqli->prepare("UPDATE lobby SET dual_guess = NULL WHERE ID_Game = ?");
                        $clear_guess->bind_param("i", $data['id_game']);
                        if ($clear_guess->execute()) {
                            echo json_encode(["status" => "succes", "message" => "Duel équitable", "draw" => true]);
                        }
                    } else {
                        echo json_encode(["status" => "error", "message" => "Erreur lors du passage de tour dans le duel"]);
                    }
                }

            } else {
                $dual_turn = $mysqli->prepare("UPDATE lobby SET Queued = 0
                                            WHERE ID_Game = ? AND Ranked IS NULL AND dual_guess IS NULL");

                $dual_turn->bind_param("i", $data['id_game']);
                if ($dual_turn->execute()) {
                    echo json_encode(["status" => "succes", "message" => "Premier paris lancé", "guess_over" => false]);
                }
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur lors de la mise à jour de la DB"]);
        }


        break;


}

?>