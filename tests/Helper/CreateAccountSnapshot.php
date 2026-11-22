<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotInput;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotInterface;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotOutput;
use Source\Account\Account\Domain\Entity\AccountSnapshot;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class CreateAccountSnapshot
{
    public static function account(): AccountIdentifier
    {
        $id = StrTestHelper::generateUuid();
        CreateAccount::create($id);
        DB::table('accounts')->where('id', $id)->update(['phone' => '+33123456789','address_country_code' => 'FR','address_postal_code' => '75001','address_line1' => 'Paris']);
        foreach (['passport','selfie'] as $type) {
            $path = $id.'/'.$type.'.pdf';
            Storage::disk('verification-documents')->put($path, $type.' bytes');
            DB::table('account_documents')->insert(['account_id' => $id,'document_type' => $type,'document_path' => $path,'uploaded_at' => '2026-01-01']);
        }

        return new AccountIdentifier($id);
    }

    public static function principal(AccountIdentifier $id): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $id);
    }

    /** @throws DocumentStorageFailedException */
    public static function save(AccountIdentifier $id): AccountSnapshot
    {
        app(SaveAccountSnapshotInterface::class)->process(new SaveAccountSnapshotInput($id, self::principal($id)), new SaveAccountSnapshotOutput());

        return app(AccountSnapshotRepositoryInterface::class)->findCurrent($id) ?? throw new LogicException('Snapshot not saved.');
    }
}
