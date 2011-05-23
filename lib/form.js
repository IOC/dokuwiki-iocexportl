/**
 * Lib wiki form export
 * 
 * @author     Marc Català <mcatala@ioc.cat>
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */
jQuery.noConflict();
jQuery("#export__form").submit(function() {
		jQuery.ajax({
          	url: "lib/plugins/iocexportl/generate.php",
          	global: false,
            beforeSend: function(){
					jQuery("#exportacio").html('<img src="lib/plugins/iocexportl/templates/loader.gif" alt="Generant arxiu" />').fadeIn("slow");
					jQuery("#id_submit").attr("disabled", "disabled");
            },
          	type: "POST",
          	data: "id="+ jQuery("input[name='pageid']").val() +"& mode="+ jQuery("input[name='mode']:checked").val(),
          	dataType: "json",
          	async: false,
          	success: function(data, textStatus, xhr){
                if(jQuery.isArray(data)){
                	if(data[0] == 'pdf'){
                   		jQuery("#exportacio").hide().fadeOut("slow").html('<a class="media mediafile mf_pdf" href="'+data[1]+'">'+data[2]+'</a>'+' <strong>|</strong> Pàgines: ' + data[4]+ ' <strong>|</strong> Mida: ' + data[3] + ' <strong>|</strong> Temps emprat: ' + data[5] + ' segons').fadeIn("slow");
            			}else if(data[0] == 'zip'){
                           jQuery("#exportacio").hide().fadeOut("slow").html('<a class="media mediafile mf_zip" href="'+data[1]+'">'+data[2]+'</a>'+' <strong>|</strong> Mida: ' +data[3]+ ' <strong>|</strong> Temps emprat: ' + data[4] + ' segons').fadeIn("slow");
            			}else{
            				jQuery("#exportacio").hide().fadeOut("slow").html('<strong>'+data[5]+'</strong><a class="media mediafile mf_txt" href="'+data[1]+'">'+data[2]+'</a>'+' <strong>|</strong> Mida: '+ data[3]+ ' <strong>|</strong> Temps emprat: ' + data[4] + ' segons').fadeIn("slow");
            			}
        		}else{
					jQuery("#exportacio").hide().fadeOut("slow").html('<strong>'+data+'</strong>').fadeIn("slow");
        		}
        		jQuery("#id_submit").removeAttr("disabled");
      		},
			error: function(xhr, textStatus, errorThrown){
                alert(xhr.responseText);
            			jQuery("#exportacio").html('<strong>ERROR!</strong>');
            			jQuery("#id_submit").removeAttr("disabled");
      		}
        });
		return false;
	});
