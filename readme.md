# Migrations Plugin for CakePHP #

Version 2.1 for cake 2.x

This migrations plugin enables developers to quickly and easily manage and migrate between database schema versions.

As an application is developed, changes to the database may be required, and managing that in teams can get extremely difficult. Migrations enables you to share and co-ordinate database changes in an iterative manner, removing the complexity of handling these changes. 

## This is NOT a backup tool

We highly recommend not to run Migrations in a production environment directly without doing a backup first.

However you can make use of the before() and after() callbacks in migrations to add some logic there to trigger a backup script for example.

## Installing ##

## Usage ##

- Unzip or clone this plugin into your app/Plugin/Migrations folder or the shared plugins folder for your CakePHP installation.
- Add the plugin to your app/Config/bootstrap.php using `CakePlugin::load('Migrations')`
- Run `Console/cake Migrations.migration run all -p Migrations` to initialize the `schema_migrations` table

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

#### Runing all pending migrations ####

To get all pending changes into your database run:

	cake Migrations.migration run all

#### Reseting your database ####

	cake Migrations.migration run reset

#### Downgrade to previous version ####

	cake Migrations.migration run down

#### Upgrade to next version ####

	cake Migrations.migration run up

#### Running migrations for plugins ####

	cake Migrations.migration run all --plugin Users

#### Getting the status of available/applied Migrations ####

	cake Migrations.migration status

### Pre-migration checks ###

The migration system supports two checking modes: exception-based and condition-based.

The main difference is that exceptions will make the migration shell fail hard while the condition based check is a more gracefully way to check for possible problems with a migration before exceptions even can happen.

If the database already has some db modification applied and you will try to execute same migration again, then the migration system will throw an exception. This is exception mode for migrations system. Exception-based checking  is the default mode.

Condition based works different. When the system is running a migration it checks that it is possible to apply the migration on the current database structure. For example if it is possible to create a table, if it already exists it will stop before applying the migration.

Another example is dropping a field, the pre migration check will check if the table and field exists and if not it wont apply the migration.

To enable condtion-based mode use '--precheck Migrations.PrecheckCondition' with the migration shell.

#### Customized pre-migration checks

It is possible to implemented customized pre-checks. Your custom pre-check class has to extend the PrecheckBase class from this plugin. You'll have to put your class into APP/Lib/Migration/<YourClass>.php or inside a plugin.

To run your class use '--precheck YourPrecheckClass' or to load it from another plugin simply follow the dot syntax and use '--precheck YourPlugin.YourPrecheckClass'

### Migration shell return codes ###

0 = Success
1 = No migrations available
2 = Not a valid migration version

### Auto migration files ###

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
		    'name' => array('length' => 11)
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

#### Alter Index ####

In order to add a new index to an existing field, you need to drop the field and create it again passing the index definition in an array.

	'drop_field' => array(
		'posts' => array('title')
	),
	'create_field' => array(
		'posts' => array(
			'title' => array('type' => 'string', 'length' => 255, 'null' => false),
			'indexes' => array('UNIQUE_TITLE' => array('column' => 'title', 'unique' => true))
		)
	)

Likewise, if you want to drop an index then you need to drop the field including the indexes you want to drop, then you create the field again.
	
	'drop_field' => array(
		'posts' => array('title', 'indexes' => array('UNIQUE_TITLE'))
	),
	'create_field' => array(
		'posts' => array(
			'title' => array('type' => 'string', 'null' => true, 'length' => 255)
		)
	)

## Callbacks ##

You can make use of callbacks in order to execute extra operations, for example, to fill tables with predefined data, you can even use the shell to ask the user for data that is going to be inserted.

Example 1: Create table statuses and fill it with some default data

	public $migration = array(
		'up' => array(
			'create_table' => array(
				'statuses' => array(
					'id' => array(
						'type' => 'string',
						'length' => 36,
						'null' => false,
						'key' => 'primary'),
					'name' => array(
						'type' => 'text',
						'null' => false,
						'default' => NULL),
				)
			)
		),
		'down' => array(
			'drop_table' => array('statuses')
		),
	);

	public function after($direction) {
		$Status = ClassRegistry::init('Status');
		if ($direction == 'up') { //add 2 records to statues table
			$data['Status'][0]['id'] = '59a6a2c0-2368-11e2-81c1-0800200c9a66';
			$data['Status'][0]['name'] = 'Published';
			$data['Status'][1]['id'] = '59a6a2c1-2368-11e2-81c1-0800200c9a67';
			$data['Status'][1]['name'] = 'Unpublished';
			$Status->create();
			if ($Status->saveAll($data)){
				echo "Statues table has been initialized";
			}
		} else if ($direction == 'down') {
			//do more work here
		}
	}

Example 2: Prompt the user to insert data

	public $migration = array(
		'up' => array(
			'create_table' => array(
				'statuses' => array(
					'id' => array(
						'type' => 'string',
						'length' => 36,
						'null' => false,
						'key' => 'primary'),
					'name' => array(
						'type' => 'text',
						'null' => false,
						'default' => NULL),
				)
			)
		),
		'down' => array(
			'drop_table' => array('statuses')
		),
	);

	public function after($direction) {
		$Status = ClassRegistry::init('Status');
		if ($direction == 'up') {
			$this->callback->out('Please enter a default status below:');
			$data['Status']['name'] = $this->callback->in('What is the name of the default status?');
			$Status->create(); 
			if ($Status->save($data)){
				echo "Statues table has been initialized";
			}
		} else if ($direction == 'down') {
			//do more work here
		}
	}

## Requirements ##

* PHP version: PHP 5.2+
* CakePHP version: 2.1

## Support ##

For support and feature request, please visit the [Migrations Plugin Support Site](http://cakedc.lighthouseapp.com/projects/59617-migrations-plugin/).

For more information about our Professional CakePHP Services please visit the [Cake Development Corporation website](http://cakedc.com).

## Branch strategy ##

The master branch holds the STABLE latest version of the plugin. 
Develop branch is UNSTABLE and used to test new features before releasing them. 

Previous maintenance versions are named after the CakePHP compatible version, for example, branch 1.3 is the maintenance version compatible with CakePHP 1.3.
All versions are updated with security patches.

## Contributing to this Plugin ##

Please feel free to contribute to the plugin with new issues, requests, unit tests and code fixes or new features. If you want to contribute some code, create a feature branch from develop, and send us your pull request. Unit tests for new features and issues detected are mandatory to keep quality high. 

## License ##

Copyright 2009-2011, [Cake Development Corporation](http://cakedc.com)

Licensed under [The MIT License](http://www.opensource.org/licenses/mit-license.php)<br/>
Redistributions of files must retain the above copyright notice.

## Copyright ##

Copyright 2009-2012<br/>
[Cake Development Corporation](http://cakedc.com)<br/>
1785 E. Sahara Avenue, Suite 490-423<br/>
Las Vegas, Nevada 89104<br/>
http://cakedc.com<br/>
