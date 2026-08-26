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
            'images.required' => 'Please select at least one image.',
            'images.max' => 'You can upload a maximum of ' . config('image_to_word.max_images') . ' images.',
            'images.*.image' => 'Each file must be a valid image.',
            'images.*.mimes' => 'Unsupported format. Please use JPG, JPEG, or PNG.',
            'images.*.max' => 'The file is too large. The maximum allowed size is ' . (config('image_to_word.max_file_size') / 1024) . ' MB.',
        ];
    }
}
