<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UgandaDistrict;
use App\Models\UgandaSubcounty;
use App\Models\UgandaParish;
use App\Models\UgandaVillage;
use Illuminate\Support\Facades\DB;

class TesoRegionLocationsSeeder extends Seeder
{
    /**
     * Seed comprehensive Teso Region data with Districts, Subcounties, Parishes, and Villages
     */
    public function run(): void
    {
        // Skip if Teso region data already exists (from SQL import)
        if (UgandaVillage::count() > 0 || UgandaParish::where('name', 'like', '%Soroti%')->count() > 0) {
            $this->command->info('⚠️  Teso region locations already exist. Skipping seeder.');
            return;
        }

        $this->command->info('Seeding comprehensive Teso Region location data...');

        // Teso Region Districts with complete hierarchy
        $tesoDistricts = [
            // SOROTI DISTRICT - Main Teso district
            [
                'name' => 'Soroti',
                'region' => 'Eastern',
                'subcounties' => [
                    [
                        'name' => 'Soroti City',
                        'type' => 'city',
                        'parishes' => [
                            ['name' => 'Eastern Division', 'villages' => ['Asuret A', 'Asuret B', 'Aloet', 'Opuyo', 'Aminit']],
                            ['name' => 'Western Division', 'villages' => ['Opucet', 'Amen', 'Tubur', 'Atiira', 'Omodoi']],
                            ['name' => 'Central Division', 'villages' => ['Agururu', 'Aloet Central', 'Aminit Central', 'Opuyo Central']],
                        ],
                    ],
                    [
                        'name' => 'Gweri',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Gweri', 'villages' => ['Gweri Central', 'Obutet', 'Otengeya', 'Kadungulu', 'Morungatuny']],
                            ['name' => 'Atiira', 'villages' => ['Atiira', 'Abalang', 'Kamongkoli', 'Opolot', 'Adungosi']],
                            ['name' => 'Kadungulu', 'villages' => ['Kadungulu A', 'Kadungulu B', 'Ongongoja', 'Ococia', 'Obutet']],
                        ],
                    ],
                    [
                        'name' => 'Kamuda',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kamuda', 'villages' => ['Kamuda Central', 'Arapai', 'Magoro', 'Obutet', 'Oditel']],
                            ['name' => 'Arapai', 'villages' => ['Arapai A', 'Arapai B', 'Katine', 'Opuyo', 'Obalanga']],
                            ['name' => 'Katine', 'villages' => ['Katine A', 'Katine B', 'Katine C', 'Ococia', 'Oditel']],
                        ],
                    ],
                    [
                        'name' => 'Tubur',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Tubur', 'villages' => ['Tubur Central', 'Asuret', 'Kateta', 'Orungo', 'Pingire']],
                            ['name' => 'Asuret', 'villages' => ['Asuret A', 'Asuret B', 'Asuret C', 'Omodoi', 'Aminit']],
                            ['name' => 'Kateta', 'villages' => ['Kateta A', 'Kateta B', 'Ococia', 'Oditel', 'Morungatuny']],
                        ],
                    ],
                    [
                        'name' => 'Arapai',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Arapai', 'villages' => ['Arapai Central', 'Obalanga', 'Opuyo', 'Opolot', 'Oditel']],
                            ['name' => 'Obalanga', 'villages' => ['Obalanga A', 'Obalanga B', 'Katine', 'Ococia', 'Morungatuny']],
                        ],
                    ],
                    [
                        'name' => 'Asuret',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Asuret', 'villages' => ['Asuret Central', 'Omodoi', 'Aminit', 'Kateta', 'Tubur']],
                            ['name' => 'Omodoi', 'villages' => ['Omodoi A', 'Omodoi B', 'Omodoi C', 'Ococia', 'Pingire']],
                        ],
                    ],
                ],
            ],

            // KUMI DISTRICT
            [
                'name' => 'Kumi',
                'region' => 'Eastern',
                'subcounties' => [
                    [
                        'name' => 'Kumi Town Council',
                        'type' => 'town council',
                        'parishes' => [
                            ['name' => 'Kumi Central', 'villages' => ['Kumi A', 'Kumi B', 'Kumi C', 'Ongino', 'Nyero']],
                            ['name' => 'Ongino', 'villages' => ['Ongino A', 'Ongino B', 'Kolir', 'Mukongoro', 'Ngora']],
                        ],
                    ],
                    [
                        'name' => 'Kanyum',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kanyum', 'villages' => ['Kanyum Central', 'Ongino', 'Moruita', 'Mukura', 'Nyero']],
                            ['name' => 'Moruita', 'villages' => ['Moruita A', 'Moruita B', 'Ongino', 'Kolir', 'Tisai']],
                            ['name' => 'Mukura', 'villages' => ['Mukura A', 'Mukura B', 'Ongino', 'Nyero', 'Tisai']],
                        ],
                    ],
                    [
                        'name' => 'Kolir',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kolir', 'villages' => ['Kolir Central', 'Ongino', 'Nyero', 'Moruita', 'Tisai']],
                            ['name' => 'Ongino', 'villages' => ['Ongino A', 'Ongino B', 'Ongino C', 'Nyero', 'Kolir']],
                            ['name' => 'Tisai', 'villages' => ['Tisai A', 'Tisai B', 'Moruita', 'Mukura', 'Nyero']],
                        ],
                    ],
                    [
                        'name' => 'Nyero',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Nyero', 'villages' => ['Nyero Central', 'Ongino', 'Kolir', 'Tisai', 'Moruita']],
                            ['name' => 'Kabwangasi', 'villages' => ['Kabwangasi A', 'Kabwangasi B', 'Nyero', 'Ongino']],
                        ],
                    ],
                    [
                        'name' => 'Ongino',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Ongino', 'villages' => ['Ongino Central', 'Nyero', 'Kolir', 'Kanyum', 'Moruita']],
                            ['name' => 'Mukongoro', 'villages' => ['Mukongoro A', 'Mukongoro B', 'Ongino', 'Nyero']],
                        ],
                    ],
                ],
            ],

            // KATAKWI DISTRICT
            [
                'name' => 'Katakwi',
                'region' => 'Eastern',
                'subcounties' => [
                    [
                        'name' => 'Katakwi Town Council',
                        'type' => 'town council',
                        'parishes' => [
                            ['name' => 'Katakwi Central', 'villages' => ['Katakwi A', 'Katakwi B', 'Toroma', 'Magoro', 'Ngariam']],
                            ['name' => 'Toroma', 'villages' => ['Toroma A', 'Toroma B', 'Magoro', 'Ngariam', 'Omodoi']],
                        ],
                    ],
                    [
                        'name' => 'Magoro',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Magoro', 'villages' => ['Magoro Central', 'Toroma', 'Ngariam', 'Omodoi', 'Kapujan']],
                            ['name' => 'Toroma', 'villages' => ['Toroma A', 'Toroma B', 'Toroma C', 'Magoro', 'Ngariam']],
                            ['name' => 'Kapujan', 'villages' => ['Kapujan A', 'Kapujan B', 'Magoro', 'Toroma', 'Omodoi']],
                        ],
                    ],
                    [
                        'name' => 'Ngariam',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Ngariam', 'villages' => ['Ngariam Central', 'Magoro', 'Toroma', 'Omodoi', 'Usuk']],
                            ['name' => 'Usuk', 'villages' => ['Usuk A', 'Usuk B', 'Ngariam', 'Magoro', 'Toroma']],
                            ['name' => 'Iriiri', 'villages' => ['Iriiri A', 'Iriiri B', 'Ngariam', 'Magoro', 'Usuk']],
                        ],
                    ],
                    [
                        'name' => 'Omodoi',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Omodoi', 'villages' => ['Omodoi Central', 'Toroma', 'Magoro', 'Ngariam', 'Kapujan']],
                            ['name' => 'Palam', 'villages' => ['Palam A', 'Palam B', 'Omodoi', 'Toroma', 'Magoro']],
                        ],
                    ],
                    [
                        'name' => 'Toroma',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Toroma', 'villages' => ['Toroma Central', 'Magoro', 'Omodoi', 'Ngariam', 'Kapujan']],
                            ['name' => 'Akoboi', 'villages' => ['Akoboi A', 'Akoboi B', 'Toroma', 'Magoro', 'Omodoi']],
                        ],
                    ],
                ],
            ],

            // AMURIA DISTRICT
            [
                'name' => 'Amuria',
                'region' => 'Eastern',
                'subcounties' => [
                    [
                        'name' => 'Amuria Town Council',
                        'type' => 'town council',
                        'parishes' => [
                            ['name' => 'Amuria Central', 'villages' => ['Amuria A', 'Amuria B', 'Kapelebyong', 'Wera', 'Acowa']],
                            ['name' => 'Kapelebyong', 'villages' => ['Kapelebyong A', 'Kapelebyong B', 'Amuria', 'Wera']],
                        ],
                    ],
                    [
                        'name' => 'Abarikol',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Abarikol', 'villages' => ['Abarikol Central', 'Acowa', 'Wera', 'Kapelebyong', 'Orungo']],
                            ['name' => 'Acowa', 'villages' => ['Acowa A', 'Acowa B', 'Abarikol', 'Wera', 'Orungo']],
                            ['name' => 'Orungo', 'villages' => ['Orungo A', 'Orungo B', 'Abarikol', 'Acowa', 'Wera']],
                        ],
                    ],
                    [
                        'name' => 'Acowa',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Acowa', 'villages' => ['Acowa Central', 'Abarikol', 'Wera', 'Kapelebyong', 'Orungo']],
                            ['name' => 'Kapir', 'villages' => ['Kapir A', 'Kapir B', 'Acowa', 'Abarikol', 'Wera']],
                        ],
                    ],
                    [
                        'name' => 'Kapelebyong',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kapelebyong', 'villages' => ['Kapelebyong Central', 'Amuria', 'Wera', 'Acowa', 'Abarikol']],
                            ['name' => 'Amusia', 'villages' => ['Amusia A', 'Amusia B', 'Kapelebyong', 'Amuria', 'Wera']],
                        ],
                    ],
                    [
                        'name' => 'Wera',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Wera', 'villages' => ['Wera Central', 'Amuria', 'Kapelebyong', 'Acowa', 'Abarikol']],
                            ['name' => 'Orungo', 'villages' => ['Orungo A', 'Orungo B', 'Wera', 'Amuria', 'Kapelebyong']],
                        ],
                    ],
                ],
            ],

            // BUKEDEA DISTRICT
            [
                'name' => 'Bukedea',
                'region' => 'Eastern',
                'subcounties' => [
                    [
                        'name' => 'Bukedea Town Council',
                        'type' => 'town council',
                        'parishes' => [
                            ['name' => 'Bukedea Central', 'villages' => ['Bukedea A', 'Bukedea B', 'Kadanya', 'Kachumbala', 'Kolir']],
                            ['name' => 'Kadanya', 'villages' => ['Kadanya A', 'Kadanya B', 'Bukedea', 'Kachumbala']],
                        ],
                    ],
                    [
                        'name' => 'Kadanya',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kadanya', 'villages' => ['Kadanya Central', 'Bukedea', 'Kachumbala', 'Kabarwa', 'Kolir']],
                            ['name' => 'Kabarwa', 'villages' => ['Kabarwa A', 'Kabarwa B', 'Kadanya', 'Bukedea', 'Kachumbala']],
                            ['name' => 'Kolir', 'villages' => ['Kolir A', 'Kolir B', 'Kadanya', 'Bukedea', 'Kabarwa']],
                        ],
                    ],
                    [
                        'name' => 'Kachumbala',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kachumbala', 'villages' => ['Kachumbala Central', 'Bukedea', 'Kadanya', 'Kabarwa', 'Kolir']],
                            ['name' => 'Kokorio', 'villages' => ['Kokorio A', 'Kokorio B', 'Kachumbala', 'Bukedea', 'Kadanya']],
                        ],
                    ],
                    [
                        'name' => 'Kabarwa',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kabarwa', 'villages' => ['Kabarwa Central', 'Kadanya', 'Bukedea', 'Kachumbala', 'Kolir']],
                            ['name' => 'Malera', 'villages' => ['Malera A', 'Malera B', 'Kabarwa', 'Kadanya', 'Bukedea']],
                        ],
                    ],
                    [
                        'name' => 'Kolir',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kolir', 'villages' => ['Kolir Central', 'Bukedea', 'Kadanya', 'Kabarwa', 'Kachumbala']],
                            ['name' => 'Kidongole', 'villages' => ['Kidongole A', 'Kidongole B', 'Kolir', 'Bukedea', 'Kadanya']],
                        ],
                    ],
                ],
            ],

            // NGORA DISTRICT
            [
                'name' => 'Ngora',
                'region' => 'Eastern',
                'subcounties' => [
                    [
                        'name' => 'Ngora Town Council',
                        'type' => 'town council',
                        'parishes' => [
                            ['name' => 'Ngora Central', 'villages' => ['Ngora A', 'Ngora B', 'Kadungulu', 'Kapir', 'Kobuin']],
                            ['name' => 'Kadungulu', 'villages' => ['Kadungulu A', 'Kadungulu B', 'Ngora', 'Kapir']],
                        ],
                    ],
                    [
                        'name' => 'Kadungulu',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kadungulu', 'villages' => ['Kadungulu Central', 'Ngora', 'Kapir', 'Kobuin', 'Mukura']],
                            ['name' => 'Kapir', 'villages' => ['Kapir A', 'Kapir B', 'Kadungulu', 'Ngora', 'Kobuin']],
                            ['name' => 'Kobuin', 'villages' => ['Kobuin A', 'Kobuin B', 'Kadungulu', 'Ngora', 'Kapir']],
                        ],
                    ],
                    [
                        'name' => 'Kapir',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kapir', 'villages' => ['Kapir Central', 'Ngora', 'Kadungulu', 'Kobuin', 'Mukura']],
                            ['name' => 'Atirir', 'villages' => ['Atirir A', 'Atirir B', 'Kapir', 'Ngora', 'Kadungulu']],
                        ],
                    ],
                    [
                        'name' => 'Kobuin',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kobuin', 'villages' => ['Kobuin Central', 'Ngora', 'Kadungulu', 'Kapir', 'Mukura']],
                            ['name' => 'Dakabela', 'villages' => ['Dakabela A', 'Dakabela B', 'Kobuin', 'Ngora', 'Kadungulu']],
                        ],
                    ],
                    [
                        'name' => 'Mukura',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Mukura', 'villages' => ['Mukura Central', 'Ngora', 'Kadungulu', 'Kapir', 'Kobuin']],
                            ['name' => 'Atirir', 'villages' => ['Atirir A', 'Atirir B', 'Mukura', 'Ngora', 'Kadungulu']],
                        ],
                    ],
                ],
            ],

            // SERERE DISTRICT
            [
                'name' => 'Serere',
                'region' => 'Eastern',
                'subcounties' => [
                    [
                        'name' => 'Serere Town Council',
                        'type' => 'town council',
                        'parishes' => [
                            ['name' => 'Serere Central', 'villages' => ['Serere A', 'Serere B', 'Kadungulu', 'Kyere', 'Olio']],
                            ['name' => 'Kadungulu', 'villages' => ['Kadungulu A', 'Kadungulu B', 'Serere', 'Kyere']],
                        ],
                    ],
                    [
                        'name' => 'Kadungulu',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kadungulu', 'villages' => ['Kadungulu Central', 'Serere', 'Kyere', 'Olio', 'Pingire']],
                            ['name' => 'Kyere', 'villages' => ['Kyere A', 'Kyere B', 'Kadungulu', 'Serere', 'Olio']],
                            ['name' => 'Olio', 'villages' => ['Olio A', 'Olio B', 'Kadungulu', 'Serere', 'Kyere']],
                        ],
                    ],
                    [
                        'name' => 'Kyere',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kyere', 'villages' => ['Kyere Central', 'Serere', 'Kadungulu', 'Olio', 'Pingire']],
                            ['name' => 'Bugondo', 'villages' => ['Bugondo A', 'Bugondo B', 'Kyere', 'Serere', 'Kadungulu']],
                        ],
                    ],
                    [
                        'name' => 'Olio',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Olio', 'villages' => ['Olio Central', 'Serere', 'Kadungulu', 'Kyere', 'Pingire']],
                            ['name' => 'Kasilo', 'villages' => ['Kasilo A', 'Kasilo B', 'Olio', 'Serere', 'Kadungulu']],
                        ],
                    ],
                    [
                        'name' => 'Pingire',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Pingire', 'villages' => ['Pingire Central', 'Serere', 'Kadungulu', 'Kyere', 'Olio']],
                            ['name' => 'Kateta', 'villages' => ['Kateta A', 'Kateta B', 'Pingire', 'Serere', 'Kadungulu']],
                        ],
                    ],
                ],
            ],

            // KALAKI DISTRICT (New - carved from Kaberamaido)
            [
                'name' => 'Kalaki',
                'region' => 'Eastern',
                'subcounties' => [
                    [
                        'name' => 'Kalaki Town Council',
                        'type' => 'town council',
                        'parishes' => [
                            ['name' => 'Kalaki Central', 'villages' => ['Kalaki A', 'Kalaki B', 'Ngariam', 'Okile', 'Apapai']],
                            ['name' => 'Ngariam', 'villages' => ['Ngariam A', 'Ngariam B', 'Kalaki', 'Okile']],
                        ],
                    ],
                    [
                        'name' => 'Ngariam',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Ngariam', 'villages' => ['Ngariam Central', 'Kalaki', 'Okile', 'Apapai', 'Toroma']],
                            ['name' => 'Okile', 'villages' => ['Okile A', 'Okile B', 'Ngariam', 'Kalaki', 'Apapai']],
                        ],
                    ],
                    [
                        'name' => 'Apapai',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Apapai', 'villages' => ['Apapai Central', 'Kalaki', 'Ngariam', 'Okile', 'Toroma']],
                            ['name' => 'Toroma', 'villages' => ['Toroma A', 'Toroma B', 'Apapai', 'Kalaki', 'Ngariam']],
                        ],
                    ],
                ],
            ],

            // KABERAMAIDO DISTRICT
            [
                'name' => 'Kaberamaido',
                'region' => 'Eastern',
                'subcounties' => [
                    [
                        'name' => 'Kaberamaido Town Council',
                        'type' => 'town council',
                        'parishes' => [
                            ['name' => 'Kaberamaido Central', 'villages' => ['Kaberamaido A', 'Kaberamaido B', 'Ochero', 'Kobulubulu']],
                            ['name' => 'Ochero', 'villages' => ['Ochero A', 'Ochero B', 'Kaberamaido', 'Kobulubulu']],
                        ],
                    ],
                    [
                        'name' => 'Ochero',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Ochero', 'villages' => ['Ochero Central', 'Kaberamaido', 'Kobulubulu', 'Alwa', 'Ojimai']],
                            ['name' => 'Kobulubulu', 'villages' => ['Kobulubulu A', 'Kobulubulu B', 'Ochero', 'Kaberamaido']],
                            ['name' => 'Alwa', 'villages' => ['Alwa A', 'Alwa B', 'Ochero', 'Kobulubulu', 'Kaberamaido']],
                        ],
                    ],
                    [
                        'name' => 'Kobulubulu',
                        'type' => 'subcounty',
                        'parishes' => [
                            ['name' => 'Kobulubulu', 'villages' => ['Kobulubulu Central', 'Ochero', 'Kaberamaido', 'Alwa', 'Ojimai']],
                            ['name' => 'Ojimai', 'villages' => ['Ojimai A', 'Ojimai B', 'Kobulubulu', 'Ochero', 'Kaberamaido']],
                        ],
                    ],
                ],
            ],
        ];

        // Insert all Teso region data
        foreach ($tesoDistricts as $districtData) {
            $this->command->info("Processing {$districtData['name']} District...");
            
            $district = UgandaDistrict::updateOrCreate(
                ['name' => $districtData['name']],
                ['region' => $districtData['region']]
            );

            foreach ($districtData['subcounties'] as $subcountyData) {
                $subcounty = UgandaSubcounty::updateOrCreate(
                    [
                        'district_id' => $district->id,
                        'name' => $subcountyData['name']
                    ],
                    [
                        'type' => $subcountyData['type']
                    ]
                );

                foreach ($subcountyData['parishes'] as $parishData) {
                    $parish = UgandaParish::updateOrCreate(
                        [
                            'subcounty_id' => $subcounty->id,
                            'name' => $parishData['name']
                        ]
                    );

                    // Insert villages
                    foreach ($parishData['villages'] as $villageName) {
                        UgandaVillage::updateOrCreate(
                            [
                                'parish_id' => $parish->id,
                                'name' => $villageName
                            ]
                        );
                    }
                }
            }
        }

        $this->command->info('Teso Region data seeded successfully!');
        $this->command->table(
            ['Type', 'Count'],
            [
                ['Teso Districts', UgandaDistrict::where('region', 'Eastern')->whereIn('name', array_column($tesoDistricts, 'name'))->count()],
                ['Total Subcounties', UgandaSubcounty::count()],
                ['Total Parishes', UgandaParish::count()],
                ['Total Villages', UgandaVillage::count()],
            ]
        );
    }
}
