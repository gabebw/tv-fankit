/**
 * Provides easy-to-use interface for adding or removing quotes from an episode.
 * Author: Gabe Berke-Williams, 2007
 */

jQuery(document).ready(function(){
    var qe = new QuoteEditor(fkQuoteVars.season, fkQuoteVars.ep_num, fkQuoteVars.callback_get, fkQuoteVars.callback_post);
    // ENGAGE
    qe.startEditing();
});

// FIXME: fix flashback css so boxes still line up
/**
 * Constructor function. Sets variables.
 * @constructor
 */
function QuoteEditor(season, ep_num, callback_get, callback_post){
    /** @type integer */
    this.season = season;
    /** @type integer */
    this.ep_num = ep_num;
    this.callback_get = callback_get;
    this.callback_post = callback_post;
    /**
     * The element that contains status messages.
     * @type jQuery object
     */
    this.statusElem = jQuery('#status');
    /**
     * The element that the user clicks on to undo the last action.
     * @type jQuery object
     */
    this.undoElem = jQuery('#undo');
    if( ! (season &&
	   ep_num &&
	   callback_get &&
	   callback_post &&
	   this.statusElem.length==1 &&
	   this.undoElem.length==1) ){
	// ERR
	//alert('uh-oh');
	return false;
    }
    /**
     * this.addLines is an array of pure (non-jQuery wrapped) elements.
     * It holds currently selected lines to be added.
     * @type array
     */
    this.addLines = [];
    /**
     * this.removeLine is an element which is the start or end
     * of an existing quote which should be removed.
     * It is used to get lines from this.lineIdToAnchor.
     * @type element
     */
    this.removeLine = null;
    /**
     * this.addAnchor and this.removeAnchor are anchors like "q29".
     * Set them to null to "clear" them.
     * mysql will set this.addAnchor
     * as the quote anchor for this.anchorToLineIds[this.addAnchor],
     * and similarly for this.removeAnchor.
     * @type string
     */
    this.addAnchor = null;
    /**
     * @type string
     */
    this.removeAnchor = null;
    /**
     * Used to make sure undo() fires only once, and
     * only when an action has already taken place
     * that can be undone.
     * @type boolean
     */
    this.canUndo = false;
    /**
     * So undo() knows what kind of action to undo.
     * @type string
     */
    this.lastActionType = '';
    /** this.highestAnchor is a number like "28", not "q28". */
    this.highestAnchor = 0;
    /**
     * this.recent exists to provide "undo" functionality.
     * It is needed because we don't have any other way to
     * track the most recently added or removed quote.
     * It is an object like so:
     * { anchor: 'q19', lines: ["l29", "l32"] }
     * where "lines" is a jQuery object.
     */
    this.recent = {anchor: null, lines: null};
    /**
     * this.anchorToLineIds is an object like: {"q9": ["l226", "l228"]}
     */
    this.anchorToLineIds = {};
    /**
     * this.lineIdToAnchor maps the start/end ids of a quote
     * to the same anchor so that we can send an anchor ("q17")
     * regardless of whether the user clicks on the start or end
     * lines.
     * @type object
     * @example
     * lineIdToAnchor["l90"] => "q2" // start quote
     * lineIdToAnchor["l92"] => "q2" // end quote
     */
    this.lineIdToAnchor = {};
}

/**
 * sortId sorts an array of ids like ["l41", "l40"] => ["l40", "l41"].
 */
QuoteEditor.prototype.sortId = function(arr){
};

/**
 * sortById sorts an array of elements by their ID,
 * e.g. [<div#l41>, <div#l39>, <div#l41>]
 * => [<div#l39>, <div#l40>, <div#l41>]
 */
QuoteEditor.prototype.sortById = function(arr){
    return arr.sort(function(x,y){ return parseInt(x.id.slice(1)) - parseInt(y.id.slice(1));});
};

/**
 * Gets quote lines and their anchors from PHP, sets style on quotes,
 * and triggers setupEvents.
 * @function
 */
