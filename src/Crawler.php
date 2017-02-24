<?php
namespace CViniciusSDias\GoogleCrawler;

use CViniciusSDias\GoogleCrawler\Proxy\{
    GoogleProxy, NoProxy
};
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\DomCrawler\Link;

class Crawler
{
    /** @var string $url*/
    protected $url;
    /** @var GoogleProxy $proxy */
    protected $proxy;

    public function __construct(SearchTerm $searchTerm, GoogleProxy $proxy = null)
    {
        // You can concatenate &gl=XX replacing XX with your country code (BR = Brazil; US = United States)
        // You should also add the coutry specific part of the google url, (like .br or .es)
        $this->url = "http://www.google.com/search?q=$searchTerm&num=100";
        $this->proxy = is_null($proxy) ? new NoProxy() : $proxy;
    }

    /**
     * Returns the found results for the specified search term
     *
     * @return ResultList
     */
    public function getResults(): ResultList
    {
        /** @var ResponseInterface $response */
        $response = $this->proxy->getHttpResponse($this->url);
        $stringResposta = (string) $response->getBody();
        $domCrawler = new DomCrawler($stringResposta);
        $googleResults = $domCrawler->filter('h3.r > a');
        $resultList = new ResultList($googleResults->count());

        foreach ($googleResults as $result) {
            $resultLink = new Link($result, 'http://google.com/');
            $googleResult = $this->parseResult($resultLink);
            $resultList->addResult($googleResult);
        }

        return $resultList;
   }

    /**
     * If $resultLink is a valid link, this method assembles the Result and adds it to $googleResults
     *
     * @param Link $resultLink
     * @return Result
     */
    private function parseResult(Link $resultLink): Result
    {
        $googleResult = new Result();
        $googleResult
            ->setTitle($resultLink->getNode()->nodeValue)
            ->setUrl($this->getUrl($resultLink->getUri()));

        return $googleResult;
    }

    private function getUrl(string $url): string
    {
        return $this->proxy->parseUrl($url);
    }
}