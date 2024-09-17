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

		$this->assertXmlStringEqualsXmlString(
			$body,
			$expectedBody->item( 0 )->sa
		);
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

		$this->assertEqualXMLStructure( $body, $expectedBody->item( 0 ) );
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
}
