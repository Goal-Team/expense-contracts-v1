<?php

namespace App\Services;

use App\Models\AgreementTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AgreementTemplateStorageService
{
    protected string $disk = 'local';
    protected string $basePath = 'agreement-templates';

    public function uploadDocx(UploadedFile $file): array
    {
        $docName = file_name($file);
        $docPath = Storage::disk($this->disk)->putFileAs($this->basePath, $file, $docName);

        return [
            'path' => $docPath,
            'filename' => $docName,
        ];
    }

    public function downloadDocx(AgreementTemplate $template)
    {
        if (empty($template->source_docx_path)) {
            return null;
        }
        if (! Storage::disk($this->disk)->exists($template->source_docx_path)) {
            return null;
        }

        $downloadName = $template->source_docx_filename ?: basename($template->source_docx_path);
        return Storage::disk($this->disk)->download($template->source_docx_path, $downloadName);
    }

    public function docxExists(AgreementTemplate $template): bool
    {
        return ! empty($template->source_docx_path)
            && Storage::disk($this->disk)->exists($template->source_docx_path);
    }

    public function getLocalPath(string $storagePath): ?string
    {
        if (Storage::disk($this->disk)->exists($storagePath)) {
            return Storage::disk($this->disk)->path($storagePath);
        }
        return null;
    }

    public function ensureRenderDirectory(int $templateId): string
    {
        $renderDir = $this->basePath . '/renders/' . $templateId;
        if (! Storage::disk($this->disk)->exists($renderDir)) {
            Storage::disk($this->disk)->makeDirectory($renderDir);
        }
        return $renderDir;
    }
}
