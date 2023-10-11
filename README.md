# Checklists

## Installation
Execute

    composer require hallowelt/checklists dev-REL1_35
within MediaWiki root or add `hallowelt/checklists` to the
`composer.json` file of your project

## Activation
Add

    wfLoadExtension( 'Checklists' );
to your `LocalSettings.php` or the appropriate `settings.d/` file.