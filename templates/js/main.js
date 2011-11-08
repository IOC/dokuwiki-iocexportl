require.ready(function(){
	require(["jquery.min","doctools","jquery-ui.min"], function(jQuery,Highlight,jUi){
		Highlight();
		require(["quiz", "functions","searchtools"], function(quiz,func,Search){
			Search.init();
			if (/search\.html/.exec(document.location.href)){
				func();
			}
		});
	});
});

