<?php

namespace App\Services;

class PromptLibrary
{
    /** @var array<string, ?string> */
    protected array $cache = [];

    public function get(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $fullPath = resource_path('prompts/'.$relativePath);

        if (! is_file($fullPath)) {
            return null;
        }

        $mtime = (string) filemtime($fullPath);
        $cacheKey = $relativePath.'|'.$mtime;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $contents = file_get_contents($fullPath);

        if ($contents === false) {
            return $this->cache[$cacheKey] = null;
        }

        $trimmed = trim($contents);

        return $this->cache[$cacheKey] = ($trimmed === '' ? null : $trimmed);
    }

    public function forgetCache(): void
    {
        $this->cache = [];
    }
}
