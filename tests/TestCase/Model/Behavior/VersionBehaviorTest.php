<?php
namespace Entheos\Versions\Test\TestCase\Model\Behavior;

use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\I18n\I18n;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Entheos\Versions\Model\Behavior\VersionBehavior;
use Entheos\Versions\Model\Behavior\Version\VersionTrait;

class TestEntity extends Entity
{
    use VersionTrait;
}

class VersionBehaviorTest extends TestCase
{
    public $fixtures = [
        'plugin.Entheos\Versions.versions',
        'plugin.Entheos\Versions.clients',
    ];

    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    public function testSaveNew()
    {
        $table = TableRegistry::get('Clients', [
            'entityClass' => 'Entheos\Versions\Test\TestCase\Model\Behavior\TestEntity'
        ]);

        if($table->hasBehavior('Version'))
            $table->removeBehavior('Version');

        $this->expectException('Exception');
        $table->addBehavior('Entheos/Versions.Version');

        $table->addBehavior('Entheos/Versions.Version', ['fields' => ['full_name', 'city']]);

        $client = $table->find('all')->first();
        $this->assertEquals(2, $client->version_id);

        $versionTable = TableRegistry::get('Versions');
        $results = $versionTable->find('all')
                                ->where(['foreign_key' => $client->id])
                                ->hydrate(false)
                                ->toArray();
        $this->assertCount(2, $results);

        $client->city = 'Roma';
        $table->save($client);

        $versionTable = TableRegistry::get('Versions');
        $results = $versionTable->find('all')
                                ->where(['foreign_key' => $client->id])
                                ->hydrate(false)
                                ->toArray();

        $this->assertEquals(3, $client->version_id);
        $this->assertCount(3, $results);
    }

    public function testFindVersion()
    {
        $table = TableRegistry::get('Clients', [
            'entityClass' => 'Entheos\Versions\Test\TestCase\Model\Behavior\TestEntity'
        ]);
        $table->addBehavior('Entheos/Versions.Version');
        $client = $table->find('all')->first();

        $this->assertEquals('Milano', $client->get('city'));
        $this->assertEquals(2, $client->get('version_id'));
        
        $client->version(1);

        $this->assertEquals('Firenze', $client->get('city'));
        $this->assertEquals(1, $client->get('version_id'));

    }

}
