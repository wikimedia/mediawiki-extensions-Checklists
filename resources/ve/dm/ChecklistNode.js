/*!
 * VisualEditor DataModel CheckListNode class.
 *
 * @copyright 2011-2020 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * DataModel list node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
checklists.ve.dm.CheckListNode = function () {
	// Parent constructor
	checklists.ve.dm.CheckListNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( checklists.ve.dm.CheckListNode, ve.dm.BranchNode );

/* Static Properties */

checklists.ve.dm.CheckListNode.static.name = 'checklists';

checklists.ve.dm.CheckListNode.static.childNodeTypes = [ 'checklistsItem' ];

checklists.ve.dm.CheckListNode.static.matchTagNames = [ 'ul' ];

checklists.ve.dm.CheckListNode.static.matchRdfaTypes = [ 'mw:checklist' ];

checklists.ve.dm.CheckListNode.static.isDiffedAsList = true;

/**
 * Creates a list item element
 *
 * @return {Object} Element data
 */
checklists.ve.dm.CheckListNode.static.createItem = function () {
	return { type: 'checklistsItem', attributes: { checked: false } };
};

// eslint-disable-next-line no-unused-vars
checklists.ve.dm.CheckListNode.static.toDataElement = function ( domElements ) {
	return { type: this.name, attributes: { checked: false } };
};

checklists.ve.dm.CheckListNode.static.toDomElements = function ( dataElement, doc ) {
	var list = doc.createElement( 'ul' );
	list.setAttribute( 'rel', 'mw:checklist' );
	list.setAttribute( 'class', 'checklist' );
	return [ list ];
};

/* Methods */

checklists.ve.dm.CheckListNode.prototype.canHaveSlugAfter = function () {
	// A paragraph can be added after a list by pressing enter in an empty list item
	return false;
};

/* Registration */

ve.dm.modelRegistry.register( checklists.ve.dm.CheckListNode );
