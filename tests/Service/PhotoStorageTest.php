<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\PhotoStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PhotoStorageTest extends TestCase
{
    private string $dossier;
    private PhotoStorage $storage;
    /** @var list<string> */
    private array $temporaires = [];

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir().'/carnet-photos-'.bin2hex(random_bytes(6));
        $this->storage = new PhotoStorage($this->dossier);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaires as $fichier) {
            if (is_file($fichier)) {
                unlink($fichier);
            }
        }

        if (is_dir($this->dossier)) {
            $iterateur = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->dossier, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterateur as $fichier) {
                $fichier->isDir() ? rmdir($fichier->getPathname()) : unlink($fichier->getPathname());
            }
            rmdir($this->dossier);
        }
    }

    /**
     * Fabrique une vraie image JPEG sur disque et l'enveloppe comme un envoi.
     */
    private function upload(int $largeur, int $hauteur, string $nom = 'radis.jpg'): UploadedFile
    {
        $image = imagecreatetruecolor($largeur, $hauteur);
        imagefilledrectangle($image, 0, 0, $largeur, $hauteur, imagecolorallocate($image, 60, 110, 20));

        $chemin = sys_get_temp_dir().'/upload-'.bin2hex(random_bytes(6)).'.jpg';
        imagejpeg($image, $chemin, 90);

        $this->temporaires[] = $chemin;

        return new UploadedFile($chemin, $nom, 'image/jpeg', null, true);
    }

    private function fichierTexte(string $nom): UploadedFile
    {
        $chemin = sys_get_temp_dir().'/faux-'.bin2hex(random_bytes(6));
        file_put_contents($chemin, "<?php echo 'ceci n\'est pas une image';");
        $this->temporaires[] = $chemin;

        return new UploadedFile($chemin, $nom, 'image/jpeg', null, true);
    }

    public function testUnePhotoEstRedimensionneeEtConvertie(): void
    {
        $photo = $this->storage->store($this->upload(3000, 2000));

        self::assertSame(PhotoStorage::LARGEUR_MAX, $photo->getWidth());
        self::assertSame(800, $photo->getHeight(), 'Le ratio doit etre conserve.');
        self::assertStringEndsWith('.webp', $photo->getPath());
        self::assertFileExists($this->storage->absolutePath($photo));
    }

    public function testUneVignetteEstGeneree(): void
    {
        $photo = $this->storage->store($this->upload(3000, 2000));

        $vignette = $this->storage->absolutePath($photo, true);
        self::assertFileExists($vignette);

        $dimensions = getimagesize($vignette);
        self::assertNotFalse($dimensions);
        self::assertSame(PhotoStorage::LARGEUR_VIGNETTE, $dimensions[0]);
    }

    public function testUnePetitePhotoNestPasAgrandie(): void
    {
        $photo = $this->storage->store($this->upload(400, 300));

        self::assertSame(400, $photo->getWidth());
        self::assertSame(300, $photo->getHeight());
    }

    public function testLaPhotoEstRangeeParAnneeEtMois(): void
    {
        $photo = $this->storage->store($this->upload(800, 600));

        self::assertMatchesRegularExpression('#^\d{4}/\d{2}/[0-9a-f]{32}\.webp$#', $photo->getPath());
    }

    public function testLeNomDorigineEstConserveMaisPasReutiliseCommeChemin(): void
    {
        $photo = $this->storage->store($this->upload(800, 600, '../../etc/passwd.jpg'));

        self::assertSame('passwd.jpg', $photo->getOriginalName(), 'Symfony nettoie deja le nom client.');
        self::assertStringNotContainsString('..', $photo->getPath());
    }

    public function testUnFichierNonImageEstRefuse(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->storage->store($this->fichierTexte('innocent.jpg'));
    }

    public function testLaSuppressionRetireLesDeuxFichiers(): void
    {
        $photo = $this->storage->store($this->upload(800, 600));
        $grande = $this->storage->absolutePath($photo);
        $vignette = $this->storage->absolutePath($photo, true);

        $this->storage->remove($photo);

        self::assertFileDoesNotExist($grande);
        self::assertFileDoesNotExist($vignette);
    }

    public function testLeWebpAllegeSensiblementLeFichier(): void
    {
        $upload = $this->upload(2400, 1800);
        $poidsOrigine = $upload->getSize();

        $photo = $this->storage->store($upload);

        self::assertNotNull($photo->getSizeBytes());
        self::assertLessThan(
            $poidsOrigine,
            $photo->getSizeBytes(),
            'Une photo stockee doit toujours peser moins que l\'originale.',
        );
    }
}
