<?php
require_once(LIB_PATH.DS.'database.php');

// Класс для работы с документом:
class Document extends DatabaseObject {

	protected static $table_name = "documents";
	protected static $db_fields = array('id', 'filename', 'type', 'size');

	public $id;
	public $filename;
	public $type;
	public $size;

	private $temp_path;
	public $upload_dir = "documents";
	public $sliders_dir = "sliders";
	public $images_dir = "images";
	
	public $errors = array();
	protected $upload_errors = array(
		UPLOAD_ERR_OK 			=> "Ошибок нет.",
		UPLOAD_ERR_INI_SIZE 	=> "Размер документа превышает допустимое значение upload_max_filesize.",
		UPLOAD_ERR_FORM_SIZE 	=> "Размер документа превышает допустимое значение MAX_FILE_SIZE формы.",
		UPLOAD_ERR_PARTIAL 		=> "Частичная загрузка.",
		UPLOAD_ERR_NO_FILE 		=> "Отсутствует файл.",
		UPLOAD_ERR_NO_TMP_DIR 	=> "Отсутствует временная директория.",
		UPLOAD_ERR_CANT_WRITE 	=> "Невозможно записать на диск.",
		UPLOAD_ERR_EXTENSION 	=> "Загрузка файла остановлена расширением."
		);

	// Поиск всех документов и сортировка по имени:
	public static function find_all_documents() {
		global $database;
		$sql = "SELECT * ";
		$sql .= "FROM documents ";
		$sql .= "ORDER BY filename ASC";
		return self::find_by_sql($sql);
	}

	// Инициализация объекта значениями из формы
	// (аргумент - $_FILE(['uploaded_file'])):
	public function attach_file($file) {
		
		// Проверка наличия ошибок:
		if(!$file || empty($file) || !is_array($file)) {
			
			// Ничего не было загружено или неправильный аргумент:
			$this->errors[] = "Файл не был загружен.";
			return false;
		} elseif($file['error'] != 0) {
			
			// Ошибка в процессе загрузки:
			$this->errors[] = $this->upload_errors[$file['error']];
			return false;
		} else {

		// Установка атрибутов объекта значениями из $_FILE:
		$this->temp_path 	= $file['tmp_name'];

		// Добавление времени к имени файла:
		$file_pathinfo = pathinfo($file['name']);
		$this->filename 	= $file_pathinfo['filename']."_".time().".".$file_pathinfo['extension'];

		$this->type 		= $file['type'];
		$this->size 		= $file['size'];
		return true;
		}
	}

	// Сохранение:
	public function save() {
		
		// Проверка наличия id (у новой записи его нет):
		if(isset($this->id)) {
			
			// Если id есть, то выполняется обновление:
			return $this->update();
		} else {
			
			// Проверка наличия ошибок:
			if(!empty($this->errors)) { return false; }

			// Проверка дополнительных требований к файлу:

			// Проверка наличия имени файла и временной директории:
			if(empty($this->filename) || empty($this->temp_path)) {
				$this->errors[] = "Расположение файла недоступно.";
				return false;
			}

			// Определение целевого пути файла:
			$target_path = PUBLIC_PATH.DS.$this->upload_dir.DS.$this->filename;

			// Проверка наличия файла с таким же именем:
			if(file_exists($target_path)) {
				$this->errors[] = "Файл {$this->filename} уже существует.";
				return false;
			}

			// Попытка перемещения файла из временной в целевую директорию:
			if(move_uploaded_file($this->temp_path, $target_path)) {
				
				// Успех:
				// Создание объекта с сохранением в базе данных:
				if($this->create()) {
					
					// Удаление временного пути (файла там уже нет):
					unset($this->temp_path);
					return true;
				}

			} else {
				
				// Неудача:
				// Вывод ошибки - файл не был перемещен:
				$this->errors[] = "Загрузка файла прошла неуспешно, возможно из-за некорректных разрешений для директории загрузки.";
				return false;
			}
		}
	}

	// Определение полного пути документа:
	public function pdf_file_path() {
		return PUBLIC_PATH.DS.$this->upload_dir.DS.$this->filename;
	}

	// Определение полного пути папки изображений:
	public function img_folder_path() {
		$pdf_file_pathinfo = pathinfo($this->filename);
		return PUBLIC_PATH.DS.$this->sliders_dir.DS.$pdf_file_pathinfo["filename"].DS.$this->images_dir;
	}

	// Определение полного пути папки слайдера:
	public function slider_folder_path() {
		$pdf_file_pathinfo = pathinfo($this->filename);
		return PUBLIC_PATH.DS.$this->sliders_dir.DS.$pdf_file_pathinfo["filename"];
	}