QuoteEditor.prototype.startEditing = function(){
    var that = this;
    // Show style but don't let people do anything until Ajax request is successful.
    this.deselect(jQuery('.line')); // Set lines to their base state.
    jQuery.ajax({
	url: that.callback_get,
	type: 'GET',
	// action parameter is for WP
	data: {action: 'get_all_quotes', season: that.season, ep_num: that.ep_num},
	dataType: 'json',
	success: function(data, textStatus){
	    that.anchorToLineIds = data;
	    var anchors = [];
	    for(var a in that.anchorToLineIds){
		anchors.push(a);
	    }
	    // PHP sorts the anchors for us
	    that.highestAnchor = parseInt( anchors[anchors.length-1].slice(1) );
	    jQuery.each(anchors, function(i, anchor){
		var lines = that.getLineIds(anchor);
		var start = lines[0],
		    end = lines[1];
		that.lineIdToAnchor[start] = anchor;
		that.lineIdToAnchor[end] = anchor;
		that.select('#'+start+',#'+end);
		that.highlight( that.makeTween(start,end) );
	    });
	    // After highlighting/selecting, let users do stuff too.
	    that.setupEvents();
	},
	error: function(transport, textStatus, errorThrown){
	    // FIXME - find a jQuery modalbox replacement?
	    //Modalbox.show('<div>Failed! Please reload.</div>');
	    //Modalbox.deactivate();
	    that.statusElem.text('Failed: (' + textStatus + ')' + transport.statusText);
	}
    });
};

/**
 * Adds event handlers.
 * Uses event delegation to determine which line triggered a click event,
 * so only one handler is set regardless of transcript size.
 * @function
 */
QuoteEditor.prototype.setupEvents = function(){
    var that = this;
    jQuery('#content').click(function(e){
	e.preventDefault(); // don't follow cast links away from the page
	// .closest() allows for clicking on e.g. a <span.internal> inside the <div.line>
	var elem = jQuery(e.target).closest('div.line')[0];
	if( elem === undefined ){
	    // User clicked on a non-div.line portion of #content. Don't do anything.
	    return false;
	}
	that.canUndo = true; // let users undo again
	if( that.lineIdToAnchor[elem.id] !== undefined ){
	    // Remove this quote.
	    that.remove(elem);
	} else {
	    var isSelected = jQuery(elem).hasClass('selected');
	    var alreadyClicked = that.addLines.indexOf(elem)>-1;
	    var twiceClicked = (isSelected && alreadyClicked);
	    var otherClicked = (! isSelected && ! alreadyClicked); // clicked on a different second line or is first line
	    if( twiceClicked || otherClicked ){
		// Elem is not selected, or if it is, user is clicking twice on the same line. Add.
		that.add(elem);
	    }
	}
    });
    this.undoElem.click(function(e){
	e.preventDefault();
	that.undo();
    });
};

/**
 * @description
 * Adds the specified element to the array of start/end lines. If
 * the element is the second element, animates everything between the
 * two lines and sends the quote to the database.
 * @function
 * @param {jQuery} $elem a line element
 */
QuoteEditor.prototype.add = function(elem){
    this.lastActionType = 'add';
    this.addLines.push(elem);
    this.select(jQuery(elem));
    if( this.addLines.length == 2 ){
	this.addLines = this.sortById(this.addLines);
	//This is the final line; select, store, and clear
	var tween = this.makeTween(this.addLines[0].id, this.addLines[1].id);
	var that = this;
	this.storeLinesAdd();
	// Send before animating. This prevents programmer confusion (it's sent before the animation
	// even if it's placed after the animation in the program,
	// since the animations take time and JS marches ahead) and possibly
	// to make the program seem more responsive(?) since it's already sent by the time the user
	// sees the animations.
	this.send();
	
	// jQuery can't do color animations on $() wrapped around an array
	// but it can if we feed it like $('#l2, #l3, #l4') so we do that.
	// Great job, jQuery.
	tween.animate(
	    { backgroundColor: '#FFFF9C' }, // white -> yellow
	    400,
	    function(){ that.highlight(jQuery(this).removeAttr('style')); }
	);
    } 
};

/**
 * Get the line anchor corresponding to a given a line id.
 * @function
 * @param {String} id a line id like "l30"
 * @returns {String} anchor, eg "q9"
 */
QuoteEditor.prototype.getAnchor = function(id){
    return this.lineIdToAnchor[id];
};

/**
 * Gets the lines associated with an anchor.
 * @function
 * @param {String} anchor a quote anchor
 * @param {String} id a line id like "l30"
 * @returns {Array} 2-element array of line ids like ["#l226", "#l228"]
 */
QuoteEditor.prototype.getLineIds = function(anchor){
    return this.anchorToLineIds[anchor];
};

/**
 * remove() only accepts one element (start/end), but it finds the other line
 * (end/start, respectively) then stores those lines,
 * animates the lines to be removed, and then tells the DB to remove the lines.
 * @function
 * @param elem A non-jQuery wrapped element.
 */
