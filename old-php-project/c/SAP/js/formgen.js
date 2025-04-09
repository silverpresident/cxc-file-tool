/*
modalEditFormRender(options)
returned object{}
-setError(message)
-setWarning(message)
-setCustomValidity(element,message)

options = {};
options.callback = function(event){}
options.save = 'label for button'
options.cancel = 'label for button' or true
options.title
options.className
options.input_column_size
options.size = 'small|medium|large'
options.includeUnchecked = true;
options.fields = [field]
options[form_attribute]
field.type
field.append
field.prepend
field.tip
field.options = [] // for type=select|radio|checkbox
field.optGroups = []// for type=select
field[input_attribute]
for field.type != [select|radio|checkbox|label|boolean]{ //REGULAR
    .label = control label and placeholder if placeholder is not defined
    .className = add to input element class
    .value = current value of element
    .name = name for input name and label for
    
    * = all input attributes and the following global attributes [id|style|title]
}
for field.type == [label]{ //used to display a paragraph
    .text
    .className
}
for field.type == [radio|checkbox]{
    .options = [option]  //OPTIONAL
    IF field.options IS NOT provided{
        ~~ all tiem specified for REGULAR input
        .checked
        .inline
        .title //provide the text displayed beside the checkbox LABEL is for the control label
        .className //added to the container
    }
    IF field.options IS  provided{
        .inline
        .name //used if option does not provide a name
    }
      
    
}
for field.type == [select]{
    ~ all tiem specified for REGULAR input
    .options = [option]
    .optGroups = [optgroup]
}
optgroup = {
    .label
    .disabled
    .options = [option]
}
option = {
    .label
    .value
    .disabled
    .selected //ONLY in type=select 
    .checked //ONLY in type=radio|checkbox
    IF option IS FOR type=radio|checkbox{
        .name
        .checked
        * = all input attributes and the following global attributes [id|style|title]
    }
}

*/(function(){
    var _dateInputSupported = null;
    var form_attributes = ['autocomplete','autofocus','target','name',
                        'action','enctype','method','novalidate',
                        'title','id','style'];
    
    var input_attributes_all = ['placeholder','pattern','maxlength','minlength','min','max','step',
                'size','autocomplete','autofocus','list','multiple','accept',
                'required','readonly','disabled','accept','rows','src','tabindex',
                'title','id','style'];
    var input_attributes_for_checkbox = [
                            'autofocus',
                            'required','readonly','disabled',
                            'title','id','style'];
    var input_attributes_for_hidden = ['name','size','id','readonly','disabled','value'];
                            
function isDateInputSupported(){
    if (_dateInputSupported === null){
        var test = document.createElement('input');
        test.type = 'date';
        _dateInputSupported = (test.type === 'date')
    }
    return _dateInputSupported;
}
function partialRenderOption(so){
    var opt = $('<option />');
    var type = (typeof so);
    if (type == 'number' || type == 'string'){
        opt.append(so);
        opt.attr('value',so);
        return opt;
    }
    if (so.label){
        opt.append(so.label);
    }
    if ('value' in so){
        opt.attr('value',so.value);
    }
    if (so.selected){
        opt.attr('selected',true);
    }
    if (so.disabled){
        opt.attr('disabled',true);
    }
    return opt;
}
function modalEditForm(options){
    var settings = {includeUnchecked:true};
    var form = $('<form></form>');
    
    var returnContainter = {};
    var el_error, fbox;
    
    $.extend(settings,options);
    form.attr('role','form');
    for (var i =0, l= form_attributes.length; i< l;i++){
        var e = form_attributes[i];
        if (e in settings){
            form.attr(e,settings[e]);
        }
    }
    var col1='',col2='';
    if (settings.horizontal){
        form.addClass('form-horizontal');
        var ic = 9;
        if (settings.input_column_size){
            ic = settings.input_column_size<12?settings.input_column_size:8;
        }
        var lc = 12-ic;
        col1 ='col-sm-' + lc;
        col2 ='col-sm-' + ic;
    }
    if (settings.className){
        form.addClass(settings.className);
    }
    form.on('submit',function(e){
        if (e.preventDefault){
            e.preventDefault();
        }
        return false;
    });
    if (typeof settings.fields =='undefined'){
        settings.fields = [];
    }
    
    el_error = $('<div class="alert"></div>').hide().appendTo(form);
    
    var localIncludeUnchecked ;
    for (var curi =0, optl= settings.fields.length; curi < optl; curi++){
        localIncludeUnchecked = settings.includeUnchecked; 
        var f = settings.fields[curi];
        if (f.type =='submit'){
            continue;
        }
        if (f.type =='divider'){
            $('<hr />').appendTo(form);
            continue;
        }
        if (f.type =='hidden'){
            var input = $('<input />').appendTo(form);
            input.attr('type',f.type);
            for (var i =0, l= input_attributes_for_hidden.length; i< l;i++){
                var e = input_attributes_for_hidden[i];
                if (e in f){
                    input.attr(e,f[e]);
                }
            }
            continue;
        }
        if (f.type =='label'){
            var formgroup = $('<div class="form-group"></div>').appendTo(form);
            var label = $('<p></p>').appendTo(formgroup);
            if (f.text){
                label.text(f.text);
            }else if (f.html){
                label.append(f.html);
            }else if (f.value){
                label.append(f.value);
            }
            if (f.className){
                label.addClass(f.className);
            }
            continue;
        }
        if (f.type =='radio-inline' || f.type=='checkbox-inline' || f.type =='boolean-inline'){
            f.type = f.type.substr(0,f.type.length - 7);
            f.inline = true;
        }
        if (f.type == 'boolean'){
            localIncludeUnchecked = true;
            f.type = 'checkbox';
        }
        if (f.type == 'date' && !isDateInputSupported()){
            if (!f.pattern){
                f.pattern = '[0-9]{4}-[0-9]{2}-[0-9]{2}';
            }
            if (!f.title){
                f.title = 'use format YYYY-MM-DD';
            }
        }
        
        
        var formgroup = $('<div class="form-group"></div>').appendTo(form);
        var label = $('<label></label>').appendTo(formgroup);
        var cg = $('<div></div>').appendTo(formgroup);
        label.addClass('control-label').addClass(col1);
        cg.addClass('controls').addClass(col2);
        if (f.name){
            label.attr('for',f.name);
        }
        
        if (f.type =='radio' || f.type=='checkbox'){
            
            if (f.label){
                label.append(f.label);
            }
        
            if (f.options){
                for (var cbi =0, cbl= f.options.length; cbi< cbl;cbi++){
                    var so = f.options[cbi];
                    if (f.inline){
                        var cbLabel = $('<label></label>').appendTo(cg);
                        cbLabel.addClass(f.type+'-inline');
                        var input = $('<input />').appendTo(cbLabel);
                    } else {
                        var cbContainer = $('<div></div>').appendTo(cg);
                        cbContainer.addClass(f.type);
                        var cbLabel = $('<label></label>').appendTo(cbContainer);
                        var input = $('<input />').appendTo(cbLabel);
                        if (so.disabled){
                            cbContainer.addClass('disabled');
                        }
                    }
                    input.attr('type',f.type);
                    if (so.name){
                        input.attr('name',so.name);
                        cbLabel.attr('for',so.name);
                    }else if (f.name){
                        input.attr('name',f.name);
                    }
                    
                    if (so.label){
                        cbLabel.append(so.label);
                    }else if (so.title){
                        cbLabel.append(so.title);
                    }
                    
                    if ('value' in so){
                        input.attr('value',so.value);
                    }
                    for (var i =0, l= input_attributes_for_checkbox.length; i< l;i++){
                        var e = input_attributes_for_checkbox[i];
                        if (e in f){
                            input.attr(e,f[e]);
                        }
                    }
                    if (so.checked){
                        input.prop('checked',true);
                    }else if (so.selected){
                        input.prop('checked',true);
                    }
                    
                    if ('value' in f){
                        if (f.value ==so.value){
                            input.prop('checked',true);
                        }
                    }
                }
                
            } else {
                //one
                var cbContainer = $('<div></div>').appendTo(cg);
                if (f.inline){
                    cbContainer.addClass(f.type+'-inine');
                } else {
                    cbContainer.addClass(f.type);
                }
                if (f.className){
                    cbContainer.addClass(f.className);
                }
                var cbLabel = $('<label></label>').appendTo(cbContainer);
                var input = $('<input />').appendTo(cbLabel);
                if (f.name){
                    cbLabel.attr('for',f.name);
                }
                if (localIncludeUnchecked){
                    var hiddenInput = input;
                    var visibleInput = $('<input type=checkbox />').appendTo(cbLabel);
                    hiddenInput.attr('type','hidden');
                    if (f.name){
                        hiddenInput.attr('name',f.name);
                    }
                    if ('value' in f){
                        visibleInput.attr('value',f.value);
                    }
                    cbLabel.on('click',function(){
                        visibleInput.get(0).checked = !visibleInput.get(0).checked;
                        if (visibleInput.get(0).checked){
                            val = visibleInput.get(0).value;
                        } else {
                            val = '0';
                        }
                        $(this).find('input[type=hidden]').val(val);
                    });
                    visibleInput.on('click',function(e){
                        e.stopPropagation();
                    });
                    visibleInput.on('change',function(){
                        if (this.checked){
                            val = this.value;
                        } else {
                            val = '0';
                        }
                        //hiddenInput.val(val);
                        $(this).parent().find('input[type=hidden]').val(val);
                    });
                    if (f.checked){
                        hiddenInput.val(visibleInput.val());
                        visibleInput.prop('checked',true);
                    }else if (f.selected){
                        hiddenInput.val(visibleInput.val());
                        visibleInput.prop('checked',true);
                    }
                    for (var i =0, l= input_attributes_for_checkbox.length; i< l;i++){
                        var e = input_attributes_for_checkbox[i];
                        
                        if (e in f){
                            if (e.toLowerCase() != 'id'){
                                visibleInput.attr(e,f[e]);
                            }
                            hiddenInput.attr(e,f[e]);
                        }
                    }
                } else {
                    input.attr('type',f.type);
                    if (f.name){
                        input.attr('name',f.name);
                    }
                    if ('value' in f){
                        input.attr('value',f.value);
                    }
                    
                    for (var i =0, l= input_attributes_for_checkbox.length; i< l;i++){
                        var e = input_attributes_for_checkbox[i];
                        if (e in f){
                            input.attr(e,f[e]);
                        }
                    }
                    if (f.checked){
                        input.prop('checked',true);
                    }else if (f.selected){
                        input.prop('checked',true);
                    }
                }
                
                if (f.title){
                    cbLabel.append(f.title);
                }
                
            }
            
            
        } else {
            if (f.type == 'select'){
                var input = $('<select />').appendTo(cg);
                
                if (f.options){
                    for (var i =0, l= f.options.length; i< l;i++){
                        var opt = partialRenderOption(f.options[i]);
                        opt.appendTo(input);
                    }
                }
                if (f.optGroups){
                    for (var ogi =0, ogl= f.optGroups.length; ogi< ogl;ogi++){
                        var oso = f.optGroups[i];
                        var optGroup = $('<optgroup />').appendTo(input);
                        if (oso.label){
                            optGroup.append(oso.label);
                        }
                        if (oso.disabled){
                            optGroup.attr('disabled',true);
                        }
                        if (oso.options){
                            for (var i =0, l= oso.options.length; i< l;i++){
                                var opt = partialRenderOption(oso.options[i]);
                                opt.appendTo(input);
                            }
                        }
                    }
                }
                
            } else {
                var input = $('<input />').appendTo(cg);
                if (f.type){
                    input.attr('type',f.type);
                } else {
                    input.attr('type','text');
                }
            }
            label.addClass('control-label');
            input.addClass('form-control');
            if (f.label){
                label.append(f.label);
                input.attr('placeholder',f.label);
            }
            if (f.name){
                input.attr('name',f.name);
            }
            if (f.className){
                input.addClass(f.className);
            }
            if ('value' in f){
                if (f.type == 'select' || f.type=='textarea'){
                    input.val(f.value);
                } else {
                    input.attr('value',f.value);
                }
            }
            
            for (var i =0, l= input_attributes_all.length; i< l;i++){
                var e = input_attributes_all[i];
                if (e in f){
                    input.attr(e,f[e]);
                }
            }
            
            if (f.prepend){
                cg.addClass('input-group');
                $('<span></span>').addClass('input-group-addon').prependTo(cg).append(f.prepend); 
            }
            if (f.append){
                $('<span></span>').addClass('input-group-addon').appendTo(cg).append(f.append);
            }
            if (f.tip){
                $('<p></p>').addClass('help-block').appendTo(cg).append(f.tip);
            }
            
        }
    }
    
    //buttons
    
    /*
    bo = success button
    if (o.type && (o.type =='danger' || o.type =='success' || o.type=='info' || o.type=='warning' || o.type=='inverse' || o.type=='link')){
                    bo['class'] = 'btn-'+o.type;
                }
                if (o.callback && $.isFunction(o.callback)){
                    bo.callback = o.callback;
                }
    */
    var bbo = {};
    if (settings.title){
        bbo.title = settings.title;
    }
    if (settings.size){
        bbo.size = settings.size;
    }
    bbo.message =form;
    bbo.buttons = {};
    bbo.buttons.save = {label:'Save', className:'btn btn-primary', callback:function(evt){
        
            
            if (settings.callback && $.isFunction(settings.callback)){
                var o = {};
                o.form =form;
                o.target = fbox;
                o.originalEvent = evt;
                o.options = settings;
                o.dataCollection = {};
                if (typeof window.FormData === 'undefined'){
                    if ($.fn.serializeArray){
                        o.data = form.serializeArray();
                        //o.dataCollection = form.serializeArray();
                    } else {
                        o.data = {};
                    }
                    o.formdata = false;
                    
                } else {
                    o.data = new FormData(form.get(0));
                    o.formdata = true;
                    var lEntries = o.data.entries();
                    
                    while ((next = lEntries.next()) && next.done === false) {
                        var pair = next.value;
                        if (pair[0].substr(-2) == '[]'){
                            var base = pair[0].substr(0,pair[0].length-2);
                            if (typeof o.dataCollection[base] == 'undefined'){
                                o.dataCollection[base] = [];
                            }
                            o.dataCollection[base].push(pair[1]);
                        } else {
                            o.dataCollection[pair[0]]=pair[1];
                        }
                    }
                    
                    /*
                    //IE problem
                    for (var pair of o.data.entries())
                    {
                        if (pair[0].substr(-2) == '[]'){
                            var base = pair[0].substr(0,pair[0].length-2);
                            if (typeof o.dataCollection[base] == 'undefined'){
                                o.dataCollection[base] = [];
                            }
                            o.dataCollection[base].push(pair[1]);
                        } else {
                            o.dataCollection[pair[0]]=pair[1];
                        }
                    }*/
                }
                
                
                var e = jQuery.Event( "validate",o );
                var r = settings.callback.call(returnContainter,e);
                if (e.isDefaultPrevented() || r===false){
                    form.get(0).reportValidity();
                    evt.preventDefault();
                    return false;
                }
                
                if (form.get(0).checkValidity()==false){
                    form.get(0).reportValidity();
                    evt.preventDefault();
                    return false;
                }
                
                o.saved = true;
                var e = jQuery.Event( "save",o );
                
                var r = settings.callback.call(returnContainter,e);
                if (e.isDefaultPrevented() || r===false){
                    evt.preventDefault();
                    return false;
                }
            } else {
                if (form.get(0).checkValidity()==false){
                    form.get(0).reportValidity();
                    evt.preventDefault();
                    return false;
                }
            }
    }};
    if (settings.save){
        bbo.buttons.save.label = settings.save;
    }
    if (!('cancel' in settings)){
        settings.cancel =true;
    }
    if (settings.cancel){
        bbo.closeButton = false;
        bbo.buttons.cancel = {label:'Cancel', className:'btn btn-warning', callback:function(evt){
            
            if (settings.callback && $.isFunction(settings.callback)){
                var o = {};
                o.form =form;
                o.target = fbox;
                o.originalEvent = evt;
                o.options = settings;
                o.data = null;
                o.formdata = false;
                o.saved = false;
                var e = jQuery.Event( "cancel",o );
                
                var r = settings.callback.call(returnContainter,e);
                if (e.isDefaultPrevented() || r===false){
                    evt.preventDefault();
                }
                
            }
            
            
        }};
        if (settings.cancel !== true){
            bbo.buttons.cancel.label = settings.cancel;
        }
    }
    fbox = bootbox.dialog(bbo);
    
    returnContainter.dialog = fbox; 
    returnContainter.form = form;
    returnContainter.setError = function(message){
        el_error.text(message);
        if (message.length){
            el_error.show();
            el_error.removeClass('alert-warning').addClass('alert-danger');
        } else {
            el_error.hide();
        }
    }
    returnContainter.setWarning = function(message){
        el_error.text(message);
        if (message.length){
            el_error.show();
            el_error.removeClass('alert-danger').addClass('alert-warning');
        } else {
            el_error.hide();
        }
    }
    returnContainter.setCustomValidity = function(element,message){
        if (element === false || element ===null){
            form.find(':input').each(function(){
                this.setCustomValidity('');
            });
            return;
        }
        if (form.get(0)[element]){
            form.get(0)[element].setCustomValidity(message);
            if (message.length){
                form.get(0).reportValidity();
            }
        }
    }
    
    return returnContainter;
}

window.modalEditFormRender = modalEditForm;

})();