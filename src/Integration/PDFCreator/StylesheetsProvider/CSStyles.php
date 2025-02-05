<?php

namespace MediaWiki\Extension\Checklists\Integration\PDFCreator\StylesheetsProvider;

use MediaWiki\Extension\PDFCreator\IStylesheetsProvider;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;

class CSStyles implements IStylesheetsProvider {

	/**
	 * @param string $module
	 * @param ExportContext $context
	 * @return array
	 */
	public function execute( string $module, ExportContext $context ): array {
		$base = dirname( __DIR__, 4 ) . '/resources/stylesheets';

		return [
			'checklist-export.css' => "$base/checklist-export.css"
		];
	}
}
