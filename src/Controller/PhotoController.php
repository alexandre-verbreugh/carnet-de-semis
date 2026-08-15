<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Photo;
use App\Service\PhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sert les photos depuis var/uploads, derriere le pare-feu.
 *
 * Les fichiers ne sont volontairement pas places dans public/ : ils y seraient
 * accessibles a quiconque devine une URL, alors qu'un jardin photographie et
 * les abords d'une maison sont des donnees privees.
 */
#[Route('/photos')]
class PhotoController extends AbstractController
{
    #[Route('/{id}', name: 'app_photo_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Photo $photo, Request $request, PhotoStorage $storage): Response
    {
        $vignette = $request->query->getBoolean('vignette');
        $chemin = $storage->absolutePath($photo, $vignette);

        if (!is_file($chemin)) {
            throw $this->createNotFoundException('Photo introuvable sur le disque.');
        }

        $response = new BinaryFileResponse($chemin);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $photo->getId().'.webp');
        $response->headers->set('Content-Type', 'image/webp');

        // Cache prive et long : le contenu ne change jamais, seul le navigateur
        // de la personne connectee doit le conserver.
        $response->setPrivate();
        $response->setMaxAge(31536000);
        $response->setEtag((string) $photo->getId());
        $response->isNotModified($request);

        return $response;
    }

    #[Route('/{id}/supprimer', name: 'app_photo_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Photo $photo,
        Request $request,
        PhotoStorage $storage,
        EntityManagerInterface $entityManager,
    ): Response {
        $observation = $photo->getObservation();
        $semis = $observation?->getSowing();

        if (!$this->isCsrfTokenValid('supprimer-photo-'.$photo->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton invalide, suppression annulée.');
        } else {
            $storage->remove($photo);
            $entityManager->remove($photo);
            $entityManager->flush();

            $this->addFlash('success', 'Photo supprimée.');
        }

        return null !== $semis
            ? $this->redirectToRoute('app_sowing_show', ['id' => $semis->getId()])
            : $this->redirectToRoute('app_plot_show', ['id' => $observation?->getPlot()?->getId()]);
    }
}
