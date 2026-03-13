<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBoardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('board')->user_id;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '보드 제목을 입력해주세요.',
            'title.max' => '보드 제목은 255자 이하로 입력해주세요.',
            'description.max' => '설명은 1000자 이하로 입력해주세요.',
        ];
    }
}
