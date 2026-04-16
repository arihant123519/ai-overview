@extends('layouts.page-app')

@section("content")
<style>
    
    .search-header {
        border-bottom: 1px solid var(--border-color);
        padding: 20px 0;
    }

    .search-form-container {
        max-width: 692px;
    }

    .logo {
        font-size: 1.8rem;
        font-weight: 500;
        color: var(--primary-color);
    }

    .search-input-group {
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 8px 16px;
        transition: box-shadow 0.2s;
    }

    .search-input-group:focus-within {
        box-shadow: 0 1px 6px rgba(32, 33, 36, 0.28);
    }

    .search-input {
        border: none;
        outline: none;
        flex: 1;
    }

    .search-input:focus {
        box-shadow: none;
    }

    .search-button {
        background-color: var(--primary-color);
        color: white;
        border-radius: 4px;
        padding: 8px 16px;
        font-size: 14px;
        border: none;
    }

    .search-tabs {
        border-bottom: 1px solid var(--border-color);
    }

    .search-tab {
        color: var(--secondary-color);
        padding: 12px 16px;
        text-decoration: none;
        border-bottom: 3px solid transparent;
    }

    .search-tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    .search-tab:hover {
        color: var(--primary-color);
    }

    .search-results-container {
        max-width: 692px;
        margin: 0 auto;
    }

    .result-stats {
        color: #70757a;
        font-size: 14px;
        margin-bottom: 20px;
    }

    .search-result {
        margin-bottom: 26px;
    }

    .result-url {
        color: #202124;
        font-size: 14px;
        display: flex;
        align-items: center;
        margin-bottom: 4px;
    }

    .result-title {
        color: var(--primary-color);
        font-size: 20px;
        font-weight: 400;
        line-height: 1.3;
        margin-bottom: 8px;
        text-decoration: none;
    }

    .result-title:hover {
        text-decoration: underline;
    }

    .result-snippet {
        color: #4d5156;
        line-height: 1.58;
        font-size: 14px;
    }

    .result-meta {
        color: #70757a;
        font-size: 12px;
    }

    .ai-overview {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        border-left: 3px solid var(--primary-color);
    }

    .ai-overview h3 {
        font-size: 18px;
        margin-bottom: 10px;
        color: #202124;
    }

    .people-ask-section {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 30px;
    }

    .people-ask-title {
        padding: 16px 20px;
        background-color: var(--light-gray);
        font-weight: 500;
        margin: 0;
    }

    .people-ask-item {
        padding: 16px 20px;
        border-top: 1px solid var(--border-color);
    }

    .people-ask-question {
        font-weight: 500;
        margin-bottom: 8px;
        color: #202124;
    }

    .people-ask-answer {
        color: #4d5156;
        margin-bottom: 0;
    }

    .related-searches {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-top: 30px;
    }

    .related-searches h4 {
        font-size: 16px;
        margin-bottom: 15px;
        color: #202124;
    }

    .related-search-item {
        display: block;
        color: var(--primary-color);
        text-decoration: none;
        padding: 6px 0;
    }

    .related-search-item:hover {
        text-decoration: underline;
    }

    .footer {
        background-color: var(--light-gray);
        padding: 20px 0;
        border-top: 1px solid var(--border-color);
        margin-top: 40px;
    }

    .pagination-container {
        margin-top: 40px;
    }

    .page-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        color: var(--primary-color);
        text-decoration: none;
        margin-right: 8px;
        border-radius: 4px;
    }

    .page-number.active {
        background-color: var(--primary-color);
        color: white;
    }

    .page-number:hover {
        background-color: rgba(26, 115, 232, 0.1);
    }

    @media (max-width: 768px) {
        .search-form-container {
            padding: 0 15px;
        }

        .search-results-container {
            padding: 0 15px;
        }

        .search-tabs {
            overflow-x: auto;
            flex-wrap: nowrap;
        }
    }

    .ai-overview {
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
    }

    .ai-overview .card-title {
        color: #1a0dab;
        font-size: 1.5rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
    }

    .ai-overview h4 {
        color: #1a0dab;
        font-size: 1.2rem;
        font-weight: 500;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }

    .ai-overview p {
        color: #4d5156;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .ai-overview ul {
        margin-bottom: 1.5rem;
    }

    .ai-overview li {
        color: #4d5156;
        line-height: 1.6;
        margin-bottom: 0.5rem;
    }

    .ai-overview .bi-dot {
        color: #70757a;
    }

    .references-section {
        border-top: 1px solid #ddd;
        padding-top: 1.5rem;
        margin-top: 1.5rem;
    }

    .references-section h5 {
        color: #70757a;
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .reference-item a {
        color: #1a0dab;
        font-size: 0.9rem;
    }

    .reference-item a:hover {
        text-decoration: underline;
    }

    .reference-item .badge {
        font-size: 0.75rem;
        padding: 0.2em 0.4em;
    }

    .paragraph-with-thumbnail img {
        border-radius: 4px;
    }

    .related-searches .badge {
        padding: 0.35em 0.65em;
        font-weight: normal;
        border: 1px solid #dadce0;
    }

    .related-searches .badge:hover {
        background-color: #f1f3f4 !important;
    }

    .result-stats {
        color: #70757a;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }


    /* Add a loading spinner style */
    .sync-loading {
        display: none;
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #1a73e8;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .btn-sync-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #sync_now i {
        margin-right: 5px;
    }

    /* AI Overview selector styles */
    .ai-selector-wrapper {
        position: relative;
    }

    .ai-selector-loading {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        display: none;
    }
    .search-container {
        display: flex;
        gap: 20px;
        margin-top: 30px;
    }

    .left-results {
        width: 65%;
    }

    .right-panel {
        width: 30%;
        background: #fafafa;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #eee;
    }

    .result-item {
        margin-bottom: 25px;
    }

    .result-item a {
        font-size: 20px;
        color: #1a0dab;
        text-decoration: none;
    }

    .result-item .result-link {
        font-size: 14px;
        color: #006621;
    }

    .knowledge-title {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .knowledge-field {
        margin-bottom: 8px;
        font-size: 14px;
    }

    /* Dynamic Pills Navigation Styles */
    #dynamicPillsNav {
        display: flex;
        flex-wrap: wrap;
        gap: 0;
    }
    
     #dynamicPillsNav .nav-item {
        display: inline-block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    .nav-pill.disabled-tab {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .nav-pill.disabled-tab:hover {
        transform: none;
        box-shadow: none;
    }
    
    .nav-pill {
        border-radius: 20px;
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 2px solid #0d6efd;
    }
    
    .nav-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
    }
    
    .nav-pill.active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
    }
    
    .nav-pill i {
        font-size: 12px;
    }
    
    /* Animation for showing pills */
    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .nav-item[style*="display: block"],
    .nav-item[style*="display: inline-block"] {
        animation: fadeInScale 0.3s ease-out;
    }

    /* Add to your existing styles */
    #medianTableSection, 
    #keywordsTableSection, 
    #keywordPlannerTableSection {
        will-change: transform;
        contain: content;
    }

    .table-responsive {
        -webkit-overflow-scrolling: touch;
    }

    /* Improve badge rendering */
    .badge {
        display: inline-block;
        transform: translateZ(0);
        backface-visibility: hidden;
    }
    /* Median Table Loading Styles */
    .median-loading {
        min-height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .median-loading .spinner-border {
        width: 3rem;
        height: 3rem;
    }

    .median-loading-content {
        text-align: center;
        max-width: 400px;
    }

    .median-progress {
        width: 200px;
        height: 6px;
        margin: 20px auto;
    }

    /* ── Sync button ── */
    .btn-sync {
        border-radius: 5px;
        padding: 7px 18px;
        font-size: 14px;
        font-weight: 500;
        border: 2px solid #198754;
        cursor: pointer;
        transition: all .25s;
        background: #fff;
        color: #198754;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-sync:hover:not(:disabled) {
        background: #198754;
        color: #fff;
        box-shadow: 0 4px 10px rgba(25,135,84,.3);
        transform: translateY(-1px);
    }
    .btn-sync:disabled {
        opacity: .65;
        cursor: not-allowed;
    }
    .btn-sync.syncing {
        background: #198754;
        color: #fff;
        pointer-events: none;
    }
    .btn-sync .sync-spinner {
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: syncSpin .7s linear infinite;
        display: none;
    }
    .btn-sync.syncing .sync-spinner { display: inline-block; }
    /* .btn-sync.syncing .sync-icon    { display: none; } */
    @keyframes syncSpin { to { transform: rotate(360deg); } }
</style>
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Add Keyword Request</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{url('view-client/' . $id)}}">Keyword List</a></li>
                            <li class="breadcrumb-item active">Add Keyword</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="live-preview">
                            <form id="keywordFilterForm" class="row g-3">
                                <input type="hidden" name="client_property_id" value="{{ $id }}">
                                <input type="hidden" name="domainmanagement_id" value="{{ $domainmanagement_id }}">
                                
                                <!-- Radio buttons for Domain/Keyword selection -->

                                <div class="col-md-12">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="filter_type" id="filterDomain" value="domain" checked>
                                        <label class="form-check-label" for="filterDomain">Domain</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="filter_type" id="filterKeyword" value="keyword">
                                        <label class="form-check-label" for="filterKeyword">Keyword</label>
                                    </div>
                                </div>

                                <!-- Domain Input (visible by default) -->
                                <div class="col-md-12" id="domainInputGroup">
                                    <label class="form-label">Domain Name</label>
                                    <input type="text" class="form-control" name="domain_name" value="{{ $domain_name }}" readonly>
                                </div>

                                <!-- Keyword Input (hidden by default) -->
                                <div class="col-md-12" id="keywordInputGroup" style="display: none;">
                                    <label class="form-label">Keyword</label>
                                    <input type="text" class="form-control" name="master_keyword" value="" placeholder="Enter keyword to filter">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Median Limits</label>
                                    <input type="number" name="median_limit" id="median_limit" value="10" class="form-control">
                                </div>

                                <div class="col-md-3" id="monthly_searches" style="display: none;">
                                    <label class="form-label">Monthly Searches</label>
                                    <div class="row g-2">
                                        <div class="col">
                                            <input type="number" name="min_searches" class="form-control" placeholder="Min" min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" name="max_searches" class="form-control" placeholder="Max" min="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2" id="competition" style="display: none;">
                                    <label class="form-label">Competition</label>
                                    <select name="competition" class="form-select">
                                        <option value="">All</option>
                                        <option value="LOW">Low</option>
                                        <option value="MEDIUM">Medium</option>
                                        <option value="HIGH">High</option>
                                    </select>
                                </div>

                                <div class="col-md-3" id="big_range" style="display: none;">
                                    <label class="form-label">Bid Range ($)</label>
                                    <div class="row g-2">
                                        <div class="col">
                                            <input type="number" step="0.01" name="min_bid" class="form-control" placeholder="Min Low Bid" min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" step="0.01" name="max_bid" class="form-control" placeholder="Max High Bid" min="0">
                                        </div>
                                    </div>
                                </div>
                                <!-- GSC Performance Filters -->
                                <div class="col-md-2 filter-item" id="clicks" style="display: none;">
                                    <label class="form-label">Clicks</label>
                                    <div class="row g-2">
                                        <div class="col">
                                            <input type="number" name="min_clicks" class="form-control" placeholder="Min" min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" name="max_clicks" class="form-control" placeholder="Max" min="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2 filter-item" id="ctr" style="display: none;">
                                    <label class="form-label">CTR (%)</label>
                                    <div class="row g-2">
                                        <div class="col">
                                            <input type="number" step="0.01" name="min_ctr" class="form-control" placeholder="Min" min="0" max="100">
                                        </div>
                                        <div class="col">
                                            <input type="number" step="0.01" name="max_ctr" class="form-control" placeholder="Max" min="0" max="100">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2 filter-item" id="impressions" style="display: none;">
                                    <label class="form-label">Impressions</label>
                                    <div class="row g-2">
                                        <div class="col">
                                            <input type="number" name="min_impressions" class="form-control" placeholder="Min" min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" name="max_impressions" class="form-control" placeholder="Max" min="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2 filter-item">
                                    <label class="form-label">Date From</label>
                                    <input type="date" name="date_from" id="filter_date_from" class="form-control" 
                                        value="<?php echo date('Y-m-d', strtotime('-90 days')) ?>"
                                        max="<?php echo date('Y-m-d', strtotime('-1 day')) ?>">
                                </div>
                                <div class="col-md-2 filter-item">
                                    <label class="form-label">Date To</label>
                                    <input type="date" name="date_to" id="filter_date_to" class="form-control" 
                                        value="<?php echo date('Y-m-d', strtotime('-1 day')) ?>"
                                        max="<?php echo date('Y-m-d', strtotime('-1 day')) ?>">
                                </div>
                                <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="">
                                        <button class="btn btn-primary" id="show_result">Show the Result</button>
                                        <a target="_blank" class="btn btn-warning" href="/median-results/{{$id}}">Median Results</a>
                                    </div>
                                        <button id="syncQueueBtn"
                                                class="btn-sync"
                                                type="button"
                                                onclick="triggerQueueSync(this)"
                                                title="Re-process any pending keyword jobs in the queue">
                                            <span class="sync-spinner"></span>
                                            <i class="fas fa-sync-alt sync-icon"></i>
                                            <span class="sync-label">Sync</span>
                                        </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
            <div class="col-md-12">
                <div id="result_box" class="mt-4"></div>
            </div>

        </div>
        <!-- end row -->
    </div> <!-- container-fluid -->
    <!-- Modal for fetching more keywords -->
    <div class="modal fade" id="fetchMoreModal" tabindex="-1" aria-labelledby="fetchMoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fetchMoreModalLabel">Fetch More Keywords</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="aioResultsMessage">
                        We're getting this much AIO result having <span id="currentKeywordCount">0</span> 
                        out of targeted median limit (<span id="targettedKeywordCount">0</span>). 
                        Required are <span id="remainingKeywordCount">0</span>.
                    </p>
                    <div id="fetchProgress" class="d-none mt-3">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                        </div>
                        <p class="text-center mt-2" id="progressText">Fetching keywords...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Current Results</button>
                    <button type="button" class="btn btn-primary" id="confirmFetchMore">Yes, Fetch More Keywords</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1056"></div>
