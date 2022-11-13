<?php

namespace App\Service\Request;

use App\Model\Dto\WebRequestParameter;
use GuzzleHttp\Psr7\Response;
use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use Psr\Log\LoggerInterface;

/**
 * Not needed for the challenge but here what the usage of a headless browser would look-like
 * for a single page application website (where the DOM is produced by the JS such as ReactJs, Angular...)
 */
class HeadlessChromeRequestService implements WebRequestServiceInterface
{
    private ProcessAwareBrowser $browser;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
        $browserFactory = new BrowserFactory('google-chrome');
        $this->browser = $browserFactory->createBrowser([
            // 'connectionDelay' => 1,
            // 'debugLogger' => 'php://stdout',
            'noSandbox' => true, // needed for docker env
            'sendSyncDefaultTimeout' => 30000,
            'startupTimeout' => 15
        ]);
    }

    public function request(string $url): Response
    {
        $this->logger->info('-- Starting web request through HeadlessChromeRequestService to : ' . $url);
        $httpCode = \Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR;
        $result = null;
        $headers = [];

        try {
            $page = $this->browser->createPage();

            /* Catch HTTP status code as chrome-php does not provide a simple way to do so */
            $page->getSession()->on(
                "method:Network.responseReceived",
                function ($params) use (&$httpCode, &$headers) {
                    $response = $params['response'];
                    $httpCode = $response['status'];
                }
            );

            $page->navigate($url)->waitForNavigation(Page::NETWORK_IDLE);
            $events = $page->getFrameManager()?->getMainFrame()?->getLifeCycle();
            if ($events && isset($events['networkIdle'])) {
                $headers[WebRequestParameter::HEADER_NAME_SERVER_TIMING] = number_format(
                    $events['networkIdle'] / 1000,
                    3
                );
            }
            $result = $page->getHTML();
            $this->browser->close();
        } catch (\Throwable $exception) {
            $this->logger->critical($exception);
        }

        return new Response($httpCode, $headers, $result);
    }
}