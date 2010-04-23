<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Ec2Test.php 20096 2010-01-06 02:05:09Z bkarwin $
 */

require_once dirname(__FILE__) . '/../../../../TestHelper.php';

require_once 'Zend/Service/Amazon/Ec2/ElasticLB.php';

require_once 'Zend/Service/Amazon/Ec2/ElasticLB/Listener.php';

require_once 'Zend/Http/Client/Adapter/Test.php';

/**
 * Zend_Service_Amazon_Ec2 test case.
 *
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Service
 * @group      Zend_Service_Amazon
 * @group      Zend_Service_Amazon_Ec2
 */
class Zend_Service_Amazon_Ec2_ElasticLBTest extends PHPUnit_Framework_TestCase
{
    /**
     * Load balancer instance
     *
     * @var Zend_Service_Amazon_Ec2_ElasticLB
     */
    protected $_elb =  null;
    
    /**
     * HTTP Test adapter
     * 
     * @var Zend_Http_Client_Adapter_Test
     */
    protected $_adapter = null;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_adapter = new Zend_Http_Client_Adapter_Test();
        $this->_elb = new Zend_Service_Amazon_Ec2_ElasticLB('access_key', 'secret_access_key');
        $this->_elb->getHttpClient()->setAdapter($this->_adapter);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->_adapter = null;
        $this->_elb = null;
        
        parent::tearDown();
    }
    
    /**
     * General Tests
     */

    /**
     * Test that an error response from Amazon throws an exception
     * 
     * @dataProvider errorResponseProvider
     */
    public function testErrorResponseThrowsException($response, $code, $msg)
    {
        $this->_adapter->setResponse($response);
        
        try {
            $this->_elb->describe();
            $this->fail("Was expecting an exception, but got none");
            
        } catch (Zend_Service_Amazon_Ec2_Exception $ex) {
            $this->assertEquals($code, $ex->getErrorCode());
            $this->assertEquals($msg, $ex->getMessage());
        }
    }
    
    /**
     * Create Load Balancer tests
     */

    /**
     * Test that passing an invalid availablity zone throws an exception
     * 
     * @param string $zone
     * @dataProvider invalidZoneProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testCreateLBInvalidAvailabilityZone($zone)
    {
        $this->_elb->create('validName', $zone, $this->_getValidListener());
    }

    /**
     * Test that passing an invalid name throws an exception
     * 
     * @param string $name
     * @dataProvider invalidNameProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testCreateLBInvalidName($name)
    {
        $this->_elb->create($name, 'eu-west-1a', $this->_getValidListener());
    }
    
    /**
     * Test that passing an invalid listener throws an exception
     * 
     * @param mixed $listener
     * @dataProvider invalidListenerProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testCreateLBInvalidListeners($listener)
    {
        $this->_elb->create('myLoadBalancer', 'eu-west-1a', $listener);
    }
    
    public function testCreateLoadBalancerInvalidResponse()
    {
        $this->markTestIncomplete("Not implemneted yet");
    }
    
    public function testCreateLoadBalancer()
    {
        $this->markTestIncomplete("Not implemneted yet");
    }
    
    /**
     * Data Providers
     */
    
    static public function invalidListenerProvider()
    {
        return array(
            array(''),
            array(null),
            array('HTTP'),
            array(array(80, 80, 'HTTP')),
            array(array()),
            array(80),
            array(new stdClass())
        );
    }
    
    static public function invalidZoneProvider()
    {
        return array(
            array(''),
            array(null),
            array('fake one'),
            array('eu-west-1'),
            array('eu-west-1g'),
            array('eu-east-1a'),
        );
    }
    
    static public function invalidNameProvider()
    {
        return array(
            array(''),
            array(null),
            array('my load balancer'),
            array('my.loadbalancer'),
            array('his_LoadBalancer'),
            array('-myLB'),
            array('myLb-'),
            array('my#Lb'),
            array('my/Lb'),
            array('veryveryveryveryveryveryveryverylongname')
        );
    }
    
    static public function errorResponseProvider()
    {
        return array(
            array(
                self::_createFakeResponse(
<<<ENDXML
<ErrorResponse xmlns="http://elasticloadbalancing.amazonaws.com/doc/2009-11-25/">
  <Error>
    <Type>Sender</Type>
    <Code>SignatureDoesNotMatch</Code>
    <Message>The request signature we calculated does not match the signature you provided. Check your AWS Secret Access Key and signing method. Consult the service documentation for details.</Message>
  </Error>
  <RequestId>cc77e3f2-4ecf-11df-9f81-21ac009b4e49</RequestId>
</ErrorResponse>

ENDXML
                ), 
                "SignatureDoesNotMatch",
                "The request signature we calculated does not match the signature you provided. Check your AWS Secret Access Key and signing method. Consult the service documentation for details."
            )
        );
    }
    
    /**
     * Helper functions
     */

    /**
     * Return a valid Listener object
     * 
     * @return Zend_Service_Amazon_Ec2_ElasticLB_Listener
     */
    protected function _getValidListener()
    {
        return new Zend_Service_Amazon_Ec2_ElasticLB_Listener(80, 80, 'HTTP');
    }
    
    /**
     * Create a fake Amazon EC2 HTTP response
     * 
     * @param  string $body
     * @return string
     */
    static protected function _createFakeResponse($body)
    {
        $response = "HTTP/1.1 200 OK\r\n" . 
                    "Date: " . date(DATE_RFC822) . "\r\n" . 
                    "Content-type: text/xml\r\n" . 
                    "Content-length: " . strlen($body) . "\r\n" . 
                    "X-amzn-requestid: cc77e3f2-4ecf-11df-9f81-21ac009b4e49\r\n" .
                    "\r\n" . 
                    $body;
                    
        return $response;
    }
}
