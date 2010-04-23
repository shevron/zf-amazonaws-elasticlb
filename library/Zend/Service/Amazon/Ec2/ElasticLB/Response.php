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
 * @see Zend_Http_Response
 */
require_once 'Zend/Service/Amazon/Ec2/Response.php';

/**
 * A response for an API request to Amazon EC2 Elastic Load Balancing
 * 
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage Ec2
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Amazon_Ec2_ElasticLB_Response extends Zend_Service_Amazon_Ec2_Response 
{
    /**
     * Default EC2 API version
     * 
     * @var string
     */
    protected $_ec2ApiVersion = '2009-11-25';
    
    /**
     * XML namespace used for EC2 responses, without the API version suffix
     * 
     * @var string
     */
    protected $_xmlNamespaceBase = 'http://elasticloadbalancing.amazonaws.com/doc/';
    
    /**
     * Full XML namespace for EC2 ELB responses
     * 
     * @var string
     */
    protected $_xmlNamespace = '';
    
    /**
     * Creates a new ElasticLB specific EC2 response object
     *
     * @param Zend_Http_Response $httpResponse the HTTP response
     * @param string             $apiVersion   the EC2 API version
     */
    public function __construct(Zend_Http_Response $httpResponse, $apiVersion = null)
    {
        if (! $apiVersion) $apiVersion = $this->_ec2ApiVersion;
        $this->_xmlNamespace = $this->_xmlNamespaceBase . $apiVersion . '/';
        
        parent::__construct($httpResponse);
    }
}