window.onload = function() {
	round_settings = {
	  tl: { radius: 8 },
	  tr: { radius: 8 },
	  bl: { radius: 8 },
	  br: { radius: 8 },
	  antiAlias: true,
	  autoPad: true
	} 
	var divObjs = document.getElementsByTagName("div");
	for (var i = 0; i < divObjs.length; i++) {
		if (divObjs[i].className == "round") {
			var cornersObj = new curvyCorners(round_settings, divObjs[i]);
			cornersObj.applyCornersToAll();
		}
	}
}