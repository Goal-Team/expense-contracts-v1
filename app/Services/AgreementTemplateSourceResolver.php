<?php

namespace App\Services;

use App\Models\AgreementTemplate;
use Illuminate\Support\Facades\Storage;

class AgreementTemplateSourceResolver
{
    /**
     * Check if Word template source mode is enabled.
     */
    public function isWordSourceEnabled(): bool
    {
        return strtolower((string) admin_setting('agreement_template_source', 'word')) === 'word';
    }

    /**
     * Resolve the best-matching published template for a given scope.
     * Tries most-specific first, falls back to less-specific.
     */
    public function resolvePublishedTemplate($contractType, $paymentType = null, $entityTypeId = null): ?AgreementTemplate
    {
        $baseQ = AgreementTemplate::where('status', 'published')
            ->where('contract_type', $contractType);

        $template = null;

        // Most specific: all 3 fields
        if ($paymentType && $entityTypeId) {
            $template = (clone $baseQ)->where('payment_type', $paymentType)
                ->where('entity_type_id', $entityTypeId)->first();
        }
        // Contract + payment only
        if (! $template && $paymentType) {
            $template = (clone $baseQ)->where('payment_type', $paymentType)
                ->whereNull('entity_type_id')->first();
        }
        // Contract + entity only
        if (! $template && $entityTypeId) {
            $template = (clone $baseQ)->whereNull('payment_type')
                ->where('entity_type_id', $entityTypeId)->first();
        }
        // Contract only (least specific)
        if (! $template) {
            $template = (clone $baseQ)->whereNull('payment_type')
                ->whereNull('entity_type_id')->first();
        }

        return $template;
    }

    /**
     * Resolve template content (HTML string) for rendering.
     */
    public function resolveTemplateContent($contractType, $paymentType = null, $entityTypeId = null): string
    {
        if (! $this->isWordSourceEnabled()) return '';

        $template = $this->resolvePublishedTemplate($contractType, $paymentType, $entityTypeId);
        if (! $template) return '';

        if (! empty($template->template_html)) {
            return (string) $template->template_html;
        }

        if (! empty($template->source_docx_path)) {
            return $this->extractHtmlFromDocx($template->source_docx_path);
        }

        return '';
    }

    private function extractHtmlFromDocx(string $docxPath): string
    {
        if (trim($docxPath) === '') return '';

        try {
            if (! Storage::disk('local')->exists($docxPath)) return '';

            $localPath = Storage::disk('local')->path($docxPath);
            if (! file_exists($localPath)) return '';

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
