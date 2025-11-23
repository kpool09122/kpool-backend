Param(
    [string]$Filter,
    [switch]$KeepDb
)

$phpunitCommand = "./vendor/bin/phpunit"
if ($Filter) {
    $phpunitCommand += " --filter=$Filter"
}
$phpunitCommand += " --coverage-html coverage-html"

$dockerArgs = @("run", "--rm", "php", "bash", "-c", $phpunitCommand)

& docker-compose @dockerArgs
$exitCode = $LASTEXITCODE

if (-not $KeepDb.IsPresent) {
    try {
        docker-compose stop testing_db | Out-Null
    } catch {
        # ignore cleanup failures
    }

    try {
        docker-compose rm -v -f testing_db | Out-Null
    } catch {
        # ignore cleanup failures
    }
}

exit $exitCode

