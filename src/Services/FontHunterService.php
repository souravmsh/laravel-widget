<?php

namespace Souravmsh\LaravelWidget\Services;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use InvalidArgumentException;

class FontHunterService
{
    private string $url;
    private string $baseDir;
    private string $fontsDir;
    private string $cssDir;
    private string $fileName;
    private array $errors = [];
    private array $success = [];
    private string $downloadUrl = '';
    private bool $isDownloadable = false;
    private array $fontFamilies = [];
    private array $extractedFontFamilies = [];


    private const FONT_FORMATS = [
        'ttf' => 'truetype',
        'otf' => 'opentype',
        'woff' => 'woff',
        'woff2' => 'woff2',
        'eot' => 'embedded-opentype',
        'svg' => 'svg',
        'pfb' => 'postscript',
        'pfm' => 'postscript',
    ];

    public function __construct()
    {
        $this->initializeConfig();
    }

    private function initializeConfig(): void
    {
        $sessionId = session()->getId();
        $this->baseDir = rtrim(config('laravel_widget.font_hunter.dir', 'laravel_widget/font_hunter'), '/') . '/' . $sessionId;
        $this->fontsDir = rtrim(config('laravel_widget.font_hunter.fonts_dir', 'fonts'), '/');
        $this->cssDir = rtrim(config('laravel_widget.font_hunter.css_dir', 'css'), '/');
        $this->fileName = config('laravel_widget.font_hunter.file_name', 'fonts.css');

        $this->validateConfig();
    }

    private function validateConfig(): void
    {
        if (empty($this->baseDir) || empty($this->fontsDir) || empty($this->cssDir) || empty($this->fileName)) {
            throw new InvalidArgumentException('Invalid configuration: Directory or file name settings are missing.');
        }
    }

    public function generate(string $url): array
    {
        try {
            $this->url = $url;
            $this->parseFontFamilies();
            $success = $this->processContent();

            if ($success) {
                $this->createArchive();
            }

            return $this->buildResponse();
        } catch (Exception $e) {
            Log::error('FontHunterService error in generate: ' . $e->getMessage());
            $this->errors[] = 'Failed to process fonts: ' . $e->getMessage();
            return $this->buildResponse();
        }
    }

    public function download(string $zipUrl): mixed
    {
        try {
            $zipFileName = basename($zipUrl);
            $zipPath = "public/{$this->baseDir}.zip";

            if (!Storage::exists($zipPath)) {
                throw new RuntimeException("Zip file not found: {$zipFileName}");
            }

            return response()->download(storage_path("app/{$zipPath}"), $zipFileName)->deleteFileAfterSend(true);
        } catch (Exception $e) {
            Log::error('FontHunterService error in download: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'url' => $zipUrl,
                'filename' => $zipFileName,
                'zip_path' => $zipPath,
            ];
        }
    }

    private function parseFontFamilies(): void
    {
        $parsedUrl = parse_url($this->url, PHP_URL_QUERY);
        if (!$parsedUrl) {
            return;
        }

        parse_str($parsedUrl, $queryParams);
        if (!isset($queryParams['family'])) {
            return;
        }

        $families = explode('|', $queryParams['family']);
        foreach ($families as $family) {
            [$name, $variants] = array_pad(explode(':', $family), 2, '');
            if (empty($variants)) {
                $this->fontFamilies[$name][] = ['weight' => '400', 'style' => 'normal'];
                continue;
            }

            $variantsList = explode(',', $variants);
            foreach ($variantsList as $variant) {
                $isItalic = str_ends_with($variant, 'i');
                $weight = $isItalic ? rtrim($variant, 'i') : $variant;
                $this->fontFamilies[$name][] = [
                    'weight' => $weight ?: '400',
                    'style' => $isItalic ? 'italic' : 'normal',
                ];
            }
        }
    }

    private function processContent(): bool
    {
        $cssPath = "public/{$this->baseDir}/{$this->cssDir}/{$this->fileName}";
        $cssContent = $this->fetchContent($this->url);

        if ($cssContent === false) {
            $this->errors[] = "Failed to fetch CSS content from {$this->url}";
            return false;
        }

        $this->extractedFontFamilies = $this->extractFontFamilyNames($cssContent);
        $fontUrls = $this->extractFontUrls($cssContent);

        if (empty($fontUrls)) {
            $this->errors[] = 'No font URLs found in CSS';
            return false;
        }

        $downloaded = $this->downloadFonts($fontUrls, $cssPath);

        if ($downloaded === 0) {
            $this->cleanup($cssPath);
            return false;
        }

        $this->success[] = "{$downloaded} font(s) downloaded";
        return true;
    }

    private function extractFontUrls(string $cssContent): array
    {
        $fontExtensions = implode('|', array_keys(self::FONT_FORMATS));
        $pattern = '#(https?://[^\s"\'()]+\.(' . $fontExtensions . ')|[^\s"\'()]+\.(' . $fontExtensions . '))(\?[^"\']*)?#i';
        preg_match_all($pattern, $cssContent, $matches);

        $fontUrls = $matches[0] ?? [];
        $absoluteFontUrls = [];

        $baseUrl = rtrim(dirname($this->url), '/');

        foreach ($fontUrls as $url) {
            if (!preg_match('#^https?://#i', $url)) {
                $absoluteUrl = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
                $absoluteFontUrls[] = $absoluteUrl;
            } else {
                $absoluteFontUrls[] = $url;
            }
        }

        return array_unique($absoluteFontUrls);
    }

