<?php

class rex_api_massif_get extends rex_api_function
{
    protected $published = true;

    protected $action;

    function execute()
    {

        $this->action = rex_request('action', 'string', '');

        $response = '';

        switch ($this->action) {

            case 'fragment':
                $response = $this->getFragment();
                break;
            case 'article_content':
                $response = $this->getArticleContent();
                break;
            case 'img':
                $response = $this->getImg();
                break;
        }

        $this->outputPrepare($response);
        exit();
    }

    private function getFragment()
    {
        $requestParams = $this->getJsonData();
        $fragment_name = rex_request('fragmentName', 'string', '');
        if (!$fragment_name && isset($requestParams['fragmentName']))
            $fragment_name = $requestParams['fragmentName'];
        $context = rex_request('context', 'string', '');
        if (!$context && isset($requestParams['context']))
            $context = $requestParams['context'];

        if (!$fragment_name) {
            return (new rex_api_error())->setMessage('no fragment name found');
        }

        // echo var_dump($params);
        $fragment = massif_utils::parse($fragment_name, null, ['context' => $context]);

        return (new rex_api_response())->setData(sprogdown($fragment));
    }

    private function getArticleContent()
    {

        $url = rex_post('url', 'string', '');
        $article_id = rex_post('article_id', 'int', 0);

        if (!$url && !$article_id) {
            $result = ['errorcode' => 1, 'required params missing'];
            self::httpError($result);
        }

        require(rex_path::assets("theme/template/config.php"));

        if ($url) {

            $fullUrl = rex_yrewrite::getFullPath() . ltrim($url, '/');
            $dataset_id = \Url\Generator::getId($fullUrl);
            if ($dataset_id) {
                $urlObject = self::getUrlData($fullUrl);
                rex::setProperty('addon-page-id', $dataset_id);
                rex::setProperty('addon-page-url', $urlObject->url);
                rex::setProperty('addon-page-key', $urlObject->urlParamKey);
                $article_id = $urlObject->articleId;
            }

            if (!$article_id) {
                $article_id_array = \rex_yrewrite::getArticleIdByUrl(\rex_yrewrite::getCurrentDomain(), ltrim($url, '/'));
                if (!is_array($article_id_array)) {
                    $result = ['errorcode' => 1, 'article not found'];
                    self::httpError($result);
                }
                $article_id = array_key_first($article_id_array);
            }
        }

        if (!(int) $article_id) {
            $result = ['errorcode' => 1, 'article ID not found'];
            self::httpError($result);
        }

        $article = rex_article::get($article_id);
        $article_content = new rex_article_content($article_id);

        // permissions prüfen
        if (!$article->isPermitted()) {
            $result = ['errorcode' => 1, 'access denied'];
            self::httpError($result);
        }

        $content = $article_content->getArticle();
        // Artikel senden
        header('Content-Type: text/html; charset=UTF-8');
        return sprogdown($content);
    }

    private function getImg()
    {

        $img = rex_post('img', 'string', '');
        $type = rex_post('type', 'string', '');


        if (!$img || !$type) {
            $result = ['errorcode' => 1, 'required params missing'];
            self::httpError($result);
        }

        if (!in_array($type, ['content', 'cover'])) {
            $result = ['errorcode' => 1, 'invalid type'];
            self::httpError($result);
        }

        $img = massif_img::get($img, ['type' => $type]);

        header('Content-Type: text/html; charset=UTF-8');
        return sprogdown($img);
    }

    private static function getUrlData($url)
    {
        $currentUrl = \Url\Url::parse($url);
        foreach (Url\Generator::$paths as $domain => $articleIds) {
            if ($currentUrl->getDomain() == $domain) {
                foreach ($articleIds as $articleId => $urlParamKeys) {
                    foreach ($urlParamKeys as $urlParamKey => $ids) {
                        foreach ($ids as $id => $clangIds) {
                            foreach ($clangIds as $clangId => $object) {
                                if ($currentUrl->getPath() == $object['url'] || in_array($currentUrl->getPath(), $object['pathNames']) || in_array($currentUrl->getPath(), $object['pathCategories'])) {
                                    return (object) $object;
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    private function getJsonData()
    {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);
        return $decoded;
    }

    public function outputPrepare($response)
    {
        if ($response instanceof rex_api_error) {
            $this->throwError($response);
        } else if ($response) {
            $this->outputSend($response);
        }
    }

    public function outputSend($response)
    {
        rex_response::setStatus($response->getStatus());
        rex_response::sendContent(json_encode($response), $response->getContentType());
        exit();
    }

    public function throwError($error)
    {
        rex_response::setStatus($error->getStatus());
        rex_response::sendContent(json_encode($error), $error->getContentType());
        exit();
    }
}
