<?php
	
require_once('classes.inc.php');
require_once('globals.inc.php');

?>

<HTML>
	<HEAD>
		<TITLE>Personal Inventory:  All Items</TITLE>
		<LINK REL="stylesheet" HREF="styles.css"/>
		<SCRIPT SRC="dropdowns.js"></SCRIPT>
	</HEAD>
	<BODY>
		<H1>All Items</H1>

<?php

function mkPrettyDollars($str) {
	// Returns $0.00 if trim($str) resolves to an empty value, otherwise formats a dollar amount for display
	if((trim($str)=='') || ($str==0)) {
		return '$0.00';
	} else {
		return money_format("$%.2n",$str);
	};
};

$inv = new Inventory;

function locationSort($inv, $only = false) {
	$display = array();
	if(!$only) {
		$collections = $inv->allLocations();
	} else {
		$onlyLocation = $inv->getLocation($only);
		if(!$onlyLocation) die('invalid location id');
		$collections = array($onlyLocation);
	};
	foreach($collections as $collection) {
		$display[] = array(
			'name' => $collection->getAttribute('shortName'),
			'items' => $inv->itemsByLocation($collection->id())
			);
	};
	return $display;
};

function groupSort($inv, $only = false) {
	$display = array();
	if(!$only) {
		$collections = $inv->allGroups();
	} else {
		$onlyGroup = $inv->getGroup($only);
		if(!$onlyGroup) die('invalid group id');
		$collections = array($onlyGroup);
	};
	foreach($collections as $collection) {
		$display[] = array(
			'name' => $collection->getAttribute('shortName'),
			'items' => $inv->itemsByGroup($collection->id())
			);
	};
	return $display;
};

