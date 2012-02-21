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
use \emberlabs\sfslib\Internal\ReportException;
use \emberlabs\sfslib\Transmission\TransmissionInstanceInterface;
use \emberlabs\sfslib\Transmission\TransmissionResponseInterface;
use \emberlabs\sfslib\Transmission\Report\Error as ReportError;
use \emberlabs\sfslib\Transmission\Report\Error as ReportResult;
use \emberlabs\openflame\Core\Utility\JSON;
use \InvalidArgumentException;

/**
 * StopForumSpam integration library - Report response object
 * 	     Represents the response from the StopForumSpam API.
 *
 * @package     sfslib
 * @author      emberlabs.org
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/emberlabs/sfslib
 */
class Response implements TransmissionResponseInterface, \ArrayAccess
{
	/**
	 * @var array - The array of data returned by the API.
	 */
	protected $data = array();

	/**
	 * Get the response object to represent the response to our report to the StopForumSpam API.
	 * @param TransmissionInstanceInterface $transmission - The transmission object that we sent to the API.
	 * @param string $json - The json string received from the API.
	 * @return self|ReportError - The response object representing data received from the API, or the error object representing the request error that occurred.
	 */
	public static function getResponse(TransmissionInstanceInterface $transmission, $json)
	{
		// empty json = fail
		if(!$json)
		{
			return new ReportError($transmission, array());
		}

		try
		{
			// decode the json into an associative array
			$data = JSON::decode($json);
		}
		catch(\RuntimeException $e)
		{
			// handling bad json responses
			return new ReportError($transmission, array());
		}

		if(!isset($data['success']) || $data['success'] != 1)
		{
			// error!
			return new ReportError($transmission, $data);
		}

		// success!
		return new self($transmission, $data);
	}

	/**
	 * Constructor
	 * @param TransmissionInstanceInterface $transmission - The transmission object that we sent to the API.
	 * @param string $json - The data array of data received from the API.
	 */
	protected function __construct(TransmissionInstanceInterface $transmission, $data)
	{
		$this->data = array_merge($transmission->getData(), array(
			'recordid'		=> $data['recordid'],
			'evidenceid'	=> $data['evidenceid'],
		));
	}

	/**
	 * Get the username reported.
	 * @return stirng - The username reported.
	 */
	public function getReportedUsername()
	{
		return $this->data['username'];
	}

	/**
	 * Get the email reported.
	 * @return string - The email reported.
	 */
	public function getReportedEmail()
	{
		return $this->data['email'];
	}

	/**
	 * Get the IP reported.
	 * @return string - The IP reported.
	 */
	public function getReportedIP()
	{
		return $this->data['ip'];
	}

	/**
	 * Get the evidence ID for this report (if evidence was submitted).
	 * @return NULL|integer - The evidence ID for the SFS report if evidence was submitted, or NULL if no evidence submitted.
	 */
	public function getEvidenceID()
	{
		return $this->data['evidenceid'];
	}

	/**
	 * Get the record ID for this report.
	 * @return integer - The record ID for the SFS report.
	 */
	public function getRecordID()
	{
		return $this->data['recordid'];
	}

	/**
	 * Magic methods
	 */

	/**
	 * __isset() magic method for grabbing reported data (and API IDs).
	 * @param string $name - The entry to check.
	 * @return boolean - Does the entry exist?
	 */
	public function __isset($name)
	{
		return isset($this->data[(string) $name]);
	}

	/**
	 * __get() magic method for grabbing reported data (and API IDs).
	 * @param string $name - The entry to get.
	 * @return mixed - The entry's value, or NULL if it does not exist
	 */
	public function __get($name)
	{
		if(isset($this->data[(string) $name]))
		{
			return $this->data[(string) $name];
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * ArrayAccess methods
	 */

	/**
	 * Check to see if the specified offset exists.
	 * @param string $offset - The offset to check.
	 * @return boolean - Does the offset exist?
	 */
	public function offsetExists($offset)
	{
		return isset($this->data[(string) $offset]);
	}

	/**
	 * Get the specified offset value.
	 * @param string $offset - The offset to get.
	 * @return mixed - The offset's value, or NULL if it does not exist
	 */
	public function offsetGet($offset)
	{
		if(isset($this->data[(string) $offset]))
		{
			return $this->data[(string) $offset];
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * @ignore
	 */
	public function offsetSet($offset, $value) { }

	/**
	 * @ignore
	 */
	public function offsetUnset($offset) { }

	/**
	 * Is this an error object?
	 * @return boolean - It's not an error object!  Returns false.
	 */
	public function isError()
	{
		return false;
	}
}
