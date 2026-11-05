<?php

namespace MediaWiki\Extension\Checklists\ParsoidExt;

use MediaWiki\Extension\Checklists\ChecklistItemAnnotator;
use MediaWiki\Extension\Checklists\ChecklistParser;
use MediaWiki\Extension\Checklists\HookHandler\ModifyOutput;
use MediaWiki\Extension\Checklists\ParsoidListItemProvider;
use MediaWiki\Extension\Checklists\WikiTextPostProcessor;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use Wikimedia\Parsoid\DOM\Element as DOMElement;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Ext\DOMProcessor;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

class ChecklistProcessor extends DOMProcessor {

	/**
	 * Marks content that is only meant for display and must not be serialized back to wikitext
	 */
	private const NOTICE_CLASS = 'checklists-not-allowed-notice';

	/**
	 * Post-process DOM in the wt2html direction.
	 *
	 * The legacy parser hooks (`ParserBeforeInternalParse`, `ParserAfterTidy`) are not
	 * invoked by Parsoid, so RL modules and item IDs need to be set up here.
	 *
	 * @param ParsoidExtensionAPI $extApi
	 * @param DocumentFragment|Element $root The root of the tree to process
	 * @param array $options
	 */
	public function wtPostprocess(
		ParsoidExtensionAPI $extApi,
		Node $root,
		array $options
	): void {
		$wikiTextPostprocessor = new WikiTextPostProcessor( new ParsoidListItemProvider() );
		$wikiTextPostprocessor->processDOM( $root );

		$annotator = new ChecklistItemAnnotator();
		$checklistElements = $annotator->getChecklistItemElements( $root );
		if ( !$checklistElements ) {
			return;
		}
		$metadata = $extApi->getMetadata();
		$metadata->addModuleStyles( [ 'ext.checklists.styles' ] );

		$pageConfig = $extApi->getPageConfig();
		if ( $pageConfig->getContentModel() !== CONTENT_MODEL_WIKITEXT ) {
			return;
		}
		$linkTarget = $pageConfig->getLinkTarget();
		if ( in_array( $linkTarget->getNamespace(), ModifyOutput::UNSUPPORTED_NAMESPACES ) ) {
			$this->addUnsupportedPageNotice( $extApi, $root );
			return;
		}
		$text = $pageConfig->getRevisionContent()?->getContent( SlotRecord::MAIN ) ?? '';
		if ( $text === '' ) {
			return;
		}
		$items = ( new ChecklistParser() )->parse( $text, Title::newFromLinkTarget( $linkTarget ), true );
		$annotator->annotate( $checklistElements, $items );
		$metadata->addModules( [ 'ext.checklists.view' ] );
	}

	/**
	 * @param ParsoidExtensionAPI $extApi
	 * @param Node $root
	 * @return void
	 */
	private function addUnsupportedPageNotice( ParsoidExtensionAPI $extApi, Node $root ): void {
		$doc = $root->ownerDocument;
		$notice = $doc->createElement( 'div' );
		$notice->setAttribute(
			'class', self::NOTICE_CLASS . ' cdx-message cdx-message--block cdx-message--error'
		);
		$icon = $doc->createElement( 'span' );
		$icon->setAttribute( 'class', 'cdx-message__icon' );
		$notice->appendChild( $icon );
		$content = $doc->createElement( 'div' );
		$content->setAttribute( 'class', 'cdx-message__content' );
		$content->appendChild( $extApi->createInterfaceI18nFragment( 'checklists-not-allowed', [] ) );
		$notice->appendChild( $content );
		$root->insertBefore( $notice, $root->firstChild );
		$extApi->getMetadata()->addModuleStyles( [ 'mediawiki.codex.messagebox.styles' ] );
	}

	/**
	 * Pre-process DOM in the html2wt direction.
	 *
	 * @param ParsoidExtensionAPI $extApi
	 * @param Element $root
	 */
	public function htmlPreprocess(
		ParsoidExtensionAPI $extApi,
		DOMElement $root
	): void {
		$notices = [];
		foreach ( $root->getElementsByTagName( 'div' ) as $node ) {
			if ( str_contains( $node->getAttribute( 'class' ) ?? '', self::NOTICE_CLASS ) ) {
				$notices[] = $node;
			}
		}
		foreach ( $notices as $notice ) {
			$notice->parentNode->removeChild( $notice );
		}

		$nodes = $root->getElementsByTagName( 'ul' );
		$checklistNodes = [];
		foreach ( $nodes as $node ) {
			if ( $node->getAttribute( 'class' ) === 'checklist' ) {
				$checklistNodes[] = $node;
			}
		}

		foreach ( $checklistNodes as $checklistNode ) {
			$childElement = $this->getChildElementsFromNode( $checklistNode );

			$total = count( $childElement );
			$count = 0;
			$newParagraphElement = $root->ownerDocument->createElement( 'p' );
			$elements = [];
			foreach ( $childElement as $element ) {
				$count++;
				$prefix = '[] ';
				if ( isset( $element[1] ) ) {
					$prefix = '[x] ';
				}

				$childNodes = $element[0]->childNodes;
				$textElement = $root->ownerDocument->createTextNode( $prefix );

				$elements[] = $textElement;

				foreach ( $childNodes as $child ) {
					$elements[] = $child;
				}

				if ( $count != $total ) {
					$newLineText = $root->ownerDocument->createTextNode( " \n" );
					$elements[] = $newLineText;
				}
			}

			$newParagraphElement = $root->ownerDocument->createElement( 'p' );

			$newParagraphElement = $this->appendChildren( $newParagraphElement, $elements );
			$checklistNode->parentNode->replaceChild( $newParagraphElement, $checklistNode );
		}
	}

	/**
	 * Add elements from a DomNodeList as children to a DomNode element
	 *
	 * @param DomNode $element
	 * @param DomNodeList $children
	 * @return DomNode
	 */
	private function appendChildren( $element, $children ) {
		foreach ( $children as $child ) {
			if ( !$child ) {
				continue;
			}
			$element->appendChild( $child );
		}
		return $element;
	}

	/**
	 * @param DomNode $element
	 * @return array
	 */
	private function getChildElementsFromNode( $element ) {
		$childElement = [];
		$elements = $element->childNodes;
		foreach ( $elements as $child ) {
			if ( $child->nodeType === XML_TEXT_NODE ) {
				continue;
			}
			$classes = $child->getAttribute( 'class' );
			if ( strpos( $classes, 'checklist-checked' ) !== false ) {
				$childElement[] = [ $child, 'x' ];
			} else {
				$childElement[] = [ $child ];
			}
		}
		return $childElement;
	}
}
