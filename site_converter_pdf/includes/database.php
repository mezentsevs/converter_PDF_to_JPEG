<?php
require_once(LIB_PATH.DS."config.php");

// Вспомогательный класс для работы с базой данных:
class MySQLDatabase {

	private $connection;
	public $last_query;
	private $magic_quotes_active;
	private $real_escape_string_exists;

	function __construct() {
		$this->open_connection();

		// Установка кодировки для правильного чтения из базы данных:
		$this->query("SET NAMES utf8");

		// Проверка наличия функций экранирования строк в текущей версии PhP:
		$this->magic_quotes_active = get_magic_quotes_gpc();
		$this->real_escape_string_exists = function_exists("mysqli_real_escape_string");
	}

	// Подключение к базе данных:
	public function open_connection() {
		$this->connection = mysqli_connect(DB_SERVER, DB_USER, DB_PASS);
		if (!$this->connection) {
			die("Database connection failed: " . mysqli_error($this->connection));
		} else {
			$db_select = mysqli_select_db($this->connection, DB_NAME);
			if (!$db_select) {
				die("Database selection failed: " . mysqli_error($this->connection));
			}
		}
	}

	// Закрытие соединения с базой данных:
	public function close_connection() {
		if (isset($this->connection)) {
			mysqli_close($this->connection);
			unset($this->connection);
		}
	}

	// Выполнение запроса SQL:
	public function query($sql) {
		$this->last_query = $sql;
		$result = mysqli_query($this->connection, $sql);
		$this->confirm_query($result);
		return $result;
	}

	// Освобождение результата запроса:
	public function free_result($result) {
		mysqli_free_result($result);
	}

	// Экранирование строк:
	public function escape_value($value) {
		if ($this->real_escape_string_exists) {
			if ($this->magic_quotes_active) {
				$value = stripslashes($value);
			}
			$value = mysqli_real_escape_string($this->connection, $value);
		} else {
			if (!$this->magic_quotes_active) {
				$value = addslashes($value);
			}
		}
		return $value;
	}

	// Извлечение данных из результата запроса:
	public function fetch_array($result_set) {
		return mysqli_fetch_assoc($result_set);
	}

	// Получение количества строк в результате запроса:
	public function num_rows($result_set) {
		return mysqli_num_rows($result_set);
	}

	// Получение последнего добавленного id:
	public function insert_id() {
		return mysqli_insert_id($this->connection);
	}

	// Получение количества строк, задействованных в запросе:
	public function affected_rows() {
		return mysqli_affected_rows($this->connection);
	}

	// Подтверждение запроса:
	private function confirm_query($result) {
		if (!$result) {
			$output = "Database query failed: " . mysqli_error($this->connection) . "<br/><br/>";
			die($output);
		}
	}
}

$database = new MySQLDatabase();
$db =& $database;

?>