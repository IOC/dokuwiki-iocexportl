define (["render"],function(render){

	var ltoc = parseInt($("#toc").css('left'),10);
	var lbridge = parseInt($("#bridge").css('left'),10);
	var lfavcount = parseInt($("#favcounter").css('left'),10);
	var lfavorites = parseInt($("#favorites").css('left'),10);
	var topheader = parseInt($("#header").css('height'),10);
	var defaultsettings = '{"menu":[{"mvisible":1}],"toc":[{"tvisible":0}],"settings":[{"fontsize":2,"contrast":0,"alignment":0,"hyphen":0,"width":2,"mimages":1,"scontent":1}],"fav":[{"urls":"0"}]}';
	
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


		$("#favcounter").animate({
			left: ((parseInt($("#favcounter").css('left'),10) == lfavcount) && ($("#favcounter").css('display') !== 'none')) ?
		        -$("#aside").outerWidth()-$("#favcounter").outerWidth():
		        lfavcount
		});

		$("#favorites").animate({
			left: ((parseInt($("#favorites").css('left'),10) == lfavorites) && ($("#favorites").css('display') !== 'none')) ?
		        -$("#aside").outerWidth()-$("#favorites").outerWidth():
		        lfavorites
		});
		if (!$("#favorites").hasClass("hidden")){
			$("#bridge").toggleClass("hidden");
		}

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
    
    //Contrast
    $('#style-newspaper').click(function() {
   	 	setContrast(0);
		setCookieProperty('contrast', 0);
    });
    
    $('#style-novel').click(function() {
    	setContrast(1);
		setCookieProperty('contrast', 1);
    });
    
    $('#style-ebook').click(function() {
    	setContrast(2);
		setCookieProperty('contrast', 2);
    });

    $('#style-inverse').click(function() {
    	setContrast(3);
		setCookieProperty('contrast', 3);
    });

    $('#style-athelas').click(function() {
    	setContrast(4);
		setCookieProperty('contrast', 4);
    });
    
    //Alignment
    $('#text-alignment').click(function() {
		if ($(this).attr("checked")){
			setAlignment(1);
			setCookieProperty('alignment', 1);
		}else{
    		setAlignment(0);
			setCookieProperty('alignment', 0);
		}
    });
    
    //Hyphenation
    $('#text-hyphen').click(function() {
		if ($(this).attr("checked")){
    		setHyphenation(0);
			setCookieProperty('hyphen', 0);
		}else{
    		setHyphenation(1);
			setCookieProperty('hyphen', 1);
		}
    });
    
    
    //Show or hide secondary content
    $('#main-images').click(function() {
		if ($(this).attr("checked")){
			setMainFig(1);
			render.infoFigure();
			setCookieProperty('mimages', 1);
		}else{
			setMainFig(0);
			setCookieProperty('mimages', 0);
		}
    });
   
    
    //Show or hide secondary content
    $('#secondary-content').click(function() {
		if ($(this).attr("checked")){
    		setSecContent(1);
			setCookieProperty('scontent', 1);
		}else{
			setSecContent(0);
			setCookieProperty('scontent', 0);
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

    $('#favcounter').click(function() {
		var info = getcookie();
		var object = $.parseJSON(info);
		if ($("#favorites").hasClass('hidden')){
			setFavUrls(object.fav[0].urls);
		}else{
			$("#favorites").addClass('hidden');
			$("#bridge").addClass('hidden');
		}
    });

    $('#favcounter a').click(function(e) {
		e.preventDefault();
    });
    
	var showSearch = (function (){
		$("#imgsearch").addClass("hidden");
    	$("#frmsearch").removeClass("hidden");
    	$("#frmsearch").addClass("visible");
    	$("input[name='q']").focus();
	});

	var setFontsize = (function (info){
		var options=new Array("text-tiny","text-small","text-normal","text-big","text-huge");
		$("article").addClass(options[info]);
		for (i=0;i<options.length;i++){
			if (i==info){
				continue;
			}
			$("article").removeClass(options[i]);
		}
		render.infoTable();
		render.infoFigure();
		setCookieProperty('fontsize', info);
	});

	var setContrast = (function (info){
		var options=new Array("style-newspaper","style-novel","style-ebook","style-inverse","style-athelas");
		$('#'+options[info]).addClass('active');
		$("body").addClass(options[info]);
		for (i=0;i<options.length;i++){
			if (i==info){
				continue;
			}
			$('#'+options[i]).removeClass('active');
			$("body").removeClass(options[i]);
		}
	});

	var setAlignment = (function (info){
		var options=new Array("text-left","text-justify");
		other = (info==0)?1:0;
   	 	$("article").addClass(options[info]);
    	$("article").removeClass(options[other]);
	});

	var setHyphenation = (function (info){
		state = (info==0)?false:true;
   	 	Hyphenator.doHyphenation = state;
   	 	Hyphenator.toggleHyphenation();
	});
	
	var setMainFig = (function (show){
		if (show == 1){
			$("article .iocfigure").removeClass("hidden");
		}else{
			$("article .iocfigure").addClass("hidden");
		}
	});
	
	var setSecContent = (function (show){
		var elements = new Array("iocfigurec", "iocnote", "ioctext", "iocreference");
		for (i=0;i<elements.length;i++){
			if (show == 1){
				$("article ."+elements[i]).removeClass("hidden");
			}else{
				$("article ."+elements[i]).addClass("hidden");
			}
		}
	});

	var setArticleWidth = (function (info){
		var options=new Array("x-narrow","narrow","medium","wide","x-wide");
		$("article").addClass(options[info]);
		for (i=0;i<options.length;i++){
			if (i==info){
				continue;
			}
			$("article").removeClass(options[i]);
		}
		render.infoTable();
		render.infoFigure();
		setCookieProperty("width", info);
	});

	var setWidthSlider = (function (value){
		$("#slider-width").slider("option", "value", value);
	});
	
	var setFontSlider = (function (value){
		$("#slider-font").slider("option", "value", value);
	});

	var setCookieProperty = (function (n, value){
		var info=getcookie();
		if (info){
			if (typeof value == 'number'){
				var patt = new RegExp("\""+n+"\":\\d+", 'g');
				info=info.replace(patt, "\""+n+"\":"+value);
			}else{
				var patt = new RegExp("\""+n+"\":\".*?\"", 'g');
				info=info.replace(patt, "\""+n+"\":\""+value+"\"");
			}
			setcookie(info);
		}
	});

	var settings = (function (info){
		if (pageIndex()){
			$("body").removeClass().addClass('index');
		}else{
			setContrast(info.settings[0]['contrast']);
			setFontsize(info.settings[0]['fontsize']);
			setFontSlider(info.settings[0]['fontsize']);
			setAlignment(info.settings[0]['alignment']);
			setHyphenation(info.settings[0]['hyphen']);
			setArticleWidth(info.settings[0]['width']);
			setWidthSlider(info.settings[0]['width']);
			setMainFig(info.settings[0]['mimages']);
			setSecContent(info.settings[0]['scontent']);
			setCheckboxes(info);
		}
	});

	var setCheckboxes = (function (info){
		if(info.settings[0]['alignment']==1){
			$("#text-alignment").attr("checked","checked");
		}else{
			$("#text-alignment").removeAttr("checked");
		}
		if(info.settings[0]['hyphen']==0){
			$("#text-hyphen").attr("checked","checked");
		}else{
			$("#text-hyphen").removeAttr("checked");
		}
		if(info.settings[0]['mimages']==1){
			$("#main-images").attr("checked","checked");
		}else{
			$("#main-images").removeAttr("checked");
		}
		if(info.settings[0]['scontent']==1){
			$("#secondary-content").attr("checked","checked");
		}else{
			$("#secondary-content").removeAttr("checked");
		}
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
				$("#favcounter").css('left',-$("#aside").outerWidth()-$("#favcounter").outerWidth());
				setbackground(false);
			}
			setFavCounter(info.fav[0]['urls']);
		}
	});

	var setmenu = (function (obj, type){
		hideMenuOptions();
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
							editFavorite(document.location.pathname);
					 	}
				 	}
				 }
			}
		}
	});
	
	var enablemenuoption = (function(){
          var url = document.location.pathname;
          var dir = "WebContent/";
          //Comprovar si estem en les pàgines d'introduccio
		  var patt = new RegExp(dir,'g');
		  if (!patt.test(url)){
			node = url.match(/\w+(?=\.html)/);
    	  }else{
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
		      node = node.replace(/\.html/,'');
		      
		      if (parent_node != ''){
		          $('#'+parent_node).parent().each(function(key, value) {
		        	  $(this).show();
		          });
		          $('#'+parent_node+"> ul").show();
		          $('#'+parent_node+"> ul").closest("li").children("p").addClass("tocdown");
		      }
		}
        if (node != ''){
      	  $('#'+node).addClass("optselected");
        }
	});

	var setbackground = (function(show){
		if(!show){
			$("body").css("background-position","-60px 0");
		}else{
			$("body").css("background-position","0 0");
		}
	});

	var pageIndex = (function (){
		var url = document.location.pathname;
		return /index\.html|.*?\/(?!.*?\.html$)/.test(url);
	});

	var indexToc = (function(show){
	  	if (show){
			mtop = parseInt($(".meta").outerHeight(true))+parseInt($(".meta img").outerHeight(true));
	  		$(".meta").css('margin-top',-mtop);
	  		$(".metainfobc").addClass('hidden');
	  		$(".metainfobr").addClass('hidden');
			$("#header .head").css('margin-top', '0px');
			$("#header .headdocument").removeClass('hidden');
			$(".headtoc h1 img").addClass('rotateup');
			$(".headtoc").css('margin-top', -$("#content").outerHeight(true)+$(".headtoc h1").outerHeight(true)-40);
			if (parseInt($(window).height()) > parseInt($(".indextoc").outerHeight(true))){
				$(".headtoc").css('height','100%');
			}else{
				$(".headtoc").css('height','auto');
			}
			$("#headtoc").removeClass("headtopdown").addClass("headtopup");
			$(".indextoc").show();
		}else{
	  		$(".meta").css('margin-top','0');
			$(".metainfobc").removeClass('hidden');
			$(".metainfobr").removeClass('hidden');
		    $(".metainfobc").css('margin-top',$(window).height()-$(".headtoc h1").outerHeight(true)-$(".metainfobc").outerHeight()-5);
		    $(".metainfobr").css('margin-top',$(window).height()-$(".headtoc h1").outerHeight(true)-$(".metainfobr").outerHeight()-5);
		    htop = parseInt($(window).height()-$(".headtoc h1").outerHeight(true)-40);
		    if (htop < 0){
		    	htop = 0;
		    }
			$("#header .head").css('margin-top', htop);
			$("#header .headdocument").addClass('hidden');
			$(".headtoc h1 img").removeClass('rotateup');			
			$(".headtoc").css('margin-top', -$(".headtoc h1").outerHeight(true));
			$(".headtoc").css('height','auto');
			$(".indextoc").hide();
			$("#headtoc").removeClass("headtopup").addClass("headtopdown");	  		
		}
	});

	//Add or remove a url inside cookie
	var editFavorite = (function(url){
		var info = getcookie();
		if (info){
			var object = $.parseJSON(info);
			var urls = [];
			if(object.fav[0]['urls'].indexOf(url)!==-1){
				var patt = new RegExp(";;"+url+"\\|.*?(?=$|;;)", 'g');
				urls=object.fav[0]['urls'].replace(patt, "");
				setCookieProperty("urls", urls);
				setFavCounter(urls);
			}else{
				title = $("h1").text();
				title = title.replace(/ /,"");
				if (title == ""){
					title = url;
				}
				url = ";;" + url + "|" + title;
				setCookieProperty("urls", object.fav[0]['urls']+url);
				setFavCounter(object.fav[0]['urls']+url);
			}
		}
	});

	var setFavCounter = (function (urls){
		var patt = new RegExp(";;", 'g');
		var result = urls.match(patt);
		if(result && result.length > 0){
			$("#favcounter").removeClass("hidden");	
			$("#favcounter a").html(result.length);			
		}else{
			$("#favcounter").addClass("hidden");	
		}
	});

	var setFavButton = (function (info){
		if (info){
			var url = document.location.pathname;
			if(info.fav[0]['urls'].indexOf(url)!==-1){
				var obj = $("#menu li[name=favorites]").find('a>img')
				src = $(obj).attr('src');
				src = src.replace(/\w+(?=\.png)/, 'fav_ok');
				$(obj).attr('src', src);
			}
		}
	});

	var setFavUrls = (function (urls){
		var patt = new RegExp("[^;|]+\\|.*?(?=$|;;)", 'g');
		var result = urls.match(patt);
		hideMenuOptions();
		if(result && result.length > 0){
			var pattu = new RegExp('u\\d+','g');
			var patts = new RegExp('a\\d+','g');
			var unit = '';
			var section = '';
			var info = '';
			var list = '<ul>';
			for (url in result){
				data = result[url].split("|");
				unit = result[url].match(pattu);
				unit = (unit)?unit + " -> ":'';
				section = result[url].match(patts);
				section = (section)?section+" -> ":''
				info =  unit + section;
				list += '<li><a href="'+data[0]+'">'+info+' '+data[1]+'</a></li>';				
			}
			list += '</ul>';
			$("#favorites").html(list);
			$("#favorites").removeClass("hidden");		
			$("#bridge").removeClass('hidden').addClass("tinybridge");
			$("#bridge").css('top',$("#favorites").css('top'));
		}
	});

	var setNumberHeader = (function (){
		var url = document.location.pathname;
		var patt = new RegExp('/a\\d+(?=/)','g');
		result = url.match(patt);
		if (result){
			patt = new RegExp('\\d+','g');
			result = parseInt(result[0].match(patt));
			$("article").css("counter-reset","counth1 "+ (result-1));
		}
	});
	
	var setNumFigRef = (function (){
		$("article .iocfigure > a").each(function(i){
			$(".figref > a[href=\"#"+$(this).attr("name")+"\"]").append("."+(i+1));
		});
	});
	
	var setNumTabRef = (function (){
		$("article .ioctable .titletable > a").each(function(i){
			$(".tabref > a[href=\"#"+$(this).attr("name")+"\"]").append("."+(i+1));
		});
	});
	
	var hideMenuOptions = (function (){
		$('#toc').addClass('hidden');
		$('#settings').addClass('hidden');
		$("#favorites").addClass('hidden');
		$("#bridge").removeClass("tinybridge").addClass('hidden');
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
			setFavButton(object);
			if(pageIndex()){
				indexToc(object.toc[0]['tvisible']==1);
			}
		}else{
			if(pageIndex()){
				indexToc(!cookiesOK());
			}
			//Save default options
			var object = $.parseJSON(defaultsettings);
			settings(object);
			sidemenu(object);
			setcookie(defaultsettings);
		}
	});
	
	var cookiesOK = (function(){
		document.cookie = 'ioc_html_test="test";';
		if (/ioc_html_test/.test(document.cookie)){
			document.cookie= 'ioc_html_test=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
			return true;
		}
		return false;
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
		if (pageIndex()){
			indexToc($(".indextoc").css('display') !== 'none');
		}
	});
	
	//Show and hide list elements
	$(".expander ul").hide();
	$(".expander p").live("click", function() {
		$(this).toggleClass("tocdown");
	    var $nestList = $(this).siblings("ul");
	    if ($(this).parent().children("ul").css('display') != 'none'){
			$(this).parent().children("ul").css('display','none', function(){
				$(this).closest("li").children("p").removeClass("tocdown");
			});
		}else{
			$(this).parent().children("ul").show('fast');
		}
		$(this).parent().siblings().children().filter('ul').css('display', 'none', function(){
			$(this).closest("li").children("p").removeClass("tocdown");
		});
	});
	
	//$(".expander p > a[href='#']").live("click", function(e) {
	$("a[href='#']").live("click", function(e) {
		e.preventDefault();
	});

	$.expr[':'].parents = function(a,i,m){
	    return $(a).parents(m[3]).children('ul').length < 1;
	};
	$('li > p').filter(':parents(li)').addClass('arrow');
	
	//TOC
	$(".indextoc").hide();
	$(".headtoc").css('margin-top', -$(".headtoc h1").outerHeight(true)-$(".metainfobc").outerHeight());
	$(".headtoc h1").live("click", function() {
		var mtop = 0;
		if (parseInt($(".meta img").outerHeight(true)) > $(".meta").outerHeight(true)){
			mtop = parseInt($(".meta img").outerHeight(true));
		}else{
			mtop = parseInt($(".meta").outerHeight(true));
		}
		$(".meta").animate({
				'margin-top': parseInt($(".meta").css('margin-top'),10) == 0 ?
					-mtop :
					0,
				'visible':'inline'
		},1500);
		if ($(".indextoc").css('display') !== 'none'){
			$("#header .head").animate({
				'margin-top': $(window).height()-$(".headtoc h1").outerHeight(true)-$(".head").outerHeight(true)
			},1500);
			$("#header .headdocument").addClass('hidden');
			$(".headtoc h1 img").removeClass('rotateup');
			$(".metainfobc").removeClass('hidden');
			$(".metainfobr").removeClass('hidden');
			$(".headtoc").animate({
				'margin-top': -$(".headtoc h1").outerHeight(true)
			},1500, function(){$(".indextoc").hide();$(".headtoc").css('height','auto');});
			$("#headtoc").removeClass("headtopup").addClass("headtopdown");
			setCookieProperty('tvisible', 0);
		}else{
			$("#header .head").animate({
				'margin-top': '0px'
			},1500);
			$("#header .headdocument").removeClass('hidden');
			$(".headtoc h1 img").addClass('rotateup');
			$(".headtoc").animate({
				'margin-top': -$("#content").outerHeight(true)+$(".headtoc h1").outerHeight()-40
			},1500,function(){$(".metainfobc").addClass('hidden');$(".metainfobr").addClass('hidden');});
			if (parseInt($(window).height()) > parseInt($(".indextoc").outerHeight(true))){
				$(".headtoc").css('height','100%');
			}
			$("#headtoc").removeClass("headtopdown").addClass("headtopup");
			$(".indextoc").show();
			setCookieProperty('tvisible', 1);
		}
	});
	
	$("#slider-width").slider({
				min:0,
				max:4,
				step:1,
				range:'min',
				animate:true,
				slide: function( event, ui ) {
				   setArticleWidth(ui.value);
                  }
	});
	$("#slider-font").slider({
				min:0,
				max:4,
				step:1,
				range:'min',
				animate:true,
				slide: function( event, ui ) {
				   setFontsize(ui.value);
                  }
	});
	//Initialize menu and settings params
	get_params();
	setNumberHeader();
	setNumFigRef();
	setNumTabRef();
	return showSearch;
});