@endsection

@section("jscontent")

<script>
    window.dataFetched = false; // guards against accidental refresh
    let currentOpenModal = null; 
    window.keywordPlannerPollingInterval = null;
    $(document).ready(function() {
    const masterKeywordInput = $('input[name="master_keyword"]');
    const showResultButton = $('#show_result');
    const filterTypeRadios = $('input[name="filter_type"]');
    
    // Function to check and update button state
    const keywordOnlyFilters = $('#monthly_searches, #competition, #bid_range, #ctr, #impressions, #clicks');

    function toggleFilters() {
            const checkedRadio = $('input[name="filter_type"]:checked');
            const isDomainMode = checkedRadio.val() === 'domain';
            const isKeywordMode = checkedRadio.val() === 'keyword';
            
            // Toggle keyword input visibility
            if (isDomainMode) {
                $('#domainInputGroup').show();
                $('#keywordInputGroup').hide();
                
                // Hide keyword-only filters
                keywordOnlyFilters.hide();
                
                // Clear keyword-only filter values
                $('input[name="min_searches"], input[name="max_searches"]').val('');
                $('select[name="competition"]').val('');
                $('input[name="min_bid"], input[name="max_bid"]').val('');
                $('input[name="min_ctr"], input[name="max_ctr"]').val('');
                $('input[name="min_clicks"], input[name="max_clicks"]').val('');
                $('input[name="min_impressions"], input[name="max_impressions"]').val('');
                
                // Show basic filters
                $('.filter-item:not(#monthly_searches, #competition, #bid_range, #ctr, #impressions, #clicks)').show();
                
            } else if (isKeywordMode) {
                $('#domainInputGroup').hide();
                $('#keywordInputGroup').show();
                
                // Show all filters
                $('.filter-item').show();
                keywordOnlyFilters.show();
            }
        }


    function checkButtonState() {
        const checkedRadio = $('input[name="filter_type"]:checked');
        let shouldDisable = false;
        
        if (checkedRadio.length > 0 && checkedRadio.val() === 'keyword') {
            const keywordValue = masterKeywordInput.val().trim();
            if (keywordValue === '') {
                shouldDisable = true;
            }
        }
        
        showResultButton.prop('disabled', shouldDisable);
        
        // Visual feedback
        if (shouldDisable) {
            showResultButton.css({
                'opacity': '0.6',
                'cursor': 'not-allowed'
            });
        } else {
            showResultButton.css({
                'opacity': '1',
                'cursor': 'pointer'
            });
        }
    }
    
    // Event listeners
    toggleFilters();
    checkButtonState();

    masterKeywordInput.on('input', checkButtonState);
    filterTypeRadios.on('change', checkButtonState);

    filterTypeRadios.on('change', function() {
            toggleFilters();
            checkButtonState();
            
            // Clear results when switching modes
            $("#result_box").html('');
            
            // Clear all filter values
            if ($(this).val() === 'domain') {
                // Keep only domain-specific filters
                $('input[name="master_keyword"]').val('');
            } else if ($(this).val() === 'keyword') {
                // Keep all filters
                // Optionally clear domain-specific filters if needed
            }
        });

    
    // Prevent click when disabled
    showResultButton.on('click', function(e) {
            if ($(this).prop('disabled')) {
                e.preventDefault();
                e.stopPropagation();
                
                if ($('input[name="filter_type"]:checked').val() === 'keyword') {
                    alert('Please enter a keyword to search');
                    masterKeywordInput.focus();
                }
                return false;
            }
        });

});
    document.addEventListener('livewire:init', () => {
        console.log('Livewire initialized');
    });
    

    
    $("#show_result").on("click", function(e) {
        e.preventDefault();
        
        let formData = $("#keywordFilterForm").serialize();
        const mode = $('input[name="filter_type"]:checked').val();
        formData += '&mode=' + mode;

        $.ajax({
            url: "{{ route('keyword-store') }}",
            type: "POST",
            data: formData,
            beforeSend: function() {
                $("#result_box").html(`
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-3">
                            <h5>Loading Keywords...</h5>
                            <p class="text-muted mt-2">Fetching keywords and starting processing...</p>
                        </div>
                    </div>
                `);
            },
            success: function(response) {
                console.log(response);
                $("#result_box").html(response.html);
                window.dataFetched = true;
                setTimeout(function() {
                    var syncBtn = document.getElementById('syncQueueBtn');
                    if (syncBtn) {
                        triggerQueueSync(syncBtn);
                    }
                }, 1500);
                // Store session ID for status checking
                sessionId = response.session_id;
                
                // Store keyword_request_id
                if (response.data && response.data.keyword_request_id) {
                    sessionStorage.setItem('keyword_request_id', response.data.keyword_request_id);
                }

                // Start polling for status updates every 5 seconds
                statusCheckInterval = setInterval(checkAllKeywordStatuses, 5000);
                
                // Initial check
                setTimeout(checkAllKeywordStatuses, 2000);
                
                // Initialize dynamic pills after a short delay
                setTimeout(function() {
                    if (typeof window.dynamicPills !== 'undefined') {
                        window.dynamicPills.init();
                    }
                }, 1000);
                
                // Show fetch more modal if needed
                if (response.total_keywords && response.total_keywords < 1000 && response.is_full_results === false) {
                    setTimeout(() => {
                        showFetchMoreModal(response.total_keywords, response.remaining_keywords);
                    }, 1000);
                }
                
                // Setup new observer after content loads
                setTimeout(() => {
                    // Move keyword planner and remaining tables into keywordsTableSection
                    const keywordPlannerSection = $('#keywordPlannerTableSection');
                    const keywordsSection = $('#keywordsTableSection');
                    
                    if (keywordPlannerSection.length && keywordsSection.length) {
                        // Move the entire keyword planner card into keywords section
                        keywordPlannerSection.appendTo(keywordsSection.closest('.card-body'));
                        keywordPlannerSection.removeClass('d-none');
                        
                        // Update pill text to indicate all data is shown
                        $('#pill-keywords').html('<i class="fas fa-table me-1"></i>Keyword Results (All Data)');
                    }
                    
                    // Same for remaining keywords table
                    const remainingCard = $('#remainingkeywordsTable').closest('.card');
                    if (remainingCard.length && keywordsSection.length) {
                        remainingCard.appendTo(keywordsSection.closest('.card-body'));
                        remainingCard.removeClass('d-none');
                    }
                }, 1500);
            },
            error: function(xhr) {
                console.log(xhr.responseText);
                $("#result_box").html("<p class='text-danger'>Something went wrong!</p>");
            }
        });
    });

    $(window).on('beforeunload', function(e) {
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }
        if (window.pillsNavigationActive || window.dataFetched) {
            const msg = 'You have unsaved keyword data. If you leave or refresh, all fetched data will be lost!';
            e.preventDefault();
            e.returnValue = msg;
            return msg;
        }
    });
    function showFetchMoreModal(currentCount, remainingCount) {
        console.log(currentCount, remainingCount);
        $('#currentKeywordCount').text(currentCount);
        $('#remainingKeywordCount').text(remainingCount);
        $('#targettedKeywordCount').text(currentCount+remainingCount);
        
        const modal = new bootstrap.Modal(document.getElementById('fetchMoreModal'));
        modal.show();
    }

