function amazonSearch($fieldname) {
	$param = document.getElementById($fieldname).value.trim();
	if($param == "") {
		alert('Empty or invalid data provided.  Please enter the missing information and try again.');
		document.getElementById($fieldname).focus();
	} else {
		$url = 'http://www.amazon.com/s/ref=nb_sb_noss?field-keywords=' + $param;
		window.open($url);
	};
}

function googleSearch($fieldname) {
	$param = document.getElementById($fieldname).value.trim();
	if($param == "") {
		alert('Empty or invalid data provided.  Please enter the missing information and try again.');
		document.getElementById($fieldname).focus();
	} else {
		$url = 'https://www.google.com/search?output=search&tbm=shop&q=' + $param;
		window.open($url);
	};
}

function viewASIN($fieldname) {
	$param = document.getElementById($fieldname).value.trim();
	if($param == "") {
		alert('No ASIN provided.');
		document.getElementById($fieldname).focus();
	} else {
		$url = 'http://www.amazon.com/o/ASIN/' + $param;
		window.open($url);
	};
}

function getDate($fieldname) {
	var today = new Date();
	var dd = today.getDate();
	var mm = today.getMonth()+1; //January is 0!
	
	var yyyy = today.getFullYear();
	if(dd<10){
		dd='0'+dd
	} 
	if(mm<10){
		mm='0'+mm
	} 
	var today = mm+'/'+dd+'/'+yyyy;
	document.getElementById($fieldname).value = today;
	document.getElementById($fieldname).focus();
}

function viewURL($fieldname) {
	$param = document.getElementById($fieldname).value.trim();
	if($param == "") {
		alert('Empty or invalid data provided.  Please enter the missing information and try again.');
		document.getElementById($fieldname).focus();
	} else {
		if (!/^https?:\/\//i.test($param)) {
			$param = 'http://' + $param;
			document.getElementById($fieldname).value = $param;
		}
		window.open($param);
	};
}