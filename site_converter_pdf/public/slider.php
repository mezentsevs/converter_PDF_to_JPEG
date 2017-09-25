<?php require_once("../includes/initialize.php"); ?>

<?php 

// Проверка отправки формы:
if(isset($_POST["submit"])) {

	// Обработка формы:

	if (isset($_GET["document"])) {
		
		// Поиск текущего документа в базе данных:
		$document = Document::find_by_id((int)$_GET["document"]);

		// Определение пути слайдера: 
		$pdf_file_pathinfo = pathinfo($document->filename);
		$source = $document->sliders_dir."/".$pdf_file_pathinfo["filename"];
		
		// Определение пути архива:
		$destination = $source . ".zip";

		// Выполнение архивации:
		if (to_zip($source, $destination)) {
			
			// Установка cookies для ссылок на 30 минут:
			setcookie("id_".$document->filename, $document->id, time() + 60*30);

			// Скачивание и удаление архива:
			download_zip($destination);
		}
	}
} else {

	// Вероятно, это GET запрос
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title>Слайдер</title>
</head>
<style>

body {
	margin: 0;
	font-family: Arial;
	font-size: 1em;
}

header {
    background: #EEE;
    height: 50px;
}

h1 {
    margin: 0;
	font-size: 1.2em;
	text-align: center;
    line-height: 50px;
}

#back{
	margin: 10px;
	text-decoration: none;
	line-height: 30px;
}

.message {
    color: darkorange;
    font-size: .8em;
}

main {
    min-height: 1000px;
}

#slider {
	margin: 50px auto;
	width: 640px;
	text-align: center;
}

#slide {
	width: 620px;
	border: 1px solid #CCC;
	-moz-box-shadow: 1px 1px 1px #CCC;
	-o-box-shadow: 1px 1px 1px #CCC;
	-webkit-box-shadow: 1px 1px 1px #CCC;
	box-shadow: 1px 1px 1px #CCC;
}

button {
	margin: 10px 5px;
	padding: 3px;
}

form {
	margin: 50px auto;
	text-align: center;
}

#images_links_list {
    text-align: center;
}

footer {
    background: #EEE;
    height: 50px;
    line-height: 50px;
    text-align: center;
}

</style>
<?php
	
// Поиск изображений для документа:
if (isset($_GET["document"])) {
	$image_set = Image::find_images_for_document((int)$_GET["document"]);
	if ($image_set) {
		foreach ($image_set as $image) {
			$pdf_file_pathinfo = pathinfo($image->document_filename);
			$slides_array[] = "'".$image->sliders_dir."/".$pdf_file_pathinfo["filename"]."/".$image->images_dir."/".$image->filename."'";
		}
	}		
}
// Передача массива c адресами изображений в js:
if (isset($slides_array)) {
	echo "<script type=\"text/javascript\">var slidesArray=[";
	echo htmlentities(join(',', $slides_array));
	echo "]</script>";
}

?>
<script>

// Объект для работы со слайдером:
var slider = {
	
	// Свойства объекта:
		// Массив с адресами изображений:
		slides:slidesArray,	

		// Индекс текущего изображения:
		index:0,

	// Методы объекта:
		// Установка текущего изображения:
		set: function(image) {
			document.getElementById("slide").setAttribute("src", image);
		},	

		// Инициализация слайдера (установка изображения с нулевым индексом):
		init: function() {
			this.set(this.slides[this.index]);
		},	

		// Уменьшение индекса:
		left: function() {
			this.index--;
			if (this.index < 0) { this.index = this.slides.length-1; }
			this.set(this.slides[this.index]);
		},	

		// Увеличение индекса:
		right: function() {
			this.index++;
			if (this.index == this.slides.length) { this.index = 0; }
			this.set(this.slides[this.index]);
		}
};

// После загрузки документа:
window.onload = function() {
	
	// Запуск слайдера с нулевым индексом:
	slider.init();

	// Периодическое увеличение индекса через интервал 5 секунд:
	setInterval(function() {
		slider.right();
	},5000);
};
</script>
<body>
	<header><h1>Слайдер</h1></header>
	<a id="back" href="index.php">&laquo; Назад</a>
	<main>
		<?php echo output_message($message); ?>

		<!-- Слайдер: -->
		<figure id="slider">
			<img id="slide" src="" alt="слайд">
			<button id="left" onclick="slider.left();">&laquo; Назад</button>
			<button id="right" onclick="slider.right();">Далее &raquo;</button>
		</figure>

		<!-- Кнопка скачать zip архив: -->
		<form action="slider.php?document=<?php echo urlencode($_GET['document']); ?>" method="POST">
			<input type="submit" name="submit" value="Скачать zip">
		</form>
		<section id="images_links_list">
			<a href="api.php?id=<?php echo urlencode($_GET['document']); ?>">Список ссылок на изображения в формате json</a>
		</section>
	</main>
	<footer><small>&copy; Copyright</small></footer>
</body>
</html>