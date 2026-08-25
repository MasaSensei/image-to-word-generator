<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageToWordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Tidak ada autentikasi
    }

    public function rules(): array
    {
        $maxImages = config('image_to_word.max_images');
        $maxSize = config('image_to_word.max_file_size');
        $mimes = config('image_to_word.allowed_mimes');

        return [
            'images' => ['required', 'array', 'min:1', 'max:' . $maxImages],
            'images.*' => ['required', 'image', 'mimes:' . $mimes, 'max:' . $maxSize],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Minimal 1 gambar harus dipilih.',
            'images.max' => 'Maksimal ' . config('image_to_word.max_images') . ' gambar yang diizinkan.',
            'images.*.image' => 'File harus berupa dokumen gambar yang sah.',
            'images.*.mimes' => 'Format tidak didukung. Gunakan ekstensi JPG, JPEG, atau PNG.',
            'images.*.max' => 'Ukuran file terlalu besar. Batas maksimum adalah ' . (config('image_to_word.max_file_size') / 1024) . ' MB.',
        ];
    }
}
