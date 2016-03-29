<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Entheos\Versions\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class TranslateFixture
 *
 */
class VersionsFixture extends TestFixture
{
    /**
     * table property
     *
     * @var string
     */
    public $table = 'versions';

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'version_id' => ['type' => 'integer'],
        'model' => ['type' => 'string', 'null' => false],
        'foreign_key' => ['type' => 'integer', 'null' => false],
        'content' => ['type' => 'text'],
        'custom_field' => ['type' => 'text'],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['version_id' => 1, 'model' => 'Clients', 'foreign_key' => 1, 'content' => '{"full_name":"Mario Rossi","city":"Firenze","created":"2016-03-29 13:44:55"}'],
        ['version_id' => 2, 'model' => 'Clients', 'foreign_key' => 1, 'content' => '{"full_name":"Mario Rossi","city":"Milano","created":"2016-03-29 17:40:00"}'],
    ];
}
