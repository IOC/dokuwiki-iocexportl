require.ready(function(){
	require(["jquery.min","doctools"], function(jQuery,Highlight){
		Highlight();
		require(["quiz", "functions","searchtools"], function(quiz,func,Search){
			Search.init();
			if (/search\.html/.exec(document.location.href)){
				func();
			}
		});
	});
});

