<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

use InvalidArgumentException;

enum AdministrativeAreaCode: string
{
    case JAPAN_HOKKAIDO = 'JP-01';
    case JAPAN_AOMORI = 'JP-02';
    case JAPAN_IWATE = 'JP-03';
    case JAPAN_MIYAGI = 'JP-04';
    case JAPAN_AKITA = 'JP-05';
    case JAPAN_YAMAGATA = 'JP-06';
    case JAPAN_FUKUSHIMA = 'JP-07';
    case JAPAN_IBARAKI = 'JP-08';
    case JAPAN_TOCHIGI = 'JP-09';
    case JAPAN_GUNMA = 'JP-10';
    case JAPAN_SAITAMA = 'JP-11';
    case JAPAN_CHIBA = 'JP-12';
    case JAPAN_TOKYO = 'JP-13';
    case JAPAN_KANAGAWA = 'JP-14';
    case JAPAN_NIIGATA = 'JP-15';
    case JAPAN_TOYAMA = 'JP-16';
    case JAPAN_ISHIKAWA = 'JP-17';
    case JAPAN_FUKUI = 'JP-18';
    case JAPAN_YAMANASHI = 'JP-19';
    case JAPAN_NAGANO = 'JP-20';
    case JAPAN_GIFU = 'JP-21';
    case JAPAN_SHIZUOKA = 'JP-22';
    case JAPAN_AICHI = 'JP-23';
    case JAPAN_MIE = 'JP-24';
    case JAPAN_SHIGA = 'JP-25';
    case JAPAN_KYOTO = 'JP-26';
    case JAPAN_OSAKA = 'JP-27';
    case JAPAN_HYOGO = 'JP-28';
    case JAPAN_NARA = 'JP-29';
    case JAPAN_WAKAYAMA = 'JP-30';
    case JAPAN_TOTTORI = 'JP-31';
    case JAPAN_SHIMANE = 'JP-32';
    case JAPAN_OKAYAMA = 'JP-33';
    case JAPAN_HIROSHIMA = 'JP-34';
    case JAPAN_YAMAGUCHI = 'JP-35';
    case JAPAN_TOKUSHIMA = 'JP-36';
    case JAPAN_KAGAWA = 'JP-37';
    case JAPAN_EHIME = 'JP-38';
    case JAPAN_KOCHI = 'JP-39';
    case JAPAN_FUKUOKA = 'JP-40';
    case JAPAN_SAGA = 'JP-41';
    case JAPAN_NAGASAKI = 'JP-42';
    case JAPAN_KUMAMOTO = 'JP-43';
    case JAPAN_OITA = 'JP-44';
    case JAPAN_MIYAZAKI = 'JP-45';
    case JAPAN_KAGOSHIMA = 'JP-46';
    case JAPAN_OKINAWA = 'JP-47';

    case KOREA_SEOUL = 'KR-11';
    case KOREA_BUSAN = 'KR-26';
    case KOREA_DAEGU = 'KR-27';
    case KOREA_INCHEON = 'KR-28';
    case KOREA_GWANGJU = 'KR-29';
    case KOREA_DAEJEON = 'KR-30';
    case KOREA_ULSAN = 'KR-31';
    case KOREA_GYEONGGI = 'KR-41';
    case KOREA_GANGWON = 'KR-42';
    case KOREA_CHUNGCHEONGBUK = 'KR-43';
    case KOREA_CHUNGCHEONGNAM = 'KR-44';
    case KOREA_JEOLLABUK = 'KR-45';
    case KOREA_JEOLLANAM = 'KR-46';
    case KOREA_GYEONGSANGBUK = 'KR-47';
    case KOREA_GYEONGSANGNAM = 'KR-48';
    case KOREA_JEJU = 'KR-49';
    case KOREA_SEJONG = 'KR-50';

