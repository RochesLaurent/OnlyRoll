<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\FileUploader;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class FileUploaderTest extends TestCase
{
    private string $uploadsDirectory;

    private SluggerInterface&MockObject $slugger;

    private LoggerInterface&MockObject $logger;

    private FileUploader $fileUploader;

    protected function setUp(): void
    {
        $this->uploadsDirectory = sys_get_temp_dir() . '/test_uploads';
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        if (!is_dir($this->uploadsDirectory)) {
            mkdir($this->uploadsDirectory, 0755, true);
        }

        $this->fileUploader = new FileUploader(
            $this->uploadsDirectory,
            $this->slugger,
            $this->logger,
        );
    }

    protected function tearDown(): void
    {
        // Cleanup
        if (is_dir($this->uploadsDirectory)) {
            $this->removeDirectory($this->uploadsDirectory);
        }
    }

    public function testUploadMapImageSuccess(): void
    {
        $file = $this->createValidUploadedFile();

        $this->slugger->expects($this->once())
            ->method('slug')
            ->willReturn(new UnicodeString('test-map'));

        $this->logger->expects($this->once())
            ->method('info');

        $url = $this->fileUploader->uploadMapImage($file);

        $this->assertStringContainsString('/uploads/maps/', $url);
        $this->assertStringContainsString('test-map', $url);
        $this->assertStringEndsWith('.jpg', $url);
    }

    public function testUploadTokenImageSuccess(): void
    {
        $file = $this->createValidUploadedFile();

        $this->slugger->expects($this->once())
            ->method('slug')
            ->willReturn(new UnicodeString('test-token'));

        $this->logger->expects($this->once())
            ->method('info');

        $url = $this->fileUploader->uploadTokenImage($file);

        $this->assertStringContainsString('/uploads/tokens/', $url);
    }

    public function testUploadAvatarSuccess(): void
    {
        $file = $this->createValidUploadedFile();

        $this->slugger->expects($this->once())
            ->method('slug')
            ->willReturn(new UnicodeString('avatar'));

        $this->logger->expects($this->once())
            ->method('info');

        $url = $this->fileUploader->uploadAvatar($file);

        $this->assertStringContainsString('/uploads/avatars/', $url);
    }

    public function testUploadThrowsExceptionForInvalidFile(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(false);
        $file->method('getErrorMessage')->willReturn('Upload error');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fichier invalide : Upload error');

        $this->fileUploader->uploadMapImage($file);
    }

    public function testUploadThrowsExceptionForFileTooLarge(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn(11 * 1024 * 1024); // 11 MB

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fichier trop volumineux');

        $this->fileUploader->uploadMapImage($file);
    }

    public function testUploadThrowsExceptionForInvalidMimeType(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn(1024 * 1024);
        $file->method('getMimeType')->willReturn('application/pdf');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type de fichier non autorisé');

        $this->fileUploader->uploadMapImage($file);
    }

    public function testDeleteFileSuccess(): void
    {
        $testFile = $this->uploadsDirectory . '/test.jpg';
        touch($testFile);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('File deleted', $this->anything());

        $this->fileUploader->deleteFile('/uploads/test.jpg');

        $this->assertFileDoesNotExist($testFile);
    }

    public function testDeleteFileDoesNothingWhenFileNotExists(): void
    {
        $this->logger->expects($this->never())
            ->method('info');

        $this->logger->expects($this->never())
            ->method('error');

        $this->fileUploader->deleteFile('/uploads/nonexistent.jpg');
    }

    public function testDeleteFileWithInvalidUrl(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Invalid URL for file deletion', $this->anything());

        $this->fileUploader->deleteFile('http://');
    }

    public function testUploadCreatesDirectoryIfNotExists(): void
    {
        $file = $this->createValidUploadedFile();

        $this->slugger->method('slug')->willReturn(new UnicodeString('test'));

        $this->fileUploader->uploadMapImage($file);

        $expectedDir = $this->uploadsDirectory . '/maps/' . date('Y/m');
        $this->assertDirectoryExists($expectedDir);
    }

    public function testGeneratedFilenameIsUnique(): void
    {
        $file1 = $this->createValidUploadedFile();
        $file2 = $this->createValidUploadedFile();

        $this->slugger->method('slug')->willReturn(new UnicodeString('test'));

        $url1 = $this->fileUploader->uploadMapImage($file1);
        $url2 = $this->fileUploader->uploadMapImage($file2);

        $this->assertNotEquals($url1, $url2);
    }

    public function testAcceptsJpegMimeType(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn(1024);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('guessExtension')->willReturn('jpg');
        $file->method('getClientOriginalName')->willReturn('test.jpg');

        $this->slugger->method('slug')->willReturn(new UnicodeString('test'));

        // Should not throw exception
        $url = $this->fileUploader->uploadMapImage($file);
        $this->assertNotEmpty($url);
    }

    public function testAcceptsPngMimeType(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn(1024);
        $file->method('getMimeType')->willReturn('image/png');
        $file->method('guessExtension')->willReturn('png');
        $file->method('getClientOriginalName')->willReturn('test.png');

        $this->slugger->method('slug')->willReturn(new UnicodeString('test'));

        $url = $this->fileUploader->uploadMapImage($file);
        $this->assertStringEndsWith('.png', $url);
    }

    public function testAcceptsWebpMimeType(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn(1024);
        $file->method('getMimeType')->willReturn('image/webp');
        $file->method('guessExtension')->willReturn('webp');
        $file->method('getClientOriginalName')->willReturn('test.webp');

        $this->slugger->method('slug')->willReturn(new UnicodeString('test'));

        $url = $this->fileUploader->uploadMapImage($file);
        $this->assertStringEndsWith('.webp', $url);
    }

    private function createValidUploadedFile(): UploadedFile&MockObject
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn(1024 * 1024); // 1MB
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('guessExtension')->willReturn('jpg');
        $file->method('getClientOriginalName')->willReturn('test-image.jpg');
        $file->method('move')->willReturn($file);

        return $file;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
