<?php

// Перенаправление на адрес:
function redirect_to( $location = NULL ) {
	if ($location != NULL) {
		header("Location: {$location}");
		exit;
	}
}

// Вывод сообщения:
function output_message( $message = "" ) {
	if (!empty($message)) {
		return 	"<div class=\"message\">" .
				"<strong>Внимание!</strong> {$message}" .
				"</div>";
	} else {
		return "";
	}
}

// Автозагрузка класса:
function __autoload($class_name) {
	$class_name = strtolower($class_name);
	$path = LIB_PATH.DS."{$class_name}.php";
	if(file_exists($path)) {
		require_once($path);
	} else {
		die("The file {$class_name}.php could not be found.");
	}
}

// Включение шаблона документа:
function include_layout_template($template="") {
	global $public_path;
	include(SITE_ROOT.DS.$public_path.DS.'layouts'.DS.$template);
}

// Вывод размера файла текстом:
function size_as_text($size) {
	if($size < 1024) {
		return "{$size} байт";
	} elseif($size < 1048576) {
		$size_kb = round($size/1024);
		return "{$size_kb} KB";
	} else {
		$size_mb = round($size/1048576, 1);
		return "{$size_mb} MB";
	}
}

// Определение количества страниц pdf:
function pdf_pages_number($pdf_filename) {
	// Открытие файла на чтение:
	$f = fopen($pdf_filename, "r");
	// Поиск регулярными выражениями количества страниц:
	$count = 0;
	while(!feof($f)) {
		// Построчное чтение файла:
		$line = fgets($f,255);
		// Поиск сочетаний "Count число" в строке:
		if (preg_match('/\/Count [0-9]+/', $line, $matches)){
			
			// Поиск значений "число" в результате предыдущего поиска:
			preg_match('/[0-9]+/',$matches[0], $matches2);
			// Запись количества страниц, если значение больше текущего:
			if ($count<$matches2[0]) { $count=$matches2[0]; }
		}
	}
	
	// Закрытие файла:
	fclose($f);
	// Возвращение количества страниц:
	return $count;
}

// Архивация zip:
function to_zip($source, $destination) {

	// Проверка загрузки расширения zip для php и источника для архивации:
	if (!extension_loaded('zip') || !file_exists($source)) {
		return false;
	}

	// Создание архива:
	$zip = new ZipArchive();
	if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
		return false;
	}

	// Подготовка пути (замена разделителей на DS):
	$source = str_replace('\\', DS, realpath($source));
	$source = str_replace('/', DS, $source);

	// Обработка директорий:
	if (is_dir($source) === true) {

		// Создание рекурсивного итератора:
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

		// Проход по каждому элементу рекурсии:
		foreach ($files as $file) {

			// Подготовка пути (замена разделителей на DS):
			$file = str_replace('\\', DS, $file);
			$file = str_replace('/', DS, $file);

			// Пропускаем итерацию в частных случаях:
			if ($file == '.' || $file == '..' || empty($file) || $file == DS) {
				continue;
			}

			// Пропускаем папки с "." и "..":
			if (in_array(substr($file, strrpos($file, DS) + 1), array('.', '..'))) {
				continue;
			}

			// Определение абсолютного пути:
			$file = realpath($file);

			// Подготовка пути (замена разделителей на DS):
			$file = str_replace('\\', DS, $file);
			$file = str_replace('/', DS, $file);

			// Если это директория:
			if (is_dir($file) === true) {
				$d = str_replace($source . DS, '', $file);
				if (empty($d)) {
					continue;
				}

				// Добавление новой директории в архив:
				$zip->addEmptyDir($d);

			// Если это файл:
			} elseif (is_file($file) === true) {
				
				// Добавление файла в архив:
				$zip->addFromString(str_replace($source . DS, '', $file),
					file_get_contents($file));
			}
		}

	// Если это файл:
	} elseif (is_file($source) === true) {
		
		// Добавление файла в архив:
		$zip->addFromString(basename($source), file_get_contents($source));
	}

	// Закрытие архива:
	return $zip->close();
}

// Скачивание zip архива:
function download_zip($file) {

	// Определение базового имени файла:
	$file_name = basename($file);

	// Установка заголовка:
	header("Content-Type: application/zip");
	header("Content-Disposition: attachment; filename=" . $file_name);
	//header("Content-Length: " . filesize($file));
	
	// Чтение файла и передача в буфер вывода:
	readfile($file);

	// Удаление файла архива:
	if (is_file($file)) {
		unlink($file);
	}

	// Завершение выполнения скрипта:
	exit;
}

?>