/**
 * Uganda Districts, Sub-counties/Counties, Parishes, and Villages
 * Simplified dataset for school registration
 */

const ugandaLocations = {
    // Central Region
    "Kampala": {
        subcounties: {
            "Kawempe Division": ["Kazo Ward", "Kawempe I", "Kawempe II", "Kyebando", "Makerere I", "Makerere II", "Mpererwe", "Nabweru", "Wandegeya"],
            "Makindye Division": ["Bukasa", "Kansanga", "Kabalagala", "Katwe I", "Katwe II", "Kibuye I", "Kibuye II", "Kisugu", "Lukuli", "Makindye", "Nsambya", "Salaama"],
            "Nakawa Division": ["Bugolobi", "Butabika", "Kiswa", "Luzira", "Mbuya I", "Mbuya II", "Nakawa", "Naguru I", "Naguru II", "Ntinda"],
            "Central Division": ["Busega", "City Hall", "Kagugube", "Kololo I", "Kololo II", "Mengo", "Muyenga", "Old Kampala", "Rubaga I", "Rubaga II"],
            "Lubaga Division": ["Busega", "Kawaala", "Lungujja", "Lubaga North", "Lubaga South", "Naakulabye", "Nateete"]
        }
    },
    "Wakiso": {
        subcounties: {
            "Busukuma": ["Bulwanyi", "Busukuma", "Kalagala", "Kiwenda", "Lwamata"],
            "Entebbe": ["Katabi", "Kigungu", "Nakiwogo"],
            "Kakiri": ["Kakiri Town", "Kalagala", "Kitara", "Lwabyata"],
            "Kira": ["Bweyogerere", "Kimwaanyi", "Kireka", "Kyaliwajjala", "Najjera"],
            "Nangabo": ["Banda", "Kasanje", "Kiganda", "Nangabo", "Wakiso"]
        }
    },
    "Mukono": {
        subcounties: {
            "Mukono Town Council": ["Central Ward", "Goma Ward", "Nantabulirwa Ward", "Seeta Ward"],
            "Goma": ["Bulwanyi", "Busaana", "Kasokwe", "Nsube"],
            "Nama": ["Kabembe", "Kasawo", "Katente", "Nama", "Nazigo"]
        }
    },
    
    // Western Region
    "Mbarara": {
        subcounties: {
            "Mbarara Municipality": ["Kamukuzi", "Kakiika", "Nyamitanga", "Kakoba", "Biharwe"],
            "Kashari": ["Bugamba", "Kashare", "Kayonza", "Rwanyamahembe"],
            "Rwampara": ["Kazo", "Kinoni", "Nyakashashara"]
        }
    },
    "Kabale": {
        subcounties: {
            "Kabale Municipality": ["Central", "Northern", "Southern"],
            "Kitumba": ["Bukinda", "Kitumba", "Rwamucucu"],
            "Maziba": ["Karengyere", "Maziba", "Muhanga"]
        }
    },
    
    // Eastern Region
    "Mbale": {
        subcounties: {
            "Mbale Municipality": ["Industrial Division", "Northern Division", "Southern Division", "Wanale Division"],
            "Bududa": ["Bukigai", "Bukibokolo", "Bukalasi", "Bulucheke"],
            "Butaleja": ["Butaleja Town", "Busabi", "Doho", "Mazimasa"]
        }
    },
    "Jinja": {
        subcounties: {
            "Jinja Municipality": ["Central Division", "Mpumudde Division", "Walukuba Division"],
            "Butagaya": ["Butagaya", "Ivukula", "Kimaka"],
            "Kagoma": ["Buwaaya", "Kagoma", "Namwendwa"]
        }
    },
    "Iganga": {
        subcounties: {
            "Iganga Municipality": ["Central Ward", "Nakigo Ward"],
            "Bugweri": ["Bugweri", "Bulamogi", "Ibulangu"],
            "Nabitende": ["Nabitende", "Namungalwe", "Namalemba"]
        }
    },
    
    // Northern Region
    "Gulu": {
        subcounties: {
            "Gulu Municipality": ["Bardege Division", "Laroo Division", "Layibi Division", "Pece Division"],
            "Awach": ["Awach", "Palaro", "Paicho"],
            "Bungatira": ["Bungatira", "Bobi", "Opit"]
        }
    },
    "Lira": {
        subcounties: {
            "Lira Municipality": ["Adyel Division", "Ojwina Division", "Railways Division"],
            "Aromo": ["Abako", "Agwata", "Aromo", "Boke"],
            "Barr": ["Aduku", "Barr", "Olilim"]
        }
    },
    
    // Add more districts as needed (this is a subset)
    "Masaka": {
        subcounties: {
            "Masaka Municipality": ["Katwe Division", "Nyendo Division"],
            "Bukoto": ["Bukoto", "Buwunga", "Kyannamukaaka"],
            "Kyesiiga": ["Kayabwe", "Kyesiiga", "Kyalulangira"]
        }
    },
    "Hoima": {
        subcounties: {
            "Hoima Municipality": ["Central Division", "Western Division"],
            "Bugambe": ["Bugambe", "Kyabigambire", "Kitoba"],
            "Buhaguzi": ["Buhaguzi", "Kiziranfumbi", "Kyangwali"]
        }
    }
};

// Export for use in forms
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ugandaLocations;
}
