<?php

namespace App\Services;

use App\Models\AgreementTemplate;
use App\Models\AgreementTemplateRender;
use Illuminate\Support\Facades\Storage;

class AgreementTemplateRenderService
{
    protected AgreementTemplateStorageService $storageService;
    protected AgreementTemplateVariableService $variableService;

    public function __construct(
        AgreementTemplateStorageService $storageService,
        AgreementTemplateVariableService $variableService
    ) {
        $this->storageService = $storageService;
        $this->variableService = $variableService;
    }

    /**
     * Render a template preview — merge values into DOCX, convert to PDF.
     */
    public function renderPreview(AgreementTemplate $template, array $values = []): array
    {
        $renderDir = $this->storageService->ensureRenderDirectory($template->id);

        $docxPath = $template->source_docx_path;
        $renderedDocxPath = null;

        $tokenService = new TemplateTokenService();
        $templateText = $this->variableService->resolveTemplateText($template);
        $tokens = $tokenService->extractTokens($templateText);

        $defaultValues = $this->variableService->buildDefaultValues($tokens);
        $mergedValues = array_replace($defaultValues, $values);

        // === DOCX MERGE (if DOCX source exists) ===
        if (! empty($docxPath) && $this->storageService->docxExists($template)) {
            try {
                $sourcePath = $this->storageService->getLocalPath($docxPath);
                $processor = new \PhpOffice\PhpWord\TemplateProcessor($sourcePath);

                foreach ($mergedValues as $key => $val) {
                    $processor->setValue($key, (string) ($val ?? ''));
                }

                $renderedDocxName = 'rendered_' . $template->id . '_' . time() . '.docx';
                $renderedDocxPath = $renderDir . '/' . $renderedDocxName;
                $processor->saveAs(Storage::disk('local')->path($renderedDocxPath));
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Failed to render DOCX preview: ' . $e->getMessage()];
            }
        }

        // === BUILD HTML CONTENT ===
        $html = '';
        if ($renderedDocxPath && Storage::disk('local')->exists($renderedDocxPath)) {
            $html = $this->extractHtmlFromDocxPath(Storage::disk('local')->path($renderedDocxPath));
        } elseif (! empty($template->template_html)) {
            $html = (string) $template->template_html;
        }

        if ($html === '') {
            return ['success' => false, 'message' => 'Unable to build preview content. Provide DOCX or HTML content.'];
        }

        // === PDF GENERATION ===
        $pdfName = 'agreement_template_preview_' . $template->id . '_' . time() . '.pdf';
        $pdfPath = $renderDir . '/' . $pdfName;

        // Try LibreOffice native conversion first (higher fidelity)
        if ($renderedDocxPath) {
            $nativePdf = $this->convertDocxToPdfNative(
                Storage::disk('local')->path($renderedDocxPath),
                $renderDir,
                $pdfName
            );
            if ($nativePdf) {
                $pdfPath = $nativePdf;
            }
        }

        // Fallback to dompdf HTML→PDF
        if (! Storage::disk('local')->exists($pdfPath)) {
            try {
                $pdf = \PDF::loadHTML($html)->setPaper('a4');
                $pdf->save(Storage::disk('local')->path($pdfPath));
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Failed to generate PDF preview: ' . $e->getMessage()];
            }
        }

        // === SAVE RENDER RECORD ===
        AgreementTemplateRender::create([
            'agreement_template_id' => $template->id,
            'merge_input_json' => $mergedValues,
            'rendered_docx_path' => $renderedDocxPath,
            'rendered_pdf_path' => $pdfPath,
            'render_status' => 'preview',
            'generated_by' => auth()->id(),
        ]);

        return [
            'success' => true,
            'pdf_path' => $pdfPath,
            'pdf_name' => $pdfName,
        ];
    }

    private function convertDocxToPdfNative(string $docxPath, string $renderDir, string $pdfName): ?string
    {
        $soffice = $this->findSofficeBinary();
        if (! $soffice || ! file_exists($docxPath)) return null;

        $outputDir = Storage::disk('local')->path($renderDir);
        if (! is_dir($outputDir)) @mkdir($outputDir, 0777, true);

        $command = '"' . $soffice . '" --headless --convert-to pdf --outdir '
            . escapeshellarg($outputDir) . ' ' . escapeshellarg($docxPath);
        @shell_exec($command);

        $expected = $outputDir . DIRECTORY_SEPARATOR . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
        if (! file_exists($expected)) return null;

        $finalPath = $outputDir . DIRECTORY_SEPARATOR . $pdfName;
        if ($expected !== $finalPath) @rename($expected, $finalPath);

        return $renderDir . '/' . $pdfName;
    }

    private function findSofficeBinary(): ?string
    {
        $candidates = [
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            '/usr/bin/soffice',
            '/usr/local/bin/soffice',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }
        return null;
    }

    private function extractHtmlFromDocxPath(string $localPath): string
    {
        if (! file_exists($localPath)) return '';

        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($localPath);
            $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');

            ob_start();
            $htmlWriter->save('php://output');
            $html = ob_get_clean();

            return is_string($html) ? $html : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
