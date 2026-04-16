<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<style>
    .badge {
        font-size: 0.85em;
        padding: 0.4em 0.8em;
    }
</style>

<!-- FIX #4: Changed layout to match Image 2 with proper card structure -->
<div class="card" id="remainingkeywordsTableSection">
    <div class="card-header">
        <h5 class="mb-0">Keyword Planner Remaining Data ({{$total_count}})</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="remainingkeywordsTable">
                <thead>
                    <tr>
                        <th scope="col">Keyword</th>
                        <th scope="col">Monthly Search</th>
                        <th scope="col">Competition</th>
                        <th scope="col">Low Bid (₹)</th>
                        <th scope="col">High Bid (₹)</th>
                        <th>Clicks</th>
                        <th>CTR (%)</th>
                        <th>Impressions</th>
                        <th>Position</th>
                        <th>Search API Status</th>
                        <th>AIO Status</th>
                        <th>Client Mentioned Status</th>
                    </tr>
                </thead>
                <tbody>
                    @if($keywords)
                    @foreach($keywords as $index => $keyword_item)
                    <tr data-index="{{ $index_offset + $index }}" data-keyword="{{ $keyword_item['keyword'] ?? $keyword_item['query'] }}" data-original-index="{{ $index }}" data-keyword-id="{{ $keyword_item['keyword_planner_id'] ?? '' }}">
                        <th scope="row" data-remaining_keyword="{{$keyword_item['keyword'] ?? $keyword_item['query']}}">{{$keyword_item['keyword'] ?? $keyword_item['query']}}</th>
                        <td data-remaining_monthly_search="{{$keyword_item['avg_monthly_searches']}}">{{$keyword_item['avg_monthly_searches'] ?? 0 }}</td>
                        <td data-remaining_competition="{{$keyword_item['competition']}}">{{$keyword_item['competition'] ?? 0 }}</td>
                        <td data-remaining_low_top_of_page_bid="{{$keyword_item['low_top_of_page_bid']}}">{{$keyword_item['low_top_of_page_bid'] ?? 0 }}</td>
                        <td data-remaining_high_top_of_page_bid="{{$keyword_item['high_top_of_page_bid']}}">{{$keyword_item['high_top_of_page_bid'] ?? 0 }}</td>
                        <td class="text-end" data-order="{{ $keyword_item['clicks'] ?? 0 }}" title="Formula applied on the round(($estimatedImpressions * $estimatedCtr) / 100">{{ $keyword_item['clicks'] ?? 0 }}</td>
                        <td class="text-end" data-remaining_ctr="{{ $keyword_item['ctr'] ?? 0 }}">
                            <span class="{{ $keyword_item['ctr'] > 5 ? 'text-success' : ($keyword_item['ctr'] > 2 ? 'text-warning' : 'text-danger') }}">
                                {{ $keyword_item['ctr'] }}%
                            </span>
                        </td>
                        <td class="text-end" data-remaining_impressions="{{ $keyword_item['impressions'] ?? 0 }}">{{ $keyword_item['impressions'] }}</td>
                        <td class="text-end" data-remaining_position="{{ $keyword_item['position'] ?? 0 }}">
                            <span class="{{ $keyword_item['position'] <= 3 ? 'text-success' : ($keyword_item['position'] <= 10 ? 'text-warning' : 'text-danger') }}">
                                {{ $keyword_item['position'] }}
                            </span>
                        </td>
                        
                        <!-- Status columns matching Image 2 -->
                        <td class="search-api-status">
                            <span class="badge bg-warning">Processing</span>
                        </td>
                        <td class="aio-status">
                            <span class="badge bg-warning">Processing</span>
                        </td>
                        <td class="client-mentioned-status">
                            <span class="badge bg-warning">Processing</span>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    
    $(document).ready(function() {

        // Status polling functionality
        let sessionId = "{{ session()->getId() }}";
        let statusCheckInterval = null;
        
        // Start checking statuses if there are rows
        if ($('#remainingkeywordsTable tbody tr').length > 0) {
            startStatusPolling();
        }
        
        function startStatusPolling() {
            // Immediate first check
            checkRemainingKeywordStatuses();
            // Then poll every 10 seconds
            statusCheckInterval = setInterval(checkRemainingKeywordStatuses, 10000);
        }
        
        // FIX #1: Batched updates to reduce lag
        let updateQueue = [];
        let updateTimer = null;
        
        function checkRemainingKeywordStatuses() {
            const indexes = [];
            $('#remainingkeywordsTable tbody tr').each(function() {
                const index = $(this).data('index');
                if (index !== undefined) {
                    indexes.push(index);
                }
            });
            
            if (indexes.length === 0) return;
            
            $.ajax({
                url: "{{ route('check.keyword.status') }}",
                type: "GET",
                data: {
                    session_id: sessionId,
                    indexes: indexes,
                    is_remaining: true
                },
                success: function(response) {
                    if (response.success) {
                        updateRemainingKeywordStatuses(response.results);
                        
                        // Check if all are processed
                        const allProcessed = Object.values(response.results).every(r => r.processed);
                        if (allProcessed) {
                            clearInterval(statusCheckInterval);
                            console.log('All remaining keywords processed');
                            triggerAutoSaveForRemainingAioKeywords();
                        }
                    }
                    if (window.performanceOptimizer?.observer) {
                        window.performanceOptimizer.observer.disconnect();
                    }
                    
                    // Setup new observer after content loads
                    setTimeout(() => {
                        if (typeof setupOptimizedObserver === 'function') {
                            setupOptimizedObserver();
                        }
                    }, 500);
                },
                error: function(xhr) {
                    console.error('Error checking status:', xhr.responseText);
                }
            });
        }
        function triggerAutoSaveForRemainingAioKeywords() {
            console.log('🔄 All remaining keywords processed! Checking for AIO "Yes" keywords to auto-save...');
            
            const dataToSave = [];
            
            // Collect all rows with AIO status "Yes" from remainingkeywordsTable
            $('#remainingkeywordsTable tbody tr').each(function() {
                const $row = $(this);
                const aioStatusBadge = $row.find('.aio-status .badge');
                const aioStatus = aioStatusBadge.text().trim();
                
                if (aioStatus === 'Yes' || aioStatus === 'Done') {
                    // Extract row data using data attributes
                    const rowData = {
                        keyword: $row.find('th[data-remaining_keyword]').data('remaining_keyword'),
                        keyword: $row.find('th[data-remaining_keyword]').data('remaining_keyword'),
                        monthly_search: parseInt($row.find('td[data-remaining_monthly_search]').data('remaining_monthly_search')) || 0,
                        competition: $row.find('td[data-remaining_competition]').data('remaining_competition') || 'UNDEFINED',
                        low_bid: parseFloat($row.find('td[data-remaining_low_top_of_page_bid]').data('remaining_low_top_of_page_bid')) || 0,
                        high_bid: parseFloat($row.find('td[data-remaining_high_top_of_page_bid]').data('remaining_high_top_of_page_bid')) || 0,
                        clicks: parseInt($row.find('td[data-order]').data('order')) || 0,
                        ctr: parseFloat($row.find('td[data-remaining_ctr]').data('remaining_ctr')) || 0,
                        impressions: parseInt($row.find('td[data-remaining_impressions]').data('remaining_impressions')) || 0,
                        position: parseFloat($row.find('td[data-remaining_position]').data('remaining_position')) || 0,
                        
                    };
                    
                    dataToSave.push(rowData);
                }
            });
            
            if (dataToSave.length > 0) {
                console.log(`✅ Found ${dataToSave.length} remaining keywords with AIO status "Yes". Triggering auto-save...`);
                
                // Get date range from form or session
                const dateFrom = $('input[name="date_from"]').val() || '';
                const dateTo = $('input[name="date_to"]').val() || '';
                
                // Call the saveMedianData function
                saveMedianDataForRemaining(dataToSave, dateFrom, dateTo);
            } else {
                console.log('⚠️ No remaining keywords with AIO status "Yes" found. Skipping auto-save.');
            }
        }

        function saveMedianDataForRemaining(dataToSave, dateFrom, dateTo) {
            $.ajax({
                url: '{{ route("autokeywordmediansave") }}',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    rows: dataToSave,
                    date_from: dateFrom,
                    date_to: dateTo,
                    client_property_id: '{{ $client_property_id ?? session('client_property_id') }}',
                    domainmanagement_id: '{{ $domainmanagement_id ?? session('domainmanagement_id') }}',
                    keyword_request_id: '{{ $keyword_request_id ?? session('keyword_request_id') }}',
                    source_table: 'remaining',  // Indicate source
                    _token: '{{ csrf_token() }}'
                }),
                success: function(response) {
                    console.log("Save Remaining Median Data automatically", response);
                    if (response.success) {
                        var tempName = response.temp_name || 'Unsaved Bucket';
                        showToast('success', 'Auto-saved as "' + tempName + '". Use "Add to Bucket List" to rename it.');
                        
                        // Clear pending save
                        window.pendingMedianSave = null;
                    } else {
                        console.error('Auto-save failed:', response.message);
                        showToast(response.message || 'Save failed', 'error');
                    }
                    
                    if (window.performanceOptimizer?.observer) {
                        window.performanceOptimizer.observer.disconnect();
                    }

                    setTimeout(() => {
                        if (typeof setupOptimizedObserver === 'function') {
                            setupOptimizedObserver();
                        }
                    }, 500);
                },
                error: function(xhr) {
                    console.error('Error auto-saving remaining keywords:', xhr.responseText);
                    showToast('Error saving remaining keywords automatically.', 'error');
                }
            });
        }

        
        function updateRemainingKeywordStatuses(results) {
            const updates = [];
            
            $.each(results, function(index, status) {
                const $row = $(`#remainingkeywordsTable tr[data-index="${index}"]`);
                if ($row.length) {
                    updates.push({
                        $row: $row,
                        status: status
                    });
                }
            });
            
            // Apply all updates at once
            updates.forEach(function(update) {
                updateStatusCell(update.$row.find('.search-api-status'), update.status.search_api_status);
                updateStatusCell(update.$row.find('.aio-status'), update.status.aio_status);
                updateStatusCell(update.$row.find('.client-mentioned-status'), update.status.client_mentioned_status);
                
                if (update.status.aio_status === 'Yes') {
                    update.$row.attr('data-has-aio', true);  // CHANGED: .data() to .attr()
                }
                if (update.status.client_mentioned_status === 'Yes') {
                    update.$row.attr('data-has-mentioned', true);  // CHANGED: .data() to .attr()
                }
            });
            
            checkAndTriggerSave('remaining');
            
            // FIX #1: When AIO status changes to Yes, recalculate median table
            const hasNewAioYes = updates.some(u => u.status.aio_status === 'Yes');
            if (hasNewAioYes) {
                if (typeof calculateAndDisplayMedians === 'function') {
                    // Debounce to avoid too many recalculations
                    if (window.medianRecalcTimer) {
                        clearTimeout(window.medianRecalcTimer);
                    }
                    window.medianRecalcTimer = setTimeout(() => {
                        calculateAndDisplayMedians();
                    }, 1000);
                }
            }
        }
        
        function updateStatusCell($cell, status) {
            let badgeClass = 'bg-warning';
            let text = status;
            
            if (status === 'Done' || status === 'Yes') {
                badgeClass = 'bg-success';
            } else if (status === 'No') {
                badgeClass = 'bg-secondary';
            } else if (status === 'Error') {
                badgeClass = 'bg-danger';
            }
            
            const badgeHtml = `<span class="badge ${badgeClass}">${text}</span>`;
            if ($cell.html().trim() !== badgeHtml.trim()) {
                $cell.html(badgeHtml);
            }
        }
        
        // Toast notification function
        
    });
</script>