<?php

namespace App\Controller;

use App\Entity\Candidate;
use App\Form\NewCandidateType;
use App\Helper\CandidateHelper;
use App\Repository\CandidateRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolation;

class CandidateController extends AbstractController
{
    #[Route('/candidate', name: 'app_candidate', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Routes ',
            'data' => [
                [
                    'description' => 'List read resume',
                    'link' => $this->generateUrl('list_read_resume'),
                    'query_params' => [
                        'sort' => 'first_name|last_name|salary|position|created_at|updated_at',
                        'sortDirection' => 'ASC|DESC',
                    ],
                ],
                [
                    'description' => 'List unread resume',
                    'link' => $this->generateUrl('list_unread_resume'),
                    'query_params' => [
                        'sort' => 'first_name|last_name|salary|position|created_at|updated_at',
                        'sortDirection' => 'ASC|DESC',
                    ],
                ],
                [
                    'description' => 'Read resume for candidateId = 1',
                    'link' => $this->generateUrl('read_resume', ['candidateId' => 1]),
                    'path_params' => [
                        'candidateId' => 'Get via ' . $this->generateUrl('list_unread_resume') . ' OR ' . $this->generateUrl('list_read_resume')
                    ]

                ],
            ],
        ]);
    }

    #[Route('/candidate/read', name: 'list_read_resume', methods: ['GET'])]
    public function listRead(
        CandidateRepository $candidateRepository,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
    ): JsonResponse
    {
        $this->validateSortParams($sort, $sortDirection);
        return $this->json($this->assembleCollectionResponseModel($candidateRepository->findRead($sort, $sortDirection)));
    }

    #[Route('/candidate/unread', name: 'list_unread_resume', methods: ['GET'])]
    public function listUnread(
        CandidateRepository $candidateRepository,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
    ): JsonResponse
    {
        $this->validateSortParams($sort, $sortDirection);
        return $this->json($this->assembleCollectionResponseModel($candidateRepository->findUnread($sort, $sortDirection)));
    }

    #[Route('/candidate/{candidateId}', name: 'read_resume', requirements: ['candidateId' => '\d+'], methods: ['GET'])]
    public function readItem(int $candidateId, CandidateRepository $candidateRepository)
    {
        $candidate = $candidateRepository->findOneById($candidateId);
        if (!$candidate) {
            return $this->json($this->createNotFoundException())->setStatusCode(Response::HTTP_NOT_FOUND);
        }
        $candidateRepository->setRead($candidateId);

        return $this->json($this->assembleResponseModel($candidateRepository->findOneById($candidateId)));
    }

    #[Route('/candidate', name: 'add_resume', methods: ['POST'])]
    public function addItem(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();

        $data['level'] = Candidate::LEVEL_JUNIOR;
        $data['created_at'] = new DateTimeImmutable('now');
        $data['update_at'] = new DateTimeImmutable('now');

        $form = $this->createForm(NewCandidateType::class);

        $form->submit($data);

        if (!$form->isValid()) {

            $errors = $form->getErrors(true);
            $resErrors = [];
            /** @var FormError $error */
            foreach ($errors as $error) {

                $cause = $error->getCause();
                if ($cause instanceof ConstraintViolation) {
                    /** @var ConstraintViolation $cause */
                    $fieldName = str_replace('data.', '', $cause->getPropertyPath());
                } else {
                    $fieldName = (string)$cause;
                }
                $resErrors[$fieldName] = $error->getMessage();
            }
            return $this->json(['errors' => $resErrors])->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        /** @var Candidate $data */
        $data = $form->getData();
        $data->setLevel(CandidateHelper::getLevelBySalary($data->getSalary()));
        $data->setViewed(false);
        $data->setCreatedAt(new DateTimeImmutable());
        $data->setUpdatedAt(new DateTimeImmutable());

        $entityManager->persist($data);
        $entityManager->flush();
        return $this->json($this->assembleResponseModel($data))->setStatusCode(Response::HTTP_CREATED);
    }

    private function validateSortParams(string $sort, string $sortDirection)
    {
        if (!in_array($sort, Candidate::SORT_FIELDS)) {
            throw new BadRequestException(
                'Sort param is invalid. Allowed fields are: ' . implode(',', Candidate::SORT_FIELDS)
            );
        }
        if (!in_array(strtoupper($sortDirection), ['ASC', 'DESC'])) {
            throw new BadRequestException(
                'Sort direction param is error. Allowed directions are: ' . implode(',', ['ASC', 'DESC'])
            );
        }
    }

    private function assembleResponseModel(Candidate $candidate): array
    {
        return [
            'data' => [
                'id' => $candidate->getId(),
                'full_name' => $candidate->getFirstName() . ' ' . $candidate->getLastName(),
                'first_name' => $candidate->getFirstName(),
                'last_name' => $candidate->getLastName(),
                'email' => $candidate->getEmail(),
                'phone' => $candidate->getPhone(),
                'salary' => $candidate->getSalary(),
                'position' => $candidate->getPosition(),
                'level' => $candidate->getLevel(),
                'created_at' => $candidate->getCreatedAt(),
                'updated_at' => $candidate->getUpdatedAt(),
            ],

            '_links' => [
                'self' => [
                    'href' => $this->generateUrl('read_resume', ['candidateId' => $candidate->getId()])
                ]
            ]

        ];
    }

    /**
     * @param Candidate[] $candidates
     * @return array
     */
    private function assembleCollectionResponseModel(array $candidates): array
    {
        $responseModels = [];
        foreach ($candidates as $candidate) {
            $responseModels[] = $this->assembleResponseModel($candidate);
        }

        return [
            'data' => $responseModels,
            'total' => count($responseModels),
        ];
    }

}
