<?php

namespace App\Entities;

use App\Exceptions\Custom\RuntimeException;
use Illuminate\Support\Arr;

class ProfileEntity
{
    public string $name;
    public string $accessKey;
    public string $secretKey;
    public string $endpointUrl;
    public string $region;

    public function __construct(array $profile)
    {
        $this->name = Arr::get($profile, 'name') ??
            throw new RuntimeException("Invalid params [name] in " . static::class);

        $this->accessKey = Arr::get($profile, 'access-key') ??
            throw new RuntimeException("Invalid params [access-key] in " . static::class);

        $this->secretKey = Arr::get($profile, 'secret-key') ??
            throw new RuntimeException("Invalid params [secret-key] in " . static::class);

        $this->endpointUrl = Arr::get($profile, 'endpoint-url') ??
            throw new RuntimeException("Invalid params [endpoint-url] in " . static::class);

        $this->region = Arr::get($profile, 'region') ??
            $this->getDefaultRegion();
    }

    protected function getDefaultRegion(): string {
        return 'ru-msk';
    }

}
