<?php

/* ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ******************************************************************************************************** */   

require_once('config.inc.php');
require_once('globals.inc.php');

class Database {

	static private $_instance = NULL;
	
	private function __construct() {}
	private function __clone() {}
	
	static function getInstance() {
		if (self::$_instance == NULL) {
			self::$_instance = new Database();
			self::$_instance->dbConnect();
		}
		return self::$_instance;
	}

	public static $db = null;
	
	protected function dbConnect() {
		$this->db = new PDO( $this->getDsn(), Config::dbUser, Config::dbPass );
	}
	
	protected function dbDisconnect() {
		unset($this->db);
	}
	
	public function getDsn() {
		return ('mysql:dbname=' . trim(Config::dbName) . ';host=' . trim(Config::dbHost)); 
	}
	
	public function export($tablename) {
		// Exports a table to CSV
		// Returns:  CSV formatted file
		
		// Get column names into $columnList[]
		$columns = $this->db->query('SHOW COLUMNS IN '.$tablename)->fetchAll();
		$columnList = array();
		foreach($columns as $thiscol) {
			$columnList[] = $thiscol[0];
		};
				
		$headerLabels = array();
		foreach($columnList as $thiscol) {
			$headerLabels[] = '"'.addslashes($thiscol).'"';
		};

		$datafile = array();
		$datafile[] = implode(',', $headerLabels);

		// Get records into $records[]
		$records = $this->db->query('SELECT * FROM '.$tablename)->fetchAll();
		foreach($records as $thisrec) {
			$line = array();
			foreach($columnList as $thiscol) {
				$line[] = '"'.addslashes($thisrec[$thiscol]).'"';
			};
			$datafile[] = implode(',', $line);
		};

		// Glue the file together
		$output = implode("\n", $datafile);
		return($output);
	}
	
	public function import($tablename, $source) {
		// Imports a table from CSV
		// Accepts:  Table name as $tablename, CSV file contents as $source
		// Returns:  true if successful, false if an error occurred
		
		// Split the data file into an array
		$datafile = explode("\n", $source);
		
		// Make sure we have at least two lines; if not, abort and return false
		if(sizeof($datafile) < 2) {
			return false;
		};
		
		// Quick callback function
		function cleanupHeaders(&$input) {
			$input = trim(stripslashes($input),'"');
		};

		// Extract and clean up the header row
		$headers = explode(',', $datafile[0]);
		array_walk($headers, 'cleanupHeaders');
		array_shift($datafile);
		
		// Verify each column header exists in the destination table
		$columns = $this->db->query('SHOW COLUMNS IN '.$tablename)->fetchAll();
		$columnList = array();
		foreach($columns as $thiscol) {
			$columnList[] = $thiscol[0];
		};
		if(sizeof(array_intersect($headers, $columnList)) !== sizeof($headers)) {
			// One or more headers not found; abort and return false
			return false;
		};

		// Walk through the file and do the import
		foreach($datafile as $line) {
			// Logic on the next line avoids warning conditions on blank lines (near EOF, etc)
			if(sizeof(explode("\n",$line)) > 1) {
				$prepare_array = array();
				$execute_array = array();
				
				$thisline = explode(',', $line);
				array_walk($thisline, 'cleanupheaders');
				print_r($thisline);
				
				$idx = 0;
				foreach($headers as $thiskey) {
					$prepare_array[$thiskey] = $tablename . '.' .$thiskey.'=:'.$thiskey;
					if($thisline[$idx]=="") {
						// Properly handle a null value
						$execute_array[$thiskey] = null;
					} else {
						$execute_array[$thiskey] = $thisline[$idx];					
					};
					$idx++;
				};
				$st = $this->db->prepare('UPDATE ' . $tablename . ' SET ' . implode(', ', $prepare_array). ' WHERE id=:id');
				$st -> execute($execute_array);
			};
		};
	}
}

/* ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ******************************************************************************************************** */   

/**
 * 
 * Generic Inventory Class
 *
 */

class Inventory extends Database {
	protected $attributes = array();

	protected $items = array();
	protected $locations = array();
	protected $groups = array();

	private $modified = false;
	
