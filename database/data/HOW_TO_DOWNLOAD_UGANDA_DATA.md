# Uganda Locations - Overpass API Query Guide

## Issue Found
Your current `export.geojson` file is empty (only 246 bytes, 0 features).

## How to Download Complete Uganda Location Data

### Option 1: Using Overpass Turbo (Recommended)

1. **Go to**: https://overpass-turbo.eu/

2. **Paste this query** to get ALL Uganda administrative boundaries:

```overpass
[out:json][timeout:300];
// Get all administrative boundaries in Uganda
area["ISO3166-1"="UG"]->.uganda;
(
  // Districts (admin_level 4)
  relation["admin_level"="4"]["boundary"="administrative"](area.uganda);
  // Counties (admin_level 5)  
  relation["admin_level"="5"]["boundary"="administrative"](area.uganda);
  // Subcounties (admin_level 6)
  relation["admin_level"="6"]["boundary"="administrative"](area.uganda);
  // Parishes (admin_level 7)
  relation["admin_level"="7"]["boundary"="administrative"](area.uganda);
  // Sub-parishes (admin_level 8)
  relation["admin_level"="8"]["boundary"="administrative"](area.uganda);
  // Villages (admin_level 9)
  relation["admin_level"="9"]["boundary"="administrative"](area.uganda);
);
out tags;
```

3. **Click "Run"** (top left)

4. **Export**: Click "Export" → "download/copy as raw OSM data" → Save as `export.geojson`

5. **Place file** in `database/data/export.geojson`

### Option 2: Direct API Call

Run this command (requires curl):

```bash
curl -X POST https://overpass-api.de/api/interpreter -d @- > database/data/export.geojson << 'EOF'
[out:json][timeout:300];
area["ISO3166-1"="UG"]->.uganda;
(
  relation["admin_level"~"^(4|5|6|7|8|9)$"]["boundary"="administrative"](area.uganda);
);
out tags;
EOF
```

### Option 3: Simplified Query (Faster)

If the above is too large, get just Districts, Counties, and Subcounties:

```overpass
[out:json][timeout:180];
area["ISO3166-1"="UG"]->.uganda;
(
  relation["admin_level"="4"]["boundary"="administrative"](area.uganda);
  relation["admin_level"="6"]["boundary"="administrative"](area.uganda);
  relation["admin_level"="7"]["boundary"="administrative"](area.uganda);
);
out tags;
```

## Expected File Size

A complete Uganda administrative data file should be:
- **File size**: 2-10 MB (depending on detail level)
- **Districts**: ~135 districts
- **Subcounties**: 1,000+ subcounties
- **Parishes**: 5,000+ parishes

## After Download

Once you have the correct file, run:

```bash
php artisan locations:import-osm --fresh
```

This will import all the real Uganda location data into your database!

## Current Workaround

Until you download the complete data, the system is using the **baseline seeder data** I created with:
- 20 major districts
- 88 subcounties  
- 334 parishes

This covers all regions of Uganda and is functional for now. The assessment form dropdowns are already working with this data!
