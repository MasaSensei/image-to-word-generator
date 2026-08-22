<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class WordGeneratorService
{
    public function generate(array $uploadedFiles): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultParagraphStyle(['align' => 'center']);

        $section = $phpWord->addSection([
            'marginTop' => 600,
            'marginBottom' => 600,
            'marginLeft' => 600,
            'marginRight' => 600,
        ]);

        $imagesPerPage = config('image_to_word.word.images_per_page');

        // Memecah array gambar menjadi kelompok (2 gambar per halaman)
        $imageChunks = array_chunk($uploadedFiles, $imagesPerPage);

        foreach ($imageChunks as $index => $chunk) {
            foreach ($chunk as $file) {
                // Simpan file ke storage publik sementara dengan nama unik
                $tempName = 'temp_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('temp_images', $tempName);
                $absolutePath = Storage::path($path);

                // Hitung dimensi dengan mempertahankan Aspect Ratio
                $dimensions = $this->calculateDimensions($absolutePath);

                // Tambahkan gambar ke dokumen Word
                $section->addImage($absolutePath, [
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                ]);

                // Beri spasi pemisah antar gambar di halaman yang sama
                $section->addTextBreak(1);
            }

            // Tambahkan Explicit Page Break jika BUKAN chunk terakhir
            if ($index < count($imageChunks) - 1) {
                $section->addPageBreak();
            }
        }

        // Pastikan direktori generated ada
        Storage::makeDirectory('generated');
        $fileName = 'generated/' . Str::uuid() . '.docx';
        $fullSavePath = Storage::path($fileName);

        // Simpan menggunakan IOFactory Writer Word2007
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fullSavePath);

        // Bersihkan folder file temporary secara aman
        Storage::deleteDirectory('temp_images');

        return $fileName;
    }

    private function calculateDimensions(string $imagePath): array
    {
        $maxWidth = config('image_to_word.word.image_max_width');
        $maxHeight = config('image_to_word.word.image_max_height');

        list($originalWidth, $originalHeight) = @getimagesize($imagePath);

        // Fallback aman jika ukuran gagal dibaca
        if (!$originalWidth || !$originalHeight) {
            return ['width' => 200, 'height' => 150];
        }

        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);

        // Jika ukuran asli lebih kecil dari bounding box, gunakan ukuran asli
        if ($ratio > 1) {
            $ratio = 1;
        }

        return [
            'width' => (int) round($originalWidth * $ratio),
            'height' => (int) round($originalHeight * $ratio),
        ];
    }
}
