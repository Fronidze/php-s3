<?php

namespace App\Helpers;

use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;

class RequestSignerVersionTwo
{
    const FULL_DATETIME_FORMAT = 'D, d M Y H:i:s O';
    private string $datetime;
    const SERVICE_NAME = 's3';

    public function __construct(
        protected MessageInterface $request,
        protected string           $region,
        protected string           $accessKey,
        protected string           $secretKey
    )
    {
        $this->datetime = gmdate(static::FULL_DATETIME_FORMAT);
        $this->request = $this->request->withHeader('Date', $this->datetime);
    }

    private function accessKey(): string
    {
        return $this->accessKey;
    }

    private function secretKey(): string
    {
        return $this->secretKey;
    }

    public function region(): string
    {
        return $this->region;
    }

    public function date(): string
    {
        $this->request = $this->request->withoutHeader('x-amz-date');
        $this->request = $this->request->withHeader('date', $this->datetime);
        return current($this->request->getHeader('date'));
    }

    public function signableHeaders(): array
    {
        $sorted = ['Content-MD5', 'Content-Type'];
        sort($sorted);
        return $sorted;
    }

    public function canonicalHeaders(): string
    {
        $headers = array();
        foreach ($this->request->getHeaders() as $name => $header) {
            $name = strtolower($name);
            if (str_starts_with($name, 'x-amz-')) {
                $value = trim(current($header));
                if ($value || $value === '0') {
                    $headers[$name] = $name . ':' . $value;
                }
            }
        }

        if (!$headers) {
            return '';
        }

        ksort($headers);
        return implode("\n", $headers) . "\n";
    }

    public function parseBucketName(): string
    {
        $host = $this->request->getHeader('Host');
        if (count($host) === 0) {
            return '';
        }

        $explodeHost = explode('.', current($host));
        $offset = 0;
        foreach ($explodeHost as $index => $value) {
            if ($value === self::SERVICE_NAME) {
                $offset = $index;
                break;
            }
        }

        return implode('.', array_slice($explodeHost, 0, $offset));
    }
    protected function availableResourceForSign(): array {
        return [
            'acl', 'cors', 'delete', 'lifecycle', 'location', 'logging',
            'notification', 'partNumber', 'policy', 'requestPayment', 'response-cache-control', 'response-content-disposition',
            'response-content-encoding', 'response-content-language', 'response-content-type', 'response-expires', 'restore',
            'tagging', 'torrent', 'uploadId', 'uploads', 'versionId', 'versioning', 'versions', 'website'
        ];
    }
    public function canonicalResource(): string
    {
        $bucketName = $this->parseBucketName();
        $path = $this->request->getUri()->getPath();

        if (str_starts_with("/{$bucketName}", $path) === false) {
            $resource = "/{$bucketName}{$path}";
        } else {
            $resource = $path;
        }

        $resource = str_replace('//', '/', $resource);
        $queryList = Query::parse($this->request->getUri()->getQuery());

        $first = true;
        foreach ($queryList as $key => $value) {

            if (in_array($key, $this->availableResourceForSign()) === false) {
                continue;
            }

            $resource .= $first ? '?' : '&';
            $first = false;
            $resource .= $key;
            if ($value !== '' && $value !== false && $value !== null) {
                $resource .= "={$value}";
            }
        }

        return str_replace(['//'], ['/'], $resource);
    }

    public function canonicalRequest(): string
    {
        $signableHeaders = '';
        foreach ($this->signableHeaders() as $header) {
            $headerValue = $this->request->getHeader($header);
            $signableHeaders .= current($headerValue) . "\n";
        }

        return sprintf(
            "%s\n%s%s\n%s%s",
            $this->request->getMethod(),
            $signableHeaders,
            $this->date(),
            $this->canonicalHeaders(),
            $this->canonicalResource()
        );
    }

    public function stringToSign(): string
    {
        return $this->canonicalRequest();
    }

    public function signedKey(): string
    {
        return $this->secretKey();
    }

    public function signature(): string
    {
        return base64_encode(hash_hmac('sha1', $this->stringToSign(), $this->signedKey(), true));
    }

    public function authorizationHeader(): string
    {
        return sprintf(
            "AWS %s:%s",
            $this->accessKey(),
            $this->signature()
        );
    }

    public function signRequest(): RequestInterface
    {
        return $this->request->withHeader('Authorization', $this->authorizationHeader());
    }
}
