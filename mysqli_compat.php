<?php
/**
 * mysqli_stmt::get_result() compatibility layer.
 *
 * Why this file exists
 * --------------------
 * `mysqli_stmt::get_result()` is only available when PHP is compiled with the
 * mysqlnd driver. Many cPanel accounts run a non-mysqlnd PHP build, and there
 * the method simply does not exist, so a call like
 *
 *     $stmt->execute();
 *     $row = app_stmt_result($stmt)->fetch_assoc();
 *
 * dies with "Call to undefined method mysqli_stmt::get_result()".
 *
 * The lottery engine, the wallet and several webapi endpoints all read rows
 * that way. On a non-mysqlnd build that single missing method breaks betting,
 * history and the admin pages even when the database itself is healthy.
 *
 * What it does
 * ------------
 * `app_stmt_result($stmt)` returns the exact same thing `app_stmt_result($stmt)`
 * would return when the driver supports it, and otherwise builds an equivalent
 * buffered result object from the statement metadata (`bind_result` + `fetch`).
 *
 * The returned object supports the mysqli_result API that this project uses:
 * fetch_assoc(), fetch_array(), fetch_row(), fetch_object(), fetch_all(),
 * num_rows, field_count, fetch_fields(), data_seek(), free(), close().
 *
 * This file is required automatically by every conn.php, so application code
 * only has to call app_stmt_result($stmt) instead of app_stmt_result($stmt).
 */

/*
 * PHP 8 string helpers used by this codebase.
 *
 * A few endpoints (GetUserInfo, CreateThirdRechargeOrderV3, ReceiveDailyAward,
 * the bundled PhpSpreadsheet library) call str_contains()/str_starts_with().
 * Those functions only exist on PHP 8, so on the older PHP interpreters that
 * some cPanel accounts still default to they abort the request. Defining them
 * keeps the same code working on PHP 7.4 as well.
 */
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return $needle === '' || strpos((string) $haystack, (string) $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        if ($needle === '') {
            return true;
        }
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!class_exists('AppMysqliResult', false)) {
    /**
     * Buffered, array backed stand-in for mysqli_result.
     */
    class AppMysqliResult
    {
        /** @var array<int,array<string,mixed>> */
        private $rows = array();
        /** @var int */
        private $pointer = 0;
        /** @var array<int,object> */
        private $fields = array();

        /** @var int Number of rows in the buffered result (mysqli_result::$num_rows). */
        public $num_rows = 0;
        /** @var int Number of selected columns (mysqli_result::$field_count). */
        public $field_count = 0;
        /** @var int Always 0: the whole result set is already buffered. */
        public $current_field = 0;
        /** @var int|array Column lengths are not needed by the project. */
        public $lengths = array();
        /** @var string */
        public $type = 'AppMysqliResult';

        /**
         * @param array<int,array<string,mixed>> $rows
         * @param array<int,object>              $fields
         */
        public function __construct(array $rows, array $fields = array())
        {
            $this->rows = array_values($rows);
            $this->num_rows = count($this->rows);
            $this->fields = array_values($fields);
            $this->field_count = count($this->fields);
        }

        /** @return array<string,mixed>|null */
        public function fetch_assoc()
        {
            if ($this->pointer >= $this->num_rows) {
                return null;
            }
            return $this->rows[$this->pointer++];
        }

        /**
         * @param int $mode MYSQLI_ASSOC, MYSQLI_NUM or MYSQLI_BOTH (default).
         * @return array<int|string,mixed>|null
         */
        public function fetch_array($mode = MYSQLI_BOTH)
        {
            $row = $this->fetch_assoc();
            if ($row === null) {
                return null;
            }
            if ($mode === MYSQLI_ASSOC) {
                return $row;
            }
            if ($mode === MYSQLI_NUM) {
                return array_values($row);
            }
            $both = array();
            $index = 0;
            foreach ($row as $key => $value) {
                $both[$index++] = $value;
                if (!is_int($key)) {
                    $both[$key] = $value;
                }
            }
            return $both;
        }

        /** @return array<int,mixed>|null */
        public function fetch_row()
        {
            $row = $this->fetch_assoc();
            return $row === null ? null : array_values($row);
        }

        /**
         * @param string $className
         * @param array  $constructorArgs
         * @return object|null
         */
        public function fetch_object($className = 'stdClass', $constructorArgs = array())
        {
            $row = $this->fetch_assoc();
            if ($row === null) {
                return null;
            }
            if ($className === 'stdClass' || $className === '') {
                return (object) $row;
            }
            $object = new $className();
            foreach ($row as $key => $value) {
                $object->$key = $value;
            }
            return $object;
        }

        /**
         * @param int $mode MYSQLI_NUM (mysqli default), MYSQLI_ASSOC or MYSQLI_BOTH.
         * @return array<int,mixed>
         */
        public function fetch_all($mode = MYSQLI_NUM)
        {
            $all = array();
            while (($row = $this->fetch_array($mode)) !== null) {
                $all[] = $row;
            }
            return $all;
        }

        /** @return object|false */
        public function fetch_field()
        {
            if (!isset($this->fields[$this->current_field])) {
                return false;
            }
            return $this->fields[$this->current_field];
        }

        /** @return array<int,object> */
        public function fetch_fields()
        {
            return $this->fields;
        }

        /** @return bool */
        public function data_seek($offset)
        {
            $offset = max(0, (int) $offset);
            if ($offset > $this->num_rows) {
                return false;
            }
            $this->pointer = $offset;
            return true;
        }

        /** @return bool */
        public function free()
        {
            $this->rows = array();
            $this->num_rows = 0;
            $this->pointer = 0;
            return true;
        }

        /** @return bool Alias of free(), keeps mysqli_result call sites working. */
        public function close()
        {
            return $this->free();
        }

        /** @return void */
        public function __destruct()
        {
            $this->rows = array();
        }
    }
}

