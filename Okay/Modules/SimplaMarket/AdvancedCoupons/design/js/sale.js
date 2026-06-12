$(document).ready(function(){
	$(".fn_time_to").each(function() {
		var time_to = $(this);
		time_to.timeTo({
			seconds: parseInt(time_to.data('time_to')),
			displayDays: 2,
			fontSize: 24,
			displayCaptions: true,
			lang: time_to.data('lang_label'),
			callback: function() {
				time_to.closest(".fn_col").remove();
			}
		});
	});
});