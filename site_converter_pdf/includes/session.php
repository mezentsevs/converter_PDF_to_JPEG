<?php

// Вспомогательный класс для работы с сессиями:
class Session {
	
	public $message;
	public $errors;

	function __construct() {
		session_start();
		$this->check_message();		
	}

	// Обработка сообщения:
	public function message($msg="") {
		if(!empty($msg)) {

			// Запись сообщения:
			$_SESSION['message'] = $msg;

		} else {
			
			// Получение сообщения:
			return $this->message;

		}
	}

	// Обработка ошибок:
	function errors() {
		
		// Проверка наличия ошибок в сессии:
		if (isset($_SESSION["errors"])) {

			// Чтение ошибок:
			$this->errors = $_SESSION["errors"];

			// Сброс ошибок после использования:
			$_SESSION["errors"] = null;

			// Получение ошибок:
			return $this->errors;
		}
	}
	
	// Проверка сообщения:
	private function check_message() {
		
		// Проверка наличия сообщения в сессии:
		if(isset($_SESSION['message'])) {
			
			// Чтение сообщения:
			$this->message = $_SESSION['message'];

			// Удаление сообщения после использования:
			unset($_SESSION['message']);

		} else {

			// Инициализация, если сообщение не установлено:
			$this->message = "";
			
		}
	}

}

$session = new Session();
$message = $session->message();
$errors = $session->errors();

?>