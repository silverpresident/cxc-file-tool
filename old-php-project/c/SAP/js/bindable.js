function Bindable(){
  var eventCallbacks = {};
  return {
    on: function(event, callback){
      eventCallbacks[event] = eventCallbacks[event] || [];
      eventCallbacks[event].push(callback);
      return this;
    },
    off: function(event){
      if (eventCallbacks[event]){
        eventCallbacks[event].length = 0;
      }
      return this;
    },
    trigger: function(event){
      var callbacks = eventCallbacks[event];
      if (callbacks && callbacks.length){
          var args = Array.prototype.slice.call(arguments);
          var r;
          var self = this;
          args.shift();
            callbacks.forEach (function(callback){
                r = callback.apply(self,args);
                if (r === false){
                    
                }
            });
      }
      return this;
    },
  };
}