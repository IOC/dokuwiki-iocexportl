<script type="text/javascript">
    function _akdsaghj(){        
        return 'http://c.brightcove.com/services/viewer/federated_f9/1326284612001?isVid=1&isUI=1&playerKey=AQ~~,AAABNMyTcTE~,zjiPB9Bfp4EykEGoTnvDHUfnwtGu2QvJ&videoID=@ID_VIDEO@';
    }
    var node = document.getElementById("@ID_DIV@");
    if(window.location.protocol=="file:"){
        var img = "../../../img/film.png";
        node.innerHTML ="<img src='" + img 
                  + "' alt='Per veure el vídeo cal estar connectat al campus' "
                  + "height='@HEIGHT@' width='@WIDTH@'/>";
        require(["dojo/ready", "dijit/Tooltip"], function(ready, Tooltip){
            ready(function(){
                new Tooltip({
                    connectId: ["@ID_DIV@"],
                    label: "Per veure el vídeo cal estar connectat al campus"
                });
            });
        });    
    }else{
        node.innerHTML = '<object seamlesstabbing="undefined" '
        + 'class="BrightcoveExperience" id="objvi@ID_VIDEO@" data="' 
        + _akdsaghj() + '" type="application/x-shockwave-flash" '
        + 'height="@HEIGHT@" width="@WIDTH@">'
        + ' <param value="always" name="allowScriptAccess"/>'
        + ' <param value="true" name="allowFullScreen"/>'
        + ' <param value="false" name="seamlessTabbing"/>'
        + ' <param value="true" name="swliveconnect"/>'
        + ' <param value="window" name="wmode"/>'
        + ' <param value="low" name="quality"/>'
        + ' <param value="#FFFFFF" name="bgcolor"/>'
        + '</object>';
    }
</script>