define (["render"],function(render){
	var focussearch = false;
	var ltoc = parseInt($("#toc").css('left'),10);
	var lbridge = parseInt($("#bridge").css('left'),10);
	var lfavcount = parseInt($("#favcounter").css('left'),10);
	var lfavorites = parseInt($("#favorites").css('left'),10);
	var topheader = parseInt($("#header").css('height'),10);
	var defaultsettings = '{"menu":[{"mvisible":1}],"toc":[{"tvisible":0}],"settings":[{"fontsize":2,"contrast":0,"alignment":0,"hyphen":0,"width":2,"mimages":1,"scontent":1}]}';
	var defaultbookmarks = '{"fav":[{"urls":"0"}]}';
	var defaultbookquizzes = '{"quiz":[{"urls":"0"}]}';
	var showtooltips = false;
	
	$('#menu li').click(function(e) {
		setmenu(this);
	});
	
	$('#menu li div img').hover(
		function(e){
			if(showtooltips){
				showhelp($(this).closest("li"),true,false);
			}
		},
		function(e){
			if(showtooltips){
				showhelp($(this).closest("li"),false,false);
			}
		}
	);
	
	$('#sidebar-hide img').hover(
			function(e){
				if(showtooltips){
					showhelp($(this).parent(),true,false);
				}
			},
			function(e){
				if(showtooltips){
					showhelp($(this).parent(),false,false);
				}
			}
	);
	
	$('#search > form > input').hover(
			function(e){
				if(showtooltips){
					showhelp($(this).closest("div"),true,false);
				}
			},
			function(e){
				if(showtooltips){
					showhelp($(this).closest("div"),false,false);
				}
			}
	);
	
	$('#favcounter span').hover(
			function(e){
				if(showtooltips){
					showhelp($(this).parent(),true,false);
				}
			},
			function(e){
				if(showtooltips){
					showhelp($(this).parent(),false,false);
				}
			}
		);

	$('#content').click(function(e) {
		setmenu(null);
		$("#help").addClass("hidden");
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
		var info=getcookie('ioc_html');
		var patt=/"mvisible":0/g;
	  	if (patt.test(info)){
			setCookieProperty('ioc_html','mvisible', 1);
		}else{
			setCookieProperty('ioc_html','mvisible', 0);
		}
	});

    //Contrast
    $('#style-newspaper').click(function() {
   	 	setContrast(0);
		setCookieProperty('ioc_html','contrast', 0);
    });
    
    $('#style-novel').click(function() {
    	setContrast(1);
		setCookieProperty('ioc_html','contrast', 1);
    });
    
    $('#style-ebook').click(function() {
    	setContrast(2);
		setCookieProperty('ioc_html','contrast', 2);
    });

    $('#style-inverse').click(function() {
    	setContrast(3);
		setCookieProperty('ioc_html','contrast', 3);
    });

    $('#style-athelas').click(function() {
    	setContrast(4);
		setCookieProperty('ioc_html','contrast', 4);
    });
    
    //Alignment
    $('#text-alignment').click(function() {
		if ($(this).attr("checked")){
			setAlignment(1);
			setCookieProperty('ioc_html','alignment', 1);
		}else{
    		setAlignment(0);
			setCookieProperty('ioc_html','alignment', 0);
		}
    });
    
    //Hyphenation
    $('#text-hyphen').click(function() {
		if ($(this).attr("checked")){
    		setHyphenation(0);
			setCookieProperty('ioc_html','hyphen', 0);
		}else{
    		setHyphenation(1);
			setCookieProperty('ioc_html','hyphen', 1);
		}
    });
    
    
    //Show or hide secondary content
    $('#main-images').click(function() {
		if ($(this).attr("checked")){
			setMainFig(1);
			render.infoFigure();
			setCookieProperty('ioc_html','mimages', 1);
		}else{
			setMainFig(0);
			setCookieProperty('ioc_html','mimages', 0);
		}
    });
   
    
    //Show or hide secondary content
    $('#secondary-content').click(function() {
		if ($(this).attr("checked")){
    		setSecContent(1);
			setCookieProperty('ioc_html','scontent', 1);
		}else{
			setSecContent(0);
			setCookieProperty('ioc_html','scontent', 0);
		}
    });
    
    
    $('#upbutton').click(function() {
    	   	 	$("html,body").animate({
    	   	 		scrollTop: 0
    	   	 	}, 500);
    });
    
    $('#navmenu').click(function() {
		setCookieProperty('ioc_html','selected','null');
    });

    $('#favcounter').click(function() {
		var info = getcookie('ioc_bookmarks');
		var object = $.parseJSON(info);
		if ($("#favorites").hasClass('hidden')){
			setFavUrls(object.fav[0].urls);
		}else{
			$("#favorites").addClass('hidden');
			$("#bridge").addClass('hidden');
		}
    });

   
	$("#frmsearch input[name='q']").on('keypress', function (event){
		if (event.which === 13){
			$("#frmsearch").submit();
		}
	});
	
	$("#frmsearch").focusin(function(){
		focussearch = true;
	});
	
	$("#frmsearch").focusout(function(){
		focussearch = false;
	});
	
	$(window).on('keypress', function (event){
		if(ispageIndex() || ispageSearch()){
			return;
		}
		//ESC
		if (event.which === 0){
			event.preventDefault();
			$("#help").addClass("hidden");
			setmenu(null);
		}else{
			//?
			if (event.which === 63){
				$("#help").toggleClass("hidden");
			}else{
				if (!focussearch){
					switch(event.which){
						//b
						case 98:$(window).scrollTop($("footer").offset().top);
							 break;
					
						//h
						case 104:$(window).scrollTop(0);
								 break;
						//i
						case 105:document.location.href = $("#navmenu ul > li > a").attr("href");
				 		 		 break;
		 		 		//j
						case 106:$(window).scrollTop($(window).scrollTop()+100)
						 		 break;
						//k
						case 107:$(window).scrollTop($(window).scrollTop()-100);
						 		 break;
				 		//o
						case 111:setmenu($("#menu li[name='settings']"));
						 		 break;
						//p
						case 112:setmenu($("#menu li[name='printer']"));
						 		 break;
				 		//s
						case 115:setmenu($("#menu li[name='favorites']"));
						 		 break;
				 		//t					 		 
						case 116:setmenu($("#menu li[name='toc']"));
				 		 		 break;
					}
				}
			}
		}
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
		setCookieProperty('ioc_html','fontsize', info);
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
		setCookieProperty('ioc_html','width', info);
	});

	var setWidthSlider = (function (value){
		$("#slider-width").slider("option", "value", value);
	});
	
	var setFontSlider = (function (value){
		$("#slider-font").slider("option", "value", value);
	});

	var setCookieProperty = (function (name, n, value){
		var info=getcookie(name);
		if (info){
			if (typeof value == 'number'){
				var patt = new RegExp("\""+n+"\":\\d+", 'g');
				info=info.replace(patt, "\""+n+"\":"+value);
			}else{
				var patt = new RegExp("\""+n+"\":\".*?\"", 'g');
				info=info.replace(patt, "\""+n+"\":\""+value+"\"");
			}
			setcookie(name,info);
		}
	});

	var settings = (function (info){
		if (ispageIndex()){
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
			postohashword();
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
		if (!ispageIndex()){
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
			info = getcookie('ioc_bookmarks');
			if (info){
				var object = $.parseJSON(info);
				setFavCounter(object.fav[0]['urls']);
			}
		}
	});

	var setmenu = (function (obj){
		hideMenuOptions();
		if (!obj){
			$("#menu ul").children().each(function(i){
				$(this).removeClass('menuselected');
			});
			return;	
		}
		var show = true;
		var type = $(obj).attr("name");
		$(obj).closest('ul').children().each(function(i){
			if (type === $(this).attr("name") && $(obj).hasClass('menuselected')){show=false;}
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
							url = $(obj).find('div>img').attr('src');
							if (/favorites/.test(url)){
								url = url.replace(/favorites/, 'fav_ok');
							}else{
								url = url.replace(/fav_ok/, 'favorites');
							}
							$(obj).find('div>img').attr('src', url);
							editFavorite(document.location.pathname,false);
					 	}else{
							if(type === 'help_icon'){
								url = $(obj).find('div>img').attr('src');
								if (/help_icon\./.test(url)){
									url = url.replace(/help_icon/, 'help_icon_active');
									showtooltips = true;
								}else{
									url = url.replace(/help_icon_active/, 'help_icon');
									hidetooltips();
									showtooltips = false;
								}
								$(obj).find('div>img').attr('src', url);
						 	}
					 	}
				 	}
				 }
			}
		}
	});
	
	var calpostooltips = (function (){
		$("#menu li").each(function(){
			var type = $(this).attr("name");
			var tooltip = $('#help-'+type);
			var item_pos = $(this).offset();
			tooltip.css('top',item_pos.top-tooltip.outerHeight(true) - 12);
		});
		var item = $("#favcounter").offset();
		$("#help-favcounter").css('top',item.top-$("#help-favcounter").outerHeight(true) - 25);
		item = $("#sidebar-hide").offset();
		$("#help-sidebar-hide").css({top:item.top-$("#help-sidebar-hide").outerHeight(true) - 25,
									left:item.left + 40
		});
		item = $("#search input[type='text']").offset();
		$("#help-search").css({top:item.top + $("#help-search").outerHeight(true) + 15,
							  left:item.left + ($("#help-search").outerWidth(true)/2) - 157
		});
	});
	
	var hidetooltips = (function (){
		$("#help-tooltips > div").each(function(){
			$(this).addClass("hidden");
		});
	});

	
	var showhelp = (function (obj, show, header){
		var type = (header)?'header':$(obj).attr("name");
		var tooltip = $('#help-'+type);
		if(show){
			tooltip.removeClass('hidden');
			tooltip.fadeTo("fast", 0.8);
		}else{
			tooltip.fadeTo("fast", 0, function(){tooltip.addClass('hidden');});
		}

		if (header && show){
			var item_pos = $(obj).offset();
			tooltip.css({top:item_pos.top - (tooltip.outerHeight()/2) + 13,
						left:item_pos.left + $(obj).width() + 40
			});
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
		          $('#'+parent_node+"> p > .buttonexp").addClass("tocdown");
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

	var ispageIndex = (function (){
		var url = document.location.pathname;
		return /index\.html|.*?\/(?!.*?\.html$)/.test(url);
	});
	
	var ispageSearch = (function (){
		var url = document.location.pathname;
		return /search\.html/.test(url);
	});
	
	var ispageExercise = (function (){
		var url = document.location.pathname;
		return /activitats\.html|exercicis\.html/.test(url);
	});
	
	var ispagenoHeader = (function (){
		var options= "glossari|mapa|objectius|presentacio|referencies|resum|mapa";
		var url = document.location.pathname;
		var patt = new RegExp(options+"\.html$","g")
		return patt.test(url);
	});
	
	var postohashword = (function (){
		var url = document.location.hash;
		if (url){
			url = url.replace(/#/,'');
			var offset = $("a[id='"+url+"']").offset();
			if (offset !== null){
				$(window).scrollTop(offset.top-110);
			}
		}
	});
	
	var postosearchword = (function (){
		var url = document.location.search;
		if (/highlight/.test(url)){
				var offset = $(".highlight:first").offset();
				if (offset !== null){
					$(window).scrollTop(offset.top-80);
				}
		}
	});
	

	var indexToc = (function(show){
	  	if (show){
			mtop = parseInt($(".meta").outerHeight(true))+parseInt($(".meta img").outerHeight(true));
	  		$(".meta").css('margin-top',-mtop);
	  		$(".metainfobc").addClass('hidden');
	  		$(".metainfobr").addClass('hidden');
			$("#header .head").css('margin-top', '0px');
			$("#header .headdocument").removeClass('hidden');
			$(".headtoc h1 img").hide();
			$(".headtoc").css('margin-top', -$("#content").outerHeight(true)+$(".headtoc h1").outerHeight(true)-40);
			if (parseInt($(window).height()) > parseInt($(".indextoc").outerHeight(true))){
				$(".headtoc").css('height','100%');
			}else{
				$(".headtoc").css('height','auto');
			}
			$("#headtoc").removeClass("headtopdown").addClass("headtopup");
			$(".headtoc h1").removeClass("hover");
			$(".indextoc").show();
			$("#button_start").show();
		}else{
	  		$(".meta").css('margin-top','5px');
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
			$(".headtoc h1 img").show();			
			$(".headtoc").css('margin-top', -$(".headtoc h1").outerHeight(true));
			$(".headtoc").css('height','auto');
			$(".indextoc").hide();
			$("#headtoc").removeClass("headtopup").addClass("headtopdown");
			$(".headtoc h1").addClass("hover");
			$("#button_start").hide();
		}
	});

	//Add or remove a url inside cookie
	var editFavorite = (function(url,idheader){
		var info = getcookie('ioc_bookmarks');
		if (info){
			var object = $.parseJSON(info);
			var urls = [];
			var patt = new RegExp(";;"+url+"\\|.*?(?=$|;;)", 'g');
			if(patt.test(object.fav[0]['urls'])){
				urls=object.fav[0]['urls'].replace(patt, "");
				setCookieProperty('ioc_bookmarks','urls', urls);
				setFavCounter(urls);
			}else{
				if (idheader){
					title = $("h2,h3,h4").children("a").filter("a[id="+idheader+"]").parent().text();
				}else{
					title = $("h1").text();
					title = title.replace(/ /,"");
				}
				if (title == ""){
					title = url;
				}
				url = object.fav[0]['urls'] + ";;" + url + "|" + title;
				setCookieProperty('ioc_bookmarks','urls', url);
				setFavCounter(url);
			}
		}
	});

	var setFavCounter = (function (urls){
		var patt = new RegExp(";;", 'g');
		var result = urls.match(patt);
		if(result && result.length > 0){
			$("#favcounter").removeClass("hidden");	
			$("#favcounter span").html(result.length);			
		}else{
			$("#favcounter").addClass("hidden");	
		}
	});

	var setFavButton = (function (info){
		if (info){
			var url = document.location.pathname;
			var patt = new RegExp(";;"+url+"\\|.*?(?=$|;;)", 'g');
			if(patt.test(info.fav[0]['urls'])){
				var obj = $("#menu li[name=favorites]").find('div>img')
				src = $(obj).attr('src');
				src = src.replace(/\w+(?=\.png)/, 'fav_ok');
				$(obj).attr('src', src);
			}
		}
	});

	var setFavHeaders = (function (info){
		if (info){
			var url = document.location.pathname;
			var patt;
			$("h2").each(function(i){
				patt = new RegExp(";;"+url+"#"+$(this).children("a").attr("id")+"\\|.*?(?=$|;;)", 'g');
				if(patt.test(info.fav[0]['urls'])){
					$(this).children("span[name='star']").removeClass().addClass("starmarked").show();
				}
			});
		}
	});

	var setCheckExercises = (function (info){
		if (info){
			var url = document.location.pathname;
			var patt;
			$("h2").each(function(i){
				patt = new RegExp(";;"+url+"\\|"+$(this).children("a").attr("id"), 'g');
				if(patt.test(info.quiz[0]['urls'])){
					$(this).children("span[name='check']").addClass("check").css('display','inline-block');
				}
			});
		}
	});

	var editCheckExercise = (function(url,idheader){
		var info = getcookie('ioc_quizzes');
		if (info){
			var object = $.parseJSON(info);
			var urls = [];
			var patt = new RegExp(";;"+url+"\\|"+idheader, 'g');
			if(!patt.test(object.quiz[0]['urls'])){
				url = object.quiz[0]['urls'] + ";;" + url + "|" + idheader;
				$("h2 > a[id='"+idheader+"']").siblings("span[name='check']").addClass("check").css('display','inline-block');
				setCookieProperty('ioc_quizzes','urls', url);
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
			var list = '<div class="menucontent">';
			list += '<ul class="favlist">';
			result.sort();
			for (url in result){
				data = result[url].split("|");
				unit = result[url].match(pattu);
				unit = (unit)?unit + "<span></span>":'';
				section = result[url].match(patts);
				section = (section)?section+"<span></span>":''
				info =  unit + section;
				list += '<li><a href="'+data[0]+'">'+info+' '+data[1]+'</a></li>';				
			}
			list += '</ul>';
			list += '</div>';
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
	var setcookie = (function(name, value){
		document.cookie = name+'=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
		document.cookie = name+"=" + escape(value) + "; path=/;";
	});

	//get params from our cookie    	
	var getcookie = (function(name){
		var i,x,y,ARRcookies=document.cookie.split(";");
		for (i=0;i<ARRcookies.length;i++)
		{
		  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
		  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
		  x=x.replace(/^\s+|\s+$/g,"");
		  if (x==name){
			return unescape(y);
		  }
		}
	});

	var get_params = (function(reset){
		var info = getcookie('ioc_html');
		if (info!=null && info!="" && !reset){
			//Get and apply stored options
			var object = $.parseJSON(info);
			settings(object);
			sidemenu(object);
			if(ispageIndex()){
				indexToc(object.toc[0]['tvisible']==1);
			}
			info = getcookie('ioc_bookmarks');
			if (info!=null && info!=""){
				object = $.parseJSON(info);
				setFavButton(object);
				setFavHeaders(object);
			}else{
				setcookie('ioc_bookmarks',defaultbookmarks);
			}
			info = getcookie('ioc_quizzes');
			if (info!=null && info!=""){
				object = $.parseJSON(info);
				setCheckExercises(object);
			}else{
				setcookie('ioc_quizzes',defaultbookquizzes);
			}

		}else{
			if(ispageIndex()){
				indexToc(!cookiesOK());
			}
			//Save default options
			var object = $.parseJSON(defaultsettings);
			settings(object);
			sidemenu(object);
			setcookie('ioc_html',defaultsettings);
			setcookie('ioc_bookmarks',defaultbookmarks);
			setcookie('ioc_quizzes',defaultbookquizzes);
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
		if (ispageIndex()){
			indexToc($(".indextoc").css('display') !== 'none');
		}else{
			if(!ispageSearch()){
				calpostooltips();
			}
		}
	});
	
	//Show and hide list elements
	$(".expander ul").hide();
	$(".expander .tocsection .buttonexp").on("click", function() {
		$("#toc .buttonexp").removeClass("tocdown");
		var parent = $(this).closest("li");
		if ($(this).closest("p").siblings("ul").css('display') == 'none'){
			$(this).addClass("tocdown");
		}
	    if ($(parent).children("ul").css('display') != 'none'){
			$(parent).children("ul").hide('fast');
		}else{
			$(parent).children("ul").show('fast');
		}
		$(parent).siblings().children().filter('ul').hide('fast');
	});

	$(document).on("click", "a[href='#']", function(e) {
		e.preventDefault();
	});

	$.expr[':'].parents = function(a,i,m){
	    return $(a).parents(m[3]).children('ul').length < 1;
	};
	
	//TOC
	$(".indextoc").hide();
	$("#button_start").hide();
	$(".headtoc").css('margin-top', -$(".headtoc h1").outerHeight(true)-$(".metainfobc").outerHeight());
	$(".headtoc h1").on("click", function() {
		if ($(".indextoc").css('display') == 'none'){
			var mtop = 0;
			if (parseInt($(".meta img").outerHeight(true)) > $(".meta").outerHeight(true)){
				mtop = parseInt($(".meta img").outerHeight(true));
			}else{
				mtop = parseInt($(".meta").outerHeight(true));
			}
			$(".meta").animate({
					'margin-top': parseInt($(".meta").css('margin-top'),10) == 5 ?
						-mtop :
						'5px',
					'visible':'inline'
			},1500);
			$("#header .head").animate({
				'margin-top': '0px'
			},1500);
			$("#header .headdocument").removeClass('hidden');
			$(".headtoc").animate({
				'margin-top': -$("#content").outerHeight(true)+$(".headtoc h1").outerHeight()-40
			},1500,function(){
						$(".metainfobc").addClass('hidden');
						$(".metainfobr").addClass('hidden');
					}
			);
			$(".headtoc h1 img").slideUp("slow");
			if (parseInt($(window).height()) > parseInt($(".indextoc").outerHeight(true))){
				$(".headtoc").css('height','100%');
			}
			$("#headtoc").removeClass("headtopdown").addClass("headtopup");
			$(".headtoc h1").removeClass("hover");
			$(".indextoc").show();
			$("#button_start").slideDown("slow");
			setCookieProperty('ioc_html','tvisible', 1);
		}
	});
	
	$("#button_start").on("click", function() {
		var mtop = 0;
		if (parseInt($(".meta img").outerHeight(true)) > $(".meta").outerHeight(true)){
			mtop = parseInt($(".meta img").outerHeight(true));
		}else{
			mtop = parseInt($(".meta").outerHeight(true));
		}
		$(".meta").animate({
				'margin-top': parseInt($(".meta").css('margin-top'),10) == 5 ?
					-mtop :
					'5px',
				'visible':'inline'
		},1500);
		$("#header .head").animate({
			'margin-top': $(window).height()-$(".headtoc h1").outerHeight(true)-$(".head").outerHeight(true)
		},1500);
		$("#header .headdocument").addClass('hidden');
		$(".metainfobc").removeClass('hidden');
		$(".metainfobr").removeClass('hidden');
		$(".headtoc").animate({
			'margin-top': -$(".headtoc h1").outerHeight(true)
		},1500, function(){
					$(".indextoc").hide();
					$(".headtoc").css('height','auto');
				}
		);
		$(this).slideUp("slow");
		$(".headtoc h1 img").slideDown("slow");
		$(".headtoc h1").addClass("hover");
		$("#headtoc").removeClass("headtopup").addClass("headtopdown");
		setCookieProperty('ioc_html','tvisible', 0);
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

	$("h2,h3,h4").each(function(i){
		if(ispageExercise()){
			$(this).append('<span name="check"></span>');
		}
		$(this).append('<span class="star" name="star"></span>').children("span").hide();
	});
	
	$("h1 > a").hover( 
			function(){
				if(showtooltips){
					showhelp($(this),true,true);
				}
			},
			function(){
				if(showtooltips){
					showhelp($(this),false,true);
				}
			}
	);
	
	$("h2 > a,h3 > a,h4 > a").hover( 
		function(){
			if ($(this).siblings("span[name='star']").hasClass("star")){
				$(this).siblings("span[name='star']").show();
			}
			if(showtooltips){
				showhelp($(this),true,true);
			}
		},
		function(){
			if ($(this).siblings("span[name='star']").hasClass("star")){
				$(this).siblings("span[name='star']").hide();
			}
			if(showtooltips){
				showhelp($(this),false,true);
			}
		}
	);

	$(document).on("click", "h2,h3,h4", function(){
		var id = $(this).children("a").attr("id");
		editFavorite(document.location.pathname+"#"+id,id);
		if ($(this).children("span[name='star']").hasClass("star")){
			$(this).children("span[name='star']").removeClass().addClass("starmarked");
			$(this).children("span[name='star']").show();
		}else{
			$(this).children("span[name='star']").removeClass().addClass("star");
		}	
	});


	//Initialize menu and settings params
	get_params();
	setNumberHeader();
	setNumFigRef();
	setNumTabRef();
	if(ispagenoHeader()){
		$("h1,h2,h3").each(function(i){
			$(this).addClass('nocount');
		});
	}
	if(ispageExercise()){
		$("h2").each(function(i){
			$(this).addClass('nocount');
		});
	}
	if(!ispageIndex() && !ispageSearch()){
		calpostooltips();
	}
	return {"editCheckExercise":editCheckExercise,
			"ispageSearch":ispageSearch,
			"postosearchword":postosearchword};
});
