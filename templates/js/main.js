require(["jquery.min","jquery-ui.min","jquery.imagesloaded","render","functions","doctools","quiz","searchtools"], function(jQuery,jUi,jIl,render,func,Highlight,quiz,Search){
	$("article").imagesLoaded(function(){
		render.infoTable();
		render.infoFigure();
	});
	Highlight();
	if (func.ispageSearch()){
		Search.init();
	}
});
