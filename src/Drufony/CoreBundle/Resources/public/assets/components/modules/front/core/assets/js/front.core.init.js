(function($, window)
{

	$('.dropdown-menu.login input').on('focus', function(e){
		e.stopPropagation();
	});

	$('[data-height]').each(function(){
		$(this).height($(this).data('height'));
	});

	if (typeof Holder != 'undefined')
	{
		Holder.add_theme("dark", {background:"#424242", foreground:"#aaa", size:9}).run();
		Holder.add_theme("white", {background:"#fff", foreground:"#c9c9c9", size:9}).run();
		Holder.add_theme("primary", {background:primaryColor, foreground:"#fff", size:9}).run();
	}

})(jQuery, window);