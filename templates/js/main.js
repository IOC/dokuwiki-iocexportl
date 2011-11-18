require.ready(function(){
	require(["jquery.min","doctools","jquery-ui.min","jquery.imagesloaded"], function(jQuery,Highlight,jUi){
		Highlight();
		require(["render"], function(render){
			$("article").imagesLoaded(function(){
				render.infoTable();
				render.infoFigure();
			});
			require(["functions","quiz","searchtools"], function(func,quiz,Search){
				Search.init();
			});
		});
	});
});

