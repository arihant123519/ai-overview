<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<style>
    #medianTable th,
    #medianTable td {
        vertical-align: middle;
    }

    .badge {
        font-size: 0.85em;
        padding: 0.4em 0.8em;
    }

    .table-responsive {
        overflow-x: auto;
    }

    #proceedForMedianBtn {
        transition: all 0.3s ease;
    }

    #proceedForMedianBtn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    #medianTableSection h5 small {
        display: block;
        margin-top: 5px;
    }

    #keywordsTable tr[data-has-aio="true"] {
        background-color: rgba(13, 110, 253, 0.05);
    }

    #keywordsTable tr[data-has-mentioned="true"] {
        background-color: rgba(13, 110, 253, 0.20);
    }
</style>
<div class="card" id="keywordsTableSection">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Keyword Results</h5>
    </div>
    <div class="card-body">
        <!-- Median Table (Initially Hidden) - FIX #3: Changed to match Keyword Results Tab layout -->
        

        <!-- Main Keywords Table -->
        <div class="mb-4">
            <div class="table-responsive">
                <table class="table" id="keywordsTable">
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
                        <tr data-index="{{ $index }}" data-keyword="{{ $keyword_item['keyword'] ?? $keyword_item['query'] }}">
                            <th data-keyword="{{ $keyword_item['keyword'] ?? $keyword_item['query'] }}" scope="row">{{$keyword_item['keyword'] ?? $keyword_item['query']}}</th>
                            <td data-avg_monthly_searches="{{$keyword_item['avg_monthly_searches'] ?? 0 }}">{{$keyword_item['avg_monthly_searches'] ?? 0 }}</td>
                            <td data-competition="{{$keyword_item['competition'] ?? 0 }}">{{$keyword_item['competition'] ?? 0 }}</td>
                            <td data-low_top_of_page_bid="{{$keyword_item['low_top_of_page_bid'] ?? 0 }}">{{$keyword_item['low_top_of_page_bid'] ?? 0 }}</td>
                            <td data-high_top_of_page_bid="{{$keyword_item['high_top_of_page_bid'] ?? 0 }}">{{$keyword_item['high_top_of_page_bid'] ?? 0 }}</td>
                            <td class="text-end" data-clicks="{{$keyword_item['clicks'] ?? 0 }}">{{ $keyword_item['clicks'] ?? 0 }}</td>
                            <td class="text-end" data-ctr="{{ $keyword_item['ctr'] ?? 0 }}">
                                <span class="{{ $keyword_item['ctr'] > 5 ? 'text-success' : ($keyword_item['ctr'] > 2 ? 'text-warning' : 'text-danger') }}">
                                    {{ $keyword_item['ctr'] }}
                                </span>
                            </td>
                            <td class="text-end" data-impressions="{{$keyword_item['impressions'] ?? 0}}">{{ $keyword_item['impressions'] ?? 0 }}</td>
                            <td class="text-end" data-position="{{$keyword_item['position'] ?? 0}}">
                                <span class="{{ $keyword_item['position'] <= 3 ? 'text-success' : ($keyword_item['position'] <= 10 ? 'text-warning' : 'text-danger') }}">
                                    {{ $keyword_item['position'] }}
                                </span>
                            </td>
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
    <!-- Modal for fetching more keywords (when median limit not reached) -->
    
</div>

