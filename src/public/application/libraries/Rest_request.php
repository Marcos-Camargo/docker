<?php

class Rest_request
{
    public $response;
    public $code;
    private $handle;
    private $session;
    private $curlopts;
	public $cookiejar;

    public function __construct()
    {
        $this->handle = curl_init();
        $this->cookiejar = tempnam(sys_get_temp_dir(), 'session');

        $this->defaultHeaders = array(
            'Accept: application/json',
            'Content-Type: application/json',
        );

        $this->curlopts = array(
            CURLOPT_HTTPHEADER => $this->defaultHeaders,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookiejar,
            CURLOPT_COOKIEFILE => $this->cookiejar,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => TRUE,
        );
    }

    /**
     * @return (object) cURL handle
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @param (array) $headers Array of header values
     * @return (array) Headers
     */
    public function setHeaders($headers)
    {
        $this->curlopts[CURLOPT_HTTPHEADER] = array_merge($headers,$this->defaultHeaders);

        curl_setopt_array($this->handle, $this->curlopts);

        $this->getHeaders();
    }

    /**
     * @return (array) Headers
     */
    public function getHeaders()
    {
        return $this->curlopts[CURLOPT_HTTPHEADER];
    }

    /**
     * @param (string) $method GET/PUT/POST/DELETE
     * @param (string) $url Request URL
     * @param (json) $data JSON data (optional)
     * @return (boolean) TRUE
     */
    public function setUp($url, string $method, $data = '')
    {
        echo($url."\n");
        curl_setopt_array($this->handle, $this->curlopts);
        curl_setopt($this->handle, CURLOPT_URL, $url);

        if ($this->session) {
            curl_setopt($this->handle, CURLOPT_COOKIE, $this->session);
        }

        curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, null);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, null);
        curl_setopt($this->handle, CURLOPT_POST, false);

        switch (strtoupper($method)) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($this->handle, CURLOPT_POST, true);
                curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'PUT':
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'DELETE':
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        return true;
    }
    public function post($url, $data = '')
    {
        curl_setopt_array($this->handle, $this->curlopts);
        curl_setopt($this->handle, CURLOPT_URL, $url);

        if ($this->session) {
            curl_setopt($this->handle, CURLOPT_COOKIE, $this->session);
        }

        curl_setopt($this->handle, CURLOPT_POST, true);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);

        return true;
    }

    /**
     * @return (boolean) Success of HTTP request
     */
    public function send()
    {
        $this->response = curl_exec($this->handle);
        $this->code = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);

        if (!$this->session) {
            $this->session = session_id() . '=' . session_id() . '; path=' . session_save_path();
        }

        session_write_close();

        return !!$this->response;
    }
    public function sendREST($url, $data = '', $method = 'GET', $header_opt = array())
    {
        $curl_handle = curl_init();

        if ($method == "GET") {
            curl_setopt($curl_handle, CURLOPT_URL, $url);
        } elseif ($method == "POST" || $method == "PUT") {

            if ($method == "PUT")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
            
            if ($method == "POST")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'POST');          

            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $header_opt);          
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);
        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        curl_close($curl_handle);

        $header['httpcode'] = $httpcode;
        $header['content']  = $response; 
        return $header;
    }
}
