// remap jQuery to $
(function($){
	var lnavi = parseInt($("#navi").css('left'),10);
	var topside = parseInt($("#topside").css('top'),10);
	var topsideheight = parseInt($("#topside").css('height'),10);
	var topheader = parseInt($("#header").css('height'),10);
	var defaultsettings = '{"settings":[{"svisible":0,"font":0,"fontsize":1,"contrast":1,"alignment":0,"hyphen":0}],"menu":[{"mvisible":0,"node":"null","selected":"null"}]}';
	
	$('#navi').click(function() {
		$("#aside").animate({
			left: parseInt($("#aside").css('left'),10) == 0 ?
		        -$("#aside").outerWidth() :
		        0
		});
		$("#navi").animate({
			left: parseInt($("#navi").css('left'),10) == lnavi ?
		        -$("#aside").outerWidth()+lnavi :
		        lnavi
		});
		var info=getcookie();
		var patt=/"mvisible":0/g;
	  	if (patt.test(info)){
			setCookieProperty('mvisible', 1);
		}else{
			setCookieProperty('mvisible', 0);
		}
	});
	
	$('#settings').click(function() {
		$("#topside").animate({
			top: parseInt($("#topside").css('top'),10) == topside ?
		        $("#topside").outerHeight()+topside :
		        topside
		});
		var info=getcookie();
		var patt=/"svisible":0/g;
	  	if (patt.test(info)){
			setCookieProperty('svisible', 1);
		}else{
			setCookieProperty('svisible', 0);
		}
	});
	
    $('#search').click(function() {
    	$("#imgsearch").addClass("hidden");
    	$("#frmsearch").removeClass("hidden");
    	$("#frmsearch").addClass("visible");
    	$("input[name='search']").focus();
    });
    
    //Font
    $('#first-font').click(function() {
		setFont(0);
		setCookieProperty('font', 0);
    });
    
    $('#second-font').click(function() {
		setFont(1);
		setCookieProperty('font', 1);
    });
    //Font size
    $('#text-small').click(function() {
		setFontsize(0);
		setCookieProperty('fontsize', 0);
    });
    
    $('#text-normal').click(function() {
		setFontsize(1);
		setCookieProperty('fontsize', 1);
    });
    
    $('#text-big').click(function() {
		setFontsize(2);
		setCookieProperty('fontsize', 2);
    });
    
    $('#text-huge').click(function() {
		setFontsize(3);
		setCookieProperty('fontsize', 3);
    });
    
    //Contrast
    $('#text-negre').click(function() {
   	 	setContrast(0);
		setCookieProperty('contrast', 0);
    });
    
    $('#text-gris').click(function() {
    	setContrast(1);
		setCookieProperty('contrast', 1);
    });
    
    $('#text-blanc').click(function() {
    	setContrast(2);
		setCookieProperty('contrast', 2);
    });
    
    //Alignment
    $('#text-left').click(function() {
    	setAlignment(1,0);
		setCookieProperty('alignment', 0);
    });
    
    $('#text-justify').click(function() {
    	setAlignment(2,1);
		setCookieProperty('alignment', 1);
    });
    
    //Hyphenation
    $('#hyphenation').click(function() {
    	setHyphenation(0);
		setCookieProperty('hyphen', 0);
    });
    
    $('#hyphenatioff').click(function() {
     	setHyphenation(1);
		setCookieProperty('hyphen', 1);
    });
    
    $('#reset').click(function() {
		var object = jQuery.parseJSON(defaultsettings);
   	 	settings(object);
		for (obj in object.settings[0]){
			setCookieProperty(obj,object.settings[0][obj]);
		}
    });
    
    $('#upbutton').click(function() {
    	   	 	$("html,body").animate({
    	   	 		scrollTop: 0
    	   	 	}, 500);
    });
    
    $('#navmenu').click(function() {
		setCookieProperty('selected','null');
    });

	var setFont = (function (info){
		var options=new Array("first-font","second-font");
		other = (info==0)?1:0;
    	$('#'+options[info]).addClass('font-selected');
    	$('#'+options[other]).removeClass('font-selected');
    	$("p").removeClass(options[other]);
   	 	$("p").addClass(options[info]);
	});

	var setFontsize = (function (info){
		var options=new Array("text-small","text-normal","text-big","text-huge");
		$('#'+options[info]).addClass('font-selected');
		$("article p").addClass(options[info]);
		for (i=0;i<options.length;i++){
			if (i==info){
				continue;
			}
			$('#'+options[i]).removeClass('font-selected');
			$("article p").removeClass(options[i]);
			
		}
	});

	var setContrast = (function (info){
		var options=new Array("text-negre","text-gris","text-blanc");
		$('#'+options[info]+' span').addClass('selected');
		$("body").addClass(options[info]);
		for (i=0;i<options.length;i++){
			if (i==info){
				continue;
			}
			$('#'+options[i]+' span').removeClass('selected');
			$("body").removeClass(options[i]);
			
		}
	});

	var setAlignment = (function (select, info){
		var options=new Array("text-left","text-justify");
		others = (select==1)?2:1;
		otheri = (info==0)?1:0;
    	$("#alignment").addClass('selector'+select);
   	 	$("article p").addClass(options[info]);
    	$("#alignment").removeClass('selector'+others);
    	$("article p").removeClass(options[otheri]);
	});

	var setHyphenation = (function (info){
		var options=new Array("selector1","selector2");
		other = (info==0)?1:0;
		state = (info==0)?false:true;
    	$('#hyphen').addClass(options[info]);
    	$('#hyphen').removeClass(options[other]);
   	 	Hyphenator.doHyphenation = state;
   	 	Hyphenator.toggleHyphenation();
	});

	var setCookieProperty = (function (n, value){
		var info=getcookie();
		if (typeof value == 'number'){
			var patt = new RegExp("\""+n+"\":\\d+", 'g');
			info=info.replace(patt, "\""+n+"\":"+value);
		}else{
			var patt = new RegExp("\""+n+"\":\"\\w+\"", 'g');
			info=info.replace(patt, "\""+n+"\":\""+value+"\"");
		}
		setcookie(info);
	});

	var settings = (function (info){
		if (info.settings[0]['svisible']==1){
			$("#topside").css('top','30px');
		}
		setFont(info.settings[0]['font']);
		setFontsize(info.settings[0]['fontsize']);
		setContrast(info.settings[0]['contrast']);
		setAlignment(info.settings[0]['alignment']+1,info.settings[0]['alignment']);
		setHyphenation(info.settings[0]['hyphen']);
	});

	var sidemenu = (function (info){
		if (info.menu[0]['mvisible']==1){
			$("#aside").css('left','0px');
			$("#navi").css('left',$("#navi").outerWidth()+9);
		}else{
			$("#aside").css('left',-$("#aside").outerWidth());
			$("#navi").css('left',-$("#aside").outerWidth()+$("#navi").outerWidth()+9);
		}
		if (info.menu[0]['node']!='null'){
			$('#'+info.menu[0]['node']).parent().each(function(key, value) {
				$(this).show();
			});
			$('#'+info.menu[0]['node']+"> ul").show();
		}
		if (info.menu[0]['selected']!='null'){
			$('#'+info.menu[0]['selected']).addClass("optselected");
			if (basename(document.location.href)!=basename($('#'+info.menu[0]['selected'] + ' a').attr("href"))){
				document.location.href=$('#'+info.menu[0]['selected'] + ' a').attr("href");
			}
		}
	});

	//Set params into our cookie
	var setcookie = (function(value){
		var exdate=new Date();
		document.cookie="ioc_html=" + escape(value) + ";";
	});

	//get params from our cookie    	
	var getcookie = (function(){
		var i,x,y,ARRcookies=document.cookie.split(";");
		for (i=0;i<ARRcookies.length;i++)
		{
		  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
		  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
		  x=x.replace(/^\s+|\s+$/g,"");
		  if (x=='ioc_html'){
			return unescape(y);
		  }
		}
	});

	var get_params = (function(reset){
		var info = getcookie();
		if (info!=null && info!="" && !reset){
			//Get and apply stored options
			var object = jQuery.parseJSON(info);
			settings(object);
			sidemenu(object);
		}else{
			//Save default options
			var object = jQuery.parseJSON(defaultsettings);
			settings(object);
			sidemenu(object);
			setcookie(defaultsettings);
		}
		set_navi();
	});
	
	var set_navi = (function(){
		var height = parseInt($("#aside").css("height"),10);
		height = (height/2)+(parseInt($("#aside").css("top"),10)-(parseInt($("#navi").css("height"),10)/2))+8;
		$("#navi").css("top",height);
	});
	
	function basename(path) {
		return path.replace(/\\/g,'/').replace( /.*\//, '' );
	}

    
    $("document").ready(function () {
    	//Header shadow
    	$(window).scroll(function () {
    		if ($(window).scrollTop() > 30){
    			$("header").addClass("header-shadow");
    			$("#upbutton").show("slow");
    		}else{
    			$("header").removeClass("header-shadow");
    			$("#upbutton").hide("slow");
    		}
    	});
    	
    	//Show and hide list elements
    	$(".expander ul").hide();
    	$(".expander h4").live("click", function() {
    	    var $this = $(this);
    	    var $nestList = $($this).siblings("ul");
    	    if ($($this).parent().children("ul").css('display') == 'inline'){
    	    	$($this).parent().children("ul").hide("fast", function (){
    				set_navi();
				});
    	    }else{
	    	    // hide visible nested lists
	    	    $($this).parent().siblings().children().filter('ul').hide("fast");
	    	    // show this list
	    	    $nestList.filter(":hidden").show("fast", function (){
    				set_navi();
				});
    	    }
    	});

		//Save current position inside menu
		$("#menu li h4").click(function() {
			var value = $(this).parent().get(0).id;
			if (value==''){
				value="null";
			}
			setCookieProperty('node', value.toString());
    	});

		//Save selected option inside menu
		$("#menu li a").click(function() {
			var selected = ($(this).parent().get(0).id=='')?"null":$(this).parent().get(0).id;
			setCookieProperty('selected', selected.toString());
    	});
		
		//Initialize menu and settings params
		get_params();
	});
    
})(window.jQuery);