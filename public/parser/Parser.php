<?php

require_once($_SERVER[ 'DOCUMENT_ROOT' ] . '/parser/Connect.php');

/**
 * Class Parser
 */
class Parser extends Connect
{
    /*
     * @var DOMXPath
     */
    private $xpath;

    /**
     * Parser constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        parent::__construct();

        $dom = new DOMDocument();

        $html = file_get_contents($url);

        libxml_use_internal_errors(true);
        $dom->loadHTML($html); // loads your HTML
        $this->xpath = new \DOMXPath($dom);
        libxml_clear_errors();
    }

    /**
     * @param string $xpathString
     *
     * @return string
     */
    public function getValue(string $xpathString): string
    {
        /** @var DOMNodeList $list */
        $list = $this->parseElementList($xpathString);

        /** @var DOMElement $value */
        foreach ($list as $value) {
            return $value->textContent;
        }

        return '';
    }

    /**
     * @param string $xpathString
     * @param string $attribute
     *
     * @return string
     */
    public function getAttribute(string $xpathString, string $attribute): string
    {
        /** @var DOMNodeList $list */
        $list = $this->parseElementList($xpathString);

        /** @var DOMElement $value */
        foreach ($list as $value) {
            return $value->getAttribute($attribute);
        }

        return '';
    }

    /**
     * @param string $xpathString
     *
     * @return DOMNodeList
     */
    public function parseElementList(string $xpathString): DOMNodeList
    {
        return $this->xpath->query($xpathString);
    }

    /**
     * @param $file
     *
     * @return bool|mixed
     */
    private function getExtension($file)
    {
        $arr = explode(".", $file);
        $extension = end($arr);

        return $extension ? $extension : false;
    }

    /**
     * @param string|null $name
     * @param string $url
     * @param string $extension
     *
     * @return string
     */
    public function writeFileFromUrl(string $url, string $extension = null, string $name = null): string
    {
        if (empty($url) || substr($url, -3) != 'jpg' && substr($url, -3) != 'jpeg' && substr($url,
                -3) != 'gif' && substr($url, -3) != 'png') {
            return $this->domain2 . 'files/empty.png';
        }

        if (empty($extension)) {
            $extension = $this->getExtension($url);
        }

        if (empty($name)) {
            $name = rand(0, 1000) . rand(0, 1000) . rand(0, 1000) . '.' . $extension;
        }

        $file = file_get_contents($url);

        file_put_contents($_SERVER[ 'DOCUMENT_ROOT' ] . '/files/' . $name, $file);

        return $this->domain2 . 'files/' . $name;
    }
}