    private function downloadFonts(array $fontUrls, string $cssPath): int
    {
        $count = 0;
        $fontFaceRules = '';
        $fontIndex = 0;

        foreach ($fontUrls as $url) {
            $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            $filename = basename($url);
            $filePath = "public/{$this->baseDir}/{$this->fontsDir}/{$filename}";
            $localFontUrl = "../{$this->fontsDir}/{$filename}";

            $fontContent = $this->fetchContent($url);

            if ($fontContent === false || !Storage::put($filePath, $fontContent)) {
                $this->errors[] = "Failed to download or save font: {$filename} from {$url}";
                continue;
            }

            $count++;
            $this->success[] = "Downloaded: {$filename}";

            $fontFamilyData = $this->getFontFamilyData($fontIndex);
            $fontFamily = $fontFamilyData['name'] ?? pathinfo($filename, PATHINFO_FILENAME);
            $weight = $fontFamilyData['weight'] ?? '400';
            $style = $fontFamilyData['style'] ?? 'normal';
            $format = $this->getFontFormat($extension);

            $fontFaceRules .= $this->generateFontFaceRule($fontFamily, $localFontUrl, $format, $weight, $style);
            $fontIndex++;
        }

        if ($count > 0) {
            if (!Storage::put($cssPath, $fontFaceRules)) {
                $this->errors[] = "Unable to write CSS content to {$cssPath}";
                $this->cleanup($cssPath);
                return 0;
            }
            $this->success[] = "{$this->fileName} generated successfully";
        }

        return $count;
    }

    private function getFontFamilyData(int $index): array
    {
        if (!empty($this->extractedFontFamilies)) {
            $familyName = $this->extractedFontFamilies[$index % count($this->extractedFontFamilies)];
            return [
                'name' => $familyName,
                'weight' => '400',
                'style' => 'normal',
            ];
        }

        $familyNames = array_keys($this->fontFamilies);
        if (empty($familyNames)) {
            return [];
        }

        $familyName = $familyNames[$index % count($familyNames)];
        $variants = $this->fontFamilies[$familyName] ?? [];
        $variant = $variants[$index % count($variants)] ?? ['weight' => '400', 'style' => 'normal'];

        return [
            'name' => $familyName,
            'weight' => $variant['weight'],
            'style' => $variant['style'],
        ];
    }


    private function createArchive(): void
    {
        try {
            $dirPath = "public/{$this->baseDir}";
            $zipPath = storage_path("app/public/{$this->baseDir}.zip");
            $zip = new ZipArchive();

            if (!Storage::exists($dirPath)) {
                throw new RuntimeException("Directory not found: {$dirPath}");
            }

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create zip archive');
            }

            $files = Storage::allFiles($dirPath);
            foreach ($files as $file) {
                $relativePath = str_replace($dirPath . '/', '', $file);
                $zip->addFile(storage_path("app/{$file}"), $relativePath);
            }

            if (!$zip->close() || !file_exists($zipPath)) {
                throw new RuntimeException('Failed to finalize zip archive');
            }

            $this->success[] = 'Zip archive created successfully';
            $this->isDownloadable = true;
            $this->downloadUrl = $this->baseDir . '.zip';
            Storage::deleteDirectory($dirPath);
        } catch (Exception $e) {
            Log::error('FontHunterService error in createArchive: ' . $e->getMessage());
            $this->errors[] = $e->getMessage();
        }
    }

    private function fetchContent(string $url): string|bool
    {
        try {
            $response = Http::timeout(30)
                ->withUserAgent('Mozilla/5.0 FontHunter/1.0')
                ->get($url);
            return $response->successful() ? $response->body() : false;
        } catch (Exception $e) {
            Log::error("FontHunterService error fetching {$url}: " . $e->getMessage());
            $this->errors[] = "Error fetching {$url}: {$e->getMessage()}";
            return false;
        }
    }

    private function getFontFormat(string $extension): string
    {
        return self::FONT_FORMATS[$extension] ?? 'truetype';
    }

    private function extractFontFamilyNames(string $cssContent): array
    {
        $pattern = '/@font-face\s*{[^}]*font-family\s*:\s*[\'"]([^\'"]+)[\'"]/i';
        preg_match_all($pattern, $cssContent, $matches);
        return $matches[1] ?? [];
    }


    private function generateFontFaceRule(string $fontFamily, string $src, string $format, string $weight, string $style): string
    {
        return <<<CSS
@font-face {
    font-family: '{$fontFamily}';
    font-style: {$style};
    font-weight: {$weight};
    src: url('{$src}') format('{$format}');
}

CSS;
    }

    private function cleanup(string $cssPath): void
    {
        Storage::delete($cssPath);
        Storage::deleteDirectory("public/{$this->baseDir}");
    }

    private function buildResponse(): array
    {
        return [
            'status' => empty($this->errors),
            'message' => empty($this->errors)
                ? 'Fonts retrieved and zipped successfully'
                : 'Errors occurred during font retrieval',
            'url' => $this->url,
            'downloadable' => $this->isDownloadable,
            'download_url' => $this->downloadUrl,
            'success' => $this->success,
            'errors' => $this->errors,
        ];
    }
}

