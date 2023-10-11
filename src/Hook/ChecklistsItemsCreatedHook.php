<?php

namespace MediaWiki\Extension\Checklists\Hook;

use MediaWiki\Extension\Checklists\ChecklistItem;
use MediaWiki\Extension\Checklists\ChecklistManager;

interface ChecklistsItemsCreatedHook {
	/**
	 * @param ChecklistItem[] $items
	 * @param ChecklistManager $checklistManager
	 *
	 * @return void
	 */
	public function onChecklistsItemsCreated( array $items, ChecklistManager $checklistManager );
}