QuoteEditor.prototype.remove = function(elem){
    this.lastActionType = 'remove';
    var anchor = this.getAnchor(elem.id);
    // quoteLines is start/end ids like:  ["l4", "l6"]
    var quoteLines = this.getLineIds(anchor);
    var minId = quoteLines[0], maxId = quoteLines[1];
    var minInt = parseInt(minId.slice(1)), maxInt = parseInt(maxId.slice(1));
    var tween = this.makeTween(minId, maxId);
    this.storeLinesRemove(anchor);
    this.recent['anchor'] = anchor;
    this.recent['lines'] = [minId,maxId];
    /* SEND */
    this.send();
    /* After this is only UI stuff - dehighlighting etc. */
    this.deselect(jQuery('#'+minId+',#'+maxId));
    /**
     * @description
     * If we deselect the tween and the tween
     * contains another quote, that one gets deselected too.
     * So we remove all lines in the tween that are
     * also in other quotes, THEN deselect.
     */
    var that = this; // lexical closures ftw.
    for(var a in this.anchorToLineIds){
	var lines = this.getLineIds(a);
	var qlMinId = lines[0], qlMaxId = lines[1];
	/*
	bug('[remove] lines', lines);
	bug('[remove] qlMin', qlMin);
	bug('[remove] qlMax', qlMax);
	*/
	// TODO: Is there a way to tell beforehand if none of the quotes intersect? Optimize?
	if( minInt > parseInt(qlMaxId.slice(1)) || maxInt < parseInt(qlMinId.slice(1)) ){
	    // No intersection. - this quote either starts after the existing or ends before it.
	    continue;
	}
	var currentTween = this.makeTween(qlMinId, qlMaxId);
	// Remove lines from tween if they are in the current loop's quote.
	tween = jQuery.grep(tween, function(el, i){
	    return jQuery.inArray(el, currentTween) == -1;
	});
    }
    // Re-wrap in jQuery because $.grep returns an array
    jQuery(tween).animate(
	{ backgroundColor: '#FFFFFF' }, // yellow -> white
	400,
	function(){ that.dehighlight(jQuery(this).removeAttr('style')); }
    );
    /* END UI stuff */
};

/**
 * Sends the add/remove action to the DB.
 * @function
 * @description FIXME - need docs
 */
QuoteEditor.prototype.send = function(){
    if(this.addAnchor === null && this.removeAnchor === null){
	return false;
    }
    if( this.addAnchor !== null && this.removeAnchor !== null){
	return false;
    }
    //Modalbox.show('<div>Sending.</div>');
    //Modalbox.deactivate();
    var that = this;
    var params = {season: this.season, ep_num: this.ep_num};
    if( this.addAnchor !== null ){
	//paramStr.push('add[]='+[this.addAnchor, this.addLines[0].id, this.addLines[1].id].join('+'));
	params['add[]'] = [this.addAnchor, this.addLines[0].id, this.addLines[1].id].join('+');
    }
    if( this.removeAnchor !== null ){
	//paramStr.push('remove[]='+this.removeAnchor);
	params['remove[]'] = this.removeAnchor;
    }
    this.addAnchor = this.removeAnchor = null;
    // Clear this.addlines because otherwise it gets more than 2 elements
    // and things get wonky.
    this.addLines = [];
    // For WP
    params['action'] = 'add_remove_quote';
    //paramStr = paramStr.join('&');
    jQuery.ajax({
	url: that.callback_post,
	type: 'POST',
	data: params,
	beforeSend: function(transport){
	    that.statusElem.text('Sending...');
	},
	error: function(transport, textStatus, errorThrown){
	    that.statusElem.text('Oh no! Error ' + transport.status + ': ' + transport.statusText);
	},
	success: function(data, textStatus){
	    that.statusElem.text('Done!');
	},
	complete: function(transport){
	    //Modalbox.hide();
	}
    });
    return true;
};

/**
 * @function
 * storeLinesAdd checks if this.addLines contains elements that are 
 * already the start/end of a quote (which it shouldn't).
 * If not, it ties the start and end of the passed-in lines to a
 * new anchor, and the anchor to the lines.
 */
QuoteEditor.prototype.storeLinesAdd = function(){
    var min = this.addLines[0].id, max = this.addLines[1].id;
    // Doesn't matter which of min/max is used.
    // Both (or neither) will be in this.lineIdToAnchor.
    if( this.lineIdToAnchor[min] === undefined ){
	// Not yet added to DB. - FIXME - check status
	// after calling send() so that if it fails
	// it's removed from lineIdToAnchor etc.
	this.highestAnchor++;
	var anchor = 'q'+this.highestAnchor;
	this.recent['anchor'] = anchor;
	this.recent['lines'] = [min, max];
	this.addAnchor = anchor;
	this.lineIdToAnchor[min] = anchor;
	this.lineIdToAnchor[max] = anchor;
	this.anchorToLineIds[anchor] = [min, max];
    }
};

