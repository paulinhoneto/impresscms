<?php
namespace ImpressCMS\Core\Database\Mysql;

use ImpressCMS\Core\Database\DatabaseUtilityInterface;

/**
 * Redefined by Claudia A. V. Callegari
 * Participation Rodrigo Lima
 * Participation Paulinho Neto 
 * ImpressCMS.org
 *
 * @copyright	The ImpressCMS Project - http://www.impresscms.org/
 * @package	ICMS\Database\MySQL
 */
abstract class DatabaseUtility implements DatabaseUtilityInterface {

    /**
     * Add a prefix.'_' to all tablenames in a query
     *
     * @param string $query  valid SQL query string
     * @param string $prefix prefix to add to all table names
     * @return mixed FALSE on failure
     */
    public static function prefixQuery($query, $prefix) {
        $pattern = "/^(INSERT INTO|CREATE TABLE|ALTER TABLE|UPDATE)(\s)+([`]?)([^`\s]+)\\3(\s)+/siU";
        $pattern2 = "/^(DROP TABLE)(\s)+([`]?)([^`\s]+)\\3(\s)?$/siU";

        // Validate $query and $prefix to prevent SQL injection attacks
        if (!self::checkSQL($query) || !self::checkSQL($prefix)) {
            return false;
        }

        if (preg_match($pattern, $query, $matches)) {
            $replace = "\\1 " . $prefix . "_\\4\\5";
            $matches[0] = preg_replace($pattern, $replace, $query);
            $query = $matches[0];
            if (preg_match('/REFERENCES/', $query) || preg_match('/DROP FOREIGN KEY/', $query)) {
                $matches_1 = $matches; // claudia
                // $pattern = "/(REFERENCES)(\s)+([`]?)([^`\s]+)\\3(\s)+/siU";
                // alterado abaixo 03/10/2011, sendo que funcionou para ADD CONSTRAINT e não para DROP FOREIGN KEY
                $pattern = "/(REFERENCES|DROP FOREIGN KEY|ADD CONSTRAINT)(\s)+([`]?)([^`\s]+)\\3(\s)+/siU";
                if (preg_match($pattern, $query, $matches)) {
                    $matches[0] = preg_replace($pattern, $replace, $query);
                    $matches_1[0] = $matches[0]; // claudia
                    $matches = $matches_1;
    /**
     * Removes comment and splits large sql files into individual queries
     * Function from phpMyAdmin (http://phpwizard.net/projects/phpMyAdmin/)
     *
     * Last revision: September 23, 2001 - gandon
     *
     * @param array $ret   the split sql commands
     * @param string $sql the sql commands
     * @return bool always true
     */
    public static function splitSqlFile(&$ret, $sql) {
        $sql = trim($sql);
        $sql_len = strlen($sql);
        $char = '';
        $string_start = '';
        $in_string = false;
        $time0 = time();

        for ($i = 0; $i < $sql_len; ++$i) {
            $char = $sql[$i];

            // We are in a string, check for not escaped end of strings except for
            // backquotes that can't be escaped
            if ($in_string) {
                for (;;) {
                    $i = strpos($sql, $string_start, $i);
                    // No end of string found -> add the current substring to the
                    // returned array
                    if (!$i) {
                        $ret[] = $sql;
                        return true;
                    }
                    // Backquotes or no backslashes before quotes: it's indeed the
                    // end of the string -> exit the loop
                    else if ($string_start == '`' || $sql[$i - 1] != '\\') {
                        $string_start = '';
                        $in_string = false;
                        break;
                    }
                    // one or more Backslashes before the presumed end of string...
                    else {
                        // ... first checks for escaped backslashes
                        $j = 2;
                        $escaped_backslash = false;
                        while ($i - $j > 0 && $sql[$i - $j] == '\\') {
                            $escaped_backslash = !$escaped_backslash;
    /**
     * Determine if the SQL string is safe
     *
     * @param string $sql
     * @return bool TRUE if the string is safe
     */
    public static function checkSQL($sql) {
        // Use mysql_real_escape_string to escape special characters
        $safe_sql = mysql_real_escape_string($sql);

        // Check if $safe_sql is equal to $sql. If not, it means that
        // mysql_real_escape_string found and escaped special characters, so $sql
        // is not safe.
        if ($safe_sql != $sql) {
            return false;
        }

        // $sql is safe
        return true;
    }
}
