<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageComparisonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:10240', // Max 10MB
            ],
        ];
    }

    /**
     * Custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'يرجى اختيار صورة أولاً.',
            'image.file' => 'الملف المرفوع يجب أن يكون ملفاً صالحاً.',
            'image.image' => 'يجب أن يكون الملف المرفوع صورة صالحة.',
            'image.mimes' => 'الصيغ المسموح بها هي: JPEG, JPG, PNG, WEBP.',
            'image.max' => 'أقصى حجم مسموح به للصورة هو 10 ميجابايت.',
        ];
    }
}
