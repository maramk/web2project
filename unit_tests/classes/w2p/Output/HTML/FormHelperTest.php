<?php
/**
 * Class for testing HTML FormHelper functionality
 *
 * Many of the tests are quite similar with the only difference being the field
 *   names being tested. The duplication is on purpose because the formatting is
 *   vitally important to various parts of the system. If the formatting on a
 *   field changes, we need to know about it.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to Clear BSD License. Please see the
 *   LICENSE file in root of site for further details
 *
 * @author      D. Keith Casey, Jr.<contrib@caseysoftware.com>
 * @category    w2p_Output_HTMLHelper
 * @package     web2project
 * @subpackage  unit_tests
 * @license     Clear BSD
 * @link        http://www.web2project.net
 */

// NOTE: This path is relative to Phing's build.xml, not this test.
include_once 'unit_tests/CommonSetup.php';

class w2p_Output_HTML_FormHelperTest extends CommonSetup
{
    protected function setUp ()
    {
        parent::setUp();

        $this->obj = new w2p_Output_HTML_FormHelper($this->_AppUI);
    }

    public function testAddLabel()
    {
        $this->assertEquals('<label>Project:</label>', $this->obj->addLabel('Project'));
    }

    public function testAddCancelButton()
    {
        $this->assertEquals('<input type="button" value="back" class="cancel button btn btn-danger" onclick="javascript:history.back(-1);" />', $this->obj->addCancelButton());
    }

    public function testAddSaveButton()
    {
        $this->assertEquals('<input type="button" value="save" class="save button btn btn-primary" onclick="submitIt()" />', $this->obj->addSaveButton());
    }

    public function testAddNonce()
    {
        $this->assertGreaterThan(0, strpos($this->obj->addNonce(), '__nonce'));
    }

    public function testAddField()
    {

    }
}