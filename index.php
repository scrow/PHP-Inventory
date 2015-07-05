<?php
require_once('classes.inc.php');
require_once('globals.inc.php');

?>

<HTML>
	<HEAD>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<TITLE>Personal Inventory - Main</TITLE>
		<LINK REL="stylesheet" href="src/less/bootstrap/dist/css/bootstrap.css"
		<link rel="stylesheet" href="src/less/bootstrap/dist/css/bootstrap-theme.css"
	</HEAD>
	<BODY>
	<div class="container">
		<H1>Personal Inventory</H1>

		<P>
			<A HREF="item.php" class="btn btn-lg btn-success" role="button">New Item</A>
			<A HREF="allitems.php" role="button" class="btn btn-lg btn-info">All Items</A>
			<A HREF="backup.php" role="button" class="btn btn-lg btn-danger">Database Backup or Restore</A>
		</P>
	</div>
		
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="src/less/bootstrap/dist/js/bootstrap.js"></script>

	</BODY>
</HTML>
