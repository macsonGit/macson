<?php
/**
 * Make DB query functions globally available.
 */

function db_insert($table, $data) {
    global $db;
    $ret=FALSE;
    if (is_object($db)) {
        if ($ret= $db->insert($table, $data)) {
            $ret=$db->lastInsertId();
        }
    }

    return $ret;
}

function db_executeQuery($sql, $args = array(), $page = 0, $itemsPerPage = ITEMS_PER_PAGE, $debug = false) {
    global $db;

    if ($page > 0) {
        $sql = "${sql} LIMIT " . ($page - 1) * $itemsPerPage . ", " . $itemsPerPage;
    }

    if ($debug) {
        db_printQuery($sql, $args);
    }



    return $db->executeQuery($sql, $args);
}

function db_executeUpdate($sql, $args = array(), $debug = false) {
    global $db;

    if ($debug) {
        db_printQuery($sql, $args);
    }

    return $db->executeUpdate($sql, $args);
}

function db_fetchAssoc($sql, $args = array(), $debug = false) {
    global $db;

    if ($debug) {
        db_printQuery($sql, $args);
    }

    $ret=FALSE;
    if (is_object($db)) {
        $ret= $db->fetchAssoc($sql, $args);
    }

    return $ret;
}

function db_fetchAllColumn($sql, $args = array(), $debug = false) {
    if ($debug) {
        db_printQuery($sql, $args);
    }

    if ($query = db_executeQuery($sql, $args)) {
        $results = $query->fetchAll(\PDO::FETCH_COLUMN);
    }

    return $results;
}

function db_fetchColumn($sql, $args = array(), $debug = false) {
    global $db;

    if ($debug) {
        db_printQuery($sql, $args);
    }

    return $db->fetchColumn($sql, $args);
}

function db_fetchAll($sql, $args = array(), $debug = false) {
    global $db;

    if ($debug) {
        db_printQuery($sql, $args);
    }

    return $db->fetchAll($sql, $args);
}

function db_update($table, $fields = array(), $values = array()) {
    global $db;

    return $db->update($table, $fields, $values);
}

function db_lastInsertId() {
    global $db;

    return $db->lastInsertId();
}

function db_delete($table, $fields) {
    global $db;

    return $db->delete($table, $fields);
}

function db_prepare($sql) {
    global $db;

    return $db->prepare($sql);
}

function db_printQuery($sql, $args) {
    if (strpos($sql, ':') === false) {
        foreach(explode('?', $sql) as $partIndex => $partData) {
            $sqlToDebug = (isset($sqlToDebug) ? $sqlToDebug : null) . $partData;
            if (isset($args[$partIndex])) {
                $sqlToDebug .= "'$args[$partIndex]'";
            }
        }

        ld($sqlToDebug);
    }
    else {
        ld($sql);
        ld($args);
    }
}

