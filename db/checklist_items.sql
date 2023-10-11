CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/checklist_items (
	`ci_id` VARCHAR( 255 ) NOT NULL PRIMARY KEY,
	`ci_page` INT unsigned NOT NULL,
	`ci_author` INT unsigned NOT NULL,
	`ci_value` VARCHAR(255) NULL DEFAULT '',
	`ci_created` VARCHAR(15) NOT NULL,
	`ci_touched` VARCHAR(15) NOT NULL,
	`ci_hash` VARCHAR(32) NOT NULL,
    `ci_text` TEXT NULL
	) /*$wgDBTableOptions*/;
