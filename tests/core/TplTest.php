<?php
require 'ETTestBase.php';

class core_TplTest extends core_ETTestBase
{
	protected static $tested = 'core_Tpl';


    /**
     * @covers core_Tpl::append
     * @covers core_Tpl::prepend
     */
    public function testAppendPrepend()
    {
    	$this->simpleTpl->append('{append}', 'placeholder');
    	$this->simpleTpl->prepend('{prepend}', 'placeholder');
        
        $this->assertEquals("Sample {prepend}{append} template", (string)$this->simpleTpl);
    }

    /**
     * @covers core_Tpl::append
     * @covers core_Tpl::replace
     */
    public function testAppendReplace()
    {
        $this->simpleTpl->append('{append}', 'placeholder');
        $this->simpleTpl->replace('{replace}', 'placeholder');
        
        $this->assertEquals("Sample {replace}{append} template", (string)$this->simpleTpl);
    }
    

    /**
     * @covers core_Tpl::append
     */
    public function testBubblingNoSink() {
    	$sub = new static::$tested('sub[#sub#]');
    	$sub->append('{append}', 'sub');
    
    	$this->simpleTpl->append($sub, 'placeholder');
    	$this->simpleTpl->append('{master}', 'sub');
    
    	$this->assertEquals('Sample sub{append}{master} template', (string)$this->simpleTpl);
    }
    
    
    /**
     * @covers core_Tpl::append
     * @covers core_Tpl::replace
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
    	 
		$this->assertEquals('master:sub1:{a-from-sub1}{a-from-sub2}:sub2:{b-from-sub1}{b-from-sub2}', (string)$master);
    }
    

    /**
     * @covers core_Tpl::isPlaceholderExists
     */
    public function testIsPlaceholderExists()
    {
        parent::testIsPlaceholderExists();

        // След replace плейсхолдърът остава
        $this->simpleTpl->replace('content', 'placeholder');
        $this->assertTrue($this->simpleTpl->isPlaceholderExists('placeholder'));
    }
}
