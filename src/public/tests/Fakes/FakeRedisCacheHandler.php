<?php

namespace Tests\Fakes;

class FakeRedisCacheHandler
{
    public function has($key) {}
    public function lpush($key, $value) {}
    public function zadd($key, $score, $value) {}
    public function set($key, $value) {} // 👈 necessário para testes passarem
}
