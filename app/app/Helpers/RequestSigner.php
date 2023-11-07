<?php

namespace App\Helpers;

use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;

class RequestSigner
{
    const FULL_DATETIME_FORMAT = 'Ymd\THis\Z';
    const SERVICE_NAME = 's3';
    protected string $datetime;

    public function __construct(
        protected MessageInterface $request,
        protected string           $region,
        protected string           $accessKey,
        protected string           $secretKey,
        protected bool             $isDebug
    )
    {
        $this->datetime = gmdate(static::FULL_DATETIME_FORMAT);
        $this->request = $this->request->withHeader('x-amz-date', $this->datetime);
        $this->request = $this->request->withHeader('x-amz-content-sha256', $this->hashedPayload());
    }

    private function accessKey(): string
    {
        return $this->accessKey;
    }

    private function secretKey(): string
    {
        return $this->secretKey;
    }

    public function fullDate(): string
    {
        return $this->datetime;
    }

    public function shortDate(): string
    {
        return substr($this->datetime, 0, 8);
    }

    public function serviceName(): string
    {
        return static::SERVICE_NAME;
    }

    public function region(): string
    {
        return $this->region;
    }

    public function scope(): string
    {
        return sprintf(
            "%s/%s/%s/aws4_request",
            $this->shortDate(),
            $this->region(),
            $this->serviceName()
        );
    }

    public function hashedPayload(): string
    {
        $this->request->getBody()->seek(0);
        $content = $this->request->getBody()->getContents();
        $this->request->getBody()->seek(0);

        return hash('sha256', $content);
    }

    public function canonicalRequest(): string
    {
        return sprintf(
            "%s\n%s\n%s\n%s\n\n%s\n%s",
            $this->request->getMethod(),
            $this->canonicalPath(),
            $this->canonicalQuery(),
            $this->canonicalHeader(),
            $this->signedHeader(),
            $this->hashedPayload()
        );
    }

    public function canonicalPath(): string
    {
        $path = $this->request->getUri()->getPath();
        $doubleEncoded = rawurldecode(ltrim($path, '/'));
        return '/' . $doubleEncoded;
    }

    public function canonicalQuery(): string
    {
        $query = Query::parse($this->request->getUri()->getQuery());
        unset($query['X-Amz-Signature']);

        if (!$query) {
            return '';
        }

        $qs = '';
        ksort($query);
        foreach ($query as $k => $v) {
            if (!is_array($v)) {
                $qs .= rawurlencode($k) . '=' . rawurlencode($v !== null ? $v : '') . '&';
            } else {
                sort($v);
                foreach ($v as $value) {
                    $qs .= rawurlencode($k) . '=' . rawurlencode($value !== null ? $value : '') . '&';
                }
            }
        }

        return substr($qs, 0, -1);
    }

    protected function prepareHeader(): array
    {
        $headers = $this->request->getHeaders();
        $orderHeaders = [];
        foreach ($headers as $key => $values) {
            $key = strtolower($key);
            foreach ($values as $v) {
                $orderHeaders[$key][] = $v;
            }
        }

        ksort($orderHeaders);
        return $orderHeaders;
    }

    public function canonicalHeader(): string
    {
        $canonHeaders = [];
        foreach ($this->prepareHeader() as $k => $v) {
            if (count($v) > 0) {
                sort($v);
            }
            $canonHeaders[] = $k . ':' . preg_replace('/\s+/', ' ', implode(',', $v));
        }

        return implode("\n", $canonHeaders);
    }

    public function signedHeader(): string
    {
        return implode(';', array_keys($this->prepareHeader()));
    }

    public function stringToSign(): string
    {
        if ($this->isDebug === true) {
            echo sprintf(
                "canonicalRequest:\n%s\n",
                $this->canonicalRequest()
            );
        }
        return sprintf(
            "%s\n%s\n%s\n%s",
            "AWS4-HMAC-SHA256",
            $this->fullDate(),
            $this->scope(),
            hash('sha256', $this->canonicalRequest())
        );
    }

    public function signedKey(): string
    {
        $dateKey = hash_hmac('sha256', $this->shortDate(), "AWS4" . $this->secretKey(), true);
        $dateRegionKey = hash_hmac('sha256', $this->region(), $dateKey, true);
        $dateRegionServiceKey = hash_hmac('sha256', $this->serviceName(), $dateRegionKey, true);
        return hash_hmac('sha256', 'aws4_request', $dateRegionServiceKey, true);
    }

    public function signature(): string
    {
        return hash_hmac('sha256', $this->stringToSign(), $this->signedKey());
    }

    public function authorizationHeader(): string
    {
        return sprintf(
            "AWS4-HMAC-SHA256 Credential=%s, SignedHeaders=%s, Signature=%s",
            "{$this->accessKey()}/{$this->scope()}",
            $this->signedHeader(),
            $this->signature()
        );
    }

    public function signRequest(): RequestInterface
    {
        return $this->request->withHeader('Authorization', $this->authorizationHeader());
    }
}