    case UNITED_STATES_ALABAMA = 'US-AL';
    case UNITED_STATES_ALASKA = 'US-AK';
    case UNITED_STATES_ARIZONA = 'US-AZ';
    case UNITED_STATES_ARKANSAS = 'US-AR';
    case UNITED_STATES_CALIFORNIA = 'US-CA';
    case UNITED_STATES_COLORADO = 'US-CO';
    case UNITED_STATES_CONNECTICUT = 'US-CT';
    case UNITED_STATES_DELAWARE = 'US-DE';
    case UNITED_STATES_DISTRICT_OF_COLUMBIA = 'US-DC';
    case UNITED_STATES_FLORIDA = 'US-FL';
    case UNITED_STATES_GEORGIA = 'US-GA';
    case UNITED_STATES_HAWAII = 'US-HI';
    case UNITED_STATES_IDAHO = 'US-ID';
    case UNITED_STATES_ILLINOIS = 'US-IL';
    case UNITED_STATES_INDIANA = 'US-IN';
    case UNITED_STATES_IOWA = 'US-IA';
    case UNITED_STATES_KANSAS = 'US-KS';
    case UNITED_STATES_KENTUCKY = 'US-KY';
    case UNITED_STATES_LOUISIANA = 'US-LA';
    case UNITED_STATES_MAINE = 'US-ME';
    case UNITED_STATES_MARYLAND = 'US-MD';
    case UNITED_STATES_MASSACHUSETTS = 'US-MA';
    case UNITED_STATES_MICHIGAN = 'US-MI';
    case UNITED_STATES_MINNESOTA = 'US-MN';
    case UNITED_STATES_MISSISSIPPI = 'US-MS';
    case UNITED_STATES_MISSOURI = 'US-MO';
    case UNITED_STATES_MONTANA = 'US-MT';
    case UNITED_STATES_NEBRASKA = 'US-NE';
    case UNITED_STATES_NEVADA = 'US-NV';
    case UNITED_STATES_NEW_HAMPSHIRE = 'US-NH';
    case UNITED_STATES_NEW_JERSEY = 'US-NJ';
    case UNITED_STATES_NEW_MEXICO = 'US-NM';
    case UNITED_STATES_NEW_YORK = 'US-NY';
    case UNITED_STATES_NORTH_CAROLINA = 'US-NC';
    case UNITED_STATES_NORTH_DAKOTA = 'US-ND';
    case UNITED_STATES_OHIO = 'US-OH';
    case UNITED_STATES_OKLAHOMA = 'US-OK';
    case UNITED_STATES_OREGON = 'US-OR';
    case UNITED_STATES_PENNSYLVANIA = 'US-PA';
    case UNITED_STATES_RHODE_ISLAND = 'US-RI';
    case UNITED_STATES_SOUTH_CAROLINA = 'US-SC';
    case UNITED_STATES_SOUTH_DAKOTA = 'US-SD';
    case UNITED_STATES_TENNESSEE = 'US-TN';
    case UNITED_STATES_TEXAS = 'US-TX';
    case UNITED_STATES_UTAH = 'US-UT';
    case UNITED_STATES_VERMONT = 'US-VT';
    case UNITED_STATES_VIRGINIA = 'US-VA';
    case UNITED_STATES_WASHINGTON = 'US-WA';
    case UNITED_STATES_WEST_VIRGINIA = 'US-WV';
    case UNITED_STATES_WISCONSIN = 'US-WI';
    case UNITED_STATES_WYOMING = 'US-WY';

    public function countryCode(): CountryCode
    {
        return CountryCode::from($this->segments()[0]);
    }

    public function code(): string
    {
        $segments = $this->segments();

        return $segments[array_key_last($segments)];
    }

    public function isSupportedBy(CountryCode $countryCode): bool
    {
        return $this->countryCode() === $countryCode;
    }

    public static function tryFromCountryAndCode(CountryCode $countryCode, string $code): ?self
    {
        return self::tryFrom($countryCode->value . '-' . strtoupper(trim($code)));
    }

    public static function fromCountryAndCode(CountryCode $countryCode, string $code): self
    {
        return self::tryFromCountryAndCode($countryCode, $code)
            ?? throw new InvalidArgumentException('Administrative area code is invalid for country code.');
    }

    /** @return non-empty-list<string> */
    private function segments(): array
    {
        return explode('-', $this->value);
    }
}
