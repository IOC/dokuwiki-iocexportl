define (function(){
	var ltoc = parseInt($("#toc").css('left'),10);
	var lbridge = parseInt($("#bridge").css('left'),10);
	var settings = parseInt($("#settings").css('top'),10);
	var topheader = parseInt($("#header").css('height'),10);
	var defaultsettings = '{"menu":[{"mvisible":1}],"toc":[{"tvisible":0}],"settings":[{"font":0,"fontsize":1,"contrast":1,"alignment":0,"hyphen":0}]}';
	
	$('#menu li > a').click(function(e) {
		e.preventDefault();
	});

	$('#menu li').click(function(e) {
		setmenu(this,$(this).attr('name'));
	});

	$('#content').click(function(e) {
		setmenu(null,'');
	});

	$('#sidebar-hide').click(function(e) {
		e.preventDefault();
		$("#aside").animate({
			left: parseInt($("#aside").css('left'),10) == 0 ?
		        -$("#aside").outerWidth() :
		        0
		});
		$("#toc").animate({
			left: ((parseInt($("#toc").css('left'),10) == ltoc) && ($("#toc").css('display') !== 'none')) ?
		        -$("#aside").outerWidth()-$("#toc").outerWidth():
		        ltoc
		});
		$("#settings").animate({
			left: ((parseInt($("#settings").css('left'),10) == ltoc) && ($("#settings").css('display') !== 'none')) ?
		        -$("#aside").outerWidth()-$("#settings").outerWidth():
		        ltoc
		});

		$("#bridge").animate({
			left: ((parseInt($("#bridge").css('left'),10) == lbridge) && ($("#bridge").css('display') !== 'none')) ?
		        -$("#aside").outerWidth()-$("#bridge").outerWidth():
		        lbridge
		});


		setbackground(($("body").css('background-position') !== '0px 0px'));
		var info=getcookie();
		var patt=/"mvisible":0/g;
	  	if (patt.test(info)){
			setCookieProperty('mvisible', 1);
		}else{
			setCookieProperty('mvisible', 0);
		}
	});

    $('#search').click(function() {
    	showSearch();
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

	var showSearch = (function (){
		$("#imgsearch").addClass("hidden");
    	$("#frmsearch").removeClass("hidden");
    	$("#frmsearch").addClass("visible");
    	$("input[name='q']").focus();
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
		setFont(info.settings[0]['font']);
		setFontsize(info.settings[0]['fontsize']);
		if (pageIndex()){
			$("body").removeClass().addClass('index');
		}else{
			setContrast(info.settings[0]['contrast']);
		}
		setAlignment(info.settings[0]['alignment']+1,info.settings[0]['alignment']);
		setHyphenation(info.settings[0]['hyphen']);
	});

	var sidemenu = (function (info){
		if (pageIndex()){
			setbackground(false);
		}else{
			$("#toc").css('left', ltoc);
			$("#settings").css('left', ltoc);
			if (info.menu[0]['mvisible']==1){
				$("#aside").css('left','0px');
				setbackground(true);
			}else{
				$("#aside").css('left',-$("#aside").outerWidth());
				setbackground(false);
			}
		}
	});

	var setmenu = (function (obj, type){
		$('#toc').addClass('hidden');
		$('#settings').addClass('hidden');
		$("#bridge").addClass('hidden');
		if (!obj){
			$("#menu ul").children().each(function(i){
				$(this).removeClass('menuselected');
			});
			return;	
		}
		var show = true;
		$(obj).closest('ul').children().each(function(i){
			if (obj === this && $(obj).hasClass('menuselected')){show=false;}
			$(this).removeClass('menuselected');
		});
		if (show){
			if (type === 'toc'){
				$(obj).addClass('menuselected');
				$('#toc').removeClass('hidden');
     			$("#bridge").removeClass('hidden');
				$("#bridge").css('top',$("#toc").css('top'));
				enablemenuoption();
			}else{
				 if(type === 'settings'){
						$(obj).addClass('menuselected');
						$('#settings').removeClass('hidden');
						$("#bridge").removeClass('hidden');
						$("#bridge").css('top',$("#settings").css('top'));
				 }else{
					if(type === 'printer'){
						window.print();
				 	}else{
						if(type === 'favorites'){
							url = $(obj).find('a>img').attr('src');
							if (/favorites/.test(url)){
								url = url.replace(/favorites/, 'fav_ok');
							}else{
								url = url.replace(/fav_ok/, 'favorites');
							}
							$(obj).find('a>img').attr('src', url);
							//Guardar la url a la cookie (si ja existeix no cal)
					 	}
				 	}
				 }
			}
		}
	});
	
	var enablemenuoption = (function(){
          var url = document.location.pathname;
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
          //Remove html extension
          var patt = new RegExp('\.html','g')
          node = node.replace(patt,'');
          
          if (parent_node != ''){
                  $('#'+parent_node).parent().each(function(key, value) {
                          $(this).show();
                  });
                  $('#'+parent_node+"> ul").show();
          }
          if (node != ''){
                  $('#'+node).addClass("optselected");
          }
	});

	var setbackground = (function(show){
		if(!show){
			$("body").css("background-position","-73px 0");
		}else{
			$("body").css("background-position","0 0");
		}
	});

	var pageIndex = (function (){
		var url = document.location.pathname;
		return /index\.html/.test(url);
	});

	var indexToc = (function(show){
	  	if (show){
	  		$(".meta").css('margin-top',-$("#header").outerHeight()-$(".meta").outerHeight()+60);
			$("#header .head").removeClass('hidden');
			$("#header .headdocument").removeClass('hidden');
			$(".headtoc h1 img").addClass('rotateup');
			$(".headtoc").css('top', '40px');
			$(".indextoc").css('overflow','visible');
			$(".indextoc").css('margin-top', '25px');
			$("#headtoc").removeClass("headtopdown").addClass("headtopup");
			$(".indextoc").show();
		}else{
	  		$(".meta").css('margin-top','0');
	  		$("#header .head").addClass('hidden');
			$("#header .headdocument").addClass('hidden');
			$(".headtoc h1 img").removeClass('rotateup');			
			$(".headtoc").css('bottom', '0');
			$(".indextoc").css('margin-top', $(window).height());
			$(".indextoc").hide();
			$("#headtoc").removeClass("headtopup").addClass("headtopdown");	  		
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

	var get_params = (function(reset){
		var info = getcookie();
		if (info!=null && info!="" && !reset){
			//Get and apply stored options
			var object = $.parseJSON(info);
			settings(object);
			sidemenu(object);
			if(pageIndex()){
				indexToc(object.toc[0]['tvisible']==1);
			}
		}else{
			//Save default options
			var object = $.parseJSON(defaultsettings);
			settings(object);
			sidemenu(object);
			setcookie(defaultsettings);
		}
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
	    if ($(this).parent().children("ul").css('display') != 'none'){
			$(this).parent().children("ul").hide('fast', function(){

			});
		}else{
			$(this).parent().children("ul").show('fast',function(){

			});
		}
		$(this).parent().siblings().children().filter('ul').hide('fast', function(){

		});
			
	});
	
	$(".expander h4 > a[href='#']").live("click", function(e) {
		e.preventDefault();
	});

	$.expr[':'].parents = function(a,i,m){
	    return $(a).parents(m[3]).children('ul').length < 1;
	};
	$('li > h4').filter(':parents(li)').addClass('arrow');
	
	//TOC
	$(".indextoc").hide();
	$(".indextoc").css('margin-top', $(window).height());
	$(".headtoc").live("click", function() {
		$(".meta").animate({
				'margin-top': parseInt($(".meta").css('margin-top'),10) == 0 ?
					-$("#header").outerHeight()-$(".meta").outerHeight()+60 :
					0,
				'visible':'inline'
		},1500);
		if ($(".indextoc").css('display') !== 'none'){
			$("#header .head").addClass('hidden');
			$("#header .headdocument").addClass('hidden');
			$(".headtoc h1 img").removeClass('rotateup');
			$(".headtoc").animate({
			'top': $(window).height()-$(".headtoc h1").outerHeight(true)-$("footer").outerHeight(true)-10,
			'bottom': '0'
			},1500, function(){$(".headtoc").css('top', '');});
			$(".indextoc").animate({
				'margin-top': $(window).height()
			},1500,function(){$(".indextoc").hide();});
			$("#headtoc").removeClass("headtopup").addClass("headtopdown");
			setCookieProperty('tvisible', 0);
		}else{
			$("#header .head").removeClass('hidden');
			$("#header .headdocument").removeClass('hidden');
			$(".headtoc h1 img").addClass('rotateup');
			$(".headtoc").css('top', $(window).height()-$(".headtoc h1").outerHeight(true)-$("footer").outerHeight(true)-10);
			$(".headtoc").animate({
				'top': '40px'
			},1500, function(){$(".indextoc").css('overflow','visible');});
			$(".indextoc").animate({
				'margin-top': '25px'
			},1500);
			$("#headtoc").removeClass("headtopdown").addClass("headtopup");
			$(".indextoc").show();
			setCookieProperty('tvisible', 1);
		}
	});
	
	//Initialize menu and settings params
	get_params();
	return showSearch;
});