	// Определение полного пути файла index.html слайдера:
	public function slider_indexhtml_path() {
		return $this->slider_folder_path().DS."index.html";
	}

	// Уничтожение:
	public function destroy() {

		// Удаление записи из базы данных:
		if($this->delete()) {
			
			// Успех:
			// Удаление файла:
			if (is_file($this->pdf_file_path())) {
				unlink($this->pdf_file_path());
			}

			// Поиск изображений для документа:
			$image_set = Image::find_images_for_document($this->id);
			if ($image_set) {
				foreach ($image_set as $image) {
					
					// Удаление изображений:
					$image->destroy();
				}
			}

			// Удаление папки изображений:
			if (is_dir($this->img_folder_path())) {
				rmdir($this->img_folder_path());
			}

			// Удаление файла index.html для слайдера:
			if (is_file($this->slider_indexhtml_path())) {
				unlink($this->slider_indexhtml_path());
			}

			// Удаление папки слайдера:
			if (is_dir($this->slider_folder_path())) {
				rmdir($this->slider_folder_path());
			}

			return true;
		} else {

			// Неудача:
			// Удаление записи из базы данных не удалось:
			return false;
		}
	}

	// Формирование содержимого файла index.html слайдера для скачивания:
	private static function slider_indexhtml_content($slides_array=[]) {
		$output  = "<!DOCTYPE html>\r\n";
		$output .= "<html lang=\"ru\">\r\n";
		$output .= "<head>\r\n";
		$output .= "<meta charset=\"utf-8\">\r\n";
		$output .= "<title>Слайдер</title>\r\n";
		$output .= "</head>\r\n";
		$output .= "<style>\r\n";
		$output .= "body { margin: 0; font-family: Arial; font-size: 1em; }\r\n";
		$output .= "header { background: #EEE; height: 50px; }\r\n";
		$output .= "h1 { margin: 0; font-size: 1.2em; text-align: center; line-height: 50px; }\r\n";
		$output .= "main { min-height: 1000px; }\r\n";
		$output .= "#slider { margin: 50px auto; width: 640px; text-align: center; }\r\n";
		$output .= "#slide { width: 620px; border: 1px solid #CCC; -moz-box-shadow: 1px 1px 1px #CCC; -o-box-shadow: 1px 1px 1px #CCC; -webkit-box-shadow: 1px 1px 1px #CCC; box-shadow: 1px 1px 1px #CCC; }\r\n";
		$output .= "button { margin: 10px 5px; padding: 3px; }\r\n";
		$output .= "footer { background: #EEE; height: 50px; line-height: 50px; text-align: center; }\r\n";
		$output .= "</style>\r\n";
		$output .= "<script>\r\n";
		$output .= "var slider = {\r\n";
		$output .= "slides:[" . htmlentities(join(',', $slides_array)) . "],\r\n";
		$output .= "index:0,\r\n";
		$output .= "set: function(image) { document.getElementById(\"slide\").setAttribute(\"src\", image); },\r\n";
		$output .= "init: function() { this.set(this.slides[this.index]); },\r\n";
		$output .= "left: function() { this.index--; if (this.index < 0) { this.index = this.slides.length-1; } this.set(this.slides[this.index]); },\r\n";
		$output .= "right: function() { this.index++; if (this.index == this.slides.length) { this.index = 0; } this.set(this.slides[this.index]); }\r\n";
		$output .= "};\r\n";
		$output .= "window.onload = function() { slider.init(); setInterval(function() { slider.right(); },5000); };\r\n";
		$output .= "</script>\r\n";
		$output .= "<body>\r\n";
		$output .= "<header><h1>Слайдер</h1></header>\r\n";
		$output .= "<main>\r\n";
		$output .= "<figure id=\"slider\">\r\n";
		$output .= "<img id=\"slide\" src=\"\" alt=\"слайд\">\r\n";
		$output .= "<button id=\"left\" onclick=\"slider.left();\">&laquo; Назад</button>\r\n";
		$output .= "<button id=\"right\" onclick=\"slider.right();\">Далее &raquo;</button>\r\n";
		$output .= "</figure>\r\n";
		$output .= "</main>\r\n";
		$output .= "<footer><small>&copy; Copyright</small></footer>\r\n";
		$output .= "</body>\r\n";
		$output .= "</html>\r\n";	
		return $output;
	}

