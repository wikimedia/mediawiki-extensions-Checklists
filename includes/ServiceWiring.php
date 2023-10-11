<?php

use MediaWiki\Extension\Checklists\ChecklistManager;
use MediaWiki\Extension\Checklists\ChecklistParser;
use MediaWiki\Extension\Checklists\ChecklistStore;
use MediaWiki\MediaWikiServices;

return [
	'ChecklistManager' => static function ( MediaWikiServices $services ) {
		return new ChecklistManager(
			new ChecklistParser(),
			new ChecklistStore(
				$services->getDBLoadBalancer(),
				$services->getUserFactory(),
				$services->getTitleFactory()
			),
			$services->getRevisionStore(),
			$services->getPageUpdaterFactory()
		);
	},
];
