<?php

namespace MediaWiki\Extension\Checklists\Tests;

use DOMDocument;
use MediaWiki\Extension\Checklists\ParsoidListItemProvider;
use PHPUnit\Framework\TestCase;

class ParsoidListItemProviderTest extends TestCase {

	/**
	 * @param string $prefix
	 * @param string $text
	 * @param string $outputHtml
	 *
	 * @covers \MediaWiki\Extension\Checklists\ParsoidListItemProvider::createNewListItem
	 * @dataProvider provideDataCreateNewItem
	 *
	 * @return void
	 */
	public function testcreateNewListItem( string $prefix, string $text, string $outputHtml ) {
		$dom = new DOMDocument();
		$dom->loadHtml( "<html><head><meta charset=\"UTF-8\"></head><body><div><p>Test</p></div></body></html>" );
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		$document = $body->firstChild;
		$provider = new ParsoidListItemProvider();
		$listItem = $provider->createNewListItem( $document, $prefix, $text );

		$expectedDoc = new DOMDocument( '1.0', 'UTF-8' );
		$expectedDoc->loadHTML( $outputHtml );
		$expectedBody = $expectedDoc->getElementsByTagName( 'body' );

		$this->assertEquals( $dom->saveHtml( $listItem ),
			$expectedDoc->saveHtml( $expectedBody->item( 0 )->firstChild ) );
	}

	/**
	 * @return array[]
	 */
	public function provideDataCreateNewItem() {
		return [
			[
				"[]",
				"[] ABC Task with ÄÖÜ",
				// phpcs:ignore Generic.Files.LineLength.TooLong
				"<html><head><meta charset=\"UTF-8\"><body><li class=\"checklist-li \" rel=\"mw:checklist\"> ABC Task with ÄÖÜ</li></body></html>"
			],
			[
				"[ x ]",
				"[ x ] My test äöüß",
				// phpcs:ignore Generic.Files.LineLength.TooLong
				"<html><head><meta charset=\"UTF-8\"><body><li class=\"checklist-li checklist-checked\" checked rel=\"mw:checklist\"> My test äöüß</li>" .
				"</body></html>"
			]
		];
	}

}