	// Запись содержимого файла index.html слайдера для скачивания:
	public function slider_indexhtml_write() {

		// Определение деталей файла pdf:
		$pdf_file_pathinfo = pathinfo($this->filename);

		// Формирование массива с адресами изображений:
		$image_set = Image::find_images_for_document($this->id);
		if ($image_set) {
			foreach ($image_set as $image) {
				$slides_array[] = "'".$this->images_dir."/".$image->filename."'";
			}
		}

		// Формирование содержимого файла:
		$content = self::slider_indexhtml_content($slides_array);

		// Запись содержимого в файл:
		file_put_contents($this->sliders_dir."/".$pdf_file_pathinfo["filename"]."/index.html", $content);
	}

	// Конвертирование документа:
	public function convert() {

		// Определение путей:
		$pdf_file_path = $this->pdf_file_path();
		$img_folder_path = $this->img_folder_path();

		// Создание директории:
		if (mkdir($img_folder_path, 0777, true)) {
			
			// Успех cоздания директории:
			// Попытка обработки документа PDF:
			try {

				// Создание нового пустого объекта Imagick:
				$imagick = new Imagick();
				// Установка кол-ва точек на дюйм (300,300 = 300dpi):
				$imagick->setResolution(300, 300);
				// Чтение документа PDF из файла:
				$imagick->readImage($pdf_file_path);		

					// Конвертирование страниц документа PDF в изображения JPEG:
					$i=0;
					foreach($imagick as $page_image) {
						$i++;
						// Добавление первого "0" для чисел меньше 10:
						if ($i<10) { $i = "0" . $i; }

						// Установка палитры:
						$page_image->setImageColorspace(255);
						// Установка компрессора JPEG:
						$page_image->setCompression(Imagick::COMPRESSION_JPEG);
						// Установка качества сжатия (1 = высокое сжатие .. 100 = низкое сжатие):
						$page_image->setCompressionQuality(80);
						// Установка формата изображения:
						$page_image->setImageFormat("jpeg");		

						// Изменение альбомной ориентации страницы в портретную:
						if ($page_image->getImageWidth() > $page_image->getImageHeight()) {
							// Поворот изображения против часовой стрелки:
							$page_image->rotateImage("#000", -90);
						}		

						// Определение пути к файлу изображения JPEG:
						$pdf_file_pathinfo = pathinfo($this->filename);
						$img_filename = $pdf_file_pathinfo["filename"]."_".$i.".jpeg";
						$img_file_path = $img_folder_path.DS.$img_filename;
						$img_file_pathinfo = pathinfo($img_file_path);
						
						// Запись страницы документа PDF в файл изображения JPEG:
						$page_image->writeImage($img_file_path);

						// Создание объекта image, инициализация и сохранение в базе данных:
						$image = new Image();
						$image->document_id = $this->id;
						$image->document_filename = $this->filename;
						$image->filename = $img_file_pathinfo["basename"];
						$image->type = $img_file_pathinfo["extension"];
						$image->size = filesize($img_file_path);
						$image->save();
					}

				// Освобождение памяти и уничтожение объекта Imagick:
				$imagick->clear();
				$imagick->destroy();

				// Запись файла index.html для слайдера:
				$this->slider_indexhtml_write();

				return true;		

			} catch (ImagickException $e) {
				// Обработка исключения - вывод сообщения:
				echo $e->getMessage();
				return false;
			}
		} else {	

			// Неудача cоздания директории:
			return false;
		}
	}

	// Получение списка ссылок на изображения документа по его id в формате json:
	public static function get_images_links() {
		
		// Установка типа содержимого документа:
		header('Content-TYPE: application/json');

		// Проверка переданного id документа:
		if (empty($_GET["id"])) {
			
			// Не передан id документа:
			echo "Error. Missing document id.";
		} else {

			// Поиск изображений для переданного id:
			$image_set = Image::find_images_for_document((int)$_GET["id"]);
			if ($image_set) {	

				// Определение нахождения папки public:
				$public_pathinfo = pathinfo($_SERVER['REQUEST_URI']);
				$public_address = $public_pathinfo["dirname"];	

				// Определение адреса изображения:
				$i=0;
				foreach ($image_set as $image) {
					$i++;
					$document_filename_pathinfo = pathinfo($image->document_filename);
					$result["image".$i] = $_SERVER['SERVER_NAME']."/".
								$public_address."/".
								$image->sliders_dir."/".
								$document_filename_pathinfo["filename"]."/".
								$image->images_dir."/".
								$image->filename;
				}	

				// Кодировка в формат json:
				echo json_encode($result);
			} else {
				
				// Изображения не найдены в базе данных или неправильный id документа:
				echo "Error. Images missing or wrong document id.";
			}
		}
	}

}

?>