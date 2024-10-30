// ANIMATE CSS
jQuery(document).ready(function () {

	wow = new WOW({
		animateClass: 'animated',
		offset: 100
	});

	wow.init();
});

jQuery(function () {
	var ct_vote = jQuery('.ct-vote');
	var ct_progress_wrap = ct_vote.find('.ct-progress');
	var ct_progress_inner = ct_progress_wrap.find('.inner');
	var ct_progress = ct_progress_inner.find('.progress-bar');
	var post_id = jQuery('input[name=post_id]').val();
	var tooltip = jQuery('.ct-vote').find('p');
	var rtl = jQuery('input[name=hidden_rtl]').val();
	var rating_type = jQuery('input[name=rating_type]').val();
	var static_text_str = jQuery('input[name=hidden_static_text]').val();
	var static_text = typeof (static_text_str) != 'undefined' ? static_text_str.split(",") : new Array();

	var post_id_voted_cookie = readCookie('post_id_voted');
	var user_voted = jQuery('input[name=hidden_flag]').val();
	var guest_voted = false;

	if (post_id_voted_cookie != null) {
		var parts = post_id_voted_cookie.split("-");
		for (i = 0; i < parts.length; i++) {
			if (parts[i] == post_id)
				guest_voted = true;
		}
	};
	//if users don't login
	if (typeof (user_voted) == 'undefined') {
		if (!guest_voted) {
			ajax_user_vote(ct_vote, ct_progress_wrap, ct_progress_inner, ct_progress, post_id, tooltip, static_text, rtl, rating_type);
		} else {
			show_msg(ct_progress_inner, tooltip, static_text);
			ct_rating_hide_comment_submit();
		}
	} else {
		if (user_voted == false) {
			ajax_user_vote(ct_vote, ct_progress_wrap, ct_progress_inner, ct_progress, post_id, tooltip, static_text, rtl, rating_type);
		} else {
			show_msg(ct_progress_inner, tooltip, static_text);
			ct_rating_hide_comment_submit();
		}
	}

});

function ajax_user_vote(ct_vote, ct_progress_wrap, ct_progress_inner, ct_progress, post_id, tooltip, static_text, rtl, rating_type) {
	var ct_vote = jQuery('.ct-vote');
	var ct_progress_wrap = ct_vote.find('.ct-progress');
	var ct_progress_inner = ct_progress_wrap.find('.inner');
	var ct_progress = ct_progress_inner.find('.progress-bar');
	var ctWidthDivider = jQuery(ct_progress_wrap).width() / 100;
	var title_obj = ct_vote.find('.rating_title');
	var score_obj = ct_vote.find('.score');
	var total_us_rate_obj = ct_vote.find('.total_user_rate');
	var initial_total_user_rate = jQuery('input[name=hidden_total_user_rate]').val();
	var initial_avg_user_rate = jQuery('input[name=hidden_avg_score_rate]').val();
	var vote_str = initial_total_user_rate > 1 ? static_text[2] : static_text[4];

	ct_progress_inner.on('mousemove click mouseleave mouseenter', function (e) {
		if (e.type == 'mousemove' || e.type == 'click') {
			var ctParentOffset = jQuery(this).parent().offset();
			var ctBaseX = Math.ceil((e.pageX - ctParentOffset.left) / ctWidthDivider);
			if (rtl == false) {
				var ctFinalX = ctBaseX + '%';
				var score = ctBaseX / 10;
			} else {
				var ctFinalX = 100 - ctBaseX + '%';
				var score = (10 - ctBaseX / 10).toFixed(1);
			}

			if (rating_type == 'percent') {
				var text_score = (score * 10) + '%'
			} else {
				var text_score = score;
			}

			title_obj.html(static_text[0]);
			total_us_rate_obj.html('');
			score_obj.html(text_score);
			ct_progress.css('width', ctFinalX);
		}

		if (e.type == 'mouseleave') {
			title_obj.html(static_text[1] + ':');
			total_us_rate_obj.html('(' + initial_total_user_rate + ' ' + vote_str + ')');
			if (rating_type == 'percent') {
				var initial_avg_user_rate_text = (initial_avg_user_rate * 10) + '%'
			} else {
				var initial_avg_user_rate_text = initial_avg_user_rate;
			}
			score_obj.html(initial_avg_user_rate_text);
			ct_progress.css('width', initial_avg_user_rate * 10 + '%');
		}


		if (e.type == 'click') {
			ct_progress_inner.off('mousemove click mouseleave mouseenter');
		}



	});


}

function ct_rating_hide_comment_submit() {
	jQuery("#wp-ct_review_form-wrap").hide();
	jQuery("#ct_rating_submit").hide();
}

