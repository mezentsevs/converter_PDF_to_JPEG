<?php
require_once(LIB_PATH.DS."database.php");

// Базовый класс для оъектов, работающих с базой данных:
class DatabaseObject {

	protected static $table_name;
	protected static $db_fields;

	// Общеиспользуемые методы базы данных:

	// Поиск всех объектов:
	public static function find_all() {
		return static::find_by_sql("SELECT * FROM ".static::$table_name);
	}

	// Поиск объектов по id:
	public static function find_by_id($id=0) {
		global $database;
		$result_array = static::find_by_sql("SELECT * FROM ".static::$table_name." WHERE id={$id} LIMIT 1");
		return !empty($result_array) ? array_shift($result_array) : false;
	}

	// Поиск объектов по sql:
	public static function find_by_sql($sql="") {
		global $database;
		$result_set = $database->query($sql);
		$object_array = array();
		while ($row = $database->fetch_array($result_set)) {
			$object_array[] = static::instantiate($row);
		}
		$database->free_result($result_set);
		return $object_array;
	}

	// Подсчет количества всех объектов:
	public static function count_all() {
		global $database;
		$sql = "SELECT COUNT(*) FROM ".static::$table_name;
		$result_set = $database->query($sql);
		$row = $database->fetch_array($result_set);
		return array_shift($row);
	}
	
	// Инстанцирование объекта:
	private static function instantiate($record) {
		$class_name = get_called_class();
		$object = new $class_name;
		foreach ($record as $attribute => $value) {
			if ($object->has_attribute($attribute)) {
				$object->$attribute = $value;
			}
		}
		return $object;
	}

	// Проверка наличия атрибута:
	private function has_attribute($attribute) {
		
		// Получение ассоциативного массива со всеми атрибутами и их значениями:
		$object_vars = $this->attributes();

		// Проверка наличия ключа в массиве (true/false):
		return array_key_exists($attribute, $object_vars);
	}

	// Получение ассоциативного массива атрибутов со значениями:
	protected function attributes() {
		$attributes = array();
		foreach (static::$db_fields as $field) {
			if (property_exists($this, $field)) {
				$attributes[$field] = $this->$field;
			}
		}
		return $attributes;
	}

	// Экранирование атрибутов:
	protected function sanitized_attributes() {
		global $database;
		$clean_attributes = array();
		foreach ($this->attributes() as $key => $value) {
			$clean_attributes[$key] = $database->escape_value($value);
		}
		return $clean_attributes;
	}

	// Сохранение:
	public function save() {
		
		// Проверка наличия id и выбор метода:
		return isset($this->id) ? $this->update() : $this->create();
	}

	// Создание:
	public function create() {
		global $database;
		$attributes = $this->sanitized_attributes();
		
		$attributes_without_id = $attributes;
		unset($attributes_without_id['id']);

		$sql  = "INSERT INTO ".static::$table_name." (";
		$sql .= join(", ", array_keys($attributes_without_id));
		$sql .= ") VALUES ('";
		$sql .= join("', '", array_values($attributes_without_id));
		$sql .= "')";
		if ($database->query($sql)) {
			$this->id = $database->insert_id();
			return true;
		} else {
			return false;
		}
	}

	// Обновление:
	public function update() {
		global $database;
		$attributes = $this->sanitized_attributes();

		$attributes_without_id = $attributes;
		unset($attributes_without_id['id']);

		$attribute_pairs = array();
		foreach ($attributes_without_id as $key => $value) {
			$attribute_pairs[] = "{$key}='{$value}'";
		}
		$sql  = "UPDATE ".static::$table_name." SET ";
		$sql .= join(", ", $attribute_pairs);
		$sql .= " WHERE id=". $database->escape_value($this->id);
		$database->query($sql);
		return ($database->affected_rows() == 1) ? true : false;
	}

	// Удаление:
	public function delete() {
		global $database;
		$sql  = "DELETE FROM ".static::$table_name;
		$sql .= " WHERE id=". $database->escape_value($this->id);
		$sql .= " LIMIT 1";
		$database->query($sql);
		return ($database->affected_rows() == 1) ? true : false;
	}

}

?>