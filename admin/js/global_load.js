// Functions to be added on dom:load are simply pushed onto this array.
// This file is loaded before others so they can make use of this global variable.
// They should be bound (if necessary).
window.domloadFuncs = [];

window.domloadFuncs.push(
    function(){
	$$('input[type=text]').invoke('observe', 'click', clearInput);
    }
);

$(document).observe('dom:loaded', function(){
	console.log(window.domloadFuncs);
    for(var i=0; i<domloadFuncs.length; i++){
	window.domloadFuncs[i]();
    }
});
