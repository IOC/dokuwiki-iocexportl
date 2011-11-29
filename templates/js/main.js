require(["jquery.min","jquery-ui.min","jquery.imagesloaded"], function(jQuery,jUi,jIl){
	require(["render"], function(render){
		$("article").imagesLoaded(function(){
			render.infoTable();
			render.infoFigure();
		});
		require(["functions","doctools","quiz","searchtools"], function(func,Highlight,quiz,Search){
			Highlight();
			if (func.ispageSearch()){
				Search.init();
			}
		});
	});
});
