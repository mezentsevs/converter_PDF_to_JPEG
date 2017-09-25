<?php

// Общие установки:
	
	// Установка временной зоны:
	date_default_timezone_set("Europe/Samara");

// Установка основных путей:

	// Установка разделителя директорий:
	defined("DS") ? null : define("DS", DIRECTORY_SEPARATOR);	

	// Установка пути корневой директории сайта:
	defined("SITE_ROOT") ? null : define("SITE_ROOT", $_SERVER["DOCUMENT_ROOT"].DS."my-site".DS."site_converter_pdf");

	// Установка пути папки public:
	$public_path = "public";
	defined("PUBLIC_PATH") ? null : define("PUBLIC_PATH", SITE_ROOT.DS.$public_path);

	// Установка пути директории с библиотеками:
	defined("LIB_PATH") ? null : define("LIB_PATH", SITE_ROOT.DS."includes");

// Подключение файлов:

	// Загрузка файлов конфигурации:
	require_once(LIB_PATH.DS."config.php");

	// Загрузка файлов с функциями:
	require_once(LIB_PATH.DS."functions.php");

	// Загрузка основных объектов:
	require_once(LIB_PATH.DS."session.php");
	require_once(LIB_PATH.DS."database.php");
	require_once(LIB_PATH.DS."database_object.php");

	// Загрузка объектов, работающих с базой данных:
	require_once(LIB_PATH.DS."document.php");
	require_once(LIB_PATH.DS."image.php");
	
?>