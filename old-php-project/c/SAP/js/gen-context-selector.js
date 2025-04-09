/*
options:
 - title
 - source
 - item-class [default: text-green]
 - demo
 -sources [S]  : an array of additional sources which can be triggered by clicking a button
 --- S = {'text':'button label','class':'text-green', 'source':'uri/to/load'}

*/

;(function(){
    //TODO: if option.sources or option.source change then reload
    function GeneralContextSelection(opt){
        var self = this;
        $.extend(self,Bindable());
        
        var dlg, ul, elLoading;
        var is_setup =false;
        function updateOption(opt){
            if (typeof opt === 'undefined') return;
            if (opt.on){
                $.each( opt.on, function( event,fx ){
                    self.on(event,fx);
                });
                delete opt.on;
            }
            self.setOption(opt);
        }
        function setUp(){
            if (is_setup) return;
            var  dlgstring = "<div class='modal fade general-context-selection-dialog' role='dialog'>"
                +"<div class='modal-dialog'><div class='modal-content'>"
                +"<div class='modal-header'><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>"
                +"<h4 class='modal-title'></h4></div>"
                +"<div class='modal-body'><div id='dlg_to_list_search'>"
                +"<div class='input-group'><input class='form-control' placeholder='type to search' type='search'>"
                +"<span class='input-group-btn'></span></div>"
                +"<div class='progress progress-striped active' style='margin-bottom:0;'><div class='progress-bar' style='width: 100%'></div></div>"
                +"<ul class='list-group'></ul></div></div></div></div></div>";
                 
            dlg = $(dlgstring);
            dlg.appendTo('body');
            ul = dlg.find('ul');
            elLoading=dlg.find('.progress');
            
            ul.css({'overflow-y': 'scroll','max-height': '300px','min-height': '180px'});
            elLoading.hide();
            
            dlg.find('button').on('click',btnTypeClick);
            dlg.find('input').on('keyup',inputSearchKeyup);
            ul.on('click','li',listItemClick);
            
            
            //more sources
            if (self.getOption('sources')){
                var sources = self.getOption('sources');
                var bg = dlg.find('.input-group-btn');
                for (var i =0, l=sources.length;i<l;i++){
                    var source = sources[i];
                    var b = $('<button>');
                    if (source.attr){
                        b.attr(source.attr);
                        delete source.attr;
                    }
                    if (source.text){
                        b.append(source.text);
                        delete source.text;
                    }
                    if (source.source){
                        b.data('source',source.source);
                        delete source.source;
                    }
                    if (source){
                        b.data(source);
                    }
                    b.addClass('btn btn-small');
                    b.on('click',handleMoreClick);
                    bg.append(b);
                }
            } else {
                dlg.find('.input-group').removeClass('input-group');
                dlg.find('.input-group-btn').hide();
            }
            
            is_setup =true;
            console.log('is_setup', is_setup);
        }
        function update(){
            var t = self.getOption('title');
            dlg.find('.modal-title').html(t);
            var active_types = 1;
            
            if (self.getOption('preload') || (active_types ==1)){
                preload();
            }
            afterDisplay();
        }
        function preload(){
            load();
        }
        function searchOnKey(list$,filter){
            if (filter==''){
                list$.find('li').show();
                return ;
            }
            
            list$.find('li').each(function(ind,el){
                if (searchVal != filter){
                    return false;
                }
                var el = $(this);
                if (el.text().toUpperCase().indexOf(filter) == -1) 
                    el.hide();
                else
                    el.show();
            });
        }    
        function sortList(list$){
            list$.find('li').detach().sort(sortLI).each(function(index, el){
                list$.append(el);
            });
        }
        function sortLI(a, b){
            var a$ = $(a), b$ = $(b);
            var compA = (a$.data('sort'))? a$.data('sort'): a$.text().trim().toUpperCase();
            var compB = (b$.data('sort'))? b$.data('sort'): b$.text().trim().toUpperCase();
            return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
        }
    
        function btnTypeClick(evt){
            var el = $(this);
            if (el.is('button.close')){
                var o = {};
                o.data = $.extend({},el.data());
                o.target =el;
                o.instance = self;
                o.originalEvent = evt;
                var e = jQuery.Event( "close",o );
                self.trigger('close',e);
                if (evt.isDefaultPrevented() || e.isDefaultPrevented()){
                    evt.preventDefault();
                    evt.stopPropagation();
                }
            }
            
        }
        function handleMoreClick(evt){
            evt.preventDefault();
            evt.stopPropagation();
            var el = $(this);
            var d = el.hide().data();
            if (d.source){
                showLoading();
                SAP.get(d.source ,function(r){
                        parseAjaxResults(r,d)
                    }).fail(function(){
                        errorLoading();
                        el.show();
                    });
            }
            
            
            var bg = dlg.find('.input-group-btn');
            if (bg.find('button:visible').length == 0){
                bg.hide();
                dlg.find('.input-group').removeClass('input-group');
            }
        }
        function listItemClick(evt){
            var el = $(this);
            var o = {};
            o.data = $.extend({},el.data());
            o.target = el;
            o.instance = self;
            o.originalEvent = evt;
            var e = jQuery.Event( "select",o );
            
            self.trigger('select',e);
            if (!e.isDefaultPrevented()){
                el.hide();
            }
            
        }
        var searchTimeout, searchVal;
        function inputSearchKeyup(){
            var me = $(this);
            var filter = me.val().trim().toUpperCase();
            if (searchVal != filter){
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function(){
                    searchVal = filter;
                    searchOnKey(ul,filter);
                }, 700);
            }
        }
        var loaded;
        function addToList(label,attrs,data){
            var el = $('<li></li>',attrs);
            var s = ['<span class="glyphicon glyphicon-circle-arrow-left"></span>'];
            if (data.type){
                s.push('<span class="badge pull-right" >');
                s.push(data.type);
                s.push('</span>');
            }
            s.push('<span class=list-group-item-text >');
            s.push(label);
            s.push('</span>');
            el.data(data).html(s.join(' '));
            el.addClass('list-group-item');
            el.css('cursor','pointer');
            ul.append(el);
            sortingNeeded = true;
        }
        var inProg =0, sortingNeeded = false;
        function showLoading(){
            inProg++;
            elLoading.show();
        }
        function hideLoading(){
            inProg--;
            if (inProg<=0){
                elLoading.hide();
                elLoading.find('progress-bar').removeClass('progress-bar-danger');
                inProg =0;
            }
        }
        function errorLoading(){
            elLoading.find('progress-bar').addClass('progress-bar-danger');
        }
        function afterDisplay(){
            if (inProg ==0){
                if (sortingNeeded){
                    sortList(ul);
                    sortingNeeded = false;
                }
            }
        }
        function parseAjaxResults(r, o){
            var a =$.extend({},o);
            if ((typeof a['class'] == 'undefined') &&  self.getOption('item-class')){
                a['class'] = self.getOption('item-class');
            }
            if (r.items){
                for (var i=0, l= r.items.length; i < l; i++){
                    var s = r.items[i];
                    addToList(s.label, a,s);
                }
            }
            hideLoading();
            afterDisplay();
        }
        function load(){
            console.log('loaded', loaded, self.getOption('title'),'is s',is_setup);
            if (loaded) return;
            showLoading();
            loaded = true;
            var a = {'class':'text-green'};
            if (self.getOption('item-class')){
                a['class'] = self.getOption('item-class');
            }
            var uri = self.getOption('source');
            
            if (self.getOption('demo')){
                for (var i=1;i<5;i++){
                    var d = {'type':'item','id':i, 'title':'item '+ i};
                    addToList(d.label, a,d);
                }
                hideLoading();
                afterDisplay();
            }else if (uri){
                SAP.get(uri ,function(r){
                    console.log('loaded', loaded);
                    parseAjaxResults(r,a);
                }).fail(function(){
                    console.log('Failed');
                    loaded = false;
                    errorLoading();
                });
            } else {
                hideLoading();
            }
        }
        var options = {};
        self.getOption = function(){
            if (arguments.length===0) return options;
            var o = arguments[0];
            if (typeof o === 'undefined') return options;
            if (o === null) return options;
            
            if (typeof o === 'string'){
                if (o in options)
                    return options[o];
                else if (arguments.length===2)
                    return arguments[1];
                else
                    return null;
            }
            if (typeof o === 'object'){
                var e, r = {};
                if ($.isArray(o)){
                    for (i in o ){
                        e = o[i];
                        if (typeof e === 'string' && (e in options)){
                            r[e] = options[e];
                        } else {
                            r[e] = null;
                        }
                    }
                    return r;
                }
                for (e in o ){
                    if (typeof e === 'string' && (e in options)){
                        r[e] = options[e];
                    } else {
                        r[e] = o[e];
                    }
                }
            }
        };
        self.setOption = function(){
            if (arguments.length===0) return;
            var o = arguments[0];
            if (typeof o === 'undefined') return;
            if (o === null) return;
            if (typeof o === 'object'){
                $.extend(options,o);
            }
            if (typeof o === 'string' && arguments.length===2){
                options[o] = arguments[1];
            }  
        };
        self.show = function(opt){
            updateOption(opt);
            setUp();
            update();
            dlg.modal();
        }
        self.hide = function(){
            console.log(dlg);
            dlg.modal('hide');
        }
        self.destroy = function(){
            dlg.modal('hide');
            dlg.removeData('modal');
            dlg.remove();
        }
        updateOption(opt);
        return self;
    }
    
    SAP.contextSelection = function(options){
        return new GeneralContextSelection(options);
    }
})();
