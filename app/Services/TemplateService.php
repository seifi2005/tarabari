<?php

namespace App\Services;

class TemplateService
{
    /**
     * جایگزینی متغیرها در قالب
     * 
     * @param string $template قالب با متغیرها (مثلاً: {customer_name})
     * @param array $variables آرایه متغیرها (مثلاً: ['customer_name' => 'علی'])
     * @return string قالب با متغیرهای جایگزین شده
     */
    public function replaceVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value ?? '', $template);
        }
        return $template;
    }
}

