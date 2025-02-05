<?php

namespace MediaWiki\Extension\Checklists\Integration\PDFCreator\PreProcessors;

use MediaWiki\Config\Config;
use MediaWiki\Extension\PDFCreator\IPreProcessor;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;

class ChecklistImage implements IPreProcessor {

	/** @var Config */
	private $config;

	/** @var array */
	private $imageNames = [ 'unchecked', 'checked' ];

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config	) {
		$this->config = $config;
	}

	/**
	 * @param ExportPage[] &$pages
	 * @param array &$images
	 * @param array &$attachments
	 * @param ExportContext $context
	 * @param string $module
	 * @param array $params
	 * @return void
	 */
	public function execute( array &$pages, array &$images, array &$attachments,
		ExportContext $context, string $module = '', $params = []
	): void {
		$extensionDir = $this->config->get( 'ExtensionDirectory' );

		foreach ( $this->imageNames as $name ) {
			$images["$name.png"] = "$extensionDir/Checklists/resources/images/$name.png";
		}
	}
}
