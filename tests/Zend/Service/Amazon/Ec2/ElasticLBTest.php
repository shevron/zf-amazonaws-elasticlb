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
     * "Real" access key, if on-line tests are enabled
     * 
     * @var null|string
     */
    static protected $_onlineAK = null;
    
    /**
     * "Real" secret key, if on-line tests are enabled
     * 
     * @var null|string
     */
    static protected $_onlineSK = null;
    
    /**
     * Region for on-line tests
     * 
     * @todo Should this be configurable?
     * @var string
     */
    static protected $_onlineRegion = 'eu-west-1';
    
    static public function setUpBeforeClass()
    {
        if (defined('TESTS_ZEND_SERVICE_AMAZON_ONLINE_ENABLED') && 
            TESTS_ZEND_SERVICE_AMAZON_ONLINE_ENABLED) {

            self::$_onlineAK = TESTS_ZEND_SERVICE_AMAZON_ONLINE_ACCESSKEYID;
            self::$_onlineSK = TESTS_ZEND_SERVICE_AMAZON_ONLINE_SECRETKEY;
        }
    }
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_adapter = new Zend_Http_Client_Adapter_Test();
        $this->_elb = new Zend_Service_Amazon_Ec2_ElasticLB(
            'access_key', 
            'secret_access_key',
            'us-east-1'
        );
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
     * Make sure an exception is thrown if the region is not set somehow
     * 
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testNotSettingRegionThrowsException()
    {
        $elb = new Zend_Service_Amazon_Ec2_ElasticLB('accessKey', 'secretKey');
    }
    
    /**
     * Create Load Balancer tests
     */

    /**
     * Test that passing an invalid availablity zone throws an exception
     * 
     * @param string $zone
     * @param string $region
     * 
     * @dataProvider invalidZoneProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testCreateLBInvalidAvailabilityZone($zone, $region = 'us-east-1')
    {
        Zend_Service_Amazon_Ec2_ElasticLB::setRegion($region);
        $elb = new Zend_Service_Amazon_Ec2_ElasticLB('accessKey', 'secretKey');
        $elb->getHttpClient()->setAdapter($this->_adapter);
        $elb->create('validName', $zone, $this->_getValidListener());        
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
        $this->_elb->create($name, 'us-east-1a', self::_getValidListener());
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
        $this->_elb->create('myLoadBalancer', 'us-east-1a', $listener);
    }
    
    /**
     * Test that all is OK when valid input is provided
     * 
     * @param string                                           $name
     * @param string|array                                     $zone
     * @param Zend_Service_Amazon_Ec2_ElasticLB_Listener|array $listener
     * 
     * @dataProvider validCreateLoadBalancerProvider
     */
    public function testCreateLoadBalancerValidInput($name, $zone, $listener)
    {
        $this->_adapter->setResponse($this->_createFakeResponse(
<<<ENDXML
<CreateLoadBalancerResponse xmlns="http://elasticloadbalancing.amazonaws.com/doc/2009-11-25/">
  <CreateLoadBalancerResult>
    <DNSName>testLoadBalancer-1962097299.eu-west-1.elb.amazonaws.com</DNSName>
  </CreateLoadBalancerResult>
  <ResponseMetadata>
    <RequestId>7cfd29f0-4efe-11df-9f81-21ac009b4e49</RequestId>
  </ResponseMetadata>
</CreateLoadBalancerResponse>
ENDXML
        ));
        try {
            $this->_elb->create($name, $zone, $listener);
        } catch (Zend_Service_Amazon_Ec2_Exception $ex) {
            $this->fail("Unexpected exception: $ex");
        }
        
        $request = $this->_elb->getHttpClient()->getLastRequest();
        $this->assertContains('Action=CreateLoadBalancer', $request);
        $this->assertContains('LoadBalancerName=' . urlencode($name), $request);
    }
    
    /**
     * Make sure an exception is thrown if we get an unexpected response
     * 
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testCreateLoadBalancerInvalidResponse()
    {
        $this->_adapter->setResponse($this->_createFakeResponse(
<<<ENDXML
<DeleteLoadBalancerResponse xmlns="http://elasticloadbalancing.amazonaws.com/doc/2009-11-25/">
  <CreateLoadBalancerResult>
    <DNSName>lbj-1962097299.eu-west-1.elb.amazonaws.com</DNSName>
  </CreateLoadBalancerResult>
  <ResponseMetadata>
    <RequestId>7cfd29f0-4efe-11df-9f81-21ac009b4e49</RequestId>
  </ResponseMetadata>
</DeleteLoadBalancerResponse>
ENDXML
        ));
        
        $response = $this->_elb->create('lbj', 'us-east-1a', self::_getValidListener());
    }
    
    public function testCreateLoadBalancerValidResponse()
    {
        $name = 'validLbName';
        
        $this->_adapter->setResponse($this->_createFakeResponse(
<<<ENDXML
<CreateLoadBalancerResponse xmlns="http://elasticloadbalancing.amazonaws.com/doc/2009-11-25/">
  <CreateLoadBalancerResult>
    <DNSName>$name-1962097299.eu-west-1.elb.amazonaws.com</DNSName>
  </CreateLoadBalancerResult>
  <ResponseMetadata>
    <RequestId>7cfd29f0-4efe-11df-9f81-21ac009b4e49</RequestId>
  </ResponseMetadata>
</CreateLoadBalancerResponse>
ENDXML
        ));
        
        $response = $this->_elb->create($name, 'us-east-1a', self::_getValidListener());
        
        $this->assertRegExp("/^$name-/", $response['DNSName']);
    }
    
    /**
     * Delete load balancer tests 
     */
    
    /**
     * Test that passing an invalid name throws an exception
     * 
     * @param string $name
     * @dataProvider invalidNameProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testDeleteLBInvalidName($name)
    {
        $this->_elb->delete($name);
    }
    
    public function testDeleteLoadBalancer()
    {
        $this->_adapter->setResponse(self::_createFakeResponse(
<<<ENDXML
<?xml version="1.0"?>
<DeleteLoadBalancerResponse xmlns="http://elasticloadbalancing.amazonaws.com/doc/2009-11-25/">
  <DeleteLoadBalancerResult/>
  <ResponseMetadata>
    <RequestId>4fb428a4-4f02-11df-9f81-21ac009b4e49</RequestId>
  </ResponseMetadata>
</DeleteLoadBalancerResponse>
ENDXML
        ));
        
        $this->assertTrue($this->_elb->delete('testLoadBalancer'));
    }
    
    /**
     * Data Providers
     */
    
    static public function validCreateLoadBalancerProvider()
    {
        return array(
            array('myLoadBalancer', 'us-east-1a', self::_getValidListener()),
            array('myLoadBalancer', 'us-east-1a', array(self::_getValidListener())),
            array('myLoadBalancer', 'us-east-1b', array(self::_getValidListener())),
            array('myLoadBalancer', array('us-east-1b'), self::_getValidListener()),
            array('other-loadbalancer', array('us-east-1b'), self::_getValidListener()),
            array('l', array('us-east-1b'), self::_getValidListener()),
            array('lb', array('us-east-1b'), self::_getValidListener()),
            array('l-b', 'us-east-1b', self::_getValidListener()),
        );         
    }
    
    static public function invalidListenerProvider()
    {
        return array(
            array(''),
            array(null),
            array('HTTP'),
            array(array(self::_getValidListener(), 'HTTP')),
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
            array(array()),
            array(array('eu-east-1a', 'bogus')),
            array(null),
            array('fake one'),
            array('eu-west-1'),
            array('eu-west-1g'),
            array('eu-east-1a'),
            array('us-west-1a', 'us-east-1'),
            array('us-east-1a', 'eu-west-1'),
        );
    }
    
    static public function invalidNameProvider()
    {
        return array(
            array(''),
            array(null),
            array(array('name')),
            array('my load balancer'),
            array('my.loadbalancer'),
            array('his_LoadBalancer'),
            array('-myLB'),
            array('myLb-'),
            array('my#Lb'),
            array('my/Lb'),
            array('veryveryveryveryveryveryveryverylongname'),
            array('לואד-בלנסר')
            
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
     * IF on-line tests are enabled, return a configured instance for on-line tests
     * Otherwise will return null
     *
     * @return Zend_Service_Amazon_Ec2_ElasticLB|null
     */
    static protected function _getOnLineInstance()
    {
        $elb = null;
        
        if (self::$_onlineAK && self::$_onlineSK) {
            $elb = new Zend_Service_Amazon_Ec2_ElasticLB(
                self::$_onlineAK, self::$_onlineSK, self::$_onlineRegion
            );
             
            $elb->setHttpClient(new Zend_Http_Client());
        }
        
        return $elb;
    }
    
    /**
     * Return a valid Listener object
     * 
     * @return Zend_Service_Amazon_Ec2_ElasticLB_Listener
     */
    static protected function _getValidListener()
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
