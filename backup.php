<?php
require_once('classes.inc.php');
?>

<HTML>
	<HEAD>
		<TITLE>Personal Inventory:  Database Backup</TITLE>
		<LINK REL="stylesheet" HREF="styles.css"/>
		<SCRIPT SRC="dropdowns.js"> </SCRIPT>
	</HEAD>
	<BODY>
		<H1>Database Backup</H1>

<?php
$db = Database::getInstance();

file_put_contents('items.csv', $db->export('items'));
file_put_contents('attachments.csv', $db->export('attachments'));
file_put_contents('locations.csv', $db->export('locations'));
file_put_contents('groups.csv', $db->export('groups'));

$zipname = 'backups/inv-'.date('Ymd-His').'.zip';
shell_exec('zip -9o '.$zipname.' items.csv attachments.csv locations.csv groups.csv attachments/* thumbs/*');
unlink('items.csv');
unlink('attachments.csv');
unlink('locations.csv');
unlink('groups.csv');

echo('<P>Database backup complete, <a href="'.$zipname.'">click here to download</A>.</P>');
?>
<?php include('footer.php');?>

	</BODY>
</HTML>
