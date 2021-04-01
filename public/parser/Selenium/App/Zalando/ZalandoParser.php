<?php

namespace App\Zalando;

use App\Parser\AbstractParser;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class ZalandoParser extends AbstractParser
{
    protected $delayInSeconds = 3;

    protected const ONE_SIZE = 'onesize';

    protected $website = 'Zalando';

    public function isLoggedIn()
    {
        $testLink = 'https://www.zalando-lounge.de/event/gender_132';
        $this->driver->navigate()->to($testLink);

        try {
            $this->driver->wait(3)->until(WebDriverExpectedCondition::urlContains('event/gender'));
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function clickAcceptCookies(): void
    {
        if ($this->cookieCalls > 2) {
            return;
        }

        $this->cookieCalls++;

        //Wait for few seconds and then click to confirm cookies
        $this->setWaitingTime(2);

        try {
            $this->driver->findElement(WebDriverBy::xpath("//button[contains(@class, 'CookieConsent_close-button')]"))->click();
            $this->saveCookies();
            $this->unsetWaitingTime();

            return;
        } catch (\Exception $e) {

        }

        try {
            $this->driver->findElement(WebDriverBy::xpath("//button[contains(@class, 'styles__Close')]"))->click();
            $this->saveCookies();
            $this->unsetWaitingTime();

            return;
        } catch (\Exception $e) {

        }

        try {
            $this->driver->findElement(WebDriverBy::cssSelector("#uc-btn-accept-banner"))->click();
            $this->saveCookies();
            $this->unsetWaitingTime();

            return;
        } catch (\Exception $e) {

        }
    }

    protected function getProductData(string $htmlPage): ?array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);

        $dom->loadHTML($htmlPage);

        //$finder = new DomXPath($dom);
        //$containerObject = $finder->query("//script[@class='re-1-1' and contains(text(), 'enrichedEntity') and @data-re-asset]")->item(0);
        $containerObject = $dom->getElementById('z-vegas-pdp-props');
        libxml_use_internal_errors($internalErrors);
        $json = $containerObject->nodeValue;

        $json = substr(trim($json), 9); //Remove "window.__INITIAL_STATE__ =" from the string
        $json = substr($json, 0, -3);

        return json_decode($json, true);
    }

    protected function getCategoryData(?string $htmlPage): ?array
    {
        if($htmlPage === null){
            return null;
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);

        $dom->loadHTML($htmlPage);

        //$finder = new DomXPath($dom);
        //$containerObject = $finder->query("//script[@class='re-1-1' and contains(text(), 'enrichedEntity') and @data-re-asset]")->item(0);
        $containerObject = $dom->getElementById('z-nvg-cognac-props');
        libxml_use_internal_errors($internalErrors);
        $json = $containerObject->nodeValue;

        $json = substr(trim($json), 9); //Remove "window.__INITIAL_STATE__ =" from the string
        $json = substr($json, 0, -3);

        return json_decode($json, true);
    }

    protected function parseSizes(array $article): array
    {
        $sizes = [];

        foreach ($article['units'] as $size) {
            if ($size['available'] === true) {
                $sizeValue = $size['size']['local'];
                $quantity = $size['stock'];
                $sizes[$sizeValue] = $quantity;
            }
        }

        return $sizes;
    }

    protected function checkProductAvailable(string $link, string $size): bool
    {
        $htmlPage = $this->curl($link);
        $productData = $this->getProductData($htmlPage);

        if ($productData === null) {
            $this->log->error('No product data, check for ban!');

            return false;
        }

        $article = $productData['articleDetails']['article'];

        if ($article['stockStatus'] === 'SOLD_OUT') {
            $this->log->error('Sold out!');

            return false;
        }

        if ($size !== self::ONE_SIZE) {
            foreach ($article['simples'] as $sizeBlock) {
                if ($sizeBlock['size'] === $size) {
                    return ($sizeBlock['stockStatus'] === 'AVAILABLE') ? true : false;
                }
            }
        } else {
            return true;
        }

        return false;
    }

    protected function curl(string $link): string
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = [];
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Pragma: no-cache';
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $headers[] = 'Sec-Fetch-Site: same-origin';
        $headers[] = 'Sec-Fetch-Mode: navigate';
        $headers[] = 'Sec-Fetch-User: ?1';
        $headers[] = 'Sec-Fetch-Dest: document';
        $headers[] = 'Accept-Language: en-US,en;q=0.9,ru;q=0.8,az;q=0.7,la;q=0.6,tr;q=0.5,uk;q=0.4,fr;q=0.3,cy;q=0.2,uz;q=0.1,de;q=0.1';
        $headers[] = 'Referer: ' . $link;
        $headers[] = $this->cookiesString;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return $result;
    }

    protected function openInitialPage()
    {
        $this->driver->navigate()->to('https://en.zalando.de/handbags/armani.calvin-klein.kate-spade.adidas-originals.anna-field.dkny.fossil.furla.guess.kurt-geiger.liebeskind-berlin.marc-cain.marc-o-polo.michael-michael-kors.rebecca-minkoff.see-by-chloe.stradivarius.ted-baker/?price_to=170');
    }

    public function prepareParser(): void
    {
        $this->openInitialPage();

        $this->getAndApplyCookies();
    }
}
