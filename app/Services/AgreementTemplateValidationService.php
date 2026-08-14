<?php

namespace App\Services;

use App\Models\AgreementTemplate;

class AgreementTemplateValidationService
{
    /**
     * Check if a published template already exists for the same scope.
     */
    public function findPublishedConflict(array $data, $ignoreId = null): ?AgreementTemplate
    {
        $query = AgreementTemplate::where('status', 'published');

        $contractType = $data['contract_type'] ?? null;
        $paymentType = $data['payment_type'] ?? null;
        $entityTypeId = $data['entity_type_id'] ?? null;

        if ($contractType === null || $contractType === '') {
            $query->whereNull('contract_type');
        } else {
            $query->where('contract_type', $contractType);
        }

        if ($paymentType === null || $paymentType === '') {
            $query->whereNull('payment_type');
        } else {
            $query->where('payment_type', $paymentType);
        }

        if ($entityTypeId === null || $entityTypeId === '') {
            $query->whereNull('entity_type_id');
        } else {
            $query->where('entity_type_id', $entityTypeId);
        }

        if (! empty($ignoreId)) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first();
    }

    public function buildPublishedScopeKey(array $data): string
    {
        $contractType = $data['contract_type'] ?? 'null';
        $paymentType = $data['payment_type'] ?? 'null';
        $entityTypeId = $data['entity_type_id'] ?? 'null';
        return md5($contractType . '|' . $paymentType . '|' . $entityTypeId);
    }

    public function canPublish(AgreementTemplate $template): array
    {
        $errors = [];

        if (empty($template->source_docx_path) && empty($template->template_html)) {
            $errors[] = 'Template must have either a DOCX file or HTML content before publishing.';
        }

        $variableService = app(AgreementTemplateVariableService::class);
        $unresolved = $variableService->getUnresolvedTokens($template);
        if (! empty($unresolved)) {
            $errors[] = 'Unresolved required variables: ' . implode(', ', $unresolved);
        }

        $conflict = $this->findPublishedConflict([
            'contract_type' => $template->contract_type,
            'payment_type' => $template->payment_type,
            'entity_type_id' => $template->entity_type_id,
        ], $template->id);

        if ($conflict) {
            $errors[] = 'Another published template (ID: ' . $conflict->id . ') already exists for this scope.';
        }

        return $errors;
    }
}
