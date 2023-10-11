<?php

namespace MediaWiki\Extension\Checklists;

use DateTime;
use MediaWiki\Page\PageIdentity;
use MediaWiki\User\UserIdentity;

class ChecklistItem implements \JsonSerializable {
	/** @var string */
	private $text;
	/** @var string */
	private $value;

	/** @var PageIdentity */
	private $page;

	/** @var string */
	private $id;
	/**
	 * @var DateTime
	 */
	private $created;
	/**
	 * @var DateTime
	 */
	private $modified;
	/**
	 * @var UserIdentity|null
	 */
	private $author;

	/**
	 * @param string $id
	 * @param string $text
	 * @param string $value
	 * @param PageIdentity $page
	 * @param DateTime $created
	 * @param DateTime $modified
	 * @param UserIdentity|null $author
	 */
	public function __construct(
		string $id, string $text, string $value, PageIdentity $page,
		DateTime $created, DateTime $modified, ?UserIdentity $author = null
	) {
		$this->id = $id;
		$this->text = $text;
		$this->value = $value;
		$this->page = $page;
		$this->created = $created;
		$this->modified = $modified;
		$this->author = $author;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getText(): string {
		return $this->text;
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		return $this->value;
	}

	/**
	 * @return PageIdentity
	 */
	public function getPage(): PageIdentity {
		return $this->page;
	}

	/**
	 * @return DateTime
	 */
	public function getCreated(): DateTime {
		return $this->created;
	}

	/**
	 * @return DateTime
	 */
	public function getModified(): DateTime {
		return $this->modified;
	}

	/**
	 * @return UserIdentity|null
	 */
	public function getAuthor(): ?UserIdentity {
		return $this->author;
	}

	/**
	 * @return string
	 */
	public function getHash(): string {
		return md5( $this->getValue() . $this->text );
	}

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	public function setValue( string $value ) {
		$this->value = $value;
	}

	/**
	 * @param string $text
	 *
	 * @return void
	 */
	public function setText( string $text ) {
		$this->text = $text;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'text' => $this->getText(),
			'value' => $this->getValue(),
			'page' => $this->getPage()->getNamespace() . '|' . $this->getPage()->getDBkey(),
			'created' => $this->getCreated()->format( 'YmdHis' ),
			'modified' => $this->getModified()->format( 'YmdHis' ),
			'author' => $this->getAuthor() ? $this->getAuthor()->getName() : null,
		];
	}
}
