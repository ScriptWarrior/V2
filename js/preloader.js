// gif preloader
var preloader_obj
var preloader_running=0
function clear_preloader()
{
        $("#"+this.preloader_id).remove()
        preloader_running=0
}
function preloader(message,preloader_id,parent_id)
{       
        // methods
        this.clear_preloader=clear_preloader
        // fields
        // CONFIG
        this.preloader_height=200
        this.preloader_width=200
        // param fields
        this.message=message
        this.preloader_id=preloader_id
        this.parent_id=parent_id
        $("#"+this.preloader_id,"#preloader_css").remove() // just in case clear after last instance
        var left=Math.ceil((document.body.clientWidth/2)-(this.preloader_width/2))
        var embed='<img height="75" width="75" src="/img/preloader.gif" alt="preloader" style="margin-top:40px" />'
        var html='<div class="preloader_container" style="position:absolute; background-color: white; color: #9d9494; width: 200px; height:200px; text-align:center; font-size:15px; font-weight:bold; z-index: 404; float: center;top: '+$("#"+this.parent_id).offset().top+'px; left: '+left+'px; display:none; visibility:hidden" id="'+this.preloader_id+'">'+this.message+'<br />'+embed+'</div>'
        $("body").append(html)
        $('.preloader_container').css('width',this.preloader_width).css({'height':this.preloader_height,'display':'block','visibility':'visible'})
        preloader_running=1
}
// example usage:
// preloader=new preloader("Making connection....","register_preloader",""register_form")
// $.ajax{costam...,success:function(){preloader.clear_preloader(); dialog_window()...}}