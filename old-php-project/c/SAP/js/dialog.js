;var SapDialog = SapDialog || {};
(function(dlg){
    $.extend(dlg,{});
    if (undefined === window.bootbox) return;
    
    
    
    var b;
    var win = $(window);
    var ifr=$('<iframe id="dialog-content" class="modal_frame" frameborder=0>&nbsp;</iframe>');
    var minHeight = 200, maxHeight = 1000;
    var minWidth = 500, maxWidth = 1200;
    
    ifr.attr('style','margin-left: auto; margin-right: auto;position: relative;  width:100%;');
    ifr.on('load',function(e){
        
            var ptitle = ifr.contents().find( "title").html( );
            if (ptitle!=''){
                b.find('.modal-title').html(ptitle);
            }
            ifr.contents().find( ".navbar").remove();
            ifr.contents().find( ".breadcrumb").remove();
            ifr.contents().find( "body").css('zoom',0.8);
            ifr.contents().find( ".outer").css('margin-top',0);
            
            var html = ifr.contents().find( "html");
            var height = html.height() ;
            var width = html.width() ;
            html.css('position','relative');
            
            maxHeight = win.height();
            minHeight = maxHeight / 2;
            maxHeight = maxHeight * 0.88;
            if (height < minHeight){ height = minHeight;}
            if (height > maxHeight){ height = maxHeight;}
            ifr.height(height);
            
            maxWidth = win.width();
            minWidth = maxWidth / 2; 
            maxWidth = maxWidth * 0.9;
            
            if (width < minWidth){ width = minWidth;}
            if (width > maxWidth){ width = maxWidth;}
            b.find('.modal-dialog').width(width);
        });
    var close = function(e){
        var event = jQuery.Event( "close.dialog.sap" , {target: dlg});
        $( document.body ).trigger( event );
        if ( event.isDefaultPrevented() ) {
            e.preventDefault();
        }
        if (typeof dlg.onClose != 'undefined'){
             console.log('DEPRECATED: SapDialog.onClose, you should bind to $( document.body ).on( "close.dialog.sap", function(){});')
             dlg.onClose(e);
        }
    }
    var show = function(o){
        if (undefined === o.src){
            o.src = o.href;
        }
        ifr.attr('src',o.src);
        o.message = ifr;
        b = bootbox.dialog(o);
        b.on("hidden.bs.modal", close);
        
        var event = jQuery.Event( "open.dialog.sap" , {target: dlg});
        $( document.body ).trigger( event );
    }
    var a_click = function(e){
        if (e.isDefaultPrevented()){
            return;
        }
        if (e.ctrlKey){
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        var o = {};
        var $this = $(this);
        if ($this.attr('title')){
             o.title = $this.attr('title');
        }
        o.src = $this.attr('href');
        show(o);
    }
    
    var render_in = function(){
        if (frameElement){
            var ifr = $(frameElement);
            if (ifr.is('.modal_frame')){
                var cbody = ifr.contents().find( "body");
                cbody.find( ".navbar").remove();
                cbody.find( ".breadcrumb").remove();
                cbody.css('zoom',0.8).css('min-height',400);
                cbody.find( ".outer").css('margin-top',0);
                cbody.find( "form .col-md-3").addClass('col-xs-3');
                cbody.find( "form .col-md-9").addClass('col-xs-9');
                cbody.find( "form .col-md-8").addClass('col-xs-8');
            }
        }
    }
    var init = function(){
        $('.dialog').off();
        $('body').on('click','a.dialog', a_click);
    }
    dlg.showHREF = function(url){
        var o = {title:' '};
        o.href = url;
        show(o);
    }
    dlg.attach = function(el){
        var $this = $(el);
        $this.off(a_click);
        $this.on('click',a_click);
    }
    dlg.show = show;
    /*
    
    if (navigator.userAgent.indexOf('Mac OS') != -1) {
        console.log('Mac OS, not dingin the dialog');
        return;
    }*/
    if (frameElement){
        SAP.ready(render_in);
    } else {
        SAP.ready(init);
    }
    console.log('SAP: dialog using bootbox');
})(SapDialog);