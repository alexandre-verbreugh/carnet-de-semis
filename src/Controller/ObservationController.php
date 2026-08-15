<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Observation;
use App\Entity\Sowing;
use App\Form\ObservationForm;
use App\Service\ObservationRecorder;
use App\Service\PhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/observations')]
class ObservationController extends AbstractController
{
    #[Route('/nouvelle', name: 'app_observation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ObservationRecorder $recorder,
        PhotoStorage $storage,
        #[MapEntity(mapping: ['semis' => 'id'])] ?Sowing $semis = null,
    ): Response {
        $observation = new Observation();

        if (null !== $semis) {
            $observation->setSowing($semis);
        }

        $form = $this->createForm(ObservationForm::class, $observation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // L'emplacement est toujours renseigne, y compris quand seule
            // l'observation d'un semis a ete saisie.
            if (null === $observation->getPlot()) {
                $observation->setPlot($observation->getSowing()?->getPlot());
            }

            if (null === $observation->getPlot()) {
                $this->addFlash('error', 'Choisis un emplacement ou un semis.');

                return $this->render('observation/new.html.twig', ['form' => $form]);
            }

            $refusees = $this->attachPhotos($observation, $form->get('photos')->getData(), $storage);

            $recorder->record($observation);

            $this->addFlash('success', 'Observation enregistrée.');

            foreach ($refusees as $message) {
                $this->addFlash('warning', $message);
            }

            return null !== $observation->getSowing()
                ? $this->redirectToRoute('app_sowing_show', ['id' => $observation->getSowing()->getId()])
                : $this->redirectToRoute('app_plot_show', ['id' => $observation->getPlot()->getId()]);
        }

        return $this->render('observation/new.html.twig', [
            'form' => $form,
            'semis' => $semis,
        ]);
    }

    /**
     * Transforme les fichiers recus en photos rattachees a l'observation.
     *
     * Une photo refusee n'annule jamais l'observation : la note ecrite au
     * jardin a plus de valeur qu'un cliche, et tout perdre parce qu'un fichier
     * est trop lourd serait le plus sur moyen de ne plus rien saisir.
     *
     * @param list<UploadedFile>|UploadedFile|null $fichiers
     *
     * @return list<string> messages des photos refusees
     */
    private function attachPhotos(Observation $observation, mixed $fichiers, PhotoStorage $storage): array
    {
        if (null === $fichiers) {
            return [];
        }

        $refusees = [];

        foreach (\is_array($fichiers) ? $fichiers : [$fichiers] as $fichier) {
            if (!$fichier instanceof UploadedFile) {
                continue;
            }

            try {
                $observation->addPhoto($storage->store($fichier));
            } catch (\RuntimeException $exception) {
                $refusees[] = \sprintf('%s : %s', $fichier->getClientOriginalName(), $exception->getMessage());
            }
        }

        return $refusees;
    }

    #[Route('/{id}/supprimer', name: 'app_observation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Observation $observation, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('supprimer-observation-'.$observation->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton invalide, suppression annulée.');

            return $this->redirectToRoute('app_sowing_index');
        }

        $semis = $observation->getSowing();
        $plot = $observation->getPlot();

        $entityManager->remove($observation);
        $entityManager->flush();

        // Les valeurs denormalisees du semis ne sont pas recalculees ici : la
        // suppression d'une levee laisserait une date orpheline. C'est assume
        // pour l'instant, et signale a la personne qui supprime.
        $this->addFlash('warning', 'Observation supprimée. Vérifie la date et le nombre de levés du semis si tu viens de supprimer une levée.');

        return null !== $semis
            ? $this->redirectToRoute('app_sowing_show', ['id' => $semis->getId()])
            : $this->redirectToRoute('app_plot_show', ['id' => $plot?->getId()]);
    }
}
