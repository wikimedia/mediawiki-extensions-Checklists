<?php

namespace MediaWiki\Extension\Checklists\Tests;

use MediaWiki\Extension\Checklists\ListItemProvider;
use MediaWiki\Extension\Checklists\ParsoidListItemProvider;
use MediaWiki\Extension\Checklists\WikiTextPostProcessor;
use MediaWikiIntegrationTestCase;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;

class WikiTextPostProcessorTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param string $inputPath
	 * @param string $outputPath
	 * @param string $mode
	 *
	 * @covers \MediaWiki\Extension\Checklists\WikiTextPostProcessor::processDOM
	 * @dataProvider provideDataProcessDOM
	 *
	 * @return void
	 */
	public function testprocessDOM( string $inputPath, string $outputPath, string $mode ) {
		$inputHtml = file_get_contents( __DIR__ . $inputPath );
		$siteConfig = $this->getServiceContainer()->getParsoidSiteConfig();
		$doc = ContentUtils::createAndLoadDocument( $inputHtml, [], $siteConfig );
		$body = DOMCompat::getBody( $doc );
		$listItemProvider = new ListItemProvider();
		if ( $mode === 'parsoid' ) {
			$listItemProvider = new ParsoidListItemProvider();
		}
		$processor = new WikiTextPostProcessor( $listItemProvider );
		$processor->processDOM( $body );

		$expectedHtml = file_get_contents( __DIR__ . $outputPath );
		$expectedDoc = ContentUtils::createAndLoadDocument( $expectedHtml, [], $siteConfig );
		$expectedBody = DOMCompat::getBody( $expectedDoc );

		$actual = $this->normalize( html_entity_decode( $doc->saveHTML( $body ), ENT_QUOTES ) );
		$expected = $this->normalize(
			html_entity_decode( $expectedDoc->saveHTML( $expectedBody ), ENT_QUOTES )
		);
		$this->assertSame( $expected, $actual );
	}

	/**
	 * @return array[]
	 */
	public function provideDataProcessDOM() {
		return [
			[
				"/../data/parsoid-html-input.html",
				"/../data/parsoid-html-output.html",
				'parsoid'
			],
			[
				"/../data/html-input.html",
				"/../data/html-output.html",
				'html'
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
		// normalize boolean checked attribute
		$html = preg_replace( '/checked="(checked|)"/', 'checked', $html );
		// remove all 2+ spaces
		return preg_replace( '/\s+/', '', $html );
	}
}
