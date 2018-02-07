<?php

namespace hamburgscleanest\GuzzleAdvancedThrottle\Cache\Adapters;

use DateInterval;
use DateTime;
use hamburgscleanest\GuzzleAdvancedThrottle\Cache\Helpers\RequestHelper;
use hamburgscleanest\GuzzleAdvancedThrottle\Cache\Interfaces\StorageInterface;
use hamburgscleanest\GuzzleAdvancedThrottle\RequestInfo;
use Illuminate\Config\Repository;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ArrayAdapter
 * @package hamburgscleanest\GuzzleAdvancedThrottle\Cache\Adapters
 */
class ArrayAdapter implements StorageInterface
{

    /** @var string */
    private const STORAGE_KEY = 'requests';
    /** @var string */
    private const RESPONSE_KEY = 'response';
    /** @var string */
    private const EXPIRATION_KEY = 'expires_at';

    /** @var array */
    private $_storage = [];

    /**
     * @param string $host
     * @param string $key
     * @param int $requestCount
     * @param \DateTime $expiresAt
     * @param int $remainingSeconds
     */
    public function save(string $host, string $key, int $requestCount, DateTime $expiresAt, int $remainingSeconds) : void
    {
        $this->_storage[$host][$key] = RequestInfo::create($requestCount, $expiresAt->getTimestamp(), $remainingSeconds);
    }

    /**
     * @param string $host
     * @param string $key
     * @return RequestInfo|null
     */
    public function get(string $host, string $key) : ? RequestInfo
    {
        return $this->_storage[$host][$key] ?? null;
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param int $duration
     * @throws \Exception
     */
    public function saveResponse(RequestInterface $request, ResponseInterface $response, int $duration = 300) : void
    {
        [$host, $path] = RequestHelper::getHostAndPath($request);

        $this->_storage[self::STORAGE_KEY][$host][$path] = [
            self::RESPONSE_KEY   => $response,
            self::EXPIRATION_KEY => (new DateTime())->add(new DateInterval('PT' . $duration . 'M'))->getTimestamp()
        ];
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface|null
     */
    public function getResponse(RequestInterface $request) : ? ResponseInterface
    {
        [$host, $path] = RequestHelper::getHostAndPath($request);

        $response = $this->_storage[self::STORAGE_KEY][$host][$path] ?? null;
        if ($response !== null)
        {
            if ($response[self::EXPIRATION_KEY] > \time())
            {
                return $response[self::RESPONSE_KEY];
            }

            $this->_invalidate($host, $path);
        }

        return null;
    }

    /**
     * @param string $host
     * @param string $path
     */
    private function _invalidate(string $host, string $path)
    {
        unset($this->_storage[self::STORAGE_KEY][$host][$path]);
    }

    /**
     * StorageInterface constructor.
     * @param Repository|null $config
     */
    public function __construct(?Repository $config = null)
    {

    }
}