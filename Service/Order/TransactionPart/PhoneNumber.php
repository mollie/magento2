<?php

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Model\Methods\In3;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class PhoneNumber implements TransactionPartInterface
{
    public const COUNTRY_CODE_MAPPING = [
        'AC' => '247', // Ascension Island
        'AD' => '376', // Andorra
        'AE' => '971', // United Arab Emirates
        'AF' => '93',  // Afghanistan
        'AG' => '1',   // Antigua and Barbuda
        'AI' => '1',   // Anguilla
        'AL' => '355', // Albania
        'AM' => '374', // Armenia
        'AO' => '244', // Angola
        'AQ' => '672', // Antarctica
        'AR' => '54',  // Argentina
        'AS' => '1',   // American Samoa
        'AT' => '43',  // Austria
        'AU' => '61',  // Australia
        'AW' => '297', // Aruba
        'AX' => '358', // Åland Islands
        'AZ' => '994', // Azerbaijan
        'BA' => '387', // Bosnia and Herzegovina
        'BB' => '1',   // Barbados
        'BD' => '880', // Bangladesh
        'BE' => '32',  // Belgium
        'BF' => '226', // Burkina Faso
        'BG' => '359', // Bulgaria
        'BH' => '973', // Bahrain
        'BI' => '257', // Burundi
        'BJ' => '229', // Benin
        'BL' => '590', // Saint Barthélemy
        'BM' => '1',   // Bermuda
        'BN' => '673', // Brunei Darussalam
        'BO' => '591', // Bolivia
        'BQ' => '599', // Caribbean Netherlands
        'BR' => '55',  // Brazil
        'BS' => '1',   // Bahamas
        'BT' => '975', // Bhutan
        'BW' => '267', // Botswana
        'BY' => '375', // Belarus
        'BZ' => '501', // Belize
        'CA' => '1',   // Canada
        'CC' => '61',  // Cocos (Keeling) Islands
        'CD' => '243', // Democratic Republic of the Congo
        'CF' => '236', // Central African Republic
        'CG' => '242', // Republic of the Congo
        'CH' => '41',  // Switzerland
        'CI' => '225', // Côte d'Ivoire
        'CK' => '682', // Cook Islands
        'CL' => '56',  // Chile
        'CM' => '237', // Cameroon
        'CN' => '86',  // China
        'CO' => '57',  // Colombia
        'CR' => '506', // Costa Rica
        'CU' => '53',  // Cuba
        'CV' => '238', // Cape Verde
        'CW' => '599', // Curaçao
        'CX' => '61',  // Christmas Island
        'CY' => '357', // Cyprus
        'CZ' => '420', // Czech Republic
        'DE' => '49',  // Germany
        'DJ' => '253', // Djibouti
        'DK' => '45',  // Denmark
        'DM' => '1',   // Dominica
        'DO' => '1',   // Dominican Republic
        'DZ' => '213', // Algeria
        'EC' => '593', // Ecuador
        'EE' => '372', // Estonia
        'EG' => '20',  // Egypt
        'EH' => '212', // Western Sahara
        'ER' => '291', // Eritrea
        'ES' => '34',  // Spain
        'ET' => '251', // Ethiopia
        'FI' => '358', // Finland
        'FJ' => '679', // Fiji
        'FK' => '500', // Falkland Islands
        'FM' => '691', // Micronesia
        'FO' => '298', // Faroe Islands
        'FR' => '33',  // France
        'GA' => '241', // Gabon
        'GB' => '44',  // United Kingdom
        'GD' => '1',   // Grenada
        'GE' => '995', // Georgia
        'GF' => '594', // French Guiana
        'GG' => '44',  // Guernsey
        'GH' => '233', // Ghana
        'GI' => '350', // Gibraltar
        'GL' => '299', // Greenland
        'GM' => '220', // Gambia
        'GN' => '224', // Guinea
        'GP' => '590', // Guadeloupe
        'GQ' => '240', // Equatorial Guinea
        'GR' => '30',  // Greece
        'GT' => '502', // Guatemala
        'GU' => '1',   // Guam
        'GW' => '245', // Guinea-Bissau
        'GY' => '592', // Guyana
        'HK' => '852', // Hong Kong
        'HN' => '504', // Honduras
        'HR' => '385', // Croatia
        'HT' => '509', // Haiti
        'HU' => '36',  // Hungary
        'ID' => '62',  // Indonesia
        'IE' => '353', // Ireland
        'IL' => '972', // Israel
        'IM' => '44',  // Isle of Man
        'IN' => '91',  // India
        'IO' => '246', // British Indian Ocean Territory
        'IQ' => '964', // Iraq
        'IR' => '98',  // Iran
        'IS' => '354', // Iceland
        'IT' => '39',  // Italy
        'JE' => '44',  // Jersey
        'JM' => '1',   // Jamaica
        'JO' => '962', // Jordan
        'JP' => '81',  // Japan
        'KE' => '254', // Kenya
        'KG' => '996', // Kyrgyzstan
        'KH' => '855', // Cambodia
        'KI' => '686', // Kiribati
        'KM' => '269', // Comoros
        'KN' => '1',   // Saint Kitts and Nevis
        'KP' => '850', // North Korea
        'KR' => '82',  // South Korea
        'KW' => '965', // Kuwait
        'KY' => '1',   // Cayman Islands
        'KZ' => '7',   // Kazakhstan
        'LA' => '856', // Laos
        'LB' => '961', // Lebanon
        'LC' => '1',   // Saint Lucia
        'LI' => '423', // Liechtenstein
        'LK' => '94',  // Sri Lanka
        'LR' => '231', // Liberia
        'LS' => '266', // Lesotho
        'LT' => '370', // Lithuania
        'LU' => '352', // Luxembourg
        'LV' => '371', // Latvia
        'LY' => '218', // Libya
        'MA' => '212', // Morocco
        'MC' => '377', // Monaco
        'MD' => '373', // Moldova
        'ME' => '382', // Montenegro
        'MF' => '590', // Saint Martin
        'MG' => '261', // Madagascar
        'MH' => '692', // Marshall Islands
        'MK' => '389', // North Macedonia
        'ML' => '223', // Mali
        'MM' => '95',  // Myanmar
        'MN' => '976', // Mongolia
        'MO' => '853', // Macau
        'MP' => '1',   // Northern Mariana Islands
        'MQ' => '596', // Martinique
        'MR' => '222', // Mauritania
        'MS' => '1',   // Montserrat
        'MT' => '356', // Malta
        'MU' => '230', // Mauritius
        'MV' => '960', // Maldives
        'MW' => '265', // Malawi
        'MX' => '52',  // Mexico
        'MY' => '60',  // Malaysia
        'MZ' => '258', // Mozambique
        'NA' => '264', // Namibia
        'NC' => '687', // New Caledonia
        'NE' => '227', // Niger
        'NF' => '672', // Norfolk Island
        'NG' => '234', // Nigeria
        'NI' => '505', // Nicaragua
        'NL' => '31',  // Netherlands
        'NO' => '47',  // Norway
        'NP' => '977', // Nepal
        'NR' => '674', // Nauru
        'NU' => '683', // Niue
        'NZ' => '64',  // New Zealand
        'OM' => '968', // Oman
        'PA' => '507', // Panama
        'PE' => '51',  // Peru
        'PF' => '689', // French Polynesia
        'PG' => '675', // Papua New Guinea
        'PH' => '63',  // Philippines
        'PK' => '92',  // Pakistan
        'PL' => '48',  // Poland
        'PM' => '508', // Saint Pierre and Miquelon
        'PN' => '64',  // Pitcairn Islands
        'PR' => '1',   // Puerto Rico
        'PS' => '970', // Palestine
        'PT' => '351', // Portugal
        'PW' => '680', // Palau
        'PY' => '595', // Paraguay
        'QA' => '974', // Qatar
        'RE' => '262', // Réunion
        'RO' => '40',  // Romania
        'RS' => '381', // Serbia
        'RU' => '7',   // Russia
        'RW' => '250', // Rwanda
        'SA' => '966', // Saudi Arabia
        'SB' => '677', // Solomon Islands
        'SC' => '248', // Seychelles
        'SD' => '249', // Sudan
        'SE' => '46',  // Sweden
        'SG' => '65',  // Singapore
        'SH' => '290', // Saint Helena
        'SI' => '386', // Slovenia
        'SJ' => '47',  // Svalbard and Jan Mayen
        'SK' => '421', // Slovakia
        'SL' => '232', // Sierra Leone
        'SM' => '378', // San Marino
        'SN' => '221', // Senegal
        'SO' => '252', // Somalia
        'SR' => '597', // Suriname
        'SS' => '211', // South Sudan
        'ST' => '239', // São Tomé and Príncipe
        'SV' => '503', // El Salvador
        'SX' => '1',   // Sint Maarten
        'SY' => '963', // Syria
        'SZ' => '268', // Eswatini
        'TC' => '1',   // Turks and Caicos Islands
        'TD' => '235', // Chad
        'TF' => '262', // French Southern Territories
        'TG' => '228', // Togo
        'TH' => '66',  // Thailand
        'TJ' => '992', // Tajikistan
        'TK' => '690', // Tokelau
        'TL' => '670', // Timor-Leste
        'TM' => '993', // Turkmenistan
        'TN' => '216', // Tunisia
        'TO' => '676', // Tonga
        'TR' => '90',  // Turkey
        'TT' => '1',   // Trinidad and Tobago
        'TV' => '688', // Tuvalu
        'TW' => '886', // Taiwan
        'TZ' => '255', // Tanzania
        'UA' => '380', // Ukraine
        'UG' => '256', // Uganda
        'US' => '1',   // United States
        'UY' => '598', // Uruguay
        'UZ' => '998', // Uzbekistan
        'VA' => '39',  // Vatican City
        'VC' => '1',   // Saint Vincent and the Grenadines
        'VE' => '58',  // Venezuela
        'VG' => '1',   // British Virgin Islands
        'VI' => '1',   // U.S. Virgin Islands
        'VN' => '84',  // Vietnam
        'VU' => '678', // Vanuatu
        'WF' => '681', // Wallis and Futuna
        'WS' => '685', // Samoa
        'XK' => '383', // Kosovo
        'YE' => '967', // Yemen
        'YT' => '262', // Mayotte
        'ZA' => '27',  // South Africa
        'ZM' => '260', // Zambia
        'ZW' => '263', // Zimbabwe
    ];

    public function process(OrderInterface $order, $apiMethod, array $transaction)
    {
        if ($order->getPayment()->getMethod() != In3::CODE) {
            return $transaction;
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            return $transaction;
        }

        $address = $order->getBillingAddress();
        $countryCode = $address->getCountryId();
        $phoneNumber = $address->getTelephone();

        if (empty($phoneNumber)) {
            return $transaction;
        }

        try {
            $transaction['billingAddress']['phone'] = $this->formatInE164($countryCode, $phoneNumber);
        } catch (\InvalidArgumentException $exception) {
            // Silently ignore the exception
        }

        return $transaction;
    }

    private function formatInE164(string $countryCodeIso2, string $phoneNumber): string
    {
        if (!array_key_exists($countryCodeIso2, self::COUNTRY_CODE_MAPPING)) {
            throw new \InvalidArgumentException(sprintf('Country code "%s" is not supported', $countryCodeIso2));
        }

        $countryCode = self::COUNTRY_CODE_MAPPING[$countryCodeIso2];

        $phoneNumber = preg_replace('/^\+' . $countryCode . '/', '', $phoneNumber);
        $phoneNumber = preg_replace('/^00' . $countryCode . '/', '', $phoneNumber);

        $formattedNumber = preg_replace('/\D/', '', $phoneNumber);

        // Remove the leading zeros from the number
        $formattedNumber = ltrim($formattedNumber, '0');

        // Add the '+' sign and the country code to the beginning of the formatted number
        $formattedNumber = '+' . $countryCode . $formattedNumber;

        if (strlen($formattedNumber) <= 3 || !preg_match('/^\+[1-9]\d{1,14}$/', $formattedNumber)) {
            throw new \InvalidArgumentException(__('Phone number "%s" is not valid', $formattedNumber));
        }

        return $formattedNumber;
    }
}
