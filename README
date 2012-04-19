Eclair Migrations
=================

A loose interpretation of what the Rails database migration framework provides, implemented in the CakePHP framework; personified in this instance by a drop-in Cake shell: Eclair.

Known Shortcomings
------------------

* Strong assumptions are made that the migrated schema will exist within a single datasource, and that no schema needs to be managed outside that source.
* Missing testing of all sorts.
* Provides a framework for regressing migrations, but makes no attempt to implement it (yet).

A Brief Conversation Between the Creator and Himself
----------------------------------------------------

### Why did you do this?

Because the migration engine built into CakePHP seemed to be woefully deficient in many respects.
* It was unable to atomize migrations into blocks that could be explained independently.
* It provided no possible mechanism for smooth regression.
* There was no way to couple business logic with schema changes, and let's be honest: sometimes that's necessary.
* It was incapable of expressing implementation- and database-specific schema elements, such as enums or relational constraints.

### Okay, that's great and all, but why did YOU do this? Hadn't anyone else built a solution?

You know full well I'm too busy reinventing wheels to search for what other people have done. Besides, there's a power to building something yourself and knowing what all the pieces do, why they're there, and how to modifiy them to do something else. Additionally, if people didn't keep reinventing the wheel, we wouldn't have hover wheels, now would we? Wait... do we have those yet?

### No. We don't.

Oh... Well it's bound to happen.

### And what's this about an interpretation of Rails migrations? How many times have you even used Rails?

Once.

### So...

Okay, so maybe I'm not familiar enough to create a full implementation. Maybe consider this an homage to Rails migration. An artistic dance exhibiting the feelings that Rails migrations instilled in me when I used them. I'm not a painter, or a sculpter, or a jazz flautist; I write code to express my feelings. Or that's what I keep telling the missus.

### And where does that 'Eclair' name come from?

...
...
Would you believe it was a streamlined 'cake'?

Usage
-----

### Installation

All that should be needed is to put the shell in the Console/Command/ directory, put the model in the Model/ directory, and create the Migrations/ directory.

### Create A Migration

    cake migration create ThisIsMySuperImportantSchemaChange

### Run A Single Migration

    cake migration run 20120417060500   # 20120417060500 = Migration serial ID.

### Update Database To Latest Version

    cake migration upgradeAll

### Upgrade A SPECIFIC Database

    cake migration upgradeAll -c anAwsomeDatabase

Thoughts
--------

Please share them! Especially if you think this is just a hackneyed attempt to reproduce functionality that already exists if you just type 'xyz command' or something like that. I would love to know that a more robust version of this already exists.
Thanks!