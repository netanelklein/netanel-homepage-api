<?php

namespace Api\Controllers;

use Api\Core\Request;
use Api\Core\Response;
use Api\Models\PortfolioModel;
use Api\Services\LoggingService;

/**
 * CV Controller
 * 
 * Handles CV generation and download functionality
 * Generates dynamic PDF resumes based on portfolio data
 */
class CvController extends BaseController
{
    private PortfolioModel $portfolioModel;
    private LoggingService $logger;

    public function __construct()
    {
        parent::__construct();
        $this->portfolioModel = new PortfolioModel();
        $this->logger = new LoggingService();
    }

    /**
     * Generate and download CV as PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function download(Request $request): Response
    {
        try {
            // Get portfolio data
            $personalInfo = $this->portfolioModel->getPersonalInfo();
            $skills = $this->portfolioModel->getSkills();
            $experience = $this->portfolioModel->getExperience();
            $education = $this->portfolioModel->getEducation();
            $projects = $this->portfolioModel->getProjects();

            if (!$personalInfo) {
                return $this->errorResponse('Personal information not found', 404);
            }

            // Generate CV content
            $cvData = [
                'personal_info' => $personalInfo,
                'skills' => $skills,
                'experience' => $experience,
                'education' => $education,
                'projects' => array_slice($projects, 0, 3) // Top 3 projects only
            ];

            // Check for different output formats
            $format = $request->getQuery('format', 'pdf');
            
            switch ($format) {
                case 'pdf':
                    return $this->generatePdfCv($cvData);
                case 'html':
                    return $this->generateHtmlCv($cvData);
                case 'json':
                    return $this->jsonResponse($cvData);
                default:
                    return $this->errorResponse('Unsupported format. Use: pdf, html, json', 400);
            }

        } catch (\Exception $e) {
            $this->logger->error('CV generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to generate CV', 500);
        }
    }

    /**
     * Generate PDF CV using HTML to PDF conversion
     * 
     * @param array $cvData
     * @return Response
     */
    private function generatePdfCv(array $cvData): Response
    {
        // Generate HTML first
        $html = $this->generateCvHtml($cvData);
        
        // For a vanilla PHP implementation without external libraries,
        // we'll use wkhtmltopdf if available, or serve HTML with print styles
        if ($this->isWkhtmltopdfAvailable()) {
            return $this->convertHtmlToPdf($html, $cvData['personal_info']['full_name']);
        } else {
            // Fallback: serve HTML with print-optimized CSS
            return $this->generateHtmlCv($cvData, true);
        }
    }

    /**
     * Generate HTML CV
     * 
     * @param array $cvData
     * @param bool $printOptimized
     * @return Response
     */
    private function generateHtmlCv(array $cvData, bool $printOptimized = false): Response
    {
        $html = $this->generateCvHtml($cvData, $printOptimized);
        
        $headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600'
        ];

        if ($printOptimized) {
            $headers['Content-Disposition'] = 'inline; filename="cv_' . 
                preg_replace('/[^a-zA-Z0-9]/', '_', $cvData['personal_info']['full_name']) . '.html"';
        }

