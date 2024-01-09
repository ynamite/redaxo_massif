<?php

class mjmlClient
{
    const ENDPOINT = 'https://api.mjml.io/v1';

    /**
     * @var string
     */
    private $applicationId;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * Mjml constructor.
     *
     * @param string $applicationId
     * @param string $secretKey
     */
    public function __construct(string $applicationId, string $secretKey)
    {
        $this->applicationId = $applicationId;
        $this->secretKey = $secretKey;
    }

    /**
     * Render MJML to HTML.
     *
     * @param $mjml
     *
     * @return string The MJML markup to transpile to responsive HTML
     *
     * @throws Exception
     */
    public function render(string $mjml): string
    {
        $response = $this->request('/render', 'POST', json_encode(['mjml' => $mjml]));
        return $response['html'];
    }

    /**
     * @param $path
     * @param $method
     * @param $body
     * @param array|null $headers
     * @param array      $curlOptions
     *
     * @return mixed
     *
     * @throws Exception
     * @throws \RuntimeException
     */
    private function request(string $path, string $method, string $body, array $headers = null, array $curlOptions = []): array
    {
        if (!$headers) {
            $headers = [
                'Content-Type' => 'application/json',
            ];
        }

        $headers = array_map(function ($key, $value) {
            //If $value contains a ':' it is already in key:value format
            if (false !== strpos($value, ':')) {
                list($key, $value) = explode(':', $value);
            }

            return sprintf('%s: %s', $key, $value);
        }, array_keys($headers), $headers);

        $ch = curl_init(self::ENDPOINT.$path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->applicationId, $this->secretKey));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        foreach ($curlOptions as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new \RuntimeException(curl_error($ch));
        }
        curl_close($ch);

        $response = json_decode($response, true);
       
        if (json_last_error()) {
            throw new \RuntimeException(json_last_error_msg());
        }

        if (200 !== $statusCode) {
	        die(print_r($response));
            $requestId = isset($response['requestId']) ? $response['requestId'] : null;
            $startedAt = isset($response['startedAt']) ? new \DateTime($response['startedAt']) : null;
            throw new Exception($response['message'], $statusCode, null, $requestId, $startedAt);
        }

        return $response;
    }
    
    public static function fixEntities($str) {
	
		// '
		$str = str_replace(array("&amp;amp;#039;", "&#039;", "&amp;#039;"), "'", $str);
		
		// < 
		$str = str_replace(array("&lt;", "&amp;lt;"), "<", $str);
		
		// >
		$str = str_replace(array("&gt;","&amp;gt;"), ">", $str);
		
		// '
		$str = str_replace(array("&quot;","&amp;quot;"), '"', $str);
		
			
		// &
		$str = str_replace(array("&amp;amp;"), "&", $str);
	
		
		return $str;
	}
	
	public static function writeCacheFile($article_id) {
		
		$cache_path = self::getCacheFilename($article_id);
		$url = rex::getServer() .rex_getUrl($article_id);
		$html = file_get_contents($url);	
		file_put_contents($cache_path, $html);
	}
	
	public static function getCacheFile($article_id) {
		$template = file_get_contents(self::getCacheFilename($article_id));
		return $template;
	}
	
	public static function getCacheFilename($article_id) {
		return rex_path::base().'../data/mails/'.$article_id.'.html';
	}
}

rex_extension::register('STRUCTURE_CONTENT_SIDEBAR', function (rex_extension_point $ep) {
  
    $params = $ep->getParams();
    $subject = $ep->getSubject();
    
    if($params['category_id'] != 43)
    	return;

	if(rex_get('mjml_write_cache','int',0) == 1)
		$sync = mjmlClient::writeCacheFile($params['article_id']);
		
	$sentTo = rex_get('mjml_write_sendto','string',null);
		
	if($sentTo) {
		
		$mailManager = new mailManager();
		$mailManager->setTo($sentTo);
		$mailManager->setSubject('TEST: Artikel '.$params['article_id']);
		$mailManager->setTemplate($params['article_id']);
		$mailManager->send();

		
	}
	
	$cache_path = mjmlClient::getCacheFilename($params['article_id']);
	$version = 'Keine Vorlage';
	if(file_exists($cache_path))
		$version = date ("d.m.Y. H:i:s", filemtime($cache_path));
	
	$panel = '';
    $panel .= '<dl class="dl-horizontal text-left">';
    $panel .= '<dt>Vorlage Version</dt>';
    $panel .= '<div style="margin-bottom: 10px;">'.$version.'</div>';
    $panel .= '<dt>Vorlage testen</dt>';
    $panel .= '<div style="margin-bottom: 10px;">
    	<form action="https://dev.whatalife.ch'.$_SERVER['REQUEST_URI'].'" method="get">
    	<input type="hidden" name="page" value="content/edit" />
    	<input type="hidden" name="category_id" value="43" />
    	<input type="hidden" name="mode" value="edit" />
    	<input type="hidden" name="article_id" value="'.$params['article_id'].'" />

			<p><input type="text" name="mjml_write_sendto" /></p><p><button type="submit">senden</button></p>
		</form></div>';
	$panel .= '<dt>Vorlage erstellen?</dt>';
    $panel .= '<div style="margin-top: 10px;"><a href="'.rex_url::backendController(['page' => 'content/edit', 'mjml_write_cache' => 1, 'article_id' => $params['article_id'], 'clang' => 1, 'ctype' => 1]).'" class="btn btn-primary">speichern</a></div>';
    $panel .= '</dl>';
    
    $fragment = new rex_fragment();
    $fragment->setVar('title', '<i class="rex-icon rex-icon-info"></i> Mail Vorlage' , false);
    $fragment->setVar('body', $panel, false);
    $fragment->setVar('collapse', true);
    $fragment->setVar('collapsed', false);
    $content = $fragment->parse('core/page/section.php');

    return $content.$subject;
	
    
});



