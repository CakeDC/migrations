# Migrations Plugin for CakePHP #

Version 2.1

This migrations plugin enables developers to quickly and easily manage and migrate between database schema versions.

As an application is developed, changes to the database may be required, and managing that in teams can get extremely difficult. Migrations enables you to share and co-ordinate database changes in an iterative manner, removing the complexity of handling these changes.

## Installing ##


## Usage ##

- Unzip or clone this plugin into your app/Plugin/Migrations folder or the shared plugins folder for your CakePHP installation.
- Add the plugin to your app/Config/bootstrap.php using `CakePlugin::load('Migrations')`
- Run `Console/cake Migrations.migration -p Migrations` to initialized the `schema_migrations` table

### Generating your first migration ###

The first step to adding migrations to an existing database is to import the database's structure into a format the migrations can work with. Namely a migration file. To create the _first_ migration file run the following command:

	cake Migrations.migration generate

Answer the questions asked, and it will generate a new file containing a database structure snapshot using the internal migration's plugin syntax.
If you want import all tables regardless if it has a model or not you can use -f (force) parameter while running the command:

	cake Migrations.migration generate -f 

### Running migrations ###

After generating or being supplied a set of migrations, you can process them to change the state of your database.

This is the crux of the migrations plugin, allowing migration of schemas up and down the migration chain,
offering flexibility and easy management of your schema and data states.

#### Runing all pending migrations ###

To get all pending changes into your database run:

	cake Migrations.migration all

#### Reseting your database ###

	cake Migrations.migration reset

#### Downgrade to previous version ###

	cake Migrations.migration down

#### Upgrade to next version ###

	cake Migrations.migration up

#### Running migrations for plugins ###

	cake migration all --plugin Users

### Migration shell return codes

0 = Success
1 = No migrations available
2 = Not a valid migration version

###  Auto migration files ### 

Once you have Generated your first Migration you will probably do more changes to your database.
To simplify the generation of new migration you can do Schema Diffs. To this, you need to follow the steps:

1. Generate your first Migration (if haven't generated yet)
2. Generate a schema file with `cake schema generate`
3. Do changes to your database using your favorite tool
4. Generate a new migration file doing `cake Migrations.migration generate`

### Manually creating migration files ###

If you prefer full control over your changes, or do not want to mess with sql at all you have the option to
manually create your migration files. First create a blank migration doing:

	cake Migrations.migration generate

And skip the databse to schema comparison if asked. Then open the newly created file under `app/Config/Migrations`.
The file must be filled using the migration directives as follows:

#### Create Table ####

Create table is used for the creation of new tables in your database.
Note that migrations will generate errors if the specified table already exists in the database.
Directives exist (Drop, Rename) to deal with existing tables before proceeding with table creation.

Example:

	'create_table' => array(
		'categories' => array(
			'id' => array(
				'type'    =>'string',
				'null'    => false,
				'default' => NULL,
				'length'  => 36,
				'key'     => 'primary'),
			'name' => array(
				'type'    =>'string',
				'null'    => false,
				'default' => NULL),
			'indexes' => array(
				'PRIMARY' => array(
					'column' => 'id',
					'unique' => 1)
			)
		),
		'emails' => array(
			'id' => array(
				'type'    => 'string',
				'length ' => 36,
				'null'    => false,
				'key'     => 'primary'),
			'data' => array(
				'type'    => 'text',
				'null'    => false,
				'default' => NULL),
			'sent' => array(
				'type'    => 'boolean',
				'null'    => false,
				'default' => '0'),
			'error' => array(
				'type'    => 'text',
				'default' => NULL),
			'created' => array(
				'type' => 'datetime'),
			'modified' => array(
				'type' => 'datetime'),
			'indexes' => array(
				'PRIMARY' => array(
					'column' => 'id',
					'unique' => 1)
			)
		)
	);

#### Drop Table ####

Drop table is used for removing tables from the schema.
Directives exist Create, Rename to handle other table based migration operations.

	'drop_table' => array(
		'categories',
		'emails'
	)

#### Rename Table ####

Changes the name of a table in the database.
Directives exist (Create, Drop) to handle creation and deletion of tables.

	'rename_table' => array(
		'categories' => 'groups',
		'emails' => 'email_addresses'
	)

#### Create Field ####

Create Field is used to add fields to an existing table in the schema.
Note that migrations will generate errors if the specified field already exists in the table.
Directives exist (Drop, Rename, Alter) to deal with existing fields before proceeding with field addition.

	'create_field' => array(
		'categories' => array(
			'created' => array(
				'type' => 'datetime'),
			'modified' => array(
				'type' => 'datetime')
		)
	)

#### Drop Field ####

Drop field is used for removing fields from existing tables in the schema.
Directives exist (Create, Rename, Alter) to handle other field based migration operations.

	'drop_field' => array(
		'categories' => array(
			'created',
			'modified'),
		'emails' => array(
			'error')
	)

#### Alter Field ####

Changes the field properties in an existing table.
Note that partial table specifications are passed, which is a subset of a full array of Table data.
These are the fields that are to be modified as part of the operation.
If you wish to leave some fields untouched, simply exclude them from the Table spec for the alter operation.
Directives exist (Create, Drop, Rename) to handle other field operations.

	'alter_field' => array(
		'categories' => array(
			'indexes' => array(
				'NAMES' => false,
				'NAME' => array(
					'column' => 'name',
					'unique' => 0),
			)
		)
	)

#### Rename Field ####

Changes the name of a field on a specified table in the database.
Directives exist (Create, Drop, Alter) to handle creation and deletion of fields.

	'rename_field' => array(
		'categories' => array(
			'name' => 'title'
		),
		'emails' => array(
			'error' => 'error_code',
			'modified' => 'updated'
		),
	)


## Requirements ##

* PHP version: PHP 5.2+
* CakePHP version: 2.1

## Support ##

For support and feature request, please visit the [Migrations Plugin Support Site](http://cakedc.lighthouseapp.com/projects/59617-migrations-plugin/).

For more information about our Professional CakePHP Services please visit the [Cake Development Corporation website](http://cakedc.com).

## License ##

Copyright 2009-2011, [Cake Development Corporation](http://cakedc.com)

Licensed under [The MIT License](http://www.opensource.org/licenses/mit-license.php)<br/>
Redistributions of files must retain the above copyright notice.

## Copyright ###

Copyright 2009-2012<br/>
[Cake Development Corporation](http://cakedc.com)<br/>
1785 E. Sahara Avenue, Suite 490-423<br/>
Las Vegas, Nevada 89104<br/>
http://cakedc.com<br/>
