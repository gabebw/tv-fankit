window.domloadFuncs.push( function(){
    jQuery('.line .dialogue').editable('callbacks/cb_ep.php', {
	indicator: 'Saving...',
	tooltip: 'Click to edit.',
	submit: 'OK',
	cancel: 'Cancel',
	name: 'new_line_text',
	submitdata: function(value, settings){
	    var myid = jQuery(this).closest('div.line').attr('id').slice(1);
	    return {season: window.season, ep_num: window.ep_num, line_num: myid};
	}
    });
});
