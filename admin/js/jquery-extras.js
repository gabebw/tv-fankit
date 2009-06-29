$.extend({
    min: function(arr){
	     /**
	      * Returns false if arr is not an array,
	      * null if it is a zero-length array, or
	      * the minimum if it is an array with length>=1.
	      */
	     if( $.isArray(arr) ){
		 if( arr.length === 0 ){
		     return null;
		 } else if( arr.length == 1 ) {
		     return arr[0];
		 }
	     } else {
		 // Not an array
		 return false;
	     }
	     var min=arr[0];
	     for(var i=0; i<arr.length; i++){
		 if( arr[i] < min ){
		     min=arr[i];
		 }
	     }
	     return min;
	 },
    max: function(arr){
	     /**
	      * Returns false if arr is not an array,
	      * null if it is a zero-length array, or
	      * the maximum if it is an array with length>=1.
	      */
	     if( $.isArray(arr) ){
		 if( arr.length === 0 ){
		     return null;
		 } else if( arr.length == 1 ) {
		     return arr[0];
		 }
	     } else {
		 // Not an array
		 return false;
	     }
	     var max=arr[0];
	     for(var i=0; i<arr.length; i++){
		 if( arr[i] > max ){
		     max=arr[i];
		 }
	     }
	     return max;
	 }
});
