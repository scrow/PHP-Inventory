function getNewGroup(selectbox) {
	var e = document.getElementById(selectbox);
	var optval = e.options[e.selectedIndex].text;
	
	if(optval == "Create new...") {
		var newOpt = window.prompt("Enter new group name:","");
		
		if(newOpt != null) {
			addOption(e,newOpt,newOpt);
			e.selectedIndex = (e.options.length -1);
			
		}

	}
}

function getNewLocation(selectbox) {
	var e = document.getElementById(selectbox);
	var optval = e.options[e.selectedIndex].text;
	
	if(optval == "Create new...") {
		var newOpt = window.prompt("Enter new location name:","");
		
		if(newOpt != null) {
			addOption(e,newOpt,newOpt);
			e.selectedIndex = (e.options.length -1);
			
		}

	}
}

function addOption(selectbox,text,value ) {
	var optn = document.createElement("OPTION");
	optn.text = text;
	optn.value = value;
	selectbox.options.add(optn);
}
