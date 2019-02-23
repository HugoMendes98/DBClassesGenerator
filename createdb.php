<?php require_once './Configuration.php';


function getSqlType($type) {
	if (strpos($type, 'TINYINT') !== false)
		return 'bool';
	if (strpos($type, 'VARCHAR') !== false || strpos($type, 'TEXT'))
		return 'string';
	if (strpos($type, 'INT') !== false)
		return 'int';
	if (strpos($type, 'DOUBLE') !== false)
		return 'float';
	if (strpos($type, 'DATE') || strpos($type, 'TIME'))
		return 'DateTime';

	return $type;
}

$db = Configuration::DB();

$creater = json_decode(file_get_contents('creater.json'), true);


foreach ($db->query('SHOW TABLES;')->fetchAll() as $table) {
	$tblName = $table[0];
	$cols = [];
	foreach ($db->query("DESCRIBE $tblName;")->fetchAll() as $column) {

		$field = $column["Field"];
		if ($field == "_id") continue;

		$type = getSqlType(mb_strtoupper($column["Type"]));
		$defaultValue = strpos($field, '_id') !== false ? -1 : $column["Default"];

		if ($type === "string" && $defaultValue === null) $defaultValue = '';
		elseif ($type === "DateTime") $defaultValue = '1970-01-01';

		$cols[] = [
			"name" => $field,
			"type" => $type,
			"default" => $defaultValue
		];
	};

	createClassFile(ltrim($tblName, Configuration::DB_PREDFIX), $cols);

}



function createClassFile($name, $cols) {
	global $creater;
	$requires[] = 'Model';
	foreach ($cols as $col) {
		if (strpos($col["name"], '_id') !== false) $requires[] = ucfirst(rtrim($col["name"], '_id'));
	}

	$fileContent = "<?php\n";
	foreach ($requires as $require) {
		$fileContent.= sprintf($creater["require_once"], $require);
	}
	unset($requires[0]);


	$fileContent.= "\nclass $name extends Model {\n\tprotected static \$tblName = Configuration::DB_PREDFIX . '$name';\n\n";

	foreach ($requires as $require) {
		$fileContent.= sprintf($creater["get_by"], $require, lcfirst($require));
	}

	foreach ($cols as $col) {
		$type = $col["type"];
		$default = $col["default"];
		if ($default === null)
			$default = "null";
		else if ($type === "string" || $type === "DateTime")
			$default = "'$default'";
		else if ($type === "bool")
			$default = $default ? "true" : "false";
		$fileContent.= sprintf($creater["private_var"], $col["name"], $default);
	}
	$fileContent.= "\n";


	$constructContent = "";

	foreach ($cols as $col) {
		$type = $col["type"];

		$funcName = ucfirst($col["name"]);
		if (strpos($funcName, '_id')) $funcName = rtrim($funcName, '_id');

		$inner = '';
		if ($type === "DateTime") {
			$inner = sprintf($creater["get_foreign"], $col["name"], "DateTime");
		} elseif ($type === "int" && $col["default"] === -1) {
			$inner = sprintf($creater["get_foreign"], $col["name"], $funcName);
			$type = $funcName;
		}

		$fileContent.= sprintf($creater["get"], $funcName, $col["name"], $type, $inner);

		$constructContent.= sprintf($creater["construct_get"], $col["name"]);

	}

	$fileContent.= sprintf($creater["construct"], $constructContent);


	$fileContent.= "}";

	$file = fopen("DB/$name.php", "w");
	fwrite($file, $fileContent);
	fclose($file);
}