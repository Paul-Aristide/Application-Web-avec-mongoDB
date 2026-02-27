<?php
/**
 * Helpers MySQL (PDO) + MongoDB - Base universite
 * Expose _id dans les lignes pour compatibilité frontend (clé primaire ou composite).
 */
function _tableIdConfig($table) {
    $map = [
        'enseignants' => ['pk' => ['ID_ENS'], 'sep' => null],
        'etudiants'   => ['pk' => ['ID_ETUDIANTS', 'NUM_CARTE'], 'sep' => '|'],
        'cours'       => ['pk' => ['ID_COURS', 'CODE_COURS'], 'sep' => '|'],
        's_inscrire'  => ['pk' => ['ID_ETUDIANTS', 'NUM_CARTE', 'ID_COURS', 'CODE_COURS'], 'sep' => '|'],
    ];
    return $map[$table] ?? null;
}

/** Retourne vrai si la configuration Mongo est disponible et utilisable. */
function _isMongo() {
    global $config;
    return !empty($config['use_mongo']) && isset($config['mongo']);
}

/** Récupère la collection Mongo correspondante à la table. */
function _mongoColl($table) {
    global $config;
    return $config['mongo']->{$table};
}

/** Normalise les types MongoDB avant insertion. */
function _normalizeMongoDoc($mongoDoc) {
    // Convertir les IDs strings en int
    $idFields = ['ID_ENS', 'ID_ETUDIANTS', 'ID_COURS', 'CODE_COURS', 'ID_INSCRIPTION', 'NBRE_CREDITS', 'SEMESTRE', 'ANNEE_ENTREE'];
    foreach ($idFields as $idField) {
        if (isset($mongoDoc[$idField]) && is_numeric($mongoDoc[$idField])) {
            $mongoDoc[$idField] = (int) $mongoDoc[$idField];
        }
    }
    return $mongoDoc;
}

function _rowToId($table, $row) {
    // si le document vient de Mongo il contient peut-être un champ _id de type
    // ObjectId. On l'ignore car l'interface frontend se base sur les colonnes
    // primaires (même que celles de MySQL) pour identifier la ligne.
    if (isset($row['_id']) && _isMongo()) {
        unset($row['_id']);
    }

    $cfg = _tableIdConfig($table);
    if (!$cfg) return $row;
    $pk = $cfg['pk'];
    $sep = $cfg['sep'];
    if ($sep === null) {
        $row['_id'] = isset($row[$pk[0]]) ? (string) $row[$pk[0]] : '';
    } else {
        $parts = [];
        foreach ($pk as $col) {
            $parts[] = $row[$col] ?? '';
        }
        $row['_id'] = implode($sep, $parts);
    }
    return $row;
}

/** Convertit _id (ou id) en clause WHERE pour la table. */
function _idToWhere($table, $id) {
    $cfg = _tableIdConfig($table);
    if (!$cfg) return [];
    $pk = $cfg['pk'];
    $sep = $cfg['sep'];
    if ($sep === null) {
        return [$pk[0] => $id];
    }
    $parts = is_string($id) ? explode($sep, $id, count($pk)) : [];
    $where = [];
    foreach ($pk as $i => $col) {
        $val = $parts[$i] ?? null;
        if ($col === 'ID_ETUDIANTS' || $col === 'ID_COURS' || $col === 'CODE_COURS' || $col === 'ID_ENS') {
            $val = (int) $val;
        }
        $where[$col] = $val;
    }
    return $where;
}

function _buildWhere($where) {
    $sql = [];
    $params = [];
    foreach ($where as $k => $v) {
        if ($k === '_id' || $k === 'id') continue;
        $sql[] = "`" . str_replace('`', '``', $k) . "` = ?";
        $params[] = $v;
    }
    return [implode(' AND ', $sql), $params];
}

function dbFind($table, $where = [], $orderBy = null, $limit = null) {
    // si Mongo est disponible, on privilégie la lecture depuis Mongo,
    // sinon on exécute la requête SQL classique.
    if (_isMongo()) {
        $coll = _mongoColl($table);
        $filter = [];
        if (!empty($where)) {
            if (isset($where['_id'])) {
                $filter = _idToWhere($table, $where['_id']);
            } else {
                $filter = $where;
            }
        }
        $options = [];
        if ($orderBy) {
            $sort = [];
            foreach (explode(',', $orderBy) as $part) {
                $part = trim($part);
                if ($part === '') continue;
                $dir = 1;
                if (preg_match('/\bDESC$/i', $part)) {
                    $dir = -1;
                }
                $col = preg_replace('/\s+(ASC|DESC)$/i','',$part);
                $sort[$col] = $dir;
            }
            $options['sort'] = $sort;
        }
        if ($limit !== null) {
            $options['limit'] = (int) $limit;
        }
        $rows = [];
        $cursor = $coll->find($filter, $options);
        foreach ($cursor as $doc) {
            $row = json_decode(json_encode($doc), true);
            $rows[] = _rowToId($table, $row);
        }
        return $rows;
    }

    $pdo = getPdo();
    $sql = "SELECT * FROM `" . str_replace('`', '``', $table) . "`";
    $params = [];
    $whereSql = '';
    if (!empty($where)) {
        if (isset($where['_id'])) {
            $where = _idToWhere($table, $where['_id']);
        }
        list($whereSql, $params) = _buildWhere($where);
    }
    if ($whereSql !== '') {
        $sql .= " WHERE " . $whereSql;
    }
    if ($orderBy) {
        $sql .= " ORDER BY " . $orderBy;
    }
    if ($limit !== null) {
        $sql .= " LIMIT " . (int) $limit;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = _rowToId($table, $row);
    }
    return $rows;
}

