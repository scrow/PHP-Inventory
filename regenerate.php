<?php 
 /** This is regenerate.php
  ** Regenerates thumbnail images if Imagemagick is installed.
  **/

if (!class_exists('Imagick',false)) {
	die('ImageMagick is not installed in PHP.');
};

require_once('classes.inc.php');

$inv = new Inventory();

$items = $inv->allItems();

$attach = 0;
$count = 0;
$regen = 0;

foreach($items as $item) {
	$count++;
	$attachments = $item->getAttachments();
	foreach($attachments as $attachment) {
		$attach++;
		if($attachment->getAttribute('hasThumbnail') == false) {
			$regen++;
			$attachment->makeThumbnail();
		};
	};
};

echo('Processed '.$attach.' attachments for '.$count.' items, and regenerated '.$regen);

unset($inv);

?>
