<script type="text/javascript">
    function _ehjkhjkl(node, data, img/*, Tooltip*/){
        node.innerHTML ="<img src='" + img 
        + "' alt='"+data+"' " 
        + "height='@HEIGHT@' width='@WIDTH@'/>";
        /*
        new Tooltip({
             connectId: ["@ID_DIV@"],
             label: data
        });
        */
    }
    
    function _dhjdksab(node, data){
        node.innerHTML = '<object seamlesstabbing="undefined" '
       + 'class="BrightcoveExperience" id="objvi@ID_VIDEO@" data="' 
       + data + '&videoID=@ID_VIDEO@" type="application/x-shockwave-flash" '
       + 'height="@HEIGHT@" width="@WIDTH@">'
       + ' <param value="always" name="allowScriptAccess"/>'
       + ' <param value="true" name="allowFullScreen"/>'
       + ' <param value="false" name="seamlessTabbing"/>'
       + ' <param value="true" name="swliveconnect"/>'
       + ' <param value="window" name="wmode"/>'
       + ' <param value="low" name="quality"/>'
       + ' <param value="#FFFFFF" name="bgcolor"/>'
       + ' <param value="false" name="play"/>'
       + '</object>';
    }
    
    function _akdsaghj(node, img){   
        jQuery.ajax({
            url: "//ioc.xtec.cat/secretaria/ioc/materials/videoService.php?type=@QUERY@&callback=?",
            crossDomain:true,
            dataType: "jsonp",
            success:function(/*PlainObjectData*/ text
                                , /*String*/ status
                                , /*jgXHR*/ jgXHR ){
                if(text.type=='data'){
                     _dhjdksab(node, text.value);
                }else{
                     _ehjkhjkl(node, text.value, img);
                }
            },
            error: function(/*jgXHR*/ jgXHR, /*String*/ error, /*String*/ ex){
                _ehjkhjkl(node, "ERROR EN CARREGAR EL VÍDEO.", img /*, Tooltip*/);
            }            
        });
        
        /*
        require(["dojo/ready", "dojo/request/script", "dijit/Tooltip"], 
                                            function(ready, request, Tooltip){
             ready(function(){                                            
                request.get("//ioc.xtec.cat/secretaria/ioc/materials/videoService.php", 
                        {handleAs: "json",
                        jsonp: "callback",
                        query: "type=@QUERY@"}).then(    
                   function(text){
                       if(text.type=='data'){
                            _dhjdksab(node, text.value);
                       }else{
                            _ehjkhjkl(node, text.value, img);
                       }
                   },function(error){
                       _ehjkhjkl(node, "ERROR EN CARREGAR EL VÍDEO.", img, Tooltip);
                   });
            });
        });
        */
    }
    
    var node = document.getElementById("@ID_DIV@");
    var img = "../../../img/film.png";
    if(window.location.protocol=="file:"){
        node.innerHTML ="<img src='" + img 
                  + "' alt='Per veure el vídeo cal estar connectat al campus' "
                  + "height='@HEIGHT@' width='@WIDTH@'/>";
        /*
        require(["dojo/ready", "dijit/Tooltip"], function(ready, Tooltip){
            ready(function(){
                new Tooltip({
                    connectId: ["@ID_DIV@"],
                    label: "Per veure el vídeo cal estar connectat al campus"
                });
            });
        });    
        */
    }else{
       _akdsaghj(node, img);
    }
</script>