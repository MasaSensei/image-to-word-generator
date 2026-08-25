<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class WordGeneratorService
{
    public function generate(array $uploadedFiles, array $descriptions = []): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultParagraphStyle(['align' => 'center']);

        // Margin sedikit diperkecil agar ruang lebih lega untuk 2 gambar
        $section = $phpWord->addSection([
            'marginTop' => 700,
            'marginBottom' => 700,
            'marginLeft' => 800,
            'marginRight' => 800,
        ]);

        $imageChunks = array_chunk($uploadedFiles, 2);

        foreach ($imageChunks as $chunkIndex => $chunk) {
            foreach ($chunk as $fileIndex => $file) {
                $globalIndex = ($chunkIndex * 2) + $fileIndex;
                $ext = strtolower($file->getClientOriginalExtension());

                $tempName = 'temp_orig_' . Str::uuid() . '.' . $ext;
                $originalPath = Storage::path($file->storeAs('temp_images', $tempName));

                // CROP gambar ke rasio 4:3
                $croppedPath = $this->cropTo4x3($originalPath, $ext);

                // 1. TULIS DESKRIPSI (DI ATAS GAMBAR)
                $descText = $descriptions[$globalIndex] ?? '';
                if (!empty(trim($descText))) {
                    $section->addText(
                        trim($descText),
                        ['size' => 11, 'color' => '333333', 'bold' => true],
                        [
                            'spaceBefore' => ($fileIndex > 0 ? 500 : 0), // Beri jarak atas jika ini gambar ke-2
                            'spaceAfter' => 100
                        ]
                    );
                } else {
                    // Jika tidak ada deskripsi tapi ini gambar ke-2, beri jarak kosong agar tidak menempel
                    if ($fileIndex > 0) {
                        $section->addTextBreak(1, ['size' => 12]);
                    }
                }

                // 2. MASUKKAN GAMBAR (DI BAWAH DESKRIPSI)
                $section->addImage($croppedPath, [
                    'width' => 450, // Sedikit dikurangi (tetap rasio 4:3) agar tidak memicu auto-pagebreak
                    'height' => 337,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                ]);
            }

            // Tambahkan Page Break HANYA jika ini bukan halaman (chunk) terakhir
            if ($chunkIndex < count($imageChunks) - 1) {
                $section->addPageBreak();
            }
        }

        Storage::makeDirectory('generated');
        $fileName = 'generated/' . Str::uuid() . '.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save(Storage::path($fileName));

        Storage::deleteDirectory('temp_images');

        return $fileName;
    }

    private function cropTo4x3(string $sourcePath, string $extension): string
    {
        $info = @getimagesize($sourcePath);
        if (!$info) return $sourcePath;

        list($width, $height) = $info;
        $mime = $info['mime'];

        $targetRatio = 4 / 3;
        $sourceRatio = $width / $height;

        if ($sourceRatio > $targetRatio) {
            $cropWidth = (int) ($height * $targetRatio);
            $cropHeight = $height;
            $x = ($width - $cropWidth) / 2;
            $y = 0;
        } else {
            $cropWidth = $width;
            $cropHeight = (int) ($width / $targetRatio);
            $x = 0;
            $y = ($height - $cropHeight) / 2;
        }

        switch ($mime) {
            case 'image/jpeg':
                $img = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $img = imagecreatefrompng($sourcePath);
                break;
            default:
                return $sourcePath;
        }

        $cropped = imagecrop($img, ['x' => $x, 'y' => $y, 'width' => $cropWidth, 'height' => $cropHeight]);

        $tempCroppedPath = Storage::path('temp_images/crop_' . Str::uuid() . '.' . $extension);

        if ($cropped !== false) {
            if ($mime == 'image/png') {
                imagepng($cropped, $tempCroppedPath);
            } else {
                imagejpeg($cropped, $tempCroppedPath, 90);
            }
            imagedestroy($cropped);
            imagedestroy($img);
            return $tempCroppedPath;
        }

        imagedestroy($img);
        return $sourcePath;
    }
}
