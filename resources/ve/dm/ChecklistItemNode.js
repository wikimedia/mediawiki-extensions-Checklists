checklists.ve.dm.ChecklistItemNode = function () {
	// Parent constructor
	checklists.ve.dm.ChecklistItemNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( checklists.ve.dm.ChecklistItemNode, ve.dm.BranchNode );

/* Static members */

checklists.ve.dm.ChecklistItemNode.static.name = 'checklistsItem';

checklists.ve.dm.ChecklistItemNode.static.parentNodeTypes = [ 'checklists' ];

checklists.ve.dm.ChecklistItemNode.static.matchTagNames = [ 'li' ];

checklists.ve.dm.ChecklistItemNode.static.matchRdfaTypes = [ 'mw:checklist' ];

checklists.ve.dm.ChecklistItemNode.static.toDataElement = function ( domElements ) {
	var checked = false;
	if ( domElements[ 0 ].hasAttribute( 'checked' ) &&
		domElements[ 0 ].getAttribute( 'checked' ) === 'checked' ) {
		checked = 'checked';
	}
	return { type: this.name, attributes: { checked: checked } };
};

checklists.ve.dm.ChecklistItemNode.static.toDomElements = function ( dataElement, doc ) {
	var listItem = doc.createElement( 'li' );
	listItem.setAttribute( 'rel', 'mw:checklist' );
	listItem.setAttribute( 'class', 'checklist-li' );
	if ( dataElement.attributes.checked === true || dataElement.attributes.checked === 'checked' ) {
		listItem.setAttribute( 'checked', 'checked' );
		listItem.removeAttribute( 'class' );
		listItem.setAttribute( 'class', 'checklist-li checklist-checked' );
	}
	return [ listItem ];
};

checklists.ve.dm.ChecklistItemNode.static.cloneElement = function () {
	var clone = checklists.ve.dm.ChecklistItemNode.super.static.cloneElement.apply(
		this, arguments );
	clone.attributes.checked = false;
	return clone;
};

/* Registration */

ve.dm.modelRegistry.register( checklists.ve.dm.ChecklistItemNode );
