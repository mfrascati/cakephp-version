
# Version

A CakePHP 3.x plugin that facilitates versioned database entities (a customized fork of josegonzales/cakephp-version)

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
Plugin::load('Entheos/Version', ['bootstrap' => true]);
```

## Usage

Run the following schema migration:

```sql
CREATE TABLE `version` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `version_id` int(11) DEFAULT NULL,
    `model` varchar(255) NOT NULL,
    `foreign_key` int(10) NOT NULL,
    `content` text,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

> You also need to add a `version_id` field of type `integer` to the table which is being versioned. This will store the latest version number of a given page.

Add the following line to your entities:

```php
use \Entheos\Version\Model\Behavior\Version\VersionTrait;
```

And then include the trait in the entity class:

```php
class PostEntity extends Entity {
    use VersionTrait;
}
```

Attach the behavior in the models you want with:

```php
public function initialize(array $config) {
    $this->addBehavior('Entheos/Version.Version', ['fields' => ['...']]);
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

### Configuration

There are two behavior configurations that may be used:

- `versionTable`: (Default: `versions`) The name of the table to be used to store versioned data. It may be useful to use a different table when versioning multiple types of entities.
- `versionField`: (Default: `version_id`) The name of the field in the versioned table that will store the current version. If missing, the plugin will continue to work as normal.
