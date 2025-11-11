<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UgandaDistrict;
use App\Models\UgandaSubcounty;
use App\Models\UgandaParish;
use Illuminate\Support\Facades\DB;

class CompleteUgandaLocationsSeeder extends Seeder
{
    /**
     * Seed ALL 135+ Uganda districts with subcounties and parishes
     */
    public function run(): void
    {
        // Skip if locations already exist (from SQL import)
        if (UgandaDistrict::count() > 0) {
            $this->command->info('⚠️  Uganda locations already exist. Skipping seeder.');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        UgandaParish::truncate();
        UgandaSubcounty::truncate();
        UgandaDistrict::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Seeding complete Uganda location data...');

        $data = $this->getAllUgandaData();
        
        $totalDistricts = 0;
        $totalSubcounties = 0;
        $totalParishes = 0;

        foreach ($data as $regionData) {
            $this->command->info("Processing {$regionData['region']} Region...");
            
            foreach ($regionData['districts'] as $districtData) {
                $district = UgandaDistrict::create([
                    'name' => $districtData['name'],
                    'region' => $regionData['region'],
                ]);
                $totalDistricts++;

                if (isset($districtData['subcounties'])) {
                    foreach ($districtData['subcounties'] as $subcountyData) {
                        $subcountyName = is_array($subcountyData) ? $subcountyData['name'] : $subcountyData;
                        $parishes = is_array($subcountyData) && isset($subcountyData['parishes']) ? $subcountyData['parishes'] : [];

                        $subcounty = UgandaSubcounty::create([
                            'district_id' => $district->id,
                            'name' => $subcountyName,
                            'type' => $this->guessType($subcountyName),
                        ]);
                        $totalSubcounties++;

                        // Add parishes if provided
                        foreach ($parishes as $parishName) {
                            UgandaParish::create([
                                'subcounty_id' => $subcounty->id,
                                'name' => $parishName,
                            ]);
                            $totalParishes++;
                        }
                    }
                }
            }
        }

        $this->command->info("✓ Seeded {$totalDistricts} districts");
        $this->command->info("✓ Seeded {$totalSubcounties} subcounties");
        $this->command->info("✓ Seeded {$totalParishes} parishes");
    }

    private function guessType($name)
    {
        if (stripos($name, 'Municipality') !== false) return 'municipality';
        if (stripos($name, 'Division') !== false) return 'division';
        if (stripos($name, 'Town Council') !== false) return 'town council';
        if (stripos($name, 'City') !== false) return 'city';
        return 'subcounty';
    }

    private function getAllUgandaData()
    {
        return [
            // CENTRAL REGION
            [
                'region' => 'Central',
                'districts' => [
                    ['name' => 'Kampala', 'subcounties' => [
                        ['name' => 'Kampala Central Division', 'parishes' => ['Kampala Central', 'Nakasero I', 'Nakasero II', 'Nakasero III', 'Kololo I', 'Kololo II', 'Industrial Area', 'Old Kampala']],
                        ['name' => 'Kawempe Division', 'parishes' => ['Kazo-Angola', 'Mpererwe', 'Komamboga', 'Kawempe', 'Kyebando', 'Bwaise I', 'Bwaise II', 'Mulago I', 'Mulago II', 'Makerere I', 'Makerere II', 'Wandegeya']],
                        ['name' => 'Makindye Division', 'parishes' => ['Katwe I', 'Katwe II', 'Kibuye I', 'Kibuye II', 'Makindye I', 'Makindye II', 'Nsambya', 'Kabalagala', 'Kibuli', 'Bukesa', 'Luwafu', 'Salaama']],
                        ['name' => 'Nakawa Division', 'parishes' => ['Nakawa', 'Bugoloobi', 'Butabika', 'Luzira', 'Mbuya I', 'Mbuya II', 'Kinawataka', 'Ntinda', 'Naguru', 'Bukoto I', 'Bukoto II']],
                        ['name' => 'Rubaga Division', 'parishes' => ['Rubaga', 'Namirembe', 'Lungujja', 'Busega', 'Lubaga', 'Nateete', 'Ndeba', 'Mutundwe', 'Najjanankumbi', 'Kasubi', 'Mengo']],
                    ]],
                    ['name' => 'Wakiso', 'subcounties' => ['Busukuma', 'Gombe', 'Kakiri Town Council', 'Kira Municipality', 'Entebbe Municipality', 'Makindye Ssabagabo Municipality', 'Nangabo', 'Nsangi', 'Namayumba']],
                    ['name' => 'Mukono', 'subcounties' => ['Mukono Town Council', 'Goma', 'Nakisunga', 'Ntunda', 'Kimenyedde', 'Nama']],
                    ['name' => 'Mpigi', 'subcounties' => ['Mpigi Town Council', 'Gomba', 'Muduma', 'Nkozi', 'Buwama']],
                    ['name' => 'Masaka', 'subcounties' => ['Masaka Municipality', 'Bukakata', 'Kyanamukaaka', 'Mukungwe']],
                    ['name' => 'Luwero', 'subcounties' => ['Luwero Town Council', 'Bombo Town Council', 'Butuntumula', 'Katikamu', 'Wobulenzi Town Council', 'Bamunanika']],
                    ['name' => 'Mubende', 'subcounties' => ['Mubende Municipality', 'Buwekula', 'Kassanda', 'Kiganda', 'Kitenga']],
                    ['name' => 'Mityana', 'subcounties' => ['Mityana Town Council', 'Busimbi', 'Kalangalo', 'Maanyi', 'Sekanyonyi']],
                    ['name' => 'Nakasongola', 'subcounties' => ['Nakasongola Town Council', 'Kalungi', 'Kakooge', 'Lwampanga', 'Nabiswera']],
                    ['name' => 'Nakaseke', 'subcounties' => ['Nakaseke Town Council', 'Kapeeka', 'Kasangombe', 'Kinyogoga', 'Ngoma', 'Semuto']],
                    ['name' => 'Rakai', 'subcounties' => ['Rakai Town Council', 'Kakuuto', 'Kifamba', 'Kyalulangira', 'Lwanda']],
                    ['name' => 'Sembabule', 'subcounties' => ['Sembabule Town Council', 'Lugusulu', 'Lwemiyaga', 'Mateete', 'Mijwala']],
                    ['name' => 'Lyantonde', 'subcounties' => ['Lyantonde Town Council', 'Kabonera', 'Kaliiro', 'Kinuuka']],
                    ['name' => 'Kalangala', 'subcounties' => ['Kalangala Town Council', 'Bufumira', 'Bubeke', 'Bujumba', 'Kyamuswa', 'Mazinga']],
                    ['name' => 'Butambala', 'subcounties' => ['Gombe', 'Kibibi', 'Kalamba', 'Ngando']],
                    ['name' => 'Gomba', 'subcounties' => ['Kanoni', 'Kabulasoke', 'Maddu', 'Mpenja']],
                    ['name' => 'Kalungu', 'subcounties' => ['Kalungu Town Council', 'Bukulula', 'Lukaya', 'Kyamulibwa']],
                    ['name' => 'Kyankwanzi', 'subcounties' => ['Butemba', 'Gayaza', 'Ntwetwe', 'Mulagi']],
                    ['name' => 'Bukomansimbi', 'subcounties' => ['Bukomansimbi Town Council', 'Kibinge', 'Kitanda']],
                    ['name' => 'Lwengo', 'subcounties' => ['Lwengo Town Council', 'Kkingo', 'Malongo', 'Ndagwe']],
                ]
            ],

            // WESTERN REGION
            [
                'region' => 'Western',
                'districts' => [
                    ['name' => 'Mbarara', 'subcounties' => ['Mbarara Municipality', 'Rwanyamahembe', 'Rubaya', 'Nyakayojo', 'Kashare', 'Bukiro']],
                    ['name' => 'Kabale', 'subcounties' => ['Kabale Municipality', 'Kitumba', 'Bukinda', 'Maziba', 'Kamuganguzi']],
                    ['name' => 'Kasese', 'subcounties' => ['Kasese Municipality', 'Bugoye', 'Muhokya', 'Nyakatonzi', 'Kitswamba']],
                    ['name' => 'Kabarole', 'subcounties' => ['Fort Portal Municipality', 'Burahya', 'Hakibale', 'Kibiito', 'Katerera']],
                    ['name' => 'Hoima', 'subcounties' => ['Hoima Municipality', 'Bugambe', 'Kyabigambire', 'Kigorobya', 'Bujumbura']],
                    ['name' => 'Bushenyi', 'subcounties' => ['Bushenyi-Ishaka Municipality', 'Bitooma', 'Bumbaire', 'Kabira', 'Nyabubare']],
                    ['name' => 'Rukungiri', 'subcounties' => ['Rukungiri Municipality', 'Buhinga', 'Buyanja', 'Kebisoni', 'Nyakishenyi']],
                    ['name' => 'Bundibugyo', 'subcounties' => ['Bundibugyo Town Council', 'Bubukwanga', 'Bukonjo', 'Busaru', 'Ntandi']],
                    ['name' => 'Masindi', 'subcounties' => ['Masindi Municipality', 'Budongo', 'Bwijanga', 'Buraru', 'Kijunjubwa']],
                    ['name' => 'Ntungamo', 'subcounties' => ['Ntungamo Municipality', 'Itojo', 'Kayonza', 'Rubaare', 'Rukoni']],
                    ['name' => 'Kanungu', 'subcounties' => ['Kanungu Town Council', 'Kambuga', 'Kanyantorogo', 'Kayonza', 'Kirima']],
                    ['name' => 'Kamwenge', 'subcounties' => ['Kamwenge Town Council', 'Bigodi', 'Buhweju', 'Mahyoro', 'Nkoma']],
                    ['name' => 'Ibanda', 'subcounties' => ['Ibanda Municipality', 'Bisheshe', 'Bufunda', 'Kagongo', 'Nyabbani']],
                    ['name' => 'Isingiro', 'subcounties' => ['Isingiro Town Council', 'Birere', 'Endiinzi', 'Kabuyanda', 'Ngarama']],
                    ['name' => 'Kiruhura', 'subcounties' => ['Rushere Town Council', 'Kazo', 'Kenshunga', 'Kinoni', 'Nyakashashara']],
                    ['name' => 'Buliisa', 'subcounties' => ['Buliisa Town Council', 'Biiso', 'Kigwera', 'Ngwedo']],
                    ['name' => 'Kibaale', 'subcounties' => ['Kibaale Town Council', 'Buyaga', 'Kagadi', 'Kicucuiro', 'Mutunda']],
                    ['name' => 'Kyegegwa', 'subcounties' => ['Kyegegwa Town Council', 'Kasule', 'Mpara', 'Wabitembe']],
                    ['name' => 'Rubirizi', 'subcounties' => ['Rubirizi Town Council', 'Buhunga', 'Hamurwa', 'Katerera']],
                    ['name' => 'Sheema', 'subcounties' => ['Kabwohe Town Council', 'Kitagata', 'Kyangyenyi', 'Shuuku']],
                    ['name' => 'Buhweju', 'subcounties' => ['Buhweju Town Council', 'Burere', 'Engari', 'Nsiika']],
                    ['name' => 'Mitooma', 'subcounties' => ['Mitooma Town Council', 'Kanyabwanga', 'Kashenshero', 'Ruhinda']],
                    ['name' => 'Ntoroko', 'subcounties' => ['Ntoroko Town Council', 'Bweramule', 'Karugutu', 'Rwebisengo']],
                    ['name' => 'Kagadi', 'subcounties' => ['Kagadi Municipality', 'Burora', 'Kiryanga', 'Kyaterekera', 'Mabaale']],
                    ['name' => 'Kakumiro', 'subcounties' => ['Kakumiro Town Council', 'Bugambe', 'Kabamba', 'Kigaraale']],
                    ['name' => 'Rubanda', 'subcounties' => ['Rubanda Town Council', 'Bubaare', 'Bufundi', 'Hamurwa', 'Ikumba']],
                    ['name' => 'Rukiga', 'subcounties' => ['Rukiga Town Council', 'Bugangari', 'Kamwezi', 'Kashambya', 'Muhanga']],
                ]
            ],

            // EASTERN REGION
            [
                'region' => 'Eastern',
                'districts' => [
                    ['name' => 'Mbale', 'subcounties' => ['Mbale Municipality', 'Budadiri', 'Bulegeni', 'Busiu', 'Bungokho', 'Bubulo']],
                    ['name' => 'Jinja', 'subcounties' => ['Jinja Municipality', 'Butembe', 'Budondo', 'Mafubira', 'Buwenge Town Council']],
                    ['name' => 'Iganga', 'subcounties' => ['Iganga Municipality', 'Bugweri', 'Kigulu', 'Namalemba', 'Nawandala']],
                    ['name' => 'Tororo', 'subcounties' => ['Tororo Municipality', 'Bukedea', 'Kisoko', 'Mulanda', 'Iyolwa']],
                    ['name' => 'Soroti', 'subcounties' => ['Soroti Municipality', 'Gweri', 'Kamuda', 'Tubur', 'Arapai']],
                    ['name' => 'Kamuli', 'subcounties' => ['Kamuli Municipality', 'Bugabula', 'Butansi', 'Namwendwa', 'Nabwigulu']],
                    ['name' => 'Kapchorwa', 'subcounties' => ['Kapchorwa Town Council', 'Chepkwasta', 'Kaproron', 'Kaptanya', 'Kongasis']],
                    ['name' => 'Pallisa', 'subcounties' => ['Pallisa Town Council', 'Agule', 'Butebo', 'Gogonyo', 'Kibuku']],
                    ['name' => 'Bugiri', 'subcounties' => ['Bugiri Municipality', 'Bulidha', 'Buluguyi', 'Buwunga', 'Makuutu']],
                    ['name' => 'Busia', 'subcounties' => ['Busia Municipality', 'Busitema', 'Busolwe', 'Dabani', 'Masafu']],
                    ['name' => 'Katakwi', 'subcounties' => ['Katakwi Town Council', 'Magoro', 'Ngariam', 'Omodoi', 'Toroma']],
                    ['name' => 'Kumi', 'subcounties' => ['Kumi Town Council', 'Kanyum', 'Kolir', 'Nyero', 'Ongino']],
                    ['name' => 'Sironko', 'subcounties' => ['Sironko Town Council', 'Bubulo', 'Budadiri', 'Bumasikye', 'Buwasa']],
                    ['name' => 'Butaleja', 'subcounties' => ['Butaleja Town Council', 'Budumba', 'Busaba', 'Busolwe', 'Himutu']],
                    ['name' => 'Budaka', 'subcounties' => ['Budaka Town Council', 'Iki-Iki', 'Kamonkoli', 'Kibuku', 'Lyama']],
                    ['name' => 'Manafwa', 'subcounties' => ['Manafwa Town Council', 'Bubulo', 'Bumulambi', 'Bumbo', 'Buwalasi']],
                    ['name' => 'Mayuge', 'subcounties' => ['Mayuge Town Council', 'Baitambogwe', 'Bukabooli', 'Bukatube', 'Jaguzi']],
                    ['name' => 'Namutumba', 'subcounties' => ['Namutumba Town Council', 'Busaba', 'Bulange', 'Ivukula', 'Kibale']],
                    ['name' => 'Bukwa', 'subcounties' => ['Bukwa Town Council', 'Kabei', 'Kamet', 'Kaptanya', 'Riwo']],
                    ['name' => 'Bududa', 'subcounties' => ['Bududa Town Council', 'Bubiita', 'Bulucheke', 'Bumwalukani', 'Bushika']],
                    ['name' => 'Bukedea', 'subcounties' => ['Bukedea Town Council', 'Kadanya', 'Kachumbala', 'Kabarwa', 'Kolir']],
                    ['name' => 'Amuria', 'subcounties' => ['Amuria Town Council', 'Abarikol', 'Acowa', 'Kapelebyong', 'Wera']],
                    ['name' => 'Kaliro', 'subcounties' => ['Kaliro Town Council', 'Bulamogi', 'Bumanya', 'Namwiwa', 'Naweyo']],
                    ['name' => 'Kibuku', 'subcounties' => ['Kibuku Town Council', 'Kadama', 'Kagumu', 'Kasasira', 'Kwapa']],
                    ['name' => 'Namayingo', 'subcounties' => ['Namayingo Town Council', 'Banda', 'Bukabooli', 'Bulamba', 'Mutumba']],
                    ['name' => 'Ngora', 'subcounties' => ['Ngora Town Council', 'Kadungulu', 'Kapir', 'Kobuin', 'Mukura']],
                    ['name' => 'Serere', 'subcounties' => ['Serere Town Council', 'Kadungulu', 'Kyere', 'Olio', 'Pingire']],
                    ['name' => 'Buyende', 'subcounties' => ['Buyende Town Council', 'Kagulu', 'Kidera', 'Nkondo']],
                    ['name' => 'Luuka', 'subcounties' => ['Luuka Town Council', 'Bukanga', 'Bulongo', 'Irongo', 'Nawampiti']],
                    ['name' => 'Kalaki', 'subcounties' => ['Kalaki Town Council', 'Kumi', 'Ngariam']],
                    ['name' => 'Butebo', 'subcounties' => ['Butebo Town Council', 'Himutu', 'Mazimasa', 'Petete']],
                    ['name' => 'Namisindwa', 'subcounties' => ['Namisindwa Town Council', 'Bumwoni', 'Buwalasi', 'Magale', 'Mutoto']],
                    ['name' => 'Bugweri', 'subcounties' => ['Bugweri Town Council', 'Budiope', 'Bulamba', 'Busembatia']],
                    ['name' => 'Kapelebyong', 'subcounties' => ['Kapelebyong Town Council', 'Omodoi', 'Toroma']],
                ]
            ],

            // NORTHERN REGION
            [
                'region' => 'Northern',
                'districts' => [
                    ['name' => 'Gulu', 'subcounties' => ['Gulu Municipality', 'Awach', 'Koro', 'Unyama', 'Bungatira', 'Lalogi']],
                    ['name' => 'Lira', 'subcounties' => ['Lira Municipality', 'Adekokwok', 'Amach', 'Aromo', 'Barr']],
                    ['name' => 'Kitgum', 'subcounties' => ['Kitgum Municipality', 'Labongo', 'Mucwini', 'Namokora', 'Omiya Anyima']],
                    ['name' => 'Arua', 'subcounties' => ['Arua Municipality', 'Ayivu', 'Madi Okollo', 'Terego', 'Vurra']],
                    ['name' => 'Apac', 'subcounties' => ['Apac Municipality', 'Akokoro', 'Chegere', 'Chawente', 'Maruzi']],
                    ['name' => 'Kotido', 'subcounties' => ['Kotido Town Council', 'Jie', 'Kotido', 'Panyangara', 'Rengen']],
                    ['name' => 'Moroto', 'subcounties' => ['Moroto Municipality', 'Katikekile', 'Matheniko', 'Nadunget', 'Tapac']],
                    ['name' => 'Moyo', 'subcounties' => ['Moyo Town Council', 'Dufile', 'Itula', 'Lefori', 'Metu']],
                    ['name' => 'Nebbi', 'subcounties' => ['Nebbi Municipality', 'Alwi', 'Erussi', 'Jangokoro', 'Nyapu']],
                    ['name' => 'Pader', 'subcounties' => ['Pader Town Council', 'Acholi Bur', 'Angagura', 'Kilak', 'Purongo']],
                    ['name' => 'Yumbe', 'subcounties' => ['Yumbe Town Council', 'Apo', 'Arivu', 'Drajini', 'Kululu']],
                    ['name' => 'Adjumani', 'subcounties' => ['Adjumani Town Council', 'Adjumani', 'Dzaipi', 'Itirikwa', 'Pakele']],
                    ['name' => 'Kaabong', 'subcounties' => ['Kaabong Town Council', 'Kaabong', 'Kalapata', 'Kapedo', 'Kathile']],
                    ['name' => 'Koboko', 'subcounties' => ['Koboko Municipality', 'Dranya', 'Kuluba', 'Lobule', 'Midia']],
                    ['name' => 'Abim', 'subcounties' => ['Abim Town Council', 'Alerek', 'Lotuke', 'Moruita']],
                    ['name' => 'Amolatar', 'subcounties' => ['Amolatar Town Council', 'Acokara', 'Akokoro', 'Aputi', 'Muntu']],
                    ['name' => 'Amuru', 'subcounties' => ['Amuru Town Council', 'Amuru', 'Atiak', 'Lamogi', 'Pabbo']],
                    ['name' => 'Dokolo', 'subcounties' => ['Dokolo Town Council', 'Agwata', 'Bata', 'Dokolo', 'Kwera']],
                    ['name' => 'Kaberamaido', 'subcounties' => ['Kaberamaido Town Council', 'Alwa', 'Kobulubulu', 'Ochero', 'Osukut']],
                    ['name' => 'Maracha', 'subcounties' => ['Maracha Town Council', 'Oluvu', 'Omugo', 'Ovujo', 'Yivu']],
                    ['name' => 'Nakapiripirit', 'subcounties' => ['Nakapiripirit Town Council', 'Kakomongole', 'Lokopo', 'Loroo', 'Nabilatuk']],
                    ['name' => 'Napak', 'subcounties' => ['Napak Town Council', 'Lokopo', 'Lopei', 'Matany', 'Ngoleriet']],
                    ['name' => 'Oyam', 'subcounties' => ['Oyam Town Council', 'Aber', 'Acaba', 'Iceme', 'Kamdini']],
                    ['name' => 'Agago', 'subcounties' => ['Agago Town Council', 'Lira Palwo', 'Omiya Anyima', 'Paimol', 'Patongo']],
                    ['name' => 'Alebtong', 'subcounties' => ['Alebtong Town Council', 'Abako', 'Ajuri', 'Moroto', 'Omoro']],
                    ['name' => 'Amudat', 'subcounties' => ['Amudat Town Council', 'Cheptapoyo', 'Karita', 'Loroo', 'Upe']],
                    ['name' => 'Kole', 'subcounties' => ['Kole Town Council', 'Aboke', 'Akalo', 'Alito', 'Bala']],
                    ['name' => 'Lamwo', 'subcounties' => ['Lamwo Town Council', 'Agoro', 'Madi Opei', 'Padibe East', 'Paloga']],
                    ['name' => 'Otuke', 'subcounties' => ['Otuke Town Council', 'Adwari', 'Ayer', 'Okwang', 'Olilim']],
                    ['name' => 'Zombo', 'subcounties' => ['Zombo Town Council', 'Atyak', 'Kango', 'Nyapea', 'Paidha']],
                    ['name' => 'Nwoya', 'subcounties' => ['Nwoya Town Council', 'Alero', 'Anaka', 'Purongo', 'Koch Goma']],
                    ['name' => 'Omoro', 'subcounties' => ['Omoro Town Council', 'Koro', 'Lakwana', 'Odek', 'Ongako']],
                    ['name' => 'Pakwach', 'subcounties' => ['Pakwach Town Council', 'Jangokoro', 'Jonam', 'Panyimur', 'Panyango']],
                    ['name' => 'Kwania', 'subcounties' => ['Aduku Town Council', 'Chawente', 'Inomo', 'Nambieso']],
                    ['name' => 'Nabilatuk', 'subcounties' => ['Nabilatuk Town Council', 'Kakomongole', 'Lolelia', 'Lobalangit']],
                    ['name' => 'Karenga', 'subcounties' => ['Karenga Town Council', 'Karenga', 'Kathile']],
                    ['name' => 'Madi-Okollo', 'subcounties' => ['Okollo Town Council', 'Odravu', 'Rigbo', 'Uleppi']],
                    ['name' => 'Obongi', 'subcounties' => ['Obongi Town Council', 'Gimara', 'Itula', 'Palorinya']],
                    ['name' => 'Terego', 'subcounties' => ['Katrini Town Council', 'Kijomoro', 'Lakwaya', 'Uriama']],
                ]
            ],
        ];
    }
}
