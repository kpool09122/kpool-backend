<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport\GetAppRegionalDailySummaryReportInput;
use Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport\GetAppRegionalDailySummaryReportInterface;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Application\UseCase\Query\RegionalReportReadModel;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiRegionalDailySummaryReport\GetWikiRegionalDailySummaryReportInput;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiRegionalDailySummaryReport\GetWikiRegionalDailySummaryReportInterface;
use Source\Shared\Domain\ValueObject\CountryCode;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateAnalyzePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class RegionalAnalysisReportAuthorizationTest extends TestCase
{
    /** @return iterable<string, array{bool}> */
    public static function contexts(): iterable
    {
        yield 'App' => [false];
        yield 'Wiki' => [true];
    }

    #[DataProvider('contexts')]
    public function testQueryRejectsCallersWithoutAnAnalyzePrincipal(bool $wiki): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->report($wiki, new PrincipalIdentifier(StrTestHelper::generateUuid()));
    }

    /** @return iterable<string, array{bool, string}> */
    public static function revokedAccess(): iterable
    {
        foreach (self::contexts() as $context => [$wiki]) {
            foreach (['suspended', 'forced_stop', 'missing_grant', 'explicit_deny', 'deleted', 'foreign_group', 'foreign_role', 'foreign_policy'] as $scenario) {
                yield $context . ': ' . $scenario => [$wiki, $scenario];
            }
        }
    }

    #[DataProvider('revokedAccess')]
    public function testQueryRechecksCurrentPermissionsOnEveryRequest(bool $wiki, string $scenario): void
    {
        $principal = CreateAnalyzePrincipal::create();
        $identifier = $principal->principalIdentifier();
        $before = $this->report($wiki, $identifier)->toArray();
        self::assertSame($before, $this->report($wiki, $identifier)->toArray());
        switch ($scenario) {
            case 'suspended':
            case 'forced_stop':
                DB::table('analyze_principals')->where('id', (string) $identifier)->update(['status' => $scenario]);

                break;
            case 'deleted':
                DB::table('analyze_principals')->where('id', (string) $identifier)->delete();

                break;
            case 'missing_grant':
                DB::table('analyze_principal_group_memberships')->where('principal_id', (string) $identifier)->delete();

                break;
            case 'explicit_deny':
                $deny = StrTestHelper::generateUuid();
                DB::table('analyze_policies')->insert(['id' => $deny, 'name' => 'Deny', 'statements' => json_encode([['effect' => 'deny', 'actions' => ['analysis:view'], 'resource_types' => ['analysis']]], JSON_THROW_ON_ERROR)]);
                DB::table('analyze_role_policy_attachments')->insert(['role_id' => '08370000-0000-7000-8000-000000000002', 'policy_id' => $deny]);

                break;
            default:
                $otherAccount = StrTestHelper::generateUuid();
                CreateAccount::create($otherAccount);
                $table = match ($scenario) {
                    'foreign_group' => 'analyze_principal_groups',
                    'foreign_role' => 'analyze_roles',
                    'foreign_policy' => 'analyze_policies',
                    default => throw new LogicException('Unknown access scenario.'),
                };
                $query = DB::table($table);
                if ($scenario === 'foreign_group') {
                    $query->where('account_id', (string) $principal->accountIdentifier());
                }
                $query->update(['account_id' => $otherAccount]);
        }
        $this->expectException(UnauthorizedException::class);
        $this->report($wiki, $identifier);
    }

    #[DataProvider('contexts')]
    public function testAnUnenrolledPrincipalCannotReadAnotherCallersReport(bool $wiki): void
    {
        $principal = CreateAnalyzePrincipal::create();
        $this->report($wiki, $principal->principalIdentifier());
        $this->expectException(UnauthorizedException::class);
        $this->report($wiki, new PrincipalIdentifier(StrTestHelper::generateUuid()));
    }

    #[DataProvider('contexts')]
    public function testAuthorizedPrincipalsCanReadTheSameReport(bool $wiki): void
    {
        $first = CreateAnalyzePrincipal::create();
        $second = CreateAnalyzePrincipal::create();
        $expected = $this->report($wiki, $first->principalIdentifier())->toArray();
        self::assertSame($expected, $this->report($wiki, $second->principalIdentifier())->toArray());
    }

    private function report(bool $wiki, PrincipalIdentifier $principalIdentifier): RegionalReportReadModel
    {
        $day = new UtcDate('2020-01-01');
        $period = new ReportingPeriod($day, $day, AnalyzeAppMetrics::now());
        if ($wiki) {
            return $this->app()->make(GetWikiRegionalDailySummaryReportInterface::class)->process(new GetWikiRegionalDailySummaryReportInput($principalIdentifier, $period, new RegionalSelection(CountryCode::JAPAN)));
        }

        return $this->app()->make(GetAppRegionalDailySummaryReportInterface::class)->process(new GetAppRegionalDailySummaryReportInput($principalIdentifier, $period, new RegionalSelection(CountryCode::JAPAN)));
    }
}
