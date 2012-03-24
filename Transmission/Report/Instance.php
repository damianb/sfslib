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

namespace emberlabs\sfslib\Transmission\Report;
use \emberlabs\sfslib\Library as SFS;
use \emberlabs\sfslib\Internal\ReportException;
use \emberlabs\sfslib\Transmission\TransmissionInstanceInterface;
use \emberlabs\sfslib\Transmission\Report\Response as ReportResponse;
use \emberlabs\sfslib\Transmission\Report\Error as ReportError;
use \emberlabs\openflame\Core\Core;
use \emberlabs\openflame\Core\DependencyInjector;
use \InvalidArgumentException;

/**
 * StopForumSpam integration library - Report Instance object
 * 	     Represents the report to be made to the StopForumSpam API.
 *
 * @package     sfslib
 * @author      emberlabs.org
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/emberlabs/sfslib
 */
class Instance implements TransmissionInstanceInterface
{
	const API_URL = 'http://www.stopforumspam.com/add.php';

	/**
	 * @var string - Username to report to StopForumSpam.
	 */
	protected $username = '';

	/**
	 * @var string - Email to report to StopForumSpam.
	 */
	protected $email = '';

	/**
	 * @var string - IP to report to StopForumSpam.
	 */
	protected $ip = '';

	/**
	 * @var string - Evidence for the report to submit to StopForumSpam.
	 */
	protected $evidence = '';

	/**
	 * @var \emberlabs\sfslib\Transmission\Report\Response - The result object.
	 */
	protected $response;

	/**
	 * Obtain a new instance of this object
	 * @return \emberlabs\sfslib\Transmission\Report\Instance - Provides a fluent interface.
	 */
	public static function newInstance()
	{
		return new self();
	}

	/**
	 * Get the data that will be reported or that has already been reported to StopForumSpam.
	 * @return array - The array of data to report or reported to StopForumSpam.
	 */
	public function getData()
	{
		return array(
			'username'		=> $this->username,
			'email'			=> $this->email,
			'ip'			=> $this->ip,
			'evidence'		=> $this->evidence,
		);
	}

	/**
	 * Build the POST URL to query StopForumSpam with.
	 * @return string - The URL param string to use.
	 */
	public function buildPOSTURL()
	{
		$url = Core::getConfig('sfs.api_url') ?: self::API_URL;
		$url .= '?f=json&id=1';

		return $url;
	}

	/**
	 * Build the POST data string to send to the server.
	 * @return string - The POST data string to use.
	 */
	public function buildPOSTData()
	{
		$sfs = SFS::getInstance();

		return http_build_query(array(
			'username'		=> $this->username,
			'email'			=> $this->email,
			'ip'			=> $this->ip,
			'api_key' 		=> $sfs->getKey(),
			'evidence'		=> $this->evidence,
		));
	}

	/**
	 * Build the GET URL params to report to StopForumSpam with.
	 * @return string - The URL param string to use.
	 */
	public function buildGETURL()
	{
		$sfs = SFS::getInstance();

		$data = array(
			'username=' . rawurlencode($this->username),
			'email=' . rawurlencode($this->email),
			'ip=' . rawurlencode($this->ip),
			'api_key=' . rawurlencode($sfs->getKey()),
		);

		// Allow the API URL to be overridden if we need to, but if we don't want to, fall back to the default URL.
		$url =  Core::getConfig('sfs.api_url') ?: self::API_URL;
		$url .= '?' . implode('&', $data) . '&f=json&id=1';

		return $url;
	}

	/**
	 * Sets the username that we are transmitting.
	 * @var string $username - The username to report.
	 * @return \emberlabs\sfslib\Transmission\Report\Instance - Provides a fluid interface.
	 *
	 * @throws ReportException
	 */
	public function setUsername($username)
	{
		if($this->response !== NULL)
		{
			throw new ReportException('Cannot modify a report already sent');
		}

		$this->username = $username;

		return $this;
	}

	/**
	 * Sets the email that we are transmitting.
	 * @var string $email - The email address to report.
	 * @return \emberlabs\sfslib\Transmission\Report\Instance - Provides a fluid interface.
	 *
	 * @throws ReportException
	 * @throws InvalidArgumentException
	 */
	public function setEmail($email)
	{
		if($this->response !== NULL)
		{
			throw new ReportException('Cannot modify a report already sent');
		}

		if(filter_var($email, FILTER_VALIDATE_EMAIL) === false)
		{
			throw new InvalidArgumentException('Invalid email address supplied');
		}

		$this->email = $email;

		return $this;
	}

	/**
	 * Sets the IP that we are transmitting.
	 * @var string $ip - The IP to report.
	 * @return \emberlabs\sfslib\Transmission\Report\Instance - Provides a fluid interface.
	 *
	 * @throws ReportException
	 * @throws InvalidArgumentException
	 */
	public function setIP($ip)
	{
		if($this->response !== NULL)
		{
			throw new ReportException('Cannot modify a report already sent');
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

		$this->ip = $ip;

		return $this;
	}


	/**
	 * Sets the evidence that we are transmitting.
	 * @var string $evidence - The evidence to report.
	 * @return \emberlabs\sfslib\Transmission\Report\Instance - Provides a fluid interface.
	 *
	 * @throws ReportException
	 */
	public function setEvidence($evidence)
	{
		if($this->response !== NULL)
		{
			throw new ReportException('Cannot modify a report already sent');
		}

		$this->evidence = $evidence;

		return $this;
	}

	/**
	 * Get the response object linked to this report.
	 * @return ReportResponse|ReportError|NULL - The response object, the error object for the error(s) received from the API, or NULL if request not yet sent.
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Generate a new response object based on the data received from StopForumSpam in response to this report.
	 * @param string $json - The JSON data received in response from StopForumSpam.
	 * @return ReportResponse|ReportError - The report response object, or the report error object created from our report data results
	 *
	 * @throws ReportException
	 */
	public function newResponse($json)
	{
		if($this->response !== NULL)
		{
			throw new ReportException('Report response already generated, use getResponse() to reobtain it');
		}

		$this->response = ReportResponse::getResponse($this, $json);
		return $this->response;
	}

	/**
	 * Send the report to the API.
	 * @return ReportResponse|RequestError - The response or error received from the API.
	 *
	 * @throws ReportException
	 */
	public function send()
	{
		$sfs = \emberlabs\sfslib\Library::getInstance();
		$transmitter = DependencyInjector::grab('sfs.transmitter');

		if(empty($this->username) || empty($this->email) || empty($this->ip))
		{
			throw new ReportException('Report must contain a username, an email, and an IP');
		}

		if(!$sfs->getKey())
		{
			throw new ReportException('An API key must be provided when reporting to the StopForumSpam API');
		}

		if(!empty($this->evidence))
		{
			return $transmitter->sendPOST($this);
		}
		else
		{
			return $transmitter->sendGET($this);
		}
	}

	/**
	 * @ignore
	 */
	public function __destruct()
	{
		$this->response = NULL;
	}
}
