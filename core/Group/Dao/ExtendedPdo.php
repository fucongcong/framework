<?php

namespace Group\Dao;

use Aura\Sql\ExtendedPdo as Aura_ExtendedPdo;

class ExtendedPdo extends Aura_ExtendedPdo
{
    /**
     * 此处增加了断线重连机制，为了支持async
     *
     * Performs a query with bound values and returns the resulting
     * PDOStatement; array values will be passed through `quote()` and their
     * respective placeholders will be replaced in the query string.
     *
     * @param string $statement The SQL statement to perform.
     *
     * @param array $values Values to bind to the query
     *
     * @return PDOStatement
     *
     * @see quote()
     * 
     */
    public function perform($statement, array $values = array())
    {	
    	for($i = 0; $i<2; $i++) {
	        try {
	        	$sth = $this->prepareWithValues($statement, $values);
	        	$this->beginProfile(__FUNCTION__);
	        	$sth->execute();
	        	break;
	        } catch (\Exception $e) {
	        	if ($sth ->errorCode() == "HY000" && php_sapi_name() == 'cli') {
	        		$this->pdo = null;
	        		$this->connect();
	        		continue;
	        	}
	        }
    	}
        $this->endProfile($statement, $values);
        return $sth;
    }

    //一下方法在使用async时也需要进行断线重连判断
    /**
     *
     * Queries the database and returns a PDOStatement.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @param int $fetch_mode The `PDO::FETCH_*` type to set on the returned
     * `PDOStatement::setFetchMode()`.
     *
     * @param mixed $fetch_arg1 The first additional argument to send to
     * `PDOStatement::setFetchMode()`.
     *
     * @param mixed $fetch_arg2 The second additional argument to send to
     * `PDOStatement::setFetchMode()`.
     *
     * @return PDOStatement
     *
     * @see http://php.net/manual/en/pdo.query.php
     *
     */
    public function query($statement)
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);

        // remove empty constructor params list if it exists
        $args = func_get_args();
        if (count($args) === 4 && $args[3] === array()) {
            unset($args[3]);
        }

        $sth = call_user_func_array(array($this->pdo, 'query'), $args);

        $this->endProfile($sth->queryString);
        return $sth;
    }

    /**
     *
     * Executes an SQL statement and returns the number of affected rows.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *
     * @return int The number of affected rows.
     *
     * @see http://php.net/manual/en/pdo.exec.php
     *
     */
    public function exec($statement)
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $affected_rows = $this->pdo->exec($statement);
        $this->endProfile($statement);
        return $affected_rows;
    }

    /**
     *
     * Returns the last inserted autoincrement sequence value.
     *
     * @param string $name The name of the sequence to check; typically needed
     * only for PostgreSQL, where it takes the form of `<table>_<column>_seq`.
     *
     * @return int
     *
     * @see http://php.net/manual/en/pdo.lastinsertid.php
     *
     */
    public function lastInsertId($name = null)
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $result = $this->pdo->lastInsertId($name);
        $this->endProfile();
        return $result;
    }

    /**
     *
     * Begins a transaction and turns off autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.begintransaction.php
     *
     */
    public function beginTransaction()
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $result = $this->pdo->beginTransaction();
        $this->endProfile();
        return $result;
    }

    /**
     *
     * Commits the existing transaction and restores autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.commit.php
     *
     */
    public function commit()
    {
        $this->connect();
        $this->beginProfile(__FUNCTION__);
        $result = $this->pdo->commit();
        $this->endProfile();
        return $result;
    }
}