switch($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		if(isset($_GET['only'])) {
			$only = $_GET['only'];
			$insert = '<INPUT TYPE="HIDDEN" NAME="only" VALUE="'.$only.'">';
		} else {
			$only = false;
			$insert = '';
		}

		if(isset($_GET['sort'])) {
			switch($_GET['sort']) {
				case 'location':
					$display = locationSort($inv, $only);
					$thirdCol = 'Group';
					$insert = $insert.'<INPUT TYPE="HIDDEN" NAME="sort" VALUE="location"/>';
					break;
				case 'group':
					$display = groupSort($inv, $only);				
					$thirdCol = 'Location';
					$insert = $insert.'<INPUT TYPE="HIDDEN" NAME="sort" VALUE="group"/>';
					break;
				default:
					$display = locationSort($inv, $only);
					$thirdCol = 'Group';
					$insert = $insert.'<INPUT TYPE="HIDDEN" NAME="sort" VALUE="location"/>';
					break;
			};
		} else {
			$display = locationSort($inv, $only);
			$thirdCol = 'Group';
			$insert = $insert.'<INPUT TYPE="HIDDEN" NAME="sort" VALUE="location"/>';
		};
		
		break;
	case 'POST':
		// Handle form submissions
		if(!isset($_POST['id'])) {
			// Not a valid form submission;  show an error
			echo('<P>No items selected.</P>');
		} else {
			
			switch ($_POST['action']) {
				case 'changegroup':
					if((isset($_POST['group'])) && (trim($_POST['group'])!=='')) {
						$grp = $inv->matchGroup($_POST['group']);
						if(!$grp) {
							// Group match not found, make a new one
							$newgrp = $inv->newGroup(null);
							$newgrp->setAttribute('shortName',trim($_POST['group']));
							$grpid = $newgrp->id();
						} else {
							$grpid = $grp->id();
						};
					};
			
					foreach(array_keys($_POST['id']) as $id) {
						$item = $inv->getItem($id);
						$item->setAttribute('group', $grpid);
					}; // end foreach
					
					$inv->cleanup();
										
					break;
				case 'changelocation':
					if((isset($_POST['location'])) && (trim($_POST['location'])!=='')) {
						$loc = $inv->matchLocation($_POST['location']);
						if(!$loc) {
							// Group match not found, make a new one
							$newloc = $inv->newLocation(null);
							$newloc->setAttribute('shortName',trim($_POST['location']));
							$locid = $newloc->id();
						} else {
							$locid = $loc->id();
						};
					};
			
					foreach(array_keys($_POST['id']) as $id) {
						$item = $inv->getItem($id);
						$item->setAttribute('location', $locid);
					}; // end foreach

					$inv->cleanup();

					break;
				case 'delete':
					$output = <<<EOT
<P>Really delete these items?
EOT;
					foreach(array_keys($_POST['id']) as $id) {
						$item = $inv->getItem($id);
						$output = $output . <<<EOT
<BR/>{$item->getAttribute('shortName')}
EOT;
					};

					$output = $output . <<<EOT
</P><FORM METHOD="POST" ACTION="allitems.php" NAME="form" ID="form">
EOT;

					foreach(array_keys($_POST['id']) as $id) {
						$output = $output . <<<EOT
<INPUT TYPE="HIDDEN" NAME="id[$id]" ID="id[$id]" VALUE="$id"/>
EOT;
					};
					
					if(isset($_POST['sort'])) {
						$output = $output . <<<EOT
<INPUT TYPE="HIDDEN" NAME="sort" ID="sort" VALUE="{$_POST['sort']}"/>
EOT;
					};
					
					if(isset($_POST['only'])) {
						$output = $output . <<<EOT
<INPUT TYPE="HIDDEN" NAME="only" ID="only" VALUE="{$_POST['only']}"/>
EOT;
					};
					
					$output = $output . <<<EOT
<INPUT TYPE="HIDDEN" NAME="action" ID="action" VALUE="deleteconfirm"/>
<INPUT TYPE="SUBMIT" NAME="submit" ID="submit" VALUE="Delete">
<INPUT TYPE="BUTTON" NAME="cancel" ID="cancel" VALUE="Cancel" onClick="javascript:history(-1)">
EOT;
					die($output);

					break;
				case 'deleteconfirm':
				
					foreach(array_keys($_POST['id']) as $id) {
						$inv -> deleteItem($id);
					};
								
					break;
			}; // end switch
			
		}; // end if

		// Prepare for display
		if(isset($_POST['only'])) {
			$only = $_POST['only'];
			$insert = '<INPUT TYPE="HIDDEN" NAME="only" VALUE="'.$only.'">';
		} else {
			$only = false;
			$insert = '';
		}

		if(isset($_POST['sort'])) {
			switch($_POST['sort']) {
				case 'location':
					$display = locationSort($inv, $only);
					$thirdCol = 'Group';
					$insert = $insert.'<INPUT TYPE="HIDDEN" NAME="sort" VALUE="location"/>';
					break;
				case 'group':
					$display = groupSort($inv, $only);				
					$thirdCol = 'Location';
					$insert = $insert.'<INPUT TYPE="HIDDEN" NAME="sort" VALUE="group"/>';
					break;
				default:
					$display = locationSort($inv, $only);
					$thirdCol = 'Group';
					$insert = $insert.'<INPUT TYPE="HIDDEN" NAME="sort" VALUE="location"/>';
					break;
			};
		} else {
			$display = locationSort($inv, $only);
			$thirdCol = 'Group';
			$insert = $insert.'<INPUT TYPE="HIDDEN" NAME="sort" VALUE="location"/>';
		};

		break;
	default:
		die('Invalid request method');
		break;
};

// Display the items

$locations = $inv -> allLocations();
$locationOptions = '';
foreach($locations as $location) {
	$locationName = $location->getAttribute('shortName');
	$locationOptions = $locationOptions . <<<EOT
<OPTION VALUE="{$locationName}">{$locationName}</OPTION>
EOT;
};


$groups = $inv -> allGroups();
$groupOptions = '';
foreach($groups as $group) {
	$groupName = $group->getAttribute('shortName');
	$groupOptions = $groupOptions . <<<EOT
<OPTION VALUE="{$groupName}">{$groupName}</OPTION>
EOT;
};

$totalvalue=0;

