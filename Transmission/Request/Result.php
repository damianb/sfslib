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
use \emberlabs\openflame\Core\DependencyInjector;

/**
 * StopForumSpam integration library - Request response object
 * 	     Represents the response from the StopForumSpam API.
 *
 * @package     sfslib
 * @author      emberlabs.org
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/emberlabs/sfslib
 */
class Result implements \ArrayAccess
{
	const RESULT_USERNAME = 1;
	const RESULT_EMAIL = 2;
	const RESULT_IP = 3;

	protected $data = array(
		'type'				=> NULL,
		'value'				=> '',
		'appears'			=> NULL,
		'normalized'		=> NULL,
		'lastseen'			=> NULL,
		'lastseen_obj'		=> NULL,
		'lastseen_diff'		=> NULL,
		'frequency'			=> NULL,
		'confidence'		=> NULL,
	);

	/**
	 * Constructor
	 * @throws RequestException
	 */
	public function __construct($data, $result_type)
	{
		// No derpy result types, now.
		if($result_type != self::RESULT_USERNAME && $result_type != self::RESULT_EMAIL && $result_type != self::RESULT_IP)
		{
			throw new RequestException('Invalid result type specified');
		}

		if($data['appears'])
		{
			$data['appears'] = true;
			$now = DependencyInjector::grab('sfs.now');

			$data['lastseen'] = (int) $data['lastseen'];
			$data['lastseen_obj'] = $lastseen = new \DateTime('@' . $data['lastseen']);
			$data['lastseen_span'] = $lastseen->diff($now, true);

			$data['confidence'] = $this->getConfidence($data['frequency'], $data['lastseen']);
		}
		else
		{
			$data['appears'] = false;
		}

		$this->data = array_merge($this->data, $data);
	}

	/**
	 * Get report confidence rating based on number of submissions and last submission time.
	 * @param integer $reports - The number of reports for this result.
	 * @param integer $unix_last_report - The unix timestamp of the time the result was last reported.
	 * @return float - Returns a percentage (0-100%) of the confidence level of the result data.
	 */
	public function getConfidence($reports, $unix_last_report)
	{
		$current_time = time();

		$ups = floor($reports / 2);

		// seconds per day: 86400
		$grace = 14; // 14 day "grace period"
		$downs = floor(($current_time - $unix_last_report) / 86400) - $grace;
		if($downs < 0)
		{
			$downs = 0;
		}

		$n = $ups + ($downs * 2);
		if($n == 0)
		{
			return 0;
		}

		// Begin with the Wilson score interval
		// http://en.wikipedia.org/wiki/Binomial_proportion_confidence_interval#Wilson_score_interval
		// based off of the blog post at:
		// http://amix.dk/blog/post/19588
		$z = 1.0; // 1.0 = 85%, 1.6 = 95%
		$phat = $ups / $n;

		$res = sqrt($phat + $z * $z / (2 * $n) - $z * (($phat * (1 - $phat) + $z * $z / (4 * $n)) / $n)) / (1 + $z * $z / $n);

		$res = round($res * 100, 2);

		return $res;
	}

	/**
	 * Get the type of this result
	 * @return integer - Type, as according to the RESULT_* integer constants.
	 */
	public function getType()
	{
		return $this->data['type'];
	}

	/**
	 * Get the original value queried
	 * @return string - The original value queried.
	 */
	public function getValue()
	{
		return (string) $this->data['value'];
	}

	/**
	 * Get the normalized value if applicable (empty string if no normalization occurred).
	 * @return string - The normalized value.
	 */
	public function getNormalized()
	{
		return (string) $this->data['normalized'];
	}

	/**
	 * Get the DateTime object that represents when the data was last seen.
	 * @return \DateTime|NULL - NULL if not seen previously, or a DateTime object representing when the queried data was last seen.
	 */
	public function getLastseen()
	{
		return $this->data['lastseen_obj'];
	}

	/**
	 * Get the DateInterval object that represents the time span since when the data was last seen.
	 * @return \DateInterval|NULL - NULL if not seen previously, or a DateInterval object representing the time span since when the queried data was last seen.
	 */
	public function getLastseenSpan()
	{
		return $this->data['lastseen_diff'];
	}

	/**
	 * Get the timestamp of when the queried data was last seen
	 * @return integer - The timestamp of when the queried data was last seen (or zero if not seen).
	 */
	public function getLastseenTimestamp()
	{
		return (int) $this->data['lastseen'];
	}

	/**
	 * Get how many times that the queried data has been reported to StopForumSpam.
	 * @return integer - The number of times the queried data has been reported.
	 */
	public function getFrequency()
	{
		return (int) $this->data['frequency'];
	}

	/**
	 * Does the queried data appear in the StopForumSpam database?
	 * @return boolean - Does the data appear?
	 */
	public function getAppears()
	{
		return (bool) $this->data['appears'];
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

}
