<?php require_once("../includes/initialize.php"); ?>
<?php
	
	// Получение списка ссылок на изображения:
	// id документа указывается в параметре GET запроса (api.php?id=)
	Document::get_images_links();
?>