	public function __construct() {
		// Load all items into $this->items[]
		$connection = Database::getInstance();
		
		$this->items = array();
		$st = $connection->db->query('SELECT id FROM items ORDER BY shortName');
		while ($record = $st->fetch(PDO::FETCH_ASSOC)) {
			$item = new Item($record['id']);
			$this->items[$record['id']] = $item;
		};
		
		// Load all locations into $this->locations[]
		$this->locations = array();
		$st = $connection->db->query('SELECT id FROM locations WHERE TRIM(shortName) IS NOT NULL ORDER BY shortName');
		while ($record = $st->fetch(PDO::FETCH_ASSOC)) {
			$location = new Location($record['id']);
			$this->locations[$record['id']] = $location;
		};
		
		// Load all groups into $this->groups[]
		$this->groups = array();
		$st = $connection->db->query('SELECT id FROM groups WHERE TRIM(shortName) IS NOT NULL ORDER BY shortName');
		while ($record = $st->fetch(PDO::FETCH_ASSOC)) {
			$group = new Group($record['id']);
			$this->groups[$record['id']] = $group;
		};

	}
	
	public function __destruct() {
		$this->cleanup();
	}
	
	public function id() {
		if(array_key_exists('id', $this->getAttributes())) {
			return $this->getAttribute('id');
		} else {
			return false;
		};
	}
	
	public function isEmpty() {
		// Returns true if all attributes except 'id' are null
		$allnull = true;
		foreach (array_keys($this->getAttributes()) as $thiskey) {
			if ($thiskey!=='id') {
				if ($this->getAttribute($thiskey) !== null) {
					$allnull = false;
				}
			}
		}
		return $allnull;
	}
	
	public function setAttribute($attribute, $value, $noDbUpdate=false) {
		// Sets the specified attribute to provided value
		// $noDbUpdate = true means do not update the database yet
		if ( array_key_exists($attribute, $this->attributes) && ($attribute !== 'id')) {
			$this->attributes[$attribute] = $value;
			// Update the modification token
			$this->modified = true;
			return true;
		} else {
			return false;
		};
		if(!$noDbUpdate) {
			$this->update('items',$attribute);
		};
	}
	
	public function setAttributes($attributes) {
		// Accepts an array of attributes and updates the corresponding keys
		// Key exclusion (ie, for the 'id' key) is provided by the setAttribute() function
		$attribUpdated = false;
		foreach(array_keys($attributes) as $thiskey) {
			if(array_key_exists($thiskey, $this->attributes)) {
				$result = $this->setAttribute($thiskey, $attributes[$thiskey], true);
				if($result) {
					$attribUpdated = true;
				};
			};
		};
		$this->modified = true;
		$this->update('items',false);
		return $attribUpdated;
	}

	public function getAttribute($attribute) {
		// Returns the specified individual attribute
		if ( array_key_exists($attribute, $this->attributes)) {
			return $this->attributes[$attribute];
		} else {
			return false;
		};
	}

	public function getAttributes() {
		// Returns the full attribute array
		return $this->attributes;
	}
	
	protected function update($tablename='items', $onlykey=null) {
		// Updates the database with current values, if changed
		// If $onlykey is specified, then only the $onlykey will be updated

		if(isset($this->attributes['id'])) { // prevents error during __destruct() if item is being deleted
			if($this->modified) {
				$connection = Database::getInstance();
		
				$keynames = array_keys($this->attributes);
				$prepare_array = array();
				$execute_array = array();
				
				foreach($keynames as $thiskey) {
					if(($thiskey !== 'id') && ((($onlykey!=null) && ($thiskey==$onlykey)) || ($onlykey==null))) {
						$prepare_array[$thiskey] = $tablename . '.' .$thiskey.'=:'.$thiskey;
						$execute_array[$thiskey] = $this->attributes[$thiskey];
					};
				};
				
				$execute_array['id'] = $this->attributes['id'];
				$st = $connection->db->prepare('UPDATE ' . $tablename . ' SET ' . implode(', ', $prepare_array). ' WHERE id=:id');
				$st -> execute($execute_array);
			
				unset($keynames);
				unset($prepare_array);
				unset($execute_array);
				unset($st);
				
				$this->modified = false;
			};
		};
		return true;
	}
	
	public function getItem($id) {
		// Returns the item from the current Inventory with $id or false if does not exist
		if(array_key_exists($id, $this->items)) {
			return $this->items[$id];
		} else {
			return false;
		};
	}
			
	public function newItem() {
		// Creates and returns a new item.  Returns the ID in the $id reference variable
		$item = new Item();
		$id = $item->getAttribute('id');
		$this->items[$id] = $item;
		$this->modified = true;
		return $this->items[$id];
	}
	
	public function allItems() {
		// Returns all items
		return $this->items;
	}
	
	public function allGroups() {
		// Returns all groups
		return $this->groups;
	}
	