function dbFindOne($table, $where) {
    if (!empty($where) && isset($where['_id'])) {
        $where = _idToWhere($table, $where['_id']);
    }
    $rows = dbFind($table, $where, null, 1);
    return $rows[0] ?? null;
}

function dbInsert($table, $data) {
    $pdo = getPdo();
    $cols = [];
    $placeholders = [];
    $params = [];
    foreach ($data as $k => $v) {
        $cols[] = "`" . str_replace('`', '``', $k) . "`";
        $placeholders[] = '?';
        $params[] = $v;
    }
    $sql = "INSERT INTO `" . str_replace('`', '``', $table) . "` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $pdo->prepare($sql)->execute($params);
    $cfg = _tableIdConfig($table);
    if (!$cfg) {
        $id = (string) $pdo->lastInsertId();
    } else {
        if ($cfg['sep'] === null) {
            $id = (string) ($data[$cfg['pk'][0]] ?? $pdo->lastInsertId());
            // make sure the inserted data also contains the auto-increment value
            if (!isset($data[$cfg['pk'][0]])) {
                $data[$cfg['pk'][0]] = $id;
            }
        } else {
            $parts = [];
            foreach ($cfg['pk'] as $col) {
                $parts[] = $data[$col] ?? '';
            }
            $id = implode($cfg['sep'], $parts);
        }
    }

    // si Mongo est disponible, on réplique immédiatement l'insertion
    if (_isMongo()) {
        try {
            $mongoDoc = _normalizeMongoDoc($data);
            // certains documents MySQL ne contiennent pas l'id après l'insertion,
            // on s'assure qu'il est présent pour les recherches futures.
            if ($cfg && $cfg['sep'] === null && !isset($mongoDoc[$cfg['pk'][0]])) {
                $mongoDoc[$cfg['pk'][0]] = (int) $id;
            }
            _mongoColl($table)->insertOne($mongoDoc);
        } catch (Exception $e) {
            // ignorer les erreurs (doublon, etc.) pour ne pas casser l'API
            error_log('dbInsert Mongo error (' . $table . '): ' . $e->getMessage());
        }
    }

    return $id;
}

function dbUpdate($table, $id, $data) {
    $pdo = getPdo();
    $where = _idToWhere($table, $id);
    unset($data['_id']);
    $set = [];
    $params = [];
    foreach ($data as $k => $v) {
        $set[] = "`" . str_replace('`', '``', $k) . "` = ?";
        $params[] = $v;
    }
    list($whereSql, $whereParams) = _buildWhere($where);
    $params = array_merge($params, $whereParams);
    $sql = "UPDATE `" . str_replace('`', '``', $table) . "` SET " . implode(', ', $set) . " WHERE " . $whereSql;
    $pdo->prepare($sql)->execute($params);

    // répliquer vers Mongo si nécessaire
    if (_isMongo()) {
        try {
            $normalizedData = _normalizeMongoDoc($data);
            _mongoColl($table)->updateOne($where, ['$set' => $normalizedData]);
        } catch (Exception $e) {
            // on ignore les erreurs pour ne pas casser l'API
            error_log('dbUpdate Mongo error (' . $table . '): ' . $e->getMessage());
        }
    }

    return true;
}

function dbDelete($table, $id) {
    $pdo = getPdo();
    $where = _idToWhere($table, $id);
    list($whereSql, $params) = _buildWhere($where);
    $sql = "DELETE FROM `" . str_replace('`', '``', $table) . "` WHERE " . $whereSql;
    $pdo->prepare($sql)->execute($params);

    if (_isMongo()) {
        try {
            _mongoColl($table)->deleteOne($where);
        } catch (Exception $e) {
            // ignore
        }
    }

    return true;
}

/** Requête SQL brute (pour stats). */
function dbQuery($sql, $params = []) {
    $pdo = getPdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
