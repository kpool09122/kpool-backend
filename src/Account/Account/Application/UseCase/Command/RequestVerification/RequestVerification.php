<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestVerification;

use DateTimeImmutable;
use Source\Account\Account\Application\Exception\AccountVerificationAlreadyRequestedException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Exception\InvalidAccountCategoryForVerificationException;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Domain\Entity\VerificationDocument;
use Source\Account\Account\Domain\Factory\AccountVerificationFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountVerificationRepositoryInterface;
use Source\Account\Account\Domain\Service\DocumentRequirementValidatorInterface;
use Source\Account\Account\Domain\ValueObject\DocumentIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Throwable;

readonly class RequestVerification implements RequestVerificationInterface
{
    public function __construct(
        private AccountVerificationRepositoryInterface $verificationRepository,
        private AccountVerificationFactoryInterface $verificationFactory,
        private AccountRepositoryInterface $accountRepository,
        private DocumentStorageServiceInterface $storageService,
        private DocumentRequirementValidatorInterface $documentRequirementValidator,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    /**
     * @param RequestVerificationInputPort $input
     * @param RequestVerificationOutputPort $output
     * @return void
     * @throws DocumentStorageFailedException
     */
    public function process(RequestVerificationInputPort $input, RequestVerificationOutputPort $output): void
    {
        // Check if account is GENERAL
        $account = $this->accountRepository->findById($input->accountIdentifier());

        if ($account === null || ! $account->accountCategory()->isGeneral()) {
            throw new InvalidAccountCategoryForVerificationException();
        }

        // Check if there's already a pending verification
        if ($this->verificationRepository->existsPending($input->accountIdentifier())) {
            throw new AccountVerificationAlreadyRequestedException();
        }

        // Validate document requirements
        $documentTypes = array_map(
            static fn (DocumentData $doc) => $doc->documentType,
            $input->documents(),
        );
        $this->documentRequirementValidator->validate($input->verificationType(), $documentTypes);

        // Create new verification
        $verification = $this->verificationFactory->create(
            $input->accountIdentifier(),
            $input->verificationType(),
            $input->applicantInfo(),
        );

        // Store files and create document entities
        $storedPaths = [];

        try {
            foreach ($input->documents() as $documentData) {
                $documentPath = $this->storageService->store(
                    $verification->verificationIdentifier(),
                    $documentData->fileName,
                    $documentData->fileContents,
                );
                $storedPaths[] = $documentPath;

                $document = new VerificationDocument(
                    new DocumentIdentifier($this->uuidGenerator->generate()),
                    $verification->verificationIdentifier(),
                    $documentData->documentType,
                    $documentPath,
                    $documentData->fileName,
                    $documentData->fileSizeBytes,
                    new DateTimeImmutable(),
                );

                $verification->addDocument($document);
            }
        } catch (Throwable $e) {
            // Cleanup stored files on failure
            foreach ($storedPaths as $path) {
                $this->storageService->delete($path);
            }

            throw new DocumentStorageFailedException(
                message: 'Failed to store verification documents: ' . $e->getMessage(),
                previous: $e,
            );
        }

        $this->verificationRepository->save($verification);

        $output->setVerification($verification);
    }
}
