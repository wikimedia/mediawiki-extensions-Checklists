# How are checklist items defined in Wikitext?

## Checkbox items

Define a checkbox item by using the following syntax:

`[] Label of the checkbox, which, since its not a part of the checkbox, can contain wikitext systax`

Checklist item will be considered: line that starts with `[]` (or `[x]` for checked items) and ends with a newline.
So text of the checkbox item is everything after `[]` until the newline.

# Checklist item IDs

How are IDs of the checklist items managed?

- IDs are determined based on the text of the checklist item, text of the line after the checkbox itself

	`[] Text of the checkbox` => `Text of the checkbox` will be used as base for determining ID
- This text will be stripped of any whitespaces, commas and periods
- Text will be lowercased
- Order of characters in the text will be sorted alphabetically
- Processed text will be hashed to get the ID (using MD5)
- Additionally, hash will be salted with the ID of the page where checklist is defined, to prevent ID collisions between checklists on different pages

This means that checkbox ID will be the same for the following lines:

`[] Text of the checkbox`

`[x] Text of the checkbox`

`[] TEXT of the checkbox`

`[] Text     , of the checkbox`

`[] Text.of.the.checkbox`

`[x] Text of TEH checkbox` <= typo

Fixing text of the checkbox that still results in the same ID will update the text of the item in the DB, but not change the ID.

`[] Text of TEH checkbox` => `[] Text of the checkbox` will not change the ID

However, making bigger changes, actually changing the text will result in a new ID.

`[] Text of TEH checkbox` => `[] New text of the checkbox` will result in a new ID and delete the old item from the DB, and create a new one.

## ID conflict resolution

As stated above, IDs will not collide between checklists on different pages, but they can collide between checklists on the same page.

If there are multiple checklist items that resolve to the same ID on the same page, these will be resolved before the page is saved,
so that on the saved page there are no conflicts.

If page contains three checklist items that resolve to the same ID:

	some text
	[] Text of the checkbox
	[x] Text of THE checkbox
	[] Text of the checkbox

they will be resolved like this:

	some text
	[] Text of the checkbox
	[x] Text of THE checkbox (2)
	[] Text of the checkbox (3)
