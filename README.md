# PHP Home Inventory #

Maintained by Steve Crow  
[scrow@sdf.org][1]  
[http://scrow.sdf.org/][2]  
[http://github.com/scrow/php-inventory][3]  

PHP Home Inventory is a simple personal inventory tracking system.  In its current form, items can be created and assigned to a single group and a single location.  Each item record can contain multiple attachments of any file format.  If ImageMagick and the PHP Imagick extension are available on the system, thumbnail images will be generated and displayed with the item record.  Fields are provided for recording purchase date and amount, manufacturer, model number, serial number, current resale and replacement values, UPC/ISBN, Amazon ASIN, and free-form notes.

This work is licensed under GPL v2.  For more information, see the `LICENSE.md` file.

## Installation ##

There are two ways to run PHP-Inventory.  You can download it to your own web server and manually install the databases, or you can use Vagrant.

### Using Vagrant ###

Ensure [Vagrant][4] and [VirtualBox][5] are installed on your system.

Clone the project, copy the sample configuration file, and launch Vagrant:

	git clone https://github.com/scrow/php-inventory.git php-inventory
	cd php-inventory
	cp config.inc.php.SAMPLE config.inc.php
	vagrant up
	
It will take several minutes to download and perform initial configuration on the Vagrant instance.  Once this process is complete, you can access PHP-Inventory via your web browser, usually at [http://localhost:8080][6].

### Manual Setup ###

PHP-Inventory requires PHP 5 or newer with PDO MySQL support enabled, and a MySQL server.  Backup capabilities are currently dependent on the `zip` and `unzip` commands being accessible via direct shell call (PHP `shell_exec()`).  Thumbnail generation requires ImageMagick and the PHP Imagick extension.

`cd` to the desired installation point and clone the project, then copy the sample configuration and edit `config.inc.php` to match your MySQL server configuration.  Then import the included MySQL schema to create the tables.

	git clone https://github.com/scrow/php-inventory.git .
	cp config.inc.php.SAMPLE config.inc.php
	mysql -uUSER -pPASSWORD << tables.sql

Optionally (and recommended for public servers) password protect the inventory service by creating an `.htpasswd` file.

## Usage ##

### Creating an Item ###

Creating an item is as simple as clicking the "Add Item" link on the main menu and providing as little or as much information as desired.

### Working with Groups and Locations ###

Groups are simply categories of items, such as furnishings, home entertainment equipment, bedding, cookware, appliances, etc.  Locations represent the physical location at which the items are stored.  You can get as detailed as possible or simply keep everything under the "Default" group and/or location.

Groups and locations are automatically deleted when there are no longer any items associated with them.  Thus, to remove a group or location, simply move all of its items to another group or location.  This may be an existing group/location or a new one.  Moving all items from one group or location to newly-created one has the same effect as renaming the original group or location.

### Attachments ###

An unlimited number of attachments may be uploaded for each item, up to five at a time, subject to the web server's upload limits.  If using the bundled Vagrant configuration, the default limit is 2MB.  For Apache web servers, including the one used by the included Vagrant configuration, this limit can be changed using an `.htaccess` file, for example:

	php_value post_max_size 32M
	php_value upload_max_filesize 32M

For more information, see the [Apache HTTP Server Tutorial: .htaccess files][7].

Other web servers may require direct manipulation of the `php.ini` or other configuration file(s).

For each uploaded attachment, a hash is generated.  This hash is used to identify the attachment in the database as well as the file on the disk.  If the same file is uploaded for multiple items, the hashes will match and no new copy will be saved to disk, providing a mechanism for data de-duplication.  Files are not removed from the disk until no more items exist which reference the attachment's hash.

Thumbnails are generated for PDF and image files, provided the PHP Imagick extension, along with the requisite ImageMagick, are installed on the server.  These tools are provided by Vagrant when using the included `Vagrantfile` and `Vagrant/bootstrap.sh`.

### Calculating Item Value ###

For reporting purposes, the following methodology is used to determine an item's inventory value:

1.  If a "Current Replacement Value" is provided, this is the only factor used to determine item value.
2.  If no "Current Replacement Value" is provided, the greater of Purchase Price or Current Sale Value will be used.

If none of these are provided, the value is assumed at $0.00.

## Usage ##

### Creating an Item ###

Creating an item is as simple as clicking the "Add Item" link on the main menu and providing as little or as much information as desired.

### Working with Groups and Locations ###

Groups are simply categories of items, such as furnishings, home entertainment equipment, bedding, cookware, appliances, etc.  Locations represent the physical location at which the items are stored.  You can get as detailed as possible or simply keep everything under the "Default" group and/or location.

Groups and locations are automatically deleted when there are no longer any items associated with them.  Thus, to remove a group or location, simply move all of its items to another group or location.  This may be an existing group/location or a new one.  Moving all items from one group or location to newly-created one has the same effect as renaming the original group or location.

### Attachments ###

An unlimited number of attachments may be uploaded for each item, up to five at a time, subject to the web server's upload limits.  If using the bundled Vagrant configuration, the default limit is 2MB.  For Apache web servers, including the one used by the included Vagrant configuration, this limit can be changed using an `.htaccess` file, for example:

	php_value post_max_size 32M
	php_value upload_max_filesize 32M

For more information, see the [Apache HTTP Server Tutorial: .htaccess files][7].

Other web servers may require direct manipulation of the `php.ini` or other configuration file(s).

For each uploaded attachment, a hash is generated.  This hash is used to identify the attachment in the database as well as the file on the disk.  If the same file is uploaded for multiple items, the hashes will match and no new copy will be saved to disk, providing a mechanism for data de-duplication.  Files are not removed from the disk until no more items exist which reference the attachment's hash.

Thumbnails are generated for PDF and image files, provided the PHP Imagick extension, along with the requisite ImageMagick, are installed on the server.  These tools are provided by Vagrant when using the included `Vagrantfile` and `Vagrant/bootstrap.sh`.

### Calculating Item Value ###

For reporting purposes, the following methodology is used to determine an item's inventory value:

1.  If a "Current Replacement Value" is provided, this is the only factor used to determine item value.
2.  If no "Current Replacement Value" is provided, the greater of Purchase Price or Current Sale Value will be used.

If none of these are provided, the value is assumed at $0.00.

## Current Limitations ##

Below is a quick unordered list of current known limitations of PHP-Inventory.  Inclusion in the list below does not imply any plans to overcome any particular limitation:

 * It's not pretty.  The focus currently is on basic functionality.  Cosmetic improvements will come later.
 
 * Limited reporting capability.  Filtering and report generation (Excel, CSV, etc) may be implemented in the future.

## Issues and Feature Requests ##

Issues and feature requests may be submitted on the [GitHub Issues page][8].

[1]: mailto:scrow@sdf.org
[2]: http://scrow.sdf.org/
[3]: http://github.com/scrow/php-inventory
[4]: http://www.vagrantup.com/
[5]: http://www.virtualbox.org/
[6]: http://localhost:8080/
[7]: http://httpd.apache.org/docs/current/howto/htaccess.html
[8]: https://github.com/scrow/php-inventory/issues
