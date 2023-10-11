<?php

namespace MediaWiki\Extension\Checklists\Hook;

use MediaWiki\Extension\Checklists\ChecklistItem;
use MediaWiki\Extension\Checklists\ChecklistManager;

interface ChecklistsItemsDeletedHook {
	/**
	 * @param ChecklistItem[] $items
	 * @param ChecklistManager $checklistManager
	 *
	 * @return void
	 */
	public function onChecklistsItemsDeleted( array $items, ChecklistManager $checklistManager );
}
