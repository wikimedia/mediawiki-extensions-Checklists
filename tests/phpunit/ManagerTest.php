<?php

namespace MediaWiki\Extension\Checklists\Tests;

use MediaWiki\Content\WikitextContent;
use MediaWiki\Extension\Checklists\ChecklistItem;
use MediaWiki\Extension\Checklists\ChecklistManager;
use MediaWiki\Extension\Checklists\ChecklistParser;
use MediaWiki\Extension\Checklists\ChecklistStore;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Storage\PageUpdaterFactory;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\Checklists\ChecklistManager::persistsOnSave
	 * @return void
	 */
	public function testPersistOnSave() {
		$manager = $this->getManager();
		$revisionMock = $this->createMock( RevisionRecord::class );
		$revisionMock->method( 'getContent' )->willReturn( new WikitextContent( '' ) );
		$revisionMock->method( 'getPage' )->willReturn(
			$this->createMock( PageIdentity::class )
		);
		$manager->persistsOnSave( $revisionMock, $this->createMock( User::class ) );
	}

	/**
	 * @covers \MediaWiki\Extension\Checklists\ChecklistManager::getParser
	 * @covers \MediaWiki\Extension\Checklists\ChecklistManager::getStore
	 * @return void
	 */
	public function testRetrieveMembers() {
		$manager = new ChecklistManager(
			$this->createMock( ChecklistParser::class ),
			$this->createMock( ChecklistStore::class ),
			$this->createMock( RevisionStore::class ),
			$this->createMock( PageUpdaterFactory::class )
		);
		$this->assertInstanceOf( ChecklistParser::class, $manager->getParser() );
		$this->assertInstanceOf( ChecklistStore::class, $manager->getStore() );
	}

	/**
	 * @return ChecklistManager
	 */
	private function getManager(): ChecklistManager {
		$parserMock = $this->createMock( ChecklistParser::class );
		$parserMock->method( 'parse' )->willReturn( [
			md5( 'abfoor#1' ) => [
				'id' => md5( 'abfoor#1' ),
				'text' => 'DUMMY',
				'value' => false,
				'type' => 'check'
			],
			md5( 'bar' ) => [
				'id' => md5( 'bar' ),
				'text' => "bar",
				'value' => true,
				'type' => 'check'
			],
		] );
		$storeMock = $this->createMock( ChecklistStore::class );
		$storeMock->method( 'delete' )->willReturn( true );
		$storeMock->method( 'forPageIdentity' )->willReturn( $storeMock );
		$storeMock->method( 'query' )->willReturn(
			[
				$this->createItem( [
					'id' => md5( 'abfoor#1' ),
					'text' => 'dummy',
					'value' => true,
					'type' => 'check'
				] ),
				$this->createItem( [
					'id' => md5( 'dummy' ),
					'text' => 'foo',
					'value' => true,
					'type' => 'check'
				] ),
			]
		);

		$storeMock->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->callback( static function ( ChecklistItem $item ) {
					return $item->getId() === md5( 'dummy' ) && $item->getText() === 'foo' && $item->getValue();
				} )
			);
		$expectedPersistItemCheckers = [
			static function ( ChecklistItem $item ) {
				return $item->getId() === md5( 'abfoor#1' ) &&
					$item->getText() === 'DUMMY' && !$item->getValue();
			},
			static function ( ChecklistItem $item ) {
				return $item->getId() === md5( 'bar' ) && $item->getText() === 'bar' && $item->getValue();
			}
		];
		$storeMock->expects( $this->exactly( 2 ) )
			->method( 'persist' )
			->willReturnCallback( function ( ChecklistItem $item ) use ( &$expectedPersistItemCheckers ) {
				$curChecker = array_shift( $expectedPersistItemCheckers );
				$this->assertTrue( $curChecker( $item ) );
				return false;
			} );
		return new ChecklistManager(
			$parserMock, $storeMock,
			$this->createMock( RevisionStore::class ),
			$this->createMock( PageUpdaterFactory::class )
		);
	}

	/**
	 * @param array $data
	 *
	 * @return ChecklistItem
	 */
	private function createItem( $data ): ChecklistItem {
		return new ChecklistItem(
			$data['id'],
			$data['text'],
			$data['value'],
			$this->createMock( Title::class ),
			new \DateTime(),
			new \DateTime(),
			$this->createMock( User::class )
		);
	}
}
