window.domloadFuncs.push(function(){
    var qe = new QuoteEditor(window.season, window.ep_num, window.cb_dir, 'status', 'undo');
    /** ENGAGE **/
    qe.startEditing();
});

String.prototype.toNum = function(){
    return parseInt(this);
};

// Get rid of all FIXME's!
// FIXME: fix flashback css so boxes still line up
// FIXME: have undo() actually do something
// - make use of addAnchor and removeAnchor (currently add/removeAnchor)
// - we only send the most recent ones but maybe could use them as arrays
//   for advanced undo stuff?
var QuoteEditor = Class.create({
initialize: function(season, ep_num, cb_dir, statusElemId, undoElemId){
		this.season = season;
		this.ep_num = ep_num;
		this.cb_dir = cb_dir;
		this.statusElem = $(statusElemId);
		this.undoElem = $(undoElemId);
		if( ! (season && ep_num && cb_dir && this.statusElem && this.undoElem) ){
		    // ERR
		    alert('uh-oh');
		    return false;
		}
		/** this.addLines holds currently selected lines to be added. */
		this.addLines = [];
		/**
		 * this.removeLine is an element which is the start or end
		 * of an existing quote which should be removed.
		 */
		this.removeLine = null;

		/**
		 * this.addAnchor and this.removeAnchor are anchors like "q29".
		 * Set them to null to "clear" them.
		 * FIXME - add docs
		 */
		this.addAnchor = null;
		this.removeAnchor = null;
		this.canUndo = false; // make sure undo() fires only once
		this.lastActionType = ''; // so undo knows what to do
		/** this.highestAnchor is a number like "28", not "q28". */
		this.highestAnchor = 0;
		/**
		 * this.recentlyRemoved exists to provide "undo" functionality.
		 * It is an array of line elements: $(minId, maxId).
		 */
		this.recentlyRemoved = [];
		this.recentlyAdded = []; // an array like so: ['q17', 'l1', 'l8'] 
		/**
		 * this.anchorToLines is an object like: {anchor: [minId,maxId]}.
		 */
		this.anchorToLines = {};
		/**
		 * this.linesToAnchor maps the start/end line *ids* of a quote
		 * to the same anchor so that we can send an anchor ("q17")
		 * regardless of whether the user clicks on the start or end
		 * lines.
		 * linesToAnchor['minId'] => an_anchor,
		 * linesToAnchor['maxId'] => the_same_anchor
		 */
		this.linesToAnchor = {};

	    },

/**
 * QuoteEditor.startEditing()
 * Gets quote lines and their anchors from PHP, sets style on quotes,
 * and triggers setup_events().
 */
startEditing: function(){
		  var that = this;
		  // Show style but don't let people do anything until Ajax request is successful.
		  // Set lines to their base state.
		  this.deselect($$('.line'));
		  new Ajax.Request(this.cb_dir + 'cb_get_all_quotes.php', {
		      method: 'GET',
		      parameters: {season: that.season, ep_num: that.ep_num},
		      onSuccess: function(transport){
			  that.anchorToLines = transport.responseJSON;
			  var anchorKeys = Object.keys(that.anchorToLines);
			  that.highestAnchor = parseInt( anchorKeys.last().slice(1) ); // PHP sorts it for us
			  anchorKeys.each(function(anchor){
			      var lines = that.anchorToLines[anchor];
			      var start = lines[0], end = lines[1];
			      that.linesToAnchor[start] = anchor;
			      that.linesToAnchor[end] = anchor;
			      that.select( $.apply($, lines) );
			      that.highlight( that.get_line_tween(start, end) );
			  });
			  that.setup_events(); // After highlighting/selecting, let users do stuff too.
		      },
		      onFailure: function(transport){
			  Modalbox.show('<div>Failed! Please reload.</div>');
			  Modalbox.deactivate();
			  that.statusElem.update('Failed: (' + tranport.status + ')' + transport.statusText);
		      }
		  });
	      },

setup_events: function(){
		  // Add event handlers.
		  var that = this;
		  var clickFunc = function(e){
		      // FIXME: don't do spans!
		      // findElement because sometimes we click on <span> (class=internal)
		      var elem = e.findElement('div');
		      that.canUndo = true; // let users undo again
		      if( elem.hasClassName('selected') && that.addLines.last() != elem ){
			  // Remove this quote.
			  that.remove(elem);
		      } else {
			  // elem is not selected, or if it is, user is clicking twice on the same line. Add.
			  that.add(elem);
		      }
		  };
		  $('content').observe('click', clickFunc);
		  this.undoElem.observe('click', function(e){
		      e.stop();
		      that.undo();
		  });
	      },

add: function(elem){
	 this.lastActionType = 'add';
	 this.addLines.push(elem);
	 this.select([elem]);
	 if( this.addLines.length == 2 ){
	     //This is the final line; select, store, and clear
	     var tween = this.get_line_tween(this.addLines[0], this.addLines[1]);
	     var that = this;
	     this.storeLinesAdd(this.addLines);
	     // The morphing is a little delayed from one element to the next.
	     // Nothing we can really do about that.
	     tween.invoke('morph', 'background-color:#FFFF9C', { // white -> yellow
		 duration: 0.4,
		 afterFinish: function(obj){
		     that.highlight([ obj['element'] ]);
		     obj['element'].removeAttribute('style');
		 }
	     });
	     this.send();
	 } 
     },

/**
 * QuoteEditor.remove(elem)
 * remove() only accepts one element (start/end), but it finds the other line
 * (end/start, respectively) then stores those lines,
 * animates the lines to be removed, and then tells the DB to remove the lines.
 */
remove: function(elem){
	    this.lastActionType = 'remove';
	    var anchor = this.linesToAnchor[elem.id];
	    var quoteLines = this.anchorToLines[anchor]; // ids of start/end of quote
	    var newMin = quoteLines[0], newMax = quoteLines[1];
	    var intNewMin =  parseInt(newMin.slice(1));
	    var intNewMax =  parseInt(newMax.slice(1));
	    var tween = this.get_line_tween(newMin, newMax);
	    this.storeLinesRemove(anchor);
	    this.recentlyRemoved = $(newMin, newMax);
	    /** UI stuff - dehighlighting etc. **/
	    /**
	     * If we deselect the tween and the tween
	     * contains another quote, that one gets deselected too.
	     * So we remove all lines in the tween that are
	     * also in other quotes, THEN deselect.
	     */
	    var that = this; // lexical closures ftw.
	    for(var k in this.anchorToLines){
		var qlMin = this.anchorToLines[k][0];
		var qlMax = this.anchorToLines[k][1];
		if( intNewMin > parseInt(qlMax.slice(1)) ||
		    intNewMax < parseInt(qlMin.slice(1)) ){
		    // No intersection. - this quote either starts after the existing or ends before it.
		    continue;
		}
		var currentTween = this.get_line_tween(qlMin, qlMax).pluck('id');
		// Remove lines from tween if they are in the current loop's quote.
		tween = tween.reject(function(el){
		    return currentTween.include(el.id);
		});
	    }
	    this.deselect($.apply($, this.recentlyRemoved) );
	    tween.invoke('morph', 'background-color:#FFFFFF', { // yellow -> white
		duration: 0.4,
		afterFinish: function(obj){
		    that.dehighlight([ obj['element'] ]);
		    obj['element'].removeAttribute('style');
		}
	    });

	    /** END UI stuff **/
	    /*** SEND ***/
	    // I think sending before the visual confirmation is sent is a bad thing.
	    // So, do at the end.
	    this.send();
	},

/**
 * FIXME - need docs
 */
send: function(){
	  if(this.addAnchor === null && this.removeAnchor === null){
	      return false;
	  }
	  //Modalbox.show('<div>Sending.</div>');
	  //Modalbox.deactivate();
	  var that = this;
	  var paramStr = ['season='+this.season, 'ep_num='+this.ep_num];
	  if( this.addAnchor !== null ){
	      // FIXME - should we even clear addLines in storeLinesAdd? 
	      // Seems like addValues is the same thing.
	      var addValues = this.anchorToLines[this.addAnchor];
	      paramStr.push('add[]='+this.addAnchor+'+'+addValues[0]+'+'+addValues[1]);
	  }
	  if( this.removeAnchor !== null ){
	      paramStr.push('remove[]='+this.removeAnchor);
	  }
	  this.addAnchor = this.removeAnchor = null;
	  paramStr = paramStr.join('&');
	  new Ajax.Request(this.cb_dir + 'cb_quote.php', {
	      parameters: paramStr,
	      onCreate: function(transport){
		  that.statusElem.update('Sending...');
	      },
	      onException: function(transport, e){
		  that.statusElem.update('Oh no! Error ' + transport.status + ': ' + transport.statusText);
	      },
	      onFailure: function(transport){
		  that.statusElem.update('Oh no! Error ' + transport.status + ': ' + transport.statusText);
	      },
	      onSuccess: function(transport){
		  that.statusElem.update('Done!');
	      },
	      onComplete: function(transport){
		  //Modalbox.hide();
	      }
	  });
	  return true;
      },

/**
 * QuoteEditor.storeLinesAdd()
 * storeLinesAdd checks if this.addLines contains elements that are 
 * already the start/end of a quote (which it shouldn't).
 * If not, it ties the start and end of the passed-in lines to a
 * new anchor, and the anchor to the lines.
 */
storeLinesAdd: function(){
		   var ids = this.addLines.pluck('id').invoke('slice', 1).invoke('toNum');
		   this.addLines = [];
		   // Doesn't matter which of min/max is used.
		   // Both (or neither) will be in this.linesToAnchor.
		   var min = 'l'+ids.min();
		   if( this.linesToAnchor[min] === undefined ){
		       // Not yet added to DB. - FIXME - check status
		       // after calling send() so that if it fails
		       // it's removed from linesToAnchor etc.
		       var  max = 'l'+ids.max();
		       this.highestAnchor++;
		       var anchor = 'q'+this.highestAnchor;
		       this.linesToAnchor[min] = anchor;
		       this.linesToAnchor[max] = anchor;
		       this.anchorToLines[anchor] = [min, max];
		       this.addAnchor = anchor;
		   }
	       },

/**
 * QuoteEditor.storeLinesRemove(anchor)
 * Sets this.removeAnchor to the passed-in anchor
 * and deletes the anchor and its associated lines from
 * this.linesToAnchor and this.anchorToLines.
 */
storeLinesRemove: function(anchor){
		      if( this.removeAnchor !== anchor ){
			  this.removeAnchor = anchor;
			  // Make way for new quotes.
			  // This does mean that we lose the old quote,
			  // so if they remove a quote that goes from
			  // x -> y then add a quote from x -> y,
			  // that quote gets a new anchor.
			  // FIXME: Unless...mysql trickery!
			  var lines = this.anchorToLines[anchor];
			  delete this.linesToAnchor[ lines[0] ];
			  delete this.linesToAnchor[ lines[1] ];
			  delete this.anchorToLines[anchor];
		      }
		  },

undo: function(){
	  if( ! this.canUndo ){
	      return;
	  }
	  this.canUndo = false;
	  if( this.lastActionType === 'add' ){
	      if( this.addLines.length === 0 ){
		  // Already stored, pop it off and deselect.
		  // FIXME: this entry in anchorToLines is deleted when we remove the quote,
		  // so how do we undo?
		  var lines = this.anchorToLines[ this.addAnchor ];
		  this.addAnchor = null;
		  var tween = this.get_line_tween($(lines[0]), $(lines[1]));
		  this.deselect( tween );
	      } else {
		  // Not yet stored.
		  this.deselect( $.apply($, this.addLines) );
		  this.addLines = [];
	      }
	  } else if( this.lastActionType === 'remove' ){
	      if( this.addLines.length !== 0 ){
		  this.deselect( $.apply($, this.addLines) );
	      }
	      this.select( $.apply($, this.recentlyRemoved) );
	      this.highlight( this.get_line_tween(this.recentlyRemoved[0], this.recentlyRemoved[1]) );
	      this.addAnchor = this.removeAnchor;
	      this.removeAnchor = null;
	  }
	  this.send();
      },

/**
 * For QuoteEditor.[de]highlight and [de]select,
 * you must wrap the passed-in element(s) in an array.
 */
highlight: function(lines){
		 lines.invoke('addClassName', 'highlight');
	   },

dehighlight: function(lines){
		 lines.invoke('removeClassName', 'highlight');
	     },

select: function(lines){
	    lines.invoke('addClassName', 'selected').invoke('removeClassName', 'offedit');
	},

deselect: function(lines){
	      lines.invoke('removeClassName','selected').invoke('addClassName','offedit');
	  },

/**
 * QuoteEditor.get_line_tween(elOne, elTwo)
 * Given two line elements, returns array of
 * line *elements* from min to max of
 * the passed-in elements' ids.
 * Always returns at least two elements, so if elOne and elTwo
 * have the same id, returns eg [<e#l8>, <e#l8>].
 * So given [<elem#l8>, <elem#l12>],
 * it returns
 * ["<e#l8>", "<e#l9>", "<e#l10>", "<e#l11>", "<e#l12>"]
 */
get_line_tween: function(elOne, elTwo){
	// allow for passing in ids.
	elOne = $(elOne);
	elTwo = $(elTwo);
	if(elOne.id === elTwo.id){
	    return $(elOne.id, elTwo.id);
	}
	var start=false, end=false;
	// [32, 9].sort() => [32, 9]. GREAT JOB, JAVASCRIPT.
	var numOne = parseInt(elOne.id.slice(1));
	var numTwo = parseInt(elTwo.id.slice(1));
	if(numOne < numTwo){
	    start=numOne;
	    end=numTwo;
	} else {
	    start=numTwo;
	    end=numOne;
	}
	// turn the nums into extended elements
	return $R(start, end).map(function(n){ return $('l'+n) });
    }
});
