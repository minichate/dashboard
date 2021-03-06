<?php

require_once 'PHPUnit/Framework.php';

require_once 'Address.php';

/**
 * Test class for Address.
 * Generated by PHPUnit on 2008-04-27 at 19:56:33.
 */
class AddressTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    Address
     * @access protected
     */
    protected $object;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('AddressTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->object = Address::make();
        
        $this->object->setCity('Halifax');
        $this->object->setCountry(31);
        $this->object->setState(7);
        $this->object->setStreetAddress('102 Chain Lake Drive');
        $this->object->setPostalCode('B3S 1A7');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
    	$this->object->delete();
    }

    /**
     * @todo Implement testGetStreetAddress().
     */
    public function testGetStreetAddress() {
        $this->assertEquals($this->object->getStreetAddress(), '102 Chain Lake Drive');
    }

    /**
     * @todo Implement testGetCity().
     */
    public function testGetCity() {
        $this->assertEquals($this->object->getCity(), 'Halifax');
    }

    /**
     * @todo Implement testGetState().
     */
    public function testGetState() {
        $this->assertEquals($this->object->getState(), 7);
    }

    /**
     * @todo Implement testGetPostalCode().
     */
    public function testGetPostalCode() {
        $this->assertEquals($this->object->getPostalCode(), 'B3S 1A7');
    }

    /**
     * @todo Implement testGetStateName().
     */
    public function testGetStateName() {
        $this->assertEquals($this->object->getStateName(), 'Nova Scotia');
    }

    /**
     * @todo Implement testGetCountryName().
     */
    public function testGetCountryName() {
        $this->assertEquals($this->object->getCountryName(), 'Canada');
    }

    /**
     * @todo Implement testGetCountry().
     */
    public function testGetCountry() {
        $this->assertEquals($this->object->getCountry(), 31);
    }

    /**
     * @todo Implement testGetId().
     */
    public function testGetId() {
        $this->object->save();
        
        if (is_null($this->object->getId())) {
        	$this->fail();
        }
    }

	public function testGetStates() {
		if (!is_array($this->object->getStates())) {
			$this->fail();
		}
	}
	
	public function testGetCountries() {
		if (!is_array($this->object->getCountries())) {
			$this->fail();
		}
	}

    /**
     * @todo Implement testSetGeocode().
     */
    public function testSetGeocode() {
        $this->object->setGeocode('44.637593, -63.665898');
        $this->assertEquals($this->object->getGeocode(), '44.637593, -63.665898');
    }

    /**
     * @todo Implement testSetStreetAddress().
     */
    public function testSetStreetAddress() {
        $this->object->setStreetAddress('27 Langbrae Drive');
        $this->assertEquals($this->object->getStreetAddress(), '27 Langbrae Drive');
    }

    /**
     * @todo Implement testSetCity().
     */
    public function testSetCity() {
        $this->object->setCity('London');
        $this->assertEquals($this->object->getCity(), 'London');
    }

    /**
     * @todo Implement testSetPostalCode().
     */
    public function testSetPostalCode() {
        $this->object->setPostalCode('N6G 2W3');
        $this->assertEquals($this->object->getPostalCode(), 'N6G 2W3');
    }

    /**
     * @todo Implement testSetState().
     */
    public function testSetState() {
        $this->object->setState(8);
        $this->assertEquals($this->object->getState(), 8);
    }

    /**
     * @todo Implement testSetCountry().
     */
    public function testSetCountry() {
        $this->object->setCountry(8);
        $this->assertEquals($this->object->getCountry(), 8);
    }

    /**
     * @todo Implement testSave().
     */
    public function testSave() {
        $this->object->save();
        $id = $this->object->getId();
        
        $id = $this->object->getId();
        $obj = Address::make($id);
        
        $obj->save();
        $this->assertEquals($obj->getId(), $id);
        
    }

    /**
     * @todo Implement testDelete().
     */
    public function testDelete() {
    	$this->testSave();
    	$id = $this->object->getId();
    	
        $this->object->delete();
        $obj = Address::make($id);
        
        $this->assertEquals($obj, Address::make());
    }

    /**
     * @todo Implement testGetAddEditForm().
     */
    public function testGetAddEditForm() {
    	$this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
?>
