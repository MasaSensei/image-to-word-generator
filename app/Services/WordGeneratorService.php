<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

class WordGeneratorService
{
    private const TEMP_DIR = 'temp_images';

    public function generateFromPaths(array $imagePaths, array $descriptions = []): string
    {
        Storage::makeDirectory(self::TEMP_DIR);

        $phpWord = new PhpWord();
        $phpWord->setDefaultParagraphStyle(['align' => 'center']);
        $section = $phpWord->addSection([
            'marginTop' => 700, 'marginBottom' => 700, 'marginLeft' => 800, 'marginRight' => 800,
        ]);

        $chunks = array_chunk($imagePaths, 2);
        $tempCroppedFiles = [];

        try {
            foreach ($chunks as $chunkIndex => $chunk) {
                foreach ($chunk as $fileIndex => $storedRelativePath) {
                    $globalIndex = ($chunkIndex * 2) + $fileIndex;
                    $number = $globalIndex + 1;
                    $sourcePath = Storage::path($storedRelativePath);
                    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg');

                    if (!is_file($sourcePath)) {
                        throw new \RuntimeException("File gambar ke-{$number} tidak ditemukan: {$storedRelativePath}");
                    }

                    $imagePath = $this->cropTo4x3($sourcePath, $ext);
                    if ($imagePath !== $sourcePath) {
                        $tempCroppedFiles[] = $imagePath;
                    }
                    if (!is_file($imagePath) || filesize($imagePath) === 0) {
                        Log::warning("Crop gagal untuk gambar ke-{$number}, fallback ke file asli.");
                        $imagePath = $sourcePath;
                    }

                    $descText = trim($descriptions[$globalIndex] ?? '');
                    $section->addText(
                        $number . '. ' . $descText,
                        ['size' => 11, 'color' => '333333', 'bold' => true],
                        ['align' => 'left', 'spaceBefore' => ($fileIndex > 0 ? 500 : 0), 'spaceAfter' => 100]
                    );

                    $section->addImage($imagePath, ['width' => 450, 'height' => 337, 'alignment' => Jc::CENTER]);
                }

                if ($chunkIndex < count($chunks) - 1) {
                    $section->addPageBreak();
                }
            }

            Storage::makeDirectory('generated');
            $fileName = 'generated/' . Str::uuid() . '.docx';
            $savePath = Storage::path($fileName);

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($savePath);

            if (!is_file($savePath) || filesize($savePath) === 0) {
                throw new \RuntimeException('Gagal menyimpan dokumen Word (file kosong/tidak tersimpan).');
            }

            $zip = new \ZipArchive();
            $isValidZip = $zip->open($savePath, \ZipArchive::CHECKCONS) === true;
            if ($isValidZip) {
                $zip->close();
            } else {
                @unlink($savePath);
                throw new \RuntimeException('Dokumen hasil generate gagal validasi integritas ZIP.');
            }

            return $fileName;
        } finally {
            foreach ($tempCroppedFiles as $tmp) {
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
        }
    }

    /**
     * Crop gambar ke rasio 4:3, ditulis ke temp lokal.
     * Return path asli jika gagal (fallback aman, tidak mengubah aspect ratio original).
     */
    private function cropTo4x3(string $sourcePath, string $extension): string
    {
        $info = @getimagesize($sourcePath);
        if (!$info) {
            return $sourcePath;
        }

        [$width, $height] = $info;
        $mime = $info['mime'];

        $targetRatio = 4 / 3;
        $sourceRatio = $width / $height;

        if ($sourceRatio > $targetRatio) {
            $cropWidth  = (int) ($height * $targetRatio);
            $cropHeight = $height;
            $x = (int) (($width - $cropWidth) / 2);
            $y = 0;
        } else {
            $cropWidth  = $width;
            $cropHeight = (int) ($width / $targetRatio);
            $x = 0;
            $y = (int) (($height - $cropHeight) / 2);
        }

        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            default      => null,
        };

        if (!$img) {
            return $sourcePath;
        }

        $cropped = imagecrop($img, [
            'x'      => $x,
            'y'      => $y,
            'width'  => $cropWidth,
            'height' => $cropHeight,
        ]);

        if ($cropped === false) {
            imagedestroy($img);
            return $sourcePath;
        }

        $tempCroppedPath = sys_get_temp_dir() . '/crop_' . Str::uuid() . '.' . $extension;

        if ($mime === 'image/png') {
            imagepng($cropped, $tempCroppedPath);
        } else {
            imagejpeg($cropped, $tempCroppedPath, 85);
        }

        imagedestroy($cropped);
        imagedestroy($img);

        return $tempCroppedPath;
    }
}