        return new Response($html, 200, $headers);
    }

    /**
     * Generate CV HTML content
     * 
     * @param array $cvData
     * @param bool $printOptimized
     * @return string
     */
    private function generateCvHtml(array $cvData, bool $printOptimized = false): string
    {
        $personalInfo = $cvData['personal_info'];
        $skills = $cvData['skills'];
        $experience = $cvData['experience'];
        $education = $cvData['education'];
        $projects = $cvData['projects'];

        $printStyles = $printOptimized ? $this->getPrintStyles() : '';

        $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>CV - {$personalInfo['full_name']}</title>
    <style>
        {$this->getCvStyles()}
        {$printStyles}
    </style>
</head>
<body>
    <div class='cv-container'>
        <header class='cv-header'>
            <h1>{$personalInfo['full_name']}</h1>
            <h2>{$personalInfo['title']}</h2>
            <div class='contact-info'>
                <p><strong>Email:</strong> {$personalInfo['email']}</p>
                <p><strong>Phone:</strong> {$personalInfo['phone']}</p>
                <p><strong>Location:</strong> {$personalInfo['location']}</p>
                <p><strong>Website:</strong> {$personalInfo['website']}</p>
            </div>
        </header>

        <section class='cv-section'>
            <h3>Professional Summary</h3>
            <p>{$personalInfo['bio']}</p>
        </section>";

        // Skills section
        if (!empty($skills)) {
            $html .= "<section class='cv-section'>
                <h3>Technical Skills</h3>
                <div class='skills-grid'>";
            
            $skillsByCategory = [];
            foreach ($skills as $skill) {
                $skillsByCategory[$skill['category']][] = $skill;
            }

            foreach ($skillsByCategory as $category => $categorySkills) {
                $html .= "<div class='skill-category'>
                    <h4>" . ucfirst($category) . "</h4>
                    <ul>";
                foreach ($categorySkills as $skill) {
                    $html .= "<li>{$skill['name']} ({$skill['proficiency_level']}/10)</li>";
                }
                $html .= "</ul></div>";
            }
            $html .= "</div></section>";
        }

        // Experience section
        if (!empty($experience)) {
            $html .= "<section class='cv-section'>
                <h3>Professional Experience</h3>";
            
            foreach ($experience as $exp) {
                $startDate = date('M Y', strtotime($exp['start_date']));
                $endDate = $exp['end_date'] ? date('M Y', strtotime($exp['end_date'])) : 'Present';
                
                $html .= "<div class='experience-item'>
                    <h4>{$exp['position']}</h4>
                    <p class='company'>{$exp['company']} | {$startDate} - {$endDate}</p>
                    <p>{$exp['description']}</p>
                </div>";
            }
            $html .= "</section>";
        }

        // Projects section
        if (!empty($projects)) {
            $html .= "<section class='cv-section'>
                <h3>Key Projects</h3>";
            
            foreach ($projects as $project) {
                $html .= "<div class='project-item'>
                    <h4>{$project['title']}</h4>
                    <p>{$project['short_description']}</p>
                    <p><strong>Technologies:</strong> {$project['technologies']}</p>
                </div>";
            }
            $html .= "</section>";
        }

        // Education section
        if (!empty($education)) {
            $html .= "<section class='cv-section'>
                <h3>Education</h3>";
            
            foreach ($education as $edu) {
                $startYear = date('Y', strtotime($edu['start_date']));
                $endYear = $edu['end_date'] ? date('Y', strtotime($edu['end_date'])) : 'Present';
                
                $html .= "<div class='education-item'>
                    <h4>{$edu['degree']}</h4>
                    <p>{$edu['institution']} | {$startYear} - {$endYear}</p>
                    <p>{$edu['description']}</p>
                </div>";
            }
            $html .= "</section>";
        }

        $html .= "
        </div>
    </body>
</html>";

        return $html;
    }

    /**
     * Get CV CSS styles
     * 
     * @return string
     */
    private function getCvStyles(): string
    {
        return "
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                background: #f5f5f5;
            }
            
            .cv-container {
                max-width: 800px;
                margin: 20px auto;
                background: white;
                padding: 40px;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            
            .cv-header {
                text-align: center;
                border-bottom: 3px solid #2c3e50;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            
            .cv-header h1 {
                font-size: 2.5em;
                color: #2c3e50;
                margin-bottom: 5px;
            }
            
            .cv-header h2 {
                font-size: 1.3em;
                color: #7f8c8d;
                font-weight: normal;
                margin-bottom: 15px;
            }
            
            .contact-info {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 20px;
            }
            
            .contact-info p {
                margin: 0;
                font-size: 0.9em;
            }
            
            .cv-section {
                margin-bottom: 30px;
            }
            
            .cv-section h3 {
                color: #2c3e50;
                font-size: 1.4em;
                border-bottom: 2px solid #3498db;
                padding-bottom: 5px;
                margin-bottom: 15px;
            }
            
            .skills-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
            }
            
            .skill-category h4 {
                color: #3498db;
                margin-bottom: 8px;
            }
            
            .skill-category ul {
                list-style: none;
            }
            
            .skill-category li {
                padding: 2px 0;
                font-size: 0.9em;
            }
            
            .experience-item, .project-item, .education-item {
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #ecf0f1;
            }
            
            .experience-item:last-child,
            .project-item:last-child,
            .education-item:last-child {
                border-bottom: none;
            }
            
            .experience-item h4, .project-item h4, .education-item h4 {
                color: #2c3e50;
                margin-bottom: 5px;
            }
            
            .company {
                color: #3498db;
                font-weight: 600;
                margin-bottom: 8px;
            }
        ";
    }

    /**
     * Get print-optimized CSS styles
     * 
     * @return string
     */
    private function getPrintStyles(): string
    {
        return "
            @media print {
                body {
                    background: white;
                    font-size: 12pt;
                }
                
                .cv-container {
                    margin: 0;
                    padding: 0;
                    box-shadow: none;
                    max-width: none;
                }
                
                .cv-section {
                    page-break-inside: avoid;
                }
                
                .cv-header {
                    page-break-after: avoid;
                }
                
                .experience-item, .project-item, .education-item {
                    page-break-inside: avoid;
                }
            }
            
            @page {
                margin: 1in;
                size: A4;
            }
        ";
    }

    /**
     * Check if wkhtmltopdf is available
     * 
     * @return bool
     */
    private function isWkhtmltopdfAvailable(): bool
    {
        $output = [];
        $returnVar = 0;
        exec('which wkhtmltopdf', $output, $returnVar);
        return $returnVar === 0;
    }

    /**
     * Convert HTML to PDF using wkhtmltopdf
     * 
     * @param string $html
     * @param string $filename
     * @return Response
     */
    private function convertHtmlToPdf(string $html, string $filename): Response
    {
        $tempHtmlFile = tempnam(sys_get_temp_dir(), 'cv_') . '.html';
        $tempPdfFile = tempnam(sys_get_temp_dir(), 'cv_') . '.pdf';
        
        try {
            // Write HTML to temp file
            file_put_contents($tempHtmlFile, $html);
            
            // Convert to PDF
            $command = "wkhtmltopdf --page-size A4 --margin-top 0.75in --margin-right 0.75in --margin-bottom 0.75in --margin-left 0.75in '$tempHtmlFile' '$tempPdfFile'";
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0 || !file_exists($tempPdfFile)) {
                throw new \Exception('PDF conversion failed');
            }
            
            $pdfContent = file_get_contents($tempPdfFile);
            $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $filename);
            
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"cv_{$safeFilename}.pdf\"",
                'Content-Length' => strlen($pdfContent),
                'Cache-Control' => 'public, max-age=3600'
            ];
            
            return new Response($pdfContent, 200, $headers);
            
        } finally {
            // Cleanup temp files
            if (file_exists($tempHtmlFile)) {
                unlink($tempHtmlFile);
            }
            if (file_exists($tempPdfFile)) {
                unlink($tempPdfFile);
            }
        }
    }

    /**
     * Get CV statistics for analytics
     * 
     * @param Request $request
     * @return Response
     */
    public function getStats(Request $request): Response
    {
        try {
            // This would typically track download counts, etc.
            // For now, return basic info
            $stats = [
                'total_downloads' => 0, // Would be tracked in database
                'formats_available' => ['pdf', 'html', 'json'],
                'last_updated' => date('Y-m-d H:i:s')
            ];
            
            return $this->jsonResponse($stats);
            
        } catch (\Exception $e) {
            $this->logger->error('CV stats retrieval failed', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve CV statistics', 500);
        }
    }
}
