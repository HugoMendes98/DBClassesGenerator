<?php require_once __DIR__ . '/../Configuration.php';

class Model {

	/**
	 * Name of the table in the database
	 * @var string
	 */
    protected static $tblName = '';

    /**
     * Children should not have access this
     * @var array
     */
    private static $all = [];

	/**
	 * Return all
	 * @return static[]
	 */
    public static function getAll() { // Return by class
        $class = static::class . static::$tblName; // to be unique

        if (self::$all[$class] == null) {
            self::$all[$class] = [];
            $sth = Configuration::DB()->query(sprintf("SELECT _id FROM `%s`;", static::$tblName));
            while ($sth && $m = $sth->fetch())
                self::$all[$class][] = new static($m["_id"]);
        }
        return self::$all[$class];
    }

	/**
	 * Delete an entry (! Foreign key)
	 * @param int $id
	 * @return bool in case of success
	 */
	public static function delete(int $id) {
		return is_array(Configuration::DB()->execute(sprintf("DELETE FROM `%s` WHERE _id = :id", static::$tblName), ["id" => $id]));
	}

    private $_id = -1;

	/**
	 * Return the id
	 * @return int
	 */
    public function getId(): int {
        return $this->_id;
    }

	/**
	 * Model constructor.
	 * @param int $id
	 */
    public function __construct(int $id) {
		$data = Configuration::DB()->execute(sprintf('SELECT * FROM `%s` WHERE _id = :id', static::$tblName), ["id" => $id]);
		if (!empty($data)) {
			$data = $data[0];
			$this->_id = $data["_id"];
			return $data;
		}
    }

	/**
	 * Update an entry
	 * @param array $params (can send Model instances)
	 * @return bool in case of success
	 */
    public function update(array $params): bool {
    	if (empty($params)) return true;

    	$sql = "";
		foreach ($params as $key => $val) {
			if ($val instanceof Model) // Send null for empty foreign key
				$params[$key] = $val->getId() <= 0 ? null : $val->getId();
			elseif ($val instanceof DateTime)
				$params[$key] = $val->format('Y-m-d H:i:s');
			$sql.= "$key = :$key, ";
    	}

    	if ($sql != "") $sql = rtrim($sql, ', ');

		$params["id"] = $this->getId();
    	return is_array(Configuration::DB()->execute(sprintf('UPDATE `%s` SET %s WHERE _id = :id', static::$tblName, $sql), $params));
	}
}