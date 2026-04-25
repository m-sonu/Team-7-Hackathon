<?php

namespace App\Services\Extractor;

use Exception;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class ResumeExtractorService
{
    public function extractText(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'pdf' => $this->extractFromPdf($file->path()),
            'docx', 'doc' => $this->extractFromWord($file->path()),
            default => throw new Exception("Unsupported file format: {$extension}"),
        };
    }

    protected function extractFromPdf(string $path): string
    {
        $parser = new PdfParser;
        $pdf = $parser->parseFile($path);

        return $pdf->getText();
    }

    protected function extractFromWord(string $path): string
    {
        // Load the document using PhpWord
        $phpWord = WordIOFactory::load($path);
        $text = '';
        // Iterate through all sections and elements to grab the text
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText()."\n";
                }
            }
        }

        return $text;
    }
}
