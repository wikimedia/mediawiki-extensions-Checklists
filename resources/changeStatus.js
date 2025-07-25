( function ( $ ) {

	function setValue( id, value ) {
		$.ajax( {
			url: mw.util.wikiScript( 'rest' ) + '/checklists/' + id + '/set_status',
			type: 'POST',
			data: JSON.stringify( { value: value } ),
			dataType: 'json',
			contentType: 'application/json; charset=utf-8',
			// eslint-disable-next-line no-unused-vars
			success: function ( data ) {
				window.location.reload();
			}
		} ).fail( () => {
			OO.ui.alert( mw.msg( 'checklists-error-set-status' ) );
		} );
	}

	$( () => {
		// eslint-disable-next-line no-jquery/no-global-selector
		$( '.checklist-li' ).each( function () {
			const $this = $( this );
			if ( mw.user.isAnon() ) {
				$this.attr( 'disabled', true );
				$this.addClass( 'checklist-item-disabled' );
				return;
			}
			$this.attr( 'tabindex', 0 );

			$this.on( 'click keypress', ( e ) => {
				if ( e.offsetX >= 0 ) {
					return;
				}

				if ( e.type === 'keypress' && e.keyCode !== 13 ) {
					return;
				}

				const id = $this.data( 'checklist-item-id' );
				if ( !id ) {
					return;
				}

				const value = $this.data( 'value' ) === 1 ? '' : 'checked';
				if ( !mw.user.options.get( 'checklists-hide-revision-dlg' ) ) {
					require( './bootstrap.js' );
					require( './ui/CheckboxDialog.js' );

					const windowManager = new OO.ui.WindowManager();
					$( document.body ).append( windowManager.$element );

					const dialog = new checklists.ui.CheckboxDialog();
					dialog.on( 'actioncompleted', () => {
						setValue( id, value );
					} );
					windowManager.addWindows( [ dialog ] );
					windowManager.openWindow( dialog );
				} else {
					setValue( id, value );
				}
			} );
		} );
	} );
}( jQuery ) );