$output = <<<EOT
<TABLE WIDTH=100% CELLSPACING=2 CELLPADDING=1 BORDER=1>	
<FORM METHOD="POST" ACTION="allitems.php" NAME="form" ID="form">
EOT;
echo $output;
foreach($display as $collection) {
	$contains = $collection['items'];

	if(sizeof($contains) > 0) {
		// Only display a section if it contains items
		
		// Initialize subtotal variable
		$subtotal = 0;
		
		$output = <<<EOT
<TR><TH COLSPAN=5>&nbsp;<BR/>{$collection['name']}<BR/>&nbsp;</TH></TR>
<TR><TH>Select</TH><TH>Name (Model, Serial)</TH><TH>{$thirdCol}</TH><TH>Key Files</TH><TH>Value</TH></TR>
EOT;
		echo($output);
	
		foreach ($contains as $item) {
			// Use the greater of replacementValue and saleValue
			if($item->getAttribute('replacementValue')==0) {
				$value = max(array($item->getAttribute('saleValue'), $item->getAttribute('purchasePrice')));
				$currentValue = mkPrettyDollars($value);
				$subtotal = $subtotal + $value;
				$totalvalue = $totalvalue + $value;
			} else {
				$value = $item->getAttribute('replacementValue');
				$currentValue = mkPrettyDollars($value);
				$subtotal = $subtotal + $value;
				$totalvalue = $totalvalue + $value;
			};
			switch ($thirdCol) {
				case 'Group':
					$thirdColTD = $inv->getGroup($item->getAttribute('group'))->getAttribute('shortName');
					break;
				case 'Location':
					$thirdColTD = $inv->getLocation($item->getAttribute('location'))->getAttribute('shortName');
					break;
			};
			
			if($item->getAttribute('itemImg')!==null) {
				$itemImg = '<A HREF="dl.php?id='.$item->getAttribute('id').'&attachment='.$item->getAttribute('itemImg').'" TARGET="_blank">Image</A>&nbsp;|&nbsp;';
			} else {
				$itemImg = 'Image&nbsp;|&nbsp;';
			};
			
			if($item->getAttribute('receiptImg')!==null) {
				$receiptImg = '<A HREF="dl.php?id='.$item->getAttribute('id').'&attachment='.$item->getAttribute('receiptImg').'" TARGET="_blank">Receipt</A>';
			} else {
				$receiptImg = 'Receipt';
			};
			
			$extendedInfo = array();
			// Enable the block below to show model numbers along with the serial number
/*			if($item->getAttribute('model')!==null) {
				$extendedInfo[] = $item->getAttribute('model');
			}; */
			if($item->getAttribute('serial')!==null) {
				$extendedInfo[] = '#'.$item->getAttribute('serial');
			};
			if(sizeof($extendedInfo) > 0) {
				$extendedInfo = ' ('.implode(' ', $extendedInfo).')';
			} else {
				$extendedInfo = '';
			};

			
			if($item->getAttribute('shortName')=='') {
				$itemLabel = '(untitled item)';
			} else {
				$itemLabel = $item->getAttribute('shortName');
			};
			
			$output = <<<EOT
<TR><TD><INPUT TYPE="CHECKBOX" NAME="id[{$item->getAttribute('id')}]" ID="id[{$item->getAttribute('id')}]"/></TD><TD><A HREF="item.php?id={$item->getAttribute('id')}">{$itemLabel}</A>$extendedInfo</TD>
<TD>$thirdColTD</TD><TD>{$itemImg}{$receiptImg}</TD><TD>$currentValue</TD></TR>
EOT;
			echo($output);
		};
		$subtotalText = mkPrettyDollars($subtotal);
		$output = <<<EOT
<TR><TD COLSPAN=4>&nbsp;</TD><TD>$subtotalText</TD></TR>
EOT;
		echo($output);
	};
};
$totalvalueText = mkPrettyDollars($totalvalue);
$output = <<<EOT
	</TABLE>
	<P>Total inventory value: $totalvalueText</P>
	<DIV CLASS="formControlBlock">
	<INPUT TYPE="RADIO" NAME="action" ID="action" VALUE="" CHECKED/> No action
	<BR/><INPUT TYPE="RADIO" NAME="action" ID="action" VALUE="delete"/> Delete Selected Items
	<BR/><DIV CLASS="comboLabel"><INPUT TYPE="RADIO" NAME="action" ID="action1" VALUE="changegroup"/> Assign to group:</DIV><SELECT NAME="group" onChange="getNewGroup('group')" ID="group" CLASS="comboDropdown" onClick="javascript:document.getElementById('action1').checked=true">{$groupOptions}<OPTION VALUE="">Create new...</OPTION></SELECT>
	<BR/><DIV CLASS="comboLabel"><INPUT TYPE="RADIO" NAME="action" ID="action2" VALUE="changelocation"/> Move to location:</DIV><SELECT NAME="location" onChange="getNewLocation('location')" ID="location" CLASS="comboDropdown" onClick="javascript:document.getElementById('action2').checked=true">{$locationOptions}<OPTION VALUE="">Create new...</OPTION></SELECT>
	<BR/>$insert
	<INPUT TYPE="SUBMIT" NAME="submit" ID="submit" VALUE="Submit"/>
	<INPUT TYPE="RESET" NAME="reset" ID="reset" VALUE="Reset"/>
	</DIV>
	</FORM>
EOT;
echo $output;
?>
<?php include('footer.php');?>
	</BODY>
</HTML>

	
