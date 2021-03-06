<?php

namespace Soli\Tests\Web;

use PHPUnit\Framework\TestCase;

use Soli\Web\Session;

class SessionTest extends TestCase
{
    /**
     * @var \Soli\Web\Session
     */
    protected $session;

    protected function setUp()
    {
        $this->session = new Session(false);
    }

    protected function tearDown()
    {
        $this->session = null;
    }

    /**
     * @runInSeparateProcess
     */
    public function testStart()
    {
        $this->assertEquals('', $this->session->getId());
        $this->assertTrue($this->session->start());
        $this->assertNotEquals('', $this->session->getId());
    }

    /**
     * @runInSeparateProcess
     */
    public function testIsStarted()
    {
        $this->assertFalse($this->session->isStarted());
        $this->session->start();
        $this->assertTrue($this->session->isStarted());
    }

    public function testSetId()
    {
        $this->assertEquals('', $this->session->getId());
        $this->session->setId('0123456789abcdef');
        $this->session->start();
        $this->assertEquals('0123456789abcdef', $this->session->getId());
    }

    public function testSetName()
    {
        $this->assertEquals('PHPSESSID', $this->session->getName());
        $this->session->setName('session.test.com');
        $this->session->start();
        $this->assertEquals('session.test.com', $this->session->getName());
    }

    public function testGet()
    {
        // tests defaults
        $this->assertNull($this->session->get('foo'));
        $this->assertEquals(1, $this->session->get('foo', 1));
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($key, $value)
    {
        $this->session->set($key, $value);
        $this->assertEquals($value, $this->session->get($key));
    }

    /**
     * @dataProvider setProvider
     */
    public function testHas($key, $value)
    {
        $this->session->set($key, $value);
        $this->assertTrue($this->session->has($key));
        $this->assertFalse($this->session->has($key.'non_value'));
    }

    /**
     * @dataProvider setProvider
     */
    public function testRemove($key, $value)
    {
        $this->session->set($key, $value);
        $this->session->remove($key);
        $this->assertFalse($this->session->has($key));
    }

    public function setProvider()
    {
        return [
            ['foo', 'bar', ['foo' => 'bar']],
            ['foo.bar', 'too much beer', ['foo.bar' => 'too much beer']],
            ['great', 'soliphp is great', ['great' => 'soliphp is great']],
        ];
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetId()
    {
        $this->assertEquals('', $this->session->getId());
        $this->session->start();
        $this->assertNotEquals('', $this->session->getId());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateId()
    {
        $this->session->start();
        $id = $this->session->getId();
        $this->session->regenerateId();
        $this->assertTrue($id != $this->session->getId());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDestroy()
    {
        ini_set('session.use_strict_mode', 1);
        $this->session->start();

        $key = 'hi.soli';
        $value = 'have a nice day';
        $this->session->set($key, $value);

        $this->session->destroy();

        $this->assertEquals($value, $this->session->get($key));
        $this->assertFalse($this->session->isStarted());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDestroyRemoveData()
    {
        ini_set('session.use_strict_mode', 1);
        $this->session->start();

        $key = 'hi.soli';
        $value = 'have a nice day';
        $this->session->set($key, $value);

        $this->session->destroy(true);
        $this->assertFalse($this->session->has($key));
        $this->assertFalse($this->session->isStarted());
    }
}
