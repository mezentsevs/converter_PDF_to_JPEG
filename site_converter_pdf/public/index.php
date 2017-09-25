<?php require_once("../includes/initialize.php"); ?>

<?php

// Максимальный размер файла (50 MB = 52428800 байт):
$max_file_size = 52428800;

// Проверка отправки формы:
if(isset($_POST["submit"])) {	
	
	// Обработка формы:

	$document = new Document();
	$document->attach_file($_FILES["file_upload"]);
	
	// Сохранение документа:
	if($document->save()) {
		
		// Успех
		// Проверка количества страниц в документе:
		$pdf_filename = $document->upload_dir.DS.$document->filename;
		if (pdf_pages_number($pdf_filename) > 20) {
			
			// Количество страниц превышает допустимое значение:
			$document->destroy();
			$message = "Количество страниц в документе превышает допустимое значение.";
		} else {
			
			// Количество страниц в норме:
			if ($document->convert()) {
				$session->message("Конвертирование завершено успешно.");
				redirect_to("slider.php?document=".$document->id);
			} else {
				$message = "Ошибка конвертирования документа.";
			}
		}
	} else {
		
		// Неудача
		$message = join("<br/>", $document->errors);
	}
} else {
	
	// Вероятно, это GET запрос
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title>Конвертер PDF в JPEG</title>
	<link rel="stylesheet" type="text/css" href="css/main.css">
</head>
<body>
	<header><h1>Конвертер PDF в JPEG</h1></header>
	<main>
		<?php echo output_message($message); ?>

		<!-- Форма для отправки документа: -->
		<form action="index.php" enctype="multipart/form-data" method="POST">
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_file_size; ?>">
			<input type="file" name="file_upload" accept="application/pdf">
			<br>
			<input type="submit" name="submit" value="Конвертировать">
		</form>

		<!-- Секция со ссылками на слайдеры: -->
		<section id="links">
<?php 
	
	// Чтение cookies:
	foreach ($_COOKIE as $cookie_key => $cookie_value) {
		
		// Поиск cookie с id:
		if (preg_match("/^id_/", $cookie_key)) {

			// Поиск документа:
			$cookie_document = Document::find_by_id((int)$cookie_value);
			
			// Вывод ссылки на слайдер:
			if ($cookie_document) {
				echo "<a href=\"slider.php?document=".urlencode($cookie_document->id)."\">";
				echo htmlentities($cookie_document->filename);
				echo "</a>";
				echo "<br>";
			}
		}
	}

?>
		</section>
	</main>
	<footer><small>&copy; Copyright</small></footer>
</body>
</html>