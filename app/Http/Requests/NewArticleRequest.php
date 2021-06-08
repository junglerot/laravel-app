<?php

namespace App\Http\Requests;

class NewArticleRequest extends UpdateArticleRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return parent::rules() + [
            'article.tagList' => 'required|array',
            'article.tagList.*' => 'required|string|distinct:strict',
        ];
    }
}
