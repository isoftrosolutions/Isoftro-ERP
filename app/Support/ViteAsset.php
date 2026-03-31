<?php

namespace App\Support;

class ViteAsset
{
    /**
     * Get the HTML tags for the requested assets.
     */
    public static function tags(array $entries): string
    {
        $hotFile = __DIR__ . '/../../hot';
        if (file_exists($hotFile)) {
            $url = trim(file_get_contents($hotFile));
            $tags = "<script type=\"module\" src=\"{$url}/@vite/client\"></script>";
            foreach ($entries as $entry) {
                if (str_ends_with($entry, '.scss') || str_ends_with($entry, '.css')) {
                    $tags .= "<link rel=\"stylesheet\" href=\"{$url}/{$entry}\">";
                } else {
                    $tags .= "<script type=\"module\" src=\"{$url}/{$entry}\"></script>";
                }
            }
            return $tags;
        }

        return self::productionTags($entries);
    }

    private static function buildPath(): string
    {
        $appUrl = rtrim(env('APP_URL', ''), '/');
        return $appUrl . '/build';
    }

    private static function productionTags(array $entries): string
    {
        $manifestPath = __DIR__ . '/../../public/build/manifest.json';
        if (!file_exists($manifestPath)) {
            return '';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $tags = '';
        
        $assetPath = self::buildPath() . '/';

        foreach ($entries as $entry) {
            if (isset($manifest[$entry])) {
                $file = $manifest[$entry]['file'];
                if (str_ends_with($file, '.css') || (isset($manifest[$entry]['isEntry']) && str_ends_with($entry, '.scss'))) {
                    $tags .= "<link rel=\"stylesheet\" href=\"{$assetPath}{$file}\">";
                } else {
                    $tags .= "<script type=\"module\" src=\"{$assetPath}{$file}\"></script>";
                }

                if (isset($manifest[$entry]['css'])) {
                    foreach ($manifest[$entry]['css'] as $cssFile) {
                        $tags .= "<link rel=\"stylesheet\" href=\"{$assetPath}{$cssFile}\">";
                    }
                }
            }
        }

        return $tags;
    }
}


