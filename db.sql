-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : dim. 08 juin 2025 à 16:50
-- Version du serveur : 8.3.0
-- Version de PHP : 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `dementer`
--

-- --------------------------------------------------------

--
-- Structure de la table `game`
--

DROP TABLE IF EXISTS `game`;
CREATE TABLE IF NOT EXISTS `game` (
  `ID_game` int NOT NULL AUTO_INCREMENT,
  `Nb_player_max` int NOT NULL DEFAULT '2',
  `Nb_player_still` int NOT NULL DEFAULT '0',
  `Current_guess` json DEFAULT NULL,
  `last_event` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Started` datetime DEFAULT NULL,
  `Finished` datetime DEFAULT NULL,
  `Winner` int DEFAULT NULL,
  `DT_Creat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_game`),
  KEY `Winner` (`Winner`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `game`
--

INSERT INTO `game` (`ID_game`, `Nb_player_max`, `Nb_player_still`, `Current_guess`, `last_event`, `Started`, `Finished`, `Winner`, `DT_Creat`) VALUES
(1, 2, 1, NULL, 'Le joueur Melia Antiqua a perdu un dé', '2025-05-26 17:18:09', '2025-06-05 16:29:18', 1, '2025-05-13 17:53:39'),
(2, 3, 1, NULL, 'Le joueur Flolink a perdu un dé', '2025-06-05 16:34:31', '2025-06-05 19:04:58', 1, '2025-05-15 16:10:38'),
(3, 4, 4, NULL, NULL, '2025-06-06 09:31:53', NULL, NULL, '2025-05-15 16:11:03'),
(4, 2, 1, NULL, 'Le joueur Luinil a perdu un dé', '2025-06-08 14:30:50', '2025-06-08 16:26:03', 5, '2025-05-15 16:11:13'),
(5, 4, 1, NULL, 'Le joueur Flolink a perdu un dé', '2025-06-06 00:32:17', '2025-06-06 01:42:55', 5, '2025-06-06 00:30:00'),
(6, 5, 2, NULL, NULL, NULL, NULL, NULL, '2025-06-07 12:45:26'),
(7, 2, 1, NULL, 'Le joueur Luinil a perdu un dé', '2025-06-07 12:48:04', '2025-06-07 12:51:33', 5, '2025-06-07 12:47:50');

-- --------------------------------------------------------

--
-- Structure de la table `lobby`
--

DROP TABLE IF EXISTS `lobby`;
CREATE TABLE IF NOT EXISTS `lobby` (
  `ID_Game` int NOT NULL,
  `ID_Player` int NOT NULL,
  `Ready` tinyint(1) NOT NULL,
  `Queued` int DEFAULT NULL,
  `Ranked` int DEFAULT NULL,
  `HP` int NOT NULL DEFAULT '5',
  `Dice1` int DEFAULT NULL,
  `Dice2` int DEFAULT NULL,
  `Dice3` int DEFAULT NULL,
  `Dice4` int DEFAULT NULL,
  `Dice5` int DEFAULT NULL,
  `dual_guess` int DEFAULT NULL,
  UNIQUE KEY `lobby_ibfk_1` (`ID_Player`,`ID_Game`) USING BTREE,
  KEY `lobby_ibfk_2` (`ID_Game`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `lobby`
--

INSERT INTO `lobby` (`ID_Game`, `ID_Player`, `Ready`, `Queued`, `Ranked`, `HP`, `Dice1`, `Dice2`, `Dice3`, `Dice4`, `Dice5`, `dual_guess`) VALUES
(1, 1, 1, 2, 1, 1, 6, NULL, NULL, NULL, NULL, 9),
(2, 1, 1, 1, 1, 1, 6, NULL, NULL, NULL, NULL, 2),
(3, 1, 1, 1, NULL, 5, 5, 1, 5, 4, 5, NULL),
(4, 1, 1, NULL, 2, 0, NULL, NULL, NULL, NULL, NULL, 5),
(5, 1, 1, NULL, 3, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 1, 1, 1, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 1, 1, NULL, 2, 0, NULL, NULL, NULL, NULL, NULL, 7),
(2, 2, 1, NULL, 2, 0, NULL, NULL, NULL, NULL, NULL, 7),
(3, 2, 1, 2, NULL, 5, 2, 1, 3, 3, 3, 1),
(5, 2, 1, NULL, 2, 0, NULL, NULL, NULL, NULL, NULL, 9),
(3, 4, 1, 3, NULL, 5, 1, 2, 6, 5, 4, NULL),
(5, 4, 1, NULL, 4, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(1, 5, 1, 0, 2, 0, NULL, NULL, NULL, NULL, NULL, 10),
(2, 5, 1, 1, 3, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 5, 1, 0, NULL, 5, 1, 1, 3, 5, 6, NULL),
(4, 5, 1, 1, 1, 1, 4, NULL, NULL, NULL, NULL, 7),
(5, 5, 1, 1, 1, 1, 2, NULL, NULL, NULL, NULL, 5),
(6, 5, 0, 1, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 5, 1, 1, 1, 1, 4, NULL, NULL, NULL, NULL, 6);

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `ID_Message` int NOT NULL AUTO_INCREMENT,
  `ID_Player` int NOT NULL,
  `ID_Game` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `DT_Creat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DT_Supp` datetime DEFAULT NULL,
  PRIMARY KEY (`ID_Message`),
  KEY `ID_Player` (`ID_Player`),
  KEY `ID_Game` (`ID_Game`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`ID_Message`, `ID_Player`, `ID_Game`, `content`, `DT_Creat`, `DT_Supp`) VALUES
(1, 1, 1, 'Bonjour !', '2025-05-13 19:09:24', NULL),
(2, 1, 1, 'Bonne chance !', '2025-05-13 19:09:24', NULL),
(3, 5, 1, 'Bonne chance à vous !', '2025-05-13 19:27:56', NULL),
(4, 5, 1, 'JE lis dans les esprits', '2025-05-13 19:27:56', NULL),
(5, 1, 1, 'TEST !', '2025-05-13 20:40:05', NULL),
(6, 1, 1, 'bijour :3', '2025-05-13 20:41:23', NULL),
(7, 5, 1, 'ça marche ! ', '2025-05-13 20:41:50', NULL),
(8, 5, 1, 'mouahahaha', '2025-05-13 20:41:56', NULL),
(9, 1, 1, 'bleh', '2025-05-13 20:43:37', NULL),
(10, 1, 1, 'youhou', '2025-05-13 20:43:53', NULL),
(12, 5, 1, 'bla bla bla bla bla bla bla bla bla bla bla blabla bla bla bla bla blabla bla bla bla bla blabla bla bla bla bla blabla bla bla bla bla blabla bla bla bla bla blabla bla bla bla bla blabla bla bla bla bla blabla bla bla bla bla blabla bla bla bla bla bla', '2025-05-13 20:46:11', NULL),
(13, 1, 1, 'ui', '2025-05-13 20:55:37', NULL),
(14, 5, 1, 'wesh', '2025-05-13 20:56:00', NULL),
(15, 1, 1, 'ces tests du chat en direct mettent en valeurs une grande maturité ainsi qu\'une riche imagination', '2025-05-26 17:22:13', NULL),
(16, 1, 1, 'test scroll', '2025-05-27 16:40:01', NULL),
(17, 5, 3, 'tutut', '2025-05-28 22:29:24', NULL),
(18, 4, 3, 'test chat à 4', '2025-06-06 09:29:29', NULL),
(19, 2, 3, 'pas de soucis à priori', '2025-06-06 09:29:42', NULL),
(20, 1, 3, 'bientôt terminé', '2025-06-06 09:30:03', NULL),
(21, 5, 3, 'super', '2025-06-06 09:30:38', NULL),
(22, 1, 6, 'coucou', '2025-06-07 12:46:11', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE IF NOT EXISTS `player` (
  `ID_player` int NOT NULL AUTO_INCREMENT,
  `Email` text COLLATE utf8mb4_general_ci NOT NULL,
  `Username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `Mdp` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Profil_pic` text COLLATE utf8mb4_general_ci NOT NULL,
  `Eliar` int NOT NULL DEFAULT '0',
  `DT_Creat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DT_Supp` datetime DEFAULT NULL,
  PRIMARY KEY (`ID_player`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `player`
--

INSERT INTO `player` (`ID_player`, `Email`, `Username`, `Mdp`, `Profil_pic`, `Eliar`, `DT_Creat`, `DT_Supp`) VALUES
(1, 'luinil@gmail.com', 'Luinil', '$2y$10$X2zEnO3Zz6tlC44vUXai2.Cruu3DMiqSONFulUmhpJcWB7krgxzYS', '..\\profil-pic\\thumb-1920-699220.png', 40, '2025-04-22 17:22:25', NULL),
(2, 'flolink@gmail.com', 'Flolink', '$2y$10$X2zEnO3Zz6tlC44vUXai2.Cruu3DMiqSONFulUmhpJcWB7krgxzYS', '..\\profil-pic\\4N68P3C.jpg', 14, '2025-04-22 17:23:42', NULL),
(3, 'laeknir@gmail.com', 'Laeknir', '$2y$10$X2zEnO3Zz6tlC44vUXai2.Cruu3DMiqSONFulUmhpJcWB7krgxzYS', '..\\profil-pic\\240058.jpg', 0, '2025-04-22 17:25:06', NULL),
(4, 'ciri@gmail.com', 'Ciri', '$2y$10$X2zEnO3Zz6tlC44vUXai2.Cruu3DMiqSONFulUmhpJcWB7krgxzYS', '..\\profil-pic\\22527.jpg', 0, '2025-04-22 17:26:26', NULL),
(5, 'melia@gmail.com', 'Melia Antiqua', '$2y$10$X2zEnO3Zz6tlC44vUXai2.Cruu3DMiqSONFulUmhpJcWB7krgxzYS', '..\\profil-pic\\2a47d4af4402d4f06a48c6d73d9c6a1f.jpg', 39, '2025-04-22 17:28:27', NULL),
(6, 'midna@gmail.com', 'Midna', '$2y$10$X2zEnO3Zz6tlC44vUXai2.Cruu3DMiqSONFulUmhpJcWB7krgxzYS', '..\\profil-pic\\twilight-princess-wolf-link-wallpaper_1820493.jpg', 0, '2025-04-22 17:29:53', NULL),
(7, 'mio@gmail.com', 'Mio', '$2y$10$g4Hjd4LTpmjPOwUroj516e8MVVcm9S4UDr4gN.lNcyvsBF2S0H6IG', '..\\profil-picprofile_684561d332ece8.82334659.jfif', 0, '2025-06-08 12:11:31', NULL);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `safe_user`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `safe_user`;
CREATE TABLE IF NOT EXISTS `safe_user` (
`DT_Creat` datetime
,`DT_Supp` datetime
,`Eliar` int
,`Email` text
,`ID_player` int
,`Mdp` varchar(250)
,`Profil_pic` text
,`Username` varchar(50)
);

-- --------------------------------------------------------

--
-- Structure de la vue `safe_user`
--
DROP TABLE IF EXISTS `safe_user`;

DROP VIEW IF EXISTS `safe_user`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `safe_user`  AS SELECT `player`.`ID_player` AS `ID_player`, `player`.`Email` AS `Email`, `player`.`Username` AS `Username`, `player`.`Mdp` AS `Mdp`, `player`.`Profil_pic` AS `Profil_pic`, `player`.`Eliar` AS `Eliar`, `player`.`DT_Creat` AS `DT_Creat`, `player`.`DT_Supp` AS `DT_Supp` FROM `player` ;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `game`
--
ALTER TABLE `game`
  ADD CONSTRAINT `game_ibfk_1` FOREIGN KEY (`Winner`) REFERENCES `player` (`ID_player`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Contraintes pour la table `lobby`
--
ALTER TABLE `lobby`
  ADD CONSTRAINT `lobby_ibfk_1` FOREIGN KEY (`ID_Player`) REFERENCES `player` (`ID_player`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `lobby_ibfk_2` FOREIGN KEY (`ID_Game`) REFERENCES `game` (`ID_game`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`ID_Player`) REFERENCES `player` (`ID_player`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`ID_Game`) REFERENCES `game` (`ID_game`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
