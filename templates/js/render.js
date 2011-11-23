define(function() {
	jQuery.expr[':'].parents = function(a,i,m){
	    return jQuery(a).closest(m[3]).length < 1;
	};

	var infoFigure = function(){
		jQuery('figure img').each(function(key, value){
			var img = jQuery(this);
			var width = img.width();
			var info = img.parents('figure').children().filter('figcaption');
			var foot = img.parents('.iocfigure').children().filter('.footfigure');
			info.css('width',width);
			foot.css('width',width);
		});
	};

	var infoTable = function(){
		jQuery('div.ioctable table, div.iocaccounting table').each(function(key, value){
			var table = jQuery(this);
			var width = table.width();
			var info = table.parents('.iocaccounting,.ioctable').children().filter('.titletable');
			var foot = table.parents('.iocaccounting,.ioctable').children().filter('.foottable');
			info.css('width',width);
			foot.css('width',width);
		});
	};
	return {"infoTable":infoTable,"infoFigure":infoFigure};
});