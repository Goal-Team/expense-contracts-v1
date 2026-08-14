<?php

namespace App\Services;

class TemplateTokenService
{
    /**
     * Extract unique tokens from template text.
     * Supports {{token}} and ${token} syntax.
     */
    public function extractTokens($templateText)
    {
        if (! is_string($templateText) || $templateText === '') {
            return [];
        }

        preg_match_all(
            '/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}|\$\{\s*([A-Za-z0-9_.-]+)\s*\}/',
            $templateText,
            $matches
        );

        $tokens = array_filter(array_merge($matches[1] ?? [], $matches[2] ?? []));
        $seen = [];
        $unique = [];

        foreach ($tokens as $token) {
            if (isset($seen[$token])) continue;
            $seen[$token] = true;
            $unique[] = $token;
        }

        return $unique;
    }

    /**
     * Parse template and validate token coverage against provided values.
     */
    public function parseAndValidate($templateText, $values = [])
    {
        $tokens = $this->extractTokens($templateText);
        $flatValues = $this->normalizeValues($values);

        $items = [];
        $missingCount = 0;
        $emptyCount = 0;

        foreach ($tokens as $token) {
            $hasValue = array_key_exists($token, $flatValues);
            $value = $hasValue ? $flatValues[$token] : null;

            if (! $hasValue) {
                $status = 'missing';
                $missingCount++;
            } elseif ($value === null || (is_string($value) && trim($value) === '')) {
                $status = 'empty';
                $emptyCount++;
            } else {
                $status = 'mapped';
            }

            $items[] = [
                'token' => $token,
                'status' => $status,
                'value' => $value,
            ];
        }

        return [
            'tokens' => $items,
            'summary' => [
                'total' => count($tokens),
                'missing' => $missingCount,
                'empty' => $emptyCount,
            ],
            'rawTokens' => $tokens,
        ];
    }

    /**
     * Replace tokens in template text with provided values.
     */
    public function replaceTokens($templateText, $values = [], $options = [])
    {
        $report = $this->parseAndValidate($templateText, $values);
        $replaceEmpty = isset($options['replace_empty']) ? (bool) $options['replace_empty'] : false;
        $strict = isset($options['strict']) ? (bool) $options['strict'] : false;

        $mergedText = (string) $templateText;
        $unresolved = [];

        foreach ($report['tokens'] as $tokenRow) {
            $token = $tokenRow['token'];
            $status = $tokenRow['status'];
            $value = $tokenRow['value'];
            $canReplace = $status === 'mapped' || ($status === 'empty' && $replaceEmpty);

            if (! $canReplace) {
                $unresolved[] = $token;
                continue;
            }

            $replacement = $value === null ? '' : (string) $value;
            $mergedText = preg_replace('/\{\{\s*' . preg_quote($token, '/') . '\s*\}\}/', $replacement, $mergedText);
            $mergedText = preg_replace('/\$\{\s*' . preg_quote($token, '/') . '\s*\}/', $replacement, $mergedText);
        }

        if ($strict && count($unresolved) > 0) {
            return [
                'success' => false,
                'data' => [
                    'unresolved' => $unresolved,
                    'report' => $report,
                ],
            ];
        }

        return [
            'success' => true,
            'data' => [
                'merged_text' => $mergedText,
                'unresolved' => $unresolved,
                'report' => $report,
            ],
        ];
    }

    public function humanizeToken($token)
    {
        $text = str_replace(['.', '_'], ' ', $token);
        $text = preg_replace('/(?<!^)[A-Z]/', ' $0', $text);
        $text = preg_replace('/\s+/', ' ', trim($text));
        return ucwords(strtolower($text));
    }

    private function normalizeValues($values)
    {
        if (! is_array($values)) return [];

        $flat = [];

        foreach ($values as $k => $v) {
            if (is_array($v) && isset($v['token']) && array_key_exists('value', $v)) {
                $flat[(string) $v['token']] = $v['value'];
            } elseif (is_array($v) && isset($v['key']) && array_key_exists('value', $v)) {
                $flat[(string) $v['key']] = $v['value'];
            }
        }

        $nested = $this->flattenArray($values);
        foreach ($nested as $k => $v) {
            $flat[$k] = $v;
        }

        return $flat;
    }

    private function flattenArray(array $values, $prefix = '')
    {
        $flat = [];
        foreach ($values as $key => $value) {
            if (is_int($key)) continue;
            $newKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $flat = array_merge($flat, $this->flattenArray($value, $newKey));
            } else {
                $flat[$newKey] = $value;
            }
        }
        return $flat;
    }
}
