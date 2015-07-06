<?php
	
require_once('classes.inc.php');
require_once('globals.inc.php');

?>

<HTML>
	<HEAD>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<TITLE>Personal Inventory - Add/Edit Item</TITLE>
		<LINK REL="stylesheet" href="src/less/bootstrap/dist/css/bootstrap.css">
		<link rel="stylesheet" href="src/less/bootstrap/dist/css/bootstrap-theme.css">

	</HEAD>
	<BODY>
		<div class="container">
		<H1>Add/View/Edit Item</H1>

<?php

switch ($_SERVER['REQUEST_METHOD']) {
	case 'POST':
		// Handle form submission
		
		if(!isset($_POST['id'])) {
			// Not a valid form submission;  abort script
			die('Invalid form submission.');
		};
				
		function maxlen($str, $len) {
			// Performs an ltrim and rtrim on $str, then chops it down to a maximum of $len characters
			$str = trim($str);
			if(strlen($str)>$len) {
				return(substr($str, 0, $len));
			} else {
				return($str);
			}
		}
		
		function toFloat($str) {
			// Takes any string and strips anything not a decimal point or valid number
			return floatval(preg_replace('/[^\d.]/', '', $str));
		}
		
		function mkDate($str) {
			// Returns an empty value if trim($str) resolves to empty value, otherwise formats a date for MySQL
			if(trim($str)=='') {
				return '';
			} else {
				return date('Y-m-d', strtotime(trim($str)));
			};
		}
		
		// Massage the data while loading it into an array
		
		$attributes = array(
			'shortName' => maxlen($_POST['shortName'],64),
			'make' => maxlen($_POST['make'],32),
			'model' => maxlen($_POST['model'],32),
			'serial' => maxlen($_POST['serial'],32),
			'upc' => maxlen($_POST['upc'],32),
			'purchaseDate' => mkDate($_POST['purchaseDate']),
			'purchasePrice' => toFloat($_POST['purchasePrice']),
			'warrantyExp' => mkDate($_POST['warrantyExp']),
			'saleValue' => toFloat($_POST['saleValue']),
			'replacementValue' => toFloat($_POST['replacementValue']),
			'valueDate' => mkDate($_POST['valueDate']),
			'url' => maxlen($_POST['url'],255),
			'amazonASIN' => maxlen($_POST['amazonASIN'],10),
			'notes' => maxlen($_POST['notes'],65535)
		);

		if(!isset($_POST['itemImg'])) {
			$attributes['itemImg']=null;
		} else {
			// Use the selected attachment as primary item image unless image is selected for deletion
			if(isset($_POST['attachment_delete']) && ( array_key_exists($_POST['itemImg'], $_POST['attachment_delete']))) {
				$attributes['itemImg'] = null;
			} else {
				$attributes['itemImg'] = $_POST['itemImg'];
			};
		};
		
		if(!isset($_POST['receiptImg'])) {
			$attributes['receiptImg'] = null;
		} else {
			// Use the selected attachment as receipt image unless image is selected for deletion
			if(isset($_POST['attachment_delete']) && ( array_key_exists($_POST['receiptImg'], $_POST['attachment_delete']))) {
				$attributes['receiptImg'] = null;
			} else {
				$attributes['receiptImg'] = $_POST['receiptImg'];				
			};
		};

		$inv = new Inventory();
		
		if(isset($_POST['id'])) {
			
			if($_POST['id']=='') {
				// build new and retrieve ID
				$item = null;
				$item = $inv->newItem();
				$id = $item->id();
			} else {
				$item = $inv->getItem($_POST['id']);
				if($item == false) {
					die('<div class="alert alert-danger" role="alert">
	<span class="glyphicon glyphicon-exclamation-sign"></span> Invalid Item ID </div>');
				};				
			}
			
		}
		
		$item->setAttributes($attributes);
		$id = $item->getAttribute('id');
		
		// Deal with new attachments
		if(isset($_FILES['attachments'])) {
			foreach ($_FILES['attachments']['error'] as $key=>$error) {
				if($error == UPLOAD_ERR_OK) {
					$item -> addAttachment($_FILES['attachments']['tmp_name'][$key], $_FILES['attachments']['name'][$key]);
				};
			};
		};
		
		// Deal with updates to existing attachments
		if(isset($_POST['attachment_shortName'])) {
			$existingAttachmentIds = array_keys($_POST['attachment_shortName']);
			foreach($existingAttachmentIds as $attachmentId) {
				$item->getAttachment($attachmentId)->setAttribute('shortName', $_POST['attachment_shortName'][$attachmentId]);
			}
		};
		
		// Deal with deletions to existing attachments
		if(isset($_POST['attachment_delete'])) {
			$existingAttachmentIds = array_keys($_POST['attachment_delete']);
			foreach($existingAttachmentIds as $attachmentId) {
				$item->deleteAttachment($attachmentId);
			}
		};
		
		// Take care of the location
		if((isset($_POST['location'])) && (trim($_POST['location'])!=='')) {
			$loc = $inv->matchLocation($_POST['location']);
			if(!$loc) {
				// Location match not found, make a new one
				$newloc = $inv->newLocation(null);
				$newloc->setAttribute('shortName',trim($_POST['location']));
				$item->setAttribute('location', $newloc->id());
			} else {
				$item->setAttribute('location', $loc->id());
			};
		} else {
			// assign to default location
			$item->setAttribute('location', $inv->matchLocation('* Default'));
		};
		
		// Take care of the group

		if((isset($_POST['group'])) && (trim($_POST['group'])!=='')) {
			$grp = $inv->matchGroup($_POST['group']);
			if(!$grp) {
				// Group match not found, make a new one
				$newgrp = $inv->newGroup(null);
				$newgrp->setAttribute('shortName',trim($_POST['group']));
				$item->setAttribute('group', $newgrp->id());
			} else {
				$item->setAttribute('group', $grp->id());
			};
		} else {
			// assign to default group
			
			$item->setAttribute('group', $inv->matchGroup('* Default'));
		};
				
		// Refresh attributes
		$attributes = $item->getAttributes();
		
		if(isset($_POST['Delete'])) {
			// User clicked Delete button; show a confirmation
			$hiddenFields = '';
			foreach(array_keys($attributes) as $thiskey) {
				if(($thiskey!=='group') && ($thiskey!=='location')) {
					$hiddenFields = $hiddenFields . <<<EOT
<INPUT TYPE="HIDDEN" NAME="{$thiskey}" ID="{$thiskey}" VALUE="{$attributes[$thiskey]}"/>
EOT;
				};
			};
			$hiddenFields = $hiddenFields . '<INPUT TYPE="HIDDEN" NAME="group" ID="group" VALUE="'.$inv->getGroup($attributes['group'])->getAttribute('shortName').'"/>';
			$hiddenFields = $hiddenFields . '<INPUT TYPE="HIDDEN" NAME="location" ID="location" VALUE="'.$inv->getLocation($attributes['location'])->getAttribute('shortName').'"/>';
			$output = <<<EOT
<FORM METHOD="POST" ACTION="item.php">
<div class="alert alert-danger" role="alert">
	<span class="glyphicon glyphicon-exclamation-sign"></span> Do you really want to delete the folowing items?
	<BR/>
{$attributes['shortName']}</div>
<INPUT TYPE="SUBMIT" NAME="Delete_Confirm" ID="Delete_Confirm" VALUE="Yes" class="btn btn-danger" />
<INPUT TYPE="SUBMIT" NAME="Delete_Cancel" ID="Delete_Cancel" VALUE="No" class="btn btn-default"/>
{$hiddenFields}
</FORM>
EOT;
			die($output);
		};
		
		if(isset($_POST['Delete_Confirm'])) {
			// User confirmed delete
			$inv->deleteItem($_POST['id']);
			echo('<div class="alert alert-success" role="alert"> <span class="glyphicon glyphicon-ok"></span> Item Successfully Deleted </div> ');
			$item = new NullItem();
			$attributes = $item->getAttributes();
			$id = $attributes['id'];
			$output = '<H1>Add Item</H1>';
			$showDeleteBtn=false;
		};
		
		// Do default output, since user isn't deleting and we have actually saved any changes
		if($item->isValid()) {
			echo('<div class="alert alert-success" role="alert">
					<span class="glyphicon glyphicon-ok"></span> Item Successfully Saved
					</div>');
		};
		
		$showDeleteBtn=true;

		break;
		
	case 'GET':
		$inv = new Inventory();
		if((isset($_GET['id'])) && ($_GET['id']!=='')) {
			// Load an existing item for editing
			$item = $inv -> getItem($_GET['id']);
			if(!$item) {
				die('<div class="alert alert-danger" role="alert">
	<span class="glyphicon glyphicon-exclamation-sign"></span> Invalid Item ID </div>');
			} else {
				$attributes = $item->getAttributes();
				$id = $attributes['id'];
				$output = '<H1>View/Edit Item</H1>';
				$showDeleteBtn = true;
			};

		} else {
			$item = new NullItem();
			$attributes = $item->getAttributes();
			$id = $attributes['id'];
			$output = '<H1>Add Item</H1>';
			$showDeleteBtn=false;
		}

		break;
		
	default:
		die('<div class="alert alert-danger" role="alert">
	<span class="glyphicon glyphicon-exclamation-sign"></span> Invalid request method </div>');
		break;
};
		
function mkPrettyDate($str) {
	// Returns an empty value if trim($str) resolves to empty value, otherwise formats a date for display
	if((trim($str)=='') || (trim($str)=='0000-00-00')) {
		return '';
	} else {
		return date('m/d/Y', strtotime(trim($str)));
	};
}

function mkPrettyDollars($str) {
	// Returns an empty value if trim($str) resolves to an empty value, otherwise formats a dollar amount for display
	if((trim($str)=='') || ($str==0)) {
		return '';
	} else {
		return money_format("%.2n",$str);
	};
}

$attachments = $item->getAttachments();
if(sizeof($attachments)==0) {
	$existingAttachments = '';
} else {
	$existingAttachments = '<LABEL>Existing Attachments:</LABEL><DIV STYLE="clear:both"></DIV>';
	$displayWidth = 128;
	foreach($attachments as $attachment) {
		if ($attachment->hasThumbnail()) {
			// Thumbnail exists, use that
			$displayHeight = ($displayWidth / $attachment->getAttribute('imgWidth')) * $attachment->getAttribute('imgHeight');
			$filebase64 = $attachment->getThumbBase64(BASE64_INLINE_IMG);
		} else {
			if($attachment->getAttribute('isImg')) {
				// No thumbnail exists, but this is an image, so use the source image
				$displayHeight = ($displayWidth / $attachment->getAttribute('imgWidth')) * $attachment->getAttribute('imgHeight');
				$filebase64 = $attachment->getFileBase64(BASE64_INLINE_IMG);
			} else {
				// No thumbnail and not an image, so use generic paperclip image
				$displayHeight = $displayWidth;
				$filebase64 = 'data:image/png;base64,' . base64_encode(file_get_contents('attach.png'));
			};
		};
		$existingAttachments = $existingAttachments . <<<EOT
<DIV><A HREF="dl.php?id={$item->getAttribute('id')}&attachment={$attachment->getAttribute('id')}" TARGET="_blank"><IMG SRC="{$filebase64}" WIDTH={$displayWidth} HEIGHT={$displayHeight} BORDER=1/></A></DIV>
EOT;
		if($item->getAttribute('receiptImg')==$attachment->getAttribute('id')) {
			$receiptImgChecked=" CHECKED";
		} else {
			$receiptImgChecked="";
		};
		
		if($item->getAttribute('itemImg')==$attachment->getAttribute('id')) {
			$itemImgChecked=" CHECKED";
		} else {
			$itemImgChecked="";
		};

		$existingAttachments = $existingAttachments.<<<EOT
<DIV CLASS="formField">Attachment description: <INPUT TYPE="TEXT" NAME="attachment_shortName[{$attachment->getAttribute('id')}]" ID="attachment_shortName[{$attachment->getAttribute('id')}]" VALUE="{$attachment->getAttribute('shortName')}"/><INPUT TYPE="CHECKBOX" NAME="attachment_delete[{$attachment->getAttribute('id')}]" ID="attachment_delete[{$attachment->getAttribute('id')}]" VALUE=1> Delete Attachment<BR/><INPUT TYPE="RADIO" NAME="itemImg" ID="itemImg" VALUE="{$attachment->getAttribute('id')}" {$itemImgChecked}/>Set as primary item image<BR/><INPUT TYPE="RADIO" NAME="receiptImg" ID="receiptImg" VALUE="{$attachment->getAttribute('id')}" {$receiptImgChecked}/>Set as primary receipt image</DIV>
EOT;
	};
	$existingAttachments = $existingAttachments . '<DIV STYLE="clear: both"></DIV>';
}

setlocale(LC_MONETARY, 'en_US');
$purchasePrice = mkPrettyDollars($attributes['purchasePrice']);
$saleValue = mkPrettyDollars($attributes['saleValue']);
$replacementValue = mkPrettyDollars($attributes['replacementValue']);
$purchaseDate = mkPrettyDate($attributes['purchaseDate']);
$warrantyExp = mkPrettyDate($attributes['warrantyExp']);
$valueDate = mkPrettyDate($attributes['valueDate']);

$locations = $inv -> allLocations();
$locationOptions = '';
foreach($locations as $location) {
	$locationName = $location->getAttribute('shortName');
	if($location->id() == $item->getAttribute('location')) {
		$selected = 'SELECTED';
	} else {
		$selected = '';
	};
	$locationOptions = $locationOptions . <<<EOT
<OPTION VALUE="{$locationName}" {$selected}>{$locationName}</OPTION>
EOT;
};


$groups = $inv -> allGroups();
$groupOptions = '';
foreach($groups as $group) {
	$groupName = $group->getAttribute('shortName');
	if($group->id() == $item->getAttribute('group')) {
		$selected = 'SELECTED';
	} else {
		$selected = '';
	};
	$groupOptions = $groupOptions . <<<EOT
<OPTION VALUE="{$groupName}" {$selected}>{$groupName}</OPTION>
EOT;
};

$output = <<<EOD
<FORM METHOD="POST" ENCTYPE="multipart/form-data" ACTION="item.php" ID="itemForm">
	<INPUT TYPE="hidden" NAME="id" ID="id" VALUE="{$id}"/>

	<div class="form-group">
	<LABEL FOR="shortName">Short Name (or Title):</LABEL>
	<INPUT TYPE="TEXT" NAME="shortName" ID="shortName" MAXLENGTH=64 VALUE="{$attributes['shortName']}"/>
	</div>
	
	<div class="form-group">
	<LABEL FOR="make">Make (or Author):</LABEL>
	<INPUT TYPE="TEXT" NAME="make" ID="make" MAXLENGTH=32 VALUE="{$attributes['make']}"/>
	</div>
	
	<div class="form-group">
	<LABEL FOR="model">Model (or Edition):</LABEL>
	<INPUT TYPE="TEXT" NAME="model" ID="model" VALUE="{$attributes['model']}"/>
	</div>
	
	<div class="form-group">
	<LABEL FOR="serial">Serial:</LABEL>
	<INPUT TYPE="TEXT" NAME="serial" ID="serial" VALUE="{$attributes['serial']}"/>
	</div>
	
	<div class="form-group">
	<LABEL FOR="upc">UPC (or ISBN): <A HREF="javascript:amazonSearch('upc')">Amazon</A> | <A HREF="javascript:googleSearch('upc')">Google</A></LABEL>
	<INPUT TYPE="TEXT" NAME="upc" ID="upc" VALUE="{$attributes['upc']}"/>
	</div>

	<div class="form-group">
	<LABEL FOR="purchaseDate">Purchase Date:</LABEL>
	<INPUT TYPE="TEXT" NAME="purchaseDate" ID="purchaseDate" VALUE="{$purchaseDate}"/>
	</div>

	<div class="form-group">
	<LABEL FOR="purchasePrice">Purchase Price:</LABEL>
	<INPUT TYPE="TEXT" NAME="purchasePrice" ID="purchasePrice" VALUE="{$purchasePrice}"/>
	</div>
	
	<div class="form-group">
	<LABEL FOR="warrantyExp">Warranty Expiration:</LABEL>
	<INPUT TYPE="TEXT" NAME="warrantyExp" ID="warrantyExp" VALUE="{$warrantyExp}"/>
	</div>
	
	<div class="form-group">
	<LABEL FOR="saleValue">Current Sale Value:</LABEL>
	<INPUT TYPE="TEXT" NAME="saleValue" ID="saleValue" VALUE="{$saleValue}"/>
	</div>
	
	<div class="form-group">
	<LABEL FOR="replacementValue">Current Replacement Value:</LABEL>
	<INPUT TYPE="TEXT" NAME="replacementValue" ID="replacementValue" VALUE="{$replacementValue}"/>
	</div>
	
	<div class="form-group">
	<LABEL FOR="valueDate">Values as of Date: <A HREF="javascript:getDate('valueDate')">Today</A></LABEL>
	<INPUT TYPE="TEXT" NAME="valueDate" ID="valueDate" VALUE="{$valueDate}"/>
	</div>
	
	<div class="form-group">
	<LABEL FOR="url">URL: <A HREF="javascript:viewURL('url')">View</A></LABEL>
	<INPUT TYPE="TEXT" NAME="url" ID="url" VALUE="{$attributes['url']}"/>
	</div>

	<div class="form-group">
	<LABEL FOR="amazonASIN">Amazon ASIN: <A HREF="javascript:viewASIN('amazonASIN')">View</A></LABEL>
	<INPUT TYPE="TEXT" NAME="amazonASIN" ID="amazonASIN" VALUE="{$attributes['amazonASIN']}"/>
	</div>

	<div class="form-group">
	<LABEL FOR="location">Location:</LABEL>
	<SELECT NAME="location" onChange="getNewLocation('location')" ID="location">{$locationOptions}
	<option value="">Other</option>
	<OPTION VALUE="">Create new...</OPTION>
	</SELECT>
	</div>
	
	<div class="form-group">
	<LABEL FOR="group">Group:</LABEL>
	<SELECT NAME="group" onChange="getNewGroup('group')" ID="group">{$groupOptions}
	<option value="">Other</option>
	<OPTION VALUE="">Create new...</OPTION>
	</SELECT>
	</div>
	
	<div class="form-group">

	<TEXTAREA NAME="notes" ID="notes" rows="5">{$attributes['notes']}Notes...</TEXTAREA>
	</div>
	
	{$existingAttachments}			
	
	<div class="form-group">	
	<LABEL FOR="attachments">Add Attachments:</LABEL>
	<DIV ID="attach">
		<INPUT TYPE="HIDDEN" NAME="MAX_FILE_SIZE" VALUE="16777216"/>
		<INPUT TYPE="FILE" NAME="attachments[]"/>
		<INPUT TYPE="FILE" NAME="attachments[]"/>
		<INPUT TYPE="FILE" NAME="attachments[]"/>
		<INPUT TYPE="FILE" NAME="attachments[]"/>
		<INPUT TYPE="FILE" NAME="attachments[]"/>
	</DIV>
	</div>	
		
	<INPUT TYPE="SUBMIT" NAME="Save" VALUE="Save" ID="Save" class="btn btn-default"/>
EOD;

if($showDeleteBtn) {
	$output = $output . <<<EOD
		<INPUT TYPE="SUBMIT" NAME="Delete" VALUE="Delete Item" ID="Delete" class="btn btn-danger"/>
EOD;
};
$output = $output . <<<EOD
		<INPUT TYPE="RESET" NAME="Reset" VALUE="Reset" ID="Reset" onClick="form.shortName.focus()" class="btn btn-warning"/>
</FORM>
EOD;
echo $output;

?>
<?php include('footer.php');?>

		</div>
	<SCRIPT SRC="dropdowns.js"> </SCRIPT>
	<SCRIPT SRC="itemlinkage.js"> </SCRIPT>
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="src/less/bootstrap/dist/js/bootstrap.js"></script>

	</BODY>
</HTML>
