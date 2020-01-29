<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\CSVFileType;
use App\Service\CSVImportWorker;
use App\Service\CSVMailSender;
use App\Service\DBProductExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{

    /**
     * @Route("/importfile", name="importfile")
     * @param Request $request
     * @param CSVImportWorker $csvImportWorker
     * @param CSVMailSender $csvMailSender
     * @return Response
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function csvImportAction(
        Request $request,
        CSVImportWorker $csvImportWorker,
        CSVMailSender $csvMailSender,
        ContainerInterface $container
    ) {
        $form = $this->createForm(CSVFileType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form["file"]->getData();
            $isTest = $form["test"]->getData();

            $destination = $this->getParameter('uploadDirectory');
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $file->move($destination, $originalFilename . ".csv");

            $container->get('old_sound_rabbit_mq.emailing_producer')->publish($destination . $originalFilename . ".csv");

//            try {
//                $csvImportWorker->importProducts($destination . $originalFilename . ".csv", $isTest);
//            } finally {
//                $csvImportWorker->removeFile($destination . $originalFilename . ".csv");
//            }
//
//            $csvMailSender->sendEmail(
//                [
//                    'total' => $csvImportWorker->totalCount,
//                    'skipped' => $csvImportWorker->skippedCount,
//                    'processed' => $csvImportWorker->processedCount,
//                    'products' => $csvImportWorker->products,
//                    'errors' => $csvImportWorker->getErrors(),
//                ],
//                $isTest
//            );

            $this->addFlash(
                'notice',
                'Your file was successfully uploaded!'
            );

            if ($csvImportWorker->getErrors()) {
                return $this->render("csv/errors.html.twig", [
                    'errors' => $csvImportWorker->getErrors(),
                ]);
            }

            return $this->render("csv/report.html.twig", [
                'total' => $csvImportWorker->totalCount,
                'skipped' => $csvImportWorker->skippedCount,
                'processed' => $csvImportWorker->processedCount,
                'products' => $csvImportWorker->products,
                'errors' => $csvImportWorker->getErrors(),
            ]);
        }
        return $this->render('csv/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/view", name="ProductsView")
     * @param Request $request
     * @param DBProductExporter $dbProductExporter
     * @return Response
     */
    public function view(Request $request, DBProductExporter $dbProductExporter)
    {
        $products = $dbProductExporter->ReturnProducts($request);
        return $this->render('csv/view.html.twig', [
            'products' => $products,
        ]);
    }
}

