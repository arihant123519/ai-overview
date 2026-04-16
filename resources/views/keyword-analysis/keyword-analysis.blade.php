@extends('layouts.page-app')
@section("content")

{{-- Pass all keyword planner data to JS so the Median tab can process it client-side --}}
@php
    $jsKeywords = $keywordplanner->map(function($k) {
        return [
            'keyword'        => $k->keyword_p,
            'monthly_search' => $k->monthlysearch_p  ?? 0,
            'competition'    => $k->competition_p    ?? 'UNKNOWN',
            'low_bid'        => $k->low_bid_p        ?? 0,
            'high_bid'       => $k->high_bid_p       ?? 0,
            'clicks'         => $k->clicks_p         ?? 0,
            'ctr'            => $k->ctr_p            ?? 0,
            'impressions'    => $k->impressions_p    ?? 0,
            'position'       => $k->position_p       ?? 0,
            'has_ai_overview'=> (bool)$k->has_ai_overview,
        ];
    })->values();
@endphp

<style>
    /* ── Tab pills ── */
    .ka-pills {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    .ka-pill {
        border-radius: 20px;
        padding: 7px 18px;
        font-size: 14px;
        font-weight: 500;
        border: 2px solid #0d6efd;
        cursor: pointer;
        transition: all .25s;
        background: #fff;
        color: #0d6efd;
    }
    .ka-pill:hover   { box-shadow: 0 4px 10px rgba(13,110,253,.25); transform: translateY(-1px); }
    .ka-pill.active  { background: #0d6efd; color: #fff; }

    /* ── Median table ── */
    #medianSection table th,
    #medianSection table td { vertical-align: middle; }
    .position-good   { color: #198754; font-weight: 600; }
    .position-mid    { color: #fd7e14; font-weight: 600; }
    .position-bad    { color: #dc3545; font-weight: 600; }
    .ctr-good        { color: #198754; }
    .ctr-mid         { color: #fd7e14; }
    .ctr-bad         { color: #dc3545; }

    /* median stat row */
    .median-stat-row { background: #f0f7ff !important; font-weight: 700; }
    .median-stat-row td { border-top: 2px solid #0d6efd !important; }

    /* Add to Bucket List button transitions */
    #kaBucketBtn { transition: all 0.3s ease; }
    #kaBucketBtn:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
</style>

<div class="page-content">
    <div class="container-fluid">

        <!-- page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Analysed Keywords</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item">Analysed Keywords</li>
                            <li class="breadcrumb-item active">{{$keywordRequest->keyword}}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page title -->

        <div class="row">
            <div class="col-lg-12">

                <!-- ── Tab Pills ── -->
                <div class="ka-pills">
                    <button class="ka-pill active" id="pill-keywords" onclick="switchTab('keywords')">
                        <i class="fas fa-table me-1"></i> Keyword Results (All Data)
                    </button>
                    <button class="ka-pill" id="pill-median" onclick="switchTab('median')">
                        <i class="fas fa-calculator me-1"></i> Median Table
                    </button>
                </div>

                <!-- ══════════════════════════════════════
                     TAB 1 — Keyword Results
                ══════════════════════════════════════ -->
                <div id="keywordsSection">
                    <div class="card">
                        <div class="card-body">
                            <table id="scroll-horizontal" class="table nowrap align-middle" style="width:100%">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Keyword</th>
                                        <th scope="col">Organic Results</th>
                                        <th scope="col">AI Overview</th>
                                        <th scope="col">Related Questions</th>
                                        <th scope="col">Related Searches</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($keywordplanner->isNotEmpty())
                                        @foreach($keywordplanner as $key => $keyword)
                                        <tr>
                                            <td class="fw-medium">{{ $key + 1 }}</td>
                                            <td>{{ $keyword->keyword_p }}</td>

                                            {{-- Organic Results --}}
                                            <td>
                                                @if($keyword->has_organic_results)
                                                    <span class="badge bg-success-subtle text-success">Available</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Not Available</span>
                                                @endif
                                            </td>

                                            {{-- AI Overview --}}
                                            <td>
                                                @if($keyword->has_ai_overview)
                                                    <span class="badge bg-success-subtle text-success">Available</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Not Available</span>
                                                @endif
                                            </td>

                                            {{-- Related Questions --}}
                                            <td>
                                                @if($keyword->has_related_questions)
                                                    <span class="badge bg-success-subtle text-success">Available</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Not Available</span>
                                                @endif
                                            </td>

                                            {{-- Related Searches --}}
                                            <td>
                                                @if($keyword->has_related_searches)
                                                    <span class="badge bg-success-subtle text-success">Available</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Not Available</span>
                                                @endif
                                            </td>

                                            <td>{{ $keyword->created_at }}</td>
                                            <td>
                                                <div class="dropdown d-inline-block">
                                                    <button class="btn btn-soft-secondary btn-sm dropdown"
                                                            type="button"
                                                            data-bs-toggle="dropdown"
                                                            aria-expanded="false">
                                                        <i class="ri-more-fill align-middle"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ url('extracted-aio-result/' . $keyword->id) }}">
                                                                <i class="ri-eye-fill align-bottom me-2 text-muted"></i> View
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════
                     TAB 2 — Median Table  (hidden by default)
                ══════════════════════════════════════ -->
                <div id="medianSection" style="display:none;">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0" id="medianTableTitle">
                                Median Calculation Table (AIO Insights Only)
                            </h5>
                            <div class="d-flex gap-2">
                                <button
                                    type="button"
                                    id="kaBucketBtn"
                                    class="btn btn-success"
                                    disabled
                                    title="Please select at least one keyword."
                                    onclick="kaSaveSelectedKeywords()">
                                    <i class="fas fa-save me-2"></i>Add to Bucket List
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Info alert -->
                            <div class="alert alert-info" id="medianInfoAlert">
                                <i class="fas fa-info-circle me-2"></i>
                                Showing only keywords that have AIO Insights available.
                            </div>

                            <!-- Empty / no-AIO warning (hidden until needed) -->
                            <div class="alert alert-warning d-none" id="medianNoDataAlert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No valid keywords with AIO Insights found. Please wait for processing to complete.
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="medianTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="kaSelectAll" title="Select / Deselect All">
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
                                        {{-- Populated by JS --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div><!-- /medianSection -->

            </div><!--end col-->
        </div><!--end row-->
    </div><!-- /container-fluid -->
</div><!-- /page-content -->

@endsection

@section("jscontent")
<script>
// ── All keyword planner data passed from server ──────────────────────────────
const ALL_KEYWORDS = @json($jsKeywords);

// Track whether the median table has been built already
// (so switching back and forth doesn't re-prompt)
let kaMedianBuilt   = false;
let kaMedianLimit   = 10;   // default; overwritten on first prompt

// ── Tab switcher ─────────────────────────────────────────────────────────────
function switchTab(tab) {
    if (tab === 'keywords') {
        document.getElementById('keywordsSection').style.display = '';
        document.getElementById('medianSection').style.display   = 'none';
        document.getElementById('pill-keywords').classList.add('active');
        document.getElementById('pill-median').classList.remove('active');
    } else {
        // Switch visual state first
        document.getElementById('pill-keywords').classList.remove('active');
        document.getElementById('pill-median').classList.add('active');

        if (!kaMedianBuilt) {
            // Ask for median limit (only the very first time)
            const input = prompt(
                '📊 Set Median Limit:\n\nHow many AIO Insights keywords should be used for median calculation?\n(e.g. enter 10 to use the 10 middle-ranked records)',
                '10'
            );

            // User cancelled → stay on Keywords tab
            if (input === null) {
                document.getElementById('pill-keywords').classList.add('active');
                document.getElementById('pill-median').classList.remove('active');
                return;
            }

            const parsed = parseInt(input, 10);
            kaMedianLimit = (!isNaN(parsed) && parsed > 0) ? parsed : 10;
            kaMedianBuilt = true;
        }

        document.getElementById('keywordsSection').style.display = 'none';
        document.getElementById('medianSection').style.display   = '';
        buildMedianTable(kaMedianLimit);
    }
}

// ── Build Median Table ────────────────────────────────────────────────────────
function buildMedianTable(medianLimit) {
    const aioKeywords = ALL_KEYWORDS.filter(k => k.has_ai_overview);
    const total       = ALL_KEYWORDS.length;
    const aioCount    = aioKeywords.length;

    const infoAlert   = document.getElementById('medianInfoAlert');
    const noDataAlert = document.getElementById('medianNoDataAlert');
    const title       = document.getElementById('medianTableTitle');
    const tbody       = document.getElementById('medianTableBody');

    if (aioCount === 0) {
        infoAlert.classList.add('d-none');
        noDataAlert.classList.remove('d-none');
        title.textContent = `Median Calculation Table (0/${total} AIO Insights Records)`;
        tbody.innerHTML = '';
        kaUpdateBucketBtn();
        return;
    }

    noDataAlert.classList.add('d-none');
    infoAlert.classList.remove('d-none');

    // ── Apply middle-median slicing (mirrors keyword-results logic) ───────────
    // Sort by monthly_search ascending for median slice
    const sorted = [...aioKeywords].sort((a, b) => a.monthly_search - b.monthly_search);
    let limitedData;

    if (sorted.length <= medianLimit) {
        limitedData = sorted;
    } else {
        const halfLimit   = Math.floor(medianLimit / 2);
        const middleIndex = Math.floor(sorted.length / 2);
        let startIndex, endIndex;

        if (medianLimit % 2 === 0) {
            startIndex = middleIndex - halfLimit;
            endIndex   = middleIndex + halfLimit;
        } else {
            startIndex = middleIndex - halfLimit;
            endIndex   = middleIndex + halfLimit + 1;
        }

        startIndex = Math.max(0, startIndex);
        endIndex   = Math.min(sorted.length, endIndex);

        // Edge adjustments
        if (endIndex - startIndex < medianLimit) {
            if (startIndex === 0) {
                endIndex = Math.min(sorted.length, medianLimit);
            } else if (endIndex === sorted.length) {
                startIndex = Math.max(0, sorted.length - medianLimit);
            }
        }

        limitedData = sorted.slice(startIndex, endIndex);
    }

    // Update header title
    title.textContent = `Median Calculation Table (${limitedData.length}/${medianLimit} AIO Insights Records)`;

    // Update info alert
    if (aioCount >= medianLimit) {
        infoAlert.innerHTML = `<i class="fas fa-info-circle me-2"></i>
            Showing ${limitedData.length} out of ${aioCount} AIO Insights keywords (median limit: ${medianLimit}).`;
    } else {
        infoAlert.innerHTML = `<i class="fas fa-info-circle me-2"></i>
            Showing all <strong>${aioCount}</strong> AIO Insights keywords found so far.`;
    }

    // ── Build rows HTML ───────────────────────────────────────────────────────
    let rowsHtml = '';
    limitedData.forEach((k, idx) => {
        const ctrClass = k.ctr > 5  ? 'ctr-good' : (k.ctr > 2  ? 'ctr-mid' : 'ctr-bad');
        const posClass = k.position <= 3 ? 'position-good' : (k.position <= 10 ? 'position-mid' : 'position-bad');

        // Safely encode the full row data as a JSON attribute for retrieval on save
        const rowJson     = JSON.stringify({
            keyword:        k.keyword,
            monthly_search: k.monthly_search,
            competition:    k.competition,
            low_bid:        k.low_bid,
            high_bid:       k.high_bid,
            clicks:         k.clicks,
            ctr:            k.ctr,
            impressions:    k.impressions,
            position:       k.position,
        });
        const safeKeyword = escHtml(k.keyword);
        const safeJson    = rowJson.replace(/"/g, '&quot;');

        rowsHtml += `
            <tr>
                <td>
                    <div class="form-check">
                        <input class="form-check-input ka-row-checkbox"
                               type="checkbox"
                               value="${safeKeyword}"
                               data-row="${safeJson}"
                               data-index="${idx}">
                    </div>
                </td>
                <td><strong>${safeKeyword}</strong></td>
                <td>${k.monthly_search.toLocaleString()}</td>
                <td>${escHtml(k.competition)}</td>
                <td>₹${parseFloat(k.low_bid).toFixed(2)}</td>
                <td>₹${parseFloat(k.high_bid).toFixed(2)}</td>
                <td class="text-end">${k.clicks}</td>
                <td class="text-end">
                    <span class="${ctrClass}">${parseFloat(k.ctr).toFixed(2)}</span>
                </td>
                <td class="text-end">${parseInt(k.impressions).toLocaleString()}</td>
                <td class="text-end">
                    <span class="${posClass}">${parseFloat(k.position).toFixed(1)}</span>
                </td>
            </tr>`;
    });

    // ── Median stat row ───────────────────────────────────────────────────────
    const medianOf = (arr) => {
        const s   = [...arr].sort((a, b) => a - b);
        const mid = Math.floor(s.length / 2);
        return s.length % 2 !== 0 ? s[mid] : (s[mid - 1] + s[mid]) / 2;
    };

    const medClicks      = medianOf(limitedData.map(k => parseFloat(k.clicks)       || 0));
    const medCtr         = medianOf(limitedData.map(k => parseFloat(k.ctr)          || 0));
    const medImpressions = medianOf(limitedData.map(k => parseFloat(k.impressions)  || 0));
    const medPosition    = medianOf(limitedData.map(k => parseFloat(k.position)     || 0));
    const medMonthly     = medianOf(limitedData.map(k => parseFloat(k.monthly_search) || 0));
    const medLowBid      = medianOf(limitedData.map(k => parseFloat(k.low_bid)      || 0));
    const medHighBid     = medianOf(limitedData.map(k => parseFloat(k.high_bid)     || 0));

    rowsHtml += `
        <tr class="median-stat-row">
            <td></td>
            <td><i class="fas fa-chart-bar me-1 text-primary"></i>MEDIAN</td>
            <td>${medMonthly.toLocaleString()}</td>
            <td>—</td>
            <td>₹${medLowBid.toFixed(2)}</td>
            <td>₹${medHighBid.toFixed(2)}</td>
            <td class="text-end">${medClicks}</td>
            <td class="text-end">${medCtr.toFixed(2)}</td>
            <td class="text-end">${parseInt(medImpressions).toLocaleString()}</td>
            <td class="text-end">${medPosition.toFixed(1)}</td>
        </tr>`;

    tbody.innerHTML = rowsHtml;

    // Reset select-all and update button state
    document.getElementById('kaSelectAll').checked = false;
    kaUpdateBucketBtn();
}

// ── Checkbox helpers ─────────────────────────────────────────────────────────
document.addEventListener('change', function(e) {
    // Select-all toggle
    if (e.target && e.target.id === 'kaSelectAll') {
        const checked = e.target.checked;
        document.querySelectorAll('.ka-row-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        kaUpdateBucketBtn();
    }

    // Individual row checkbox
    if (e.target && e.target.classList.contains('ka-row-checkbox')) {
        const total   = document.querySelectorAll('.ka-row-checkbox').length;
        const checked = document.querySelectorAll('.ka-row-checkbox:checked').length;
        document.getElementById('kaSelectAll').checked = (total === checked && total > 0);
        kaUpdateBucketBtn();
    }
});

function kaUpdateBucketBtn() {
    const btn     = document.getElementById('kaBucketBtn');
    const checked = document.querySelectorAll('.ka-row-checkbox:checked').length;
    btn.disabled  = (checked === 0);
    btn.title     = checked > 0
        ? `${checked} keyword(s) selected`
        : 'Please select at least one keyword.';
}

// ── Save selected keywords to bucket ─────────────────────────────────────────
function kaSaveSelectedKeywords() {
    const selectedRows = [];

    document.querySelectorAll('.ka-row-checkbox:checked').forEach(cb => {
        try {
            // Decode the HTML-encoded JSON stored in data-row
            const rawJson   = cb.getAttribute('data-row').replace(/&quot;/g, '"');
            const rowData   = JSON.parse(rawJson);
            selectedRows.push(rowData);
        } catch (err) {
            console.warn('Could not parse row data:', err);
        }
    });

    if (selectedRows.length === 0) {
        alert('Please select at least one keyword to save.');
        return;
    }

    // Ask for median bucket name (mirrors keyword-results behaviour)
    const medianName = prompt(
        '📝 Name this Median Bucket:\n\nEnter a name to identify this set of median keywords (e.g. "Skin Care – Feb 2026").\nLeave blank to save without a name.',
        ''
    );

    // User cancelled
    if (medianName === null) {
        return;
    }

    const bucketName = medianName.trim() || null;

    const btn = document.getElementById('kaBucketBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

    fetch('{{ route("keywordmediansave") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN':  '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            rows:                selectedRows,
            median_name:         bucketName,
            date_from:           null,
            date_to:             null,
            client_property_id:  '{{ $keywordRequest->client_property_id }}',
            domainmanagement_id: '{{ $keywordRequest->domainmanagement_id }}',
            keyword_request_id:  '{{ $keywordRequest->id }}',
            _token:              '{{ csrf_token() }}'
        })
    })
    .then(res => res.json())
    .then(response => {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i>Add to Bucket List';

        if (response.success) {
            const savedName    = response.median_name || bucketName;
            const successMsg   = savedName
                ? '✅ Median keywords saved successfully!\n\nBucket Name: "' + savedName + '"'
                : '✅ Median keywords saved successfully!';
            alert(successMsg);
        } else {
            alert(response.message || 'Save failed. Please try again.');
        }
    })
    .catch(err => {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i>Add to Bucket List';
        console.error('Error saving keywords:', err);
        alert('Error saving keywords. Please try again.');
    });
}

// ── Utility ───────────────────────────────────────────────────────────────────
function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}
</script>
@endsection