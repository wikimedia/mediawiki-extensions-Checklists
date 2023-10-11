<?php

namespace MediaWiki\Extension\Checklists\Hook;

use MediaWiki\Extension\Checklists\ChecklistItem;
use MediaWiki\Extension\Checklists\ChecklistManager;

interface ChecklistsItemsUpdatedHook {
	/**
	 * @param ChecklistItem[] $items
	 * @param ChecklistManager $checklistManager
	 *
	 * @return void
	 */
	public function onChecklistsItemsUpdated( array $items, ChecklistManager $checklistManager );
}
