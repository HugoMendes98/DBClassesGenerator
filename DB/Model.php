<?php require_once __DIR__ . '/../Configuration.php';

class Model {

	/**
	 * Database operators
	 */
	private const DB_OPERATORS = [
		10 => ' OR ',
		11 => ' AND ',
		12 => '(',
		13 => ')',
		15 => ' BETWEEN ',
		20 => ' = ',
		21 => ' != ',
		22 => ' > ',
		23 => ' >= ',
		24 => ' < ',
		25 => ' <= ',
	];

	/**
	 * Or
	 */
	protected const DB_OR = 10;
	/**
	 * And
	 */
	protected const DB_AND = 11;
	/**
	 * create a group '('
	 */
	protected const DB_GROUPSTART = 12;
	/**
	 * close a group ')'
	 */
	protected const DB_GROUPEND = 13;

	/**
	 * Between 2 values
	 */
	protected const DB_BETWEEN = 15;

	/**
	 * equal
	 */
	protected const DB_EQ = 20;
	/**
	 * not equal
	 */
	protected const DB_NE = 21;
	/**
	 * greater than
	 */
	protected const DB_GT = 22;
	/**
	 * greater than or equal
	 */
	protected const DB_GE = 23;
	/**
	 * less than
	 */
	protected const DB_LT = 24;
	/**
	 * less than or equal
	 */
	protected const DB_LE = 25;

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

	/* ------- Static functions ------- */
	/**
	 * Return all
	 * @return static[]
	 */
    public static function getAll() { // Return by class
        $class = static::class . static::$tblName; // to be unique

        if (!isset(self::$all[$class])) {
            self::$all[$class] = [];
            $models = self::select(['_id']);
            if ($models !== false)
            	foreach ($models as $model)
					self::$all[$class][] = new static($model["_id"]);
        }
        return self::$all[$class];
    }

	/**
	 * Do a select
	 * @param array $fields
	 * @param array $params
	 * @return array|false
	 */
    protected static function select(array $fields, array $params = []) {
    	if (empty($fields)) return false;

    	$whereStmt = '';
		$whereParams = [];

    	$varCount = 0;
    	$groupsCount = 0;

    	foreach ($params as $key => $value) {
    		if (is_int($value)) { // Boolean operators
    			switch ($value) { // for autoclosing groups
					case self::DB_GROUPSTART: $groupsCount++;
						break;
					case self::DB_GROUPEND:
						if ($groupsCount > 0) $groupsCount--;
						break;
				}
    			$whereStmt.= self::DB_OPERATORS[$value];
			} else {
    			$field = array_keys($value)[0];
    			$whereStmt.= $field;

    			$param = $value[$field];
				if (!is_array($param)) // for ["field" => val]
					$param = [
						"op" => self::DB_EQ,
						"val" => $param
					];

				$op = $param["op"];
				$val= $param["val"];
				$paramName = "var$varCount";

				if ($op === self::DB_BETWEEN) {
					$whereStmt.= self::DB_OPERATORS[self::DB_BETWEEN] . ":1$paramName" . self::DB_OPERATORS[self::DB_AND] . ":2$paramName";
					$whereParams["1$paramName"] = $val[0];
					$whereParams["2$paramName"] = $val[1];
				} else {
					$whereStmt.= self::DB_OPERATORS[$op] . ":$paramName";
					$whereParams[$paramName] = $val;
				}
				$varCount++;
			}
		}

    	for (;$groupsCount > 0; $groupsCount--) // Close groups
    		$whereStmt.= self::DB_OPERATORS[self::DB_GROUPEND];

    	foreach ($whereParams as $key => $val) {
    		if ($val instanceof DateTime)
    			$whereParams[$key] = $val->format('Y-m-d H:i:s');
			else if ($val instanceof Model)
				$whereParams[$key] = $val->getId();
		}

    	if ($whereStmt != '') $whereStmt = "WHERE $whereStmt";

		return Configuration::DB()->execute(sprintf("SELECT %s FROM `%s` %s;", implode($fields, ', '), static::$tblName, $whereStmt), $whereParams);
	}

	/**
	 * Insert an entry
	 * @param array $params
	 * @return int|false return the id or false if failed
	 */
    public static function insert(array $params) {
		if (empty($params)) return false;

    	$keys = [];
		foreach ($params as $key => $val) {
			if ($val instanceof Model) // Send null for empty foreign key
				$params[$key] = $val->getId() <= 0 ? null : $val->getId();
			elseif ($val instanceof DateTime)
				$params[$key] = $val->format('Y-m-d H:i:s');
			$keys[] = $key;
		}

    	$ok = is_array(Configuration::DB()->execute(sprintf("INSERT INTO `%s` (%s) VALUES (:%s);", static::$tblName, implode($keys, ', '), implode($keys, ', :')), $params));
		return $ok ? intval(Configuration::DB()->lastInsertId()) : false;
	}

	/**
	 * Delete an entry (! Foreign key)
	 * @param int $id
	 * @return bool in case of success
	 */
	public static function delete(int $id) {
		return is_array(Configuration::DB()->execute(sprintf("DELETE FROM `%s` WHERE _id = :id", static::$tblName), ["id" => $id]));
	}

	/* ---------- Properties ---------- */
    private $_id = -1;

	/**
	 * Return the id
	 * @return int
	 */
    public function getId(): int {
        return $this->_id;
    }

	/* ------- Object functions ------- */
	/**
	 * Model constructor.
	 * @param int $id
	 */
    public function __construct(int $id) {
		$data = self::select(['*'], [["_id" => $id]]);
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
    	return is_array(Configuration::DB()->execute(sprintf("UPDATE `%s` SET %s WHERE _id = :id", static::$tblName, $sql), $params));
	}
}