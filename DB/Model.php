<?php require_once __DIR__ . '/../Configuration.php';

class Model {

	/**
	 * Name of the table in the database
	 * @var string
	 */
    protected static $tblName = '';

    private static $all = null;

	/**
	 * Return all
	 * @return static[]
	 */
    public static function getAll() {
        if (self::$all == null) {
            self::$all = [];
            $sth = Configuration::DB()->query(sprintf("SELECT _id FROM %s;", static::$tblName));
            while ($sth && $m = $sth->fetch())
                self::$all[] = new static($m["_id"]);
        }
        return self::$all;
    }

	/**
	 * Delete an entry (! Foreign key)
	 * @param int $id
	 * @return bool if successed
	 */
	public static function delete(int $id) {
		return is_array(Configuration::DB()->execute(sprintf("DELETE FROM %s WHERE _id = :id", static::$tblName), ["id" => $id]));
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
		$data = Configuration::DB()->execute('SELECT * FROM `'.static::$tblName.'` WHERE _id = :id', ["id" => $id]);
		if (!empty($data)) {
			$data = $data[0];
			$this->_id = $data["_id"];
			return $data;
		}
    }
}