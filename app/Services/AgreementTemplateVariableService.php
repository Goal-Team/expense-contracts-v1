<?php

namespace App\Services;

use App\Models\AgreementTemplate;
use App\Models\AgreementTemplateVariable;
use App\Models\CustomVarDocs;

class AgreementTemplateVariableService
{
    /**
     * Sync placeholders from template content into the variables table.
     */
    public function syncPlaceholders(AgreementTemplate $template): array
    {
        $templateText = $this->resolveTemplateText($template);

        if ($templateText === '') {
            return [
                'success' => false,
                'message' => 'Template content is empty. Upload a DOCX or add template HTML.',
            ];
        }

        $tokenService = new TemplateTokenService();
        $tokens = $tokenService->extractTokens($templateText);

        $customVarMap = $this->buildCustomVarMapFromDb();

        // Remove old variables not in current template
        AgreementTemplateVariable::where('agreement_template_id', $template->id)
            ->whereNotIn('variable_key', $tokens)
            ->delete();

        foreach ($tokens as $token) {
            $meta = $customVarMap[$token] ?? null;
            AgreementTemplateVariable::updateOrCreate(
                [
                    'agreement_template_id' => $template->id,
                    'variable_key' => $token,
                ],
                [
                    'source' => $meta ? 'custom_var_docs' : 'manual',
                ]
            );
        }

        return ['success' => true, 'token_count' => count($tokens)];
    }

    /**
     * Get required variables that have no default value set.
     */
    public function getUnresolvedTokens(AgreementTemplate $template): array
    {
        $variables = AgreementTemplateVariable::where('agreement_template_id', $template->id)->get();
        $unresolved = [];

        foreach ($variables as $variable) {
            if ($variable->required && empty($variable->default_value)) {
                $unresolved[] = $variable->variable_key;
            }
        }

        return $unresolved;
    }

    /**
     * Returns available variable definitions from custom_var_docs for the UI.
     */
    public function getCustomVarList()
    {
        return CustomVarDocs::where('status', 1)->get();
    }

    /**
     * Bulk update required flags and default values for template variables.
     */
    public function updateVariables(AgreementTemplate $template, array $required, array $defaults): void
    {
        $variables = AgreementTemplateVariable::where('agreement_template_id', $template->id)->get();

        foreach ($variables as $variable) {
            $variable->required = isset($required[$variable->id]);
            if (array_key_exists($variable->id, $defaults)) {
                $variable->default_value = $defaults[$variable->id];
            }
            $variable->save();
        }
    }

    /**
     * Build token→meta map from custom_var_docs table.
     */
    public function buildCustomVarMapFromDb(): array
    {
        $allVars = CustomVarDocs::where('status', 1)->get();
        return $this->buildCustomVarMap($allVars);
    }

    /**
     * Build token→meta map from variable definitions.
     */
    public function buildCustomVarMap($varDefinitions): array
    {
        $map = [];
        foreach ($varDefinitions as $row) {
            $varVar = is_array($row) ? ($row['var_var'] ?? null) : ($row->var_var ?? null);
            $varField = is_array($row) ? ($row['var_field'] ?? null) : ($row->var_field ?? null);
            $varTable = is_array($row) ? ($row['var_table'] ?? null) : ($row->var_table ?? null);
            $varDisp = is_array($row) ? ($row['var_disp_var'] ?? null) : ($row->var_disp_var ?? null);

            $token = $this->normalizeCustomVarToken($varVar);
            if (! $token) continue;

            $map[$token] = [
                'label' => $varDisp ?: ($varField ?: $token),
                'source' => ($varTable && $varField) ? ($varTable . '.' . $varField) : null,
            ];
        }
        return $map;
    }

    /**
     * Build default values for tokens using custom_var_docs definitions.
     */
    public function buildDefaultValues(array $tokens): array
    {
        $map = $this->buildCustomVarMapFromDb();
        $defaults = [];

        foreach ($tokens as $token) {
            if (isset($map[$token])) {
                $defaults[$token] = $map[$token]['label'] ?? $token;
            }
        }

        return $defaults;
    }

    /**
     * Extract plain text from template (HTML or DOCX).
     */
    public function resolveTemplateText(AgreementTemplate $template): string
    {
        if (! empty($template->template_html)) {
            $raw = html_entity_decode((string) $template->template_html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return trim(strip_tags($raw));
        }

        if (! empty($template->source_docx_path)) {
            return $this->extractTextFromDocxPath($template->source_docx_path);
        }

        return '';
    }

    private function extractTextFromDocxPath(string $docxPath): string
    {
        if (trim($docxPath) === '') return '';

        try {
            if (! \Illuminate\Support\Facades\Storage::disk('local')->exists($docxPath)) {
                return '';
            }

            $localPath = \Illuminate\Support\Facades\Storage::disk('local')->path($docxPath);
            if (! file_exists($localPath)) return '';

            $phpWord = \PhpOffice\PhpWord\IOFactory::load($localPath);
            $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');

            ob_start();
            $htmlWriter->save('php://output');
            $html = ob_get_clean();

            $raw = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return trim(strip_tags($raw));
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function normalizeCustomVarToken($varVar): ?string
    {
        if (! is_string($varVar)) return null;

        $token = trim($varVar);
        $token = preg_replace('/^\$\{(.+)\}$/', '$1', $token);
        $token = preg_replace('/^\{\{(.+)\}\}$/', '$1', $token);
        return trim($token);
    }
}
