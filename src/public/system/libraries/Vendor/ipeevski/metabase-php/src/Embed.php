<?php
namespace Metabase;
use Lcobucci\JWT\Token;

/**
 * Convenience class to embed Metabase dashboards and questions
 */
class Embed
{
    private $url;
    private $key;
    private $exp;

    public $border = false;
    public $title = false;
    public $theme;

    public $width = '100%';
    public $height = '1000';
//    public $height = '800';

    /**
     * Default constructor
     * $metabaseUrl string base url for the Metabase installation
     * $metabaseKey int secret Metabase key
     */
    public function __construct()
    {
        $ci = get_instance();
        $ci->load->model('model_settings');

        $metabaseKey = 'ae6d1865a5a5f98308d1fab73891d7372b55f12627d2e9bb1611517040c62a13';

        $setting_metabse_key = $ci->model_settings->getValueIfAtiveByName('metabase_key');
        if ($setting_metabse_key) {
            $metabaseKey = $setting_metabse_key;
        }
       
        $metabaseUrl = $ci->config->item('Metabase_Url');
        if (is_null($metabaseUrl)) {
            if (ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x')
                $metabaseUrl    = 'metabaseprd.conectala.com.br';
            // elseif(ENVIRONMENT === 'production_gcp') {   // Não usar. Utilizar o $config['Metabase_Url'] =  no config.php
            //    $metabaseUrl    = 'metabase-production-rtttdnrswa-uk.a.run.app';
            // }
            // elseif(ENVIRONMENT === 'development_gcp') {    // Não usar . Utilizar o $config['Metabase_Url'] = no config.php
            //    $metabaseUrl    = 'metabase-rtttdnrswa-uc.a.run.app';
            // }
            else
                $metabaseUrl    = 'metabaseteste.conectala.com.br';
        }

		if (strpos( current_url(), 'https://') === false) {
			$metabaseUrl  = 'http://'.$metabaseUrl;
		}
		else {
			$metabaseUrl  = 'https://'.$metabaseUrl;
		}
		
        $this->url = $metabaseUrl;
        $this->key = $metabaseKey;
        $this->exp = round(time()) + (60 * 60); # 60 minute expiration
    }

    /**
     * Get the embed URL for a Metabase question
     *
     * @param $questionId int id of the question to embed
     * @param $params array an associate array with variables to be passed to the question
     *
     * @return Embed URL
     */
    public function questionUrl($questionId, $params = array())
    {
        return $this->url('question', $questionId, $params);
    }

    /**
     * Get the embed URL for a Metabase dashboard
     *
     * @param $dashboardId int the id of the dashboard to embed
     * @param $params array an associate array with variables to be passed to the dashboard
     *
     * @return Embed URL
     */
    public function dashboardUrl($dashboardId, $params = array())
    {
        return $this->url('dashboard', $dashboardId, $params);
    }

    /**
     * Use JWT to encode tokens
     *
     * @param $resource array resource to encode (question or dashboard)
     * @param $params array an associate array with variables to be passed to the dashboard
     *
     * @return Token
     */
    private function encode($resource, $params)
    {
        $jwt = new \Lcobucci\JWT\Builder();
        $jwt->set('resource', $resource);
        if (empty($params)) {
            $jwt->set('params', (object)[]);
        } else {
            $jwt->set('params', $params);
        }
        if ($this->exp !== null) {
            $jwt->set('exp', $this->exp);
        }
        $jwt->sign(new \Lcobucci\JWT\Signer\Hmac\Sha256(), $this->key);

        return $jwt->getToken();
    }

    protected function url($resource, $id, $params)
    {
        // Generate auth token, using JWT
        $token = $this->encode(array($resource => $id), $params);

        // Generate embed URL
        $url = $this->url . '/embed/' . $resource . '/' . $token . '#';

        // Should border be included
        if ($this->border) {
            $url .= 'bordered=true&';
        } else {
            $url .= 'bordered=false&';
        }

        // Should title be included
        if ($this->title) {
            $url .= 'titled=true&';
        } else {
            $url .= 'titled=false&';
        }

        // Set selected theme (if any)
        if (!empty($this->theme)) {
            $url .= 'theme=' . $this->theme . '&';
        }

        // Remove trailing &
        $url = rtrim($url, '&');

        return $url;
    }

    /**
     * Generate the HTML to embed a question iframe with a given question id.
     * It assumes no iframe border. Size can be manipulated via
     * class $width/$height
     *
     * @param $questionId int the id of the question to embed
     * @param $params array an associate array with variables to be passed to the question
     *
     * @return string Code to embed
     */
    public function questionIFrame($questionId, $params = array())
    {
        $url = $this->questionUrl($questionId, $params);
        return $this->iframe($url);
    }

    /**
     * Generate the HTML to embed a dashboard iframe with a given dashboard id.
     * It assumes no iframe border. Size can be manipulated via
     * class $width/$height
     *
     * @param $dashboardId int the id of the dashboard to embed
     * @param $params array an associate array with variables to be passed to the dashboard
     *
     * @return string Code to embed
     */
    public function dashboardIFrame($dashboardId, $params = array())
    {
        $url = $this->dashboardUrl($dashboardId, $params);
        return $this->iframe($url);
    }

    /**
     * Generate the HTML to embed an iframe with a given URL.
     * It assumes no iframe border. Size can be manipulated via
     * class $width/$height
     *
     * @param $iframeUrl string the URL to create an iframe for
     *
     * @return string Code to embed
     */
    protected function iframe($iframeUrl)
    {
        if(ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x')
            $metabaseUrl    = 'metabaseprd.conectala.com.br';
        else
            $metabaseUrl    = 'metabaseteste.conectala.com.br';
		
		if (strpos( current_url(), 'https://') === false) {
			$metabaseUrl  = 'http://'.$metabaseUrl;
		}
		else {
			$metabaseUrl  = 'https://'.$metabaseUrl;
		}
		
        return "
            <script src='{$metabaseUrl}/app/iframeResizer.js'></script>
            <iframe 
                onload='iFrameResize({}, this)' 
                src='{$iframeUrl}'
                frameborder='0'
                width='{$this->width}'
                height='{$this->height}''
                allowtransparency
            ></iframe>";
    }
}