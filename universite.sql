-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 26 fév. 2026 à 00:56
-- Version du serveur : 8.3.0
-- Version de PHP : 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `universite`
--

-- --------------------------------------------------------

--
-- Structure de la table `cours`
--

DROP TABLE IF EXISTS `cours`;
CREATE TABLE IF NOT EXISTS `cours` (
  `ID_COURS` int NOT NULL,
  `CODE_COURS` int NOT NULL,
  `ID_ENS` int NOT NULL,
  `INTITULE` varchar(100) NOT NULL,
  `DESCRIPTION_` longtext,
  `NBRE_CREDITS` float NOT NULL,
  `SEMESTRE` int NOT NULL,
  `NIVEAU` varchar(10) NOT NULL,
  `DEPARTEMENT` varchar(100) NOT NULL,
  `PREREQUIS` longtext,
  PRIMARY KEY (`ID_COURS`,`CODE_COURS`),
  KEY `DONNER_FK` (`ID_ENS`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `enseignants`
--

DROP TABLE IF EXISTS `enseignants`;
CREATE TABLE IF NOT EXISTS `enseignants` (
  `ID_ENS` int NOT NULL,
  `NUM_ENS` char(10) NOT NULL,
  `NOM` varchar(100) NOT NULL,
  `PRENOM` varchar(200) NOT NULL,
  `EMAIL` varchar(50) NOT NULL,
  `DEPARTEMENT` varchar(100) DEFAULT NULL,
  `GRADE` char(5) DEFAULT NULL,
  `SPECIALITE` varchar(50) NOT NULL,
  PRIMARY KEY (`ID_ENS`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `etudiants`
--

DROP TABLE IF EXISTS `etudiants`;
CREATE TABLE IF NOT EXISTS `etudiants` (
  `ID_ETUDIANTS` smallint NOT NULL,
  `NUM_CARTE` varchar(20) NOT NULL,
  `NOM` varchar(100) DEFAULT NULL,
  `PRENOM` varchar(200) DEFAULT NULL,
  `EMAIL` varchar(50) DEFAULT NULL,
  `TELEPHONE` varchar(20) DEFAULT NULL,
  `FILIERE` varchar(500) DEFAULT NULL,
  `ANNEE_ENTREE` int DEFAULT NULL,
  `DATE_NAISSANCE` date DEFAULT NULL,
  PRIMARY KEY (`ID_ETUDIANTS`,`NUM_CARTE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `s_inscrire`
--

DROP TABLE IF EXISTS `s_inscrire`;
CREATE TABLE IF NOT EXISTS `s_inscrire` (
  `ID_ETUDIANTS` smallint NOT NULL,
  `NUM_CARTE` varchar(20) NOT NULL,
  `ID_COURS` int NOT NULL,
  `CODE_COURS` int NOT NULL,
  PRIMARY KEY (`ID_ETUDIANTS`,`NUM_CARTE`,`ID_COURS`,`CODE_COURS`),
  KEY `S_INSCRIRE2_FK` (`ID_ETUDIANTS`,`NUM_CARTE`),
  KEY `S_INSCRIRE_FK` (`ID_COURS`,`CODE_COURS`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `cours`
--
ALTER TABLE `cours`
  ADD CONSTRAINT `FK_COURS_DONNER_ENSEIGNA` FOREIGN KEY (`ID_ENS`) REFERENCES `enseignants` (`ID_ENS`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Contraintes pour la table `s_inscrire`
--
ALTER TABLE `s_inscrire`
  ADD CONSTRAINT `FK_S_INSCRI_S_INSCRIR_COURS` FOREIGN KEY (`ID_COURS`,`CODE_COURS`) REFERENCES `cours` (`ID_COURS`, `CODE_COURS`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_S_INSCRI_S_INSCRIR_ETUDIANT` FOREIGN KEY (`ID_ETUDIANTS`,`NUM_CARTE`) REFERENCES `etudiants` (`ID_ETUDIANTS`, `NUM_CARTE`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
