<?php

namespace MediaWiki\Extension\Checklists\Tests;

use DOMDocument;
use MediaWiki\Extension\Checklists\ListItemProvider;
use PHPUnit\Framework\TestCase;

class ListItemProviderTest extends TestCase {

	/**
	 * @param string $prefix
	 * @param string $text
	 * @param string $outputHtml
	 *
	 * @covers \MediaWiki\Extension\Checklists\ListItemProvider::createNewListItem
	 * @dataProvider provideDataCreateNewItem
	 *
	 * @return void
	 */
	public function testcreateNewListItem( string $prefix, string $text, string $outputHtml ) {
		$dom = new DOMDocument();
		$dom->loadHtml( "<html><head><meta charset=\"UTF-8\"><body><div><p>Test</p></div></body></html>" );
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		$document = $body->firstChild;
		$provider = new ListItemProvider();
		$listItem = $provider->createNewListItem( $document, $prefix, $text );

		$expectedDoc = new DOMDocument();
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
				"[] ABC ÄÖÜé",
				// phpcs:ignore Generic.Files.LineLength.TooLong
				"<html><head><meta charset=\"UTF-8\"><body><li class=\"checklist-li \" role=\"checkbox\" aria-checked=\"false\"> ABC ÄÖÜé</li></body></html>"
			],
			[
				"[ x ]",
				"[ x ] My test äöüß",
				// phpcs:ignore Generic.Files.LineLength.TooLong
				"<html><head><meta charset=\"UTF-8\"><body><li class=\"checklist-li checklist-checked\" role=\"checkbox\" checked aria-checked=\"true\"> My test äöüß</li></body></html>"
			],
		];
	}

}
