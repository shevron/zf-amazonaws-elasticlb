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

require_once dirname(__FILE__) . '/../../../../../TestHelper.php';

require_once 'Zend/Service/Amazon/Ec2/ElasticLB/Listener.php';

require_once 'Zend/Http/Client/Adapter/Test.php';

/**
 * Zend_Service_Amazon_Ec2_ElasticLB_Listener test cases.
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
class Zend_Service_Amazon_Ec2_ElasticLB_ListenerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test that we can access the LB port
     * 
     * @param integer $lbport
     * @param integer $instanceport
     * @param string  $protocol
     * 
     * @dataProvider validParametersProvider
     */
    public function testValidParamsGetLbPort($lbport, $instanceport, $protocol)
    {
        $l = new Zend_Service_Amazon_Ec2_ElasticLB_Listener($lbport, $instanceport, $protocol);
        $this->assertEquals($lbport, $l->getLoadBalancerPort());
    }
    
    /**
     * Test that we can access the instance port
     * 
     * @param integer $lbport
     * @param integer $instanceport
     * @param string  $protocol
     * 
     * @dataProvider validParametersProvider
     */
    public function testValidParamsGetInstancePort($lbport, $instanceport, $protocol)
    {
        $l = new Zend_Service_Amazon_Ec2_ElasticLB_Listener($lbport, $instanceport, $protocol);
        $this->assertEquals($instanceport, $l->getInstancePort());
    }
    
    /**
     * Test that we can access the protocol
     * 
     * @param integer $lbport
     * @param integer $instanceport
     * @param string  $protocol
     * 
     * @dataProvider validParametersProvider
     */
    public function testValidParamsGetProtocol($lbport, $instanceport, $protocol)
    {
        $l = new Zend_Service_Amazon_Ec2_ElasticLB_Listener($lbport, $instanceport, $protocol);
        $this->assertEquals($protocol, $l->getProtocol());
    }
    
    /**
     * Test that we can get the listener properties as parameters for an API request
     * 
     * @param integer $lbport
     * @param integer $instanceport
     * @param string  $protocol
     * 
     * @dataProvider validParametersProvider
     */
    public function testToParamsArray($lbport, $instanceport, $protocol)
    {
        $ex = array(
            'Listeners.member.5.LoadBalancerPort' => $lbport,
            'Listeners.member.5.InstancePort'     => $instanceport,
            'Listeners.member.5.Protocol'         => $protocol
        );
        
        $l = new Zend_Service_Amazon_Ec2_ElasticLB_Listener($lbport, $instanceport, $protocol);
        
        $this->assertEquals($ex, $l->toParametersArray(5));
    }
    
    /**
     * Test that an exception is thrown for invalid LB port in constructor
     * 
     * @param integer $port
     * @dataProvider invalidPortProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testConstructorInvalidLBPort($port)
    {
        $l = new Zend_Service_Amazon_Ec2_ElasticLB_Listener($port, 80, 'HTTP');
    }

    /**
     * Test that an exception is thrown for invalid instance port in constructor
     * 
     * @param integer $port
     * @dataProvider invalidPortProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testConstructorInvalidInstancePort($port)
    {
        $l = new Zend_Service_Amazon_Ec2_ElasticLB_Listener(80, $port, 'HTTP');
    }
    
    /**
     * Test that an exception is thrown for invalid protocol in constructor
     * 
     * @param string      $proto
     * @dataProvider      invalidProtocolProvider
     * @expectedException Zend_Service_Amazon_Ec2_Exception
     */
    public function testConstructorInvalidProtocol($proto)
    {
        $l = new Zend_Service_Amazon_Ec2_ElasticLB_Listener(80, 80, $proto);
    }
    
    /**
     * Test that an exception is thrown if setting an invalid LB port
     * 
     * @param integer $port
     * @dataProvider invalidPortProvider
     */
    public function testSetInvalidLBPort($port)
    {
        $l = new Zend_Service_Amazon_Ec2_ElasticLB_Listener(80, 8080, 'HTTP');
        try {
            $l->setLoadBalancerPort($port);
            $this->fail("Expected an exception but got none");
        } catch (Zend_Service_Amazon_Ec2_Exception $e) {
            // All is well
        }
    }

    /**
     * Test that an exception is thrown if setting an invalid instance port
     * 
     * @param integer $port
     * @dataProvider invalidPortProvider
     */
    public function testSetInvalidInstancePort($port)
    {
        $l = new Zend_Service_Amazon_Ec2_ElasticLB_Listener(80, 8080, 'HTTP');
        try {
            $l->setInstancePort($port);
            $this->fail("Expected an exception but got none");
        } catch (Zend_Service_Amazon_Ec2_Exception $e) {
            // All is well
        }
    }
    
    /**
     * Test that an exception is thrown if setting an invalid protocol
     * 
     * @param string      $proto
     * @dataProvider      invalidProtocolProvider
     */
    public function testSetInvalidProtocol($proto)
    {
        $l = new Zend_Service_Amazon_Ec2_ElasticLB_Listener(80, 8080, 'HTTP');
        try {
            $l->setProtocol($proto);
            $this->fail("Expected an exception but got none");
        } catch (Zend_Service_Amazon_Ec2_Exception $e) {
            // All is well
        }
    }
    
    /**
     * Data Providers
     */
    
    static public function validParametersProvider()
    {
        return array(
            array(80, 80, 'HTTP'),
            array(80, 8080, 'TCP'),
            array(1, 0xffff, Zend_Service_Amazon_Ec2_ElasticLB_Listener::HTTP),
            array(12345, 54321, Zend_Service_Amazon_Ec2_ElasticLB_Listener::HTTP),
            array(123, 456, Zend_Service_Amazon_Ec2_ElasticLB_Listener::TCP)
        );
    }
    
    static public function invalidProtocolProvider()
    {
        return array(
            array(80),
            array('UDP'),
            array('SSH'),
            array('HTTPS'),
            array('HTTP/1.1'),
            array('http'),
            array(''),
            array(null),
            array(true),
            array(array())
        );
    }
    
    static public function invalidPortProvider()
    {
        return array(
            array(0),
            array(0x10000),
            array(22.5),
            array(100000),
            array('HTTP'),
            array(null),
            array(true),
            array(-2),
            array(array(22)),
        );
    }
}
