<?php

class DB extends PDO {

	/**
	 * DB constructor (like PDO).
	 * @param string $user
	 * @param string $pass
	 * @param string $name
	 * @param string $host
	 * @param int $port
	 */
    public function __construct(string $user, string $pass, string $name, string $host = "", int $port = 3306) {
        parent::__construct("mysql:dbname=$name;host=$host;port=$port;charset=latin1", $user, $pass);
    }
    
    /**
     * @param string $query
     * @param array $attributes
     * @return array|bool
     */
    public function execute($query, array $attributes = []) {
        $stmt = $this->prepare($query);
        $ok = $stmt->execute($attributes);
        if (!$ok) var_dump($stmt->errorInfo()); // TODO: log it
        return $ok ? $stmt->fetchAll() : false;
    }
}