require(["jquery.min"], function(jQuery){
	require(["jquery-ui.min","jquery.imagesloaded"], function(jUi,jIl){
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
});
