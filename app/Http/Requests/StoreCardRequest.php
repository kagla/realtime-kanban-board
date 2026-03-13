<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '카드 제목을 입력해주세요.',
            'title.max' => '카드 제목은 255자 이하로 입력해주세요.',
            'description.max' => '설명은 2000자 이하로 입력해주세요.',
            'priority.in' => '올바른 우선순위를 선택해주세요.',
            'due_date.date' => '올바른 날짜 형식을 입력해주세요.',
        ];
    }
}
