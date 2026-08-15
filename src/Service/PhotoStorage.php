<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Photo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Reception, controle et stockage des photos.
 *
 * Les photos sont, et de tres loin, le premier poste de consommation de cette
 * application : tout le reste d'une page pese moins qu'un seul cliche non
 * redimensionne. D'ou le plafond a 1200 px et la conversion systematique en
 * WebP, qui divise le poids par trois a quatre par rapport au JPEG d'origine.
 *
 * Les fichiers sont ecrits hors de public/ et servis par un controleur derriere
 * le pare-feu : un jardin photographie reste une donnee privee.
 */
class PhotoStorage
{
    public const int LARGEUR_MAX = 1200;
    public const int LARGEUR_VIGNETTE = 300;
    public const int QUALITE = 75;
    public const int TAILLE_MAX_OCTETS = 12 * 1024 * 1024;

    /**
     * Types reellement acceptes, verifies par lecture du contenu et non par
     * l'extension : un fichier PHP renomme en .jpg est le vecteur d'attaque
     * classique des formulaires d'envoi.
     */
    public const array TYPES_ACCEPTES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private readonly string $photoDirectory)
    {
    }

    /**
     * @throws \RuntimeException si le fichier est refuse ou illisible
     */
    public function store(UploadedFile $file): Photo
    {
        $this->assertAcceptable($file);

        $source = $this->createImage($file->getPathname());
        $source = $this->applyExifOrientation($source, $file->getPathname());

        $dossierRelatif = (new \DateTimeImmutable())->format('Y/m');
        $dossier = rtrim($this->photoDirectory, '/').'/'.$dossierRelatif;

        if (!is_dir($dossier) && !mkdir($dossier, 0o750, true) && !is_dir($dossier)) {
            throw new \RuntimeException('Impossible de creer le dossier de stockage.');
        }

        $identifiant = bin2hex(random_bytes(16));
        $chemin = $dossierRelatif.'/'.$identifiant.'.webp';

        $grande = $this->resize($source, self::LARGEUR_MAX);
        $this->write($grande, $dossier.'/'.$identifiant.'.webp');

        $vignette = $this->resize($source, self::LARGEUR_VIGNETTE);
        $this->write($vignette, $dossier.'/'.$identifiant.'-vignette.webp');

        $largeur = imagesx($grande);
        $hauteur = imagesy($grande);

        $photo = new Photo();
        $photo->setPath($chemin);
        $photo->setOriginalName($file->getClientOriginalName());
        $photo->setWidth($largeur);
        $photo->setHeight($hauteur);
        $photo->setSizeBytes(filesize($dossier.'/'.$identifiant.'.webp') ?: null);

        return $photo;
    }

    /**
     * Chemin absolu d'une photo, ou de sa vignette.
     */
    public function absolutePath(Photo $photo, bool $vignette = false): string
    {
        $chemin = $vignette
            ? preg_replace('/\.webp$/', '-vignette.webp', $photo->getPath()) ?? $photo->getPath()
            : $photo->getPath();

        return rtrim($this->photoDirectory, '/').'/'.$chemin;
    }

    public function remove(Photo $photo): void
    {
        foreach ([false, true] as $vignette) {
            $chemin = $this->absolutePath($photo, $vignette);
            if (is_file($chemin)) {
                unlink($chemin);
            }
        }
    }

    private function assertAcceptable(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \RuntimeException($this->describeUploadError($file->getError()));
        }

        if ($file->getSize() > self::TAILLE_MAX_OCTETS) {
            throw new \RuntimeException(\sprintf(
                'Fichier trop lourd : %s Mo, maximum %s Mo.',
                round(($file->getSize() ?? 0) / 1024 / 1024, 1),
                round(self::TAILLE_MAX_OCTETS / 1024 / 1024),
            ));
        }

        // getMimeType() lit le contenu du fichier, pas son extension.
        $type = $file->getMimeType();
        if (!\in_array($type, self::TYPES_ACCEPTES, true)) {
            throw new \RuntimeException('Format non pris en charge. Formats acceptes : JPEG, PNG, WebP.');
        }

        // Second garde-fou : getimagesize echoue sur tout ce qui n'est pas une
        // image, y compris un fichier au type MIME correctement maquille.
        $dimensions = @getimagesize($file->getPathname());
        if (false === $dimensions) {
            throw new \RuntimeException('Ce fichier n\'est pas une image exploitable.');
        }
    }

    private function describeUploadError(int $code): string
    {
        return match ($code) {
            \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => \sprintf(
                'Photo trop lourde pour la configuration du serveur (upload_max_filesize vaut %s).',
                ini_get('upload_max_filesize'),
            ),
            \UPLOAD_ERR_PARTIAL => 'Envoi interrompu, la photo n\'est arrivee qu\'en partie.',
            \UPLOAD_ERR_NO_FILE => 'Aucun fichier recu.',
            \UPLOAD_ERR_NO_TMP_DIR, \UPLOAD_ERR_CANT_WRITE => 'Le serveur n\'a pas pu ecrire le fichier temporaire.',
            default => 'Envoi impossible.',
        };
    }

    private function createImage(string $chemin): \GdImage
    {
        $image = @imagecreatefromstring(file_get_contents($chemin) ?: '');

        if (false === $image) {
            throw new \RuntimeException('Image illisible.');
        }

        return $image;
    }

    /**
     * Redresse la photo selon l'orientation enregistree par l'appareil.
     *
     * Sans cela, toute photo prise en tenant le telephone autrement qu'en mode
     * portrait apparait couchee : les donnees des pixels sont dans un sens, et
     * seule l'etiquette EXIF dit comment les afficher.
     */
    private function applyExifOrientation(\GdImage $image, string $chemin): \GdImage
    {
        if (!\function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($chemin);
        $orientation = \is_array($exif) && isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;

        $pivote = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if (null === $pivote || false === $pivote) {
            return $image;
        }

        return $pivote;
    }

    /**
     * Reduit l'image pour que son plus grand cote n'excede pas la limite.
     *
     * Une image deja plus petite est recopiee telle quelle : l'agrandir
     * n'ajouterait aucun detail et ne ferait que gonfler le fichier.
     */
    private function resize(\GdImage $source, int $limite): \GdImage
    {
        $largeur = imagesx($source);
        $hauteur = imagesy($source);
        $plusGrandCote = max($largeur, $hauteur);

        $ratio = $plusGrandCote > $limite ? $limite / $plusGrandCote : 1.0;
        $nouvelleLargeur = max(1, (int) round($largeur * $ratio));
        $nouvelleHauteur = max(1, (int) round($hauteur * $ratio));

        $destination = imagecreatetruecolor($nouvelleLargeur, $nouvelleHauteur);
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $nouvelleLargeur, $nouvelleHauteur, $largeur, $hauteur);

        return $destination;
    }

    private function write(\GdImage $image, string $chemin): void
    {
        if (!imagewebp($image, $chemin, self::QUALITE)) {
            throw new \RuntimeException('Ecriture de la photo impossible.');
        }

        chmod($chemin, 0o640);
    }
}
