checklists.ve.ce.ChecklistNode = function () {
	// Parent constructor
	checklists.ve.ce.ChecklistNode.super.apply( this, arguments );
	this.$element.addClass( 'checklist' );
};

/* Inheritance */

OO.inheritClass( checklists.ve.ce.ChecklistNode, ve.ce.BranchNode );

/* Static properties */

checklists.ve.ce.ChecklistNode.static.name = 'checklists';

checklists.ve.ce.ChecklistNode.static.tagName = 'ul';

checklists.ve.ce.ChecklistNode.static.removeEmptyLastChildOnEnter = true;

/* Registration */

ve.ce.nodeFactory.register( checklists.ve.ce.ChecklistNode );
