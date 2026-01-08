<?php

namespace App\Services;

use Hashids\Hashids;

class HashidService
{
    protected $hashids;

    public function __construct()
    {
        $salt = config('app.id_salt', config('app.key'));
        $this->hashids = new Hashids($salt, 10);
    }

    public function encode(int $id): string
    {
        return $this->hashids->encode($id);
    }

    public function decode(string $hash): ?int
    {
        $decoded = $this->hashids->decode($hash);
        return !empty($decoded) ? $decoded[0] : null;
    }
}