// Handle the fetch more action



    function fetchAioResult(clickedButton, keyword) {
        
        // Get the row data
        let keyword_planner_id = null;

        const row = clickedButton.closest('tr');
        const positionCell = row.cells[8];
        const isSet = positionCell && positionCell.textContent.trim() !== '';

        let keywordData;

        if (isSet) {
            // SET (full columns exist)
            // INSTEAD of reading textContent by cell index, use data attributes:
            keywordData = {
                keyword:       keyword,
                monthly_search: row.querySelector('td[data-avg_monthly_searches]')?.dataset.avg_monthly_searches || 0,
                competition:   row.querySelector('td[data-competition]')?.dataset.competition || '',
                low_bid:       row.querySelector('td[data-low_top_of_page_bid]')?.dataset.low_top_of_page_bid || 0,
                high_bid:      row.querySelector('td[data-high_top_of_page_bid]')?.dataset.high_top_of_page_bid || 0,
                clicks:        row.querySelector('td[data-clicks]')?.dataset.clicks || 0,
                ctr:           row.querySelector('td[data-ctr]')?.dataset.ctr || 0,
                impressions:   row.querySelector('td[data-impressions]')?.dataset.impressions || 0,
                position:      row.querySelector('td[data-position]')?.dataset.position || 0,
            };
        } else {
            // NOT SET (limited columns)
            keywordData = {
                keyword: keyword,
                clicks: row.cells[1].textContent.trim(),
                ctr: row.cells[2].textContent.trim().replace('%', ''),
                impressions: row.cells[3].textContent.trim(),
                position: row.cells[4].textContent.trim(),
            };
        }
        
        // Get form data
        const client_property_id = $("input[name='client_property_id']").val();
        const domainmanagement_id = $("input[name='domainmanagement_id']").val();
        
        // Get keyword_request_id from sessionStorage
        const keyword_request_id = @json(session('keyword_request_id'));

        // Disable button
        clickedButton.disabled = true;
        clickedButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...';
        
        $.ajax({
            url: "{{ route('get.aio.result') }}", // Create this route
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                keyword: keyword,
                keyword_data: keywordData,
                keyword_request_id: keyword_request_id,
                client_property_id: client_property_id,
                domainmanagement_id: domainmanagement_id
            },
            success: function(response) {
                if (response.success) {
                    keyword_planner_id = response.keyword_planner_id;
                    showAioModalDirect(response.data);

                } else {
                    alert('Error: ' + response.message);
                    resetButton(clickedButton);
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                alert('Failed to fetch AIO result');
                resetButton(clickedButton);
            }
        });
    }
    function showAioModalDirect(data) {
        
        // Create and show modal with data
        const modalHtml = `
        <div class="modal fade" id="aioDirectModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${data.ai_overview ? 'AIO Insights' : 'Search Insights'} for: "${data.keyword}"</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${data.ai_overview ? 
                            `<div class="p-3 bg-light rounded border">
                                ${data.ai_overview.markdown ? data.ai_overview.markdown : 'No AI Overview available'}
                            </div>` 
                            : 
                            '<p class="text-muted">No AI Overview available for this keyword. Showing search insights instead.</p>'
                        }
                        <div class="mt-3">
                            <p><strong>Keyword:</strong> ${data.keyword}</p>
                            <p><strong>Status:</strong> ${data.ai_overview ? 'AIO Found ✓' : 'AIO Not Found'}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a target="_blank" href="/extracted-aio-result/${data.keyword_planner_id}" class="btn ${data.ai_overview ? 'btn-success' : 'btn-warning'}">
                            ${data.ai_overview ? 'View AIO Insights' : 'View Search Insights'}
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;

        
        // Remove existing modal
        $('#aioDirectModal').remove();
        
        // Add new modal
        $('body').append(modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('aioDirectModal'));
        modal.show();
        
        // Reset button when modal is hidden
        keyword_planner_id = data.keyword_planner_id;

        $('#aioDirectModal').on('hidden.bs.modal', function () {
            
            document.querySelectorAll('.get-aio-btn[disabled]').forEach(btn => {
                // Create a new link element
                const link = document.createElement('a');
                link.href = `/extracted-aio-result/${keyword_planner_id}`;
                link.className = `btn ${data.ai_overview ? 'btn-primary' : 'btn-warning'}`;
                link.textContent = data.ai_overview ? 'AIO Insights' : 'Search Insights';
        
                link.target = '_blank';
                
                // Replace the button with the link
                btn.parentNode.replaceChild(link, btn);
        
            });

            $(this).remove();
        });
    }

function resetButton(button) {
    button.disabled = false;
    button.innerHTML = 'Get AIO Result';
}
function triggerQueueSync(btn) {
    if (!btn) btn = document.getElementById('syncQueueBtn');
    if (!btn) return;
 
    // Visual: enter syncing state
    btn.disabled = true;
    btn.classList.add('syncing');
    const label = btn.querySelector('.sync-label');
    if (label) label.textContent = 'Syncing…';
 
    $.ajax({
        url: "{{ route('sync.queue') }}",   // POST /sync-queue
        type: 'POST',
        data: { _token: "{{ csrf_token() }}" },
 
        success: function(response) {
            // Exit syncing state
            btn.classList.remove('syncing');
            btn.disabled = false;
            if (label) label.textContent = 'Sync';
 
            if (response.success) {
                // Kick off an immediate status poll so the table refreshes
                if (typeof checkAllKeywordStatuses === 'function') {
                    setTimeout(checkAllKeywordStatuses, 500);
                }
 
                // Optional: brief green pulse to confirm success
                btn.classList.add('btn-sync--done');
                setTimeout(() => btn.classList.remove('btn-sync--done'), 2000);
            } else {
                console.warn('Sync warning:', response.message);
                alert('Sync returned a warning: ' + (response.message || 'Unknown'));
            }
        },
 
        error: function(xhr) {
            btn.classList.remove('syncing');
            btn.disabled = false;
            if (label) label.textContent = 'Sync';
            console.error('Sync error:', xhr.responseText);
            alert('Queue sync failed. Check the server logs for details.');
        }
    });
}

    
document.addEventListener('livewire:init', () => {
    // console.log(data);
    Livewire.on('aioModalClosed', (data) => {
        const keyword = data.keyword || data;
        resetButtonState(keyword);
    });
    
    Livewire.on('aioModalError', (data) => {
        const keyword = data.keyword || data;
        resetButtonState(keyword);
    });
});
    function resetButtonState(keyword) {
    const button = document.querySelector(`button[onclick*="${keyword}"]`);
    if (button) {
        button.disabled = false;
        button.innerHTML = 'Get AIO Result';
        button.classList.remove('disabled');
    }
}

    document.addEventListener('hidden.bs.modal', function (event) {
        if (event.target.id === 'aioModal') {
            // Find all loading buttons and reset them
            document.querySelectorAll('.get-aio-btn[disabled]').forEach(button => {
                button.disabled = false;
                button.innerHTML = 'Get AIO Result';
                button.classList.remove('disabled');
            });
        }
    });
    function showAioModal(status, data = null) {
        // Implement modal display logic here
        
        const modalEl = document.getElementById('aioModal');
        const modal = new bootstrap.Modal(modalEl, {
            backdrop: 'static', // optional
            keyboard: true
        });

        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const domainInputGroup = document.getElementById('domainInputGroup');
        const keywordInputGroup = document.getElementById('keywordInputGroup');
        const domainInput = document.querySelector('input[name="domain_name"]');
        const keywordInput = document.querySelector('input[name="master_keyword"]');

        const filterTypeRadios = document.querySelectorAll('input[name="filter_type"]');

        function toggleInputs() {
            const checkedRadio = document.querySelector('input[name="filter_type"]:checked');

            if (checkedRadio && checkedRadio.value === 'domain') {
                domainInputGroup.style.display = 'block';
                keywordInputGroup.style.display = 'none';
                
                // Enable and make domain required
                domainInput.disabled = false;
                domainInput.required = true;
                domainInput.readOnly = true;
                
                // Disable and remove requirement from keyword
                keywordInput.disabled = true;
                keywordInput.required = false;
                $("#result_box").html('');
                
            } else if (checkedRadio && checkedRadio.value === 'keyword') {
                domainInputGroup.style.display = 'none';
                keywordInputGroup.style.display = 'block';
                
                // Enable and make keyword required
                keywordInput.disabled = false;
                keywordInput.required = true;
                keywordInput.readOnly = false;
                
                // Disable and remove requirement from domain
                domainInput.disabled = true;
                domainInput.required = false;
                domainInput.readOnly = true;
                $("#result_box").html('');
            }
        }

        filterTypeRadios.forEach(radio => {
            radio.addEventListener('change', toggleInputs);
        });

        toggleInputs();
    });
</script>

<script>
// Replace the entire $(document).ready(function() { section with this:

$(document).ready(function() {
    // Global state management
    window.medianState = {
        hasAioResults: false,
        medianLimitReached: false,
        modalShown: false,
        userForcedShow: false
    };

    // Performance optimization
    window.performanceOptimizer = {
        updateQueue: [],
        updateTimer: null,
        observer: null,
        
        addUpdate: function($element, html) {
            this.updateQueue.push({ $element, html });
            this.scheduleProcess();
        },
        
        scheduleProcess: function() {
            if (this.updateTimer) {
                clearTimeout(this.updateTimer);
            }
            this.updateTimer = setTimeout(() => this.processQueue(), 100);
        },
        
        processQueue: function() {
            if (this.updateQueue.length === 0) return;
            
            // Use requestAnimationFrame for smoother updates
            requestAnimationFrame(() => {
                const fragment = document.createDocumentFragment();
                const tempDiv = document.createElement('div');
                
                this.updateQueue.forEach(({ $element, html }) => {
                    if ($element.length) {
                        tempDiv.innerHTML = html;
                        while (tempDiv.firstChild) {
                            fragment.appendChild(tempDiv.firstChild);
                        }
                        $element[0].innerHTML = '';
                        $element[0].appendChild(fragment.cloneNode(true));
                    }
                });
                
                this.updateQueue = [];
                this.updateTimer = null;
            });
        }
    };

    /**
     * Check if we have at least one AIO result with "Yes"
     */
    window.checkAioResults = function() {
        let hasAioYes = false;
        let aioYesCount = 0;
        
        // ✅ Check keywordsTable using DataTable API (ALL pages)
        if (typeof keywordsTable !== 'undefined' && $.fn.DataTable.isDataTable('#keywordsTable')) {
            keywordsTable.rows({ page: 'all', search: 'applied' }).every(function() {
                const $row = $(this.node());
                const aioBadge = $row.find('.aio-status .badge');
                if (aioBadge.length) {
                    const status = aioBadge.text().trim();
                    if (status === 'Yes' || status === 'Done') {
                        hasAioYes = true;
                        aioYesCount++;
                    }
                }
            });
        }
        
        // Check other tables (no pagination)

        $('#keywordPlannerTable .aio-status .badge').each(function() {
            const status = $(this).text().trim();
            if (status === 'Yes' || status === 'Done') {
                hasAioYes = true;
                aioYesCount++;
            }
        });
        
        $('#remainingkeywordsTable .aio-status .badge').each(function() {
            const status = $(this).text().trim();
            if (status === 'Yes' || status === 'Done') {
                hasAioYes = true;
                aioYesCount++;
            }
        });
        
        console.log(`📊 AIO Count Check: ${aioYesCount} keywords with AIO "Yes" found`);
        
        return { hasAioYes, aioYesCount };
    };

    /**
     * Check median limit and show modal if needed
     */
    window.checkMedianAndShowModal = function() {
        const { hasAioYes, aioYesCount } = window.checkAioResults();
        const medianLimit = parseInt($('#median_limit').val()) || 10;
        
        window.medianState.hasAioResults = hasAioYes;
        
        // Don't proceed if no AIO results at all
        if (!hasAioYes) {
            return false;
        }
        
        // Check if we have enough AIO results for median limit
        if (aioYesCount < medianLimit && !window.medianState.modalShown) {
            
            // Show modal before showing Median tab
            showMedianLimitModal(medianLimit, aioYesCount, medianLimit - aioYesCount);
            window.medianState.modalShown = true;
            return false;
        }
        
        // Enough AIO results or user forced show
        window.medianState.medianLimitReached = true;
        return true;
    };

    /**
     * Improved sync function with performance optimization
     */
    window.syncAioKeywordsToMedianTable = function() {
        // Don't run if median table doesn't exist
        if ($('#medianTable tbody#medianTableBody').length === 0) {
            return;
        }
        
        // Check conditions before proceeding
        if (!window.medianState.hasAioResults) {
            const checkResult = window.checkAioResults();
            if (!checkResult.hasAioYes) {
                
                // Update pills if needed
                if (typeof window.dynamicPills !== 'undefined') {
                    window.dynamicPills.update();
                }
                return;
            }
        }
        
        // Use debouncing to prevent too frequent updates
        if (window.syncDebounceTimer) {
            clearTimeout(window.syncDebounceTimer);
        }
        
        window.syncDebounceTimer = setTimeout(() => {
            requestAnimationFrame(() => {
                performMedianSync();
            });
        }, 300);
    };
    
    function performMedianSync() {
        let aioKeywords = [];
        const medianLimit = parseInt($('#median_limit').val()) || 10;
        const { hasAioYes, aioYesCount } = window.checkAioResults();
        
        if (!hasAioYes) {
            $('#medianTable tbody#medianTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No keywords with AIO Insights found yet.
                        </div>
                    </td>
                </tr>
            `);
            return;
        }
        
        // Collect AIO keywords from all tables (optimized)
        collectAioKeywords(aioKeywords);
        
        if (aioKeywords.length === 0) {
            return;
        }
        
        // Sort and limit
        aioKeywords.sort((a, b) => a.monthly_search - b.monthly_search);
        
        let limitedAioKeywords = [];
        if (aioKeywords.length <= medianLimit || window.medianState.userForcedShow) {
            limitedAioKeywords = aioKeywords.slice(0, medianLimit);
        } else {
            const middleIndex = Math.floor(aioKeywords.length / 2);
            const halfLimit = Math.floor(medianLimit / 2);
            const startIndex = Math.max(0, middleIndex - halfLimit);
            const endIndex = Math.min(aioKeywords.length, startIndex + medianLimit);
            limitedAioKeywords = aioKeywords.slice(startIndex, endIndex);
        }
        
        // Update median table with performance optimization
        updateMedianTable(limitedAioKeywords, aioKeywords.length, medianLimit);
        
    }
    
    function collectAioKeywords(aioKeywords) {
        // Optimized collection using querySelectorAll for better performance
        const tables = [
            { selector: '#keywordsTable', type: 'main', isDataTable: true },
            { selector: '#keywordPlannerTable', type: 'planner' },
            { selector: '#remainingkeywordsTable', type: 'remaining' }
        ];
        
        tables.forEach(({ selector, type, isDataTable }) => {
            if (isDataTable && $.fn.DataTable.isDataTable(selector)) {
                // Use DataTables API for keywordsTable
                const table = $(selector).DataTable();
                table.rows().every(function() {
                    const row = this.node();
                    const aioBadge = row.querySelector('.aio-status .badge');
                    if (aioBadge && (aioBadge.textContent.trim() === 'Yes' || aioBadge.textContent.trim() === 'Done')) {
                        const data = extractRowData(row, type);
                        if (data) aioKeywords.push(data);
                    }
                });
            } else {
                // Fallback to DOM for non-DataTables
                const rows = document.querySelectorAll(`${selector} tbody tr`);
                rows.forEach(row => {
                    const aioBadge = row.querySelector('.aio-status .badge');
                    if (aioBadge && (aioBadge.textContent.trim() === 'Yes' || aioBadge.textContent.trim() === 'Done')) {
                        const data = extractRowData(row, type);
                        if (data) aioKeywords.push(data);
                    }
                });
            }
        });
    }
    
    function extractRowData(row, type) {
        // Optimized data extraction
        const getText = (selector) => {
            const el = row.querySelector(selector);
            return el ? el.textContent.trim() : '';
        };
        
        const getData = (selector, attr) => {
            const el = row.querySelector(selector);
            return el ? el.getAttribute(`data-${attr}`) : '';
        };
        
        if (type === 'main') {
            return {
                keyword: getData('th', 'keyword'),
                monthly_search: parseInt(getData('td:nth-child(2)', 'avg_monthly_searches')) || 0,
                competition: getData('td:nth-child(3)', 'competition') || 'UNDEFINED',
                low_bid: parseFloat(getData('td:nth-child(4)', 'low_top_of_page_bid')) || 0,
                high_bid: parseFloat(getData('td:nth-child(5)', 'high_top_of_page_bid')) || 0,
                clicks: parseInt(getData('td:nth-child(6)', 'clicks')) || 0,
                ctr: parseFloat(getData('td:nth-child(7)', 'ctr')) || 0,
                impressions: parseInt(getData('td:nth-child(8)', 'impressions')) || 0,
                position: parseFloat(getData('td:nth-child(9)', 'position')) || 0,
                keyword_planner_id: row.getAttribute('data-keyword-id') || null
            };
        }
        // Add other types as needed...
        
        return null;
    }
    
    function updateMedianTable(mediankeywords, totalCount, limit) {
        const tableBody = $('#medianTable tbody#medianTableBody');
        if (mediankeywords.length === 0) {
            tableBody.html(`
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No keywords with AIO Insights found.
                        </div>
                    </td>
                </tr>
            `);
            return;
        }
        
        let mediantablehtml = '';
        mediankeywords.forEach((data, index) => {
            mediantablehtml += `
                <tr>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input keyword-checkbox" type="checkbox" 
                                value="${data.keyword}" 
                                data-keyword-id="${data.keyword_planner_id || ''}" 
                                data-index="${index}" 
                                data-row='${JSON.stringify(data)}'>
                        </div>
                    </td>
                    <td><strong>${data.keyword}</strong></td>
                    <td>${data.monthly_search.toLocaleString()}</td>
                    <td>${data.competition}</td>
                    <td>₹${data.low_bid.toFixed(2)}</td>
                    <td>₹${data.high_bid.toFixed(2)}</td>
                    <td class="text-end">${data.clicks}</td>
                    <td class="text-end">
                        <span class="${data.ctr > 5 ? 'text-success' : (data.ctr > 2 ? 'text-warning' : 'text-danger')}">
                            ${data.ctr.toFixed(2)}
                        </span>
                    </td>
                    <td class="text-end">${data.impressions.toLocaleString()}</td>
                    <td class="text-end">
                        <span class="${data.position <= 3 ? 'text-success' : (data.position <= 10 ? 'text-warning' : 'text-danger')}">
                            ${data.position.toFixed(1)}
                        </span>
                    </td>
                </tr>
            `;
        });
        calculateAndDisplayMedians();
        // window.performanceOptimizer.addUpdate(tableBody, mediantablehtml);
        
        // Update info
        let alertMessage = `<i class="fas fa-info-circle me-2"></i>`;
        if (totalCount > limit && !window.medianState.userForcedShow) {
            alertMessage += `Showing ${mediankeywords.length} out of ${totalCount} keywords (median limit: ${limit})`;
        } else {
            alertMessage += `Showing ${mediankeywords.length} keywords with AIO Insights`;
        }
        
        // window.performanceOptimizer.addUpdate($('#medianTableSection .alert'), alertMessage);
    }
    
    // Setup optimized mutation observer
    function setupOptimizedObserver() {
        if (window.performanceOptimizer.observer) {
            window.performanceOptimizer.observer.disconnect();
        }
        
        window.performanceOptimizer.observer = new MutationObserver((mutations) => {
            let needsMedianUpdate = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) {
                            // Check if this is a status update
                            if (node.classList?.contains('badge') || 
                                node.closest?.('.aio-status') || 
                                node.closest?.('.search-api-status')) {
                                needsMedianUpdate = true;
                            }
                        }
                    });
                }
            });
            
            if (needsMedianUpdate) {
                // Debounce the update
                if (window.observerDebounceTimer) {
                    clearTimeout(window.observerDebounceTimer);
                }
                window.observerDebounceTimer = setTimeout(() => {
                    window.syncAioKeywordsToMedianTable();
                }, 500);
            }
        });
        
        // Observe only specific areas
        const observeElement = (selector) => {
            const element = document.querySelector(selector);
            if (element) {
                window.performanceOptimizer.observer.observe(element, {
                    childList: true,
                    subtree: true,
                    attributes: false, // Disable for performance
                    characterData: false
                });
            }
        };
        
        ['#keywordsTable', '#keywordPlannerTable', '#remainingkeywordsTable'].forEach(observeElement);
    }
    
    // Initialize after DOM is ready
    setTimeout(() => {
        setupOptimizedObserver();
        
        // Initial check for AIO results
        setTimeout(() => {
            window.checkMedianAndShowModal();
        }, 2000);
    }, 1000);
});
</script>

