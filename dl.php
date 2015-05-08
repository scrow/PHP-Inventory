<?php
	
require_once('classes.inc.php');
require_once('globals.inc.php');

switch($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		if((!isset($_GET['id'])) || (!isset($_GET['attachment']))) {
			die('One or more parameters missing.');
		};
		
		$item = new Item($_GET['id']);
		if(!$item->isValid()) {
			die('Invalid item id');
		};
		
		$attachment = $item->getAttachment($_GET['attachment']);
		
		if(!$attachment) {
			die('Invalid attachment id');
		};
		
		$content = $attachment->getFiledata();

		$filename = $attachment->getAttribute('sha1') . '.' . $attachment->getAttribute('originalExt');
		
		header("Cache-control: private");
		header("Content-type: " . $attachment->getAttribute('mime'));
// To force downloading:
//		header("Content-type: application/force-download");
		header("Content-transfer-encoding: binary\n");
		header("Content-disposition: filename=\"$filename\"");
// To force downloading:
//		header("Content-disposition: attachment; filename=\"$filename\"");
		header("Content-Length: ".strlen($content));

		echo($content);
		
		break;
	
	default:
		die('Invalid request method');
		break;
}

