checklists.ve.tools.ChecklistItemTool = function ChecklistItemTool() {
	checklists.ve.tools.ChecklistItemTool.super.apply( this, arguments );
};

OO.inheritClass( checklists.ve.tools.ChecklistItemTool, ve.ui.ListTool );

checklists.ve.tools.ChecklistItemTool.static.name = 'checklists';
checklists.ve.tools.ChecklistItemTool.static.group = 'structure';
checklists.ve.tools.ChecklistItemTool.static.icon = 'checkAll';
checklists.ve.tools.ChecklistItemTool.static.title = mw.message( 'checklists-ve-tool-title' ).plain();
checklists.ve.tools.ChecklistItemTool.static.commandName = 'checklists';

checklists.ve.tools.ChecklistItemTool.prototype.onUpdateState = function ( fragment ) {
	// Parent method
	checklists.ve.tools.ChecklistItemTool.super.prototype.onUpdateState.apply( this, arguments );

	const isMatching = fragment.hasMatchingAncestor( 'checklists' );
	this.setActive( isMatching );
};

/* Registration */

ve.ui.toolFactory.register( checklists.ve.tools.ChecklistItemTool );

/* Command */

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'checklists', 'list', 'toggle',
		{ args: [ null, false, 'checklists' ], supportedSelections: [ 'linear' ] }
	)
);

/* Command help */

// TODO: i18n
ve.ui.commandHelpRegistry.register( 'formatting', 'listCheckList', {
	sequences: [ 'checklists' ], label: 'Check item'
} );

/* Sequence */

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'checklists', 'checklists', [ { type: 'paragraph' }, '[', ']', ' ' ], 3 )
);
