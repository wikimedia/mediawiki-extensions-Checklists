<?php

namespace MediaWiki\Extension\Checklists\ContentDroplets;

use MediaWiki\Extension\ContentDroplets\Droplet\GenericDroplet;
use MediaWiki\Message\Message;

class ChecklistDroplet extends GenericDroplet {

	/**
	 */
	public function __construct() {
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return Message::newFromKey( 'checklists-droplet-checklist-title' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): Message {
		return Message::newFromKey( 'checklists-droplet-checklist-description' );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'droplet-tasklist';
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModules(): array {
		return [ 'ext.checklists.ve.checkList' ];
	}

	/**
	 * @return array
	 */
	public function getCategories(): array {
		return [ 'content', 'data', 'featured' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getContent(): string {
		return '[] Test';
	}

	/**
	 * @return string|null
	 */
	public function getVeCommand(): ?string {
		return 'checklists';
	}

}