	public function deleteItem($id) {
		// Deletes the item with $id
		$item = $this->getItem($id);
		$item->delete();
		$this->modified=true;
		unset($this->items[$id]);
	}
	
	public function cleanup() {
		$this->cleanupGroups();
		$this->cleanupLocations();
	}

	private function cleanupGroups() {
		// Deletes unused groups; do not allow deletion of "* Default" group
		foreach($this->groups as $group) {
			if (($group->getAttribute('shortName')!=='* Default') && (sizeof($this->itemsByGroup($group->id())) == 0)) {
				$group->delete();
				unset($this->groups[$group->id()]);
				$this->modified=true;
			};
		};
	}

	private function cleanupLocations() {
		// Deletes unused locations; do not allow deletion of "* Default" location
		foreach($this->locations as $location) {
			if(($location->getAttribute('shortName')!=='* Default') &&  (sizeof($this->itemsByLocation($location->id())) == 0)) {
				$location->delete();
				unset($this->locations[$location->id()]);
				$this->modified=true;
			};
		};
	}

	public function allLocations($nonEmpty=0) {
		return $this->locations;
	}
	
	public function getLocation($id) {
		// Returns the location corresponding to single $id, or false if not found
		if(array_key_exists($id, $this->locations)) {
			return($this->locations[$id]);
		} else {
			return false;
		};
	}

	public function getGroup($id) {
		// Returns the group corresponding to single $id, or false if not found
		if(array_key_exists($id, $this->groups)) {
			return($this->groups[$id]);
		} else {
			return false;
		};
	}
	
	public function matchLocation($shortName) {
		// Performs a case-insensitive search for location by $shortName
		// Returns the corresponding location, or false if none found
		$connection = Database::getInstance();
		$st = $connection->db->prepare('SELECT id FROM locations WHERE TRIM(LOWER(shortName))=?');
		$st->execute(array(trim(strtolower($shortName))));
		$record = $st->fetch(PDO::FETCH_ASSOC);
		if($record) {
			return $this->getLocation($record['id']);
		} else {
			return false;
		};
	}

	public function newLocation($idnum) {
		// Creates and returns a new location.  Returns the ID in the $idnum reference variable
		$location = new Location();
		$this->locations[$location->id()] = $location;
		$this->modified = true;
		return $this->locations[$location->id()];
	}

	public function matchGroup($shortName) {
		// Performs a case-insensitive search for group by $shortName
		// Returns the corresponding group, or false if none found
		$connection = Database::getInstance();
		$st = $connection->db->prepare('SELECT id FROM groups WHERE TRIM(LOWER(shortName))=?');
		$st->execute(array(trim(strtolower($shortName))));
		$record = $st->fetch(PDO::FETCH_ASSOC);
		if($record) {
			return $this->getGroup($record['id']);
		} else {
			return false;
		};
	}

	public function newGroup($idnum) {
		// Creates and returns a new group.  Returns the ID in the $idnum reference variable
		$group = new Group();
		$this->groups[$group->id()] = $group;
		$this->modified = true;
		return $this->groups[$group->id()];
	}
	
	public function itemsByGroup($group) {
		// Returns an array of all items belonging to the specified $group
		$list = array();
		foreach($this->items as $item) {
			if ($item->getAttribute('group') == $group) {
				$list[$item->id()] = $item;
			};
		};
		return $list;
	}
	
	public function itemsByLocation($location) {
		// Returns a list of all items belonging to the specified $location id
		$list = array();
		foreach($this->items as $item) {
			if ($item->getAttribute('location') == $location) {
				$list[$item->id()] = $item;
			};
		};
		return $list;
	}
	
}

/* ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ******************************************************************************************************** */   

/**
 *
 * Item Class
 *
 */

class Item extends Inventory {

	private $attachments = array();
	private $valid = false;
	private $modified = false;
	
	public function __construct($id=null) {
		$this->buildItem($id, 'items');
	}
	
	public function __tostring() {
		return $this->attributes['id'];
	}
	
