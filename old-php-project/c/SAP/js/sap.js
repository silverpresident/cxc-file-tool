/*
this is the generic JS library for SMS
operating name = SAP

//SCALAR VAR
SAP.base
SAP.url
SAP.user_id

//FUNCTION
SAP.setTitle(STRING)
SAP.uri(STRING)
SAP.href(STRING)
SAP.isHttps()
SAP.redirect()
SAP.getUserId()
SAP.scrollTo(SELECTOR)
SAP.ready(function)
SAP.whenFramed(function)
SAP.togglePasswordView(DOM)
SAP.isDevSite();
SAP.isDemoSite();

//OBJECTS
SAP.sessionStorage  {.get .getAll .set .remove .clear .addEvent}
SAP.localStorage

$c() -cache version of jQuery
*/

if (!Array.prototype.hasOwnProperty('contains')){
Object.defineProperty(Array.prototype, 'contains', {
  enumerable: false,
  configurable: false,
  writable: false,
  value: function(obj){
        var i = this.length;
        while (i--){
            if (this[i] === obj){
                return true;
            }
        }
        return false;
    }
});
}
if (!Date.now){
    Date.now = function(){ return new Date().getTime(); };
}

if (!window.JSON){
  window.JSON = {
    parse: function(sJSON){ return eval('(' + sJSON + ')'); },
    stringify: (function (){
      var toString = Object.prototype.toString;
      var isArray = Array.isArray || function (a){ return toString.call(a) === '[object Array]'; };
      var escMap = {'"': '\\"', '\\': '\\\\', '\b': '\\b', '\f': '\\f', '\n': '\\n', '\r': '\\r', '\t': '\\t'};
      var escFunc = function (m){ return escMap[m] || '\\u' + (m.charCodeAt(0) + 0x10000).toString(16).substr(1); };
      var escRE = /[\\"\u0000-\u001F\u2028\u2029]/g;
      return function stringify(value){
        if (value == null){
          return 'null';
        } else if (typeof value === 'number'){
          return Number.isFinite(value) ? value.toString() : 'null';
        } else if (typeof value === 'boolean'){
          return value.toString();
        } else if (typeof value === 'object'){
          if (typeof value.toJSON === 'function'){
            return stringify(value.toJSON());
          } else if (isArray(value)){
            var res = '[';
            for (var i = 0; i < value.length; i++)
              res += (i ? ', ' : '') + stringify(value[i]);
            return res + ']';
          } else if (toString.call(value) === '[object Object]'){
            var tmp = [];
            for (var k in value){
              if (value.hasOwnProperty(k))
                tmp.push(stringify(k) + ': ' + stringify(value[k]));
            }
            return '{' + tmp.join(', ') + '}';
          }
        }
        return '"' + value.toString().replace(escRE, escFunc) + '"';
      };
    })()
  };
}

// Extend the default Number object with a formatMoney() method:
// usage: someVar.formatMoney(decimalPlaces, symbol, thousandsSeparator, decimalSeparator)
// defaults: (2, "$", ",", ".")
if (!Number.prototype.hasOwnProperty('formatMoney')){
Object.defineProperty(Number.prototype, 'formatMoney', {
  enumerable: false,
  configurable: false,
  writable: false,
  value: function(places, symbol, thousand, decimal) {
    	places = !isNaN(places = Math.abs(places)) ? places : 2;
    	symbol = symbol !== undefined ? symbol : "$";
    	thousand = thousand || ",";
    	decimal = decimal || ".";
    	var number = this, 
    	    negative = number < 0 ? "-" : "",
    	    i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
    	    j = (j = i.length) > 3 ? j % 3 : 0;
    	return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
    }
});
}

window.SAP = (function (){
    "use strict";
    
    function buildUrl(base,args){
        var params = {};
        var segments = [];
        var temp;
        for (var i = 0; i < args.length; i++){
            var e = args[i];
            if (typeof args[i] === 'object'){
                if($.isArray(args[i])){
                    temp = args[i].join('/');
                    if (temp!=='') segments.push(temp);
                } else {
                    $.extend(params,args[i]);
                }
            } else if (args[i]!=='') {
                segments.push(args[i]);
            }
        }
        var queryString = $.param(params);
        if (segments.length){
            base += '/' + segments.join('/');
        }
        if (queryString.length){
            base += '?' + queryString;
        }
        return base;
    }
    
    var SAP = {
        uri: function(){
            if (this.url === undefined){
                var p = '/ajax';
            } else {
                var p = this.url;
                if (arguments.length && (p.substr(-1,1) == '/')){
                    p = p.substr(0,p.length-1);
                }
            }
            return buildUrl(p,arguments);
        },
        href: function(){
           if (this.base === undefined){
                var p = '';
            } else {
                var p = this.base;
                if (arguments.length && (p.substr(-1,1) == '/')){
                    p = p.substr(0,p.length-1);
                }
            }
            return buildUrl(p,arguments);
        },
        isHttps: function(){
            return location.protocol == 'https:';
        },
        simpleEncrypt: function (message, cypherKey) {
            //https://www.henryalgus.com/creating-basic-javascript-encryption-between-frontend-and-backend/
    		var enc = "";
    		var str = "";
    		// make sure that input is string
    		str = message.toString();
    		for (var i = 0; i < s.length; i++) {
    			// create block
    			var a = s.charCodeAt(i);
    			// bitwise XOR
    			var b = a ^ cypherKey;
    			enc = enc + String.fromCharCode(b);
    		}
    		return enc;
    	},
        redirect: function(){
            if (this.base === undefined){
                var p = '';
            } else {
                var p = this.base;
                if (arguments.length && (p.substr(-1,1) == '/')){
                    p = p.substr(0,p.length-1);
                }
            }
            location.href = buildUrl(p,arguments);
        },
        setTitle: function(v){
            if (arguments.length == 0){
                v='';
            }
            if (this.title === undefined){
                var t = '';
            } else {
                var t = this.title;
            }
            if (v.length == 0){
                document.title = t;
            }else if (t.length == 0){
                document.title = v;
            } else {
                document.title = v + ' | ' + t;
            }
        },
        isDevSite: function(){
            if (this.is_dev_site === undefined){
                return false;
            }
            return this.is_dev_site;
        },
        isDemoSite: function(){
            if (this.is_demo_site === undefined){
                return false;
            }
            return this.is_demo_site;
        },
        getUserId: function(){
            if (this.user_id === undefined){
                return 0;
            }
            return this.user_id;
        },
        setUserId: function(uid){
            if (uid === undefined){
                uid = 0;
            }
            if (uid > 0){
                $('body').removeClass('no-user');
            } else {
                $('body').addClass('no-user');
            }
            this.user_id = uid;
        },
        getInitialJsonData: function(defaultdata){
            var initialData;
            try{
                var x, id = 'initial_data';
                if (typeof defaultdata === 'string'){
                    id = defaultdata;
                    if (arguments.length == 2){
                        defaultdata = arguments[0];
                    }
                }
                x = JSON.parse(document.getElementById(id).textContent);
                if(x){
                    initialData = x;
                }
            } catch (e){
                initialData = {};
            }
            if (typeof defaultdata === 'object'){
                initialData = $.extend(defaultdata,initialData);
            }
            return initialData;
        },
        scrollTo: function(selector)
        {
            var el =$(selector);
            if (el.length){
                var oset = el.offset().top;
                oset -= 100;
                $('html,body').animate({ scrollTop: '+=' + oset + 'px' }, 'fast');
            }
        },
        editor: function(elid){
           if ( (typeof elid == 'undefined') || elid==''){
                elid = 'textarea';
           }
           if (typeof tinymce != 'undefined'){
                if (elid != 'textarea') elid = '#'+elid;
                tinymce.init({selector:elid,browser_spellcheck : true,
                 plugins: [
                "advlist autolink lists link image charmap print preview hr anchor pagebreak",
                "searchreplace wordcount visualblocks visualchars code fullscreen",
                "insertdatetime media nonbreaking save table contextmenu directionality",
                "emoticons template paste textcolor "
                ],
                toolbar1: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
                toolbar2: "print preview media | forecolor backcolor emoticons",
                image_advtab: true});
           }else if (typeof CKEDITOR != 'undefined'){
                if (elid == 'textarea'){
                    CKEDITOR.replaceAll();
                }
                else{
                    CKEDITOR.replace(elid);
                }
           }
        },
        togglePasswordView : function(selector,totext){
            var el =$(selector);
            if (arguments.length == 1){
                totext = el.attr('type') == 'password';
            }
            if (totext){
                el.attr('type', 'text');
            } else {
                el.attr('type', 'password');
            }
        },
        alerts : function(o){
            for (var e in o){
                if (o.hasOwnProperty(e)) SAP.alert(o[e]);
            }
        },
        messages: function(o){
            for (var e in o){
                if (o.hasOwnProperty(e)) SAP.message(o[e]);
            }
        },
        prompts: function(o){
            for (var e in o){
                if (o.hasOwnProperty(e)) SAP.prompt(o[e]);
            }
        },
        confirms: function(o){
            for (var e in o){
                if (o.hasOwnProperty(e)) SAP.confirm(o[e]);
            }
        },
        message: function(msg, seconds)
        {//mesage must be SHORT and unobtrusive with dismssal option
            //console.log(msg);
            //window.status = msg;
            if (arguments.length===0) return;
            if (arguments.length===1){
                if ( msg=='' || msg===null) return;
                if ((typeof msg =='object') && $.isEmptyObject(msg)) return;
            }
            var o = {'type':'default','delay':7000};
            if (typeof seconds =='object'){
                 $.extend(o , seconds);
            }
            if (typeof msg =='object'){ 
                $.extend(o , msg);
                msg = '';
                if ('text' in o) msg = o.text;
                if ('message' in o) msg = o.message;
            }
            if (o.type =='script'){
                var s = $('<script>').append(o.script);
                $('body').append(s);
                return;
            }
            
            if (typeof seconds =='number'){
                o.delay = seconds * 1000;
            }else if (typeof seconds =='string'){
                if (seconds == 'error') seconds = 'danger';
                if (seconds =='info' || seconds =='success' || seconds=='danger'|| seconds=='warning'){
                    o.type = seconds;
                }
            }   
            if (o.type == 'error'){
                o.type = 'danger';
            }
            o.delay = o.delay || o.seconds*1000;
            
            if (typeof $.snackbar !='undefined'){
                o.content = msg;
                if (o.type =='default'){
                    o.style ='snackbar-default toast';
                } else {
                    o.style ='toast text-' + o.type;
                }
                if (o.delay){
                    o.timeout = o.delay;
                }
                $.snackbar(o);
            }else if (typeof $.bootstrapGrowl !='undefined'){
                if (typeof o.offset =='undefined'){
                    if (window.frameElement){
                        o.offset = {from: 'bottom', amount: 20};
                    } else {
                        o.offset = {from: 'top', amount: 20};
                    }
                }
                if (o.type =='default'){
                    o.type ='info';
                }
                $.bootstrapGrowl(msg,o);
                
            }else if (typeof $.notify !='undefined'){
                if (typeof o.placement =='undefined'){
                    if (window.frameElement){
                        o.placement = {from: 'bottom'}
                    } else {
                        o.placement = {from: 'top'};;
                        o.newest_on_top= false;
                    }
                }
                if (typeof o.offset =='undefined'){
                    o.offset =  20;
                }
                
                $.notify(msg,o);
                
            }else if (typeof $.growl !='undefined'){
                if (typeof o.position =='undefined'){
                    if (window.frameElement){
                        o.position = {from: 'bottom'};
                    } else {
                        o.position = {from: 'top'};
                    }
                }
                if (typeof o.offset =='undefined'){
                    o.offset =  20;
                }
                if (o.type =='default'){
                    o.type ='info';
                }
                $.growl(msg,o);
            } else {
                //TODO default message method
            }
        },
        alert: function(msg, seconds){
           var o = {};
           if (typeof seconds == 'object'){
                $.extend(o , seconds);
           }
           if (typeof msg == 'object'){ 
                $.extend(o , msg);
                msg = '';
                if ('text' in o) msg = o.text;
                if ('message' in o) msg = o.message;
            }
            
            if (typeof bootbox.alert !='undefined'){
                
                
                if (typeof seconds == 'number'){
                    o.delay = seconds * 1000;
                }else if (typeof seconds =='string'){
                    if (seconds == 'error'){
                        o.type = 'danger';
                    }else if (seconds =='info' || seconds =='success' || seconds=='danger' || seconds=='warning'){
                        o.type = seconds;
                    }
                }else if (typeof seconds =='function'){
                    o.callback = seconds;
                }
                if (typeof o.buttons == 'undefined'){
                    var bo={label:'OK'};
                    if (o.type && (o.type =='danger' || o.type =='success' || o.type=='info' || o.type=='warning' || o.type=='inverse' || o.type=='link')){
                        bo['class'] = 'btn-'+o.type;
                    }
                    if (o.callback && $.isFunction(o.callback)){
                        bo.callback = o.callback;
                    }
                    o.buttons = { success: bo};
                }
                o.delay = o.delay || o.seconds*1000;
                o.message = msg;
                
                
                var bb = bootbox.dialog(o);
                if (o.type && (o.type =='info' || o.type =='success' || o.type=='danger'|| o.type=='warning')){
                    bb.find('.modal-body').addClass('alert alert-'+o.type);
                } else {
                    bb.find('.modal-body').removeClass('alert alert-info alert-success alert-danger alert-warning');
                }
                if (o.delay > 0){
                    if (o.delay < 5000) o.delay = 5000;
                    setTimeout(function(){ bb.modal('hide'); },o.delay);
                }
                    
            } else {
                alert(msg);
                console.log('Loading BOOTBOX script');
                $('head').append('<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>');
            }
        },
        /*
        Object.type
        Object.delay = 0 or integer > 5000; number seconds
        Object.post = STRING page to send data to; OBJECT params to send, use .page to set the page to post to;
        Object.callback
        Object.value = default value
        Object.cancel = label for cancel button; set cancel to null or '' to hide cancel button
        Object.ok = label for ok button
        
        */
        
        prompt: function(param){
            var o = {'type':'default',cancel:'Cancel',ok:'OK','value':''};
            var bbo = {'value':''};
            
            if (typeof param =='object'){
                $.extend(o , param);
            } else {
                bbo.title =param;
            };
            var a = ['title','size','value','show','inputType','inputOptions','className','buttons','placeholder','pattern','maxlength'];
            for (var i =0, l= a.length; i< l;i++){
                var e = a[i];
                if (e in o){
                    bbo[e] = o[e]; delete o[e];
                }
            }
            
            //decide on m
            if (typeof bbo.title == 'undefined'){
                if ('title' in o){
                    bbo.title = o.title; delete o.title;
                }else if ('text' in o){
                    bbo.title = o.text; delete o.text;
                }else if ('message' in o){
                    bbo.title = o.message; delete o.message;
                }else if ('msg' in o){
                    bbo.title = o.msg; delete o.msg;
                } else {
                    bbo.title = '?';
                }
            }
            if (typeof bbo.inputType == 'undefined'){
                if ('inputtype' in o){
                    bbo.inputType = o.inputtype; delete o.inputtype;
                }
                if ('inputoptions' in o){
                    bbo.inputOptions = o.inputoptions; delete o.inputoptions;
                }
            }
            if (bbo.inputType == 'tel'){
                bbo.inputType = 'text';
                o.inputType = 'tel';
            }
            bbo.callback =  function(result){
              
              if (o.post){
                if (result!==null){
                    var p ='prompt',v={};
                    if (typeof o.post =='object'){
                        $.extend(v,o.post);
                        if (v.page) p = page;
                    } else {
                        p = o.post;
                    }
                    v.result =result;
                    SAP.post(p,v);
                }
              }
              if (o.callback && $.isFunction(o.callback)){
                    o.callback.call(o,result);
              }
            }
            var bb;
            function showBox(){
                
                if (o.cancel==='' || o.cancel===null){
                    bb.find('.modal-footer a[data-handler=0]').hide();
                }
                
                if (o.type && (o.type =='info' || o.type =='success' || o.type=='danger'|| o.type=='warning')){
                    bb.find('.modal-body').addClass('bg-'+o.type);
                } else {
                    bb.find('.modal-body').removeClass('bg-info bg-success bg-danger bg-warning');
                } 
                var input = bb.find('input');
                if (o.inputType == 'tel'){
                    input.attr('type','tel');
                }
                if (o.max){
                    input.attr('max',o.max);
                }
                if (o.min){
                    input.attr('min',o.min);
                }
                if (o.minlength){
                    input.attr('minlength',o.minlength);
                }
                if (o.required){
                    input.attr('required',o.required);
                }
                
                
                if (o.delay > 0){
                    if (o.delay < 5000) o.delay = 5000;
                    setTimeout(function(){ bb.modal('hide'); },o.delay);
                }
            }
            
            if (bbo.inputType =='select' && !bbo.inputOptions){
                /*if (o.inputOptions){
                    
                }*/
                //var el = $('<select>').addClass('bootbox-input bootbox-input-text form-control');
                SAP.get(o.source,function(data){
                    if ($.isArray(data)){
                        bbo.inputOptions = data;
                    } else {
                        var io = [];
                        var len = data.length;
                        for (var i = 0; i< len; i++){
                            var o = {value:data[i].id, 'text':data[i].title};
                            if (data[i].group){
                                o.group = data[i].group;
                            }
                            io.push(o);
                        }
                        
                        bbo.inputOptions = io;
                    }
                    bb = bootbox.prompt(bbo);
                    //bb.find('input.bootbox-input-text').replaceWith(el);
                    showBox();
                });
                
            } else {
                bb = bootbox.prompt(bbo);
                showBox();
            }
        },
        /*
        Object.type
        Object.delay = 0 or integer > 5000; number seconds
        Object.post = STRING page to send data to; OBJECT params to send, use .page to set the page to post to;
        Object.callback
        Object.cancel = label for cancel button; set cancel to null or '' to hide cancel button
        Object.ok = label for ok button
        
        */
        confirm: function(param){
            var o = {'type':'default',cancel:'Cancel',ok:'OK'};
            var bbo = {'message':'Are you sure?'};
            var icon = '';
            
            if (typeof param =='object'){
                $.extend(o , param);
            } else {
                bbo.message = param;
            }
            var a = ['title','size','message','show','className','buttons'];
            for (var i =0, l= a.length; i< l;i++){
                var e = a[i];
                if (e in o){
                    bbo[e] = o[e]; delete o[e];
                }
            }
            
            if (typeof bbo.message == 'undefined'){
                if ('text' in o){
                    bbo.message = o.text; delete o.text;
                } else {
                    bbo.message = 'Are you sure?';
                }
            }
            if ('icon' in o){
                icon = o.icon; delete o.icon;
            } else{
                if (o.type=='danger'){
                    icon = '<i class="fa fa-exclamation-triangle text-red" style="font-size: 42px;"></i>';
                } else if (o.type=='warning'){
                    icon = '<i class="fa fa-exclamation-circle text-yellow" style="font-size: 42px;"></i>'
                }
            }
            if (arguments.length==2 && $.isFunction(arguments[1])){
                //only called if true
                o.onconfirm = arguments[1];
            }
            bbo.message = icon + ' &nbsp; ' +bbo.message;
            
            bbo.callback = function(result){
                if (o.post){
                    var p ='confirm',v={};
                    if (typeof o.post =='object'){
                        $.extend(v,o.post);
                        if (v.page) p = page;
                    } else {
                        p = o.post;
                    }
                    v.result =result?1:0;
                    SAP.post(p,v);
                }
                
                if (result && o.onconfirm && $.isFunction(o.onconfirm)){
                    o.onconfirm.call(o,result);
                }
                if (o.onrespond && $.isFunction(o.onrespond)){
                    o.onrespond.call(o,result);
                }else if (o.callback && $.isFunction(o.callback)){
                    o.callback.call(o,result);
                }
            }
            if (bootbox.confirm){
                var bb = bootbox.confirm(bbo);
                bb.find('.modal-body').removeClass('alert bg-info bg-success bg-danger bg-warning');
                bb.find('.modal-footer a').removeClass('btn-danger btn-success btn-info btn-warning btn-inverse btn-link');
                    
                if (o.type){
                    if (o.type =='info' || o.type =='success' || o.type=='danger' || o.type=='warning'){
                        bb.find('.modal-header').addClass('bg-'+o.type);
                        bb.find('.modal-body').addClass('text-'+o.type);
                    }else if (o.type =='primary'){
                        bb.find('.modal-body').addClass('text-'+o.type);
                    }
                    if (o.type =='danger' || o.type =='success' || o.type=='info' || o.type=='warning' || o.type=='inverse' || o.type=='link'){
                        bb.find('.modal-body a[data-handler=1]').addClass('btn-'+o.type);
                    }
                }
                
                if (o.delay > 0){
                    if (o.delay < 5000) o.delay = 5000;
                    setTimeout(function(){ bb.modal('hide'); },o.delay);
                }
            } else {
                var r = confirm(bbo.message);
                bbo.callback(r);
            }
        }
    };
    
    SAP.extend = jQuery.fn.extend;
    var isReady = false;
    var _readyList = [];
    SAP.ready = function(){
        if ($.isFunction(arguments[0])){
            if (isReady){
                arguments[0]();
            } else {
                _readyList.push(arguments[0]);
            }
        }else
            console.log('Expected function');
    }
    SAP.whenFramed = function(){
        if ($.isFunction(arguments[0])){
            if (window.frameElement){
                if (isReady){
                    arguments[0]();
                } else {
                    _readyList.push(arguments[0]);
                }
            }
        }else
            console.log('Expected function');
    }
    $(document).ready(function(){
        isReady = true;
        for (var i in _readyList ){
            var e = _readyList[i];
            e();
        }
        _readyList.length=0;
    });
    
    var _safeLeave = false;
    SAP.enableSafeLeaveCheck = function(){
        var state = true;
        if (arguments.length){
            state = arguments[0];
        }
        if (state){
            if (!_safeLeave){
                doSavePoint();
            }
            _safeLeave = true;
        } else {
            _safeLeave = false;
        }
    };
    SAP.savePoint = doSavePoint;
    function doSavePoint()
    {
        var forms =false;
        $('form').each(function(){
            forms =true;
            $(this).data('initialForm', $(this).serialize());
        }).submit(function(e){
           
            if ($(this).data('initialForm') != $(this).serialize())
            {
                $(window).unbind('beforeunload', checkSafeLeaveFunction);
            }
        });
        if (forms)
        {
            $(window).bind('beforeunload', checkSafeLeaveFunction);
        }
    } 
    
    function checkSafeLeaveFunction()
    {
        if (_safeLeave)
        {
            var changed = false;
            $('form').each(function(){
                if ($(this).data('initialForm') != $(this).serialize()){
                  changed = true;
                  $(this).addClass('form-changed');
                } else {
                  $(this).removeClass('form-changed');
                }
            });
            if (changed){
                return 'You have entered information on this page which you have not yet saved.  Are you sure you want to leave this page?';
            }
        }
    }
    
    function oStorage(StorageType, prefix){
        var keyPrefix = '', keyLength=0;
        if (typeof prefix != 'undefined'){
            keyPrefix = prefix + '-';
            keyLength = keyPrefix.length; 
        }
        return {
            getAll:function(){
                var o = {};
                if (StorageType && typeof StorageType != 'undefined'){
                    var key, value;
                    for ( var i = 0, len = StorageType.length; i < len; ++i ){
                        key = StorageType.key(i);
                        if (keyLength>0 && key.substr(0,keyLength) != keyPrefix){
                            continue;
                        }
                        value = StorageType.getItem(key);
                        if (typeof value === 'undefined'){
                            o[key] = null;
                        } else {
                            o[key] = JSON.parse(value);
                        }
                    }
                }
                return o;
            },
            get:function(){
                if (arguments.length===0){
                    return this.getAll();
                }
                var key = keyPrefix + arguments[0];
                if (StorageType){
                    var value = StorageType.getItem(key);
                    if (typeof value === 'undefined') return undefined; 
                    return JSON.parse(value);
                }
                return null;
            },
            remove:function(key){
                key = keyPrefix + key;
                StorageType.removeItem(key);
            },
            clear:function(){
                if (keyLength>0){
                    for ( var i = 0, len = StorageType.length; i < len; ++i ){
                        key = StorageType.key(i);
                        if (key.substr(0,keyLength) == keyPrefix){
                            StorageType.removeItem(key);
                        }
                    }
                } else {
                    StorageType.clear();
                }
            },
            set:function(key,value){
                key = keyPrefix + key;
                if (arguments.length==1){
                    return StorageType.removeItem(key);
                }
                
                if (StorageType){
                    return StorageType.setItem(key,JSON.stringify(value));
                }
            },
            addEvent:function(callback){
                return window.addEventListener('storage', callback, false);
            },
        }
    }
    
    Object.defineProperty(SAP,'localStorage', {
      enumerable: true,
      configurable: false,
      writable: false,
      value: new oStorage(localStorage )
    });
    Object.defineProperty(SAP,'sessionStorage', {
      enumerable: true,
      configurable: false,
      writable: false,
      value: new oStorage(sessionStorage )
    });
    Object.defineProperty(SAP,'version', {
      enumerable: true,
      configurable: false,
      writable: false,
      value: '1.2'
    });
    SAP.getStorage = function(prefix){
        return new oStorage(localStorage,prefix );
    }
    
    var options = {};
    SAP.getOption = function(){
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
    SAP.setOption = function(){
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


    var alertMethod;
    if (typeof SAP.message != 'undefined'){
        alertMethod = SAP.message;
    } else {
        alertMethod = console.log;
    }
    
    function ajaxFailParser(e,et,m){
        if (et=='error'){
            alertMethod(m);
        }else if (et=='timeout'){
            alertMethod('Timeout to be handled');
        }else if (et=='parsererror'){
            alertMethod('parsererror to be handled');
        }else if (et=='abort'){
            alertMethod('abort to be handled');
        } else {
            alertMethod('Unknown error to be handled');
        }
    };
    function ajaxSuccessParser(data){
        if (typeof data !== 'object') return;
        
        if ('options' in data) SAP.setOption(data.options);
        if ('user_id' in data) SAP.user_id = data.user_id;
        
        if ('prompt' in data) SAP.prompt(data.prompt);
        if ('confirm' in data) SAP.confirm(data.confirm);
        if ('message' in data) SAP.message(data.message);
        if ('alert' in data) SAP.alert(data.alert);
        if ('error' in data) SAP.message(data.error,'error');
        
        if ('prompts' in data) SAP.prompts(data.prompts);
        if ('confirms' in data) SAP.confirms(data.confirms);
        if ('messages' in data){ SAP.messages(data.messages); SAP.sessionStorage.set('messages',data.messages);};
        if ('alerts' in data){ SAP.alerts(data.alerts); SAP.sessionStorage.set('alerts',data.alerts);};
        if ('notification' in data){
             SAP.notification(data.notification);
             SAP.sessionStorage.set('notification',data.notification);
        }
    }
    jQuery.each( [ "get", "post", "head","put","delete" ], function( i, method ){
        SAP[ method ] = function( urn, data, callback, type ){
            if (jQuery.isFunction(data)){
                type = type || callback;
                callback = data;
                data = undefined;
            }
            if (typeof urn == 'undefined') urn ='';
            var aData = {
                url: SAP.uri(urn),
                type: method,
                dataType: type,
                data: data
            };
            if (FormData && (data instanceof FormData)){
                aData.processData = false;
                aData.contentType = false;
            }
            if (method == "head"){
                aData.complete = function (XMLHttpRequest, textStatus){
                        try {
                            var headers = XMLHttpRequest.getAllResponseHeaders().split("\n");
                            var extraHeaders = ['status', 'multipart', 'withCredentials'];
                            var iEHl = extraHeaders.length;
                            for (var iEH = 0; iEH < iEHl; iEH++){
                              var sHeader = extraHeaders[iEH];
                              try {
                                /* STH: 2010-09-17, Extended so that status is also returned */
                                headers.push(sHeader + ': ' + XMLHttpRequest[sHeader]);
                              } catch (e){
                                // Okay - just ignore, header not here
                              }
                            };
                            
                            var new_headers = {};
                            var l = headers.length;
                            for (var key = 0; key < l; key++){
                              if (headers[key].length > 1){
                                header = headers[key].split(": ");
                                new_headers[header[0]] = header[1];
                              }
                            }
                            if (jQuery.isFunction(callback)){
                              callback(new_headers);
                            }
                        } catch (e){
                            alertMethod('Error in SAP.head: ' + e.Message + '\nAre you using URL outside your current domain?');
                        }
                }; 
            } else {
                aData.success= callback;
            }
            
            
            return jQuery.ajax(aData)
                            .done(ajaxSuccessParser)
                            .fail(ajaxFailParser);    
                
        };
    });
    
    SAP.getScript = function( url, options ) {
        //This function is primarily mean to load libraries NOT internal scripts
        //It is therefore CACHED by default different from jQuery.getScript which 
        //does not cahce by default
        // Allow user to set any option except for dataType, and url
        options = jQuery.extend( options || {}, {
            dataType: "script",
            url: url
        });
        if (typeof options.cache == 'undefined'){
            options.cache = true;
        }
        // Use $.ajax() since it is more flexible than $.getScript
        // Return the jqXHR object so we can chain callbacks
        return jQuery.ajax( options );
    };
    
    var nButton, notification_is_setup, nCount, nEnvelop, nDock, nNoMessage;
    var notiClick,setBody,setCount,addItem;
    var nHolder = $('#menubar_notifications');
    
    if (nHolder.length > 0){
        notification_is_setup =true;
        nButton= $('<a class="notification-btn"></a>');
        nCount = $('<span class="notification-count"></span>'); 
        nEnvelop = $('<span class="fa fa-envelope"></span>');
        nDock = $('<div class="notification-dock"></div>');
        nNoMessage = $('<div class="notification-no-mesage"><em>there are no notifications.</em></div>');
        nButton.append(nCount,nEnvelop,nDock);
        nDock.css('display','block');
        nButton.on('click',notiClick);
        notiClick = function (e){
            nDock.toggle();
            e.preventDefault();
            return false;
        }
        setBody = function (v){
            if (v == ''){
                nDock.html(nNoMessage);
                nNoMessage.show();   
            } else {
                nDock.html(v);
            }
        }
        setCount = function (v){
            var i = parseInt(v);
            if (i > 0){
                nCount.text(i);
                if (i > 3){
                    nCount.addClass('text-danger');
                } else {
                    nCount.removeClass('text-danger');
                }
                nButton.show();
            } else {
                nCount.text('');
                nButton.hide();
            }
        }
        addItem = function (v){
            var ni = $('<div class="notification-item"></div>');
            var cn;
            if (v.href){
                cn = $('<a></a>');
                cn.attr('href', v.href);
                ni.append(cn);
            } else {
                cn = ni;
            }
            var add;
            if (v.subject){
                var s = $('<div class="notification-subject"></div>');
                s.append(v.subject);
                cn.append(s);
                add=true;
            }
            if (v.message){
                var s = $('<div class="notification-message"></div>');
                s.append(v.message);
                cn.append(s);
                add=true;
            }
            if (add){
                nNoMessage.hide();
                nDock.append(ni);
            }
        }
        nDock.hide();
        setBody('');
        setCount(0);
        nHolder.empty().append(nButton);
    }
    
    SAP.notification = function(o){
        if (!notification_is_setup) return;
        
        if (('clear' in o) && o.clear){
            setCount(0);
            setBody('');
        }
        
        if ('items' in o){
            for (var i =0, l = o.items.length; i < l; i++ ){
                addItem(o.items[i]);
            }
            if (!o.count || (o.count == 0)){
                o.count = i;
            }
        }
        if ('count' in o){
            setCount(o.count);
        }
        if ('html' in o){
            setBody(o.html);
        }
    }
    
    return SAP;
}());

window.$c = (function(){
    var cache = {};
    
    return function(query){
        console.log('a',query);
       
      if (!cache[query]){
        cache[query] = jQuery(query);
      }
      return cache[query];
    };
})();

$(function(){
    var aa = document.getElementsByTagName('a')
    $.each(aa, function(){
        if (location.hostname != this.hostname){
            this.target='_blank'; 
        }
    });
    
    var menu = $('#site-navbar');
    if (window.frameElement){
        menu.hide();
    } else {
        var main = $('.container-fluid.outer');
        if (menu.hasClass('navbar-fixed-top')){
            main.css('margin-top',menu.height()+8);
        }    
    }
    
    if (SAP.getUserId() == 0){
        $('body').addClass('no-user');
    }
});
$(function(){
    function sc(scode){
        var code = scode;
        this.OK = 200;
        this.CREATED = 201;
        this.ACCEPTED = 202;
        this.MOVED_PERMANENTLY = 301;
        this.FOUND = 302;
        this.SEE_OTHER = 303;
        this.NOT_MODIFIED = 304;
        this.TEMPORARY_REDIRECT = 307;
        this.PERMANENT_REDIRECT = 308;
        this.BAD_REQUEST = 400;
        this.UNAUTHORIZED = 401;
        this.FORBIDDEN = 403;
        this.NOT_FOUND = 404;
        this.METHOD_NOT_ALLOWED=405;
        this.REQUEST_TIMEOUT = 408;
        this.INTERNAL_SERVER_ERROR = 500;
        this.NOT_IMPLEMENTED = 501;
        this.BAD_GATEWAY = 502;
        this.SERVICE_UNAVAILABLE = 503;
        this.get_code = function (){ return code;}
        this.getCode = function (){ return code;}
        this.toString = function (){ return code;}
        this.is_informational = function (){ return code>=100 && code < 200;}
        this.is_success = function (){ return code>=200 && code < 300;}
        this.is_redirect = function (){ return code>=300 && code < 400;}
        this.is_client_error = function (){ return code>=400 && code < 500;}
        this.is_server_error = function (){ return code>=500 && code < 600;}
        this.is = function (){ 
            for (var i =0, l=arguments.length; i<l;i++ ){
                if (arguments[i] == code) return true;
            }
            return false;
        }
    }
    SAP.getStatusCode = function(code ){
        return new sc(code);
    } 
});