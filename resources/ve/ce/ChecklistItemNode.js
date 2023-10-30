checklists.ve.ce.ChecklistItemNode = function () {
	// Parent constructor
	checklists.ve.ce.ChecklistItemNode.super.apply( this, arguments );
	this.$element.addClass( 'checklist-li' );

	this.model.connect( this, { attributeChange: 'onAttributeChange' } );
	this.$element.on( 'click', this.onClick.bind( this ) );

	this.updateChecked();

};

/* Inheritance */

OO.inheritClass( checklists.ve.ce.ChecklistItemNode, ve.ce.BranchNode );

/* Static properties */

checklists.ve.ce.ChecklistItemNode.static.name = 'checklistsItem';

checklists.ve.ce.ChecklistItemNode.static.tagName = 'li';

// If body is empty, tag does not render anything
checklists.ve.ce.ChecklistItemNode.static.splitOnEnter = true;

checklists.ve.ce.ChecklistItemNode.prototype.onClick = function ( e ) {
	if ( e.target === this.$element[ 0 ] ) {
		var isChecked = this.getModel().getAttribute( 'checked' );
		this.$element.toggleClass( 'checklist-checked', !isChecked );
		var fragment =
			this.getRoot().getSurface().getModel().getLinearFragment( this.getOuterRange(), true );
		fragment.changeAttributes( { checked: !isChecked } );
	}
};

/**
 * @param {string} key Attribute key
 * @param {string} from Old value
 * @param {string} to New value
 * @return {void}
 */
checklists.ve.ce.ChecklistItemNode.prototype.onAttributeChange = function ( key ) {
	if ( key === 'checked' ) {
		this.updateChecked();
	}
};

checklists.ve.ce.ChecklistItemNode.prototype.updateChecked = function () {
	var isChecked = this.getModel().getAttribute( 'checked' );
	this.$element.toggleClass( 'checklist-checked', !!isChecked );
	if ( isChecked === 'checked' ) {
		this.getModel().element.attributes.checked = !!isChecked;
	}
};

/* Registration */

ve.ce.nodeFactory.register( checklists.ve.ce.ChecklistItemNode );
