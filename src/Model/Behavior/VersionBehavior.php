<?php

namespace Entheos\Versions\Model\Behavior;

use ArrayObject;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\I18n\Time;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Database\Type;

/**
 * This behavior provides a way to version dynamic data by keeping versions
 * in a separate table linked to the original record from another one. Versioned
 * fields have to be configured in behaviour loading.
 *
 * If you want to retrieve all versions for each of the fetched records,
 * you can use the custom `versions` finders that is exposed to the table.
 */
class VersionBehavior extends Behavior
{

    /**
     * Table instance
     *
     * @var \Cake\ORM\Table
     */
    protected $_table;

    /**
     * Default config
     *
     * These are merged with user-provided configuration when the behavior is used.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'versionTable' => 'versions',
        'versionField' => 'version_id',
        'sameVersionTime' => '10 minutes',
        'fields' => null
    ];

    /**
     * Constructor hook method.
     *
     * Implement this method to avoid having to overwrite
     * the constructor and call parent.
     *
     * @param array $config The configuration settings provided to this behavior.
     * @return void
     */
    public function initialize(array $config)
    {
        $config = $this->config();
        if(empty($config['fields']))
            throw new \Exception("No fields specified for versioning");
            
        $this->setupFieldAssociations($config['versionTable']);
    }

    /**
     * Creates the associations between the bound table and every field passed to
     * this method.
     *
     * Additionally it creates a `i18n` HasMany association that will be
     * used for fetching all versions for each record in the bound table
     *
     * @param string $table the table name to use for storing each field version
     * @return void
     */
    public function setupFieldAssociations($table)
    {

        $this->_table->hasMany($table, [
            'foreignKey' => 'foreign_key',
            'strategy' => 'subquery',
            'conditions' => ["$table.model" => $this->_table->alias()],
            'propertyName' => '__version',
            'dependent' => true
        ]);
    }

    /**
     * Modifies the entity before it is saved so that versioned fields are persisted
     * in the database too.
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired
     * @param \Cake\ORM\Entity $entity The entity that is going to be saved
     * @param \ArrayObject $options the options passed to the save method
     * @return void
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $table = $this->_config['versionTable'];
        $newOptions = [$table => ['validate' => false]];
        $options['associated'] = $newOptions + $options['associated'];

        $fields = $this->_fields();
        $values = $entity->extract($fields);

        $model = $this->_table->alias();
        $primaryKey = $this->_table->primaryKey();
        $foreignKey = $entity->get($primaryKey);
        $versionField = $this->_config['versionField'];

        $createVersion = $this->_needToVersion($entity);

        if($createVersion)
        {
            $preexistent = TableRegistry::get($table)->find()
                ->select(['version_id'])
                ->where(compact('foreign_key', 'model'))
                ->order(['id desc'])
                ->limit(1)
                ->hydrate(false)
                ->toArray();

            $versionId = Hash::get($preexistent, '0.version_id', 0) + 1;

            $created = new Time();

            foreach($values as $field=>&$value)
            {
                $columnType = $this->_table->schema()->columnType($field);
                if($columnType == 'datetime')
                    $value = $value->format('Y-m-d H:i:s');
                elseif($columnType == 'date')
                    $value = $value->format('Y-m-d');
            }
            
            $data = [
                'model' => $model,
                'version_id' => $versionId,
                'foreign_key' => $foreignKey,
                'content' => json_encode($values),
            ];

            $version = [new Entity($data, [
                'useSetters' => false,
                'markNew' => true
            ])];
            
            $entity->set('__version', $version);

            if (!empty($versionField)) {
                $entity->set($this->_config['versionField'], $versionId);
            }
            // debug($entity);die();
        }
    }

    /**
     * Checks if the fields monitored for versioning are dirty or if the entity is new
     *  
     * @param  Entity $entity 
     * @return boolean Whether there is the need save a versioned copy of the entity
     */
    protected function _needToVersion($entity)
    {
        if($entity->isNew())
            return true;
        else
        {
            $fields = $this->_fields();
            foreach($fields as $field)
            {
                if($entity->dirty($field))
                {
                    return true;
                }
            }
        }
        return;
    }

    /**
     * Unsets the temporary `__version` property after the entity has been saved
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired
     * @param \Cake\ORM\Entity $entity The entity that is going to be saved
     * @return void
     */
    public function afterSave(Event $event, Entity $entity)
    {
        $entity->unsetProperty('__version');
    }

    /**
     * Returns an array of fields to be versioned.
     *
     * @return array
     */
    protected function _fields()
    {
        return array_merge($this->_config['fields'], ['created']);
    }
}
