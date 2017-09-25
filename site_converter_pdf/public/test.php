<?php require_once("../includes/initialize.php"); ?>
<?php

$document = Document::find_by_id(154);
$document->destroy();

?>