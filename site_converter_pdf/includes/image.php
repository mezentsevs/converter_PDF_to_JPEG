<?php
require_once(LIB_PATH.DS.'database.php');

// Класс для работы с изображениями:
class Image extends DatabaseObject {

	protected static $table_name = "images";
	protected static $db_fields = array('id', 'document_id', 'document_filename', 'filename', 'type', 'size');

	public $id;
	public $document_id;
	public $document_filename;
	public $filename;
	public $type;
	public $size;

	public $sliders_dir = "sliders";
	public $images_dir = "images";

	// Поиск всех изображений и сортировка по имени:
	public static function find_all_images() {
		global $database;
		$sql = "SELECT * ";
		$sql .= "FROM images ";
		$sql .= "ORDER BY filename ASC";
		return self::find_by_sql($sql);
	}

	// Поиск изображений для документа по его id:
	public static function find_images_for_document($document_id=0) {
		global $database;
		$sql  = "SELECT * FROM " . self::$table_name;
		$sql .= "  WHERE document_id=" . $database->escape_value($document_id);
		$sql .= "  ORDER BY filename ASC";
		return self::find_by_sql($sql);
	}

	// Определение полного пути изображения:
	public function img_file_path() {
		$document_filename_pathinfo = pathinfo($this->document_filename);
		return PUBLIC_PATH.DS.$this->sliders_dir.DS.$document_filename_pathinfo["filename"].DS.$this->images_dir.DS.$this->filename;
	}

	// Уничтожение:
	public function destroy() {
		
		// Удаление записи из базы данных:
		if($this->delete()) {
			
			// Успех:
			// Удаление файла:
			if (is_file($this->img_file_path())) {
				return unlink($this->img_file_path()) ? true : false;
			} else {

				// Файл отсутствует:
				return false;
			}
		} else {

			// Неудача:
			// Удаление записи из базы данных не удалось:
			return false;
		}
	}

}

?>