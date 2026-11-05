<?php

namespace MediaWiki\Extension\Checklists\Tests;

use MediaWiki\Extension\Checklists\ChecklistItemAnnotator;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Checklists\ChecklistItemAnnotator
 */
class ChecklistItemAnnotatorTest extends MediaWikiUnitTestCase {

	private const HTML = '<div><p>Intro</p><ul class="checklist">'
		. '<li class="checklist-li">One</li>'
		. '<li class="checklist-li" data-checklist-item-id="nested" data-value="1">Nested</li>'
		. '<li class="checklist-li checklist-checked">Two</li>'
		. '</ul><ul><li>Regular list</li></ul></div>';

	/**
	 * @return array
	 */
	private function getItems(): array {
		return [
			'id1' => [ 'id' => 'id1', 'value' => false ],
			'id2' => [ 'id' => 'id2', 'value' => true ],
		];
	}

	public function testAnnotateLegacyDom() {
		$document = new \DOMDocument();
		$document->loadHTML( self::HTML, LIBXML_NOERROR );
		$this->assertAnnotated( $document );
	}

	public function testAnnotateHtml5Dom() {
		if ( !class_exists( '\Dom\HTMLDocument' ) ) {
			$this->markTestSkipped( '\Dom\HTMLDocument requires PHP 8.4+' );
		}
		// \Dom\Element::$nodeName is upper-case for HTML elements
		$document = \Dom\HTMLDocument::createFromString( self::HTML, LIBXML_NOERROR );
		$this->assertAnnotated( $document );
	}

	/**
	 * @param \DOMDocument|\Dom\Document $document
	 * @return void
	 */
	private function assertAnnotated( $document ): void {
		$annotator = new ChecklistItemAnnotator();
		$elements = $annotator->getChecklistItemElements( $document );
		$this->assertCount( 3, $elements );

		$annotator->annotate( $elements, $this->getItems() );
		$this->assertSame( 'id1', $elements[0]->getAttribute( 'data-checklist-item-id' ) );
		$this->assertSame( '0', $elements[0]->getAttribute( 'data-value' ) );
		$this->assertSame( 'nested', $elements[1]->getAttribute( 'data-checklist-item-id' ) );
		$this->assertSame( 'id2', $elements[2]->getAttribute( 'data-checklist-item-id' ) );
		$this->assertSame( '1', $elements[2]->getAttribute( 'data-value' ) );
	}
}
