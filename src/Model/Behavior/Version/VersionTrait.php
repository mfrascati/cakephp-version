<?php
namespace Entheos\Versions\Model\Behavior\Version;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Database\Type;

trait VersionTrait
{
    /**
     * Updates the entity to a specified version
     *
     * @param int $versionId The version number to retrieve
     * @param bool $fresh If true, will re-retrieve the related version collection
     * @return void
     */
    public function version($versionId, $fresh = false)
    {
        if($versionId == $this->version)
            return;
        $versions = $this->versions($fresh);
        if (empty($versions[$versionId])) {
            return;
        }

        foreach($versions[$versionId] as $field=>$value)
        {
            $this->set($field, $value);
        }
        $this->set('version_id', $versionId);

        return;
    }

    /**
     * Retrieves the related versions for the current entity
     *
     * @param bool $fresh If true, will re-retrieve the related version collection
     * @return \Cake\Collection\Collection
     */
    public function versions($fresh = false)
    {
        if ($fresh === false && $this->has('_versions')) {
            return $this->get('_versions');
        }

        $table = TableRegistry::get('versions');

        $entities = $table->find('all')
            ->where(['foreign_key' => $this->id, 'model' => $this->getSource()])
            ->select(['id', 'version_id', 'content'])
            ->order(['id' => 'DESC'])
            ->formatResults([$this, 'processVersions'])
            ->combine('version_id', 'content');

        if ($entities->isEmpty()) {
            return [];
        }

        $this->set('_versions', $entities->toArray());
        return $this->get('_versions');
    }

    /**
     * Processes the retrieved versions to explode the content and casts to the right type
     * @param  Collection $results 
     * @return Collection
     */
    public function processVersions ($results){
        return $results->map(function ($row) {
            $row['content'] = json_decode($row['content']);

            $table = TableRegistry::get($this->getSource());

            foreach($row['content'] as $field=>&$value)
            {
                $columnType = $table->getSchema()->columnType($field);

                if(empty($columnType)){
                    // throw new \Cake\Core\Exception\Exception("Version Plugin: Non trovo il campo $field su DB");
                    \Cake\Log\Log::write('debug', "Version Plugin: Previously serialized field '$field' doesn't exist in DB");
                    continue;
                }

                $converter = Type::build($columnType);
                $value = $converter->marshal($value);
            }
            return $row;
        });
    }
}