	protected function buildItem($id, $tablename="items") {
		$connection = Database::getInstance();
		if($id==null) {
			// Create new record
			$connection->db->query('INSERT INTO ' . $tablename . ' () VALUES ()');
			$this->attributes['id'] = $connection->db->lastInsertId();
			
			// Read it back in to initialize the attributes
			$record = $connection->db->query('SELECT * FROM ' . $tablename . ' WHERE id=' . $this->attributes['id']) -> fetch(PDO::FETCH_ASSOC);

			$this->attributes = $record;
			$this->valid = true;
			$this->loadAttachments($this->attributes['id']);
			$this->modified=true;
		} else {
			// Read existing record
			$st = $connection->db->prepare('SELECT * FROM ' . $tablename . ' WHERE id=?');
			$st->execute(array($id));
			$record = $st->fetch(PDO::FETCH_ASSOC);
			
			if($record['id'] == null) {
				// No record returned, abort
				$this->valid = false;
			} else {
				$this->valid = true;
				$this->attributes = $record;
				$this->loadAttachments($this->attributes['id']);
			};
		};

		$this->dbDisconnect();
		unset($record);		
	}
	
	public function isValid() {
		return $this->valid;
	}
	
	private function loadAttachments($id) {
		// Loads attachment items from database
		$attachmentList = Attachment::attachmentsForItem($id);
		foreach($attachmentList as $attachmentId) {
			$this->attachments[$attachmentId] = new Attachment($attachmentId);
		};
		unset($attachmentList);
		unset($attachmentId);
	}
	
	public function addAttachment($filename, $originalName) {
		// Adds a new attachment stored on the server as $filename
		// $originalName is the name of the file on the client machine
		
		$newAttachment = new Attachment();
		$newAttachment->setAttribute('item', $this->attributes['id']);
		$newAttachment->addFile($filename,$originalName);
		$this->attachments[$newAttachment->getAttribute('id')] = $newAttachment;
		$this->modified = true;
	}

	public function __destruct() {
		foreach($this->attachments as $attachment) {
			$attachment->__destruct();
		}
		$this->update('items');
	}
	
	public function getAttachment($id) {
		// Returns specific attachment
		if(array_key_exists($id, $this->attachments)) {
			return $this->attachments[$id];		
		} else {
			return false;
		}
	}
	
	public function getAttachments() {
		// Returns all attachments
		return $this->attachments;
	}
	
	public function deleteAttachment($id) {
		// Destroys the specified attachment with $id
		$this->attachments[$id]->delete();
		unset($this->attachments[$id]);
		$this->modified = true;
	}
	
	public function delete() {
		// Destroys the current item
		// Inventory::deleteItem is probably what you're looking for
		foreach ($this->attachments as $attachment) {
			$this->deleteAttachment($attachment->getAttribute('id'));
		};
		$connection = Database::getInstance();
		$st = $connection->db->prepare('DELETE FROM items WHERE id=?');
		$st -> execute(array($this->attributes['id']));
		$this->attributes = array();
		return true;
	}
	
}

/* ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ******************************************************************************************************** */   


class NullItem extends Item {

	public function __construct() {
		// Creates a simulated attributes() array with all values set to null
		$connection = Database::getInstance();
		$result = $connection -> db -> query('SHOW FULL COLUMNS IN items');
		while($thiskey = $result->fetch(PDO::FETCH_ASSOC)) {
			$this->attributes[$thiskey['Field']] = null;
		};
	}
	
	public function __destruct() {
		// Do nothing
	}
}

/* ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ******************************************************************************************************** */   

/**
 *
 * Group class
 *
 */

class Group extends Inventory {
	protected $attributes = array();
	
	public function __construct($id=null) {
		$this->buildGroup($id, 'groups');
	}
	
	public function __destruct() {
		$this->update('groups');
	}
	
	public function delete() {
		// Deletes this ID from the database
		// Note:  Inventory::deleteGroup() is probably what you're looking for...
		$connection = Database::getInstance();
		
		$st = $connection->db->prepare('DELETE FROM groups WHERE id=?');
		$st->execute(array($this->getAttribute('id')));

	}
	
	private function buildGroup($id, $tablename) {
		$connection = Database::getInstance();
		if($id==null) {
			// Create new record
			$connection->db->query('INSERT INTO ' . $tablename . ' () VALUES ()');
			$this->attributes['id'] = $connection->db->lastInsertId();
			
			// Read it back in to initialize the attributes
			$record = $connection->db->query('SELECT * FROM ' . $tablename . ' WHERE id=' . $this->attributes['id']) -> fetch(PDO::FETCH_ASSOC);

			$this->attributes = $record;
			$this->valid = true;
		} else {
			// Read existing record
			$st = $connection->db->prepare('SELECT * FROM ' . $tablename . ' WHERE id=?');
			$st->execute(array($id));
			$record = $st->fetch(PDO::FETCH_ASSOC);
			
			if($record['id'] == null) {
				// No record returned, abort
				$this->valid = false;
			} else {
				$this->valid = true;
				$this->attributes = $record;
			};
		};
	}
	
}








/* ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ******************************************************************************************************** */   

/**
 *
 * Location class
 *
 */

class Location extends Inventory {
	protected $attributes = array();
	
	public function __construct($id=null) {
		$this->buildLocation($id, 'locations');
	}
	
	private function buildLocation($id, $tablename) {
		$connection = Database::getInstance();
		if($id==null) {
			// Create new record
			$connection->db->query('INSERT INTO ' . $tablename . ' () VALUES ()');
			$this->attributes['id'] = $connection->db->lastInsertId();
			
			// Read it back in to initialize the attributes
			$record = $connection->db->query('SELECT * FROM ' . $tablename . ' WHERE id=' . $this->attributes['id']) -> fetch(PDO::FETCH_ASSOC);

			$this->attributes = $record;
			$this->valid = true;
		} else {
			// Read existing record
			$st = $connection->db->prepare('SELECT * FROM ' . $tablename . ' WHERE id=?');
			$st->execute(array($id));
			$record = $st->fetch(PDO::FETCH_ASSOC);
			
			if($record['id'] == null) {
				// No record returned, abort
				$this->valid = false;
			} else {
				$this->valid = true;
				$this->attributes = $record;
			};
		};

		unset($record);		
	}

	
	public function __destruct() {
		$this->update('locations');
	}
	
	public function delete() {
		// Deletes this ID from the database
		// Note:  Inventory::deleteLocation() is probably what you're looking for...
		$connection = Database::getInstance();
		
		$st = $connection->db->prepare('DELETE FROM locations WHERE id=?');
		$st->execute(array($this->getAttribute('id')));

	}
	
	public function contains() {
		// returns an array list of item id's which belong to the location $id
		$connection = Database::getInstance();
		$st = $connection->db->prepare('SELECT id FROM items WHERE location=?');
		$st -> execute(array($this->id()));
		$result = $st->fetch(PDO::FETCH_ASSOC);
		return $result;
	}
	
}


/* ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ********************************************************************************************************
   ******************************************************************************************************** */   

/**
 *
 * Attachment class
 *
 */

class Attachment extends Item {
	protected $attributes = array();
	
	public function __construct($id=null) {
		$this->buildItem($id, 'attachments');
	}
		
	public function __destruct() {
		$this->update('attachments');
	}
		
	public function addFile($filename, $originalName) {
		// Adds the data from the specified $filename to the record
		// $originalName is the name of the file from the client computer
		
		// Determine if it's an image
		$imgInfo = getimagesize($filename);
		
		$isImg = ($imgInfo[2]>0);
		
		if($isImg) {
			// It's an image
			$this->setAttribute('isImg',true);
			$this->setAttribute('imgType',$imgInfo[2]);
			$this->setAttribute('imgWidth',$imgInfo[0]);
			$this->setAttribute('imgHeight',$imgInfo[1]);
			$this->setAttribute('mime',$imgInfo['mime']);
			$this->setAttribute('sha1',sha1_file($filename));
			$this->setAttribute('originalExt',pathinfo(strtolower($originalName), PATHINFO_EXTENSION));
		} else {
			// Not an image
			$this->setAttribute('isImg',false);
			$this->setAttribute('imgType',null);
			$this->setAttribute('imgWidth',null);
			$this->setAttribute('imgHeight',null);
			$this->setAttribute('mime', trim(shell_exec("/usr/bin/file -bi " . escapeshellarg($filename))));
			$this->setAttribute('sha1', sha1_file($filename));
			$this->setAttribute('originalExt', pathinfo(strtolower($originalName), PATHINFO_EXTENSION));
		};

		rename($filename, $this->getFilename());
		
		$this->makeThumbnail();

		$this->update('attachments');

		unset($filename);
	}
	
	private function getFilename() {
		// Returns the relative path and name of the attachment data file
		return ('attachments/'.$this->getAttribute('sha1').'.'.$this->getAttribute('originalExt'));
	}
	
	public function getFiledata() {
		// Returns the raw binary content of the attachment data file
		return (file_get_contents($this->getFilename()));
	}
	
	public function getFileBase64($inline = false) {
		// Returns the base64-encoded content of the attachment data file
		// Adds base64 encoding if $inline parameter is set to BASE64_INLINE_IMG
		if($inline == BASE64_INLINE_IMG) {
			return ('data:' . $this->getAttribute('mime') . ';base64,' . base64_encode($this->getFiledata()));
		} else {
			return (base64_encode($this->getFiledata()));
		};
	}
	
