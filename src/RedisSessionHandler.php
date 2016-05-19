<?php

namespace Prezto;


class RedisSessionHandler implements \SessionHandlerInterface
{

    /**
     * @var null
     */
    private static $_instance = null;

    /**
     * @var null|string
     */
    public $host = null;

    /**
     * @var null|string
     */
    public $port = null;

    /**
     * @var null
     */
    public $pass = null;

    /**
     * @var null
     */
    public $handle = null;

    /**
     * @param string $host
     * @param string $port
     * @param null $pass
     */
    private function __construct($host, $port, $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->pass = $pass;
        session_set_save_handler($this);
        session_start();
    }

    /**
     * @return RedisHandler|null
     */
    public static function init($host = 'localhost', $port = '6379', $pass = null)
    {
        if (self::$_instance !== null)
            return self::$_instance;

        return self::$_instance = new self($host, $port, $pass);
    }

    /**
     *
     */
    public function auth()
    {
        $this->handle->auth($this->pass);
    }

    /**
     * PHP >= 5.4.0<br/>
     * Close the session
     * @link http://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     */
    public function close()
    {
        $this->handle = null;
        return true;
    }

    /**
     * PHP >= 5.4.0<br/>
     * Destroy a session
     * @link http://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param int $session_id The session ID being destroyed.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     */
    public function destroy($session_id)
    {
        $this->handle->del($session_id);
    }

    /**
     * PHP >= 5.4.0<br/>
     * Cleanup old sessions
     * @link http://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $maxlifetime <p>
     * Sessions that have not updated for
     * the last maxlifetime seconds will be removed.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     */
    public function gc($maxlifetime)
    {
        // Redis will do this by itself, because of the key expiration that is set in the write operation.
        return true;
    }

    /**
     * PHP >= 5.4.0<br/>
     * Initialize session
     * @link http://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $save_path The path where to store/retrieve the session.
     * @param string $session_id The session id.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     */
    public function open($save_path, $session_id)
    {
        try {
            $this->handle = new \TinyRedisClient(sprintf("%s:%s", $this->host, $this->port));

            if ($this->pass !== null)
                $this->auth();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * PHP >= 5.4.0<br/>
     * Read session data
     * @link http://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $session_id The session id to read data for.
     * @return string <p>
     * Returns an encoded string of the read data.
     * If nothing was read, it must return an empty string.
     * Note this value is returned internally to PHP for processing.
     * </p>
     */
    public function read($session_id)
    {
        $session_data = $this->handle->get($session_id);

        if (substr($session_data, 0, 1) === '-')
            return '';

        return $session_data;
    }

    /**
     * PHP >= 5.4.0<br/>
     * Write session data
     * @link http://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $session_id The session id.
     * @param string $session_data <p>
     * The encoded session data. This data is the
     * result of the PHP internally encoding
     * the $_SESSION superglobal to a serialized
     * string and passing it as this parameter.
     * Please note sessions use an alternative serialization method.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     */
    public function write($session_id, $session_data)
    {
        $result = $this->handle->set($session_id, $session_data);

        if ($result !== 'OK')
            return false;

        $this->handle->expire($session_id, ini_get('session.gc_maxlifetime'));
        return true;
    }

}
