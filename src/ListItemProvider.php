<?php

namespace MediaWiki\Extension\Checklists;

class ListItemProvider implements IListItemProvider {

	/**
	 * @inheritDoc
	 */
	public function createNewListItem( $document, $prefix, $text ) {
		$checklistDescriptionText = substr( $text, strlen( $prefix ), strlen( $text ) );
		$checkedClass = $this->getClassForStatus( $prefix );
		$listItemEl = $document->ownerDocument->createElement( 'li', $checklistDescriptionText );
		$classes = 'checklist-li ' . $checkedClass;
		$listItemEl->setAttribute( 'class', $classes );
		if ( $checkedClass === 'checklist-checked' ) {
			$listItemEl->setAttribute( 'checked', 'checked' );
		}
		return $listItemEl;
	}

	/**
	 *
	 * @param string $match
	 * @return string
	 */
	private function getClassForStatus( $match ) {
		$content = substr( $match, 1, -1 );
		$content = trim( $content );
		if ( empty( $content ) ) {
			return '';
		}
		if ( $content === 'x' ) {
			return 'checklist-checked';
		}
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function createChecklistElement( $document ) {
		$checklistEl = $document->ownerDocument->createElement( 'ul' );
		$checklistEl->setAttribute( 'class', 'checklist' );
		return $checklistEl;
	}
}
