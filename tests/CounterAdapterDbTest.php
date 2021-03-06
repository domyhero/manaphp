<?php

namespace Tests;

use ManaPHP\Counter\Adapter\Db;
use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\DbInterface;
use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class CounterAdapterDbTest extends TestCase
{
    protected $_di;

    public function setUp()
    {
        parent::setUp();

        $this->_di = new FactoryDefault();
        $this->_di->setShared('db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new Mysql($config['mysql']);
            $db->attachEvent('db:beforeQuery', function (DbInterface $source, $data) {
                //  var_dump(['sql'=>$source->getSQL(),'bind'=>$source->getBind()]);
                var_dump($source->getSQL(), $source->getEmulatedSQL(2));

            });
            $db->execute('SET GLOBAL innodb_flush_log_at_trx_commit=2');
            return $db;
        });
    }

    public function test_get()
    {
        $counter = new Db();

        $counter->delete('c', '1');

        $this->assertEquals(0, $counter->get('c', '1'));
        $counter->increment('c', '1');
        $this->assertEquals(1, $counter->get('c', '1'));

        $counter->delete('c', 1);
        $this->assertEquals(0, $counter->get('c', 1));

        $counter->increment('c', 1, 100);
        $this->assertEquals(100, $counter->get('c', 1));
    }

    public function test_increment()
    {
        $counter = new Db();

        $counter->delete('c', '1');
        $this->assertEquals(2, $counter->increment('c', '1', 2));
        $this->assertEquals(22, $counter->increment('c', '1', 20));
        $this->assertEquals(2, $counter->increment('c', '1', -20));
    }

    public function test_delete()
    {
        $counter = new Db();

        $counter->delete('c', '1');

        $counter->increment('c', '1', 1);
        $counter->delete('c', '1');
    }
}
