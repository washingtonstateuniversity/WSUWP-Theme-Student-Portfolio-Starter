(function($, window){
	/**
	 * Cache the portfolio creation form element for use throughout.
	 *
	 * @type {*|HTMLElement}
	 */
	var portfolio_create_form = $('.portfolio-create-form');

	/**
	 * Cache the spinner element for use throughout.
	 *
	 * @type {*|HTMLElement}
	 */
	var portfolio_loading = $('.portfolio-loading');

	/**
	 * Handle the click action on the form submission button.
	 */
	function handle_click( e ) {
		e.preventDefault();

		var portfolio_name = $('#portfolio-name').val(),
			portfolio_path = $('#portfolio-path').val(),
			nonce        = $('#portfolio-create-nonce').val();

		// Build the data for our ajax call
		var data = {
			action:       'submit_portfolio_create_request',
			portfolio_name: portfolio_name,
			portfolio_path: portfolio_path,
			_ajax_nonce:  nonce
		};

		portfolio_create_form.hide();
		portfolio_loading.show();

		// Make the ajax call
		$.post( window.portfolio_create_data.ajax_url, data, function( response ) {
			response = $.parseJSON( response );

			if ( response.success ) {
				portfolio_create_form.html('').addClass('portfolio-create-success').append( response.success ).show();
				portfolio_loading.hide();
			} else {
				$( '.portfolio-create-error' ).remove();
				portfolio_create_form.prepend('<p class="portfolio-create-error">' + response.error + '</p>' ).show();
				portfolio_loading.hide();
			}
		});
	}

	$( '#submit-portfolio-create' ).on( 'click', handle_click );
}(jQuery,window));