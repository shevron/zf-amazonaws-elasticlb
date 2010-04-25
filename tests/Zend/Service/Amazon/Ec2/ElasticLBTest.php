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
 * @group      Zend_Service_Amazon_Ec2_ElasticLB
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
        $this->_adapter->setResponse(self::_createResponseFromFile('response-create-01.xml'));
        
        try {
            $this->_elb->create($name, $zone, $listener);
        } catch (Zend_Service_Amazon_Ec2_Exception $ex) {
            $this->fail("Unexpected exception: $ex");
        }
        
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        $this->assertArrayHasKey('Action', $params);
        $this->assertEquals('CreateLoadBalancer', $params['Action']);
        $this->assertArrayHasKey('LoadBalancerName', $params);
        $this->assertEquals($name, $params['LoadBalancerName']);
    }
    
    /**
     * Make sure an exception is thrown if we get an unexpected response
     * 
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testCreateLoadBalancerInvalidResponse()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-delete-01.xml'));
        $response = $this->_elb->create('lbj', 'us-east-1a', self::_getValidListener());
    }
    
    public function testCreateLoadBalancerValidResponse()
    {
        $name = 'testLoadBalancer';        
        $this->_adapter->setResponse(self::_createResponseFromFile('response-create-01.xml'));
        
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
        $name = 'testLoadBalancer';
        $this->_adapter->setResponse(self::_createResponseFromFile("response-delete-01.xml"));
        $this->assertTrue($this->_elb->delete($name));
        
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        $this->assertEquals('DeleteLoadBalancer', $params['Action']);
        $this->assertEquals($name, $params['LoadBalancerName']);
    }
    
    /**
     * registerInstance tests
     */
    
    /**
     * Test that an exception is thrown if invalid name is passed here
     * 
     * @param string $name
     * @dataProvider invalidNameProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testRegisterInstancesInvalidName($name)
    {
        $this->_elb->registerInstances($name, 'i-a12345');        
    }
    
    /**
     * Test that an exception is thrown if invalid instance ID is passed
     * 
     * @param string $instanceId
     * @dataProvider invalidInstanceIdProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testRegisterInstancesInvalidInstanceId($instanceId)
    {
        $this->_elb->registerInstances('mylb', $instanceId);        
    }
    
    public function testRegisterInstancesReturnsInstaceIds()
    {
        $instanceIds = array('i-a2b10ed5', 'i-a0b10ed7');
        $this->_adapter->setResponse(self::_createResponseFromFile('response-registerinstance-01.xml'));
        
        $result = $this->_elb->registerInstances('myLb', $instanceIds);
        
        $this->assertEquals(array('Instances' => $instanceIds), $result);
    }
    
    /**
     * deregisterInstance tests
     */
    
    /**
     * Test that an exception is thrown if invalid name is passed here
     * 
     * @param string $name
     * @dataProvider invalidNameProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testDeregisterInstancesInvalidName($name)
    {
        $this->_elb->deregisterInstances($name, 'i-a12345');        
    }
    
    /**
     * Test that an exception is thrown if invalid instance ID is passed
     * 
     * @param string $instanceId
     * @dataProvider invalidInstanceIdProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testDeregisterInstancesInvalidInstanceId($instanceId)
    {
        $this->_elb->deregisterInstances('mylb', $instanceId);        
    }
    
    public function testDeregisterInstancesProperParams()
    {
        $instanceIds = array('i-a2b10ed5', 'i-a0b10ed7');
        $name = 'myLb';
        
        $this->_adapter->setResponse(self::_createResponseFromFile('response-deregisterinstance-01.xml'));
        $this->_elb->deregisterInstances($name, $instanceIds);
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        $this->assertEquals('DeregisterInstancesFromLoadBalancer', $params['Action']);
        $this->assertEquals($name, $params['LoadBalancerName']);
        foreach($instanceIds as $i => $instId) {
            $key = 'Instances.member.' . ++$i . '.InstanceId';
            $this->assertArrayHasKey($key, $params);
            $this->assertEquals($instId, $params[$key]);
        }
    }
    
    public function testDeregisterInstancesReturnsInstaceIds()
    {
        $instanceIds = array('i-a2b10ed5', 'i-a0b10ed7');
        $this->_adapter->setResponse(self::_createResponseFromFile('response-deregisterinstance-01.xml'));
        
        $result = $this->_elb->deregisterInstances('myLb', $instanceIds);
        
        $this->assertEquals(array('Instances' => $instanceIds), $result);
    }
    
    /**
     * describe() method tests
     */

    /**
     * Test that an exception is thrown if invalid name is passed here
     * 
     * @param string|array $name
     * @dataProvider invalidNamesForDescribeProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testDescribeInvalidLoadBalancerNames($name)
    {
        $this->_elb->describe($name);
    }
    
    /**
     * Check that the describe request is sent with no load balancer names if 
     * no names are provided
     * 
     */
    public function testDescribeNoLoadBalancers()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-describe-01.xml'));
        $this->_elb->describe();
        
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        $this->assertArrayNotHasKey('LoadBalancerNames.member.1', $params);
    }
    
    public function testDescribeSingleLoadBalancer()
    {
        $lbName = 'myLoadBalancer';
        $this->_adapter->setResponse(self::_createResponseFromFile('response-describe-01.xml'));

        $this->_elb->describe($lbName);
        
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        $this->assertArrayHasKey('LoadBalancerNames.member.1', $params);
        $this->assertEquals($lbName, $params['LoadBalancerNames.member.1']);
    }
    
    public function testDescribeMultipleLoadBalancers()
    {
        $lbNames = array('myLoadBalancer', 'anotherLoadBalancer', '3rdLb');
        $this->_adapter->setResponse(self::_createResponseFromFile('response-describe-01.xml'));

        $this->_elb->describe($lbNames);
        
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        foreach($lbNames as $k => $name) {
            $key = 'LoadBalancerNames.member.' . ($k + 1);
            $this->assertArrayHasKey($key, $params);
            $this->assertEquals($name, $params[$key]);
        }
    }
    
    /**
     * Check that the response contains an array for each LB with the right keys
     * 
     */
    public function testDescribeWithMultipleLoadBalancers()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-describe-01.xml'));
        $keys = array(
            'LoadBalancerName', 'AvailabilityZones', 'CreatedTime', 'DNSName', 
            'Instances', 'HealthCheck', 'ListenerDescriptions', 'Policies'
        );
        
        $response = $this->_elb->describe();
        
        // Check we have 2 load balancers describe
        $this->assertEquals(2, count($response));
        foreach($response as $lbDesc) {
            foreach($keys as $key) {
                $this->assertArrayHasKey($key, $lbDesc);
            }
        }
    }
    
    public function checkDescribeReturnsCorrectStringParams()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-describe-01.xml'));
        $lbs = array(
            'my-load-balancer' => array(
                'LoadBalancerName' => 'my-load-balancer',
                'CreatedTime'      => '2010-04-24T11:17:46.240Z',
                'DNSName'          => 'my-load-balancer-328577524.eu-west-1.elb.amazonaws.com',
            ),
            'myLoadBalancer'   => array(
                'LoadBalancerName' => 'myLoadBalancer',
                'CreatedTime'      => '2010-04-24T11:07:34.040Z',
                'DNSName'          => 'myLoadBalancer-1547734986.eu-west-1.elb.amazonaws.com',
            )
        );
        
        $response = $this->_elb->describe();
        
        // Check we have 2 load balancers describe
        foreach($response as $key => $lbDesc) {
            $this->assertArrayHasKey($key, $lbs);
            foreach($lbs[$key] as $param => $value) {
                $this->assertEquals($value, $lbDesc[$param]);
            }
        }
    }
    
    public function testDescribeReturnsCorrectAvailabilityZones()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-describe-01.xml'));
        $lbs = array(
            'my-load-balancer' => array('eu-west-1a'),
            'myLoadBalancer'   => array('eu-west-1a', 'eu-west-1b')
        );
        
        $response = $this->_elb->describe();
        
        // Check we have 2 load balancers describe
        foreach($response as $key => $lbDesc) {
            $this->assertArrayHasKey($key, $lbs);
            $this->assertEquals($lbs[$key], $lbDesc['AvailabilityZones']);
        }
    }
    
    public function testDescribeReturnsCorrectInstances()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-describe-01.xml'));
        $lbs = array(
            'my-load-balancer' => array(),
            'myLoadBalancer'   => array('i-1053ec67', 'i-2e53ec59')
        );
        
        $response = $this->_elb->describe();
        
        // Check we have 2 load balancers describe
        foreach($response as $key => $lbDesc) {
            $this->assertArrayHasKey($key, $lbs);
            $this->assertEquals($lbs[$key], $lbDesc['Instances']);
        }
    }
    
    public function testDescribeReturnsCorrectHealthInfo()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-describe-01.xml'));
        $lbs = array(
            'my-load-balancer' => array(
                'Interval'           => 30,
                'Target'             => 'TCP:443',
                'HealthyThreshold'   => 5,
                'Timeout'            => 5,
                'UnhealthyThreshold' => 3
            ),
            'myLoadBalancer'   => array(
                'Interval'           => 30,
                'Target'             => 'TCP:80',
                'HealthyThreshold'   => 10,
                'Timeout'            => 5,
                'UnhealthyThreshold' => 2
            )
        );
        
        $response = $this->_elb->describe();
        
        // Check we have 2 load balancers describe
        foreach($response as $key => $lbDesc) {
            $this->assertArrayHasKey($key, $lbs);
            $this->assertEquals($lbs[$key], $lbDesc['HealthCheck']);
        }
    }
    
    public function testDescribeReturnsCorrectListenerDescriptionNoPolicy()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-describe-01.xml'));
        $lbs = array(
            'my-load-balancer' => array(
                array(
                    'PolicyNames' => array(),
                    'Listener'    => new Zend_Service_Amazon_Ec2_ElasticLB_Listener(80, 80, 'HTTP')
                ),
                array(
                    'PolicyNames' => array(),
                    'Listener'    => new Zend_Service_Amazon_Ec2_ElasticLB_Listener(443, 443, 'TCP')
                )
            ),
            'myLoadBalancer'   => array(
                array(
                    'PolicyNames' => array(),
                    'Listener'    => new Zend_Service_Amazon_Ec2_ElasticLB_Listener(80, 8080, 'HTTP')
                )
            )
        );
        
        $response = $this->_elb->describe();
        
        // Check we have 2 load balancers describe
        foreach($response as $key => $lbDesc) {
            $this->assertArrayHasKey($key, $lbs);
            $this->assertEquals($lbs[$key], $lbDesc['ListenerDescriptions']);
        }
    }
    
    public function testDescribeReturnsCorrectListenerDescriptionWithPolicy()
    {
        $this->markTestIncomplete();
    }
    
    public function testDescribeWithAppCookiePolicy()
    {
        $this->markTestIncomplete();
    }
    
    public function testDescribeWithLBCookiePolicy()
    {
        $this->markTestIncomplete();
    }
    
    /**
     * enableAvailabilityZones tests
     */
    
    /**
     * Test that an exception is thrown if passing an invalid LB name
     * 
     * @param string $name
     * @dataProvider invalidNameProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testEnableZonesInvalidLBName($name)
    {
        $this->_elb->enableAvailabilityZones($name, 'us-east-1');
    }
    
    /**
     * Test that passing an invalid availablity zone throws an exception
     * 
     * @param string $zone
     * @param string $region
     * 
     * @dataProvider invalidZoneProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testEnableZonesInvalidZones($zone, $region = 'us-east-1')
    {
        Zend_Service_Amazon_Ec2_ElasticLB::setRegion($region);
        $elb = new Zend_Service_Amazon_Ec2_ElasticLB('accessKey', 'secretKey');
        $elb->getHttpClient()->setAdapter($this->_adapter);
        $elb->enableAvailabilityZones('myloadbalancer', $zone);        
    }
    
    public function testEnableZonesSendsCorrectParams()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-enablezones-01.xml'));
        
        $this->_elb->enableAvailabilityZones('myLoadBalancer', 'us-east-1b');
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        $this->assertEquals('EnableAvailabilityZonesForLoadBalancer', $params['Action']);
        $this->assertEquals('myLoadBalancer', $params['LoadBalancerName']);
    }
    
    public function testEnableZonesSendsSingleZoneParam()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-enablezones-01.xml'));
        
        $this->_elb->enableAvailabilityZones('myLoadBalancer', 'us-east-1b');
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        $this->assertArrayHasKey('AvailabilityZones.member.1', $params);
        $this->assertEquals('us-east-1b', $params['AvailabilityZones.member.1']);
    }
    
    public function testEnableZonesSendsMultiZoneParam()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-enablezones-01.xml'));
        
        $zones = array('us-east-1b', 'us-east-1a', 'us-east-1d');
        $this->_elb->enableAvailabilityZones('myLoadBalancer', $zones);
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        foreach($zones as $k => $zone) {
            $this->assertArrayHasKey('AvailabilityZones.member.' . ++$k, $params);
            $this->assertEquals($zone, $params['AvailabilityZones.member.' . $k]);
        }
    }
    
    public function testEnableZonesReturnsZonesArray()
    {
        $zones = array('us-east-1c', 'us-east-1b', 'us-east-1a');
        $this->_adapter->setResponse(self::_createResponseFromFile('response-enablezones-01.xml'));
        
        $response = $this->_elb->enableAvailabilityZones('myLoadBalancer', 'us-east-1a');
        
        $this->assertArrayHasKey('AvailabilityZones', $response);
        $this->assertEquals($zones, $response['AvailabilityZones']);
    }
    
    /**
     * Check that recieving an unexpected response type throws an exception
     * 
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testEnableZonesInvalidResponseType()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-disablezones-01.xml'));
        $this->_elb->enableAvailabilityZones('myLoadBalancer', 'us-east-1b');
    } 
    
    /**
     * disableAvailabilityZones tests
     */
    
        /**
     * Test that an exception is thrown if passing an invalid LB name
     * 
     * @param string $name
     * @dataProvider invalidNameProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testDisableZonesInvalidLBName($name)
    {
        $this->_elb->disableAvailabilityZones($name, 'us-east-1');
    }
    
    /**
     * Test that passing an invalid availablity zone throws an exception
     * 
     * @param string $zone
     * @param string $region
     * 
     * @dataProvider invalidZoneProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testDisableZonesInvalidZones($zone, $region = 'us-east-1')
    {
        Zend_Service_Amazon_Ec2_ElasticLB::setRegion($region);
        $elb = new Zend_Service_Amazon_Ec2_ElasticLB('accessKey', 'secretKey');
        $elb->getHttpClient()->setAdapter($this->_adapter);
        $elb->disableAvailabilityZones('myloadbalancer', $zone);        
    }
    
    public function testDisableZonesSendsCorrectParams()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-disablezones-01.xml'));
        
        $this->_elb->disableAvailabilityZones('myLoadBalancer', 'us-east-1b');
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        $this->assertEquals('DisableAvailabilityZonesForLoadBalancer', $params['Action']);
        $this->assertEquals('myLoadBalancer', $params['LoadBalancerName']);
    }
    
    public function testDisableZonesSendsSingleZoneParam()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-disablezones-01.xml'));
        
        $this->_elb->disableAvailabilityZones('myLoadBalancer', 'us-east-1b');
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        $this->assertArrayHasKey('AvailabilityZones.member.1', $params);
        $this->assertEquals('us-east-1b', $params['AvailabilityZones.member.1']);
    }
    
    public function testDisableZonesSendsMultiZoneParam()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-disablezones-01.xml'));
        
        $zones = array('us-east-1b', 'us-east-1a', 'us-east-1d');
        $this->_elb->disableAvailabilityZones('myLoadBalancer', $zones);
        $params = self::_getRequestParams($this->_elb->getHttpClient()->getLastRequest());
        
        foreach($zones as $k => $zone) {
            $this->assertArrayHasKey('AvailabilityZones.member.' . ++$k, $params);
            $this->assertEquals($zone, $params['AvailabilityZones.member.' . $k]);
        }
    }
    
    public function testDisableZonesReturnsZonesArray()
    {
        $zones = array('us-east-1c', 'us-east-1b');
        $this->_adapter->setResponse(self::_createResponseFromFile('response-disablezones-01.xml'));
        
        $response = $this->_elb->disableAvailabilityZones('myLoadBalancer', 'us-east-1c');
        
        $this->assertArrayHasKey('AvailabilityZones', $response);
        $this->assertEquals($zones, $response['AvailabilityZones']);
    }
    
    /**
     * Check that recieving an unexpected response type throws an exception
     * 
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testDisableZonesInvalidResponseType()
    {
        $this->_adapter->setResponse(self::_createResponseFromFile('response-enablezones-01.xml'));
        $this->_elb->disableAvailabilityZones('myLoadBalancer', 'us-east-1b');
    }
    
    /**
     * Data Providers
     */
    
    static public function invalidInstanceIdProvider()
    {
        return array(
            array(array()),
            array(''),
            array('  '),
            array(array('i-1235', null)),
            array(null),
        );
    }
    
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
            array('l-'),
            array('-b'),
            array('veryveryveryveryveryveryveryverylongname'),
            array('לואד-בלנסר')
            
        );
    }
    
    static public function invalidNamesForDescribeProvider()
    {
        $data = self::invalidNameProvider();
        foreach($data as $k => $v) {
            if (! is_string($v[0])) unset($data[$k]);
        }
        
        $moreData = array(
            array(false),
            array(array('goodName', 'bad name')),
            array(array()),
            array(array('mylb-'))
        );
        
        return array_merge($data, $moreData); 
    }
    
    static public function errorResponseProvider()
    {
        return array(
            array(
                self::_createResponseFromFile('errorresponse-01.xml'),
                "SignatureDoesNotMatch",
                "The request signature we calculated does not match the signature you provided. " . 
                    "Check your AWS Secret Access Key and signing method. Consult the service documentation for details."
            )
        );
    }
    
    /**
     * Helper functions
     */

    /**
     * Extract the array of POST parameters from an HTTP request and return it
     * 
     * @param   string $request HTTP request
     * @returns array
     */
    static protected function _getRequestParams($request)
    {
        list($headers, $body) = explode("\r\n\r\n", $request, 2);
        
        // parse_str replaces '.' with '_', so we will implement our own 
        // x-form-urlencoded parsing code 
        $params = array();
        $pairs = explode('&', $body);
        foreach($pairs as $pair) {
            list($key, $value) = explode('=', $pair);
            $params[urldecode($key)] = urldecode($value);
        }
        
        return $params;
    }
    
    /**
     * If on-line tests are enabled, return a configured instance for on-line tests
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
    static protected function _createResponse($body)
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
    
    /**
     * Create a fake Amazon EC2 HTTP response from file contents
     * 
     * @param  string $file
     * @return string
     */
    static protected function _createResponseFromFile($file)
    {
        return self::_createResponse(self::_getTestFile($file));
    }
    
    /**
     * Get the contents of one of the files under _files
     * 
     * @param  string $file
     * @return string File content
     */
    static protected function _getTestFile($file)
    {
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . 
                                    DIRECTORY_SEPARATOR . $file;

        return file_get_contents($file);
    }
}
