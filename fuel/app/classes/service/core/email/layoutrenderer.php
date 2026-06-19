<?php

class Service_Core_Email_LayoutRenderer
{
    public function render_html($layout_code, $body, array $variables = [], array &$warnings = [])
    {
        $layout = $this->find_layout($layout_code);

        if (!$layout || trim((string) $layout->html_layout) === '') {
            return (string) $body;
        }

        $html = str_replace('{{body}}', (string) $body, (string) $layout->html_layout);

        return $this->replace_variables($html, $variables, $warnings);
    }

    public function render_text($layout_code, $body, array $variables = [], array &$warnings = [])
    {
        $layout = $this->find_layout($layout_code);

        if (!$layout || trim((string) $layout->text_layout) === '') {
            return (string) $body;
        }

        $text = str_replace('{{body}}', (string) $body, (string) $layout->text_layout);

        return $this->replace_variables($text, $variables, $warnings);
    }

    protected function find_layout($layout_code)
    {
        return Model_Core_Email_Layout::query()
            ->where('code', trim((string) $layout_code))
            ->where('active', 1)
            ->get_one();
    }

    protected function replace_variables($content, array $variables, array &$warnings = [])
    {
        $renderer = new Service_Core_Email_TemplateRenderer();
        $warnings = is_array($warnings) ? $warnings : [];

        return $renderer->render((string) $content, $variables, $warnings);
    }
}
