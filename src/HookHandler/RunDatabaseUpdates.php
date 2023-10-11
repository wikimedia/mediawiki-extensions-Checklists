<?php

namespace MediaWiki\Extension\Checklists\HookHandler;

use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class RunDatabaseUpdates implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable(
			'checklist_items',
			__DIR__ . '/../../db/checklist_items.sql'
		);
	}
}
