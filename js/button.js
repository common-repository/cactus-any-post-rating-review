// JavaScript Document
(function() {
    tinymce.PluginManager.add('tm_rating', function(editor, url) {
		editor.addButton('tm_rating', {
			text: '',
			tooltip: 'Insert Review',
			id: 'cactus_review_shortcode',
			icon: 'icon-review',
			onclick: function() {
				editor.insertContent('[ct_rating]<br class="nc"/>');
			}
		});
	});
})();
