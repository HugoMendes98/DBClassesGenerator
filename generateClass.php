<?php require_once './Configuration.php';

$dir = __DIR__ . '/export';
$val = getopt("d:", ["dir:"]);
if ($val !== false && $val) {
    $dir = isset($val["d"]) ? $val["d"] : $val["dir"];
}

function convertSql2PhpType($type) {
	if (strpos($type, 'TINYINT(1)') !== false)
		return 'bool';
	if (strpos($type, 'VARCHAR') !== false || strpos($type, 'TEXT') !== false)
		return 'string';
	if (strpos($type, 'INT') !== false)
		return 'int';
	if (strpos($type, 'DOUBLE') !== false)
		return 'float';
	if (strpos($type, 'DATE') !== false || strpos($type, 'TIME') !== false)
		return 'DateTime';
	return 'null';
}

$fileParts = json_decode(file_get_contents('fileParts.json'), true);

$db = Configuration::DB();
foreach ($db->query('SHOW TABLES;')->fetchAll() as $table) {
	$tblName = $table[0];
	$cols = [];
	foreach ($db->query("DESCRIBE $tblName;")->fetchAll() as $column) {
		$field = $column["Field"];
		if ($field == Configuration::DB_ENDWORD_ID) continue;

		$type = convertSql2PhpType(mb_strtoupper($column["Type"]));
		$defaultValue = strpos($field, '_id') !== false ? -1 : $column["Default"];

		if ($type === "string" && $defaultValue === null) $defaultValue = '';
		elseif ($type === "DateTime") $defaultValue = '1970-01-01';

		$cols[] = [
			"name" => $field,
			"type" => $type,
			"default" => $defaultValue
		];
	};
	createClassFile(ltrim($tblName, Configuration::DB_PREFIX), $cols);
}

function createClassFile($name, $cols) {
	global $fileParts, $dir;

	$requireContent = sprintf($fileParts["require_once"], 'Model');
	$getByContent = '';
	$privateContent = '';
	$constructContent = '';
	$propertiesContent = '';

	$jsonFunction = '';

	foreach ($cols as $col) {
		$fieldName = $col["name"];
		$funcName = ucfirst($fieldName);
		$type = $col["type"];
		$defaultValue = $col["default"];

        $jsonReturn = "get$funcName()";

		// some get functions return an object
		$innerGet = '';

		// foreign keys use another class
		if (strpos($fieldName, Configuration::DB_ENDWORD_ID) !== false) {
			$funcName = rtrim($funcName, Configuration::DB_ENDWORD_ID);
			$requireContent.= sprintf($fileParts["require_once"], $funcName);
			$getByContent.= sprintf($fileParts["get_by"], $funcName, lcfirst($funcName), $name);
			$type = $funcName;
			$innerGet = sprintf($fileParts["get_foreign"], $fieldName, $funcName);

            $jsonReturn = "get$funcName()->getId()";
		}

		if ($defaultValue === null)
			$defaultValue = "null";
		else if ($type === "string" || $type === "DateTime")
			$defaultValue = "'$defaultValue'";
		else if ($type === "bool")
            $defaultValue = $defaultValue ? "true" : "false";
		$privateContent.= sprintf($fileParts["private_var"], $fieldName, $defaultValue);

		if ($type === "DateTime") {
			$innerGet = sprintf($fileParts["get_foreign"], $fieldName, "DateTime");
            $jsonReturn.= "->getTimestamp()";
		}

		if ($type === "bool" && (substr(strtolower($fieldName), 0, 2) == 'is' || substr(strtolower($fieldName), 0, 3) == 'has')) {
			$propertiesContent.= sprintf($fileParts["get_bool"], $fieldName);
            $jsonReturn = "$fieldName()";
		} else // int for bool when we set
			$propertiesContent.= sprintf($fileParts["get"], $funcName, $fieldName, $type, $innerGet) . sprintf($fileParts["set"], $funcName, lcfirst($funcName), $fieldName, $type === "bool" ? "int" : $type);
		$constructContent.= sprintf($fileParts["construct_get"], $fieldName);

        $jsonFunction.= "\t\t\t\"$fieldName\" => \$this->$jsonReturn,\n";
	}

	$jsonFunction.= "\t\t\t\"id\" => \$this->getId()";

	$classContent = sprintf($fileParts["class"], $name,  $getByContent, $privateContent, $propertiesContent, sprintf($fileParts["get_json"], $jsonFunction) ,sprintf($fileParts["construct"], $constructContent), sprintf($fileParts["update"], $name));
	$fileContent = sprintf($fileParts["file"], $requireContent, $classContent);

	$file = fopen("$dir/". ucfirst($name) .".php", "w");
	fwrite($file, $fileContent);
	fclose($file);
}

copy(__DIR__ . '/DB/DB.php', "$dir/DB.php");
copy(__DIR__ . '/DB/Model.php', "$dir/Model.php");
copy(__DIR__ . '/Configuration.php.example', "$dir/Configuration.php");