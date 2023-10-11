<?php

namespace MediaWiki\Extension\Checklists\Tests;

use MediaWiki\Extension\Checklists\ChecklistParser;
use MediaWiki\Page\PageIdentity;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase {

	/**
	 * @param string $text
	 * @param array $expected
	 *
	 * @covers \MediaWiki\Extension\Checklists\Parser::parse
	 * @dataProvider provideData
	 *
	 * @return void
	 */
	public function testParse( $text, array $expected ) {
		$parser = new ChecklistParser();
		$checklist = $parser->parse( $text, $this->getPageMock( 1 ) );
		$this->assertEquals( $expected, array_values( $checklist ) );
	}

	/**
	 * @covers \MediaWiki\Extension\Checklists\Parser::deduplicate
	 */
	public function testDeduplication() {
		$textWithDuplicates = $this->getText( 'checklist_with_duplicates' );
		$expected = $this->getText( 'checklist_with_fixed_duplicates' );
		$parser = new ChecklistParser();
		$fixed = $parser->deduplicate( $textWithDuplicates, $this->getPageMock( 1 ) );
		$this->assertSame( $expected, $fixed );
	}

	/**
	 * @covers \MediaWiki\Extension\Checklists\Parser::parse
	 */
	public function testIDResolution() {
		$text = $this->getText( 'checklist_common_id' );
		$parser = new ChecklistParser();
		$items = $parser->parse( $text, $this->getPageMock( 1 ) );
		$ids = array_map( static function ( $item ) {
			return $item['id'];
		}, $items );
		$this->assertCount( 2, array_unique( $ids ) );
	}

	/**
	 * @covers \MediaWiki\Extension\Checklists\Parser::setItemCheckStatus
	 */
	public function testSetItemCheckStatus() {
		$parser = new ChecklistParser();
		$titleMock = $this->getPageMock( 1 );
		$originalInput = $this->getText( 'checklist' );
		$items = $parser->parse( $originalInput, $titleMock );
		foreach ( $items as $item ) {
			if ( $item['id'] === '89eab160d5b53808cea91533d6b012c4' ) {
				$this->assertFalse( $item['value'] );
			}
			if ( $item['id'] === 'b0ef5d246eb31c989b4951a1f317cdc0' ) {
				$this->assertTrue( $item['value'] );
			}
			if ( $item['id'] === '9f575ecc1872cd6c9faf97889b0e1b60' ) {
				$this->assertTrue( $item['value'] );
			}
			if ( $item['id'] === '4caa55b5cc5308d2d18e78f364ef2987' ) {
				$this->assertFalse( $item['value'] );
			}
		}

		$alteredInput = $originalInput;
		$alteredInput = $parser->setItemValue(
			$alteredInput, '89eab160d5b53808cea91533d6b012c4', 'checked', $titleMock
		);
		$alteredInput = $parser->setItemValue(
			$alteredInput, 'b0ef5d246eb31c989b4951a1f317cdc0', '0', $titleMock
		);
		$alteredInput = $parser->setItemValue(
			$alteredInput, '4caa55b5cc5308d2d18e78f364ef2987', '1', $titleMock
		);
		$alteredItems = $parser->parse( $alteredInput, $titleMock );
		foreach ( $alteredItems as $item ) {
			if ( $item['id'] === '89eab160d5b53808cea91533d6b012c4' ) {
				$this->assertTrue( $item['value'] );
			}
			if ( $item['id'] === 'b0ef5d246eb31c989b4951a1f317cdc0' ) {
				$this->assertFalse( $item['value'] );
			}
			if ( $item['id'] === '4caa55b5cc5308d2d18e78f364ef2987' ) {
				$this->assertTrue( $item['value'] );
			}
			if ( $item['id'] === '9f575ecc1872cd6c9faf97889b0e1b60' ) {
				$this->assertTrue( $item['value'] );
			}
		}
	}

	/**
	 * @return array[]
	 */
	public function provideData() {
		return [
			[
				"[] foo Bar ",
				[
					[
						'id' => md5( 'abfoor#1' ),
						'text' => "foo Bar",
						'value' => false,
						'type' => 'check'
					]
				]
			],
			[
				"[x] foo Bar",
				[
					[
						'id' => md5( 'abfoor#1' ),
						'text' => "foo Bar",
						'value' => true,
						'type' => 'check'
					]
				]
			],
			[
				$this->getText( 'checklist' ),
				$this->getChecklistData( 'checklist_data' )
			],
		];
	}

	/**
	 * @param string $string
	 *
	 * @return false|string
	 */
	private function getText( string $string ) {
		return file_get_contents( __DIR__ . "/../data/$string.txt" );
	}

	/**
	 * @param string $string
	 *
	 * @return mixed
	 */
	private function getChecklistData( string $string ) {
		return json_decode( file_get_contents( __DIR__ . "/../data/$string.json" ), 1 );
	}

	/**
	 * @param int $id
	 *
	 * @return PageIdentity&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getPageMock( int $id ) {
		$mock = $this->getMockBuilder( PageIdentity::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->method( 'getId' )->willReturn( $id );
		return $mock;
	}
}
