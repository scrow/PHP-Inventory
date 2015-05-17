function disableInputs() {
	var length = document.backupForm['actionBtn[]'].length;
	for (var i =0; i < length; i++) {
		document.backupForm['actionBtn[]'][i].disabled = true;
	};
	
	var length = document.backupForm['filename[]'].length;
	for (var i = 0; i < length; i++) {
		document.backupForm['filename[]'][i].disabled = true;
	};
}

function createBackup() {
	alert('The backup process may take a few moments to complete.');
	document.backupForm.action.value = 'Create Backup';
	document.getElementById('message').innerHTML = '<P>Creating backup file, please wait...</P>';
	document.backupForm.submit();
	// Line below prevents button from staying enabled if there are no radio buttons in the filename[] array
	document.getElementById('actionBtn[]').disabled = true;
	disableInputs();
};

function deleteBackup() {
	document.backupForm.action.value = 'Delete';
	document.backupForm.submit();
	disableInputs();
};

function restoreBackup () {
	document.backupForm.action.value = 'Restore';
	document.backupForm.submit();
	disableInputs();
};