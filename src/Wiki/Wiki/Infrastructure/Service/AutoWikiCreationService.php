<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Service;

use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyParams;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyRequest;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupParams;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupRequest;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongParams;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongRequest;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentParams;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki\GeneratedWikiData;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\AutoWikiCreationServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Birthday;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Throwable;

readonly class AutoWikiCreationService implements AutoWikiCreationServiceInterface
{
    public function __construct(
        private GeminiClient $geminiClient,
        private LoggerInterface $logger,
        private WikiRepositoryInterface $wikiRepository,
    ) {
    }

    public function generate(
        AutoWikiCreationPayload $payload,
    ): GeneratedWikiData {
        return match ($payload->resourceType()) {
            ResourceType::AGENCY => $this->generateAgency($payload),
            ResourceType::GROUP => $this->generateGroup($payload),
            ResourceType::TALENT => $this->generateTalent($payload),
            ResourceType::SONG => $this->generateSong($payload),
            default => throw new InvalidArgumentException("Unsupported resource type: {$payload->resourceType()->value}"),
        };
    }

    private function generateAgency(AutoWikiCreationPayload $payload): GeneratedWikiData
    {
        $request = new GenerateAgencyRequest(
            agencyName: (string) $payload->name(),
            language: $payload->language()->value,
        );

        try {
            $response = $this->geminiClient->generateAgency($request);
            $params = $response->params();
        } catch (Throwable $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateAgencyParams::empty();
        }

        $basic = AgencyBasic::fromArray([
            'type' => 'agency',
            'name' => (string) $payload->name(),
            'ceo' => $params->ceoName() ?? '',
            'founded_in' => $params->foundedYear() !== null ? $params->foundedYear() . '-01-01' : null,
        ]);

        return new GeneratedWikiData(
            alphabetName: $params->alphabetName(),
            basic: $basic,
            sections: $this->buildSections($params->description()),
            sources: $params->sources(),
        );
    }

    private function generateGroup(AutoWikiCreationPayload $payload): GeneratedWikiData
    {
        $agencyName = $this->resolveAgencyName($payload->agencyIdentifier());

        $request = new GenerateGroupRequest(
            groupName: (string) $payload->name(),
            language: $payload->language()->value,
            agencyName: $agencyName,
        );

        try {
            $response = $this->geminiClient->generateGroup($request);
            $params = $response->params();
        } catch (Throwable $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateGroupParams::empty();
        }

        $basic = GroupBasic::fromArray([
            'type' => 'group',
            'name' => (string) $payload->name(),
            'agency_identifier' => $payload->agencyIdentifier() !== null ? (string) $payload->agencyIdentifier() : null,
        ]);

        return new GeneratedWikiData(
            alphabetName: $params->alphabetName(),
            basic: $basic,
            sections: $this->buildSections($params->description()),
            sources: $params->sources(),
        );
    }

    private function generateTalent(AutoWikiCreationPayload $payload): GeneratedWikiData
    {
        $agencyName = $this->resolveAgencyName($payload->agencyIdentifier());
        $groupNames = $this->resolveGroupNames($payload->groupIdentifiers());

        $request = new GenerateTalentRequest(
            talentName: (string) $payload->name(),
            language: $payload->language()->value,
            agencyName: $agencyName,
            groupNames: $groupNames,
        );

        try {
            $response = $this->geminiClient->generateTalent($request);
            $params = $response->params();
        } catch (Throwable $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateTalentParams::empty();
        }

        $birthday = null;
        if ($params->birthday() !== null) {
            try {
                $birthday = new Birthday(new DateTimeImmutable($params->birthday()));
            } catch (Throwable) {
                // ignore invalid birthday
            }
        }

        $basic = TalentBasic::fromArray([
            'type' => 'talent',
            'name' => (string) $payload->name(),
            'real_name' => $params->realName() ?? '',
            'birthday' => $birthday,
            'agency_identifier' => $payload->agencyIdentifier() !== null ? (string) $payload->agencyIdentifier() : null,
            'group_identifiers' => array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $payload->groupIdentifiers(),
            ),
        ]);

        return new GeneratedWikiData(
            alphabetName: $params->alphabetName(),
            basic: $basic,
            sections: $this->buildSections($params->description()),
            sources: $params->sources(),
        );
    }

    private function generateSong(AutoWikiCreationPayload $payload): GeneratedWikiData
    {
        $agencyName = $this->resolveAgencyName($payload->agencyIdentifier());

        $groupIdentifiers = $payload->groupIdentifiers();
        $groupName = null;
        if ($groupIdentifiers !== []) {
            $groupNames = $this->resolveGroupNames($groupIdentifiers);
            $groupName = $groupNames[0] ?? null;
        }

        $talentIdentifiers = $payload->talentIdentifiers();
        $talentName = null;
        if ($talentIdentifiers !== []) {
            $talentName = $this->resolveTalentName($talentIdentifiers[0]);
        }

        $request = new GenerateSongRequest(
            songName: (string) $payload->name(),
            language: $payload->language()->value,
            agencyName: $agencyName,
            groupName: $groupName,
            talentName: $talentName,
        );

        try {
            $response = $this->geminiClient->generateSong($request);
            $params = $response->params();
        } catch (Throwable $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateSongParams::empty();
        }

        $basic = SongBasic::fromArray([
            'type' => 'song',
            'name' => (string) $payload->name(),
            'agency_identifier' => $payload->agencyIdentifier() !== null ? (string) $payload->agencyIdentifier() : null,
            'group_identifiers' => array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $payload->groupIdentifiers(),
            ),
            'talent_identifiers' => array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $payload->talentIdentifiers(),
            ),
            'release_date' => $params->releaseDate(),
            'lyricist' => $params->lyricist() ?? '',
            'composer' => $params->composer() ?? '',
        ]);

        return new GeneratedWikiData(
            alphabetName: $params->alphabetName(),
            basic: $basic,
            sections: $this->buildSections($params->overview()),
            sources: $params->sources(),
        );
    }

    private function resolveAgencyName(?WikiIdentifier $agencyIdentifier): ?string
    {
        if ($agencyIdentifier === null) {
            return null;
        }

        $wiki = $this->wikiRepository->findById($agencyIdentifier);
        if ($wiki === null) {
            return null;
        }

        return (string) $wiki->basic()->name();
    }

    /**
     * @param WikiIdentifier[] $groupIdentifiers
     * @return string[]
     */
    private function resolveGroupNames(array $groupIdentifiers): array
    {
        $names = [];
        foreach ($groupIdentifiers as $groupIdentifier) {
            $wiki = $this->wikiRepository->findById($groupIdentifier);
            if ($wiki !== null) {
                $names[] = (string) $wiki->basic()->name();
            }
        }

        return $names;
    }

    private function resolveTalentName(WikiIdentifier $talentIdentifier): ?string
    {
        $wiki = $this->wikiRepository->findById($talentIdentifier);
        if ($wiki === null) {
            return null;
        }

        return (string) $wiki->basic()->name();
    }

    private function buildSections(?string $description): SectionContentCollection
    {
        if ($description === null || $description === '') {
            return new SectionContentCollection();
        }

        return new SectionContentCollection([
            new TextBlock(displayOrder: 0, content: $description),
        ]);
    }
}
