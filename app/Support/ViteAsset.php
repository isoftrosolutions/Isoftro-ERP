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

    private static function productionTags(array $entries): string
    {
        $manifestPath = __DIR__ . '/../../build/manifest.json';
        if (!file_exists($manifestPath)) {
            return '';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $tags = '';
        
        // Determine the base path for assets
        // If APP_URL is defined, use it, otherwise use a relative path
        $baseUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
        // If APP_URL contains 'frontend' but assets are in 'public/build', we might need to adjust
        // For now, let's assume assets are accessible via /build/ relative to the server root
        // or relative to the APP_URL if we fix it.
        $assetPath = '/erp/build/';

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


