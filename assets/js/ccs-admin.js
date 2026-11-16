/**
 * Now let's get started.
 */
jQuery( document ).ready( function($) {

	// Add a confirm to the row action move.
	$( 'span.ccs-add-cold' ).on( 'click', '.ccs-cold-store-row-link', function( event ) {

		// Stop anything else happening if we don't confirm.
		if ( ! confirm( ccsLocalArgs.confirmTo ) ) {
			event.preventDefault();
		}

		// And be done.
		return;
	});

	// Add a confirm to the row action restore.
	$( 'span.ccs-back-cold' ).on( 'click', '.ccs-cold-restore-row-link', function( event ) {

		// Stop anything else happening if we don't confirm.
		if ( ! confirm( ccsLocalArgs.confirmFrom ) ) {
			event.preventDefault();
		}

		// And be done.
		return;
	});

//********************************************************
// You're still here? It's over. Go home.
//********************************************************
});
