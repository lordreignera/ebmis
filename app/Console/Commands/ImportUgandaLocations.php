<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UgandaDistrict;
use App\Models\UgandaSubcounty;
use App\Models\UgandaParish;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportUgandaLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:import-osm {--fresh : Clear existing data before import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Uganda locations from Overpass OSM GeoJSON data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jsonPath = database_path('data/export.geojson');

        if (!File::exists($jsonPath)) {
            $this->error("GeoJSON file not found at: {$jsonPath}");
            return 1;
        }

        $this->info('Reading GeoJSON file...');
        $jsonContent = File::get($jsonPath);
        
        // Clean up malformed JSON (Overpass sometimes adds extra newlines)
        $jsonContent = preg_replace('/\n\s*\n/', "\n", $jsonContent);
        $jsonContent = str_replace('"elements": [' . "\n" . "\n" . '{', '"elements": [{', $jsonContent);
        
        $data = json_decode($jsonContent, true);

        if (!$data) {
            $this->error('Failed to parse JSON. Error: ' . json_last_error_msg());
            return 1;
        }

        if (!isset($data['elements'])) {
            $this->error('No elements found in JSON data');
            $this->info('Available keys: ' . implode(', ', array_keys($data)));
            return 1;
        }

        if ($this->option('fresh')) {
            $this->info('Clearing existing location data...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            UgandaParish::truncate();
            UgandaSubcounty::truncate();
            UgandaDistrict::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info('Processing ' . count($data['elements']) . ' elements...');

        // First pass: Import all districts (admin_level 4)
        $districts = [];
        $subcounties = [];
        $parishes = [];

        $this->info('Pass 1: Extracting districts...');
        $districtBar = $this->output->createProgressBar();

        foreach ($data['elements'] as $element) {
            if ($element['type'] === 'relation' && 
                isset($element['tags']['admin_level']) && 
                $element['tags']['admin_level'] === '4' &&
                isset($element['tags']['name'])) {
                
                $districts[$element['id']] = [
                    'osm_id' => $element['id'],
                    'name' => $element['tags']['name'],
                    'iso_code' => $element['tags']['ISO3166-2'] ?? null,
                    'wikidata' => $element['tags']['wikidata'] ?? null,
                ];
                $districtBar->advance();
            }
        }

        $districtBar->finish();
        $this->newLine();
        $this->info('Found ' . count($districts) . ' districts');

        // Insert districts
        $this->info('Importing districts into database...');
        $districtMap = [];
        
        foreach ($districts as $osmId => $districtData) {
            $district = UgandaDistrict::updateOrCreate(
                ['name' => $districtData['name']],
                [
                    'region' => $this->guessRegion($districtData['name']),
                ]
            );
            $districtMap[$osmId] = $district->id;
        }

        $this->info('Districts imported: ' . count($districtMap));

        // Second pass: Import subcounties (admin_level 5-9)
        $this->info('Pass 2: Extracting subcounties and parishes...');
        $subcountyBar = $this->output->createProgressBar();

        foreach ($data['elements'] as $element) {
            if ($element['type'] === 'relation' && 
                isset($element['tags']['admin_level']) && 
                isset($element['tags']['name'])) {
                
                $adminLevel = (int)$element['tags']['admin_level'];
                
                // Subcounties are typically level 5-7 in Uganda OSM
                if ($adminLevel >= 5 && $adminLevel <= 7) {
                    $subcounties[] = [
                        'osm_id' => $element['id'],
                        'name' => $element['tags']['name'],
                        'admin_level' => $adminLevel,
                        'tags' => $element['tags'],
                    ];
                }
                
                // Parishes are typically level 8-9
                if ($adminLevel >= 8 && $adminLevel <= 9) {
                    $parishes[] = [
                        'osm_id' => $element['id'],
                        'name' => $element['tags']['name'],
                        'admin_level' => $adminLevel,
                        'tags' => $element['tags'],
                    ];
                }
                
                $subcountyBar->advance();
            }
        }

        $subcountyBar->finish();
        $this->newLine();
        $this->info('Found ' . count($subcounties) . ' subcounties');
        $this->info('Found ' . count($parishes) . ' parishes');

        // Import subcounties (we'll link them to districts by name matching)
        $this->info('Importing subcounties...');
        $subcountyMap = [];
        
        foreach ($subcounties as $subcountyData) {
            // Try to find parent district from name (e.g., "Kampala Central Division" -> "Kampala")
            $districtId = $this->findDistrictForLocation($subcountyData['name'], $districtMap, $districts);
            
            if ($districtId) {
                $subcounty = UgandaSubcounty::updateOrCreate(
                    [
                        'district_id' => $districtId,
                        'name' => $subcountyData['name']
                    ],
                    [
                        'type' => $this->guessSubcountyType($subcountyData['name']),
                    ]
                );
                $subcountyMap[$subcountyData['osm_id']] = $subcounty->id;
            }
        }

        $this->info('Subcounties imported: ' . count($subcountyMap));

        // Import parishes
        $this->info('Importing parishes...');
        $parishCount = 0;
        
        foreach ($parishes as $parishData) {
            // Try to find parent subcounty
            $subcountyId = $this->findSubcountyForParish($parishData['name'], $subcountyMap, $subcounties);
            
            if ($subcountyId) {
                UgandaParish::updateOrCreate(
                    [
                        'subcounty_id' => $subcountyId,
                        'name' => $parishData['name']
                    ]
                );
                $parishCount++;
            }
        }

        $this->info('Parishes imported: ' . $parishCount);

        // Display summary
        $this->newLine();
        $this->info('Import completed successfully!');
        $this->table(
            ['Type', 'Count'],
            [
                ['Districts', UgandaDistrict::count()],
                ['Subcounties', UgandaSubcounty::count()],
                ['Parishes', UgandaParish::count()],
            ]
        );

        return 0;
    }

    /**
     * Guess the region for a district
     */
    private function guessRegion($districtName)
    {
        $central = ['Kampala', 'Wakiso', 'Mukono', 'Mpigi', 'Masaka', 'Luwero', 'Mubende', 'Mityana', 
                   'Nakasongola', 'Nakaseke', 'Rakai', 'Sembabule', 'Lyantonde', 'Kalangala', 'Butambala',
                   'Gomba', 'Kalungu', 'Kyankwanzi', 'Bukomansimbi', 'Lwengo'];
        
        $western = ['Mbarara', 'Kabale', 'Kasese', 'Hoima', 'Bushenyi', 'Rukungiri', 'Bundibugyo',
                   'Kabarole', 'Masindi', 'Ntungamo', 'Kanungu', 'Kamwenge', 'Ibanda', 'Isingiro',
                   'Kiruhura', 'Buliisa', 'Kibaale', 'Kyegegwa', 'Rubirizi', 'Sheema', 'Buhweju',
                   'Mitooma', 'Ntoroko', 'Kagadi', 'Kakumiro'];
        
        $eastern = ['Jinja', 'Mbale', 'Iganga', 'Tororo', 'Soroti', 'Kamuli', 'Kapchorwa', 'Pallisa',
                   'Bugiri', 'Busia', 'Katakwi', 'Kumi', 'Sironko', 'Butaleja', 'Budaka', 'Manafwa',
                   'Mayuge', 'Namutumba', 'Bukwa', 'Bududa', 'Bukedea', 'Amuria', 'Kaliro', 'Kibuku',
                   'Namayingo', 'Ngora', 'Serere', 'Buyende', 'Luuka'];
        
        $northern = ['Gulu', 'Lira', 'Kitgum', 'Arua', 'Apac', 'Kotido', 'Moroto', 'Moyo', 'Nebbi',
                    'Pader', 'Yumbe', 'Adjumani', 'Kaabong', 'Koboko', 'Abim', 'Amolatar', 'Amuru',
                    'Dokolo', 'Kaberamaido', 'Maracha', 'Nakapiripirit', 'Napak', 'Oyam', 'Agago',
                    'Alebtong', 'Amudat', 'Kole', 'Lamwo', 'Otuke', 'Zombo', 'Nwoya'];

        if (in_array($districtName, $central)) return 'Central';
        if (in_array($districtName, $western)) return 'Western';
        if (in_array($districtName, $eastern)) return 'Eastern';
        if (in_array($districtName, $northern)) return 'Northern';

        return null;
    }

    /**
     * Guess subcounty type from name
     */
    private function guessSubcountyType($name)
    {
        if (stripos($name, 'Municipality') !== false) return 'municipality';
        if (stripos($name, 'Division') !== false) return 'division';
        if (stripos($name, 'Town Council') !== false) return 'town council';
        return 'subcounty';
    }

    /**
     * Find district ID for a location by name matching
     */
    private function findDistrictForLocation($name, $districtMap, $districts)
    {
        // Direct match
        foreach ($districts as $osmId => $district) {
            if (stripos($name, $district['name']) !== false) {
                return $districtMap[$osmId] ?? null;
            }
        }
        
        // Fallback: assign to first district (will need manual review)
        return $districtMap[array_key_first($districtMap)] ?? null;
    }

    /**
     * Find subcounty ID for a parish
     */
    private function findSubcountyForParish($name, $subcountyMap, $subcounties)
    {
        // Try name matching
        foreach ($subcounties as $subcounty) {
            $subcountyName = str_replace([' Sub-County', ' Division', ' Town Council'], '', $subcounty['name']);
            if (stripos($name, $subcountyName) !== false && isset($subcountyMap[$subcounty['osm_id']])) {
                return $subcountyMap[$subcounty['osm_id']];
            }
        }
        
        // Fallback to first subcounty
        return $subcountyMap[array_key_first($subcountyMap)] ?? null;
    }
}
