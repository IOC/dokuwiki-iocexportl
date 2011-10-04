define (function(){
	var lnavi = parseInt($("#navi").css('left'),10);
	var topside = parseInt($("#topside").css('top'),10);
	var topsideheight = parseInt($("#topside").css('height'),10);
	var topheader = parseInt($("#header").css('height'),10);
	var defaultsettings = '{"settings":[{"svisible":0,"font":0,"fontsize":1,"contrast":1,"alignment":0,"hyphen":0}],"menu":[{"mvisible":0}]}';
	
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
		var object = $.parseJSON(defaultsettings);
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
			$("#navi").css('left',lnavi);
		}else{
			$("#aside").css('left',-$("#aside").outerWidth());
			$("#navi").css('left',-$("#aside").outerWidth()+lnavi);
		}
		enablesideoption();
	});

	var enablesideoption = (function(){
		var url = document.location.href;
		var dir = "WebContent/";
		url = url.slice(url.indexOf(dir)+dir.length,url.length);
		var elements = url.split("/");
		var node = '';
		var parent_node = '';
		for (i=0;i<elements.length;i++)
		{
			if (elements[i].indexOf('html')==-1){
				parent_node+=elements[i];
			}
			node+=elements[i];
		}
		//Remove html extension and others characters at the end
		var patt = new RegExp('\.html.*?','g')
		node = node.replace(patt,'');
		
		if (parent_node!=''){
			$('#'+parent_node).parent().each(function(key, value) {
				$(this).show();
			});
			$('#'+parent_node+"> ul").show();
		}
		if (node != ''){
			$('#'+node).addClass("optselected");
		}
	});

	var setUrl = (function(url){
		local = document.location.href;
		local = local.slice(local.indexOf("WebContent"),local.length);
		url2 = url.slice(url.indexOf("WebContent"),local.length);
		if (local!=basename(url2)){
			document.location.href=url;
		}
	});


	//Set params into our cookie
	var setcookie = (function(value){
		document.cookie ='ioc_html=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
		document.cookie="ioc_html=" + escape(value) + "; path=/;";
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

	var set_navi = (function(h){
		var height = parseInt($("#aside").css("height"),10);
		height = (height/2)+(parseInt($("#aside").css("top"),10)-(parseInt($("#navi").css("height"),10)/2))+8;
		$("#navi").css("top",height);
	});

	var get_params = (function(reset){
		var info = getcookie();
		if (info!=null && info!="" && !reset){
			//Get and apply stored options
			var object = $.parseJSON(info);
			settings(object);
			sidemenu(object);
		}else{
			//Save default options
			var object = $.parseJSON(defaultsettings);
			settings(object);
			sidemenu(object);
			setcookie(defaultsettings);
		}
		set_navi();
	});
	
	
	function basename(path) {
		return path.replace(/\\/g,'/').replace( /.*\//, '' );
	}

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
	
	jQuery(window).resize(function() {
		get_params();
	});
	
	//Show and hide list elements
	$(".expander ul").hide();
	$(".expander h4").live("click", function() {

	    var $nestList = $(this).siblings("ul");
	    if ($(this).parent().children("ul").css('display') == 'inline'){
			$(this).parent().children("ul").hide('fast', function(){
				set_navi();
			});
		}else{
			$(this).parent().children("ul").show('fast',function(){
				set_navi();
			});
		}
		$(this).parent().siblings().children().filter('ul').hide('fast', function(){
			set_navi();
		});
			
	});

	//Save selected option inside menu
	$("#menu li > a").click(function(e) {
		e.preventDefault();
		setUrl($(this).attr("href"));
	});
	
	$.expr[':'].parents = function(a,i,m){
	    return $(a).parents(m[3]).children('ul').length < 1;
	};
	$('li > h4').filter(':parents(li)').addClass('arrow');
	
	//Initialize menu and settings params
	get_params();
    
});
