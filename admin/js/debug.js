/**
 * debug.js
 * Author: Gabe Berke-Williams, 2009
 *
 * Contains useful debugging functions, most of them tied to Firebug.
 * If you don't have it, I recommend it.
 */
/** The default print function to call.
 * Can be one of: log, debug, error, warn.
 */
var defaultFx = 'debug';
window._bug = function(name, value, type){
    /**
     * 'type' lets you e.g. specify 'warn' for console.warn
     */
    var type = (type === undefined) ? defaultFx : type;
    var func = console[type];
    if(value === null){
	value = 'null';
    } else if(value === undefined){
	value = 'undefined';
    }
    if(typeof value === 'object'){
	// real object or array
	func('%s:', name);
	func(value);
    } else {
	func('%s: %s', name, value);
    }
    func('=========================');
}

/**
 * These functions are convenience wrappers
 * around window._bug.
 */
window.bug = function(name, value){
    window._bug(name, value, defaultFx);
}
window.bug_log = function(name, value){
    window._bug(name, value, 'log');
}
window.bug_warn = function(name, value){
    window._bug(name, value, 'warn');
}
window.bug_error = function(name, value){
    window._bug(name, value, 'error');
}
/**
 * Use bug_msg to just print out a string
 */
window.bug_msg = function(msg){
    console[defaultFx](msg);
}

/**
 * Provides a nice string representation of whatever is passed in.
 */
function pretty_print(x){
    var isArr = (x.constructor === Array);
    var str = '';
    var type = typeof x;
    if( type === 'string' ){
	str = x;
    }
    else if( type === 'boolean' ){
	str = (x === true ? 'true' : 'false');
    }
    else if( x === undefined ){
	str = 'undefined';
    }
    else if( x === null ){
	str = 'null';
    }
    else if( type === 'number' ){
	str = parseFloat(x);
    }
    else if( isArr ){
	str = "[" + x.join(',') + "]";
    }
    else if( toString.call(x) === "[object Object]" ){
	// for( )
    }
    return str;
}


