-- create the log table
CREATE TABLE IF NOT EXISTS sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    operation ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    record_id INT NOT NULL,
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    synced BOOLEAN DEFAULT FALSE,
    INDEX sym_pending (synced)
);

-- enseignants triggers
DELIMITER $$

CREATE TRIGGER enseignants_after_insert
AFTER INSERT ON enseignants
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        'enseignants',
        'INSERT',
        NEW.ID_ENS,
        JSON_OBJECT(
            'ID_ENS', NEW.ID_ENS,
            'nom', NEW.nom,
            'prenom', NEW.prenom,
            'email', NEW.email
        )
    );
END$$

CREATE TRIGGER enseignants_after_update
AFTER UPDATE ON enseignants
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        'enseignants',
        'UPDATE',
        NEW.ID_ENS,
        JSON_OBJECT(
            'ID_ENS', NEW.ID_ENS,
            'nom', NEW.nom,
            'prenom', NEW.prenom,
            'email', NEW.email
        )
    );
END$$

CREATE TRIGGER enseignants_after_delete
AFTER DELETE ON enseignants
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        'enseignants',
        'DELETE',
        OLD.ID_ENS,
        NULL
    );
END$$

DELIMITER ;

-- etudiants triggers (clé primaire ID_ETUDIANTS)
DELIMITER $$

CREATE TRIGGER etudiants_after_insert
AFTER INSERT ON etudiants
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        'etudiants',
        'INSERT',
        NEW.ID_ETUDIANTS,
        JSON_OBJECT(
            'ID_ETUDIANTS', NEW.ID_ETUDIANTS,
            'NUM_CARTE', NEW.NUM_CARTE,
            'nom', NEW.nom,
            'prenom', NEW.prenom,
            'email', NEW.email
        )
    );
END$$

CREATE TRIGGER etudiants_after_update
AFTER UPDATE ON etudiants
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        'etudiants',
        'UPDATE',
        NEW.ID_ETUDIANTS,
        JSON_OBJECT(
            'ID_ETUDIANTS', NEW.ID_ETUDIANTS,
            'NUM_CARTE', NEW.NUM_CARTE,
            'nom', NEW.nom,
            'prenom', NEW.prenom,
            'email', NEW.email
        )
    );
END$$

CREATE TRIGGER etudiants_after_delete
AFTER DELETE ON etudiants
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        'etudiants',
        'DELETE',
        OLD.ID_ETUDIANTS,
        NULL
    );
END$$

DELIMITER ;

-- cours triggers (clé primaire ID_COURS)
DELIMITER $$

CREATE TRIGGER cours_after_insert
AFTER INSERT ON cours
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        'cours',
        'INSERT',
        NEW.ID_COURS,
        JSON_OBJECT(
            'ID_COURS', NEW.ID_COURS,
            'CODE_COURS', NEW.CODE_COURS,
            'INTITULE', NEW.INTITULE,
            'NBRE_CREDITS', NEW.NBRE_CREDITS,
            'SEMESTRE', NEW.SEMESTRE,
            'NIVEAU', NEW.NIVEAU,
            'DEPARTEMENT', NEW.DEPARTEMENT
        )
    );
END$$

CREATE TRIGGER cours_after_update
AFTER UPDATE ON cours
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        'cours',
        'UPDATE',
        NEW.ID_COURS,
        JSON_OBJECT(
            'ID_COURS', NEW.ID_COURS,
            'CODE_COURS', NEW.CODE_COURS,
            'INTITULE', NEW.INTITULE,
            'NBRE_CREDITS', NEW.NBRE_CREDITS,
            'SEMESTRE', NEW.SEMESTRE,
            'NIVEAU', NEW.NIVEAU,
            'DEPARTEMENT', NEW.DEPARTEMENT
        )
    );
END$$

CREATE TRIGGER cours_after_delete
AFTER DELETE ON cours
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        'cours',
        'DELETE',
        OLD.ID_COURS,
        NULL
    );
END$$

DELIMITER ;

-- s_inscrire triggers (clé composite)
DELIMITER $$

CREATE TRIGGER s_inscrire_after_insert
AFTER INSERT ON s_inscrire
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        's_inscrire',
        'INSERT',
        NEW.ID_INSCRIPTION,
        JSON_OBJECT(
            'ID_INSCRIPTION', NEW.ID_INSCRIPTION,
            'ID_ETUDIANTS', NEW.ID_ETUDIANTS,
            'NUM_CARTE', NEW.NUM_CARTE,
            'ID_COURS', NEW.ID_COURS,
            'CODE_COURS', NEW.CODE_COURS,
            'date_inscription', NEW.date_inscription
        )
    );
END$$

CREATE TRIGGER s_inscrire_after_update
AFTER UPDATE ON s_inscrire
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        's_inscrire',
        'UPDATE',
        NEW.ID_INSCRIPTION,
        JSON_OBJECT(
            'ID_INSCRIPTION', NEW.ID_INSCRIPTION,
            'ID_ETUDIANTS', NEW.ID_ETUDIANTS,
            'NUM_CARTE', NEW.NUM_CARTE,
            'ID_COURS', NEW.ID_COURS,
            'CODE_COURS', NEW.CODE_COURS,
            'date_inscription', NEW.date_inscription
        )
    );
END$$

CREATE TRIGGER s_inscrire_after_delete
AFTER DELETE ON s_inscrire
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (table_name, operation, record_id, data)
    VALUES (
        's_inscrire',
        'DELETE',
        OLD.ID_INSCRIPTION,
        NULL
    );
END$$

DELIMITER ;

-- triggers présents ?
SHOW TRIGGERS LIKE 'enseignants';
-- logs en attente ?
SELECT COUNT(*) AS pending FROM sync_log WHERE synced = FALSE;
SELECT * FROM sync_log ORDER BY id DESC LIMIT 5;
