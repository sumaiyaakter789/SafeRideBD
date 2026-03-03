<?php
include_once 'navbar.php';
include_once 'db_config.php';

$locations = [];
$location_query = "SELECT DISTINCT `from` as location FROM fare_chart 
                   UNION 
                   SELECT DISTINCT `to` as location FROM fare_chart 
                   ORDER BY location";
$location_result = $conn->query($location_query);
if ($location_result && $location_result->num_rows > 0) {
    while ($row = $location_result->fetch_assoc()) {
        $locations[] = $row['location'];
    }
} else {
    $locations = ["কোন লোকেশন বিবরণ পাওয়া যায়নি"];
}
?>

<style>
    /* Additional styles for index page */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 24px;
        flex: 1;
    }

    .page-header {
        text-align: center;
        margin-bottom: 48px;
    }

    .page-title {
        font-size: 42px;
        font-weight: 700;
        margin-bottom: 16px;
        background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .page-subtitle {
        color: var(--text-secondary);
        font-size: 18px;
        max-width: 700px;
        margin: 0 auto 20px;
        line-height: 1.8;
    }

    .security-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: var(--bg-card);
        padding: 8px 16px;
        border-radius: 8px;
        color: var(--accent-secondary);
        font-size: 14px;
        border: 1px solid var(--border-color);
    }

    .card {
        background-color: var(--bg-card);
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 32px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }

    .card-title {
        color: var(--text-primary);
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .card-title i {
        color: var(--accent-primary);
        font-size: 24px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-secondary);
        font-size: 16px;
        font-weight: 500;
    }

    .typeahead-container {
        position: relative;
    }

    .select-container {
        position: relative;
    }

    .select-container i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        z-index: 2;
        font-size: 18px;
    }

    .typeahead-input {
        width: 100%;
        padding: 16px 20px 16px 48px;
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: 'Bornomala', serif;
        font-size: 16px;
        color: var(--text-primary);
        transition: all 0.2s;
    }

    .typeahead-input:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: var(--glow);
    }

    .typeahead-input::placeholder {
        color: var(--text-muted);
    }

    .suggestions-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 0 0 8px 8px;
        max-height: 250px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: var(--shadow);
    }

    .suggestions-dropdown.active {
        display: block;
    }

    .suggestion-item {
        padding: 12px 20px;
        cursor: pointer;
        color: var(--text-secondary);
        border-bottom: 1px solid var(--border-color);
        transition: all 0.2s;
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }

    .suggestion-item:hover {
        background-color: var(--bg-hover);
        color: var(--text-primary);
    }

    .search-btn {
        width: 100%;
        padding: 18px 24px;
        background-color: var(--accent-primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-family: 'Bornomala', serif;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 16px;
    }

    .search-btn:hover:not(:disabled) {
        background-color: #ff5a3a;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(255, 107, 74, 0.3);
    }

    .search-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .error-message {
        background-color: rgba(239, 68, 68, 0.1);
        border-left: 4px solid var(--accent-danger);
        color: var(--accent-danger);
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 24px;
        display: none;
    }

    .error-message.active {
        display: block;
    }

    .loading {
        text-align: center;
        padding: 40px;
        display: none;
    }

    .loading.active {
        display: block;
    }

    .spinner {
        border: 3px solid var(--border-color);
        border-top: 3px solid var(--accent-primary);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 16px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .results-card {
        display: none;
    }

    .results-card.active {
        display: block;
    }

    /* Quick Stats */
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 32px;
    }

    .stat-card {
        background-color: var(--bg-secondary);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        border: 1px solid var(--border-color);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 20px;
    }

    .stat-icon.distance {
        background-color: rgba(255, 107, 74, 0.1);
        color: var(--accent-primary);
    }

    .stat-icon.time {
        background-color: rgba(251, 191, 36, 0.1);
        color: var(--accent-warning);
    }

    .stat-icon.segments {
        background-color: rgba(74, 222, 128, 0.1);
        color: var(--accent-secondary);
    }

    .stat-icon.transfers {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--accent-danger);
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 14px;
        color: var(--text-muted);
    }

    /* Journey Section */
    .journey-section {
        background-color: var(--bg-secondary);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid var(--border-color);
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-color);
    }

    .section-icon {
        width: 40px;
        height: 40px;
        background-color: rgba(255, 107, 74, 0.1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-primary);
        font-size: 18px;
    }

    .section-title {
        color: var(--text-primary);
        font-size: 20px;
        font-weight: 600;
    }

    /* Path Visualization */
    .path-visualization {
        position: relative;
        padding: 16px 0;
    }

    .path-line {
        position: absolute;
        left: 35px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: var(--border-color);
    }

    .path-step {
        position: relative;
        margin-bottom: 24px;
        padding-left: 70px;
    }

    .step-marker {
        position: absolute;
        left: 25px;
        top: 20px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: var(--bg-card);
        border: 2px solid;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 12px;
        z-index: 2;
    }

    .step-marker.start {
        border-color: var(--accent-primary);
        color: var(--accent-primary);
    }

    .step-marker.transfer {
        border-color: var(--accent-warning);
        color: var(--accent-warning);
    }

    .step-marker.end {
        border-color: var(--accent-secondary);
        color: var(--accent-secondary);
    }

    .step-content {
        background-color: var(--bg-card);
        border-radius: 8px;
        padding: 20px;
        border: 1px solid var(--border-color);
    }

    .step-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .step-locations {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .step-from {
        color: var(--accent-primary);
    }

    .step-to {
        color: var(--accent-secondary);
    }

    .step-arrow {
        color: var(--text-muted);
    }

    .step-distance {
        background-color: var(--bg-secondary);
        padding: 4px 12px;
        border-radius: 4px;
        color: var(--text-secondary);
        font-size: 14px;
        border: 1px solid var(--border-color);
    }

    .step-fares {
        display: flex;
        gap: 16px;
    }

    .fare-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
    }

    .fare-badge.regular {
        background-color: rgba(255, 107, 74, 0.1);
        color: var(--accent-primary);
    }

    .fare-badge.student {
        background-color: rgba(74, 222, 128, 0.1);
        color: var(--accent-secondary);
    }

    .transfer-notice {
        margin-top: 12px;
        padding: 16px;
        background-color: rgba(251, 191, 36, 0.1);
        border-left: 4px solid var(--accent-warning);
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .transfer-icon {
        width: 32px;
        height: 32px;
        background-color: rgba(251, 191, 36, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-warning);
    }

    .transfer-content h4 {
        color: var(--accent-warning);
        font-size: 16px;
        margin-bottom: 4px;
    }

    .transfer-content p {
        color: var(--text-secondary);
        font-size: 14px;
    }

    /* Bus Services */
    .bus-category {
        margin-bottom: 24px;
    }

    .category-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        color: var(--text-secondary);
        font-size: 16px;
    }

    .category-header i {
        color: var(--accent-primary);
    }

    .bus-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }

    .bus-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 16px;
        transition: all 0.2s;
    }

    .bus-card:hover {
        border-color: var(--accent-primary);
        transform: translateY(-2px);
    }

    .bus-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .bus-number {
        width: 32px;
        height: 32px;
        background-color: var(--accent-primary);
        color: white;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }

    .bus-name {
        font-weight: 500;
        color: var(--text-primary);
    }

    .bus-fares {
        display: flex;
        justify-content: space-between;
        padding-top: 12px;
        border-top: 1px dashed var(--border-color);
    }

    .bus-fare-item {
        text-align: center;
    }

    .bus-fare-label {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 4px;
    }

    .bus-fare-amount.regular {
        color: var(--accent-primary);
        font-weight: 600;
    }

    .bus-fare-amount.student {
        color: var(--accent-secondary);
        font-weight: 600;
    }

    /* Fare Breakdown */
    .fare-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 0;
        border-bottom: 1px dashed var(--border-color);
    }

    .fare-item-label {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--text-secondary);
    }

    .fare-item-label i {
        color: var(--accent-primary);
        width: 20px;
    }

    .fare-item-amounts {
        display: flex;
        gap: 24px;
    }

    .fare-amount {
        text-align: center;
    }

    .fare-type {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 2px;
    }

    .fare-value.regular {
        color: var(--accent-primary);
        font-weight: 600;
    }

    .fare-value.student {
        color: var(--accent-secondary);
        font-weight: 600;
    }

    .fare-total {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 2px solid var(--border-color);
    }

    .fare-total-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .total-label {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .total-amount.regular {
        font-size: 24px;
        font-weight: 700;
        color: var(--accent-primary);
    }

    .total-amount.student {
        font-size: 24px;
        font-weight: 700;
        color: var(--accent-secondary);
    }

    .discount-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: rgba(74, 222, 128, 0.1);
        color: var(--accent-secondary);
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
    }

    /* Safety Tips */
    .safety-tips {
        background-color: var(--bg-secondary);
        border-radius: 12px;
        padding: 24px;
        margin-top: 24px;
        border: 1px solid var(--border-color);
    }

    .tips-header {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--accent-secondary);
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .tips-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .tip-item {
        display: flex;
        gap: 12px;
    }

    .tip-icon {
        width: 36px;
        height: 36px;
        background-color: rgba(255, 107, 74, 0.1);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-primary);
        flex-shrink: 0;
    }

    .tip-content h4 {
        color: var(--text-primary);
        font-size: 15px;
        margin-bottom: 4px;
    }

    .tip-content p {
        color: var(--text-muted);
        font-size: 13px;
        line-height: 1.5;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 16px;
        margin-top: 32px;
    }

    .search-again-btn {
        flex: 1;
        padding: 16px;
        background-color: var(--bg-secondary);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: 'Bornomala', serif;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .search-again-btn:hover {
        background-color: var(--bg-hover);
        border-color: var(--accent-primary);
        color: var(--text-primary);
    }

    .save-btn {
        flex: 1;
        padding: 16px;
        background-color: rgba(74, 222, 128, 0.1);
        color: var(--accent-secondary);
        border: 1px solid rgba(74, 222, 128, 0.3);
        border-radius: 8px;
        font-family: 'Bornomala', serif;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .save-btn:hover:not(:disabled) {
        background-color: rgba(74, 222, 128, 0.2);
        border-color: var(--accent-secondary);
        transform: translateY(-2px);
    }

    .save-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .quick-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .page-title {
            font-size: 32px;
        }

        .card {
            padding: 24px;
        }

        .step-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .fare-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .fare-item-amounts {
            width: 100%;
            justify-content: space-between;
        }

        .action-buttons {
            flex-direction: column;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 24px 16px;
        }

        .quick-stats {
            grid-template-columns: 1fr;
        }

        .bus-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Published Reports Section */
    .published-reports-section {
        margin-top: 50px;
        padding-top: 30px;
        border-top: 2px solid var(--border-color);
    }

    .view-all-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background-color: var(--bg-hover);
        border-radius: 8px;
        transition: all 0.2s;
    }

    .view-all-link:hover {
        background-color: var(--accent-primary);
        color: white !important;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">পাবলিক বাসের ভাড়া ক্যালকুলেটর</h1>
        <p class="page-subtitle">ঢাকার পাবলিক বাসের জন্য বিআরটিএ অনুমোদিত সঠিক ভাড়া এবং ছাত্রছাত্রীদের জন্য অনুমোদিত ছাড়ের তথ্য। আপনার রুট দেখুন এবং বিস্তারিত খরচ সম্পর্কে জানুন।</p>
        <div class="security-badge">
            <i class="fas fa-shield-alt"></i> নির্ভরযোগ্য ও সঠিক ভাড়ার তথ্য
        </div>
    </div>

    <div class="error-message" id="errorMessage"></div>

    <div class="loading" id="loadingIndicator">
        <div class="spinner"></div>
        <p style="color: var(--text-secondary);">অনুগ্রহপূর্বক অপেক্ষা করুন...</p>
    </div>

    <div class="card">
        <h2 class="card-title">
            <i class="fas fa-route"></i> আপনার যাত্রার তথ্য খুঁজুন
        </h2>
        <p style="color: var(--text-muted); margin-bottom: 24px;">আপনার যাত্রা শুরু ও গন্তব্য নির্বাচন করে সঠিক ভাড়া ও রুটের বিস্তারিত তথ্য জানুন</p>

        <form id="fareForm">
            <div class="form-group">
                <label for="fromLocation">
                    <i class="fas fa-map-marker" style="color: var(--accent-primary);"></i>
                    যাত্রা শুরুর স্থান
                </label>
                <div class="typeahead-container">
                    <div class="select-container">
                        <i class="fas fa-search-location"></i>
                        <input type="text" id="fromLocation" class="typeahead-input" placeholder="যাত্রা শুরুর স্থান লিখুন" required autocomplete="off">
                    </div>
                    <div class="suggestions-dropdown" id="fromSuggestions"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="toLocation">
                    <i class="fas fa-map-marker-alt" style="color: var(--accent-secondary);"></i>
                    গন্তব্য স্থান
                </label>
                <div class="typeahead-container">
                    <div class="select-container">
                        <i class="fas fa-flag-checkered"></i>
                        <input type="text" id="toLocation" class="typeahead-input" placeholder="গন্তব্যের স্থান লিখুন" required autocomplete="off">
                    </div>
                    <div class="suggestions-dropdown" id="toSuggestions"></div>
                </div>
            </div>

            <button type="submit" class="search-btn" id="searchBtn">
                <i class="fas fa-route"></i> ভাড়া ও রুটের তথ্য দেখুন
            </button>
        </form>
    </div>

    <div class="card results-card" id="resultsCard">
        <h2 class="card-title">
            <i class="fas fa-file-invoice-dollar"></i> আপনার যাত্রার বিবরণ
        </h2>

        <div class="quick-stats" id="quickStats" style="display: none;">
            <div class="stat-card">
                <div class="stat-icon distance">
                    <i class="fas fa-road"></i>
                </div>
                <div class="stat-value" id="statDistance">0</div>
                <div class="stat-label">মোট দূরত্ব</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon time">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value" id="statTime">0</div>
                <div class="stat-label">সময়</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon segments">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-value" id="statSegments">0</div>
                <div class="stat-label">সেগমেন্ট</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon transfers">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-value" id="statTransfers">0</div>
                <div class="stat-label">বাস বদল</div>
            </div>
        </div>

        <div class="journey-section" id="routePathSection" style="display: none;">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-map-signs"></i>
                </div>
                <h3 class="section-title">যাত্রাপথ</h3>
            </div>
            <div class="path-visualization" id="pathVisualization"></div>
        </div>

        <div class="journey-section" id="busServicesSection" style="display: none;">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-bus"></i>
                </div>
                <h3 class="section-title">চলাচলকারী বাসের তথ্য</h3>
            </div>
            <div id="busServicesContent"></div>
        </div>

        <div class="journey-section" id="fareBreakdownSection" style="display: none;">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <h3 class="section-title">ভাড়ার বিবরণ</h3>
            </div>
            <div id="fareBreakdownContent"></div>
        </div>

        <div class="safety-tips" id="safetyTips" style="display: none;">
            <div class="tips-header">
                <i class="fas fa-lightbulb"></i>
                <span>নিরাপদ যাত্রা সম্পর্কিত টিপস</span>
            </div>
            <div class="tips-grid">
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="tip-content">
                        <h4>নির্ধারিত স্টপেজে উঠুন</h4>
                        <p>নির্ধারিত বাস স্টপ থেকে উঠানামা করুন। এটি নিরাপদ এবং দৈনন্দিন যানজট কমাতে সাহায্য করে।</p>
                    </div>
                </div>
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="tip-content">
                        <h4>ছাত্র আইডি কার্ড সাথে রাখুন</h4>
                        <p>নির্ধারিত ছাড় পেতে আপনার বৈধ ছাত্র আইডি সঙ্গে রাখুন এবং ভাড়া দেওয়ার সময় দেখান।</p>
                    </div>
                </div>
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="tip-content">
                        <h4>সতর্ক থাকুন</h4>
                        <p>মূল্যবান জিনিসপত্র নিজের কাছে রাখুন এবং আশেপাশের অবস্থা সম্পর্কে সচেতন থাকুন।</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <button class="search-again-btn" id="searchAgainBtn">
                <i class="fas fa-search"></i> নতুন অনুসন্ধান
            </button>
            <button class="save-btn" id="saveRouteBtn">
                <i class="fas fa-bookmark"></i> রুট সংরক্ষণ
            </button>
        </div>
    </div>

    <!-- Published Reports Section -->
    <div class="published-reports-section" style="margin-top: 50px;">
        <div class="section-header" style="margin-bottom: 30px;">
            <div class="section-icon" style="background-color: rgba(255, 107, 74, 0.1);">
                <i class="fas fa-newspaper" style="color: var(--accent-primary);"></i>
            </div>
            <h2 class="section-title" style="font-size: 28px;">সাম্প্রতিক প্রতিবেদন</h2>
            <a href="all_reports.php" class="view-all-link" style="margin-left: auto; color: var(--accent-primary); text-decoration: none;">
                সকল প্রতিবেদন <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <?php
        // Get latest published reports
        $reports_sql = "SELECT id, title, cover_image, author, publish_date, views, category,
                    SUBSTRING(content, 1, 150) as excerpt 
                    FROM published_reports 
                    WHERE status = 'published' 
                    ORDER BY publish_date DESC 
                    LIMIT 3";
        $reports_result = $conn->query($reports_sql);

        if ($reports_result && $reports_result->num_rows > 0):
        ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px;">
                <?php while ($report_item = $reports_result->fetch_assoc()): ?>
                    <a href="view_published_report.php?id=<?php echo $report_item['id']; ?>" style="text-decoration: none;">
                        <div style="background-color: var(--bg-card); border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color); transition: all 0.3s; height: 100%;">
                            <div style="height: 200px; background-color: var(--bg-secondary); position: relative;">
                                <?php if (!empty($report_item['cover_image']) && file_exists($report_item['cover_image'])): ?>
                                    <img src="<?php echo $report_item['cover_image']; ?>" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); color: white; font-size: 48px;">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                <?php endif; ?>
                                <div style="position: absolute; top: 15px; right: 15px; background-color: var(--bg-card); padding: 5px 12px; border-radius: 20px; font-size: 12px; border: 1px solid var(--border-color);">
                                    <?php
                                    $categories = [
                                        'incident' => 'ঘটনা',
                                        'safety' => 'নিরাপত্তা',
                                        'update' => 'আপডেট',
                                        'other' => 'অন্যান্য'
                                    ];
                                    echo $categories[$report_item['category']] ?? $report_item['category'];
                                    ?>
                                </div>
                            </div>
                            <div style="padding: 20px;">
                                <h3 style="color: var(--text-primary); font-size: 18px; margin-bottom: 10px; line-height: 1.4;">
                                    <?php echo htmlspecialchars($report_item['title']); ?>
                                </h3>
                                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px; line-height: 1.6;">
                                    <?php echo htmlspecialchars($report_item['excerpt']); ?>...
                                </p>
                                <div style="display: flex; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 13px;">
                                    <span>
                                        <i class="fas fa-user" style="color: var(--accent-primary); margin-right: 5px;"></i>
                                        <?php echo htmlspecialchars($report_item['author']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar" style="color: var(--accent-primary); margin-right: 5px;"></i>
                                        <?php echo date('d M Y', strtotime($report_item['publish_date'])); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-eye" style="color: var(--accent-primary); margin-right: 5px;"></i>
                                        <?php echo $report_item['views']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 60px; background-color: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
                <i class="fas fa-newspaper" style="font-size: 48px; color: var(--text-muted); margin-bottom: 15px;"></i>
                <p style="color: var(--text-muted);">কোন প্রকাশিত প্রতিবেদন পাওয়া যায়নি</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const dhakaLocations = <?php echo json_encode($locations); ?>;
    let currentRouteData = null;

    function setupTypeahead(inputId, suggestionsId) {
        const input = document.getElementById(inputId);
        const suggestionsDropdown = document.getElementById(suggestionsId);
        let abortController = null;

        input.addEventListener('input', debounce(async function() {
            const query = input.value.toLowerCase().trim();

            if (query.length < 2) {
                suggestionsDropdown.classList.remove('active');
                return;
            }

            // Cancel previous request
            if (abortController) {
                abortController.abort();
            }

            abortController = new AbortController();

            try {
                const response = await fetch(`get_locations.php?q=${encodeURIComponent(query)}`, {
                    signal: abortController.signal
                });
                const locations = await response.json();

                suggestionsDropdown.innerHTML = '';

                if (locations.length > 0) {
                    locations.forEach(location => {
                        const suggestionItem = document.createElement('div');
                        suggestionItem.className = 'suggestion-item';
                        suggestionItem.textContent = location;
                        suggestionItem.addEventListener('click', function() {
                            input.value = location;
                            suggestionsDropdown.classList.remove('active');
                        });
                        suggestionsDropdown.appendChild(suggestionItem);
                    });
                    suggestionsDropdown.classList.add('active');
                } else {
                    suggestionsDropdown.classList.remove('active');
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }
                console.error('Error fetching locations:', error);
            }
        }, 300));

        input.addEventListener('input', function() {
            const query = input.value.toLowerCase().trim();
            suggestionsDropdown.innerHTML = '';

            if (query.length > 0) {
                const filteredLocations = dhakaLocations.filter(location =>
                    location.toLowerCase().includes(query)
                );

                if (filteredLocations.length > 0) {
                    filteredLocations.forEach(location => {
                        const suggestionItem = document.createElement('div');
                        suggestionItem.className = 'suggestion-item';
                        suggestionItem.textContent = location;
                        suggestionItem.addEventListener('click', function() {
                            input.value = location;
                            suggestionsDropdown.classList.remove('active');
                        });
                        suggestionsDropdown.appendChild(suggestionItem);
                    });
                    suggestionsDropdown.classList.add('active');
                } else {
                    suggestionsDropdown.classList.remove('active');
                }
            } else {
                suggestionsDropdown.classList.remove('active');
            }
        });

        document.addEventListener('click', function(event) {
            if (!input.contains(event.target) && !suggestionsDropdown.contains(event.target)) {
                suggestionsDropdown.classList.remove('active');
            }
        });

        input.addEventListener('focus', function() {
            if (input.value === '') {
                suggestionsDropdown.innerHTML = '';
                dhakaLocations.forEach(location => {
                    const suggestionItem = document.createElement('div');
                    suggestionItem.className = 'suggestion-item';
                    suggestionItem.textContent = location;
                    suggestionItem.addEventListener('click', function() {
                        input.value = location;
                        suggestionsDropdown.classList.remove('active');
                    });
                    suggestionsDropdown.appendChild(suggestionItem);
                });
                suggestionsDropdown.classList.add('active');
            }
        });
    }

    setupTypeahead('fromLocation', 'fromSuggestions');
    setupTypeahead('toLocation', 'toSuggestions');

    const fareForm = document.getElementById('fareForm');
    const resultsCard = document.getElementById('resultsCard');
    const quickStats = document.getElementById('quickStats');
    const routePathSection = document.getElementById('routePathSection');
    const busServicesSection = document.getElementById('busServicesSection');
    const fareBreakdownSection = document.getElementById('fareBreakdownSection');
    const safetyTips = document.getElementById('safetyTips');
    const pathVisualization = document.getElementById('pathVisualization');
    const busServicesContent = document.getElementById('busServicesContent');
    const fareBreakdownContent = document.getElementById('fareBreakdownContent');
    const searchBtn = document.getElementById('searchBtn');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessage = document.getElementById('errorMessage');
    const searchAgainBtn = document.getElementById('searchAgainBtn');
    const saveRouteBtn = document.getElementById('saveRouteBtn');

    const statDistance = document.getElementById('statDistance');
    const statTime = document.getElementById('statTime');
    const statSegments = document.getElementById('statSegments');
    const statTransfers = document.getElementById('statTransfers');

    fareForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        const fromLocation = document.getElementById('fromLocation').value.trim();
        const toLocation = document.getElementById('toLocation').value.trim();

        // Validate locations
        const validation = validateLocations(fromLocation, toLocation);
        if (!validation.valid) {
            showError(validation.message);
            return;
        }

        loadingIndicator.classList.add('active');
        searchBtn.disabled = true;
        errorMessage.classList.remove('active');

        try {
            const response = await fetch('get_fare.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `from=${encodeURIComponent(fromLocation)}&to=${encodeURIComponent(toLocation)}`
            });

            const data = await response.json();

            loadingIndicator.classList.remove('active');
            searchBtn.disabled = false;

            if (data.success) {
                // Validate route direction
                if (data.route_breakdown && data.route_breakdown.length > 0) {
                    const firstSegmentFrom = data.route_breakdown[0].from;
                    const lastSegmentTo = data.route_breakdown[data.route_breakdown.length - 1].to;

                    // Check if route starts from correct location
                    if (firstSegmentFrom.toLowerCase() !== fromLocation.toLowerCase()) {
                        console.error('Route direction mismatch');
                        showError('রুটের দিক নির্দেশনা সঠিক নয়। অনুগ্রহপূর্বক আবার চেষ্টা করুন।');
                        return;
                    }

                    // Check if route ends at correct location
                    if (lastSegmentTo.toLowerCase() !== toLocation.toLowerCase()) {
                        console.error('Route destination mismatch');
                        showError('রুটের গন্তব্য সঠিক নয়। অনুগ্রহপূর্বক আবার চেষ্টা করুন।');
                        return;
                    }
                }

                currentRouteData = data;
                displayResults(data);
            } else {
                showError(data.message || "এই রুটের জন্য কোনো ভাড়ার তথ্য ডাটাবেজে পাওয়া যায়নি।");
            }
        } catch (error) {
            loadingIndicator.classList.remove('active');
            searchBtn.disabled = false;
            showError("ভাড়ার তথ্য পেতে সমস্যা হচ্ছে। আবার চেষ্টা করুন।");
            console.error('Error:', error);
        }
    });

    function displayResults(data) {
        quickStats.style.display = 'grid';
        routePathSection.style.display = 'block';
        busServicesSection.style.display = 'block';
        fareBreakdownSection.style.display = 'block';
        safetyTips.style.display = 'block';

        statDistance.textContent = `${data.distance_km} কিমি`;
        const estTime = Math.round(data.distance_km / 20 * 60);
        statTime.textContent = `${estTime} মিনিট`;
        statSegments.textContent = data.route_breakdown.length;
        statTransfers.textContent = data.transfers || data.route_breakdown.length - 1;

        displayRoutePath(data.route_breakdown);
        displayBusServices(data);
        displayFareBreakdown(data);

        resultsCard.classList.add('active');
        resultsCard.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }

    function displayRoutePath(routeBreakdown) {
        let pathHtml = '<div class="path-line"></div>';

        routeBreakdown.forEach((segment, index) => {
            const isFirst = index === 0;
            const isLast = index === routeBreakdown.length - 1;
            const isTransfer = !isLast;

            let markerClass = 'step-marker ';
            if (isFirst) markerClass += 'start';
            else if (isLast) markerClass += 'end';
            else markerClass += 'transfer';

            const stepNumber = index + 1;

            // Transfer message
            let transferMessage = '';
            if (isTransfer) {
                transferMessage = `
                    <div class="transfer-notice">
                        <div class="transfer-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="transfer-content">
                            <h4>বাস বদলাতে হবে</h4>
                            <p><strong>${segment.to}</strong>-এ নামুন এবং পরবর্তী বাস ধরুন</p>
                        </div>
                    </div>
                `;
            }

            pathHtml += `
                <div class="path-step">
                    <div class="${markerClass}">${stepNumber}</div>
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-locations">
                                <span class="step-from">${segment.from}</span>
                                <i class="fas fa-arrow-right step-arrow"></i>
                                <span class="step-to">${segment.to}</span>
                            </div>
                            <div class="step-distance">${segment.distance_km} কিমি</div>
                        </div>
                        <div class="step-fares">
                            <div class="fare-badge regular">
                                <i class="fas fa-ticket-alt"></i>
                                <span>সাধারণ: ৳${segment.fare}</span>
                            </div>
                            <div class="fare-badge student">
                                <i class="fas fa-graduation-cap"></i>
                                <span>ছাত্র: ৳${segment.student_fare}</span>
                            </div>
                        </div>
                    </div>
                    ${transferMessage}
                </div>
            `;
        });

        pathVisualization.innerHTML = pathHtml;
    }

    function validateLocations(fromLocation, toLocation) {
        // Check if locations are not empty
        if (!fromLocation || !toLocation) {
            return {
                valid: false,
                message: "যাত্রা শুরুর স্থান এবং গন্তব্যস্থল দুটোই লিখুন।"
            };
        }

        // Check if locations exist in the list (case-insensitive)
        const fromExists = dhakaLocations.some(loc => loc.toLowerCase() === fromLocation.toLowerCase());
        const toExists = dhakaLocations.some(loc => loc.toLowerCase() === toLocation.toLowerCase());

        if (!fromExists) {
            return {
                valid: false,
                message: `"${fromLocation}" তালিকায় নেই। অনুগ্রহপূর্বক সঠিক লোকেশন নির্বাচন করুন।`
            };
        }

        if (!toExists) {
            return {
                valid: false,
                message: `"${toLocation}" তালিকায় নেই। অনুগ্রহপূর্বক সঠিক লোকেশন নির্বাচন করুন।`
            };
        }

        if (fromLocation.toLowerCase() === toLocation.toLowerCase()) {
            return {
                valid: false,
                message: "যাত্রা শুরু এবং গন্তব্য স্থান অবশ্যই ভিন্ন হতে হবে।"
            };
        }

        return {
            valid: true
        };
    }

    // Update the form submit handler
    fareForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        const fromLocation = document.getElementById('fromLocation').value.trim();
        const toLocation = document.getElementById('toLocation').value.trim();

        const validation = validateLocations(fromLocation, toLocation);
        if (!validation.valid) {
            showError(validation.message);
            return;
        }

        loadingIndicator.classList.add('active');
        searchBtn.disabled = true;
        errorMessage.classList.remove('active');

        try {
            const response = await fetch('get_fare.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `from=${encodeURIComponent(fromLocation)}&to=${encodeURIComponent(toLocation)}`
            });

            const data = await response.json();

            loadingIndicator.classList.remove('active');
            searchBtn.disabled = false;

            if (data.success) {
                // Additional validation to ensure the route makes sense
                if (data.route_breakdown && data.route_breakdown.length > 0) {
                    // Check if first segment starts with from location
                    const firstSegmentFrom = data.route_breakdown[0].from.toLowerCase();
                    if (firstSegmentFrom !== fromLocation.toLowerCase()) {
                        console.warn('Route starts from wrong location:', firstSegmentFrom, 'vs', fromLocation);
                        // Try to fix by reversing if needed
                        if (firstSegmentFrom === toLocation.toLowerCase()) {
                            // Swap the locations
                            document.getElementById('fromLocation').value = toLocation;
                            document.getElementById('toLocation').value = fromLocation;
                            showError('রুটটি উল্টোভাবে দেখানো হয়েছে। লোকেশন অদলবদল করে আবার চেষ্টা করুন।');
                            return;
                        }
                    }
                }

                currentRouteData = data;
                displayResults(data);
            } else {
                showError(data.message || "এই রুটের জন্য কোনো ভাড়ার তথ্য ডাটাবেজে পাওয়া যায়নি।");
            }
        } catch (error) {
            loadingIndicator.classList.remove('active');
            searchBtn.disabled = false;
            showError("ভাড়ার তথ্য পেতে সমস্যা হচ্ছে। আবার চেষ্টা করুন।");
            console.error('Error:', error);
        }
    });

    // Update the route path display to show correct direction
    function displayRoutePath(routeBreakdown) {
        let pathHtml = '<div class="path-line"></div>';

        routeBreakdown.forEach((segment, index) => {
            const isFirst = index === 0;
            const isLast = index === routeBreakdown.length - 1;
            const isTransfer = !isLast;

            let markerClass = 'step-marker ';
            if (isFirst) markerClass += 'start';
            else if (isLast) markerClass += 'end';
            else markerClass += 'transfer';

            const stepNumber = index + 1;

            // Determine transfer message
            let transferMessage = '';
            if (isTransfer) {
                transferMessage = `
                <div class="transfer-notice">
                    <div class="transfer-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="transfer-content">
                        <h4>বাস বদলাতে হবে</h4>
                        <p><strong>${segment.to}</strong>-এ নামুন এবং পরবর্তী বাস ধরুন</p>
                        ${index === routeBreakdown.length - 2 ? 
                            '<p style="margin-top: 5px; font-size: 12px;">গন্তব্যে পৌঁছাতে শেষ সেগমেন্ট</p>' : 
                            ''}
                    </div>
                </div>
            `;
            }

            pathHtml += `
            <div class="path-step">
                <div class="${markerClass}">${stepNumber}</div>
                <div class="step-content">
                    <div class="step-header">
                        <div class="step-locations">
                            <span class="step-from">${segment.from}</span>
                            <i class="fas fa-arrow-right step-arrow"></i>
                            <span class="step-to">${segment.to}</span>
                        </div>
                        <div class="step-distance">${segment.distance_km} কিমি</div>
                    </div>
                    <div class="step-fares">
                        <div class="fare-badge regular">
                            <i class="fas fa-ticket-alt"></i>
                            <span>সাধারণ: ৳${segment.fare}</span>
                        </div>
                        <div class="fare-badge student">
                            <i class="fas fa-graduation-cap"></i>
                            <span>ছাত্র: ৳${segment.student_fare}</span>
                        </div>
                    </div>
                </div>
                ${transferMessage}
            </div>
        `;
        });

        pathVisualization.innerHTML = pathHtml;
    }

    function displayBusServices(data) {
        let busHtml = '';

        data.route_breakdown.forEach((segment, index) => {
            if (segment.operating_bus && segment.operating_bus.trim() !== '') {
                const buses = segment.operating_bus.split(',').map(bus => bus.trim()).filter(bus => bus !== '');

                if (buses.length > 0) {
                    busHtml += `
                        <div class="bus-category">
                            <div class="category-header">
                                <i class="fas fa-route"></i>
                                <span>সেগমেন্ট ${index + 1}: ${segment.from} → ${segment.to}</span>
                            </div>
                            <div class="bus-grid">
                                ${buses.map((bus, busIndex) => `
                                    <div class="bus-card">
                                        <div class="bus-header">
                                            <div class="bus-number">${busIndex + 1}</div>
                                            <div class="bus-name">${bus}</div>
                                        </div>
                                        <div class="bus-fares">
                                            <div class="bus-fare-item">
                                                <div class="bus-fare-label">সাধারণ যাত্রী</div>
                                                <div class="bus-fare-amount regular">৳${segment.fare}</div>
                                            </div>
                                            <div class="bus-fare-item">
                                                <div class="bus-fare-label">ছাত্র</div>
                                                <div class="bus-fare-amount student">৳${segment.student_fare}</div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }
            }
        });

        if (busHtml === '') {
            busHtml = `
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <i class="fas fa-bus" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>কোনো নির্দিষ্ট বাসের তথ্য পাওয়া যায়নি</p>
                    <p style="font-size: 14px;">এই রুটে চলাচলকারী যেকোনো বাস ব্যবহার করতে পারেন</p>
                </div>
            `;
        }

        busServicesContent.innerHTML = busHtml;
    }

    function displayFareBreakdown(data) {
        const routeBreakdown = data.route_breakdown;
        let totalRegularFare = 0;
        let totalStudentFare = 0;

        let breakdownHtml = '';

        routeBreakdown.forEach((segment, index) => {
            totalRegularFare += parseFloat(segment.fare);
            totalStudentFare += parseFloat(segment.student_fare);

            breakdownHtml += `
                <div class="fare-item">
                    <div class="fare-item-label">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${segment.from} → ${segment.to} (${segment.distance_km} কিমি)</span>
                    </div>
                    <div class="fare-item-amounts">
                        <div class="fare-amount">
                            <div class="fare-type">সাধারণ</div>
                            <div class="fare-value regular">৳${segment.fare}</div>
                        </div>
                        <div class="fare-amount">
                            <div class="fare-type">ছাত্র</div>
                            <div class="fare-value student">৳${segment.student_fare}</div>
                        </div>
                    </div>
                </div>
            `;
        });

        const savings = totalRegularFare - totalStudentFare;
        const savingsPercentage = ((savings / totalRegularFare) * 100).toFixed(0);

        breakdownHtml += `
            <div class="fare-total">
                <div class="fare-total-item">
                    <div class="total-label">মোট ভাড়া (সাধারণ যাত্রী)</div>
                    <div class="total-amount regular">৳${totalRegularFare.toFixed(2)}</div>
                </div>
                <div class="fare-total-item">
                    <div class="total-label">মোট ভাড়া (ছাত্র)</div>
                    <div class="total-amount student">৳${totalStudentFare.toFixed(2)}</div>
                </div>
                <div style="text-align: center; margin-top: 16px;">
                    <div class="discount-badge">
                        <i class="fas fa-percentage"></i>
                        <span>ছাত্র আইডিতে সাশ্রয় ৳${savings.toFixed(2)} (${savingsPercentage}%)</span>
                    </div>
                </div>
            </div>
        `;

        fareBreakdownContent.innerHTML = breakdownHtml;
    }

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.classList.add('active');
        errorMessage.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }

    searchAgainBtn.addEventListener('click', function() {
        resetUI();
        fareForm.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    });

    function resetUI() {
        resultsCard.classList.remove('active');
        currentRouteData = null;
        fareForm.reset();

        quickStats.style.display = 'none';
        routePathSection.style.display = 'none';
        busServicesSection.style.display = 'none';
        fareBreakdownSection.style.display = 'none';
        safetyTips.style.display = 'none';

        document.getElementById('fromSuggestions').classList.remove('active');
        document.getElementById('toSuggestions').classList.remove('active');
        errorMessage.classList.remove('active');
    }

    saveRouteBtn.addEventListener('click', async function() {
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        if (!isLoggedIn) {
            showError("রুট সংরক্ষণ করতে লগইন করুন।");
            return;
        }

        if (!resultsCard.classList.contains('active')) {
            showError("প্রথমে ভাড়া গণনা করুন।");
            return;
        }

        const fromLocation = document.getElementById('fromLocation').value.trim();
        const toLocation = document.getElementById('toLocation').value.trim();
        const distance = document.getElementById('statDistance').textContent.split(' ')[0];

        const regularFare = document.querySelector('.total-amount.regular')?.textContent.replace('৳', '') || '0';
        const studentFare = document.querySelector('.total-amount.student')?.textContent.replace('৳', '') || '0';

        let operatingBus = '';
        if (currentRouteData && currentRouteData.operating_bus) {
            operatingBus = currentRouteData.operating_bus;
        } else {
            const busNames = document.querySelectorAll('.bus-name');
            const operatingBusArray = [];
            busNames.forEach(bus => {
                operatingBusArray.push(bus.textContent);
            });
            operatingBus = operatingBusArray.join(', ');
        }

        saveRouteBtn.disabled = true;
        saveRouteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> সংরক্ষণ হচ্ছে...';

        try {
            const response = await fetch('save_route.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    from: fromLocation,
                    to: toLocation,
                    distance: distance,
                    regular_fare: regularFare,
                    student_fare: studentFare,
                    operating_bus: operatingBus
                })
            });

            const data = await response.json();

            if (data.success) {
                const successMessage = document.createElement('div');
                successMessage.className = 'error-message';
                successMessage.style.backgroundColor = 'rgba(74, 222, 128, 0.1)';
                successMessage.style.borderLeftColor = 'var(--accent-secondary)';
                successMessage.style.color = 'var(--accent-secondary)';
                successMessage.textContent = '✓ রুট সফলভাবে আপনার প্রোফাইলে সংরক্ষিত হয়েছে!';
                successMessage.classList.add('active');

                resultsCard.parentNode.insertBefore(successMessage, resultsCard);

                setTimeout(() => {
                    successMessage.classList.remove('active');
                    setTimeout(() => successMessage.remove(), 300);
                }, 3000);

                saveRouteBtn.innerHTML = '<i class="fas fa-check"></i> সংরক্ষিত হয়েছে';

                setTimeout(() => {
                    saveRouteBtn.innerHTML = '<i class="fas fa-bookmark"></i> রুট সংরক্ষণ';
                    saveRouteBtn.disabled = false;
                }, 2000);
            } else {
                showError(data.message);
                saveRouteBtn.disabled = false;
                saveRouteBtn.innerHTML = '<i class="fas fa-bookmark"></i> রুট সংরক্ষণ';
            }
        } catch (error) {
            console.error('Error:', error);
            showError("রুট সংরক্ষণ করতে সমস্যা হচ্ছে। আবার চেষ্টা করুন।");
            saveRouteBtn.disabled = false;
            saveRouteBtn.innerHTML = '<i class="fas fa-bookmark"></i> রুট সংরক্ষণ';
        }
    });

    // Debounce function to prevent multiple rapid submissions
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Preload common routes
    const routeCache = new Map();

    // Optimized form submission with debouncing
    const debouncedSearch = debounce(async function(fromLocation, toLocation) {
        // Check cache first
        const cacheKey = `${fromLocation}_${toLocation}`;
        if (routeCache.has(cacheKey)) {
            displayResults(routeCache.get(cacheKey));
            return;
        }

        loadingIndicator.classList.add('active');
        searchBtn.disabled = true;
        errorMessage.classList.remove('active');

        try {
            const response = await fetch('get_fare.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `from=${encodeURIComponent(fromLocation)}&to=${encodeURIComponent(toLocation)}`
            });

            const data = await response.json();

            loadingIndicator.classList.remove('active');
            searchBtn.disabled = false;

            if (data.success) {
                routeCache.set(cacheKey, data); // Cache the result
                currentRouteData = data;
                displayResults(data);
            } else {
                showError(data.message || "এই রুটের জন্য কোনো ভাড়ার তথ্য ডাটাবেজে পাওয়া যায়নি।");
            }
        } catch (error) {
            loadingIndicator.classList.remove('active');
            searchBtn.disabled = false;
            showError("ভাড়ার তথ্য পেতে সমস্যা হচ্ছে। আবার চেষ্টা করুন।");
            console.error('Error:', error);
        }
    }, 300); // 300ms debounce

    fareForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const fromLocation = document.getElementById('fromLocation').value.trim();
        const toLocation = document.getElementById('toLocation').value.trim();

        if (!fromLocation || !toLocation) {
            showError("যাত্রা শুরুর স্থান এবং গন্তব্যস্থল দুটোই লিখুন।");
            return;
        }

        if (fromLocation.toLowerCase() === toLocation.toLowerCase()) {
            showError("যাত্রা শুরু এবং গন্তব্য স্থান অবশ্যই ভিন্ন হতে হবে।");
            return;
        }

        if (!dhakaLocations.includes(fromLocation) || !dhakaLocations.includes(toLocation)) {
            showError("তালিকা প্রদত্ত সঠিক লোকেশন নির্বাচন করুন।");
            return;
        }

        debouncedSearch(fromLocation, toLocation);
    });

    // Optimized typeahead with debouncing
    const debouncedTypeahead = debounce(function(inputId, suggestionsId, query) {
        const suggestionsDropdown = document.getElementById(suggestionsId);
        suggestionsDropdown.innerHTML = '';

        if (query.length > 0) {
            const filteredLocations = dhakaLocations.filter(location =>
                location.toLowerCase().includes(query)
            ).slice(0, 10); // Limit to 10 suggestions for performance

            if (filteredLocations.length > 0) {
                filteredLocations.forEach(location => {
                    const suggestionItem = document.createElement('div');
                    suggestionItem.className = 'suggestion-item';
                    suggestionItem.textContent = location;
                    suggestionItem.addEventListener('click', function() {
                        document.getElementById(inputId).value = location;
                        suggestionsDropdown.classList.remove('active');
                    });
                    suggestionsDropdown.appendChild(suggestionItem);
                });
                suggestionsDropdown.classList.add('active');
            } else {
                suggestionsDropdown.classList.remove('active');
            }
        } else {
            suggestionsDropdown.classList.remove('active');
        }
    }, 200);

    function setupTypeahead(inputId, suggestionsId) {
        const input = document.getElementById(inputId);

        input.addEventListener('input', function() {
            const query = input.value.toLowerCase().trim();
            debouncedTypeahead(inputId, suggestionsId, query);
        });

        // Rest of your existing typeahead code...
        document.addEventListener('click', function(event) {
            if (!input.contains(event.target) && !document.getElementById(suggestionsId).contains(event.target)) {
                document.getElementById(suggestionsId).classList.remove('active');
            }
        });

        input.addEventListener('focus', function() {
            if (input.value === '') {
                const suggestionsDropdown = document.getElementById(suggestionsId);
                suggestionsDropdown.innerHTML = '';

                // Show only first 10 locations for performance
                dhakaLocations.slice(0, 10).forEach(location => {
                    const suggestionItem = document.createElement('div');
                    suggestionItem.className = 'suggestion-item';
                    suggestionItem.textContent = location;
                    suggestionItem.addEventListener('click', function() {
                        input.value = location;
                        suggestionsDropdown.classList.remove('active');
                    });
                    suggestionsDropdown.appendChild(suggestionItem);
                });
                suggestionsDropdown.classList.add('active');
            }
        });
    }
</script>

<?php
$conn->close();
include_once 'footer.php';
?>