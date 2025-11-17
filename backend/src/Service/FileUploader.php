<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class FileUploader
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public function __construct(
        private string $uploadsDirectory,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Upload une image de carte.
     *
     * @param UploadedFile $file Fichier uploadé
     *
     * @return string URL publique du fichier
     *
     * @throws InvalidArgumentException Si le fichier est invalide
     * @throws FileException Si l'upload échoue
     */
    public function uploadMapImage(UploadedFile $file): string
    {
        $this->validateFile($file);

        $directory = $this->uploadsDirectory . '/maps/' . date('Y/m');
        $filename = $this->generateUniqueFilename($file);

        return $this->moveFile($file, $directory, $filename);
    }

    /**
     * Upload une image de token.
     *
     * @param UploadedFile $file Fichier uploadé
     *
     * @return string URL publique du fichier
     */
    public function uploadTokenImage(UploadedFile $file): string
    {
        $this->validateFile($file);

        $directory = $this->uploadsDirectory . '/tokens/' . date('Y/m');
        $filename = $this->generateUniqueFilename($file);

        return $this->moveFile($file, $directory, $filename);
    }

    /**
     * Upload un avatar utilisateur.
     *
     * @param UploadedFile $file Fichier uploadé
     *
     * @return string URL publique du fichier
     */
    public function uploadAvatar(UploadedFile $file): string
    {
        $this->validateFile($file);

        $directory = $this->uploadsDirectory . '/avatars';
        $filename = $this->generateUniqueFilename($file);

        return $this->moveFile($file, $directory, $filename);
    }

    /**
     * Supprime un fichier (ne fait rien si le fichier n'existe pas).
     *
     * @param string $url URL publique du fichier
     */
    public function deleteFile(string $url): void
    {
        $parsedPath = parse_url($url, \PHP_URL_PATH);
        if (false === $parsedPath || null === $parsedPath) {
            $this->logger->warning('Invalid URL for file deletion', ['url' => $url]);

            return;
        }

        $relativePath = str_replace('/uploads/', '', $parsedPath);
        $fullPath = $this->uploadsDirectory . '/' . $relativePath;

        if (file_exists($fullPath)) {
            try {
                unlink($fullPath);
                $this->logger->info('File deleted', ['path' => $fullPath]);
            }
            catch (Exception $e) {
                $this->logger->error('Failed to delete file', [
                    'path' => $fullPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Valide un fichier uploadé.
     *
     * @throws InvalidArgumentException Si le fichier est invalide
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('Fichier invalide : ' . $file->getErrorMessage());
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException(
                \sprintf('Fichier trop volumineux (max %d Mo)', self::MAX_FILE_SIZE / 1024 / 1024),
            );
        }

        $mimeType = $file->getMimeType();
        if (!\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException(
                \sprintf('Type de fichier non autorisé : %s', $mimeType),
            );
        }
    }

    /**
     * Génère un nom de fichier unique et sécurisé.
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $extension = $file->guessExtension();

        return \sprintf(
            '%s-%s.%s',
            $safeFilename,
            uniqid('', true),
            $extension,
        );
    }

    /**
     * Déplace le fichier uploadé vers le répertoire de destination.
     *
     * @return string URL publique du fichier
     *
     * @throws FileException Si le déplacement échoue
     */
    private function moveFile(UploadedFile $file, string $directory, string $filename): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        try {
            $file->move($directory, $filename);

            $relativePath = str_replace($this->uploadsDirectory, '', $directory);
            $publicUrl = '/uploads' . $relativePath . '/' . $filename;

            $this->logger->info('File uploaded', [
                'filename' => $filename,
                'directory' => $directory,
                'url' => $publicUrl,
            ]);

            return $publicUrl;
        }
        catch (FileException $e) {
            $this->logger->error('File upload failed', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            throw new FileException('Échec de l\'upload : ' . $e->getMessage());
        }
    }
}
