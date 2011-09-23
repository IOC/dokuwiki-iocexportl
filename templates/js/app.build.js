({
    appDir: "../",
    baseUrl: "js/",
    dir: "../html/_/js",
    //Comment out the optimize line if you want
    //the code minified by UglifyJS
    //optimize: "none",

    modules: [
        {
            name: "main",
			exclude: ["jquery.min"]
        },
    ]
})
