<?php

namespace App\Controller;

use App\Entity\QuizAttempt;
use App\Entity\QuizResult;
use App\Repository\QuizAttemptRepository;
use App\Repository\QuizResultRepository;
use App\Service\QuizAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/advanced-pdf')]
class AdvancedPdfReportController extends AbstractController
{
    public function __construct(
        private Pdf $pdf,
        private EntityManagerInterface $entityManager,
        private QuizResultRepository $quizResultRepository,
        private QuizAttemptRepository $quizAttemptRepository,
        private QuizAnalysisService $analysisService
    ) {}

    #[Route('/generate/{quizResultId}', name: 'advanced_pdf_generate')]
    public function generateAdvancedReport(int $quizResultId): Response
    {
        $quizResult = $this->quizResultRepository->find($quizResultId);
        
        if (!$quizResult) {
            throw $this->createNotFoundException('Quiz result not found');
        }

        // Get additional data for analysis
        $quizAttempts = $this->quizAttemptRepository->findBy([
            'quiz' => $quizResult->getQuiz(),
            'user' => $quizResult->getUser()
        ]);

        // Generate intelligent analysis data
        $analysisData = $this->analysisService->generateIntelligentAnalysis($quizResult, $quizAttempts);
        
        // Generate QR code for verification
        $qrCodeData = $this->generateQRCode($quizResult);
        
        // Render HTML template
        $html = $this->renderView('advanced_pdf/report.html.twig', [
            'quizResult' => $quizResult,
            'analysis' => $analysisData,
            'qrCode' => $qrCodeData,
            'user' => $quizResult->getUser(),
            'quiz' => $quizResult->getQuiz()
        ]);

        // Generate PDF
        return new Response(
            $this->pdf->getOutputFromHtml($html, [
                'orientation' => 'Portrait',
                'default-header' => true,
                'margin-top' => '20mm',
                'margin-bottom' => '20mm',
                'margin-left' => '15mm',
                'margin-right' => '15mm',
                'header-html' => $this->renderView('advanced_pdf/header.html.twig'),
                'footer-html' => $this->renderView('advanced_pdf/footer.html.twig')
            ]),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="rapport-quiz-%s-%s.pdf"', 
                    $quizResult->getUser()->getFullName(), 
                    $quizResult->getQuiz()->getTitle()
                )
            ]
        );
    }

    private function generateQRCode(QuizResult $quizResult): string
    {
        $verificationUrl = $this->generateUrl('quiz_verification', [
            'resultId' => $quizResult->getId(),
            'token' => md5($quizResult->getId() . $quizResult->getCreatedAt()->format('Y-m-d H:i:s'))
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = Builder::create()
            ->data($verificationUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelLow())
            ->size(150)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        return $result->getDataUri();
    }
}
