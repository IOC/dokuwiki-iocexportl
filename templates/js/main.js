require.ready(function(){
	require(["jquery.min","doctools","jquery-ui.min","jquery.imagesloaded"], function(jQuery,Highlight,jUi){
		Highlight();
		require(["render"], function(render){
			$("article").imagesLoaded(function(){
				render.infoTable();
				render.infoFigure();
			});
			require(["quiz", "functions","searchtools"], function(quiz,func,Search){
				Search.init();
				if (/search\.html/.exec(document.location.pathname)){
					func();
				}
			});
		});
	});
});

