<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Infrastructure\Service;

use InvalidArgumentException;
use Source\Analyze\Shared\Application\Service\RegionNormalizerInterface;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

/** GA4 region is a localized geographic name, not an ISO code. Ambiguity fails closed. */
readonly class RegionNormalizer implements RegionNormalizerInterface
{
    /** GA4 English spellings aligned to our existing display codes. */
    private const array ALIASES = [
        'abra' => 'PH-ABR',
        'agusandelnorte' => 'PH-AGN',
        'agusandelsur' => 'PH-AGS',
        'aklan' => 'PH-AKL',
        'albay' => 'PH-ALB',
        'anhui' => 'CN-34',
        'antique' => 'PH-ANT',
        'apayao' => 'PH-APA',
        'aurora' => 'PH-AUR',
        'bangsamoroautonomousregioninmuslimmindanao' => 'PH-14',
        'basilan' => 'PH-BAS',
        'bataan' => 'PH-BAN',
        'batanes' => 'PH-BTN',
        'batangas' => 'PH-BTG',
        'beijing' => 'CN-11',
        'benguet' => 'PH-BEN',
        'bicol' => 'PH-05',
        'biliran' => 'PH-BIL',
        'bohol' => 'PH-BOH',
        'bukidnon' => 'PH-BUK',
        'bulacan' => 'PH-BUL',
        'busan' => 'KR-26',
        'cagayan' => 'PH-CAG',
        'cagayanvalley' => 'PH-02',
        'calabarzon' => 'PH-40',
        'camarinesnorte' => 'PH-CAN',
        'camarinessur' => 'PH-CAS',
        'camiguin' => 'PH-CAM',
        'capiz' => 'PH-CAP',
        'caraga' => 'PH-13',
        'catanduanes' => 'PH-CAT',
        'cavite' => 'PH-CAV',
        'cebu' => 'PH-CEB',
        'centralluzon' => 'PH-03',
        'centralvisayas' => 'PH-07',
        'chiayicity' => 'TW-CYI',
        'chiayicounty' => 'TW-CYQ',
        'chongqing' => 'CN-50',
        'chungcheongbuk' => 'KR-43',
        'chungcheongnam' => 'KR-44',
        'cordilleraadministrativeregion' => 'PH-15',
        'cotabato' => 'PH-NCO',
        'daegu' => 'KR-27',
        'daejeon' => 'KR-30',
        'davao' => 'PH-11',
        'davaodelnorte' => 'PH-DAV',
        'davaodelsur' => 'PH-DAS',
        'davaodeoro' => 'PH-COM',
        'davaooccidental' => 'PH-DVO',
        'davaooriental' => 'PH-DAO',
        'dinagatislands' => 'PH-DIN',
        'easternsamar' => 'PH-EAS',
        'easternvisayas' => 'PH-08',
        'fujian' => 'CN-35',
        'gangwon' => 'KR-42',
        'gangwondo' => 'KR-42',
        'gangwonstate' => 'KR-42',
        'gansu' => 'CN-62',
        'guangdong' => 'CN-44',
        'guangxi' => 'CN-45',
        'guimaras' => 'PH-GUI',
        'guizhou' => 'CN-52',
        'gwangju' => 'KR-29',
        'gyeonggi' => 'KR-41',
        'gyeonggiprovince' => 'KR-41',
        'gyeongsangbuk' => 'KR-47',
        'gyeongsangnam' => 'KR-48',
        'hainan' => 'CN-46',
        'hebei' => 'CN-13',
        'heilongjiang' => 'CN-23',
        'henan' => 'CN-41',
        'hongkong' => 'CN-91',
        'hsinchucity' => 'TW-HSZ',
        'hsinchucounty' => 'TW-HSQ',
        'hubei' => 'CN-42',
        'hunan' => 'CN-43',
        'ifugao' => 'PH-IFU',
        'ilocos' => 'PH-01',
        'ilocosnorte' => 'PH-ILN',
        'ilocossur' => 'PH-ILS',
        'iloilo' => 'PH-ILI',
        'incheon' => 'KR-28',
        'innermongolia' => 'CN-15',
        'isabela' => 'PH-ISA',
        'jeju' => 'KR-49',
        'jejudo' => 'KR-49',
        'jejuisland' => 'KR-49',
        'jeollabuk' => 'KR-45',
        'jeollabukdo' => 'KR-45',
        'jeollanam' => 'KR-46',
        'jiangsu' => 'CN-32',
        'jiangxi' => 'CN-36',
        'jilin' => 'CN-22',
        'kalinga' => 'PH-KAL',
        'kaohsiungcity' => 'TW-KHH',
        'laguna' => 'PH-LAG',
        'lanaodelnorte' => 'PH-LAN',
        'lanaodelsur' => 'PH-LAS',
        'launion' => 'PH-LUN',
        'leyte' => 'PH-LEY',
        'liaoning' => 'CN-21',
        'macao' => 'CN-92',
        'macau' => 'CN-92',
        'maguindanaodelnorte' => 'PH-MGN',
        'maguindanaodelsur' => 'PH-MGS',
        'marinduque' => 'PH-MAD',
        'masbate' => 'PH-MAS',
        'metromanila' => 'PH-00',
        'mimaropa' => 'PH-41',
        'misamisoccidental' => 'PH-MSC',
        'misamisoriental' => 'PH-MSR',
        'mountainprovince' => 'PH-MOU',
        'nationalcapitalregion' => 'PH-00',
        'negrosoccidental' => 'PH-NEC',
        'negrosoriental' => 'PH-NER',
        'newtaipeicity' => 'TW-NWT',
        'ningxia' => 'CN-64',
        'northchungcheongprovince' => 'KR-43',
        'northernmindanao' => 'PH-10',
        'northernsamar' => 'PH-NSA',
        'northgyeongsangprovince' => 'KR-47',
        'northjeollaprovince' => 'KR-45',
        'nuevaecija' => 'PH-NUE',
        'nuevavizcaya' => 'PH-NUV',
        'occidentalmindoro' => 'PH-MDC',
        'orientalmindoro' => 'PH-MDR',
        'palawan' => 'PH-PLW',
        'pampanga' => 'PH-PAM',
        'pangasinan' => 'PH-PAN',
        'qinghai' => 'CN-63',
        'quezon' => 'PH-QUE',
        'quirino' => 'PH-QUI',
        'rizal' => 'PH-RIZ',
        'romblon' => 'PH-ROM',
        'samar' => 'PH-WSA',
        'sarangani' => 'PH-SAR',
        'sejong' => 'KR-50',
        'seoul' => 'KR-11',
        'shaanxi' => 'CN-61',
        'shandong' => 'CN-37',
        'shanghai' => 'CN-31',
        'shanxi' => 'CN-14',
        'sichuan' => 'CN-51',
        'siquijor' => 'PH-SIG',
        'soccsksargen' => 'PH-12',
        'sorsogon' => 'PH-SOR',
        'southchungcheongprovince' => 'KR-44',
        'southcotabato' => 'PH-SCO',
        'southernleyte' => 'PH-SLE',
        'southgyeongsangprovince' => 'KR-48',
        'southjeollaprovince' => 'KR-46',
        'sultankudarat' => 'PH-SUK',
        'sulu' => 'PH-SLU',
        'surigaodelnorte' => 'PH-SUN',
        'surigaodelsur' => 'PH-SUR',
        'taichungcity' => 'TW-TXG',
        'tainancity' => 'TW-TNN',
        'taipeicity' => 'TW-TPE',
        'taiwan' => 'CN-71',
        'taoyuancity' => 'TW-TAO',
        'tarlac' => 'PH-TAR',
        'tawitawi' => 'PH-TAW',
        'tianjin' => 'CN-12',
        'tibet' => 'CN-54',
        'ulsan' => 'KR-31',
        'westernvisayas' => 'PH-06',
        'xinjiang' => 'CN-65',
        'xizang' => 'CN-54',
        'yunnan' => 'CN-53',
        'zambales' => 'PH-ZMB',
        'zamboangadelnorte' => 'PH-ZAN',
        'zamboangadelsur' => 'PH-ZAS',
        'zamboangapeninsula' => 'PH-09',
        'zamboangasibugay' => 'PH-ZSI',
        'zhejiang' => 'CN-33',
    ];

    /** @var array<string, list<string>> */
    private array $codesByName;

    public function __construct()
    {
        $index = [];
        foreach (SubdivisionCode::NAMES as $code => $label) {
            $country = explode('-', $code, 2)[0];
            $index[$country . ':' . $this->key($label)][] = $code;
        }
        $this->codesByName = $index;
    }

    public function normalize(CountryCode $country, string $name): ?SubdivisionCode
    {
        if (in_array($name, ['', '(not set)'], true)) {
            return null;
        }
        if (str_starts_with($name, $country->value . '-')) {
            try {
                return new SubdivisionCode($country, $name);
            } catch (InvalidArgumentException) {
                return null;
            }
        }
        $normalized = $this->key($name);
        $alias = self::ALIASES[$normalized] ?? null;
        if ($alias !== null && str_starts_with($alias, $country->value . '-')) {
            return new SubdivisionCode($country, $alias);
        }
        $matches = $this->codesByName[$country->value . ':' . $normalized] ?? [];

        return count($matches) === 1 ? new SubdivisionCode($country, $matches[0]) : null;
    }

    private function key(string $name): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $name);

        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ascii === false ? $name : $ascii) ?? '');
    }
}
