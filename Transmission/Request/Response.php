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
use \emberlabs\sfslib\Transmission\TransmissionResponseInterface;
use \emberlabs\sfslib\Transmission\Request\Error as RequestError;
use \emberlabs\sfslib\Transmission\Request\Error as RequestResult;
use \emberlabs\openflame\Core\Utility\JSON;
use \InvalidArgumentException;

/**
 * StopForumSpam integration library - Request response object
 * 	     Represents the response from the StopForumSpam API.
 *
 * @package     sfslib
 * @author      emberlabs.org
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/emberlabs/sfslib
 */
class Response implements TransmissionResponseInterface
{
	/**
	 * @var array - The array of result objects representing the data returned by the API.
	 */
	protected $data = array();

	/**
	 * Get the response object to represent the response to our query by the StopForumSpam API.
	 * @param TransmissionInstanceInterface $transmission - The transmission object that we sent to the API.
	 * @param string $json - The json string received from the API.
	 * @return self|RequestError - The response object representing data received from the API, or the error object representing the request error that occurred.
	 */
	public static function getResponse(TransmissionInstanceInterface $transmission, $json)
	{
		// empty json = fail
		if(!$json)
		{
			return new RequestError($transmission, array());
		}

		try
		{
			// decode the json into an associative array
			$data = JSON::decode($json);
		}
		catch(\RuntimeException $e)
		{
			// handling bad json responses
			return new RequestError($transmission, array());
		}

		if(!isset($data['success']) || $data['success'] != 1)
		{
			// error!
			return new RequestError($transmission, $data);
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
		foreach($data as $type => $set)
		{
			if($type != 'username' && $type != 'email' && $type != 'ip')
			{
				continue;
			}

			foreach($set as $entry)
			{
				if($type == 'username')
				{
					$result = new Result($entry, Result::RESULT_USERNAME);
				}
				elseif($type == 'email')
				{
					$result = new Result($entry, Result::RESULT_EMAIL);
				}
				elseif($type == 'ip')
				{
					$result = new Result($entry, Result::RESULT_IP);
				}

				$this->data[$type][$result->getValue()] = $result;
			}
		}
	}

	/**
	 * Is this an error object?
	 * @return boolean - It's not an error object!  Returns false.
	 */
	public function isError()
	{
		return false;
	}

	/**
	 * Get a username result object from this response
	 * @param string $username - The username to look up
	 * @return NULL|RequestResult - The request result object we want, or NULL if no such result.
	 */
	public function getUsername($username)
	{
		$username = mb_strtolower($username);

        if(!isset($this->data['username'][(string) $username]))
        {
            return NULL;
        }

        return $this->data['username'][(string) $username];
	}

	/**
	 * Get an email result object from this response.
	 * @param string $email - The email to look up.
	 * @return NULL|RequestResult - The request result object we want, or NULL if no such result.
	 */
	public function getEmail($email)
	{
		$email = mb_strtolower($email);

		if(!isset($this->data['email'][(string) $email]))
		{
			return NULL;
		}

		return $this->data['email'][(string) $email];
	}

	/**
	 * Get an ip result object from this response.
	 * @param string $ip - The IP to look up.
	 * @return NULL|RequestResult - The request result object we want, or NULL if no such result.
	 */
	public function getIP($ip)
	{
		$ip = mb_strtolower($ip);

		if(!isset($this->data['ip'][(string) $ip]))
		{
			return NULL;
		}

		return $this->data['ip'][(string) $ip];
	}

	/**
	 * @ignore
	 */
	public function __destruct()
	{
		$this->data = array();
	}
}
