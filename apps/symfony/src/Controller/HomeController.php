<?php

namespace App\Controller;

use App\Model\Dto\CrawlProcessOptionsDto;
use App\Service\CrawlManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    // $crawlProcessOptions = (new CrawlProcessOptionsDto())->setUrl('https://agencyanalytics.com/');
    // $report = $crawlerService->crawl('https://agencyanalytics.com/');
    // $report = $crawlerService->crawlLink('https://minimal-kit-react.vercel.app/dashboard/app');

    /**
     * @throws \Exception
     */
    #[Route('/', name: 'app_home')]
    public function index(
        Request $request,
        CrawlManager $crawlManager
    ): Response {
        if ($request->getMethod() === 'POST') {
            try {
                $crawlProcessOptions = (new CrawlProcessOptionsDto())->initializeFromRequest($request);
                $report = $crawlManager->crawl($crawlProcessOptions);
            } catch (\Throwable $e) {
                return $this->render('home/index.html.twig', [
                    'errorMessage' => $e->getMessage(),
                ]);
            }
            return $this->render('home/index.html.twig', [
                'report' => $report,
            ]);
        }

        return $this->render('home/index.html.twig');
    }
}
