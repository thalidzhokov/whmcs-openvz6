<?php
/**
 * Author: Albert Thalidzhokov
 */

/**
 * Class SSH2
 */
class SSH2
{
	// Constants
	const PORT = 22;

	// Static
	public static $host;

	public $session;
	public $stream;


	/**
	 * SSH2 constructor.
	 * @param $host
	 * @param int $port
	 * @throws Exception
	 */
	function __construct($host, $port = self::PORT)
	{
		if (function_exists('ssh2_connect')) {
			try {
				self::$host = $host;
				$this->session = ssh2_connect(
					self::$host,
					$port
				);
			} catch (Exception $e) {
				throw new Exception('Unable to ssh2_connect to ' . $host . ' Error: ' . $e->getMessage());
			}
		}

		return $this;
	}

	/**
	 * @param $username
	 * @param $auth
	 * @param null $private
	 * @param null $secret
	 * @return bool
	 * @throws Exception
	 */
	function auth($username, $auth, $private = null, $secret = null)
	{
		if (is_file($auth) && is_readable($auth) && isset($private)) {
			// If $auth is a file, and $private is set, try pubkey auth
			try {
				if (ssh2_auth_pubkey_file($this->session, $username, $auth, $private, $secret)) {
					return true;
				}
			} catch (Exception $e) {
				throw new Exception('Unable to ssh2_auth_pubkey_file to ' . self::$host . ' Error: ' . $e->getMessage());
			}
		} else {
			// Auth with password
			try {
				if (ssh2_auth_password($this->session, $username, $auth)) {
					return true;
				}
			} catch (Exception $e) {
				throw new Exception('Unable to ssh2_auth_password to ' . self::$host . ' Error: ' . $e->getMessage());
			}
		}

		return false;
	}

	/**
	 * @param $localFile
	 * @param $remoteFile
	 * @param $createMode
	 * @return bool
	 * @throws Exception
	 */
	function send($localFile, $remoteFile, $createMode)
	{
		try {
			if (ssh2_scp_send($this->session, $localFile, $remoteFile, $createMode)) {
				return true;
			}
		} catch (Exception $e) {
			throw new Exception('Unable to ssh2_scp_send to ' . self::$host . ' Error: ' . $e->getMessage());
		}

		return false;
	}

	/**
	 * @param $remoteFile
	 * @param $localFile
	 * @return bool
	 * @throws Exception
	 */
	function get($remoteFile, $localFile)
	{
		try {
			if (ssh2_scp_recv($this->session, $remoteFile, $localFile)) {
				return true;
			}
		} catch (Exception $e) {
			throw new Exception('Unable to ssh2_scp_recv to ' . self::$host . ' Error: ' . $e->getMessage());
		}

		return false;
	}

	/**
	 * @param $cmd
	 * @param bool $blocking
	 * @return bool
	 * @throws Exception
	 */
	function exec($cmd, $blocking = True)
	{
		try {
			if ($this->stream = ssh2_exec($this->session, $cmd)) {
				stream_set_blocking($this->stream, $blocking);
				return true;
			}
		} catch (Exception $e) {
			throw new Exception('Unable to ssh2_exec ' . self::$host . ' Error: ' . $e->getMessage());
		}

		return false;
	}

	/**
	 * @param $cmd
	 * @param bool $blocking
	 */
	function cmd($cmd, $blocking = True)
	{
		$this->exec($cmd, $blocking);
	}

	/**
	 * @return null|string
	 */
	function output()
	{
		$errBuf = null;
		$outBuf = null;
		$stdout = $this->stream;
		$stderr = ssh2_fetch_stream($stdout, SSH2_STREAM_STDERR);

		if (!empty($stdout)) {
			$t0 = time();

			do {
				$errBuf.= fread($stderr, 4096);
				$outBuf.= fread($stdout, 4096);

				$done = 0;

				if (feof($stderr)) {
					$done++;
				}

				if (feof($stdout)) {
					$done++;
				}

				$t1 = time();
				$span = $t1 - $t0;

				sleep(1);
			} while (($span < 30) && ($done < 2));
		}

		if ($outBuf) {
			$rtn = $outBuf;
		} else if ($errBuf) {
			$rtn = $errBuf;
		} else {
			$rtn = "Failed to Shell";
		}

		return preg_replace("/[^A-ZА-Яa-zа-я0-9\:\-\/\.\n ]/", '', $rtn);
	}
}