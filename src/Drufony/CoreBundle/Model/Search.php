<?php
/**
 * Implements the main class to search contents from the database, based on given strings.
 */

namespace Drufony\CoreBundle\Model;

// Class constants
// Defines constants if undefined. Those constants could be defined in
// a setting file.
defined('TABLES_TO_SEARCH') or define('TABLES_TO_SEARCH', 'page section item product');

/**
 * Provides needed methods to make queries to the database. This class allows to search
 * contents based on given strings.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Search
{
    /**
     * Retrieves the content containing the words giving in the string
     *
     * @param string $string: string to find in the content body and title
     * @return array with the title, body and type of all content found. Empty if nothing is found
     */
    public static function query($string) {

        #Where condition
        $sqlCondition = " (title LIKE ? OR body LIKE ?) ";
        $bindValues = array();
        $sqlWhereConditions = array();

        //Get the where conditions for every word in the string
        foreach(explode(' ', $string) as $termToFind) {
            $sqlWhereConditions[] = $sqlCondition;

            //Get the binds values for the query
            array_push($bindValues, "%${termToFind}%", "%${termToFind}%");
        }

        $where = implode('AND', $sqlWhereConditions);
        $result = array();

        //Makes a query for every single table
        foreach(explode(' ', TABLES_TO_SEARCH) as $tableName) {
            $sql = "SELECT id, nid, title, body, \"${tableName}\" as \"type\" ";
            $sql .= "FROM ${tableName} ";
            $sql .= "WHERE ${where} ";

            $queryResult = db_executeQuery($sql, $bindValues);
            $queryFetched = $queryResult->fetchAll();

            $result = array_merge($result, $queryFetched);
            //$result[$tableName] = $queryFetched;
        }

        return $result;
    }

    /**
     * Retrieves the users containing the words giving in the string
     *
     * @param string $string: string to find in the username or email
     * @return array users that match
     */
    public static function getUsers($string) {
        $whereCondition = '(username LIKE ? OR email LIKE ?)';
        $whereArray = array();
        $bindValues = array();

        //Get the where conditions for every word in the string
        foreach(explode(' ', $string) as $termToFind) {
            $whereArray[] = $whereCondition;

            //Get the binds values for the query
            array_push($bindValues, "%${termToFind}%", "%${termToFind}%");
        }

        $where = implode('AND', $whereArray);

        $sql  = "SELECT uid, username, email ";
        $sql .= "FROM users ";
        $sql .= "WHERE ${where}";

        $queryResult = db_executeQuery($sql, $bindValues);
        $result = $queryResult->fetchAll();

        return $result;
    }
}
