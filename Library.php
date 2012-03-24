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

namespace emberlabs\sfslib;
use \emberlabs\openflame\Core\Core;
use \emberlabs\openflame\Core\DependencyInjector;
use \emberlabs\sfslib\Transmission\Request\Instance as RequestInstance;
use \emberlabs\sfslib\Transmission\Request\Response as RequestResponse;
use \emberlabs\sfslib\Transmission\Request\Error as RequestError;
use \emberlabs\sfslib\Transmission\Report\Instance as ReportInstance;
use \emberlabs\sfslib\Transmission\Report\Response as ReportResponse;
use \emberlabs\sfslib\Transmission\Report\Error as ReportError;

/**
 * StopForumSpam integration library - Manager object
 * 	     Provides quick and easy access to the library's functionality.
 *
 * @package     sfslib
 * @author      emberlabs.org
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/emberlabs/sfslib
 */
class Library
{
	/**
	 * @const string - The version of the library.
	 */
	const LIB_VERSION = '1.0.0-rc2';

	/**
	 * @var \emberlabs\sfslib\Library - The singleton instance of this object.
	 */
	protected static $instance;

	/**
	 * @var string - The API key to use with StopForumSpam
	 */
	protected $key = '';

	/**
	 * Get the singleton instance of the Library object.
	 * @return \emberlabs\sfslib\Library - Returns the singleton instance of this object.
	 */
	public static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the user agent string to use for the library.
	 * @return string - The user agent string to use
	 */
	public static function getUserAgent()
	{
		return sprintf('emberlabs.sfslib:%1$s;PHP:%2$s', self::LIB_VERSION, PHP_VERSION);
	}

	/**
	 * Constructor
	 */
	protected function __construct()
	{
		// set default options...
		$defaults = array(
			'sfs.timeout'				=> 30,
			'sfs.transmitter'			=> 'file',
		);

		$injector = DependencyInjector::getInstance();
		$injector->setInjector('sfs.transmitter', function() use($injector) {
			return $injector->get('sfs.transmitter.' . Core::getConfig('sfs.transmitter'));
		});
		$injector->setInjector('sfs.transmitter.curl', function() {
			return new \emberlabs\sfslib\Transmitter\cURL();
		});
		$injector->setInjector('sfs.transmitter.file', function() {
			return new \emberlabs\sfslib\Transmitter\File();
		});
		$injector->setInjector('sfs.transmitter.mock', function() {
			return new \emberlabs\sfslib\Transmitter\Mock();
		});
		$injector->setInjector('sfs.now', function() {
			return new \DateTime('now');
		});

		foreach($defaults as $name => $config)
		{
			if(Core::getConfig($name) === NULL)
			{
				Core::setConfig($name, $config);
			}
		}
	}

	/**
	 * Set the API key to use for restricted communications with the StopForumSpam API.
	 * @param string $key - The API key to use.
	 * @return \emberlabs\sfslib\Library - Provides a fluent interface.
	 */
	public function setKey($key)
	{
		$this->key = (string) $key;

		return $this;
	}

	/**
	 * Get the API key in use for restricted communications with the StopForumSpam API.
	 * @return string - The API key in use.
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * Create a new request for the StopForumSpam API and immediately submit the request.
	 * @param string $username - The username to query.
	 * @param string $email - The email to query.
	 * @param string $ip - The IP to query.
	 * @return RequestResponse|RequestError - The response received from the StopForumSpam API.
	 */
	public function request($username, $email, $ip)
	{
		$request = RequestInstance::newInstance()
			->setUsername($username)
			->setEmail($email)
			->setIP($ip);

		// This will NOT return the just-created RequestInstance, instead it will return a RequestError or a RequestResponse object.
		return $request->send();
	}

	/**
	 * Create a new report for the StopForumSpam API and immediately submit the report.
	 * @param string $username - The username to report.
	 * @param string $email - The email to report.
	 * @param string $ip - The IP to report.
	 * @param string $evidence - The evidence to report (optional).
	 * @return ReportResponse|ReportError - The response received from the StopForumSpam API.
	 */
	public function report($username, $email, $ip, $evidence = '')
	{
		$report = ReportInstance::newInstance()
			->setUsername($username)
			->setEmail($email)
			->setIP($ip);

		if(!empty($evidence))
		{
			$report->setEvidence($evidence);
		}

		// This will NOT return the just-created ReportInstance, instead it will return a ReportError or a ReportResponse object.
		return $report->send();
	}

	/**
	 * Create a new request object to query the StopForumSpam API with.
	 * @return RequestInstance - The newly created request instance.
	 */
	public function newRequest()
	{
		return RequestInstance::newInstance();
	}

	/**
	 * Create a new report object to report data to the StopForumSPam API with.
	 * @return ReportInstance - The newly created report instance.
	 */
	public function newReport()
	{
		return ReportInstance::newInstance();
	}
}
