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
