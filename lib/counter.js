/**
 * Lib wiki page counter
 * 
 * @author     Marc Català <mcatala@ioc.cat>
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */
jQuery.noConflict();
jQuery(document).ready(function() {
	jQuery('#content ul > li > div > a[class=wikilink1]').each(function(key, value) {
  		var id = jQuery(this).attr('title').replace(/:/g,'_');
		var tag = jQuery('<span id="'+id+'"><img src="lib/plugins/iocexportl/img/loader.gif" alt="Generant arxiu" /></span>').appendTo(jQuery(this).parent());
		jQuery.ajax({
          	url: "lib/plugins/iocexportl/calculate.php",
          	global: false,
          	type: "POST",
          	data: "id=" + this.title,
          	dataType: "json",
          	async: true,
          	success: function(data, textStatus, xhr){
          		if(jQuery.isArray(data)){
          			if(data.length == 2){
          				tag.hide().fadeOut("slow").html("<strong> "+data[1]+" caràcters</strong>").fadeIn("slow");
          			}else{
          				tag.hide().fadeOut("slow").html("<strong> "+data[1]+" caràcters ("+data[2]+")</strong> | <strong> "+data[3]+" caràcters ("+data[4]+")</strong> | <strong>Total: " + (parseInt(data[1])+parseInt(data[3]))+" caràcters</strong>").fadeIn("slow");
          			}
          		}else{
          			tag.hide();
          		}
     		},
			error: function(xhr, textStatus, errorThrown){
				tag.hide().fadeOut("slow").html(xhr.responseText).fadeIn("slow");
      		}
        });
	});
});