<!-- DYNAMIC PILLS NAVIGATION SCRIPT -->
<!-- In the DYNAMIC PILLS NAVIGATION SCRIPT section, replace the entire script with: -->

<!-- In the DYNAMIC PILLS NAVIGATION SCRIPT section, replace the entire script with: -->
<!-- Replace the entire DYNAMIC PILLS NAVIGATION SCRIPT section with: -->

<script>
/**
 * Simplified Pills Navigation - Only 2 tabs
 * 1. Keyword Results (shows all data)
 * 2. Median Table
 */

    var keywordsTable;
$(document).ready(function() {
    // Initialize the pills navigation after results are loaded
    function initializeDynamicPills() {
        // Check if result box exists and has content
        if ($('#result_box').children().length === 0) {
            return;
        }
        
        // Create pills container if it doesn't exist
        if ($('#dynamicPillsNav').length === 0) {
            createPillsNavigation();
        }
        setTimeout(() => {
            $('#keywordPlannerTableSection').addClass('d-none');
            $('#remainingkeywordsTable').closest('.card').addClass('d-none');
        }, 500);
        
        // Activate first tab (Keyword Results)
        activateFirstTab();
    }
    
    function createPillsNavigation() {
        window.pillsNavigationActive = true;
        const pillsHtml = `
            <div class="card mb-3">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
 
                    {{-- Left side: tab pills --}}
                    <ul class="nav nav-pills mb-0" id="dynamicPillsNav" role="tablist">
 
                        {{-- Keyword Results Tab --}}
                        <li class="nav-item" role="presentation" id="pill-keywords-container">
                            <button class="nav-pill btn btn-outline-primary me-2 active"
                                    id="pill-keywords"
                                    type="button"
                                    data-target="keywordsTableSection">
                                <i class="fas fa-table me-1"></i>
                                Keyword Results (All Data)
                            </button>
                        </li>
 
                        {{-- Median Table Tab --}}
                        <li class="nav-item" role="presentation" id="pill-median-container">
                            <button class="nav-pill btn btn-outline-primary me-2"
                                    id="pill-median"
                                    type="button"
                                    data-target="medianTableSection">
                                <i class="fas fa-calculator me-1"></i>
                                Median Table
                            </button>
                        </li>
                    </ul>
 
                </div>
            </div>
        `;
        
        // Insert pills navigation at the top of result_box
        $('#result_box').prepend(pillsHtml);
        
        // Add click handlers
        $('.nav-pill').on('click', function() {
            const target = $(this).data('target');
            activateTab(target, $(this));
        });
    }
    
    function activateTab(targetId, buttonElement) {
    // Remove active class from all pills
    $('.nav-pill').removeClass('active btn-primary').addClass('btn-outline-primary');
    buttonElement.removeClass('btn-outline-primary').addClass('active btn-primary');

    // Special handling for Median tab
    if (targetId === 'medianTableSection') {

        console.log('Showing median table directly');
        showMedianTableDirectly();
        return;

        const { hasAioYes, aioYesCount } = window.checkAioResults?.() || { hasAioYes: false, aioYesCount: 0 };
        const medianLimit = parseInt($('#median_limit').val()) || 10;
        
        // CASE 1: No AIO results at all
        if (!hasAioYes) {
            showMedianInfoModal(medianLimit, aioYesCount, medianLimit - aioYesCount);
            
            // Keep Keyword tab active instead
            $('.nav-pill').removeClass('active btn-primary').addClass('btn-outline-primary');
            $('#pill-keywords').removeClass('btn-outline-primary').addClass('active btn-primary');
            activateTab('keywordsTableSection', $('#pill-keywords'));
            return;
        }
        
        // CASE 2: Not enough AIO results for median limit
        if (aioYesCount < medianLimit && !window.medianState?.userForcedShow) {
            console.log(`Insufficient AIO results: ${aioYesCount}/${medianLimit}`);
            showMedianLimitModal(medianLimit, aioYesCount, medianLimit - aioYesCount);
            
            // Keep Keyword tab active
            $('.nav-pill').removeClass('active btn-primary').addClass('btn-outline-primary');
            $('#pill-keywords').removeClass('btn-outline-primary').addClass('active btn-primary');
            activateTab('keywordsTableSection', $('#pill-keywords'));
            return;
        }
        
        // CASE 3: Enough AIO results OR user forced show - Show median table
        console.log('✅ Showing median table...');
        
        // Hide all other sections
        $('#keywordsTableSection').addClass('d-none');
        $('#keywordPlannerTableSection').addClass('d-none');
        $('#remainingkeywordsTableSection').addClass('d-none');
        
        // Show median table
        $('#medianTableSection').removeClass('d-none');
        if (typeof calculateAndDisplayMedians === 'function') {
            calculateAndDisplayMedians(false);
        }
        
        // // Calculate and display medians
        // if (typeof window.medianFunctions?.showMedianTableWithLoading === 'function') {
        //     window.medianFunctions.showMedianTableWithLoading();
        // } else if (typeof calculateAndDisplayMedians === 'function') {
        //     calculateAndDisplayMedians();
        // }
        
    } else if (targetId === 'keywordsTableSection') {
        // Show all keyword-related tables
        $('#keywordsTableSection').removeClass('d-none');
        
        if ($('#keywordPlannerTable tbody tr').length > 0) {
            $('#keywordPlannerTableSection').removeClass('d-none');
        }
        if ($('#remainingkeywordsTable tbody tr').length > 0) {
            $('#remainingkeywordsTableSection').removeClass('d-none');
        }
        
        // Hide median table
        $('#medianTableSection').addClass('d-none');
    }

    // Scroll to the primary target section
    const $target = $('#' + targetId);
    if ($target.length) {
        $('html, body').animate({
            scrollTop: $target.offset().top - 100
        }, 300);
    }
}
    
    function activateFirstTab() {
        // Activate Keyword Results tab by default
        const keywordPill = $('#pill-keywords');
        if (keywordPill.length > 0) {
            activateTab('keywordsTableSection', keywordPill);
        }
    }
    
    // Simple modal for Median tab info when no AIO results
    function showMedianInfoModal(limit, current, remaining) {
        const modalHtml = `
            <div class="modal fade" id="medianInfoModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Median Table Information</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>The Median Table shows keywords that have AIO Insights available.</p>
                            <p>Currently: <strong>${current}</strong> out of <strong>${limit}</strong> required keywords have AIO Insights.</p>
                            <p>Please wait for more keywords to process or click "Fetch More Keywords" to get additional data.</p>
                            <div class="mt-3">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                    <span class="visually-hidden">Updating...</span>
                                </div>
                                <small class="text-muted">Auto-updating as keywords are processed...</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#medianInfoModal').remove();
    
        // Add and show modal
        $('body').append(modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('medianInfoModal'));
        
        // ✅ Start auto-updates when modal is shown
        $('#medianInfoModal').on('shown.bs.modal', function() {
            currentOpenModal = 'medianInfoModal';
            startModalUpdates();
        });
        
        // ✅ Stop auto-updates when modal is hidden
        $('#medianInfoModal').on('hidden.bs.modal', function() {
            currentOpenModal = null;
            stopModalUpdates();
        });
        
        modal.show();
    }
    function showMedianTableWithLoading() {
        const medianTableBody = $('#medianTable tbody#medianTableBody');
        const medianTableSection = $('#medianTableSection');
        
        // Show loading state
        medianTableBody.html(`
            <tr>
                <td colspan="10" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="text-muted">Calculating Median Table...</h5>
                        <p class="text-muted">Please wait while we process AIO Insights data</p>
                        <div class="progress mt-3" style="width: 200px; height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                        </div>
                    </div>
                </td>
            </tr>
        `);
        
        // Ensure median table section is visible
        medianTableSection.removeClass('d-none');
        
        // Hide other sections
        $('#keywordsTableSection').addClass('d-none');
        $('#keywordPlannerTableSection').addClass('d-none');
        if ($('#remainingkeywordsTable').closest('.card').length) {
            $('#remainingkeywordsTable').closest('.card').addClass('d-none');
        }
        
        // Run median calculation with delay to ensure UI updates first
        setTimeout(() => {
            if (typeof window.continueMedianCalculation === 'function') {
                // Check if we have enough data first
                const { hasAioYes, aioYesCount } = window.checkAioResults();
                const medianLimit = parseInt($('#median_limit').val()) || 10;
                
                if (aioYesCount < medianLimit && !window.medianState?.userForcedShow) {
                    // Show modal asking to fetch more
                    showMedianLimitModal(medianLimit, aioYesCount, medianLimit - aioYesCount);
                } else {
                    // Collect fresh data and calculate
                    collectAllAioDataAndCalculate();
                }
            } else if (typeof calculateAndDisplayMedians === 'function') {
                // Use the existing function
                calculateAndDisplayMedians();
            } else {
                // Fallback: show error
                medianTableBody.html(`
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error: Median calculation function not found
                            </div>
                        </td>
                    </tr>
                `);
            }
        }, 500);
    }

    /**
     * Collect fresh data from all tables and recalculate
     */
    function collectAllAioDataAndCalculate() {
        console.log('🔄 Collecting fresh data for median calculation...');
        
        let aioTableData = [];
        
        // 1. Collect from keywordsTable
        keywordsTable.rows().every(function() {
            var $row = $(this.node());
            var aioStatusBadge = $row.find('.aio-status .badge');
            var hasAio = aioStatusBadge.text().trim() === 'Yes' || aioStatusBadge.text().trim() === 'Done';
            
            if (hasAio) {
                var rowData = getRowData($row);
                if (rowData && rowData.keyword) {
                    aioTableData.push(rowData);
                }
            }
        });
        
        // 2. Collect from remainingkeywordsTable (if visible)
        $('#remainingkeywordsTable tbody tr').each(function() {
            var $row = $(this);
            var aioStatusBadge = $row.find('.aio-status .badge');
            var hasAio = aioStatusBadge.text().trim() === 'Yes' || aioStatusBadge.text().trim() === 'Done';
            
            if (hasAio) {
                var rowData = getRemainingRowData($row);
                if (rowData && rowData.keyword) {
                    aioTableData.push(rowData);
                }
            }
        });
        
        // 3. Collect from keywordPlannerTable (if visible)
        $('#keywordPlannerTable tbody tr').each(function() {
            var $row = $(this);
            var aioStatusBadge = $row.find('.aio-status .badge');
            var hasAio = aioStatusBadge.text().trim() === 'Yes' || aioStatusBadge.text().trim() === 'Done';
            
            if (hasAio) {
                var rowData = getKeywordPlannerRowData($row);
                if (rowData && rowData.keyword) {
                    aioTableData.push(rowData);
                }
            }
        });
        
        // Now calculate with the fresh data
        const medianLimit = parseInt($('#median_limit').val()) || 10;
        
        if (aioTableData.length === 0) {
            $('#medianTable tbody#medianTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No keywords with AIO Insights found yet. Please wait for processing to complete.
                        </div>
                    </td>
                </tr>
            `);
            return;
        }
        
        // Use the existing continueMedianCalculation function
        if (typeof window.continueMedianCalculation === 'function') {
            window.continueMedianCalculation(aioTableData, medianLimit);
        } else if (typeof window.keywordProcessingFix?.continueMedianCalculation === 'function') {
            window.keywordProcessingFix.continueMedianCalculation(aioTableData, medianLimit);
        }
    }

    function showMedianTableDirectly() {
        console.log('🔄 Showing median table directly...');
        
        // Hide all other sections
        $('#keywordsTableSection').addClass('d-none');
        $('#remainingkeywordsTableSection').addClass('d-none');
        $('#keywordPlannerTableSection').addClass('d-none');
        
        // Show median section
        $('#medianTableSection').removeClass('d-none');
        
        // Update active pill
        $('.nav-pill').removeClass('active');
        $('#pill-median').addClass('active');
        
        // Always recalculate from the live DOM so we pick up any new AIO "Yes"
        // badges that arrived while the user was on the Keywords tab.
        // calculateAndDisplayMedians already collects from all three tables.
        if (typeof calculateAndDisplayMedians === 'function') {
            calculateAndDisplayMedians(true);
        } else {
            // Fallback (should never happen)
            collectAllAioDataAndCalculate();
        }
    }

    
    // Initialize after AJAX success
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (settings.url && settings.url.includes('keyword-store')) {
            setTimeout(function() {
                initializeDynamicPills();
            }, 1000);
        }
    });
    
    // Expose functions globally
    window.dynamicPills = {
        init: initializeDynamicPills,
        activateTab: activateTab
    };
    // Initialize median functions globally
    window.medianFunctions = {
        showMedianTableWithLoading,
        collectAllAioDataAndCalculate,
        getRowData,
        getRemainingRowData,
        getKeywordPlannerRowData
    };

    // Make sure continueMedianCalculation is available globally
    if (typeof window.continueMedianCalculation === 'undefined' && typeof window.keywordProcessingFix?.continueMedianCalculation === 'function') {
        window.continueMedianCalculation = window.keywordProcessingFix.continueMedianCalculation;
    }
    function showToast(message, type = 'success') {
        const toastId = 'toast-' + Date.now();
        const icon = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        }[type] || 'info-circle';
            
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${icon} me-2"></i>
                            ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>`;
            
        // Add toast container if it doesn't exist
        if ($('#toastContainer').length === 0) {
            $('body').append('<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060"></div>');
        }
            
        $('#toastContainer').append(toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        toast.show();
            
        // Remove toast after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }
    
});

</script>

@endsection