<?php

namespace App\Services;

class FileSecurityService
{
    /**
     * Allowed educational file extensions (Whitelist).
     */
    protected const ALLOWED_EXTENSIONS = [
        // Documents
        'pdf', 'doc', 'docx', 'odt', 'rtf', 'txt',
        // Presentations
        'ppt', 'pptx', 'odp',
        // Spreadsheets
        'xls', 'xlsx', 'ods', 'csv',
        // Archives
        'zip', 'rar', '7z', 'tar', 'gz',
        // Images
        'jpg', 'jpeg', 'png', 'webp', 'heic',
    ];

    /**
     * Dangerous executable and script extensions (Hard Blacklist).
     */
    protected const DANGEROUS_EXTENSIONS = [
        'exe', 'dll', 'so', 'dylib', 'bat', 'cmd', 'sh', 'bash', 'zsh',
        'ps1', 'vbs', 'vbe', 'js', 'jse', 'wsf', 'wsh', 'hta', 'scr',
        'pif', 'com', 'jar', 'apk', 'app', 'ipa', 'bin', 'iso', 'img',
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phar',
        'py', 'pyc', 'pyo', 'rb', 'pl', 'cgi', 'asp', 'aspx', 'jsp',
        'shtml', 'htaccess', 'htpasswd', 'env', 'config',
    ];

    /**
     * Known binary signatures ("Magic Bytes") for allowed types.
     */
    protected const MAGIC_BYTES = [
        'pdf'  => ["%PDF-"],
        'zip'  => ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"],
        'docx' => ["PK\x03\x04"],
        'pptx' => ["PK\x03\x04"],
        'xlsx' => ["PK\x03\x04"],
        'odt'  => ["PK\x03\x04"],
        'odp'  => ["PK\x03\x04"],
        'ods'  => ["PK\x03\x04"],
        'png'  => ["\x89PNG\r\n\x1a\n"],
        'jpg'  => ["\xFF\xD8\xFF"],
        'jpeg' => ["\xFF\xD8\xFF"],
        'webp' => ["RIFF"],
        'rar'  => ["Rar!\x1A\x07\x00", "Rar!\x1A\x07\x01\x00"],
        '7z'   => ["7z\xBC\xAF\x27\x1C"],
        'gz'   => ["\x1F\x8B"],
    ];

    /**
     * Signatures of dangerous executable files (Forbidden Signatures).
     */
    protected const MALICIOUS_SIGNATURES = [
        'MZ'     => 'Windows Executable/DLL (PE binary)',
        "\x7FELF" => 'Linux Executable (ELF binary)',
        "\xCA\xFE\xBA\xBE" => 'Java/Mach-O Binary',
        "#!/"    => 'Shell Script Executable',
        "<?php"  => 'PHP Web Script',
        "<?="    => 'PHP Short Tag Script',
        "<script" => 'HTML/JS Executable Payload',
    ];

    /**
     * Validate an incoming file submission.
     *
     * @param string $originalFilename Original name of the uploaded file
     * @param string|null $mimeType Client-reported MIME type
     * @param string|null $filePath Local path to the file for deep inspection (if downloaded)
     * @return array [bool 'valid', string|null 'reason', string 'sanitized_filename']
     */
    public function validateUpload(string $originalFilename, ?string $mimeType = null, ?string $filePath = null): array
    {
        $sanitizedName = $this->sanitizeFileName($originalFilename);
        $extension = strtolower(pathinfo($sanitizedName, PATHINFO_EXTENSION));

        // 1. Check for empty filename or missing extension
        if (empty($extension)) {
            return [
                'valid' => false,
                'reason' => 'missing_extension',
                'sanitized_filename' => $sanitizedName,
            ];
        }

        // 2. Strict Blacklist Check (Executable or dangerous files)
        if (in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
            return [
                'valid' => false,
                'reason' => 'dangerous_extension',
                'sanitized_filename' => $sanitizedName,
            ];
        }

        // 3. Double-extension attack check (e.g. document.php.pdf, exploit.exe.docx)
        if ($this->hasDangerousDoubleExtension($sanitizedName)) {
            return [
                'valid' => false,
                'reason' => 'double_extension',
                'sanitized_filename' => $sanitizedName,
            ];
        }

        // 4. Strict Whitelist Check (Must be recognized educational format)
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return [
                'valid' => false,
                'reason' => 'unsupported_extension',
                'sanitized_filename' => $sanitizedName,
            ];
        }

