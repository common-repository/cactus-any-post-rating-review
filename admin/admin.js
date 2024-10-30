jQuery(document).ready(function(e) {
    jQuery('#c_aprr-form .image-select input:radio').addClass('input_hidden');
	jQuery('#c_aprr-form .image-select label').click(function(){
		jQuery(this).addClass('selected').siblings().removeClass('selected');
	});
});