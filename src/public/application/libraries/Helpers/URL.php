<?php

namespace libraries\Helpers;

class URL
{
    private $originalUrl;

    private $protocol;
    private $host;
    private $port;
    private $user;
    private $pass;
    private $path;
    private $query = [];
    private $anchor;

    public function __construct(string $url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \Exception("{$url} is not a valid URL!");
        }
        $this->originalUrl = trim($url);
        $this->protocol = parse_url($this->originalUrl, PHP_URL_SCHEME);
        $this->user = parse_url($this->originalUrl, PHP_URL_USER);
        $this->pass = parse_url($this->originalUrl, PHP_URL_PASS);
        $this->host = parse_url($this->originalUrl, PHP_URL_HOST);
        $this->port = parse_url($this->originalUrl, PHP_URL_PORT);
        $this->path = parse_url($this->originalUrl, PHP_URL_PATH);
        $qryString = parse_url($this->originalUrl, PHP_URL_QUERY);
        parse_str($qryString, $this->query);
        $this->anchor = parse_url($this->originalUrl, PHP_URL_FRAGMENT);
    }

    private function getProtocolSegment(): string
    {
        return "{$this->protocol}://";
    }

    private function getUserPassSegment(): string
    {
        if (empty($this->user) || empty($this->pass)) return '';
        return "{$this->user}:{$this->pass}@";
    }

    private function getPortSegment(): string
    {
        if (empty($this->port)) return '';
        return ":{$this->port}";
    }

    private function getQuerySegment(): string
    {
        if (empty($this->query)) return '';
        $strQuery = http_build_query($this->query);
        return "?{$strQuery}";
    }

    private function getAnchorSegment(): string
    {
        if (empty($this->anchor)) return '';
        return "#{$this->anchor}";
    }

    public function addQuery(array $queryParams = []): URL
    {
        $this->query = array_merge($this->query, $queryParams);
        return $this;
    }

    public function getURL(): string
    {
        $strProtocol = $this->getProtocolSegment();
        $strUserPass = $this->getUserPassSegment();
        $strPort = $this->getPortSegment();
        $strQuery = $this->getQuerySegment();
        $srtAnchor = $this->getAnchorSegment();
        return "{$strProtocol}{$strUserPass}{$this->host}{$strPort}{$this->path}{$strQuery}{$srtAnchor}";
    }

    public static function retrieveServerCurrentURL(array $options = []): string
    {
        $protocol = (isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0 ? "https" : "http");
        $currentUrl = "{$protocol}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        try {
            return (new URL($currentUrl))->getURL();
        } catch (\Throwable $e) {
        }
        return $currentUrl;
    }
}