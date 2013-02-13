<script type="text/javascript">
    function _akdsaghj(){
        return "http://bcove.me/@ID_VIDEO@";
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
        node.innerHTML = "<iframe height='@HEIGHT@' width='@WIDTH@' src='"
                  + _akdsaghj() + "'></iframe>";
    }
</script>