/*
 * mysqlnd-only procedural API polyfills.
 *
 * `mysqli_stmt_get_result()` and `mysqli_fetch_all()` are documented as
 * "available only with mysqlnd". Legacy code in this project calls both
 * directly (the admin panel helper DB_select() for example), so they are
 * defined here when the running PHP build does not provide them.
 */
if (!function_exists('mysqli_stmt_get_result')) {
    /**
     * @param mysqli_stmt $stmt
     * @return AppMysqliResult|false
     */
    function mysqli_stmt_get_result($stmt)
    {
        return app_stmt_bind_result_rows($stmt);
    }
}

if (!function_exists('mysqli_fetch_all')) {
    /**
     * @param mysqli_result|AppMysqliResult $result
     * @param int                           $mode
     * @return array<int,mixed>
     */
    function mysqli_fetch_all($result, $mode = MYSQLI_NUM)
    {
        if ($result instanceof AppMysqliResult) {
            return $result->fetch_all($mode);
        }
        if ($result instanceof mysqli_result) {
            $rows = array();
            while (($row = $result->fetch_array($mode)) !== null) {
                $rows[] = $row;
            }
            return $rows;
        }
        return array();
    }
}

if (!function_exists('app_stmt_bind_result_rows')) {
    /**
     * Reads every row of an executed statement without mysqlnd.
     *
     * @param mysqli_stmt $stmt
     * @return AppMysqliResult|false
     */
    function app_stmt_bind_result_rows($stmt)
    {
        if (!($stmt instanceof mysqli_stmt)) {
            return false;
        }

        $metadata = @$stmt->result_metadata();
        if (!is_object($metadata)) {
            // Statement has no result set (INSERT/UPDATE/DELETE) or it failed.
            return false;
        }

        $fields = method_exists($metadata, 'fetch_fields') ? $metadata->fetch_fields() : array();
        if (!is_array($fields) || $fields === array()) {
            return false;
        }

        $row = array();
        $bind = array();
        foreach ($fields as $field) {
            $name = isset($field->name) ? (string) $field->name : '';
            if ($name === '') {
                continue;
            }
            $row[$name] = null;
            $bind[] = &$row[$name];
        }
        if ($bind === array()) {
            return false;
        }

        if (!@call_user_func_array(array($stmt, 'bind_result'), $bind)) {
            return false;
        }

        $rows = array();
        $guard = 0;
        // Standard non-mysqlnd idiom: fetch() is truthy while rows remain.
        while (@$stmt->fetch()) {
            $copy = array();
            foreach ($row as $key => $value) {
                $copy[$key] = $value;
            }
            $rows[] = $copy;
            $guard++;
            if ($guard > 200000) {
                break;
            }
        }

        return new AppMysqliResult($rows, $fields);
    }
}

if (!function_exists('app_stmt_result')) {
    /**
     * Drop-in replacement for mysqli_stmt::get_result().
     *
     * @param mysqli_stmt $stmt Executed statement.
     * @return mysqli_result|AppMysqliResult|false
     */
    function app_stmt_result($stmt)
    {
        if (!($stmt instanceof mysqli_stmt)) {
            return false;
        }

        if (method_exists($stmt, 'get_result')) {
            $result = @app_stmt_result($stmt);
            if ($result instanceof mysqli_result) {
                return $result;
            }
            if ($result === false) {
                // Non mysqlnd builds can expose the method but refuse to buffer.
                // Fall through to the portable reader when a result set exists.
                $fallback = app_stmt_bind_result_rows($stmt);
                if ($fallback instanceof AppMysqliResult) {
                    return $fallback;
                }
            }
            return $result;
        }

        return app_stmt_bind_result_rows($stmt);
    }
}
