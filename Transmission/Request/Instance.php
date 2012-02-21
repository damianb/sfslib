<?php
/**
 *
 *===================================================================
 *
 *  StopForumSpam integration library
 *-------------------------------------------------------------------
 * @package     sfslib
 * @author      emberlabs.org
 * @copyright   (c) 2010 - 2011 emberlabs.org
 * @license     MIT License
 * @link        https://github.com/emberlabs/sfslib
 *
 *===================================================================
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 *
 */

namespace emberlabs\sfslib\Transmission\Request;
use \emberlabs\sfslib\Internal\RequestException;
use \emberlabs\sfslib\Transmission\TransmissionInstanceInterface;
use \emberlabs\sfslib\Transmission\Request\Response as RequestResponse;
use \emberlabs\sfslib\Transmission\Request\Error as RequestError;
use \emberlabs\openflame\Core\Core;
use \emberlabs\openflame\Core\DependencyInjector;
use \InvalidArgumentException;

/**
 * StopForumSpam integration library - Request Instance object
 * 	     Represents the request to be made of the StopForumSpam API.
 *
 * @package     sfslib
 * @author      emberlabs.org
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/emberlabs/sfslib
 */
class Instance implements TransmissionInstanceInterface
{
	const API_URL = 'http://www.stopforumspam.com/api';

	/**
	 * @var array - Array of usernames to check against StopForumSpam
	 */
	protected $username = array();

	/**
	 * @var array - Array of email addresses to check against StopForumSpam
	 */
	protected $email = array();

	/**
	 * @var array - Array of IP addresses to check against StopForumSpam
	 */
	protected $ip = array();

	/**
	 * @var integer - The number of data points currently to be queried.
	 */
	protected $num_datapoints = 0;

	/**
	 * @var \emberlabs\sfslib\Transmission\Request\Response - The result object.
	 */
	protected $response;

	/**
	 * Obtain a new instance of this object
	 * @return \emberlabs\sfslib\Transmission\Request\Instance - Provides a fluent interface.
	 */
	public static function newInstance()
	{
		return new self();
	}

	/**
	 * Build the POST URL to query StopForumSpam with.
	 * @return string - The URL param string to use.
	 *
	 * @throws RequestException
	 */
	public function buildPOSTURL()
	{
		throw new RequestException('POST-based API requests are not supported');
	}

	/**
	 * Build the POST data string to send to the server.
	 * @return string - The POST data string to use.
	 *
	 * @throws RequestException
	 */
	public function buildPOSTData()
	{
		throw new RequestException('POST-based API requests are not supported');
	}

	/**
	 * Build the GET URL params to query StopForumSpam with.
	 * @return string - The URL param string to use.
	 */
	public function buildGETURL()
	{
		$data = array();
		if(!empty($this->username))
		{
			$this->username = array_map('rawurlencode', $this->username);
			$data[] = 'username[]=' . implode('&username[]=', $this->username);
		}

		if(!empty($this->email))
		{
			$this->email = array_map('rawurlencode', $this->email);
			$data[] = 'email[]=' . implode('&email[]=', $this->email);
		}

		if(!empty($this->ip))
		{
			$this->ip = array_map('rawurlencode', $this->ip);
			$data[] = 'ip[]=' . implode('&ip[]=', $this->ip);
		}

		// Allow the API URL to be overridden if we need to, but if we don't want to, fall back to the default URL.
		$url = Core::getConfig('sfs.api_url') ?: self::API_URL;
		$url .= '?' . implode('&', $data) . '&f=json&unix=1';

		return $url;
	}

	/**
	 * Sets the username that we are transmitting.
	 * @var string $username - The username to check.
	 * @return \emberlabs\sfslib\Transmission\Request\Instance - Provides a fluid interface.
	 *
	 * @throws RequestException
	 */
	public function setUsername($username)
	{
		if($this->num_datapoints >= 15)
		{
			throw new RequestException('Maximum number of data points to query reached for request instance');
		}

		if($this->response !== NULL)
		{
			throw new RequestException('Cannot modify a request already sent');
		}

		$this->username[] = $username;
		$this->num_datapoints++;

		return $this;
	}

	/**
	 * Sets the email that we are transmitting.
	 * @var string $email - The email address to check.
	 * @return \emberlabs\sfslib\Transmission\Request\Instance - Provides a fluid interface.
	 *
	 * @throws RequestException
	 * @throws InvalidArgumentException
	 */
	public function setEmail($email)
	{
		if($this->num_datapoints >= 15)
		{
			throw new RequestException('Maximum number of data points to query reached for request instance');
		}

		if($this->response !== NULL)
		{
			throw new RequestException('Cannot modify a request already sent');
		}

		if(filter_var($email, FILTER_VALIDATE_EMAIL) === false)
		{
			throw new InvalidArgumentException('Invalid email address supplied');
		}

		$this->email[] = $email;
		$this->num_datapoints++;

		return $this;
	}

	/**
	 * Sets the IP that we are transmitting.
	 * @var string $ip - The IP to check.
	 * @return \emberlabs\sfslib\Transmission\Request\Instance - Provides a fluid interface.
	 *
	 * @throws RequestException
	 * @throws InvalidArgumentException
	 */
	public function setIP($ip)
	{
		if($this->num_datapoints >= 15)
		{
			throw new RequestException('Maximum number of data points to query reached for request instance');
		}

		if($this->response !== NULL)
		{
			throw new RequestException('Cannot modify a request already sent');
		}

		/**
		 * Validation will check for reserved or private IP ranges
		 *
		 * Validation will fail on the following IP ranges:
		 * 0.0.0.0/8
		 * 10.0.0.0/8
		 * 169.254.0.0/16
		 * 172.16.0.0/12
		 * 192.0.2.0/24
		 * 192.168.0.0/16
		 * 224.0.0.0/4
		 * FC* (IPv6)
		 * FD* (IPv6)
		 */
		if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 & FILTER_FLAG_IPV6 & FILTER_FLAG_NO_PRIV_RANGE & FILTER_FLAG_NO_RES_RANGE) === false)
		{
			throw new InvalidArgumentException('Invalid IP address supplied');
		}

		$this->ip[] = $ip;
		$this->num_datapoints++;

		return $this;
	}

	/**
	 * Get the response object linked to this query
	 * @return RequestResponse|RequestError|NULL - The response object, the error object for the error(s) received from the API, or NULL if request not yet sent.
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Generate a new response object based on the data received from StopForumSpam in response to this query.
	 * @param string $json - The JSON data received in response from StopForumSpam.
	 * @return RequestResponse|RequestError - The request response object, or the request error object created from our query data results
	 *
	 * @throws RequestException
	 */
	public function newResponse($json)
	{
		if($this->response !== NULL)
		{
			throw new RequestException('Request response already generated, use getResponse() to reobtain it');
		}

		$this->response = RequestResponse::getResponse($this, $json);
		return $this->response;
	}

	/**
	 * Send the query to the API.
	 * @return RequestResponse|RequestError - The response or error received from the API.
	 */
	public function send()
	{
		$transmitter = DependencyInjector::grab('sfs.transmitter');

		return $transmitter->sendGET($this);
	}

	/**
	 * @ignore
	 */
	public function __destruct()
	{
		$this->response = NULL;
	}
}
