<?php

namespace MediaWiki\Extension\Checklists\Tests;

use MediaWiki\Extension\Checklists\ParsoidExt\ChecklistProcessor;
use MediaWikiIntegrationTestCase;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;

class ChecklistProcessorTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param string $inputPath
	 * @param string $outputPath
	 *
	 * @covers \MediaWiki\Extension\Checklists\ParsoidExt\ChecklistProcessor::wtPostprocess
	 * @dataProvider provideDataPostProcess
	 *
	 * @return void
	 */
	public function testParsoidPostProcess( string $inputPath, string $outputPath ) {
		$mockApi = $this->createMock( ParsoidExtensionAPI::class );

		$inputHtml = file_get_contents( __DIR__ . $inputPath );
		$doc = ContentUtils::createAndLoadDocument( $inputHtml );
		$body = DOMCompat::getBody( $doc );
		$processor = new ChecklistProcessor();
		$processor->wtPostprocess( $mockApi, $body, [] );

		$expectedDoc = new Document();
		$expectedDoc->loadHTMLFile( __DIR__ . $outputPath );
		$expectedBody = $expectedDoc->getElementsByTagName( 'body' );

		$body = $this->normalize( $doc->saveHTML( $body ) );
		$expectedBody = $this->normalize( $expectedDoc->saveHTML( $expectedBody->item( 0 ) ) );
		$this->assertSame( $expectedBody, $body );
	}

	/**
	 * @param string $inputPath
	 * @param string $outputPath
	 *
	 * @covers \MediaWiki\Extension\Checklists\ParsoidExt\ChecklistProcessor::htmlPreprocess
	 * @dataProvider provideDataPreProcess
	 *
	 * @return void
	 */
	public function testPreprocess( string $inputPath, string $outputPath ) {
		$mockApi = $this->createMock( ParsoidExtensionAPI::class );

		$inputHtml = file_get_contents( __DIR__ . $inputPath );
		$doc = ContentUtils::createAndLoadDocument( $inputHtml );
		$body = DOMCompat::getBody( $doc );
		$processor = new ChecklistProcessor();
		$processor->htmlPreprocess( $mockApi, $body );

		$expectedDoc = new Document();
		$expectedDoc->loadHTMLFile( __DIR__ . $outputPath );
		$expectedBody = $expectedDoc->getElementsByTagName( 'body' );

		$body = $this->normalize( $doc->saveHTML( $body ) );
		$expectedBody = $this->normalize( $expectedDoc->saveHTML( $expectedBody->item( 0 ) ) );
		$this->assertSame( $expectedBody, $body );
	}

	/**
	 * @return array[]
	 */
	public function provideDataPostProcess() {
		return [
			[
				"/../data/parsoid-html-input.html",
				"/../data/parsoid-html-output.html"
			],
			[
				"/../data/parsoid-html-input-table.html",
				"/../data/parsoid-html-output-table.html"
			]
		];
	}

	/**
	 * @return array[]
	 */
	public function provideDataPreProcess() {
		return [
			[
				"/../data/parsoid-html-output.html",
				"/../data/parsoid-html-input.html"
			],
			[
				"/../data/parsoid-html-output-table.html",
				"/../data/parsoid-html-input-table.html"
			]
		];
	}

	/**
	 * @param string $html
	 * @return string
	 */
	private function normalize( string $html ): string {
		// remove all newlines
		$html = preg_replace( '/\\n/', '', $html );
		$html = preg_replace( '/\n/', '', $html );
		// strip data-object-id
		$html = preg_replace( '/\sdata-object-id="\d*?"/', '', $html );
		// remove all 2+ spaces
		return preg_replace( '/\s+/', '', $html );
	}
}
