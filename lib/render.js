jQuery(document).ready(function() {
	jQuery.expr[':'].parents = function(a,i,m){
	    return jQuery(a).parents(m[3]).length < 1;
	};
	jQuery('p > a > img').filter(':parents(.iocfigure)').parents('p').addClass('imgb');
	jQuery('p > a > img').filter(':parents(.iocfigure)').each(function(key, value){
		var element = jQuery('<div class="imgb"></div>');
		var remove = jQuery(this).closest('p');
		jQuery(remove).before(element);
		var anchor = jQuery(this).parent();
		jQuery(anchor).appendTo(element);
		var title = jQuery(this).attr('title');
		if (title){
			jQuery('<div class="title">'+title+'</div>').appendTo(element);
		}
		jQuery(remove).remove();
	});
});



