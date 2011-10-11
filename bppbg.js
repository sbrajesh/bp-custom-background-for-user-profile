jQuery(document).ready(function(){
    
   var jq=jQuery;
   jq("#bppg-del-image").live('click',function(){
      var $this=jq(this);
      var noce='';
      
      jq.post(ajaxurl,{action:'bppg_delete_bg',
                      cookie:encodeURIComponent(document.cookie),
                       _wpnonce:jq($this.parents('form').get(0)).find('#_wpnonce').val()
          
      },
        function(response){
            //remove the current image
            $this.prev('current-bg').fadeOut(100);//hide current image
            $this.parent().remove();//remove from dom the delete link
        }
  

);
    return false;
       
   })
    
    
});