	public function getThumbname() {
		// Returns relative path and name of thumbnail file
		return ('thumbs/'.$this->getAttribute('sha1').'.jpg');
	}
	
	private function isPdf() {
		// Returns true/false if file is a PDF
		if (strpos($this->getAttribute('mime'), 'application/pdf') === false) {
			return false;
		} else {
			return true;
		};
	}

	public function makeThumbnail() {
		// Generates a thumbnail image for a file
		if (!class_exists('Imagick',false)) {
			// Imagemagick is not installed, so abort
			return false;
		};
		
		if (($this->isPdf()) && (!file_exists($this->getThumbname()))) {
			// Make a thumbnail of the PDF if one does not already exist
			$imagick = new Imagick();
			$imagick->readImage($this->getFilename().'[0]');
			$imagick = $imagick->flattenImages();
			$imagick->writeImage($this->getThumbname()); // Temporarily write to disk so we can retrieve the dimensions
			unset($imagick);
			
			$imagick = new Imagick();
			$imagick->readImage($this->getThumbname());
			$this->setAttribute('imgWidth', $imagick->getImageWidth());
			$this->setAttribute('imgHeight', $imagick->getImageHeight());
			$imagick->scaleImage(128,128,false);
			$imagick->writeImage($this->getThumbname());
			$this->setAttribute('hasThumb', true);
			unset ($imagick);
			return true;
		};

		if (($this->getAttribute('isImg')) && (!file_exists($this->getThumbname()))) {
			// Make a thumbnail of the image if one does not already exist
			$imagick = new Imagick();
			$imagick->readImage($this->getFilename());
			$imagick->scaleImage(128,128,false);
			$imagick->writeImage($this->getThumbname());
			$this->setAttribute('hasThumb', true);
			unset ($imagick);
			return true;				
		};
		
		return false;
	}
	
	public function hasThumbnail() {
		// returns true/false if a thumbnail exists
		return ($this->getAttribute('hasThumb', true));
	}
	
	public function getThumbBase64($inline = false) {
		// Returns the base64-encoded content of the attachment data file
		// Adds base64 encoding if $inline parameter is set to BASE64_INLINE_IMG
		// Returns false if no thumbnail exists
		if($this->hasThumbnail()) {
			if($inline == BASE64_INLINE_IMG) {
				return ('data:image/jpeg;base64,' . base64_encode(file_get_contents($this->getThumbname())));
			} else {
				return (base64_encode($this->getFiledata()));
			};
		} else {
			return false;
		};
	}

	private function getFileType() {
		// Returns MIME data type of the file stored in the fileData attribute
		if($this->getAttribute('fileData') !== false) {
			$fi = new finfo(FILEINFO_MIME, '/usr/share/file/magic');
			return $fi -> buffer($this->getAttribute('filedata'));
		} else {
			return false;
		};
	}
	
	private function isImage() {
		// Returns true if the file stored in the fileData attribute is an image
		if($this->getAttribute('fileData') !== false) {
			return (strpos( $this->getFileType(), 'image/') >= 1);
		} else {
			return false;
		}
	}
	
	public function attachmentsForItem($id) {
		// Returns an array of attachment id's which belong to the specified item id
		if($id==null) {
			return null;
		} else {
			$idlist = array();
			$connection = Database::getInstance();
			$st = $connection->db->prepare('SELECT id FROM attachments WHERE item=?');
			$st->execute(array($id));
			while ($record = $st->fetch(PDO::FETCH_ASSOC)) {
				$idlist[] = $record['id'];
			};
			unset($st);
			unset($record);
			return($idlist);
		}
	}
	
	public function delete() {
		// Deletes this ID from the database and the array, and removes source file and thumbnail if not in use by another record
		// Note:  Item::deleteAttachment() is probably what you're looking for...
		$connection = Database::getInstance();
		
		$st = $connection->db->prepare('SELECT COUNT(id) FROM attachments WHERE sha1=?');
		$st -> execute(array($this->getAttribute('sha1')));
		$records = $st->fetch(PDO::FETCH_NUM);
		if($records[0] == 1) {
			unlink($this->getFilename());
			if($this->hasThumbnail()) {
				unlink($this->getThumbname());
			};
		};
		
		unset($records);
		
		$st = $connection->db->prepare('DELETE FROM attachments WHERE id=?');
		$st->execute(array($this->getAttribute('id')));
	}
	
}





