<?php 
	/**
	  * Calls the Inventory::regenerateThumbs() function
	  * regenerateThumbs(false) regenerates only missing thumbnails (file is missing or hasThumbnail()==false)
	  * regenerateThumbs(true) regenerates all thumbnails, removing any existing thumbnail files first
	  */
ini_set('max_execution_time', 600);

require_once('classes.inc.php');

$inv = new Inventory();

$inv->regenerateThumbs(false);

unset($inv);

?>
