jQuery(document).ready(function() {
	jQuery.expr[':'].parents = function(a,i,m){
	    return jQuery(a).parents(m[3]).length < 1;
	};
	jQuery('p > a > img').filter(':parents(.iocfigure)').parents('p').addClass('imgb');
});



