
# Version

A CakePHP 3.x plugin that facilitates versioned database entities (a customized fork of josegonzalez/cakephp-version)

The puropose of this plugin is to make possibile to track version of a record, making possibile to access the previous states. For example, let's say you make Invoices for a Client. Then the Client changes city he lives, and you make another Invoice. Without versioning you can lose the original city information (unless you duplicate the full record in you DB).

The main differences from the original plugin are:
- instead saving a record per modified fields it saves a single json encoded string for all the fields
- you need to explicitly set the monitored fields, instead of versioning every field of the entity
- you can set a time window in which additional changes to the record are considered as corrections and not a new version, so the last version is updated (TO DO)

## Installation

Add the following lines to your application's `composer.json`:

```json
"require": {
    "mfrascati/cakephp-version": "dev-master"
}
```

followed by the command:

`composer update`

Or run the following command directly without changing your `composer.json`:

`composer require mfrascati/cakephp-version:dev-master`

## Usage

In your app's `config/bootstrap.php` add:

```php
Plugin::load('Entheos/Versions');
```

## Usage

Run the following schema migration:

```sql
CREATE TABLE `versions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `version_id` int(11) unsigned DEFAULT '1',
    `model` varchar(255) NOT NULL,
    `foreign_key` int(10) unsigned NOT NULL,
    `content` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

> You also need to add a `version_id` field of type `integer` to the table which is being versioned. This will store the latest version number of a given record.

```
->addColumn('version_id', 'integer', ['default' => 1, 'signed' => false, 'null' => true, 'after' => 'id' ])
```

Add the following line to your entities:

```php
use \Entheos\Versions\Model\Behavior\Version\VersionTrait;
```

And then include the trait in the entity class:

```php
class ClientEntity extends Entity {
    use VersionTrait;
}
```

Attach the behavior in the models you want with, specifying the monitored fields ('created' is always added by default):

```php
public function initialize(array $config) {
    $this->addBehavior('Entheos/Versions.Version', ['fields' => ['...']]);
}
```

Whenever an entity is persisted - whether via insert or update - the monitored fields of the entity are also persisted to the `versions` table. You can change the entity values to a given revision by executing the following code:

```php
$entity->version(1);
```

You can optionally retrieve all the versions:

```php
$versions = $entity->versions();
```

When you save a record referencing a versioned row, apart from the foreign key you'll also need to save the version for that record.
For example, if you have Client a Contract, and Contract has many Clients, on Contracts table you'll have Contracts.client_id and Contracts.client_version. After retrieving the current state of the Contract, you can go to the specified version:

```php
// In your ContractsController 
public function view($id = null)
{
    $contract = $this->Contracts->find('all')
        ->where(['Contracts.id' => $id])
        ->contain(['Clients'])
        ->formatResults(function ($results){
            return $results->map(function ($row) {
                $row->client->version($row->client_version);
                return $row;
            });
        })
        ->first();
    // ...
}
```
### Configuration

There are two behavior configurations that may be used:

- `versionTable`: (Default: `versions`) The name of the table to be used to store versioned data. It may be useful to use a different table when versioning multiple types of entities.
- `versionField`: (Default: `version_id`) The name of the field in the versioned table that will store the current version.