        // 5. Deep Magic-Bytes & Antivirus Inspection (when file path is accessible)
        if ($filePath && file_exists($filePath)) {
            $magicResult = $this->verifyFileContent($filePath, $extension);
            if (! $magicResult['valid']) {
                return [
                    'valid' => false,
                    'reason' => $magicResult['reason'],
                    'sanitized_filename' => $sanitizedName,
                ];
            }

            // 6. Optional ClamAV scan (if installed on the server)
            if ($this->isClamAvAvailable()) {
                $clamResult = $this->scanWithClamAv($filePath);
                if (! $clamResult['clean']) {
                    return [
                        'valid' => false,
                        'reason' => 'virus_detected',
                        'sanitized_filename' => $sanitizedName,
                    ];
                }
            }
        }

        return [
            'valid' => true,
            'reason' => null,
            'sanitized_filename' => $sanitizedName,
        ];
    }

    /**
     * Verify the raw binary header of a file against expected signatures and forbidden patterns.
     */
    public function verifyFileContent(string $filePath, string $extension): array
    {
        $handle = @fopen($filePath, 'rb');
        if (! $handle) {
            return ['valid' => true, 'reason' => null];
        }

        $header = fread($handle, 512);
        fclose($handle);

        if ($header === false || strlen($header) === 0) {
            return ['valid' => false, 'reason' => 'empty_file'];
        }

        // A. Check for known malicious binary signatures regardless of extension
        foreach (self::MALICIOUS_SIGNATURES as $signature => $desc) {
            if (str_starts_with($header, $signature)) {
                return ['valid' => false, 'reason' => 'malicious_signature_detected'];
            }
        }

        // Special check: Case-insensitive search for PHP code (Don't do that colleague) in initial bytes
        if (stripos($header, '<?php') !== false || stripos($header, 'eval(') !== false) {
            return ['valid' => false, 'reason' => 'embedded_script_detected'];
        }

        // B. Magic Bytes verification for strictly structured formats (PDF, Images, Archives)
        if (isset(self::MAGIC_BYTES[$extension])) {
            $matched = false;
            foreach (self::MAGIC_BYTES[$extension] as $magic) {
                if (str_starts_with($header, $magic)) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                return ['valid' => false, 'reason' => 'magic_bytes_mismatch'];
            }
        }

        return ['valid' => true, 'reason' => null];
    }

    /**
     * Check if a filename contains dangerous secondary extensions (e.g. file.php.pdf).
     */
    protected function hasDangerousDoubleExtension(string $filename): bool
    {
        $parts = explode('.', strtolower($filename));
        if (count($parts) < 3) {
            return false;
        }

        // Drop the final extension and inspect preceding parts
        array_pop($parts);
        foreach ($parts as $part) {
            if (in_array($part, self::DANGEROUS_EXTENSIONS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize a filename to strip path traversals, control characters, and hidden components.
     */
    public function sanitizeFileName(string $filename): string
    {
        // 1. Strip null-bytes and directory separators
        $cleaned = str_replace(["\0", '../', '..\\', '/', '\\'], '', $filename);

        // 2. Strip control characters
        $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned);

        // 3. Fallback for blank names
        $cleaned = trim($cleaned);
        if ($cleaned === '' || $cleaned === '.') {
            $cleaned = 'document_' . date('Ymd_His') . '.pdf';
        }

        return $cleaned;
    }

    /**
     * Check if ClamAV CLI scanner (clamscan) is available on the system.
     */
    public function isClamAvAvailable(): bool
    {
        static $available = null;
        if ($available !== null) {
            return $available;
        }

        if (! function_exists('exec')) {
            $available = false;
            return false;
        }

        // Check clamscan binary
        $output = [];
        $returnVar = 0;
        @exec('clamscan --version 2>&1', $output, $returnVar);

        $available = ($returnVar === 0);
        return $available;
    }

    /**
     * Scan a local file using ClamAV.
     */
    public function scanWithClamAv(string $filePath): array
    {
        $escapedPath = escapeshellarg($filePath);
        $output = [];
        $returnVar = 0;

        @exec("clamscan --no-summary {$escapedPath} 2>&1", $output, $returnVar);

        // ClamAV return codes: 0 = clean, 1 = infected, 2 = error
        if ($returnVar === 1) {
            return [
                'clean' => false,
                'details' => implode("\n", $output),
            ];
        }

        return [
            'clean' => true,
            'details' => null,
        ];
    }
}
