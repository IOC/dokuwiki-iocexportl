require.ready(function(){
	require(["jquery.min"], function(){
		require(["quiz", "functions"], function(){
		    //Initialize menu and settings params
		    Hyphenator.config({
				minwordlength : 4,
		    });
		    Hyphenator.run();
		});
	});
});



