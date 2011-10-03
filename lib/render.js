jQuery(document).ready(function() {
	if (!JSINFO['plugin_iocexportl']['toccontents']){
		jQuery('#toc__inside').css('display', 'none');
	}
	
	jQuery.expr[':'].parents = function(a,i,m){
	    return jQuery(a).parents(m[3]).length < 1;
	};
	jQuery('p > a > img').filter(':parents(.iocfigure)').filter(':parents(.ioccontent)').parents('p').addClass('imgb');
	jQuery('p > a > img').filter(':parents(.iocfigure)').filter(':parents(.ioccontent)').each(function(key, value){
		var element = jQuery('<div class="imgb"></div>');
		var remove = jQuery(this).closest('p');
		jQuery(remove).before(element);
		var anchor = jQuery(this).parent();
		jQuery(anchor).appendTo(element);
		var title = jQuery(this).attr('title');
		if (title){
			title = title.replace(/\/[-+]?\w+$/gi,"");
		}
		if (title){
			jQuery('<div class="title">'+title+'</div>').appendTo(element);
		}
		jQuery(remove).remove();
	});

	jQuery('div.iocfigure img').each(function(key, value){
		var img = jQuery(this);
		var width = parseInt(img.attr('width'));
		var height = parseInt(img.attr('height'));
		widthaux = width * 1.5;
		if (widthaux > 800){
			widthaux = 800;
		}
		var url = img.attr('src');
		var patt = new RegExp("w=\\d+", 'g');
		var patt2 = new RegExp("h=\\d+", 'g');
		if (patt.test(url)){
			url=url.replace(patt, "w="+widthaux);
			jQuery(this).attr('src', url);	
		}
		if (height){
			var ratio = parseFloat(width/height);
			height = parseInt(widthaux/ratio);
			url=url.replace(patt2, "h="+height);
			jQuery(this).attr('src', url);	
			jQuery(this).attr('height', height);
		}
		jQuery(this).attr('width', widthaux);
	});
	
	infoFigure();
	infoTable();

	jQuery('p > img.latex_inline').filter('[title*=Fail]').each(function(key, value){
		jQuery(this).parent().remove();
	});
});

jQuery(window).resize(function() {
	infoFigure();
	infoTable();
});

var infoFigure = function(){
	jQuery('div.iocfigure img').each(function(key, value){
		var img = jQuery(this);
		var width = img.width();
		var info = img.parents('.iocfigure').children().filter('.iocinfo');
		var offset = img.offset();
		var margin = offset.left-parseInt(jQuery('#content').css('margin-left'))-15;
		info.css('margin-left',margin);
		info.css('width',width);				
	});
};

var infoTable = function(){
	jQuery('div.ioctable table').each(function(key, value){
		var table = jQuery(this);
		var width = table.width();
		var info = table.parents('.ioctable').children().filter('.iocinfo');
		var offset = table.offset();
		var margin = offset.left-parseInt(jQuery('#content').css('margin-left'))-15;
		info.css('margin-left',margin);
		info.css('width',width);				
	});
};
