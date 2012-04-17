<?php
/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-03-28 at 10:47:31.
 */

class core_ETTestBase extends PHPUnit_Framework_TestCase
{
	/**
	 * @var core_ET
	 */
	protected $simpleTpl;

	/**
	 * @var core_ET
	 */
	protected $parentTpl;

	protected static $tested = NULL;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->simpleTpl = new static::$tested("Sample [#placeholder#] template");
    	$this->parentTpl = new static::$tested("before <!--ET_BEGIN block-->with [#row#]<!--ET_END block--> after");
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testConstruct() {
    	$tpl = new static::$tested('test[#1#]test[#3#][#2#]', '{one}', '{two}', '{three}');
    	$this->assertEquals('test{one}test{three}{two}', (string)$tpl);
    }

    /**
     * @covers core_ET::append
     */
    public function testAppend()
    {
    	$this->simpleTpl->append('{append-1}', 'placeholder');
    	$this->simpleTpl->append('{append-2}', 'placeholder');

    	$this->assertEquals("Sample {append-1}{append-2} template", (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::append
     */
    public function testAppendNoPlace()
    {
    	$this->simpleTpl->append('{append-1}');
    	$this->simpleTpl->append('{append-2}');

    	$this->assertEquals("Sample  template{append-1}{append-2}", (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::appendOnce
     */
    public function testAppendOnce()
    {
    	$this->simpleTpl->appendOnce('{once}', 'placeholder');
    	$this->simpleTpl->appendOnce('{twice}', 'placeholder');
    	$this->simpleTpl->appendOnce('{once}', 'placeholder');
    	$this->simpleTpl->appendOnce('{twice}', 'placeholder');
    	$this->simpleTpl->prepend('{twice}', 'placeholder', TRUE);

    	$this->assertEquals("Sample {once}{twice} template", (string)$this->simpleTpl);
	}

    /**
     * @covers core_ET::prepend
     */
    public function testPrepend()
    {
    	$this->simpleTpl->prepend('{prepend-1}', 'placeholder');
    	$this->simpleTpl->prepend('{prepend-2}', 'placeholder');

    	$this->assertEquals("Sample {prepend-2}{prepend-1} template", (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::append
     */
    public function testPrependNoPlace()
    {
    	$this->simpleTpl->prepend('{prepend-1}');
    	$this->simpleTpl->prepend('{prepend-2}');

    	$this->assertEquals("{prepend-2}{prepend-1}Sample  template", (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::replace
     */
    public function testReplace()
    {
        $this->simpleTpl->replace('{replace}', 'placeholder');
        $this->simpleTpl->replace('{again replace}', 'placeholder');

        $this->assertEquals("Sample {replace} template", (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::replace
     */
    public function testReplaceStatic()
    {
        $this->simpleTpl->replace('[#replaced#]', 'placeholder');
        $this->simpleTpl->replace('{again replaced}', 'replaced');

        $this->assertEquals("Sample &#91;#replaced#] template", (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::replace
     */
    public function testReplaceDynamic()
    {
        $this->simpleTpl->replace(new static::$tested('[#replaced#]'), 'placeholder');
        $this->simpleTpl->replace('{again replaced}', 'replaced');

        $this->assertEquals("Sample {again replaced} template", (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::append
     * @covers core_ET::prepend
     */
    public function testAppendPrepend()
    {
    	$this->simpleTpl->append('{append}', 'placeholder');
    	$this->simpleTpl->prepend('{prepend}', 'placeholder');

        $this->assertEquals("Sample {append}{prepend} template", (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::append
     * @covers core_ET::prepend
     */
    public function testAppendPrependNoPlace()
    {
    	$this->simpleTpl->append('{append}');
    	$this->simpleTpl->prepend('{prepend}');

        $this->assertEquals("{prepend}Sample  template{append}", (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::append
     * @covers core_ET::replace
     */
    public function testAppendReplace()
    {
        $this->simpleTpl->append('{append}', 'placeholder');
        $this->simpleTpl->replace('{replace}', 'placeholder');

        $this->assertEquals("Sample {append}{replace} template", (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::append
     * @covers core_ET::replace
     */
    public function testReplaceAppend()
    {
        $this->simpleTpl->replace('{replace}', 'placeholder');
        $this->simpleTpl->append('{append}', 'placeholder');

        $this->assertEquals("Sample {replace} template", (string)$this->simpleTpl);
    }


    /**
     * @covers core_ET::push
     */
    public function testPush()
    {
    	$this->simpleTpl->push('one', 'JS');
    	$this->simpleTpl->push('two', 'JS');

    	$getArray = static::getMethod('getArray');

    	$JS = $getArray->invoke($this->simpleTpl, 'JS');

    	$this->assertEquals(array('one', 'two'), $JS);
    }


    /**
     * @covers core_ET::push
     */
    public function testPushArray()
    {
    	$this->simpleTpl->push(array('one', 'two'), 'JS');

    	$getArray = static::getMethod('getArray');

    	$JS = $getArray->invoke($this->simpleTpl, 'JS');

    	$this->assertEquals(array('one', 'two'), $JS);
    }


    /**
     * @covers core_ET::push
     */
    public function testPushDeep()
    {
        $sub = new static::$tested('sub');
        $sub->push('sub', 'JS');

    	$this->simpleTpl->push('one', 'JS');
    	$this->simpleTpl->push('two', 'JS');

    	$this->simpleTpl->replace($sub, 'whatever');

    	$getArray = static::getMethod('getArray');

    	$JS = $getArray->invoke($this->simpleTpl, 'JS');

    	$this->assertEquals(array('one', 'two', 'sub'), $JS);
    }


    /**
     * @covers core_ET::append
     * @covers core_ET::replace
     */
    public function testBubling1() {
    	$sub = new static::$tested('sub');
		$sub->replace('{replace}', 'placeholder');

		$this->simpleTpl->append($sub);

		$this->assertEquals('Sample {replace} templatesub', (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::append
     */
    public function testBubbling2() {
    	$sub = new static::$tested('sub');
		$sub->append('{replace-1}', 'placeholder');
		$sub->append('{replace-2}', 'placeholder');

		$this->simpleTpl->append($sub, 'placeholder');

		$this->assertEquals('Sample {replace-1}{replace-2}sub template', (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::append
     */
    public function testBubblingNoSink() {
    	$sub = new static::$tested('sub[#sub#]');
		$sub->append('{append}', 'sub');

		$this->simpleTpl->append($sub, 'placeholder');
		$this->simpleTpl->append('{master}', 'sub');

		$this->assertEquals('Sample sub{append} template', (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::append
     */
    public function testBubbling4() {
    	$sub = new static::$tested('sub[#sub#]');

    	$this->simpleTpl->append($sub, 'placeholder');
		$this->simpleTpl->append('{master}', 'sub');

		$this->assertEquals('Sample sub{master} template', (string)$this->simpleTpl);
    }

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

		$this->assertEquals('master:sub1:{a}:sub2:{b}', (string)$master);
    }

    /**
     * @covers core_ET::append
     */
    public function testCrossAppend() {
    	$sub1   = new static::$tested('sub1:[#a#]');
    	$sub2   = new static::$tested('sub2:[#b#]');

    	$master = new static::$tested('master:[#sub1#]:[#sub2#]');

    	$sub1->append('{a-from-sub1}', 'a');
    	$sub1->append('{b-from-sub1}', 'b');

    	$sub2->append('{a-from-sub2}', 'a');
    	$sub2->append('{b-from-sub2}', 'b');

    	$master->replace($sub1, 'sub1');
    	$master->replace($sub2, 'sub2');

		$this->assertEquals('master:sub1:{a-from-sub1}:sub2:{b-from-sub2}', (string)$master);
    }

    /**
     * @covers core_ET::output
     */
    public function testOutput()
    {
        $this->expectOutputString('Sample  template');
        $this->simpleTpl->output();
    }

    /**
     * @covers core_ET::output
     */
    public function testOutputParams()
    {
        $this->expectOutputString('Sample {placeholder} template');
        $this->simpleTpl->output('{placeholder}', 'placeholder');
    }

    /**
     * Блоковете, чиито имена са същите като на плейсхолдър вътре в тях, трябва да самоизчезват
     */
    public function testRemovableBlock() {
    	$tpl = new static::$tested('prefix<!--ET_BEGIN removable-->blockstart[#removable#]blockend<!--ET_END removable-->suffix');
    	$this->assertEquals('prefixsuffix', (string)$tpl);
    }

    /**
     * Каквато и да е субституция вътре в самоизчезващ блок възпира самоизчезването му.
     */
    public function testAppendSameInRemovableBlock()
    {
    	$tpl = new static::$tested('<prefix /><!--ET_BEGIN removable--><removable>[#removable#]</removable><!--ET_END removable--><suffix />');
    	$tpl->append('{removable}', 'removable');
    	$this->assertEquals('<prefix /><removable>{removable}</removable><suffix />', (string)$tpl);
    }

    public function testAppendOtherInRemovableBlock()
    {
    	$tpl = new static::$tested('<prefix /><!--ET_BEGIN removable--><removable>[#other#][#removable#]</removable><!--ET_END removable--><suffix />');
    	$tpl->append('{other}', 'other');
    	$this->assertEquals('<prefix /><removable>{other}</removable><suffix />', (string)$tpl);
    }

    public function testAppendMissingInRemovableBlock()
    {
    	$tpl = new static::$tested('<prefix /><!--ET_BEGIN removable--><removable>[#other#][#removable#]</removable><!--ET_END removable--><suffix />');
    	$tpl->append('{missing}', 'missing');
    	$this->assertEquals('<prefix /><suffix />', (string)$tpl);
    }

    public function testAppendNullInRemovableBlock()
    {
    	$tpl = new static::$tested('<prefix /><!--ET_BEGIN removable--><removable>[#other#][#removable#]</removable><!--ET_END removable--><suffix />');
    	$tpl->append(NULL, 'removable');
    	$this->assertEquals('<prefix /><suffix />', (string)$tpl);
    }


    public function testDeepRemovableBlock()
    {
        $layout = new static::$tested('[#FORM#]');
        $form   = new static::$tested('<form <!--ET_BEGIN ON_SUBMIT-->onsubmit="[#ON_SUBMIT#]"<!--ET_END ON_SUBMIT-->>');

        $layout->append($form, 'FORM');
        $layout->replace('{ON_SUBMIT}', 'ON_SUBMIT');

        $this->assertEquals('<form onsubmit="{ON_SUBMIT}">', (string)$layout);
    }


    public function testDeepRemovableBlock1()
    {
        $page    = new static::$tested('<!--ET_BEGIN PAGE_CONTENT-->[#PAGE_CONTENT#]<!--ET_END PAGE_CONTENT-->');
        $layout  = new static::$tested('<layout>[#PAGE_CONTENT#]</layout>');
        $content = new static::$tested('<content>This is content</content>');

        $page->replace($layout, 'PAGE_CONTENT');
        $page->replace($content, 'PAGE_CONTENT');

        $this->assertEquals('<layout><content>This is content</content></layout>', (string)$page);
    }


    /**
     * Блоковете, чиито имена са същите като на плейсхолдър извън тях, трябва да самоизчезват
     */
    public function testFakeRemovableBlock() {
    	$tpl = new static::$tested('prefix[#removable#]<!--ET_BEGIN removable-->blockstart[#var#]blockend<!--ET_END removable-->suffix');
    	$this->assertEquals('prefixsuffix', (string)$tpl);
    }

    /**
     * Блоковете, за които няма плейсхолдър със същото име, трябва да останат в резултата
     */
    public function testSolidBlock() {
    	$tpl = new static::$tested('prefix<!--ET_BEGIN solid-->blockstart[#removable#]blockend<!--ET_END removable-->suffix');
    	$this->assertEquals('prefix<!--ET_BEGIN solid-->blockstartblockend<!--ET_END removable-->suffix', (string)$tpl);
    }

    /**
     * @covers core_ET::getBlock
     */
    public function testGetBlock()
    {
    	$block = $this->parentTpl->getBlock('block');
    	$this->assertTrue($block instanceof static::$tested);
    }

    /**
     * Плейсхолдърите, които никога не са били замествани не изчезват от резултата (removeBlocks = TRUE)
     *
     * @covers core_ET::getContent
     */
    public function testGetContentRemoveBlocks()
    {
        $result = $this->simpleTpl->getContent(NULL, "CONTENT", FALSE, TRUE);
        $this->assertEquals('Sample  template', $result);
    }

    /**
     * Плейсхолдърите, които никога не са били замествани не изчезват от резултата (removeBlocks = FALSE)
     *
     * @covers core_ET::getContent
     */
    public function testGetContentNotRemoveBlocks()
    {
        $result = $this->simpleTpl->getContent(NULL, "CONTENT", FALSE, FALSE);
        $this->assertEquals('Sample [#placeholder#] template', $result);
    }

    /**
     * @covers core_ET::placeObject
     */
    public function testPlaceObject()
    {
    	$this->simpleTpl->placeObject(
    		(object)array(
    			'placeholder' => '{object}'
    		)
    	);

    	$this->assertEquals('Sample {object} template', (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::placeArray
     */
    public function testPlaceArray()
    {
    	$this->simpleTpl->placeArray(
    		array(
    			'placeholder' => '{array}'
    		)
    	);

    	$this->assertEquals('Sample {array} template', (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::isPlaceholderExists
     */
    public function testIsPlaceholderExists()
    {
        $this->assertTrue($this->simpleTpl->isPlaceholderExists('placeholder'));
        $this->assertFalse($this->simpleTpl->isPlaceholderExists('nonexistent'));
        $this->assertFalse($this->simpleTpl->isPlaceholderExists(''));
        $this->assertTrue($this->parentTpl->isPlaceholderExists('row'));

        $this->simpleTpl->append('content', 'placeholder');
        $this->assertTrue($this->simpleTpl->isPlaceholderExists('placeholder'));

        $this->simpleTpl->prepend('content', 'placeholder');
        $this->assertTrue($this->simpleTpl->isPlaceholderExists('placeholder'));
    }

    /**
     * @covers core_ET::__toString
     * @todo   Implement test__toString().
     */
    public function test__toString()
    {
        $this->assertEquals($this->simpleTpl->getContent(), (string)$this->simpleTpl);
    }

    /**
     * @covers core_ET::append2Master
     * @todo   Implement testAppend2Master().
     */
    public function testAppend2Master()
    {
    	$block = $this->parentTpl->getBlock('block');

    	$this->assertInstanceOf(static::$tested, $block);

    	$rows = array(
    		array('row' => '{row-1}'),
    		array('row' => '{row-2}'),
    		array('row' => '{row-3}'),
    	);

    	foreach ($rows as $row) {
    		$block->placeArray($row);
    		$block->append2Master();
    	}

    	$this->assertEquals('before with {row-1}with {row-2}with {row-3} after', (string)$this->parentTpl);
    }


    public function testRecursiveReplace()
    {
        $t1 = new static::$tested('<t1:a>[#a#]</t1:a>');
        $t2 = new static::$tested('<t2:a>[#a#]</t2:a>');

        $t1->replace($t2, 'a');

        $this->assertEquals('<t1:a><t2:a></t2:a></t1:a>', (string)$t1);
    }


    public function testBug1()
    {
    	$tpl = new static::$tested('[#a#][#b#]');
    	$a   = new static::$tested('[#1#]', '{a}');
    	$b   = new static::$tested('[#1#]', '{b}');

    	$tpl->replace($a, 'a');
    	$tpl->append($b, 'b');

    	$this->assertEquals('{a}{b}', (string)$tpl);
    }


    /**
     * @param string $method
     * @return ReflectionMethod
     */
    protected static function getMethod($method)
    {
		$tested = new ReflectionClass(static::$tested);

		$method = $tested->getMethod($method);
		$method->setAccessible(true);

		return $method;
    }
}
