<?php

namespace App\Entities;

use App\Exceptions\Custom\RuntimeException;

class CredentialsEntity
{
    private ?string $version = null;
    private ?string $accessKey = null;
    private ?string $secretKey = null;
    private ?string $endpointUrl = null;
    private ?string $region = null;

    public function __construct()
    {
    }

    public function setVersionAuth(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getVersionAuth(): ?string
    {
        return $this->version;
    }

    public function setAccessKey(?string $accessKey): self
    {
        if ($accessKey === null) {
            throw new RuntimeException('Can not set AccessKey is null');
        }

        $this->accessKey = $accessKey;
        return $this;
    }

    public function getAccessKey(): ?string {
        return $this->accessKey;
    }

    public function setSecretKey(?string $secretKey): self
    {
        if ($secretKey === null) {
            throw new RuntimeException('Can not set SecretKey is null');
        }

        $this->secretKey = $secretKey;
        return $this;
    }

    public function getSecretKey(): ?string {
        return $this->secretKey;
    }

    public function setEndpointUrl(?string $endpointUrl): self
    {
        if ($endpointUrl === null) {
            throw new RuntimeException('Can not set EndpointUrl is null');
        }

        $this->endpointUrl = $endpointUrl;
        return $this;
    }

    public function getEndpointUrl(): ?string {
        return $this->endpointUrl;
    }

    public function setRegion(?string $region): self
    {
        if ($region === null) {
            throw new RuntimeException('Can not set region is null');
        }

        $this->region = $region;
        return $this;
    }

    public function getRegion(): ?string {
        return $this->region;
    }

}
