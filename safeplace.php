<?php
include 'navbar.php';

// Set language to Bangla
$_SESSION['lang'] = 'bn';

// Bangla text constants
$text = [
    'title' => 'নিরাপদ স্থান অনুসন্ধান',
    'subtitle' => 'রিয়েল-টাইম নেভিগেশন সহ কাছাকাছি নিরাপদ স্থান খুঁজুন',
    'current_location' => 'বর্তমান অবস্থান',
    'search_radius' => 'অনুসন্ধানের ব্যাসার্ধ',
    'radius_500m' => '৫০০ মিটার',
    'radius_1km' => '১ কিলোমিটার',
    'radius_2km' => '২ কিলোমিটার',
    'radius_5km' => '৫ কিলোমিটার',
    'find_safe_places' => 'নিরাপদ স্থান খুঁজুন',
    'safe_places_nearby' => 'কাছাকাছি নিরাপদ স্থান',
    'category_all' => 'সব স্থান',
    'category_police' => 'পুলিশ স্টেশন',
    'category_hospital' => 'হাসপাতাল',
    'category_fire' => 'ফায়ার স্টেশন',
    'category_school' => 'স্কুল/কলেজ',
    'category_mall' => 'শপিং মল',
    'category_restaurant' => 'রেস্তোরা',
    'category_atm' => 'এটিএম/ব্যাংক',
    'category_hotel' => 'হোটেল',
    'distance' => 'দূরত্ব',
    'get_directions' => 'দিকনির্দেশনা',
    'navigate_now' => 'এখনই নেভিগেট',
    'no_location' => 'অবস্থান প্রয়োজন',
    'enable_location' => 'এই সুবিধা ব্যবহার করতে অনুগ্রহ করে অবস্থান অ্যাক্সেস সক্রিয় করুন',
    'loading' => 'লোড হচ্ছে...',
    'select_category' => 'ধরণ নির্বাচন',
    'sort_by' => 'সাজান',
    'sort_distance' => 'দূরত্ব অনুযায়ী',
    'sort_name' => 'নাম অনুযায়ী',
    'current_pos' => 'আপনার অবস্থান',
    'back_to_home' => 'হোমে ফিরুন',
    'emergency_contacts' => 'জরুরি যোগাযোগ',
    'police' => 'পুলিশ',
    'fire' => 'ফায়ার সার্ভিস',
    'ambulance' => 'অ্যাম্বুলেন্স',
    'no_places_found' => 'কোন স্থান পাওয়া যায়নি',
    'try_increasing_radius' => 'অনুসন্ধানের ব্যাসার্ধ বাড়ান বা ধরণ পরিবর্তন করুন',
    'your_location' => 'আপনার অবস্থান',
    'searching' => 'অনুসন্ধান করা হচ্ছে...',
    'found_places' => 'টি স্থান পাওয়া গেছে',
    'address' => 'ঠিকানা',
    'open_now' => 'এখন খোলা',
    'closed' => 'বন্ধ',
    'call' => 'কল করুন',
    'save_place' => 'স্থান সংরক্ষণ',
    'share_location' => 'অবস্থান শেয়ার',
    'report_safety' => 'নিরাপত্তা রিপোর্ট',
    'latitude' => 'অক্ষাংশ',
    'longitude' => 'দ্রাঘিমাংশ',
    'accuracy' => 'নির্ভুলতা',
    'meter' => 'মিটার',
    'kilometer' => 'কিলোমিটার'
];
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>নিরাপদ স্থান অনুসন্ধান - SafeRideBD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://banglawebfonts.pages.dev/css/bornomala.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0a0c0f;
            --bg-secondary: #14181c;
            --bg-card: #1e2429;
            --bg-hover: #2a323a;
            --text-primary: #ffffff;
            --text-secondary: #b0b8c2;
            --text-muted: #8a929c;
            --accent-primary: #ff6b4a;
            --accent-secondary: #4ade80;
            --accent-warning: #fbbf24;
            --accent-danger: #ef4444;
            --border-color: #2e3a44;
            --border-light: #3a4754;
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
            --glow: 0 0 0 2px rgba(255, 107, 74, 0.2);
        }

        body {
            font-family: 'Bornomala', serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .page-header {
            background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px 40px;
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 25px;
        }

        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar Styles */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .card-title {
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title i {
            color: var(--accent-primary);
        }

        /* Location Display */
        .location-display {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 16px;
            border: 1px solid var(--border-color);
        }

        .location-status {
            color: var(--accent-secondary);
            font-weight: 500;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .location-coords {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .coord-item {
            background-color: var(--bg-hover);
            padding: 6px;
            border-radius: 4px;
            text-align: center;
        }

        /* Radius Buttons */
        .radius-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .radius-btn {
            padding: 12px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            font-family: 'Bornomala', serif;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .radius-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        .radius-btn.active {
            background-color: var(--accent-primary);
            border-color: var(--accent-primary);
            color: white;
        }

        /* Category Select */
        .category-select {
            width: 100%;
            padding: 14px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: 'Bornomala', serif;
            font-size: 15px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .category-select:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: var(--glow);
        }

        .category-select option {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }

        /* Search Button */
        .search-btn {
            width: 100%;
            padding: 16px;
            background-color: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .search-btn:hover:not(:disabled) {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.3);
        }

        .search-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Emergency Contacts */
        .emergency-card {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(255, 107, 74, 0.1));
            border: 1px solid var(--accent-danger);
        }

        .contact-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .contact-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background-color: var(--bg-secondary);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .contact-name {
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .contact-number {
            color: var(--accent-danger);
            font-weight: 600;
            font-size: 18px;
        }

        /* Map */
        .map-container {
            background-color: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        #map {
            height: 450px;
            width: 100%;
        }

        /* Places Header */
        .places-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .results-count {
            color: var(--text-secondary);
            font-size: 16px;
            background-color: var(--bg-secondary);
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }

        .sort-select {
            padding: 10px 20px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-family: 'Bornomala', serif;
            font-size: 14px;
            cursor: pointer;
        }

        .sort-select option {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }

        /* Places Grid */
        .places-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            max-height: 600px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .places-grid::-webkit-scrollbar {
            width: 6px;
        }

        .places-grid::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 3px;
        }

        .places-grid::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .places-grid::-webkit-scrollbar-thumb:hover {
            background: var(--accent-primary);
        }

        /* Place Card */
        .place-card {
            background-color: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
            cursor: pointer;
        }

        .place-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .place-card.selected {
            border-color: var(--accent-secondary);
            background-color: rgba(74, 222, 128, 0.1);
        }

        .place-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .place-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .place-name i {
            color: var(--accent-primary);
        }

        .place-badge {
            font-size: 11px;
            padding: 4px 8px;
            background-color: var(--bg-hover);
            border-radius: 4px;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .place-address {
            color: var(--text-muted);
            font-size: 13px;
            margin-bottom: 15px;
            line-height: 1.5;
            padding-left: 24px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .place-address i {
            color: var(--accent-primary);
            margin-top: 2px;
        }

        .place-distance {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--accent-secondary);
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 15px;
            padding-left: 24px;
        }

        .place-distance i {
            color: var(--accent-primary);
        }

        .place-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-family: 'Bornomala', serif;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .directions-btn {
            background-color: var(--accent-primary);
            color: white;
        }

        .directions-btn:hover {
            background-color: #ff5a3a;
        }

        .navigate-btn {
            background-color: var(--accent-secondary);
            color: var(--bg-primary);
        }

        .navigate-btn:hover {
            background-color: #3bcc6c;
        }

        .quick-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .quick-btn {
            flex: 1;
            min-width: 80px;
            padding: 8px 10px;
            background-color: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-secondary);
            font-size: 11px;
            font-family: 'Bornomala', serif;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .quick-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
        }

        .status-open {
            background-color: rgba(74, 222, 128, 0.2);
            color: var(--accent-secondary);
        }

        .status-closed {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--accent-danger);
        }

        .no-places {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background-color: var(--bg-secondary);
            border-radius: 10px;
            border: 1px dashed var(--border-color);
        }

        .no-places i {
            font-size: 48px;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        .no-places h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .no-places p {
            color: var(--text-muted);
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }

        .spinner {
            border: 3px solid var(--border-color);
            border-top: 3px solid var(--accent-primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Leaflet Customization */
        .leaflet-container {
            background-color: var(--bg-secondary) !important;
        }

        .leaflet-control-attribution {
            background-color: var(--bg-card) !important;
            color: var(--text-muted) !important;
            font-size: 10px !important;
        }

        .leaflet-control-attribution a {
            color: var(--accent-primary) !important;
        }

        .leaflet-popup-content-wrapper {
            background-color: var(--bg-card);
            color: var(--text-primary);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-family: 'Bornomala', serif;
        }

        .leaflet-popup-tip {
            background-color: var(--bg-card);
        }

        .leaflet-popup-close-button {
            color: var(--text-muted) !important;
        }

        .leaflet-popup-close-button:hover {
            color: var(--accent-danger) !important;
        }

        .user-marker {
            background-color: var(--accent-primary);
            border: 3px solid white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            box-shadow: 0 0 20px var(--accent-primary);
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 28px;
            }
            
            .location-coords {
                grid-template-columns: 1fr;
            }
            
            .places-grid {
                grid-template-columns: 1fr;
            }
            
            .place-actions {
                flex-direction: column;
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .quick-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1 class="page-title"><?php echo $text['title']; ?></h1>
        <p class="page-subtitle"><?php echo $text['subtitle']; ?></p>
    </div>

    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Location Card -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-location-dot"></i>
                    <?php echo $text['current_location']; ?>
                </div>
                <div class="location-display">
                    <div class="location-status" id="locationStatus">
                        <i class="fas fa-sync-alt fa-spin"></i>
                        <?php echo $text['loading']; ?>
                    </div>
                    <div class="location-coords" id="locationCoords">
                        <div class="coord-item" id="latDisplay"><?php echo $text['latitude']; ?>: --</div>
                        <div class="coord-item" id="lngDisplay"><?php echo $text['longitude']; ?>: --</div>
                        <div class="coord-item" id="accDisplay"><?php echo $text['accuracy']; ?>: --</div>
                    </div>
                </div>
            </div>

            <!-- Search Controls Card -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-sliders-h"></i>
                    <?php echo $text['search_radius']; ?>
                </div>
                
                <div class="radius-grid">
                    <button class="radius-btn active" data-radius="500"><?php echo $text['radius_500m']; ?></button>
                    <button class="radius-btn" data-radius="1000"><?php echo $text['radius_1km']; ?></button>
                    <button class="radius-btn" data-radius="2000"><?php echo $text['radius_2km']; ?></button>
                    <button class="radius-btn" data-radius="5000"><?php echo $text['radius_5km']; ?></button>
                </div>

                <select class="category-select" id="categorySelect">
                    <option value="all"><?php echo $text['category_all']; ?></option>
                    <option value="police"><?php echo $text['category_police']; ?></option>
                    <option value="hospital"><?php echo $text['category_hospital']; ?></option>
                    <option value="fire_station"><?php echo $text['category_fire']; ?></option>
                    <option value="school"><?php echo $text['category_school']; ?></option>
                    <option value="shopping_mall"><?php echo $text['category_mall']; ?></option>
                    <option value="restaurant"><?php echo $text['category_restaurant']; ?></option>
                    <option value="atm"><?php echo $text['category_atm']; ?></option>
                    <option value="hotel"><?php echo $text['category_hotel']; ?></option>
                </select>

                <button class="search-btn" id="searchBtn" disabled>
                    <i class="fas fa-search"></i>
                    <?php echo $text['find_safe_places']; ?>
                </button>
            </div>

            <!-- Emergency Contacts Card -->
            <div class="card emergency-card">
                <div class="card-title" style="color: var(--accent-danger);">
                    <i class="fas fa-phone-alt"></i>
                    <?php echo $text['emergency_contacts']; ?>
                </div>
                <div class="contact-list">
                    <div class="contact-item">
                        <span class="contact-name">
                            <i class="fas fa-shield-alt" style="color: var(--accent-danger);"></i>
                            <?php echo $text['police']; ?>
                        </span>
                        <span class="contact-number">৯৯৯</span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-name">
                            <i class="fas fa-fire-extinguisher" style="color: var(--accent-danger);"></i>
                            <?php echo $text['fire']; ?>
                        </span>
                        <span class="contact-number">১০১</span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-name">
                            <i class="fas fa-ambulance" style="color: var(--accent-danger);"></i>
                            <?php echo $text['ambulance']; ?>
                        </span>
                        <span class="contact-number">১০২</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Map -->
            <div class="map-container">
                <div id="map"></div>
            </div>

            <!-- Places List -->
            <div class="card">
                <div class="places-header">
                    <div class="results-count" id="resultsCount">
                        <?php echo $text['safe_places_nearby']; ?> (০)
                    </div>
                    <select class="sort-select" id="sortSelect">
                        <option value="distance"><?php echo $text['sort_distance']; ?></option>
                        <option value="name"><?php echo $text['sort_name']; ?></option>
                    </select>
                </div>

                <div class="places-grid" id="placesGrid">
                    <div class="no-places" id="noPlacesMessage">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3><?php echo $text['no_location']; ?></h3>
                        <p><?php echo $text['enable_location']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // PHP text array passed to JavaScript
        const text = <?php echo json_encode($text); ?>;

        // State variables
        let map;
        let userMarker;
        let radiusCircle;
        let placesMarkers = [];
        let currentLocation = null;
        let currentRadius = 500;
        let allPlaces = [];

        // Initialize map
        function initMap(lat, lng) {
            map = L.map('map').setView([lat, lng], 15);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
                maxZoom: 19
            }).addTo(map);

            // User marker
            userMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    html: '<div class="user-marker"></div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                })
            }).addTo(map).bindPopup(`<b>${text.current_pos}</b>`);

            // Radius circle
            radiusCircle = L.circle([lat, lng], {
                color: '#ff6b4a',
                fillColor: '#ff6b4a',
                fillOpacity: 0.1,
                weight: 2,
                radius: currentRadius
            }).addTo(map);
        }

        // Get current location
        function getCurrentLocation() {
            return new Promise((resolve, reject) => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    });
                } else {
                    reject(new Error('Geolocation not supported'));
                }
            });
        }

        // Update location display
        function updateLocationDisplay(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const acc = position.coords.accuracy;

            document.getElementById('locationStatus').innerHTML = `
                <i class="fas fa-check-circle" style="color: var(--accent-secondary);"></i>
                ${text.your_location}
            `;
            document.getElementById('latDisplay').textContent = `${text.latitude}: ${lat.toFixed(6)}`;
            document.getElementById('lngDisplay').textContent = `${text.longitude}: ${lng.toFixed(6)}`;
            document.getElementById('accDisplay').textContent = `${text.accuracy}: ${Math.round(acc)}${text.meter}`;

            currentLocation = { lat, lng };
        }

        // Calculate distance
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3;
            const φ1 = lat1 * Math.PI/180;
            const φ2 = lat2 * Math.PI/180;
            const Δφ = (lat2-lat1) * Math.PI/180;
            const Δλ = (lon2-lon1) * Math.PI/180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            return R * c;
        }

        // Format distance
        function formatDistance(meters) {
            if (meters < 1000) {
                return Math.round(meters) + ' ' + text.meter;
            }
            return (meters / 1000).toFixed(1) + ' ' + text.kilometer;
        }

        // Get category icon
        function getCategoryIcon(category) {
            const icons = {
                'police': 'fa-shield-alt',
                'hospital': 'fa-hospital',
                'fire_station': 'fa-fire-extinguisher',
                'school': 'fa-graduation-cap',
                'shopping_mall': 'fa-shopping-cart',
                'restaurant': 'fa-utensils',
                'atm': 'fa-money-bill-wave',
                'hotel': 'fa-hotel',
                'other': 'fa-map-marker-alt'
            };
            return icons[category] || icons['other'];
        }

        // Get category name in Bangla
        function getCategoryName(category) {
            const names = {
                'police': text.category_police,
                'hospital': text.category_hospital,
                'fire_station': text.category_fire,
                'school': text.category_school,
                'shopping_mall': text.category_mall,
                'restaurant': text.category_restaurant,
                'atm': text.category_atm,
                'hotel': text.category_hotel,
                'other': 'অন্যান্য'
            };
            return names[category] || names['other'];
        }

        // Get category color
        function getCategoryColor(category) {
            const colors = {
                'police': '#4285F4',
                'hospital': '#EA4335',
                'fire_station': '#FBBC05',
                'school': '#34A853',
                'shopping_mall': '#9C27B0',
                'restaurant': '#FF9800',
                'atm': '#607D8B',
                'hotel': '#795548',
                'other': '#ff6b4a'
            };
            return colors[category] || colors['other'];
        }

        // Search places using Overpass API
        async function searchPlaces(lat, lng, radius, category) {
            const queries = {
                'all': `(
                    node["amenity"="police"](around:${radius},${lat},${lng});
                    node["amenity"="hospital"](around:${radius},${lat},${lng});
                    node["amenity"="doctors"](around:${radius},${lat},${lng});
                    node["amenity"="clinic"](around:${radius},${lat},${lng});
                    node["amenity"="fire_station"](around:${radius},${lat},${lng});
                    node["amenity"="school"](around:${radius},${lat},${lng});
                    node["amenity"="college"](around:${radius},${lat},${lng});
                    node["amenity"="university"](around:${radius},${lat},${lng});
                    node["shop"="mall"](around:${radius},${lat},${lng});
                    node["amenity"="restaurant"](around:${radius},${lat},${lng});
                    node["amenity"="cafe"](around:${radius},${lat},${lng});
                    node["amenity"="fast_food"](around:${radius},${lat},${lng});
                    node["amenity"="atm"](around:${radius},${lat},${lng});
                    node["amenity"="bank"](around:${radius},${lat},${lng});
                    node["tourism"="hotel"](around:${radius},${lat},${lng});
                )`,
                'police': `node["amenity"="police"](around:${radius},${lat},${lng});`,
                'hospital': `(
                    node["amenity"="hospital"](around:${radius},${lat},${lng});
                    node["amenity"="doctors"](around:${radius},${lat},${lng});
                    node["amenity"="clinic"](around:${radius},${lat},${lng});
                )`,
                'fire_station': `node["amenity"="fire_station"](around:${radius},${lat},${lng});`,
                'school': `(
                    node["amenity"="school"](around:${radius},${lat},${lng});
                    node["amenity"="college"](around:${radius},${lat},${lng});
                    node["amenity"="university"](around:${radius},${lat},${lng});
                )`,
                'shopping_mall': `node["shop"="mall"](around:${radius},${lat},${lng});`,
                'restaurant': `(
                    node["amenity"="restaurant"](around:${radius},${lat},${lng});
                    node["amenity"="cafe"](around:${radius},${lat},${lng});
                    node["amenity"="fast_food"](around:${radius},${lat},${lng});
                )`,
                'atm': `(
                    node["amenity"="atm"](around:${radius},${lat},${lng});
                    node["amenity"="bank"](around:${radius},${lat},${lng});
                )`,
                'hotel': `node["tourism"="hotel"](around:${radius},${lat},${lng});`
            };

            const query = queries[category] || queries['all'];
            const overpassQuery = `[out:json][timeout:30];${query};out body;`;

            try {
                const response = await fetch('https://overpass-api.de/api/interpreter', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'data=' + encodeURIComponent(overpassQuery)
                });
                
                const data = await response.json();
                const places = [];
                const seenIds = new Set();

                data.elements.forEach(element => {
                    if (element.type === 'node' && element.tags && element.tags.name) {
                        const placeId = element.id;
                        
                        if (seenIds.has(placeId)) return;
                        seenIds.add(placeId);
                        
                        const distance = calculateDistance(lat, lng, element.lat, element.lon);
                        
                        // Determine category
                        let placeCategory = 'other';
                        if (element.tags.amenity === 'police') placeCategory = 'police';
                        else if (element.tags.amenity === 'hospital' || element.tags.amenity === 'doctors' || element.tags.amenity === 'clinic') placeCategory = 'hospital';
                        else if (element.tags.amenity === 'fire_station') placeCategory = 'fire_station';
                        else if (element.tags.amenity === 'school' || element.tags.amenity === 'college' || element.tags.amenity === 'university') placeCategory = 'school';
                        else if (element.tags.shop === 'mall') placeCategory = 'shopping_mall';
                        else if (element.tags.amenity === 'restaurant' || element.tags.amenity === 'cafe' || element.tags.amenity === 'fast_food') placeCategory = 'restaurant';
                        else if (element.tags.amenity === 'atm' || element.tags.amenity === 'bank') placeCategory = 'atm';
                        else if (element.tags.tourism === 'hotel') placeCategory = 'hotel';
                        
                        places.push({
                            id: placeId,
                            name: element.tags.name,
                            address: element.tags['addr:street'] ? 
                                element.tags['addr:street'] + (element.tags['addr:housenumber'] ? ' ' + element.tags['addr:housenumber'] : '') : 
                                element.tags['addr:full'] || text.address + ' পাওয়া যায়নি',
                            lat: element.lat,
                            lng: element.lon,
                            category: placeCategory,
                            categoryName: getCategoryName(placeCategory),
                            distance: distance,
                            phone: element.tags.phone || element.tags['contact:phone'] || null
                        });
                    }
                });
                
                return places;
                
            } catch (error) {
                console.error('Error searching places:', error);
                return [];
            }
        }

        // Display places on map and grid
        function displayPlaces(places) {
            // Clear existing markers
            placesMarkers.forEach(marker => map.removeLayer(marker));
            placesMarkers = [];

            const grid = document.getElementById('placesGrid');
            grid.innerHTML = '';

            document.getElementById('resultsCount').textContent = 
                `${text.safe_places_nearby} (${places.length})`;

            if (places.length === 0) {
                grid.innerHTML = `
                    <div class="no-places">
                        <i class="fas fa-search"></i>
                        <h3>${text.no_places_found}</h3>
                        <p>${text.try_increasing_radius}</p>
                    </div>
                `;
                return;
            }

            // Sort places based on selected option
            const sortBy = document.getElementById('sortSelect').value;
            if (sortBy === 'distance') {
                places.sort((a, b) => a.distance - b.distance);
            } else if (sortBy === 'name') {
                places.sort((a, b) => a.name.localeCompare(b.name));
            }

            places.forEach(place => {
                const color = getCategoryColor(place.category);
                
                // Add marker to map
                const marker = L.marker([place.lat, place.lng], {
                    icon: L.divIcon({
                        html: `<div style="background: ${color}; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
                        iconSize: [14, 14],
                        iconAnchor: [7, 7]
                    })
                }).addTo(map);
                
                marker.bindPopup(`
                    <div style="font-family: 'Bornomala', serif; min-width: 150px;">
                        <b style="color: ${color};">${place.name}</b><br>
                        <small style="color: #b0b8c2;">${place.categoryName}</small><br>
                        <small style="color: #4ade80;">${formatDistance(place.distance)}</small><br>
                        <div style="display: flex; gap: 5px; margin-top: 8px;">
                            <button onclick="navigateToPlace(${place.lat}, ${place.lng})" 
                                    style="flex:1; padding: 5px; background: #ff6b4a; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-family: 'Bornomala', serif;">
                                <i class="fas fa-directions"></i> ${text.navigate_now}
                            </button>
                        </div>
                    </div>
                `);
                
                placesMarkers.push(marker);

                // Add card to grid
                const card = document.createElement('div');
                card.className = 'place-card';
                card.dataset.id = place.id;
                card.dataset.lat = place.lat;
                card.dataset.lng = place.lng;
                
                card.innerHTML = `
                    <div class="place-header">
                        <div class="place-name">
                            <i class="fas ${getCategoryIcon(place.category)}"></i>
                            ${place.name}
                        </div>
                        <span class="place-badge">${place.categoryName}</span>
                    </div>
                    <div class="place-address">
                        <i class="fas fa-map-pin"></i>
                        ${place.address}
                    </div>
                    <div class="place-distance">
                        <i class="fas fa-location-arrow"></i>
                        ${formatDistance(place.distance)}
                    </div>
                    <div class="place-actions">
                        <button class="action-btn directions-btn" onclick="showDirections(${place.lat}, ${place.lng})">
                            <i class="fas fa-directions"></i> ${text.get_directions}
                        </button>
                        <button class="action-btn navigate-btn" onclick="navigateToPlace(${place.lat}, ${place.lng})">
                            <i class="fas fa-road"></i> ${text.navigate_now}
                        </button>
                    </div>
                    <div class="quick-actions">
                        ${place.phone ? `
                            <button class="quick-btn" onclick="window.location.href='tel:${place.phone}'">
                                <i class="fas fa-phone"></i> ${text.call}
                            </button>
                        ` : ''}
                        <button class="quick-btn" onclick="savePlace('${place.name}')">
                            <i class="fas fa-bookmark"></i> ${text.save_place}
                        </button>
                        <button class="quick-btn" onclick="shareLocation(${place.lat}, ${place.lng})">
                            <i class="fas fa-share"></i> ${text.share_location}
                        </button>
                    </div>
                `;

                card.addEventListener('click', function(e) {
                    if (!e.target.closest('button')) {
                        // Remove selected class from all cards
                        document.querySelectorAll('.place-card').forEach(c => c.classList.remove('selected'));
                        this.classList.add('selected');
                        
                        // Center map on this place
                        map.setView([place.lat, place.lng], 17);
                        
                        // Find and open corresponding marker popup
                        placesMarkers.forEach(m => {
                            if (m.getLatLng().lat === place.lat && m.getLatLng().lng === place.lng) {
                                m.openPopup();
                            }
                        });
                    }
                });

                grid.appendChild(card);
            });

            // Fit map bounds to show all markers
            if (places.length > 0) {
                const group = L.featureGroup([userMarker, ...placesMarkers]);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }

        // Navigation functions
        function showDirections(lat, lng) {
            if (currentLocation) {
                const url = `https://www.google.com/maps/dir/?api=1&origin=${currentLocation.lat},${currentLocation.lng}&destination=${lat},${lng}&travelmode=walking`;
                window.open(url, '_blank');
            }
        }

        function navigateToPlace(lat, lng) {
            if (currentLocation) {
                const url = `https://www.google.com/maps/dir/?api=1&origin=${currentLocation.lat},${currentLocation.lng}&destination=${lat},${lng}&travelmode=driving&dir_action=navigate`;
                window.open(url, '_blank');
            }
        }

        function savePlace(name) {
            alert(`"${name}" ${text.save_place}`);
        }

        function shareLocation(lat, lng) {
            if (navigator.share) {
                navigator.share({
                    title: text.share_location,
                    text: `${text.latitude}: ${lat}, ${text.longitude}: ${lng}`,
                    url: `https://www.google.com/maps?q=${lat},${lng}`
                }).catch(() => {
                    prompt(text.share_location, `https://www.google.com/maps?q=${lat},${lng}`);
                });
            } else {
                prompt(text.share_location, `https://www.google.com/maps?q=${lat},${lng}`);
            }
        }

        // Update radius circle
        function updateRadiusCircle() {
            if (radiusCircle && currentLocation) {
                map.removeLayer(radiusCircle);
                radiusCircle = L.circle([currentLocation.lat, currentLocation.lng], {
                    color: '#ff6b4a',
                    fillColor: '#ff6b4a',
                    fillOpacity: 0.1,
                    weight: 2,
                    radius: currentRadius
                }).addTo(map);
            }
        }

        // Search and display places
        async function searchAndDisplayPlaces() {
            if (!currentLocation) return;

            const searchBtn = document.getElementById('searchBtn');
            const originalText = searchBtn.innerHTML;
            
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + text.searching;
            searchBtn.disabled = true;

            const category = document.getElementById('categorySelect').value;
            const places = await searchPlaces(
                currentLocation.lat,
                currentLocation.lng,
                currentRadius,
                category
            );
            
            allPlaces = places;
            displayPlaces(places);
            
            searchBtn.innerHTML = originalText;
            searchBtn.disabled = false;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                const position = await getCurrentLocation();
                updateLocationDisplay(position);
                initMap(position.coords.latitude, position.coords.longitude);
                
                document.getElementById('searchBtn').disabled = false;
                document.getElementById('noPlacesMessage').style.display = 'none';
                
                await searchAndDisplayPlaces();
            } catch (error) {
                console.error('Error getting location:', error);
                document.getElementById('locationStatus').innerHTML = `
                    <i class="fas fa-exclamation-triangle" style="color: var(--accent-danger);"></i>
                    ${text.enable_location}
                `;
                
                // Use Dhaka as default location
                const dhakaLocation = {
                    coords: {
                        latitude: 23.8103,
                        longitude: 90.4125,
                        accuracy: 100
                    }
                };
                updateLocationDisplay(dhakaLocation);
                initMap(23.8103, 90.4125);
                document.getElementById('searchBtn').disabled = false;
                document.getElementById('noPlacesMessage').style.display = 'none';
                await searchAndDisplayPlaces();
            }
            
            // Radius buttons
            document.querySelectorAll('.radius-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.radius-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentRadius = parseInt(this.dataset.radius);
                    updateRadiusCircle();
                    searchAndDisplayPlaces();
                });
            });
            
            // Category change
            document.getElementById('categorySelect').addEventListener('change', function() {
                searchAndDisplayPlaces();
            });
            
            // Sort change - reorder existing places without new API call
            document.getElementById('sortSelect').addEventListener('change', function() {
                if (allPlaces.length > 0) {
                    displayPlaces(allPlaces);
                }
            });
            
            // Search button
            document.getElementById('searchBtn').addEventListener('click', function() {
                searchAndDisplayPlaces();
            });
        });
    </script>
</body>
</html>