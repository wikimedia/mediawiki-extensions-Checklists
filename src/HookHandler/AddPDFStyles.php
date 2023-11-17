<?php

namespace MediaWiki\Extension\Checklists\HookHandler;

use BlueSpice\UEModulePDF\Hook\BSUEModulePDFBeforeAddingStyleBlocksHook;
use Config;

class AddPDFStyles implements BSUEModulePDFBeforeAddingStyleBlocksHook {

	/** @var Config */
	private $config;

	/**
	 *
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function onBSUEModulePDFBeforeAddingStyleBlocks( array &$template, array &$styleBlocks ): void {
		$base = dirname( __DIR__, 2 ) . '/resources/stylesheets';
		$styleBlocks[ 'ChecklistsListStyles' ] = file_get_contents( "$base/checklist-export.css" );

		$extensionDir = $this->config->get( 'ExtensionDirectory' );

		$imageNames = [ 'unchecked', 'checked' ];
		foreach ( $imageNames as $name ) {
			$img = "$extensionDir/Checklists/resources/images/$name.png";
			if ( !file_exists( $img ) ) {
				$img = "$extensionDir/extensions/Checklists/resources/images/$name.png";
			}

			$template['resources']['IMAGE']["$name.png"] = $img;
		}
	}
}
