jQuery(document).ready(
	function(){
		jQuery('#myAccordion').Accordion({
			headerSelector: 'dt',
			panelSelector: 'dd',
			activeClass: 'myAccordionActive',
			hoverClass: 'myAccordionHover',
			panelHeight: 200,
			speed: 300
		});
	}
);
