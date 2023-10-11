<?php

namespace MediaWiki\Extension\Checklists\ParsoidExt;

use Wikimedia\Parsoid\Ext\ExtensionModule;

class ChecklistMarkup implements ExtensionModule {

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'Checklists',
			'domProcessors' => [
				ChecklistProcessor::class
			]
		];
	}
}
