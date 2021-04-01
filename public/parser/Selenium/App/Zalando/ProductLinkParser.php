<?php

namespace App\Zalando;

use App\Services\Exception\ParserException;

class ProductLinkParser extends ZalandoParser
{
    private $limit;
    private $totalCount = 0;

    /**
     * @return array
     * @throws ParserException
     */
    public function parse(): array
    {
        $startTime = microtime(true);

        $link = $this->task->getLink();
        $limit = (int)$this->task->getLimit();
        $productLinks = $this->paginateAndParseProductLinks($link, $limit);

        if (count($productLinks) === 0) {
            return [];
        }

        $endTime = microtime(true);
        $elapsed = $endTime - $startTime;

        //$this->log->info("Execution time : $elapsed seconds for " . count($productLinks) . " products");

        return $productLinks;
    }

    private function getPagesCount(string $link): int
    {
        $xmlPage = $this->curl($link);
        $xmlPage = $this->getCategoryData($xmlPage);

        if (!$xmlPage['pagination']['page_count']) {
            throw new ParserException('Can not get pages count, check for ban (count issue)!');
        }

        return (int)$xmlPage['pagination']['page_count'];
    }

    /**
     * @throws ParserException
     */
    private function paginateAndParseProductLinks(string $link, int $limit): array
    {
        $this->limit = $limit;
        $pagesCount = $this->getPagesCount($link);

        $products = [];

        for ($i = 1; $i <= $pagesCount; $i++) {
            if ((int)$limit != 0 && $this->totalCount === (int)$limit) {
                break;
            }

            $products[] = $this->parseProductLinksFromOnePage($link, $i);
            sleep($this->delayInSeconds);
        }

        return $products;
    }

    /**
     * @throws ParserException
     */
    private function parseProductLinksFromOnePage(string $link, int $pageNumber): array
    {
        if (stristr($link, '?')) {
            $connectingSymbol = '&';
        } else {
            $connectingSymbol = '?';
        }

        if ($pageNumber === 1) {
            $newLink = $link;
        } else {
            $orderBy = '&order=price&dir=asc';
            if (stristr($link, $orderBy)) {
                $linkWithoutOrder = str_replace($orderBy, '', $link);
                $newLink = $linkWithoutOrder . $connectingSymbol . 'p=' . $pageNumber . $orderBy;
            } else {
                $newLink = $link . $connectingSymbol . 'p=' . $pageNumber;
            }
        }

        $htmlPage = $this->curl($newLink);
        $categoryData = $this->getCategoryData($htmlPage);

        if ($categoryData === null) {
            throw new ParserException('Corrupt category... Please recheck it! Link: ' . $newLink);
        }

        $products = [];

        $articles = $categoryData['articles'];

        foreach ($articles as $article) {
            if ((int)$this->limit != 0 && $this->totalCount === $this->limit) {
                break;
            }

            $url = $article['url_key'];

            if (!$url) {
                throw new ParserException('Check DOM elements!');
            }

            $url = 'https://en.zalando.de/' . $url . '.html';

            $products[] = $url;

            $this->totalCount++;
        }

        return $products;
    }
}
