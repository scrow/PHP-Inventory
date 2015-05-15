# PHP Home Inventory #

Maintained by Steve Crow  
[scrow@sdf.org](mailto:scrow@sdf.org)  
[http://scrow.sdf.org/](http://scrow.sdf.org)  
[http://github.com/scrow/php-inventory](http://github.com/scrow/php-inventory)  

PHP Home Inventory is a simple personal inventory tracking system.  In its current form, items can be created and assigned to a single group and a single location.  Each item record can contain multiple attachments of any file format.  If ImageMagick and the PHP Imagick extension are available on the system, thumbnail images will be generated and displayed with the item record.

This work is licensed under GPL v2.  For more information, see the `LICENSE.md` file.

## Installation ##

There are two ways to run PHP-Inventory.  You can download it to your own web server and manually install the databases, or you can use Vagrant.

### Using Vagrant ###

Ensure [Vagrant](http://www.vagrantup.com/) and [VirtualBox](http://www.virtualbox.org/) are installed on your system.

Clone the project, copy the sample configuration file, and launch Vagrant:

  git clone https://github.com/scrow/php-inventory.git php-inventory
	cd php-inventory
	cp config.inc.php.SAMPLE config.inc.php
	vagrant up
	
It will take several minutes to download and perform initial configuration on the Vagrant instance.  Once this process is complete, you can access PHP-Inventory via your web browser, usually at [http://localhost:8080](http://localhost:8080).

### Manual Setup ###

PHP-Inventory requires PHP 5 or newer with PDO MySQL support enabled, and a MySQL server.  Backup capabilities are currently dependent on the `zip` and `unzip` commands being accessible via direct shell call (PHP `shell_exec()`).  Thumbnail generation requires ImageMagick and the PHP Imagick extension.

`cd` to the desired installation point and clone the project, then copy the sample configuration and edit `config.inc.php` to match your MySQL server configuration.  Then import the included MySQL schema to create the tables.

	git clone https://github.com/scrow/php-inventory.git .
	cp config.inc.php.SAMPLE config.inc.php
	mysql -uUSER -pPASSWORD << tables.sql

Optionally (and recommended for public servers) password protect the inventory service by creating an `.htpasswd` file.

## Current Limitations ##

Below is a quick unordered list of current known limitations of PHP-Inventory.  Inclusion in the list below does not imply any plans to overcome any particular limitation:

 * It's not pretty.  The focus currently is on basic functionality.  Cosmetic improvements will come later.
 
 * No restore functionality.  Currently there is a provision for creating a backup of the database, but not for restoring from that backup.  This will be resolved in a future (next?) version.
 
 * Limited reporting capability.  Filtering and report generation (Excel, CSV, etc) may be implemented in the future.

## Issues and Feature Requests ##

Issues and feature requests may be submitted on the [GitHub Issues page](https://github.com/scrow/php-inventory/issues).