function ct_rating_submit() {
	var rating_type = jQuery('input[name=rating_type]').val();
	
	tinyMCE.triggerSave();
	var ct_review_form = tinyMCE.activeEditor.getContent();
	
	var new_score = jQuery(".ct-vote .score").html();
	var post_id = jQuery('input[name=post_id]').val();
	var ct_vote = jQuery('.ct-vote');
	var title_obj = ct_vote.find('.rating_title');
	var static_text_str = jQuery('input[name=hidden_static_text]').val();
	var static_text = typeof (static_text_str) != 'undefined' ? static_text_str.split(",") : new Array();
	var initial_total_user_rate = jQuery('input[name=hidden_total_user_rate]').val();
	var initial_avg_user_rate = jQuery('input[name=hidden_avg_score_rate]').val();
	var avg_after_vote = (((parseInt(initial_total_user_rate) - 1) * parseFloat(initial_avg_user_rate) + parseFloat(new_score)) / initial_total_user_rate);
	var ct_progress_wrap = ct_vote.find('.ct-progress');
	var ct_progress_inner = ct_progress_wrap.find('.inner');
	var ct_progress = ct_progress_inner.find('.progress-bar');
	var total_us_rate_obj = ct_vote.find('.total_user_rate');
	var score_obj = ct_vote.find('.score');
	var vote_str = initial_total_user_rate > 1 ? static_text[2] : static_text[4];
	var user_rating_block = jQuery('.rating-block');
	var tooltip = jQuery('.ct-vote').find('p');

	if(jQuery('#ct_rating_submit').length > 0) {
		if (rating_type == 'star') {
			new_score = jQuery(".rating-block input[name=score]").val();
			new_score = parseInt(new_score * 2);
		} else if (rating_type == 'point') {
			new_score = parseFloat(new_score);
		} else {
			new_score = parseInt(new_score.slice(0, -1)) / 10;
		}
	}
	
	jQuery.ajax({
		type: 'post',
		url: ct_rating.ajaxurl,
		data: {
			'ct_review_form': ct_review_form,
			'score': new_score,
			'post_id': post_id,
			'action': 'add_user_rate'
		},
		success: function () {

			ct_rating_hide_comment_submit();

			if (rating_type != 'star') {
				initial_total_user_rate = (parseInt(initial_total_user_rate) + 1);
				total_us_rate_obj.html('(' + initial_total_user_rate + ' ' + vote_str + ')');
				avg_after_vote = (((parseInt(initial_total_user_rate) - 1) * parseFloat(initial_avg_user_rate) + parseFloat(new_score)) / initial_total_user_rate);
				if (rating_type == 'percent') {
					var avg_after_vote_text = (avg_after_vote.toFixed(1) * 10) + '%';
				} else {
					var avg_after_vote_text = avg_after_vote.toFixed(1);
				}

				title_obj.html(static_text[1] + ':');
				score_obj.html(avg_after_vote_text);
				ct_progress.css('width', initial_avg_user_rate * 10 + '%');
				show_msg(ct_progress_inner, tooltip, static_text);
			} else {
				tooltip = jQuery('.rating-stars').find('p');
				show_msg_star_type(user_rating_block, tooltip, static_text);
			}
			
			jQuery( document.body ).trigger( 'ct_rating_completed' );
		}
	});

}

function show_msg(ct_progress_inner, tooltip, static_text) {
	tooltip.html(static_text[3]);
	ct_progress_inner.hover(function () {
		if (!tooltip.hasClass('active')) {
			tooltip.addClass('active');
		};
	}, function () {
		if (tooltip.hasClass('active')) {
			tooltip.removeClass('active');
		};
	});
};


jQuery(document).ready(function () {

	var post_id = jQuery('input[name=post_id]').val();
	var rtl = jQuery('input[name=hidden_rtl]').val();
	var static_text_str = jQuery('input[name=hidden_static_text]').val();
	var static_text = typeof (static_text_str) != 'undefined' ? static_text_str.split(",") : new Array();
	var user_rating_block = jQuery('.rating-block');
	var tooltip = user_rating_block.find('p');

	var initial_total_user_rate = jQuery('input[name=hidden_total_user_rate]').val();
	var initial_avg_user_rate = jQuery('input[name=hidden_avg_score_rate]').val();

	var post_id_voted_cookie = readCookie('post_id_voted');
	var user_voted = jQuery('input[name=hidden_flag]').val();
	var guest_voted = false;
	var read_only = false;

	if (post_id_voted_cookie != null) {
		var parts = post_id_voted_cookie.split("-");
		for (i = 0; i < parts.length; i++) {
			if (parts[i] == post_id)
				guest_voted = true;
		}
	}

	if (typeof (user_voted) == 'undefined') {
		if (!guest_voted) {
			// ajax_user_vote();
		} else {
			show_msg_star_type(user_rating_block, tooltip, static_text);
			read_only = true;
		}
	} else {
		if (user_voted == false) {
			// ajax_user_vote();
		} else {
			show_msg_star_type(user_rating_block, tooltip, static_text);
			read_only = true;
		}
	}

	jQuery('#rating-id').raty({
		half: true,
		readOnly: read_only,
		score: function () {
			return jQuery(this).attr('data-score');
		},
		click: function (rating, evt) {
			var $this = jQuery(this);
			$this.css("pointer-events", "none");
		}
	});



});

function show_msg_star_type(user_rating_block, tooltip, static_text) {
	tooltip.html(static_text[3]);
	user_rating_block.hover(function () {
		if (!tooltip.hasClass('active')) {
			tooltip.addClass('active');
		};
	}, function () {
		if (tooltip.hasClass('active')) {
			tooltip.removeClass('active');
		};
	});
};

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}