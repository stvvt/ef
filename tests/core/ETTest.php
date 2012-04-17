<?php
require 'ETTestBase.php';

/**
 * Зареждаме тествания клас за да избегнем автоматичното зареждане на евентуален клас-псевдоним
 */
require EF_EF_PATH . '/core/ET.class.php';

class core_ETTest extends core_ETTestBase
{
	protected static $tested = 'core_ET';


	/**
	 * @covers core_ET::replace
	 */
	public function testBubbling5() {
		$sub1   = new static::$tested('sub1:[#a#]');
		$sub2   = new static::$tested('sub2:[#b#]');
		$master = new static::$tested('master:[#sub1#]:[#sub2#]');

		$sub1->replace('{b}', 'b');
		$sub2->replace('{a}', 'a');

		$master->replace($sub1, 'sub1');
		$master->replace($sub2, 'sub2');

		$this->assertEquals('master:sub1:{a}:sub2:', (string)$master);
	}


    /**
     * @covers core_ET::isPlaceholderExists
     */
    public function testIsPlaceholderExists()
    {
        parent::testIsPlaceholderExists();

        // След replace плейсхолдърът изчезва
        $this->simpleTpl->replace('content', 'placeholder');
        $this->assertFalse($this->simpleTpl->isPlaceholderExists('placeholder'));
    }
}