<div id="medianTableSection" class="card d-none">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Median Calculation Table (AIO Insights Only)</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" id="confirmFetchMoreData">
                <i class="fas fa-download me-2"></i>Fetch More Data
            </button>
            <button disabled title="Please select at least one keyword." class="btn btn-success" id="saveToDbBtn" onclick="saveSelectedKeywords()">
                <i class="fas fa-save me-2"></i>Add to Bucket List
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Showing only keywords that have AIO Insights available.
        </div>
        <div class="table-responsive">
            <table class="table table-bordered" id="medianTable">
                <thead class="table-light">
                    <tr>
                        <th width="50">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllKeywords">
                            </div>
                        </th>
                        <th>Keyword</th>
                        <th>Monthly Search</th>
                        <th>Competition</th>
                        <th>Low Bid (₹)</th>
                        <th>High Bid (₹)</th>
                        <th>Clicks</th>
                        <th>CTR (%)</th>
                        <th>Impressions</th>
                        <th>Position</th>
                    </tr>
                </thead>
                <tbody id="medianTableBody">
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No keywords with AIO Insights found yet. Please wait for processing to complete.
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="keywordPlannerTableSection" class="d-none mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Keywords Extracted from Keyword Planner</h5>
        </div>
        <div class="card-body d-none" id="keywordPlannerTableSectionloading"></div>
        <div class="card-body d-none" id="keywordPlannerTableSection_main">
            <div class="table-responsive">
                <table class="table table-bordered" id="keywordPlannerTable">
                    <thead class="table-light">
                        <tr>
                            <th>Keyword</th>
                            <th>Monthly Search</th>
                            <th>Competition</th>
                            <th>Low Bid (₹)</th>
                            <th>High Bid (₹)</th>
                            <th>Clicks</th>
                            <th>CTR (%)</th>
                            <th>Impressions</th>
                            <th>Position</th>
                            <th>Search API Status</th>
                            <th>AIO Status</th>
                            <th>Client Mentioned Status</th>
                        </tr>
                    </thead>
                    <tbody id="keywordPlannerTableBody">
                        <!-- Data will be populated here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    let client_property_id = $("input[name='client_property_id']").val();
    let domainmanagement_id = $("input[name='domainmanagement_id']").val();
            
    let autoSaveTriggered = false;
    let processingState = {
        keywordPlannerComplete: false,
        remainingKeywordsComplete: false,
        initialKeywordsComplete: false
    };
    // Global flag to control Median tab access
    window.canAccessMedianTab = window.canAccessMedianTab || false;
    let hasTriggeredMedianAutoCheck = false;
    
    // FIX #1: Batch DOM updates to reduce lag
    let updateQueue = [];
    let updateTimer = null;
    let aio_result_extracted = null;
    $(document).ready(function() {

        // Calculate the CTR column index
        var ctrColumnIndex = 5;

        // Initialize DataTable
        keywordsTable = $('#keywordsTable').DataTable({
            "order": [
                [ctrColumnIndex, "desc"]
            ],
            "pageLength": 10,
            "lengthMenu": [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            "language": {
                "search": "Filter keywords:",
                "lengthMenu": "Show _MENU_ keywords",
                "info": "Showing _START_ to _END_ of _TOTAL_ keywords",
                "infoEmpty": "No keywords available",
                "infoFiltered": "(filtered from _MAX_ total keywords)",
                "zeroRecords": "No matching keywords found"
            },
            "columnDefs": [{
                "targets": "text-end",
                "className": "text-end"
            }],
            "initComplete": function() {
                $(this).closest('.dataTables_wrapper').addClass('table-responsive');
            }
        });

        // "Fetch More Data" button inside Median tab
        $(document).on('click', '#fetchMoreMedianBtn', function () {
            const medianLimit = parseInt($('#median_limit').val()) || 1;
            const medianRowCount = $('#medianTable tbody#medianTableBody tr').length;

            // Check limit condition
            if (medianLimit > medianRowCount) {
                // OK → fetch more median data
                calculateAndDisplayMedians();
            } else {
                // ❌ Limit exceeded
                // Remove active from all nav pills
                $('.nav-pill').removeClass('active');

                // Activate Keyword tab
                $('#pill-keywords').addClass('active');

                // OPTIONAL: trigger tab content change if you use a function
                activateTab('keywordsTableSection', $('#pill-keywords'));
            }
        });
        function updateSaveButtonState() {
            var checkedCount = $('.keyword-checkbox:checked').length;
            var $saveBtn = $('#saveToDbBtn');
            
            if (checkedCount > 0) {
                // Enable the button if at least one checkbox is checked
                $saveBtn.prop('disabled', false);
                $saveBtn.attr('title', `${checkedCount} keyword(s) selected`);
            } else {
                // Disable the button if no checkboxes are checked
                $saveBtn.prop('disabled', true);
                $saveBtn.attr('title', 'Please select at least one keyword');
            }
            
            console.log('✅ Save button state updated:', checkedCount, 'keyword(s) checked');
        }

        // Select All checkbox handler
        $(document).on('change', '#selectAllKeywords', function() {
            var isChecked = $(this).prop('checked');
            $('.keyword-checkbox').prop('checked', isChecked);
            updateSaveButtonState();
            console.log('✅ Select All clicked:', isChecked);
        });
        $(document).on('change', '.keyword-checkbox', function() {
            updateSaveButtonState();
            
            // Update "Select All" checkbox state
            var totalCheckboxes = $('.keyword-checkbox').length;
            var checkedCheckboxes = $('.keyword-checkbox:checked').length;
            $('#selectAllKeywords').prop('checked', totalCheckboxes === checkedCheckboxes);
            
            console.log('✅ Individual checkbox changed:', $(this).is(':checked'), '| Total checked:', checkedCheckboxes, '/', totalCheckboxes);
        });

        
        // FIX #3: Initialize manual filtering for median table
        $('#medianTableFilter').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('#medianTable tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        $('#medianTableLength').on('change', function() {
            const length = parseInt($(this).val());
            const $rows = $('#medianTable tbody tr');
            
            $rows.each(function(index) {
                const $row = $(this);
                if ($row.find('.alert').length === 0) { // Skip alert rows
                    if (length === -1) {
                        $row.show();
                    } else {
                        $row.toggle(index < length);
                    }
                }
            });
        });
        $('#medianTableFilter').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('#medianTable tbody tr').each(function() {
                const $row = $(this);
                // Don't filter out alert rows
                if ($row.find('.alert').length === 0) {
                    $row.toggle($row.text().toLowerCase().indexOf(value) > -1);
                }
            });
        });
    });


    // Function to calculate medians and display in median table (AIO Insights only)
    function calculateAndDisplayMedians(isManualTabClick = false) {
        var aioTableData = [];
        var medianLimit = parseInt($('#median_limit').val()) || 10;

        // 1. Collect AIO data from #keywordsTable
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

        if ($('#remainingkeywordsTable tbody tr').length > 0) {
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
        }

        // Always allow user to access Median tab once we have any AIO results
        if (aioTableData.length > 0) {
            window.canAccessMedianTab = true;
        }

        if (!isManualTabClick && aioTableData.length < medianLimit) {
            console.log(`Background processing - ${aioTableData.length}/${medianLimit} AIO keywords so far.`);
        }

        // Single call — guard removed from continueMedianCalculation so this
        // works both when the user is on the Median tab AND in the background.
        continueMedianCalculation(aioTableData, medianLimit);

        // Update pills so Median tab becomes visible
        if (typeof window.dynamicPills !== 'undefined') {
            window.dynamicPills.update();
        }

        return true;
    }

    function getRemainingRowData($row) {
        const keywordPlannerId = $row.data('keyword-id') || null;
        const searchApiStatus = $row.find('.search-api-status .badge').text().trim();
        const aioStatus = $row.find('.aio-status .badge').text().trim();
        const clientMentionedStatus = $row.find('.client-mentioned-status .badge').text().trim();
        
        return {
            hasAio: true,
            keywordId: keywordPlannerId,
            keyword: $row.find('th[data-remaining_keyword]').data('remaining_keyword'),
            monthly_search: parseInt($row.find('td[data-remaining_monthly_search]').data('remaining_monthly_search')) || 0,
            competition: $row.find('td[data-remaining_competition]').data('remaining_competition') || 'UNDEFINED',
            low_bid: parseFloat($row.find('td[data-remaining_low_top_of_page_bid]').data('remaining_low_top_of_page_bid')) || 0,
            high_bid: parseFloat($row.find('td[data-remaining_high_top_of_page_bid]').data('remaining_high_top_of_page_bid')) || 0,
            clicks: parseInt($row.find('td[data-order]').data('order')) || 0,
            ctr: parseFloat($row.find('td[data-remaining_ctr]').data('remaining_ctr')) || 0,
            impressions: parseInt($row.find('td[data-remaining_impressions]').data('remaining_impressions')) || 0,
            position: parseFloat($row.find('td[data-remaining_position]').data('remaining_position')) || 0,
            search_api_status: searchApiStatus,
            aio_status: aioStatus,
            client_mentioned_status: clientMentionedStatus
        };
    }



    function continueMedianCalculation(aioTableData, medianLimit){
        console.log('continueMedianCalculation');
        // NOTE: Guard removed intentionally — median data must update in the background
        // so it is ready (and correct) when the user switches to the Median tab.
        
        aioTableData = aioTableData.filter(function(data) {
            // Must have all required fields with valid values
            const isValid = data && 
                data.keyword && 
                data.keyword.trim() !== '' &&
                typeof data.monthly_search !== 'undefined' &&
                typeof data.competition !== 'undefined' &&
                typeof data.low_bid !== 'undefined' &&
                typeof data.high_bid !== 'undefined' &&
                typeof data.clicks !== 'undefined' &&
                typeof data.ctr !== 'undefined' &&
                typeof data.impressions !== 'undefined' &&
                typeof data.position !== 'undefined';
            
            if (!isValid) {
                console.warn('Invalid data filtered out:', data);
            }
            
            return isValid;
        });
        console.log('aioTableData 1: ', aioTableData);
        
        if (aioTableData.length === 0) {
            $('#medianTable tbody#medianTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No valid keywords with AIO Insights found. Please wait for processing to complete.
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        // Sort by monthly_search for median calculation
        aioTableData.sort(function(a, b) {
            return a.monthly_search - b.monthly_search;
        });

        var limitedAioTableData;
        console.log('limitedAioTableData 1: ', limitedAioTableData);

        var totalAioRecords = aioTableData.length;
        
        if (totalAioRecords <= medianLimit) {
            // If total records are less than or equal to medianLimit, use all records
            limitedAioTableData = aioTableData;
        } else {
            // Calculate how many records to take from the middle
            var halfLimit = Math.floor(medianLimit / 2);
            var startIndex, endIndex;
            
            if (medianLimit % 2 === 0) {
                // Even medianLimit
                var middleIndex = Math.floor(totalAioRecords / 2);
                startIndex = middleIndex - halfLimit;
                endIndex = middleIndex + halfLimit;
            } else {
                // Odd medianLimit
                var middleIndex = Math.floor(totalAioRecords / 2);
                startIndex = middleIndex - halfLimit;
                endIndex = middleIndex + halfLimit + 1;
            }
            
            // Ensure indices are within bounds
            startIndex = Math.max(0, startIndex);
            endIndex = Math.min(totalAioRecords, endIndex);
            
            // Adjust if we're too close to the beginning or end
            if (endIndex - startIndex < medianLimit) {
                if (startIndex === 0) {
                    endIndex = Math.min(totalAioRecords, medianLimit);
                } else if (endIndex === totalAioRecords) {
                    startIndex = Math.max(0, totalAioRecords - medianLimit);
                }
            }
            
            // Get the middle records - RESPECTING THE MEDIAN LIMIT
            limitedAioTableData = aioTableData.slice(startIndex, endIndex);
        }
        aio_result_extracted = limitedAioTableData.length;
        
        // Display median summary
        $('#medianTableSection h5').html(`
            Median Calculation Table (${limitedAioTableData.length}/${medianLimit} AIO Insights Records)
        `);

        // FIX #3: Build median table rows with status columns matching Image 2
        var medianTableHtml = '';
        limitedAioTableData.forEach(function(data, index) {
            const keyword = String(data.keyword || '').trim();
            const monthly_search = parseInt(data.monthly_search) || 0;
            const competition = String(data.competition || 'UNKNOWN');
            const low_bid = parseFloat(data.low_bid) || 0;
            const high_bid = parseFloat(data.high_bid) || 0;
            const clicks = parseInt(data.clicks) || 0;
            const ctr = parseFloat(data.ctr) || 0;
            const impressions = parseInt(data.impressions) || 0;
            const position = parseFloat(data.position) || 0;
            const keywordId = String(data.keywordId || data.keyword_planner_id || '');
            
            // Escape JSON for data-row attribute
            if (!keyword) {
                console.warn('Skipping row with empty keyword:', data);
                return;
            }
            
            // Escape data for attributes
            const escapedKeyword = keyword.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            const jsonData = JSON.stringify(data).replace(/"/g, '&quot;');
            medianTableHtml += `
                <tr>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input keyword-checkbox" type="checkbox" 
                                value="${escapedKeyword}" data-keyword-id="${keywordId}" 
                            data-index="${index}" 
                            data-row="${jsonData}">
                        </div>
                    </td>
                    <td>
                        <strong>${escapedKeyword}</strong>
                    </td>
                    <td>${monthly_search.toLocaleString()}</td>
                    <td>
                        ${competition || 'UNDEFINED'}
                    </td>
                    <td>₹${low_bid.toFixed(2)}</td>
                    <td>₹${high_bid.toFixed(2)}</td>
                    <td class="text-end">${clicks}</td>
                    <td class="text-end">
                        <span class="${ctr > 5 ? 'text-success' : (ctr > 2 ? 'text-warning' : 'text-danger')}">
                            ${ctr.toFixed(2)}%
                        </span>
                    </td>
                    <td class="text-end">${impressions.toLocaleString()}</td>
                    <td class="text-end">
                        <span class="${position <= 3 ? 'text-success' : (position <= 10 ? 'text-warning' : 'text-danger')}">
                            ${position.toFixed(1)}
                        </span>
                    </td>
                </tr>
            `;
        });

        const $tbody = $('#medianTable tbody#medianTableBody');
        console.log('limitedAioTableData 2: ', limitedAioTableData);
        
        if ($tbody.length === 0) {
            // If tbody doesn't exist, create it
            if ($('#medianTable').length > 0) {
                $('#medianTable').html(`
                    <thead class="table-light">
                        <tr>
                            <th width="50">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllKeywords">
                                </div>
                            </th>
                            <th>Keyword</th>
                            <th>Monthly Search</th>
                            <th>Competition</th>
                            <th>Low Bid (₹)</th>
                            <th>High Bid (₹)</th>
                            <th>Clicks</th>
                            <th>CTR (%)</th>
                            <th>Impressions</th>
                            <th>Position</th>
                        </tr>
                    </thead>
                    <tbody id="medianTableBody"></tbody>
                `);
            }
        }
        
        // CRITICAL FIX: Only update if we have valid HTML
        if (medianTableHtml.trim() !== '') {
            // Double-check tbody exists before updating
            const finalTbody = $('#medianTable tbody#medianTableBody');
            if (finalTbody.length > 0) {
                finalTbody.html(medianTableHtml);
                setTimeout(function() {
                    updateSaveButtonState();
                    console.log('✅ Median table populated with', limitedAioTableData.length, 'rows');
                    console.log('✅ Checkboxes in table:', $('.keyword-checkbox').length);
                }, 100);
                
                // Verify rows were added
                const rowCount = finalTbody.find('tr').length;
                
                if (rowCount === 0) {
                    console.error('❌ No rows found after update!');
                }
            } else {
                console.error('❌ Still cannot find tbody element!');
            }
        } else {
            $('#medianTable tbody#medianTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No valid keywords could be displayed. Please check the data.
                        </div>
                    </td>
                </tr>
            `);
        }

        // Show the median table section
        if ($('.nav-pill.active').data('target') === 'medianTableSection') {
            $('#medianTableSection').removeClass('d-none');
        }

        var dateFrom = $('#filter_date_from').val();
        var dateTo   = $('#filter_date_to').val();
        
        // Prepare data for backend
        var dataToSave = limitedAioTableData.map(function(item) {
            return {
                keyword: item.keyword,
                monthly_search: item.monthly_search || 0,
                competition: item.competition || 'UNDEFINED',
                low_bid: item.low_bid || 0,
                high_bid: item.high_bid || 0,
                clicks: item.clicks || 0,
                ctr: item.ctr || 0,
                impressions: item.impressions || 0,
                position: item.position || 0,
                keywordId: item.keywordId || null
            };
        });
        
        console.log('Data to save (limited to median limit):', dataToSave.length, 'keywords');
        
        // Update alert message — always runs regardless of processing state
        const processingDone = areAllKeywordsProcessed();
        let alertMessage = `<i class="fas fa-info-circle me-2"></i>`;
        if (totalAioRecords >= medianLimit) {
            alertMessage += `Showing ${limitedAioTableData.length} out of ${totalAioRecords} AIO Insights keywords (median limit: ${medianLimit}).`;
        } else {
            const stillNeeded = medianLimit - totalAioRecords;
            alertMessage += `Showing all <strong>${totalAioRecords}</strong> AIO Insights keywords found so far. `
                + `<strong>${stillNeeded}</strong> more needed to reach the median limit of ${medianLimit}. `
                + (processingDone
                    ? `All keywords have finished processing.`
                    : `Processing is still running.`);
        }
        $('#medianTableSection .alert').html(alertMessage);

        // Save logic — only runs once all keywords are processed
        if (!processingDone) {
            window.pendingMedianSave = {
                rows: dataToSave,
                date_from: dateFrom,
                date_to: dateTo,
                client_property_id: '{{ $client_property_id ?? session('client_property_id') }}',
                domainmanagement_id: '{{ $domainmanagement_id ?? session('domainmanagement_id') }}',
                keyword_request_id: '{{ $keyword_request_id ?? session('keyword_request_id') }}'
            };
            return;
        }
        
        console.log('✅ ALL KEYWORDS PROCESSED - Proceeding with auto-save');
        saveMedianData(dataToSave, dateFrom, dateTo);
    }


    function areAllKeywordsProcessed(tableType = 'all') {
        // Check initial keywords table
        if (tableType === 'all' || tableType === 'initial') {
            let initialComplete = true;
            keywordsTable.rows().every(function() {
                var $row = $(this.node());
                const searchStatus = $row.find('.search-api-status .badge').text().trim();
                const aioStatus = $row.find('.aio-status .badge').text().trim();
                const clientStatus = $row.find('.client-mentioned-status .badge').text().trim();

                if (searchStatus === 'Processing' || aioStatus === 'Processing' || clientStatus === 'Processing') {
                    initialComplete = false;
                    return false; // break
                }
            });
            
            if (tableType === 'initial') return initialComplete;
            if (!initialComplete) return false;
        }

        // Check remaining keywords table if it exists
        if (tableType === 'all' || tableType === 'remaining') {
            let remainingComplete = true;
            if ($('#remainingkeywordsTable tbody tr').length > 0) {
                $('#remainingkeywordsTable tbody tr').each(function() {
                    const searchStatus = $(this).find('.search-api-status .badge').text().trim();
                    const aioStatus = $(this).find('.aio-status .badge').text().trim();
                    const clientStatus = $(this).find('.client-mentioned-status .badge').text().trim();

                    if (searchStatus === 'Processing' || aioStatus === 'Processing' || clientStatus === 'Processing') {
                        remainingComplete = false;
                        return false; // break
                    }
                });
            }
            
            if (tableType === 'remaining') return remainingComplete;
            if (!remainingComplete) return false;
        }

        // Check keyword planner table if it exists
        if (tableType === 'all' || tableType === 'planner') {
            let keywordPlannerComplete = true;
            if ($('#keywordPlannerTable tbody tr').length > 0) {
                $('#keywordPlannerTable tbody tr').each(function() {
                    const searchStatus = $(this).find('.search-api-status .badge').text().trim();
                    const aioStatus = $(this).find('.aio-status .badge').text().trim();
                    const clientStatus = $(this).find('.client-mentioned-status .badge').text().trim();

                    if (searchStatus === 'Processing' || aioStatus === 'Processing' || clientStatus === 'Processing') {
                        keywordPlannerComplete = false;
                        return false; // break
                    }
                });
            }
            
            if (tableType === 'planner') return keywordPlannerComplete;
            if (!keywordPlannerComplete) return false;
        }

        return true;
    }

    

    function saveMedianData(dataToSave, dateFrom, dateTo) {
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
                _token: '{{ csrf_token() }}'
            }),
            success: function(response) {
                console.log("Save Median Data automatically", response);
                if (response.success) {
                    var tempName = response.temp_name || 'Unsaved Bucket';
                    showToast('success', 'Auto-saved as "' + tempName + '". Use "Add to Bucket List" to rename it.');

                    // Clear pending save
                    window.pendingMedianSave = null;
                } else {
                    console.error('Auto-save failed:', response.message);
                    showToast('error', response.message || 'Save failed');
                }
                // $('#saveToDbBtn').prop('disabled', false).attr('title', '');
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
                console.error('Error auto-saving keywords:', xhr.responseText);
                showToast('error', 'Error saving keywords automatically.');
            }
        });
    }
    

    // Let user force-show Median table with current AIO count
    $('#showMedianAnywayBtn').on('click', function() {
        window.medianState.userForcedShow = true;
        window.medianState.medianLimitReached = true;
        
        if (window.medianModal) {
            window.medianModal.hide();
        }
        
        // Force show median table
        if (typeof window.syncAioKeywordsToMedianTable === 'function') {
            window.syncAioKeywordsToMedianTable();
        }
        
        // Update pills to show Median tab
        if (typeof window.dynamicPills !== 'undefined') {
            window.dynamicPills.update();
            
            // Activate Median tab
            const $medianPill = $('#pill-median');
            if ($medianPill.length) {
                window.dynamicPills.activateTab('medianTableSection', $medianPill);
            }
        }
        
        window.dynamicPills.activateTab('medianTableSection', $('#pill-median'));
        showMedianTableWithLoading();
    });

    $(window).on('beforeunload', function() {
        if (window.performanceOptimizer?.observer) {
            window.performanceOptimizer.observer.disconnect();
        }
        
        if (window.syncDebounceTimer) {
            clearTimeout(window.syncDebounceTimer);
        }
        
        if (window.observerDebounceTimer) {
            clearTimeout(window.observerDebounceTimer);
        }
    });

    function getRowData($row) {
        const keywordPlannerId = $row.data('keyword-id') || null;
        const searchApiStatus = $row.find('.search-api-status .badge').text().trim();
        const aioStatus = $row.find('.aio-status .badge').text().trim();
        const clientMentionedStatus = $row.find('.client-mentioned-status .badge').text().trim();
        
        return {
            hasAio: true,
            keywordId: keywordPlannerId,
            keyword: $row.find('th[data-keyword]').data('keyword'),
            monthly_search: parseInt($row.find('td[data-avg_monthly_searches]').data('avg_monthly_searches') || 0),
            competition: $row.find('td[data-competition]').data('competition'),
            low_bid: parseFloat($row.find('td[data-low_top_of_page_bid]').data('low_top_of_page_bid') || 0),
            high_bid: parseFloat($row.find('td[data-high_top_of_page_bid]').data('high_top_of_page_bid') || 0),
            clicks: parseInt($row.find('td[data-clicks]').data('clicks') || 0),
            ctr: parseFloat($row.find('td[data-ctr]').data('ctr') || 0),
            impressions: parseInt($row.find('td[data-impressions]').data('impressions') || 0),
            position: parseFloat($row.find('td[data-position]').data('position') || 0),
            search_api_status: searchApiStatus,
            aio_status: aioStatus,
            client_mentioned_status: clientMentionedStatus
        };
    }


    function getKeywordPlannerRowData($row) {
        const keywordPlannerId = $row.data('kp-keyword-id') || null;
        const searchApiStatus = $row.find('.search-api-status .badge').text().trim();
        const aioStatus = $row.find('.aio-status .badge').text().trim();
        const clientMentionedStatus = $row.find('.client-mentioned-status .badge').text().trim();
        
        return {
            hasAio: true,
            keywordId: keywordPlannerId,
            keyword: $row.find('td[data-kp-keyword]').data('kp-keyword'),
            monthly_search: parseInt($row.find('td[data-kp-monthly_search]').data('kp-monthly_search') || 0),
            competition: $row.find('td[data-kp-competition]').data('kp-competition'),
            low_bid: parseFloat($row.find('td[data-kp-low_top_of_page_bid]').data('kp-low_top_of_page_bid') || 0),
            high_bid: parseFloat($row.find('td[data-kp-high_top_of_page_bid]').data('kp-high_top_of_page_bid') || 0),
            clicks: parseInt($row.find('td[data-kp-clicks]').data('kp-clicks') || 0),
            ctr: parseFloat($row.find('td[data-kp-ctr]').data('kp-ctr') || 0),
            impressions: parseInt($row.find('td[data-kp-impressions]').data('kp-impressions') || 0),
            position: parseFloat($row.find('td[data-kp-position]').data('kp-position') || 0),
            search_api_status: searchApiStatus,
            aio_status: aioStatus,
            client_mentioned_status: clientMentionedStatus
        };
    }


    function showMedianTableSection() {
        $('#medianTableSection').removeClass('d-none');
        // ADD: Hide the keywords table
        $('#keywordsTableSection').addClass('d-none');
        // ADD: Also hide keyword planner table if visible
        $('#keywordPlannerTableSection').addClass('d-none');

        // Scroll to median table
        $('html, body').animate({
            scrollTop: $('#medianTableSection').offset().top - 20
        }, 500);
    }

    // Also add this function to show keywords table when hiding median
    function showKeywordsTableSection() {
        $('#keywordsTableSection').removeClass('d-none');
        $('#medianTableSection').addClass('d-none');

        // Show keyword planner table if it has data
        if ($('#keywordPlannerTable tbody tr').length > 0) {
            $('#keywordPlannerTableSection').removeClass('d-none');
        }
    }


    function toggleMedianView() {
        $('#medianTableSection').removeClass('d-none');
        $('#keywordsTable').closest('.dataTables_wrapper').addClass('d-none');
        $('#keywordPlannerTableSection').addClass('d-none');
    }

    function populateKeywordPlannerTable(keywords) {
        const $tbody = $('#keywordPlannerTable tbody');
        $tbody.empty();
        
        const existingRowCount = $('#keywordPlannerTable tbody tr').length;
        
        keywords.forEach((keyword, idx) => {
            const index = existingRowCount + idx;
            var row = `
                <tr data-kp-index="kp-${index}" data-kp-keyword-id="${keyword.keyword_planner_id || ''}">
                    <td data-kp-keyword="${keyword.keyword || ''}">${keyword.keyword}</td>
                    <td data-kp-monthly_search="${keyword.avg_monthly_searches || 0}">${keyword.avg_monthly_searches || 0}</td>
                    <td data-kp-competition="${keyword.competition || ''}">${keyword.competition || ''}</td>
                    <td data-kp-low_top_of_page_bid="${keyword.low_top_of_page_bid || 0}">₹${keyword.low_top_of_page_bid || 0}</td>
                    <td data-kp-high_top_of_page_bid="${keyword.high_top_of_page_bid || 0}">₹${keyword.high_top_of_page_bid || 0}</td>
                    <td data-kp-clicks="${keyword.clicks || 0}">${keyword.clicks || 0}</td>
                    <td data-kp-ctr="${keyword.ctr || 0}">${keyword.ctr || 0}%</td>
                    <td data-kp-impressions="${keyword.impressions || 0}">${keyword.impressions || 0}</td>
                    <td data-kp-position="${keyword.position || 0}">${keyword.position || 0}</td>
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
            `;
            $tbody.append(row);
        });
        $('#keywordPlannerTableSection').removeClass('d-none');
    }

    function startKeywordPlannerStatusPolling() {
    
        // Clear any existing interval
        if (window.keywordPlannerPollingInterval) {
            clearInterval(window.keywordPlannerPollingInterval);
        }
        
        // Immediate first check
        checkKeywordPlannerStatuses();
        
        // Then poll every 10 seconds
        window.keywordPlannerPollingInterval = setInterval(checkKeywordPlannerStatuses, 10000);
    }

    function checkKeywordPlannerStatuses() {
        const indexes = [];
        
        $('#keywordPlannerTable tbody tr').each(function() {
            const index = $(this).data('index');
            if (index !== undefined) {
                indexes.push(index);
            }
        });
        
        if (indexes.length === 0) {
            // No keyword planner rows to check
            return;
        }
        
        $.ajax({
            url: "{{ route('check.keyword.status') }}",
            type: "GET",
            data: {
                session_id: sessionId,
                indexes: indexes,
                is_keyword_planner: true  // Flag to differentiate from other tables
            },
            success: function(response) {
                if (response.success) {
                    updateKeywordPlannerStatuses(response.results);
                    
                    // Check if all processed
                    const allProcessed = Object.values(response.results).every(r => r.processed);
                    if (allProcessed) {
                        clearInterval(window.keywordPlannerPollingInterval);
                        showToast('success', 'All keyword planner keywords have been processed!');
                    }
                }
            }
        });
    }
    function updateKeywordPlannerStatuses(results) {
    const updates = [];
    
    $.each(results, function(index, status) {
        const $row = $(`#keywordPlannerTable tr[data-index="${index}"]`);
        if ($row.length) {
            updates.push({
                $row: $row,
                status: status
            });
        }
    });
    
    // Apply all updates at once (batch update for performance)
    updates.forEach(function(update) {
        updateStatusCell(update.$row.find('.search-api-status'), update.status.search_api_status);
        updateStatusCell(update.$row.find('.aio-status'), update.status.aio_status);
        updateStatusCell(update.$row.find('.client-mentioned-status'), update.status.client_mentioned_status);
        
        // Update row highlighting
        if (update.status.aio_status === 'Yes') {
            update.$row.attr('data-has-aio', true);
        }
        if (update.status.client_mentioned_status === 'Yes') {
            update.$row.attr('data-has-mentioned', true);
        }
    });
    
    // Trigger save check and median recalculation
    checkAndTriggerSave('keyword_planner');
    
    const hasNewAioYes = updates.some(u => u.status.aio_status === 'Yes');
    if (hasNewAioYes && typeof calculateAndDisplayMedians === 'function') {
        if (window.medianRecalcTimer) {
            clearTimeout(window.medianRecalcTimer);
        }
        window.medianRecalcTimer = setTimeout(() => {
            calculateAndDisplayMedians();
        }, 1000);
    }
}

    function processKeywordPlannerKeywords(statuses) {
        
        $.each(statuses, function(index, status) {
            const $row = $('#keywordPlannerTable tbody tr[data-kp-index="' + index + '"]');
            
            if ($row.length) {
                // Update each status column
                updateStatusCell($row.find('.search-api-status'), status.search_api_status);
                updateStatusCell($row.find('.aio-status'), status.aio_status);
                updateStatusCell($row.find('.client-mentioned-status'), status.client_mentioned_status);
                
                // Mark row if has AIO
                if (status.aio_status === 'Yes' || status.aio_status === 'Done') {
                    $row.attr('data-has-aio', 'true');
                }
            } else {
                console.warn('⚠️ Row not found for index:', index);
            }
        });
        
        // Check if all keywords are processed
        checkAndTriggerSave('keyword-planner');
        
        // Sync to median table if function exists
        if (typeof window.syncAioKeywordsToMedianTable === 'function') {
            window.syncAioKeywordsToMedianTable();
        }
        
        // Recalculate median if any new AIO "Yes" found
        const hasNewAioYes = Object.values(statuses).some(s => 
            s.aio_status === 'Yes' || s.aio_status === 'Done'
        );
        
        if (hasNewAioYes && $('#medianTableSection').is(':visible')) {
            // New AIO data found Recalculating median table
            if (typeof calculateAndDisplayMedians === 'function') {
                setTimeout(() => calculateAndDisplayMedians(), 1000);
            }
        }
    }


    // Function to calculate median
    function calculateMedian(values) {
        if (values.length === 0) return 0;

        var sum = values.reduce(function(total, value) {
            return total + value;
        }, 0);

        return sum / values.length;
    }

    // Function to calculate individual median score
    function calculateIndividualMedianScore(data, medians) {
        var scores = [];

        // Monthly Search score (higher is better)
        if (medians.monthly_search > 0) {
            scores.push(Math.min(data.monthly_search / medians.monthly_search, 1));
        }

        // CTR score (higher is better)
        if (medians.ctr > 0) {
            scores.push(Math.min(data.ctr / medians.ctr, 1));
        }

        // Position score (lower is better, so we invert)
        if (medians.position > 0 && data.position > 0) {
            scores.push(Math.min(medians.position / data.position, 1));
        }

        // Impressions score (higher is better)
        if (medians.impressions > 0) {
            scores.push(Math.min(data.impressions / medians.impressions, 1));
        }

        // Clicks score (higher is better)
        if (medians.clicks > 0) {
            scores.push(Math.min(data.clicks / medians.clicks, 1));
        }

        // Calculate average score
        return scores.length > 0 ? scores.reduce((a, b) => a + b) / scores.length : 0;
    }

    // Function to save selected keywords to database
    function saveSelectedKeywords() {
        var selectedRows = [];

        $('.keyword-checkbox:checked').each(function() {
            var rowData = $(this).data('row');
            selectedRows.push(rowData);
        });

        if (selectedRows.length === 0) {
            alert('Please select at least one keyword to save.');
            return;
        }
        var medianName = prompt('📝 Name this Median Bucket:\n\nEnter a name to identify this set of median keywords (e.g. "Skin Care – Feb 2026").', '');

        if (medianName === null) {
            // User cancelled the prompt
            return;
        }

        medianName = medianName.trim();

        if (medianName === '') {
            showToast('error', 'Please enter a name for this Median Bucket before saving.');
            return;
        }

        $('#saveToDbBtn').prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

        var dateFrom = $('#filter_date_from').val();
        var dateTo = $('#filter_date_to').val();
        $.ajax({
            url: '{{ route("keywordmediansave") }}',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                rows: selectedRows,
                median_name: medianName,
                date_from: dateFrom,
                date_to: dateTo,
                client_property_id: '{{ $client_property_id ?? session('client_property_id') }}',
                domainmanagement_id: '{{ $domainmanagement_id ?? session('domainmanagement_id') }}',
                keyword_request_id: '{{ $keyword_request_id ?? session('keyword_request_id') }}',
                _token: '{{ csrf_token() }}'
            }),
            success: function(response) {
                $('#saveToDbBtn').prop('disabled', false)
                    .html('<i class="fas fa-save me-2"></i>Add to Bucket List');

                if (response.success) {
                    var savedName = response.median_name || medianName;
                    var successMsg = savedName
                        ? '✅ Median keywords saved successfully!\n\nBucket Name: "' + savedName + '"'
                        : '✅ Median keywords saved successfully!';
                    alert(successMsg);
                    // location.reload();
                } else {
                    alert(response.message || 'Save failed');
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
                $('#saveToDbBtn').prop('disabled', false)
                    .html('<i class="fas fa-save me-2"></i>Add to Bucket List');
                alert('Error saving keywords.');
                console.error(xhr.responseText);
            }
        });
    }

    function showToast(type, message) {
        const toastId = 'toast-' + Date.now();
        const icon = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        } [type] || 'info-circle';

        const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${icon} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

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
    let sessionId = null;
    let statusCheckInterval = null;

    // Function to check status for all keywords
    function checkAllKeywordStatuses() {
        if (!sessionId) return;

        // Get all row indexes
        const indexes = [];
        keywordsTable.rows({
            page: 'all',
            search: 'applied'
        }).every(function() {
            const $row = $(this.node());
            const index = $row.data('index');
            if (index !== undefined) {
                indexes.push(index);
            }
        });

        $.ajax({
            url: "{{ route('check.keyword.status') }}",
            type: "GET",
            data: {
                session_id: sessionId,
                indexes: indexes
            },
            success: function(response) {
                if (response.success) {
                    updateKeywordStatuses(response.results);

                    // Check if all are processed
                    const allProcessed = Object.values(response.results).every(r => r.processed);
                    if (allProcessed) {
                        clearInterval(statusCheckInterval);
                        showToast('success', 'All keywords have been processed!');
                        triggerAutoSaveForAioKeywords();
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
    function triggerAutoSaveForAioKeywords() {
        console.log('🔄 All keywords processed! Checking for AIO "Yes" keywords to auto-save...');
        
        const dataToSave = [];
        
        // Collect all rows with AIO status "Yes" from keywordsTable
        keywordsTable.rows().every(function() {
            const $row = $(this.node());
            const aioStatusBadge = $row.find('.aio-status .badge');
            const aioStatus = aioStatusBadge.text().trim();
            
            if (aioStatus === 'Yes' || aioStatus === 'Done') {
                // Extract row data
                const rowData = {
                    keyword: $row.find('[data-keyword]').data('keyword') || $row.find('th:first').text().trim(),
                    monthly_search: $row.find('[data-avg_monthly_searches]').data('avg_monthly_searches') || 0,
                    competition: $row.find('[data-competition]').data('competition') || 0,
                    low_bid: $row.find('[data-low_top_of_page_bid]').data('low_top_of_page_bid') || 0,
                    high_bid: $row.find('[data-high_top_of_page_bid]').data('high_top_of_page_bid') || 0,
                    clicks: $row.find('[data-clicks]').data('clicks') || 0,
                    ctr: $row.find('[data-ctr]').data('ctr') || 0,
                    impressions: $row.find('[data-impressions]').data('impressions') || 0,
                    position: $row.find('[data-position]').data('position') || 0
                };
                
                dataToSave.push(rowData);
            }
        });
        
        if (dataToSave.length > 0) {
            console.log(`✅ Found ${dataToSave.length} keywords with AIO status "Yes". Triggering auto-save...`);
            
            // Get date range from form or session
            const dateFrom = $('input[name="date_from"]').val() || '';
            const dateTo = $('input[name="date_to"]').val() || '';
            
            // Call the saveMedianData function
            saveMedianData(dataToSave, dateFrom, dateTo);
        } else {
            console.log('⚠️ No keywords with AIO status "Yes" found. Skipping auto-save.');
        }
    }

    // FIX #1: Batched status updates to reduce lag
    function updateKeywordStatuses(results) {
        // Collect all updates first
        const updates = [];
        
        $.each(results, function(index, status) {
            keywordsTable.rows().every(function() {
                const $row = $(this.node());
                const rowIndex = $row.data('index');
                if (rowIndex === parseInt(index)) {
                    updates.push({
                        $row: $row,
                        status: status
                    });
                    return false;
                }
            });
        });
        
        if (updates.length === 0) {
            return;
        }
        
        // Apply all updates in one batch
        updates.forEach(function(update) {
            updateStatusCell(update.$row.find('.search-api-status'), update.status.search_api_status);
            updateStatusCell(update.$row.find('.aio-status'), update.status.aio_status);
            updateStatusCell(update.$row.find('.client-mentioned-status'), update.status.client_mentioned_status);
        });
        
        // Single redraw at the end
        keywordsTable.draw(false);
        
        // Check if all keywords are now processed
        checkAndTriggerSave('initial');

        // When any AIO status becomes "Yes", debounce a median recalculation
        // (mirrors the same pattern in keyword-remaining-results.blade.php)
        const hasNewAioYes = updates.some(u => u.status.aio_status === 'Yes' || u.status.aio_status === 'Done');
        if (hasNewAioYes && typeof calculateAndDisplayMedians === 'function') {
            if (window.medianRecalcTimer) {
                clearTimeout(window.medianRecalcTimer);
            }
            window.medianRecalcTimer = setTimeout(() => {
                calculateAndDisplayMedians();
            }, 1000);
        }
        
        // SYNC TO MEDIAN TABLE
        if (typeof window.syncAioKeywordsToMedianTable === 'function') {
            window.syncAioKeywordsToMedianTable();
        }
    }

    // FIX #1: Improved updateStatusCell with batching
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

        // Queue the update instead of applying immediately
        updateQueue.push({
            $cell: $cell,
            html: `<span class="badge ${badgeClass}">${text}</span>`
        });
        
        // Clear existing timer
        if (updateTimer) {
            clearTimeout(updateTimer);
        }
        
        // Process queue after a short delay (batching multiple updates)
        updateTimer = setTimeout(function() {
            // Apply all queued updates at once
            updateQueue.forEach(function(item) {
                item.$cell.html(item.html);
            });
            updateQueue = [];
        }, 150); // 150ms batching window to reduce DOM thrashing
    }

    function checkAndTriggerSave(source) {

        if (areAllKeywordsProcessed()) {
            
            // Run median calculation and median-limit check once after all processing is done
            if (!hasTriggeredMedianAutoCheck && typeof calculateAndDisplayMedians === 'function') {
                hasTriggeredMedianAutoCheck = true;
                calculateAndDisplayMedians(false);
            }

            // Show completion message
            if (source === 'initial' || source === 'keyword-planner') {
                console.log('All keyword planner keywords processed!');
            }
            if (source === 'remaining') {
                console.log('All remaining keywords processed!');
            }

            // If there's pending median save data, save it now
            if (window.pendingMedianSave) {
                showToast('info', 'All keywords processed! Saving median data...');

                const data = window.pendingMedianSave;
                saveMedianData(
                    data.rows,
                    data.date_from,
                    data.date_to
                );
            }

            // Stop all status polling
            if (statusCheckInterval) {
                clearInterval(statusCheckInterval);
                statusCheckInterval = null;
            }
            

        } else {
            console.log('⏳ Still processing keywords...');
        }
    }
    window.keywordProcessingFix = {
        areAllKeywordsProcessed,
        saveMedianData,
        checkAndTriggerSave,
        continueMedianCalculation
    };

    // Also monitor changes via MutationObserver for better performance
    const aioObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && 
                        (node.classList.contains('badge') || node.querySelector('.badge'))) {
                        const badge = node.classList.contains('badge') ? node : node.querySelector('.badge');
                        if (badge && (badge.textContent.trim() === 'Yes' || badge.textContent.trim() === 'Done')) {
                            setTimeout(() => {
                                if (typeof window.updatePillsVisibility === 'function') {
                                    window.updatePillsVisibility();
                                }
                            }, 500);
                        }
                    }
                });
            }
        });
    });

    // Start observing when table is loaded
    $(document).ready(function() {
        setTimeout(() => {
            const aioCells = document.querySelectorAll('.aio-status');
            aioCells.forEach(cell => {
                aioObserver.observe(cell, { childList: true, subtree: true });
            });
        }, 2000);

        $('#fetchMorekeywordplannerBtn').on('click', function() {
            
            $('button[data-target="keywordsTableSection"]').trigger('click');
            $('#keywordPlannerTable').removeClass('d-none');
            
            const btn = $(this);
            const originalText = btn.html();
            
            // Get current keyword count
            const currentCount = $('#keywordPlannerTable tbody tr').length;
            const medianLimit = parseInt($('#median_limit').val()) || 10;
            const remainingNeeded = Math.max(0, medianLimit - currentCount);
            let keywords = getExistingKeywordPlannerKeywords();
            
            // Show confirmation modal
            const message = remainingNeeded > 0 
                ? `You have ${currentCount} out of ${medianLimit} keywords. Do you want to fetch ${remainingNeeded} more?`
                : `You already have ${currentCount} keywords. Do you want to fetch more?`;

            const confirmFetch = confirm(message);
            if (confirmFetch) {
                triggerKeywordPlannerFetch();
            }
        });
        function triggerKeywordPlannerFetch() {
            const btn = $('#fetchMorekeywordplannerBtn');
            const originalText = btn.html();
            
            // Get form data
            let formData = $("#keywordFilterForm").serialize();
            const domainName = $('input[name="domain_name"]').val();
            const medianLimit = parseInt($('#median_limit').val()) || 10;
            
            // Add parameters
            formData += '&domain_name=' + encodeURIComponent(domainName) +
                '&fetch_from_keyword_planner=true' +
                '&limit=' + medianLimit +
                '&keyword_request_id=' + (sessionStorage.getItem('keyword_request_id') || '') +
                '&client_property_id=' + $("input[name='client_property_id']").val() +
                '&domainmanagement_id=' + $("input[name='domainmanagement_id']").val();
            
            // Show loading
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Fetching...');
            
            $.ajax({
                url: "{{ route('fetch.keyword.planner.keywords') }}",
                type: "POST",
                data: formData,
                beforeSend: function() {
                    $('#keywordPlannerTableSectionloading').removeClass('d-none');
                    $("#keywordPlannerTableSectionloading").html(`
                        <div class="text-center p-5">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-3">
                                <h5>Fetching Keywords from Keyword Planner...</h5>
                                <div class="progress mt-3" style="height: 10px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                                </div>
                                <p class="text-muted mt-2">This may take a few moments...</p>
                            </div>
                        </div>
                    `);
                },
                success: function(response) {
                    
                    if (response.success && response.keywords && response.keywords.length > 0) {
                        // Hide loading
                        $('#keywordPlannerTableSectionloading').addClass('d-none');
                        
                        // Populate table
                        populateKeywordPlannerTable(response.keywords);
                        
                        // Show table
                        $('#keywordPlannerTableSection').removeClass('d-none');
                        $('#keywordPlannerTableSection_main').removeClass('d-none');
                        
                        // Start processing
                        $.ajax({
                            url: "{{ route('process.keyword.planner.keywords') }}",
                            type: "POST",
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                keyword_request_id: response.keyword_request_id,
                                session_id: "{{ session()->getId() }}",
                            },
                            success: function(processResponse) {
                                console.log('✅ Processing started');
                                startKeywordPlannerStatusPolling();
                                showToast('success', `Successfully fetched ${response.keywords.length} keywords! Processing started...`);
                            },
                            error: function(xhr) {
                                console.error('❌ Processing failed:', xhr.responseText);
                                showToast('error', 'Keywords fetched but processing failed. Please refresh.');
                            }
                        });
                        
                    } else {
                        $('#keywordPlannerTableSectionloading').addClass('d-none');
                        showToast('info', 'No additional keywords found in Keyword Planner');
                    }
                },
                error: function(xhr) {
                    console.error('❌ Fetch failed:', xhr.responseText);
                    $('#keywordPlannerTableSectionloading').addClass('d-none');
                    showToast('error', 'Failed to fetch keywords. Please try again.');
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        }
        function getExistingKeywordPlannerKeywords() {
            let keywords = [];
            $('#keywordPlannerTable tbody tr').each(function () {
                let keyword = $(this).find('td:first').text().trim();
                if (keyword) keywords.push(keyword);
            });
            return keywords;
        }
        // Replace this entire function in create.blade.php
        $('#confirmFetchMoreData').on('click', function() {
            console.log('🔄 Confirm Fetch More Keywords clicked...');
            const btn = $(this);
            const originalText = btn.text();
            
            // Show progress
            $('#fetchProgress').removeClass('d-none');
            btn.prop('disabled', true).text('Fetching...');
            
            // Get form data
            let formData = $("#keywordFilterForm").serialize();
            const mode = $('input[name="filter_type"]:checked').val();
            
            // Add flag to indicate we want more keywords
            
            const existingRows = $('#remainingkeywordsTable tbody tr').length;
            const tableextractedRows = keywordsTable.data().length + $('#remainingkeywordsTable tbody tr').length;
            const medianLimit = parseInt($('#median_limit').val()) || 10;
            const currentRemaining = aio_result_extracted || 0;
            const current_extract = Math.abs(currentRemaining-medianLimit);
            if(current_extract == 0){
                showToast('info', 'No more keywords needed to reach the median limit.');
                $('#fetchProgress').addClass('d-none');
                btn.prop('disabled', false).text(originalText);
                return
            }
            const remainingcount = existingRows > 0 ? currentRemaining + 5 : 5;
            formData += '&mode=' + mode + '&fetch_more=true&remaining_limit=50&current_extracted=' + tableextractedRows;
            
            $.ajax({
                url: "{{ route('keyword-store-more') }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    // $('.nav-pill').removeClass('active');
                    // $('#pill-keywords').addClass('active');
                    
                    window.dynamicPills.activateTab('keywordsTableSection', $('#pill-keywords'));
                    // Check if remaining keywords table section exists
                    if ($('#remainingkeywordsTable tbody tr').length === 0) {
                        // If table doesn't exist then append the full card
                        $("#result_box").append(response.html);
                    } else {
                        // If table exists then append only the rows to the existing table
                        const $newHtml = $(response.html);
                        const $newRows = $newHtml.find('#remainingkeywordsTable tbody tr');
                        
                        if ($newRows.length > 0) {
                            // Get existing DataTable instance
                            // const existingTable = $('#remainingkeywordsTable').DataTable();
                            
                            // // Add rows one by one to DataTable
                            // $newRows.each(function() {
                            //     const rowNode = this;
                            //     existingTable.row.add($(rowNode)).draw(false);
                            // });
                            $('#remainingkeywordsTable tbody').append($newRows);
                            
                            // Update the card title with new total count
                            const totalCount = $('#remainingkeywordsTable tbody tr').length;
                            $('#remainingkeywordsTableSection .card-header h5').text(`Keyword Planner Remaining Data (${totalCount})`);
                        }
                    }
                    
                    btn.prop('disabled', false).text(originalText);
                    // Hide modal and reset
                    bootstrap.Modal.getInstance(document.getElementById('fetchMoreModal')).hide();
                    $('#fetchProgress').addClass('d-none');
                    $('.progress-bar').css('width', '0%');
                    
                    // Update dynamic pills
                    setTimeout(function() {
                        if (typeof window.dynamicPills !== 'undefined') {
                            window.dynamicPills.update();
                        }
                    }, 500);
                    
                    // Show success message
                    if (typeof showToast === 'function') {
                        showToast('success', 'Successfully fetched ' + response.new_keywords + ' more keywords!');
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    btn.prop('disabled', false).text(originalText);
                    $('#fetchProgress').addClass('d-none');
                    alert('Failed to fetch more keywords. Please try again.');
                }
            });
            
            // btn.prop('disabled', false).text(originalText);
        });
        
    });
</script>