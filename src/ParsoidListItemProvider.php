<?php

namespace MediaWiki\Extension\Checklists;

class ParsoidListItemProvider extends ListItemProvider {

	/**
	 * @inheritDoc
	 */
	public function createNewListItem( $document, $prefix, $text ) {
		$listItemEl = parent::createNewListItem( $document, $prefix, $text );
		$listItemEl->setAttribute( 'rel', 'mw:checklist' );
		return $listItemEl;
	}

	/**
	 * @inheritDoc
	 */
	public function createChecklistElement( $document ) {
		$checkListItemEl = parent::createChecklistElement( $document );
		$checkListItemEl->setAttribute( 'rel', 'mw:checklist' );
		return $checkListItemEl;
	}
}
