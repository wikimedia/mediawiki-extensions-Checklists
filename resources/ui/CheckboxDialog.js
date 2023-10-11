checklists.ui.CheckboxDialog = function ( config ) {
	config = config || {};
	config.size = 'medium';
	checklists.ui.CheckboxDialog.super.call( this, config );
}

OO.inheritClass( checklists.ui.CheckboxDialog, OO.ui.ProcessDialog );

checklists.ui.CheckboxDialog.static.name = 'CheckboxDialog';
checklists.ui.CheckboxDialog.static.title = mw.msg( 'checklists-checkbox-dlg-title' );
checklists.ui.CheckboxDialog.static.actions = [
	{
		action: 'save',
		label: mw.msg( 'checklists-checkbox-dlg-action-done' ),
		flags: [ 'primary', 'progressive' ]
	},
	{
		label: mw.msg( 'checklists-checkbox-dlg-action-cancel' ),
		flags: 'safe'
	}
];

checklists.ui.CheckboxDialog.prototype.initialize = function () {
	checklists.ui.CheckboxDialog.super.prototype.initialize.apply( this, arguments );

	this.content = new OO.ui.PanelLayout( { padded: true, expanded: false } );
	var label = new OO.ui.LabelWidget( {
		label: mw.msg( 'checklists-confirm-change-status' )
	} );
	this.content.$element.append( label.$element );

	this.checkbox = new OO.ui.CheckboxInputWidget();
	var field = new OO.ui.FieldLayout( this.checkbox, {
		label: mw.msg( 'checklists-checkbox-dlg-hide-revision-label' ),
		align: 'inline'
	} );
	this.content.$element.append( field.$element );
	this.$body.append( this.content.$element );
};

checklists.ui.CheckboxDialog.prototype.getActionProcess = function ( action ) {
	if ( action ) {
		const setPref = this.setUserPreference();
		setPref.next( this.onActionDone, this );
		return setPref;
	}
	return checklists.ui.CheckboxDialog.super.prototype.getActionProcess.call( this, action );
};

checklists.ui.CheckboxDialog.prototype.onActionDone = function ( action ) {
	var args = [ 'actioncompleted' ];
	this.emit.apply( this, args );
	this.close( { action: action } );
};

checklists.ui.CheckboxDialog.prototype.setUserPreference = function () {
	const dfd = new $.Deferred();
	var checked = this.checkbox.isSelected();
	if ( checked ) {
		if ( !mw.user.isAnon() ) {
			mw.loader.using( 'mediawiki.api' ).done( function () {
				mw.user.options.set( 'checklists-hide-revision-dlg', '1' );
				new mw.Api().saveOption( 'checklists-hide-revision-dlg', '1' );
				dfd.resolve();
			} ).fail( function () {
				dfd.reject.apply( this, arguments );
			} );
		}
	} else {
		dfd.resolve();
	}
	return new OO.ui.Process( dfd.promise(), this );
};
