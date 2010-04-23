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
 * @subpackage Ec2
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Response.php 20096 2010-01-06 02:05:09Z bkarwin $
 */

/**
 * A listener in an Amazon Elastic Load Balancer instance
 * 
 * Listeners represent a single port accepting traffic to be balanced by the 
 * Amazon Load Balancer. The listener object is used to describe listeners when 
 * creating or configuring a load balancer.
 * 
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage Ec2
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Amazon_Ec2_ElasticLB_Listener
{
    /**
     * Protocol constants
     */
    const HTTP = 'HTTP';
    const TCP  = 'TCP';

    protected $_validProtocols = array('HTTP', 'TCP');
    
    /**
     * Load balancer port
     * 
     * @var integer
     */
    protected $_lbPort;
    
    /**
     * Instance port
     * 
     * @var integer
     */
    protected $_instancePort;
    
    /**
     * Protocol
     * 
     * @var string
     */
    protected $_protocol;
    
    /**
     * Create a new listener object
     * 
     * @param integer $lbPort       
     * @param integer $instancePort 
     * @param string  $protocol     
     */
    public function __construct($lbPort, $instancePort, $protocol)
    {
        $this->setLoadBalancerPort($lbPort);
        $this->setInstancePort($instancePort);
        $this->setProtocol($protocol);
    }
    
	/**
	 * Get the load balancer port
	 * 
     * @return integer
     */
    public function getLoadBalancerPort()
    {
        return $this->_lbPort;
    }

	/**
	 * Get the instance port 
	 * 
     * @return integer
     */
    public function getInstancePort()
    {
        return $this->_instancePort;
    }

	/**
	 * Get the protocol
	 * 
     * @return string
     */
    public function getProtocol()
    {
        return $this->_protocol;
    }

	/**
	 * Set the load balancer port
	 * 
     * @param  integer $lbPort
     * @throws Zend_Service_Amazon_Ec2_Exception
     * @return Zend_Service_Amazon_Ec2_ElasticLB_Listener
     */
    public function setLoadBalancerPort($lbPort)
    {
        $lbPort = (int) $lbPort;
        if ($lbPort < 1 || $lbPort > 0xffff) {
            $message = "Invalid port: expecting a number between 1 and 65535, got $lbPort";
            require_once 'Zend/Service/Amazon/Ec2/Exception.php';
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $this->_lbPort = $lbPort;
        
        return $this;
    }

    /**
     * Set the instance port
     * 
     * @param  integer $instancePort
     * @throws Zend_Service_Amazon_Ec2_Exception
     * @return Zend_Service_Amazon_Ec2_ElasticLB_Listener
     */
    public function setInstancePort($instancePort)
    {
        $instancePort = (int) $instancePort;
        if ($instancePort < 1 || $instancePort > 0xffff) {
            $message = "Invalid port: expecting a number between 1 and 65535, got $instancePort";
            require_once 'Zend/Service/Amazon/Ec2/Exception.php';
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $this->_instancePort = $instancePort;
        
        return $this;
    }
    
    /**
     * Set the protocol
     * 
     * @param  string $protocol
     * @throws Zend_Service_Amazon_Ec2_Exception
     * @return Zend_Service_Amazon_Ec2_ElasticLB_Listener
     */
    public function setProtocol($protocol)
    {
        $protocol = (string) $protocol;
        if (! in_array($protocol, $this->_validProtocols)) {
            $message = "Protocol '$protocol' is not a valid Listener protocol";
            require_once 'Zend/Service/Amazon/Ec2/Exception.php';
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $this->_protocol = $protocol;
        
        return $this;
    }
    
    /**
     * Get all listener properties as an array ready to be used in an API request
     * 
     * @param  integer $id The index number of this listener
     * @return array
     */
    public function toParametersArray($id)
    {
        $key = 'Listeners.member.' . $id;
        
        return array(
            $key . '.Protocol'         => $this->_protocol,
            $key . '.LoadBalancerPort' => $this->_lbPort,
            $key . '.InstancePort'     => $this->_instancePort
        );
    }    
}