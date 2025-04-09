//http://stackoverflow.com/questions/6658937/how-to-check-if-a-form-is-valid-programatically-using-jquery-validation-plugin
;
$.fn.isValid = function(){
    var validate = true;
    this.each(function(){
        if (this.checkValidity()==false){
            validate = false;
        }
    });
    return validate;
};
$.fn.getValidationMessage = function(){
    var message = "";
    var name = "";
    this.each(function(){
        if (this.checkValidity()==false){
            name = ($( "label[for=" + this.id + "] ").html() || this.placeholder || this.name || this.id);
            message = message + name +":"+ (this.validationMessage || 'Invalid value.')+"\n<br>";
        }
    })
    return message;
};