/**
 * Sets this.removeAnchor to the passed-in anchor
 * and deletes the anchor and its associated lines from
 * this.lineIdToAnchor and this.anchorToLineIds.
 * @function
 * @param {String} anchor anchor corresponding to lines to remove
 */
QuoteEditor.prototype.storeLinesRemove = function(anchor){
    if( anchor != this.removeAnchor ){
	this.removeAnchor = anchor;
	// Make way for new quotes.
	// This does mean that we lose the old quote,
	// so if they remove a quote that goes from
	// x -> y then add a quote from x -> y,
	// that quote gets a new anchor.
	// FIXME: Unless...mysql trickery!
	var lines = this.getLineIds(anchor);
	delete this.lineIdToAnchor[ lines[0] ];
	delete this.lineIdToAnchor[ lines[1] ];
	delete this.anchorToLineIds[anchor];
    }
};

/**
 * Undoes last user action (add/remove).
 * @function
 * FIXME: add docs
 */
QuoteEditor.prototype.undo = function(){
    if( ! this.canUndo ){
	return;
    }
    this.canUndo = false;
    if( this.lastActionType === 'add' ){
	if( this.addLines.length === 0 ){
	    // Already stored, set removeAnchor for later sending and deselect.
	    this.addAnchor = null;
	    this.removeAnchor = this.recent['anchor'];
	    var lineIds = this.recent['lines'];
	    var tween = this.makeTween(lineIds[0], lineIds[1]);
	    this.dehighlight(tween);
	    this.deselect(tween);
	} else {
	    // Not yet stored, just deselect and clear addLines.
	    this.deselect(this.addLines);
	    this.addLines = [];
	}
    } else if( this.lastActionType === 'remove' ){
	/**
	 * If they hit undo while they're in the
	 * midst of adding another quote,
	 * deselect the selected line.
	 */
	if( this.addLines.length !== 0 ){
	    this.deselect( this.addLines );
	}
	this.select( this.recent );
	this.highlight( this.makeTween(this.recent[0], this.recent[1]) );
	this.addAnchor = this.removeAnchor;
	this.removeAnchor = null;
    }
    this.send();
};

/*
 * For QuoteEditor.[de]highlight and [de]select, pass
 * in something that jQuery can wrap, ie
 * a jQuery element, an array of pure (non-jQuery) elements,
 * or a selector string like "#l90,#l92".
 */

/**
 * @function
 */
QuoteEditor.prototype.highlight = function(lines){
    jQuery(lines).addClass('highlight');
};

/**
 * @function
 */
QuoteEditor.prototype.dehighlight = function(lines){
    jQuery(lines).removeClass('highlight');
};

/**
 * @function
 */
QuoteEditor.prototype.select = function(lines){
    jQuery(lines).addClass('selected').removeClass('offedit');
};

/**
 * @function
 */
QuoteEditor.prototype.deselect = function(lines){
    jQuery(lines).removeClass('selected').addClass('offedit');
};

/**
 * Given two line ids like "l29" and "l32", returns
 * a jQuery object of elements from min to max
 * of the passed-in elements' ids.
 * If the ids are the same, returns a single-element object.
 * @function
 * @param   {String} idOne
 * @param   {String} idTwo
 * @returns {jQuery} a jQuery object of 1+ elements
 * @updated 2009-03-01
 * @example
 * So given "l8" and "l12",
 * it returns
 * $(<#l8>, <#l9>, <#l10>, <#l11>, <#l12>)
 */
QuoteEditor.prototype.makeTween = function(idOne, idTwo){
    var start,
	end;
    // "l60" -> 60
    idOne = parseInt(idOne.slice(1));
    idTwo = parseInt(idTwo.slice(1));
    if(idOne === idTwo){
	return jQuery('#'+idOne);
    }else if(idOne < idTwo) {
	start = idOne;
	end = idTwo;
    } else if(idOne > idTwo ){
	start = idTwo;
	end = idOne;
    }
    // Minor optimization(?) - use :lt to whittle down
    // how many lines we select.
    // :lt(60) gives div.line from #l1 to #l60,
    // since we start at "#l1" but jQuery indexes from 0.
    return jQuery('div.line:lt('+end+')').slice(start-1);
};
