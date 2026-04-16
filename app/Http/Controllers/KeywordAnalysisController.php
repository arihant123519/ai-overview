<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use App\Helpers\GeneralHelper;
use App\Jobs\ProcessGscKeywordJob;
use App\Jobs\ProcessKeywordJob;
use App\Jobs\ProcessKeywordStatusJob;
use App\Models\Ads;
use App\Models\AiOverview;
use App\Models\Client_propertiesModel;
use App\Models\ClusterRequest;
use App\Models\DomainManagementModel;
use App\Models\GoogledataModel;
use App\Models\HistoryLog;
use App\Models\KeywordPlanner;
use App\Models\KeywordRequest;
use App\Models\MedianFetch;
use App\Models\MedianInfo;
use App\Models\OrganicResult;
use App\Models\ParentKeyword;
use App\Models\RelatedQuestions;
use App\Models\RelatedSearches;
use App\Services\GoogleSearchConsoleService;
use App\Services\KeywordPlannerService;
use Exception;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Client;
use Illuminate\Http\Request;
use Google\Service\Webmasters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI;

class KeywordAnalysisController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // protected $audioAnalyzer;
    private $openaiApiKey;
    private $openai;
    public $endpoints = [];

    private $geminiApiKey;
    private $projectId;
    private $location;
    private $modelId;
    private $keyFilePath;

    private $whisperEndpoint = 'https://api.openai.com/v1/audio/transcriptions';
    private $chatEndpoint = 'https://api.openai.com/v1/chat/completions';
    private $geminiEndpoint;
    public $gscService;
    public $kpService;

    public function __construct(Request $request)
    {
        date_default_timezone_set('Asia/Kolkata');
        $this->openaiApiKey = env('OPENAI_API_KEY');
        $this->geminiApiKey = env('GEMINI_API_KEY');

        $this->projectId = env('GCP_PROJECT_ID', 'composed-arch-472508-u2');
        $this->location = env('GCP_LOCATION', 'us-central1');
        $this->modelId = env('GEMINI_MODEL_ID', 'gemini-2.5-flash');
        $this->keyFilePath = storage_path('app/service-account-key.json');

        $this->geminiEndpoint = "https://{$this->location}-aiplatform.googleapis.com/v1/projects/{$this->projectId}/locations/{$this->location}/publishers/google/models/{$this->modelId}:generateContent";
        $this->openai = OpenAI::client(env('GEMINI_KEY'));
        $this->endpoints = [];

        $customerId = DomainManagementModel::where('id', $request->domainmanagement_id)->value('customer_id');
        $managerId = DomainManagementModel::where('id', $request->domainmanagement_id)->value('manager_id');

        $this->gscService = new GoogleSearchConsoleService($customerId,$managerId);
        $this->kpService = new KeywordPlannerService($customerId,$managerId);
    }

    public function index($id)
    {
        $keywordRequest = KeywordRequest::where('client_property_id', $id)->get();
        return view('keyword-analysis.index', compact('id', 'keywordRequest'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id)
    {
        $client_property = Client_propertiesModel::where('id', $id)->first();
        $domainmanagement_id = $client_property->domainmanagement_id;
        $domain_name = $client_property->domain;
        // dd($client_property->toArray());
        return view('keyword-analysis.create', compact('id', 'domainmanagement_id', 'domain_name'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $data = [];
        $client = new KeywordRequest;
        $data = $request->all();

        $client->keyword = $data["keyword"];
        $client->domainmanagement_id = $data["domainmanagement_id"];
        $client->client_property_id = $data["client_property_id"];
        $client->save();


        if ($client->save()) {
            $request->session()->flash("message", "Client has been added successfully");
            return redirect('/add-client');
        } else {
            $request->session()->flash("error", "Unable to add client. Please try again later");
        }
    }

    public function keywordStore(Request $request)
    {
        // dd($request->all());

        $url = $request->domain_name;
        $keyword = $request->master_keyword; // Changed from $request->keyword to $request->master_keyword
        $limit =50; 
        $median_limit = $request->limit;
        // 1. First store or get the KeywordRequest
        $info = [
            'client_property_id' => $request->client_property_id,
            'domainmanagement_id' => $request->domainmanagement_id,
            'keyword' => $request->master_keyword ?? $url, // Use master_keyword if available, otherwise use URL
            'domain_name' => $request->domain_name,
            'master_keyword' => $request->master_keyword ?? null,
        ];

        // Store KeywordRequest
        $keyword_request = KeywordRequest::where([
            ['keyword', $info['keyword']],
            ['client_property_id', $info['client_property_id']],
            ['domainmanagement_id', $info['domainmanagement_id']]
        ])->first();

        // dd($keyword_request);
        if (!$keyword_request) {
            $keyword_request = KeywordRequest::create([
                'domainmanagement_id' => $info['domainmanagement_id'],
                'client_property_id' => $info['client_property_id'],
                'keyword' => $info['keyword'],
            ]);
        }
        // dd($keyword_request);
        $keyword_request_id = $keyword_request->id;

        $keyword_planner = KeywordPlanner::where([
            ['keyword_p', $info['keyword']],
            ['keyword_request_id', $keyword_request->id],
            ['client_property_id', $info['client_property_id']],
            ['domainmanagement_id', $info['domainmanagement_id']]
        ])->first();
        $ai_overview = null;
        if ($keyword_planner) {
            $ai_overview = AiOverview::where([
                ['keyword_planner_id', $keyword_planner->id],
                ['keyword_request_id', $keyword_request->id],
                ['client_property_id', $info['client_property_id']],
                ['domainmanagement_id', $info['domainmanagement_id']]
            ])->first();
        }

        // Store keyword_request_id in session for later use
        session(['keyword_request_id' => $keyword_request_id]);

        $filters = array_filter(
            $request->only(['min_searches', 'max_searches', 'competition', 'min_bid', 'max_bid', 'date_from', 'date_to', 'min_clicks', 'max_clicks', 'min_ctr', 'max_ctr', 'min_impressions', 'max_impressions']),
            fn($value) => !is_null($value) && $value !== ''
        );
        // Get the keywords based on URL or keyword
        if (!empty($url) && empty($keyword)) {
            $keyplan = $this->enrichWithGscData(null, $url, $request->date_from ?? null, $request->date_to ?? null, $limit);
            if (!empty($filters)) {
                $keyplan = $this->applyFilters($keyplan, $filters);
            }
            $totalKeywords = count($keyplan);
            $isFullResults = ($totalKeywords >= $limit);
            $remainingKeywords = $limit - $totalKeywords;
        } else {
            $keyplan = $this->kpService->searchKeywords($keyword, $limit, $request->date_from ?? null, $request->date_to ?? null, ['2840'], '1000', $request->client_property_id);
            
            unset($filters['date_from'], $filters['date_to']);
            if (!empty($filters)) {
                $keyplan = $this->applyFilters($keyplan, $filters);
            }
        }


        $storedKeywords = [];
        foreach ($keyplan as $keywordData) {
            // Check if the keyword already exists for this request
            $existingKeywordPlanner = KeywordPlanner::where('keyword_p', $keywordData['keyword'])
                ->where('keyword_request_id', $keyword_request->id)
                ->first();
            
            if($existingKeywordPlanner){
            // Update existing record
                $existingKeywordPlanner->update([
                    'monthlysearch_p' => $keywordData['avg_monthly_searches'] ?? $existingKeywordPlanner->monthlysearch_p,
                    'competition_p' => $keywordData['competition'] ?? $existingKeywordPlanner->competition_p,
                    'low_bid_p' => $keywordData['low_top_of_page_bid'] ?? $existingKeywordPlanner->low_bid_p,
                    'high_bid_p' => $keywordData['high_top_of_page_bid'] ?? $existingKeywordPlanner->high_bid_p,
                    'monthlysearchvolume_p' => null,
                    'clicks_p' => $keywordData['clicks'] ?? $existingKeywordPlanner->clicks_p,
                    'ctr_p' => $keywordData['ctr'] ?? $existingKeywordPlanner->ctr_p,
                    'impressions_p' => $keywordData['impressions'] ?? $existingKeywordPlanner->impressions_p,
                    'position_p' => $keywordData['position'] ?? $existingKeywordPlanner->position_p,
                ]);
            }else{

                KeywordPlanner::create([
                    'domainmanagement_id' => $info['domainmanagement_id'],
                    'client_property_id' => $info['client_property_id'],
                    'keyword_request_id' => $keyword_request->id,
                    'keyword_p' => $keywordData['keyword'],
                    'monthlysearch_p' => $keywordData['avg_monthly_searches'] ?? null,
                    'competition_p' => $keywordData['competition'] ?? null,
                    'low_bid_p' => $keywordData['low_top_of_page_bid'] ?? null,
                    'high_bid_p' => $keywordData['high_top_of_page_bid'] ?? null,
                    'monthlysearchvolume_p' => null,
                    'clicks_p' => $keywordData['clicks'] ?? null,
                    'ctr_p' => $keywordData['ctr'] ?? null,
                    'impressions_p' => $keywordData['impressions'] ?? null,
                    'position_p' => $keywordData['position'] ?? null,
                ]);
            }
                
                $storedKeywords[] = $existingKeywordPlanner;
        }
 
        $extractedKeywords = array_map(function ($item) {
            return $item['keyword'] ?? $item['query'] ?? '';
        }, $keyplan);
        // dd($extractedKeywords, $keyplan);


        session(['extracted_keywords' => $extractedKeywords]);

        // dd($keyplan);
        if (!empty($url) && empty($keyword)) {
            session(['is_full_results' => $isFullResults]);
            session(['remaining_keywords' => $remainingKeywords]);
        }

        // Store keyword planner data temporarily in session for use in modal
        session(['temp_keyplan' => $keyplan]);
        session(['temp_info' => $info]);

        // Generate HTML view
        $html = view('keyword-analysis.keyword-results', [
            'keywords' => $keyplan,
            'domain_name' => $url,
            'ai_status' => $ai_overview ? true : false,
            'total_count' => $totalKeywords ?? count($keyplan),
            'keyword_request_id' => $keyword_request_id,
            'client_property_id' => $request->client_property_id,
            'domainmanagement_id' => $request->domainmanagement_id,
            'is_full_results' => $isFullResults ?? null,
            'remaining_keywords' => $remainingKeywords ?? null,
        ])->render();

        $sessionId = session()->getId() . '_' . time();
        session(['keyword_processing_session' => $sessionId]);
        foreach ($keyplan as $index => $keyword_item) {
            $keyword = $keyword_item['keyword'] ?? $keyword_item['query'];
            // Log::info("Dispatching job for keyword: {$keyword}");
            // dd($keyword, $index);
            // Initialize cache for this keyword
            $cacheKey = "keyword_status_{$sessionId}_{$index}";
            cache()->put($cacheKey, [
                'keyword' => $keyword,
                'search_api_status' => 'Processing',
                'aio_status' => 'Processing',
                'client_mentioned_status' => 'Processing',
                'processed' => false,
                'index' => $index,
                'keyword_planner_id' => null,
            ], now()->addHours(24));
            
            // Dispatch the job
            ProcessKeywordStatusJob::dispatch(
                $keyword,
                $keyword_request_id,
                $request->client_property_id,
                $request->domainmanagement_id,
                $sessionId,
                $index,
                null
            );
        }

        return response()->json([
            'html' => $html, 
            'data' => $keyplan, 
            'total_keywords' => $totalKeywords ?? count($keyplan), 
            'is_full_results' => $isFullResults ?? null, 
            'remaining_keywords' => $remainingKeywords ?? null,
            'session_id' => $sessionId
        ]);
    }
    public function medianResults($id)
    {
        $client_property = Client_propertiesModel::where('id', $id)->first();
        $client_name = $client_property->domain;


        $keywordRequests = KeywordRequest::where('client_property_id', $id)
            ->get()
            ->keyBy('id');
        // dd($keywordRequests);


        $medianResults = MedianFetch::where('client_property_id', $id)->get();

        $dropdownData = [];
        $usedKeywordRequestIds = []; // 👈 to track grouping

        foreach ($medianResults as $median) {

            $krId = $median->keyword_request_id;
            // dd($keywordRequests);

            // Skip if we already added this keyword_request_id (GROUP BY effect)
            if (isset($usedKeywordRequestIds[$krId])) {
                continue;
            }

            if (isset($keywordRequests[$krId])) {
                $request = $keywordRequests[$krId];

                $dropdownData[] = [
                    'keyword' => $request->keyword,
                    'median_name' => $median->median_name,
                    'median_id' => $median->keyword_request_id,
                    'date_from' => $request->date_from,
                    'date_to'   => $request->date_to,
                ];

                $usedKeywordRequestIds[$krId] = true; // mark as used
            }
        }

        return view('median.index', compact('id', 'dropdownData', 'client_name'));
    }
    public function medianDisplay(Request $request)
    {
        $medianId = $request->median_result_id;

        if (!$medianId) {
            return response()->json([
                'html' => "<p class='text-danger'>Please select a median result.</p>"
            ]);
        }

        // Fetch all keywords for this median
        $allKeywords = MedianFetch::where('median-fetch.keyword_request_id', $medianId)
        ->join('keyword_planner', 'keyword_planner.id', '=', 'median-fetch.keyword_p')
        ->select('median-fetch.*', 'keyword_planner.keyword_p as keyword_term')
        ->get();

        
        // Split into two groups based on bucket value
        $addedBucket = $allKeywords->where('bucket', 1);
        $notAddedBucket = $allKeywords->where('bucket', 0);

        $html = view('median.result_table', compact('addedBucket', 'notAddedBucket'))->render();

        return response()->json([
            'html' => $html,
            'total_keywords' => $allKeywords->count(),
            'is_full_results' => true
        ]);
    }

    public function storeKeywordPlanner(Request $request)
    {

        try {
            $keyword = $request->keyword;
            $keyword_request_id = $request->keyword_request_id;
            Log::info('store Keyword planner: ' . $keyword . 'Yes (ID: ' . $keyword_request_id . ')');

            // Get ALL data from request parameters, not from session
            $keyword_data = $request->keyword_data ?? [];
            $client_property_id = $request->client_property_id;
            $domainmanagement_id = $request->domainmanagement_id;

            // Check if keyword planner already exists
            $keyword_planner = KeywordPlanner::where([
                ['keyword_p', $keyword],
                ['client_property_id', $client_property_id],
                ['domainmanagement_id', $domainmanagement_id],
                ['keyword_request_id', $keyword_request_id]
            ])->first();

            // Log::info('Keyword planner exists: ' . ($keyword_planner ? 'Yes (ID: ' . $keyword_planner->id . ')' : 'No'));

            $is_new = false;
            if (!$keyword_planner) {
                Log::info('Creating new keyword planner record');

                // Convert string values to appropriate types

                $monthly_search = $keyword_data['monthly_search'] ?? null;
                $low_bid = $keyword_data['low_bid'] ?? null;
                $high_bid = $keyword_data['high_bid'] ?? null;
                $clicks = $keyword_data['clicks'] ?? null;
                $ctr = $keyword_data['ctr'] ?? null;
                $impressions = $keyword_data['impressions'] ?? null;
                $position = $keyword_data['position'] ?? null;
                // dd("keyword_data",[
                //     'domainmanagement_id' => $domainmanagement_id,
                //     'client_property_id' => $client_property_id,
                //     'keyword_request_id' => $keyword_request_id,
                //     'keyword_p' => $keyword,
                //     'monthlysearch_p' => $monthly_search,
                //     'competition_p' => $keyword_data['competition'] ?? null,
                //     'low_bid_p' => $low_bid,
                //     'high_bid_p' => $high_bid,
                //     'clicks_p' => $clicks,
                //     'ai_status' => 0,
                //     'ctr_p' => $ctr,
                //     'impressions_p' => $impressions,
                //     'position_p' => $position]);
                // Log::info("create planner". $low_bid." ".$high_bid." ".$clicks." 0 ".$ctr." ".$impressions." ".$position);
                // dd($keyword_data);

                $keyword_planner = KeywordPlanner::create([
                    'domainmanagement_id' => $domainmanagement_id,
                    'client_property_id' => $client_property_id,
                    'keyword_request_id' => $keyword_request_id,
                    'keyword_p' => $keyword,
                    'monthlysearch_p' => $monthly_search ?? null,
                    'competition_p' => $keyword_data['competition'] ?? null,
                    'low_bid_p' => $low_bid ?? null,
                    'high_bid_p' => $high_bid ?? null,
                    'monthlysearchvolume_p' => null,
                    'clicks_p' => $clicks ?? null,
                    'ctr_p' => $ctr ?? null,
                    'impressions_p' => $impressions ?? null,
                    'position_p' => $position ?? null,
                    'ai_status' => '0',
                ]);
                // dd($keyword_planner);

                // Log::info("keyword_planner: ".$keyword_planner);
                $is_new = true;
                // Log::info('Keyword planner created with ID: ' . $keyword_planner->id);
            }

            // Now fetch the AI Overview data
            $this->status = 'Fetching AI Overview...';

            // Log::info('Returning success response with AI Overview');

            return [
                'data' => [
                    'keyword' => $keyword,
                ],
                'keyword_planner_id' => $keyword_planner->id,
                'is_new' => $is_new,
                'message' => 'Keyword stored successfully.'
            ];
        } catch (\Exception $e) {
            Log::error('Error trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function keywordClusterAnalysis($id)
    {
        $client_property = Client_propertiesModel::where('id', $id)->first();
        $domainmanagement_id = $client_property->domainmanagement_id;
        $domain_name = $client_property->domain;

        $all_cluster_requests = ClusterRequest::where([
            ['client_property_id', $client_property->id],
            ['domainmanagement_id', $client_property->domainmanagement_id],
        ])->get();

        // If you have multiple cluster requests and want to separate domains/keywords
        $domain_cluster_request = [];
        $keyword_cluster_request = [];

        foreach ($all_cluster_requests as $request) {
            // Check if type field exists, otherwise use pattern matching
            if (isset($request->type) && $request->type === 'keyword') {
                $keyword_cluster_request[] = $request;
            } elseif (isset($request->type) && $request->type === 'domain') {
                $domain_cluster_request[] = $request;
            } else {
                // Fallback: pattern matching
                $value = $request->keyword ?? '';
                $domain_pattern = '/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/.*)?$/';

                if (preg_match($domain_pattern, $value)) {
                    $domain_cluster_request[] = $request;
                } else {
                    $keyword_cluster_request[] = $request;
                }
            }
        }

        // dd($domain_cluster_request, $keyword_cluster_request);

        return view('keyword-analysis.keyword-cluster-analysis', compact(
            'id',
            'domainmanagement_id',
            'domain_name',
            'all_cluster_requests',
            'domain_cluster_request',
            'keyword_cluster_request'
        ));
    }

    public function aioClusterAnalysis($id)
    {
        $client_property = Client_propertiesModel::where('id', $id)->first();
        $domainmanagement_id = $client_property->domainmanagement_id;
        $domain_name = $client_property->domain;

        $all_cluster_requests = ClusterRequest::where([
            ['client_property_id', $client_property->id],
            ['domainmanagement_id', $client_property->domainmanagement_id],
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        // If you have multiple cluster requests and want to separate domains/keywords
        $domain_cluster_request = [];
        $keyword_cluster_request = [];

        foreach ($all_cluster_requests as $request) {
            // Check if type field exists, otherwise use pattern matching
            if (isset($request->type) && $request->type === 'keyword') {
                $keyword_cluster_request[] = $request;
            } elseif (isset($request->type) && $request->type === 'domain') {
                $domain_cluster_request[] = $request;
            } else {
                // Fallback: pattern matching
                $value = $request->keyword ?? '';
                $domain_pattern = '/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/.*)?$/';

                if (preg_match($domain_pattern, $value)) {
                    $domain_cluster_request[] = $request;
                } else {
                    $keyword_cluster_request[] = $request;
                }
            }
        }

        // dd($domain_cluster_request, $keyword_cluster_request);

        return view('keyword-analysis.aio-cluster-analysis', compact(
            'id',
            'domainmanagement_id',
            'domain_name',
            'all_cluster_requests',
            'domain_cluster_request',
            'keyword_cluster_request'
        ));
    }

    public function checkAioResult($id)
    {
        $keyword_planner = KeywordPlanner::findOrFail($id);
        $ai_overview = AiOverview::where('keyword_planner_id', $id)->first();

        return view('keyword-analysis.check-aio-result', compact('keyword_planner', 'ai_overview'));
    }
    private function enrichWithGscData($keywords, $domain, $dateFrom, $dateTo, $limit)
    {
        try {
            // dd('asdasd');
            // Get GSC data for the domain - pass the domain as the property
            if (isset($dateFrom) && isset($dateTo)) {
                $gscData = $this->getEnrichedSearchAnalytics($domain, $limit, $dateFrom, $dateTo);
                // dd($gscData);
            } else {
                $gscData = $this->getEnrichedSearchAnalytics($domain, $limit);
            }
            // dd($gscData);
            if ($domain) {
                return $gscData;
            }
            // Create a lookup array from GSC data for faster matching
            $gscLookup = [];
            // dd($keywords, $gscData);
            foreach ($gscData as $item) {
                // Normalize the query for case-insensitive comparison
                $normalizedQuery = strtolower(trim($item['query'] ?? ''));
                $gscLookup[$normalizedQuery] = $item;
            }
            // dd($keywords);

            // Map GSC data to keywords
            $i = 0;
            foreach ($keywords as &$keyword) {
                $i++;
                $keywordText = $keyword['keyword'] ?? '';
                // Normalize the keyword for comparison
                $normalizedKeyword = strtolower(trim($keywordText));

                // Find matching GSC data for this keyword
                if (isset($gscLookup[$normalizedKeyword])) {
                    $gscMatch = $gscLookup[$normalizedKeyword];
                    $keyword['clicks'] = $gscMatch['clicks'] ?? 0;
                    $keyword['impressions'] = $gscMatch['impressions'] ?? 0;
                    $keyword['ctr'] = $gscMatch['ctr'] ?? 0;
                    $keyword['position'] = $gscMatch['position'] ?? 0;
                } else {
                    // Try partial matching if exact match fails
                    $gscMatch = $this->findPartialMatch($normalizedKeyword, $gscData);

                    if ($gscMatch) {
                        $keyword['clicks'] = $gscMatch['clicks'] ?? 0;
                        $keyword['impressions'] = $gscMatch['impressions'] ?? 0;
                        $keyword['ctr'] = $gscMatch['ctr'] ?? 0;
                        $keyword['position'] = $gscMatch['position'] ?? 0;
                    } else {
                        // Default values if no GSC data found
                        $keyword['clicks'] = 0;
                        $keyword['impressions'] = 0;
                        $keyword['ctr'] = 0;
                        $keyword['position'] = 0;
                    }
                }
            }


            return $keywords;
        } catch (\Exception $e) {
            Log::error('GSC enrichment error: ' . $e->getMessage());
            // If GSC data fails, still return keywords with default values
            foreach ($keywords as &$keyword) {
                $keyword['clicks'] = 0;
                $keyword['impressions'] = 0;
                $keyword['ctr'] = 0;
                $keyword['position'] = 0;
            }
            return $keywords;
        }
    }

    /**
     * Helper method to find partial keyword matches in GSC data
     */
    private function findPartialMatch($keyword, $gscData)
    {
        foreach ($gscData as $item) {
            $gscQuery = strtolower(trim($item['query'] ?? ''));

            // Check if keyword contains GSC query or vice versa
            if (strpos($keyword, $gscQuery) !== false || strpos($gscQuery, $keyword) !== false) {
                return $item;
            }

            // Remove common modifiers for better matching
            $cleanKeyword = $this->cleanKeyword($keyword);
            $cleanGscQuery = $this->cleanKeyword($gscQuery);

            if ($cleanKeyword === $cleanGscQuery) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Clean keyword by removing common modifiers
     */
    private function cleanKeyword($keyword)
    {
        $modifiers = [' near me', ' clinic', ' treatment', ' doctor', ' best'];

        $cleaned = $keyword;
        foreach ($modifiers as $modifier) {
            $cleaned = str_replace($modifier, '', $cleaned);
        }

        return trim($cleaned);
    }


    /**
     * Apply filters to keyword results
     */
    private function applyFilters($keywords, $filters)
    {
        return array_filter($keywords, function ($item) use ($filters) {
            // Monthly searches filter
            if (isset($filters['min_searches']) && $filters['min_searches'] !== '' && $filters['min_searches'] !== null && $item['avg_monthly_searches'] < $filters['min_searches']) {
                return false;
            }
            
            if (isset($filters['max_searches']) && $filters['max_searches'] !== '' && $filters['max_searches'] !== null && $item['avg_monthly_searches'] > $filters['max_searches']) {
                return false;
            }
            // dd($item);

            // Competition filter
            if (isset($filters['competition']) && !empty($filters['competition']) && $item['competition'] !== $filters['competition']) {
                return false;
            }

            // Low bid filter
            if (isset($filters['min_bid']) && $filters['min_bid'] !== '' && $item['low_top_of_page_bid_micros'] < $filters['min_bid']) {
                return false;
            }

            // High bid filter
            if (isset($filters['max_bid']) && $filters['max_bid'] !== '' && $item['high_top_of_page_bid_micros'] > $filters['max_bid']) {
                return false;
            }

            // Clicks filter
            if (isset($filters['min_clicks']) && $filters['min_clicks'] !== '' && $item['clicks'] < $filters['min_clicks']) {
                return false;
            }

            if (isset($filters['max_clicks']) && $filters['max_clicks'] !== '' && $item['clicks'] > $filters['max_clicks']) {
                return false;
            }

            // CTR filter (percentage)
            if (isset($filters['min_ctr']) && $filters['min_ctr'] !== '' && $item['ctr'] < $filters['min_ctr']) {
                return false;
            }

            if (isset($filters['max_ctr']) && $filters['max_ctr'] !== '' && $item['ctr'] > $filters['max_ctr']) {
                return false;
            }

            // Impressions filter
            if (isset($filters['min_impressions']) && $filters['min_impressions'] !== '' && $item['impressions'] < $filters['min_impressions']) {
                return false;
            }

            if (isset($filters['max_impressions']) && $filters['max_impressions'] !== '' && $item['impressions'] > $filters['max_impressions']) {
                return false;
            }

            // Date filter (you might need to store dates in your data)
            // This is placeholder - you'll need to add date field to your keyword data
            if (isset($filters['date_from']) && isset($item['date']) && strtotime($item['date']) < strtotime($filters['date_from'])) {
                return false;
            }

            if (isset($filters['date_to']) && isset($item['date']) && strtotime($item['date']) > strtotime($filters['date_to'])) {
                return false;
            }

            return true;
        });
    }



    // public function allkeywordStore($info, $request)
    // {
    //     $organicResultsData = [];
    //     $relatedQuestionsData = [];
    //     $relatedSearchesData = [];
    //     $keywordplannerData = [];
    //     // dd($request);

    //     // $domain = 'https://www.hairtransplantdelhi.org/';
    //     $keyword = $info['keyword'];
    //     // $keyplan=$this->kpService->searchKeywordsByUrl($domain);
    //     // dd($keyplan);
    //     if(isset($request)){
    //         foreach ($request as $keywordplanner) {
    //             $keywordplannerData[] = [
    //                 'domainmanagement_id' => $info['domainmanagement_id'],
    //                 'client_property_id' => $info['client_property_id'],
    //                 // 'keyword_request_id' => $keyword_request->id,
    //                 'keyword_request_id' => '1',

    //                 'keyword_p' => $keywordplanner['keyword'] ?? null,
    //                 'monthlysearch_p' => $keywordplanner['avg_monthly_searches'] ?? null,
    //                 'competition_p' => $keywordplanner['competition'] ?? null,
    //                 'low_bid_p' => $keywordplanner['low_top_of_page_bid_micros'] ?? null,
    //                 'high_bid_p' => $keywordplanner['high_top_of_page_bid_micros'] ?? null,
    //             ];
    //         }
    //         // if (!empty($keywordplannerData)) {
    //         //     foreach (array_chunk($keywordplannerData, 300) as $chunk) {
    //         //         KeywordPlanner::insert($chunk);
    //         //     }
    //         // }
    //     }
    //     dd($keywordplannerData); 

    //     $search_json = GeneralHelper::getSearchResult($keyword);
    //     $search_data = json_decode($search_json, true);

    //     $aio_data = null;

    //     $keyword_request = KeywordRequest::create([
    //         'domainmanagement_id' => $request->domainmanagement_id,
    //         'client_property_id' => $request->client_property_id,
    //         'keyword' => $request->keyword,
    //         'ai_overview' => isset($search_data['ai_overview']) ? "1" : "0",
    //         'json' => json_encode($search_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
    //     ]);

    //     if(isset($search_data['ai_overview']) && !isset($search_data['ai_overview']['page_token'])){
    //         AiOverview::create([
    //             'domainmanagement_id' => $request->domainmanagement_id,
    //             'client_property_id' => $request->client_property_id,
    //             'keyword_request_id' => $keyword_request->id,
    //             'text_blocks' => isset($search_data['ai_overview']['text_blocks']) ? json_encode($search_data['ai_overview']['text_blocks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
    //             'json' => $search_data['ai_overview'] ? json_encode($search_data['ai_overview'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
    //             'markdown' => $search_data['ai_overview']['markdown'] ?? null,
    //         ]);
    //     }else{
    //         $aio_json = GeneralHelper::getaioResult($search_data['ai_overview']['page_token'] ?? '');
    //         $aio_data = json_decode($aio_json, true);
    //         AiOverview::create([
    //             'domainmanagement_id' => $request->domainmanagement_id,
    //             'client_property_id' => $request->client_property_id,
    //             'keyword_request_id' => $keyword_request->id,
    //             'text_blocks' => isset($aio_data['text_blocks']) ? json_encode($aio_data['text_blocks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
    //             'json' => $aio_data ? json_encode($aio_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
    //             'markdown' => $aio_data['markdown'] ?? null,
    //         ]);
    //     }

    //     if(isset($search_data['organic_results'])){
    //         foreach ($search_data['organic_results'] as $result) {
    //             $organicResultsData[] = [
    //                 'domainmanagement_id' => $request->domainmanagement_id,
    //                 'client_property_id' => $request->client_property_id,
    //                 'keyword_request_id' => $keyword_request->id,

    //                 'position' => $result['position'] ?? null,
    //                 'title' => $result['title'] ?? null,
    //                 'link' => $result['link'] ?? null,
    //                 'source' => $result['source'] ?? null,
    //                 'domain' => $result['domain'] ?? null,
    //                 'displayed_link' => $result['displayed_link'] ?? null,
    //                 'snippet' => $result['snippet'] ?? null,
    //                 'snippet_highlighted_word' => isset($result['snippet_highlighted_words']) ? implode(", ", $result['snippet_highlighted_words']) : null,
    //                 'sitelinks' => (isset($result['sitelinks'])) ? json_encode($result['sitelinks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
    //                 'favicon' => null,
    //                 'date' => $result['date'] ?? null,
    //                 'json' => $result ? json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT): null,  
    //             ];
    //         }
    //         if (!empty($organicResultsData)) {
    //             foreach (array_chunk($organicResultsData, 300) as $chunk) {
    //                 OrganicResult::insert($chunk);
    //             }
    //         }
    //     }

    //     if(isset($search_data['related_questions'])){

    //         foreach ($search_data['related_questions'] as $question) {
    //             if($question['is_ai_overview'] ?? false){
    //                 continue;
    //             }
    //             $relatedQuestionsData[] = [
    //                 'domainmanagement_id' => $request->domainmanagement_id,
    //                 'client_property_id' => $request->client_property_id,
    //                 'keyword_request_id' => $keyword_request->id,

    //                 'question' => $question['question'] ?? null,
    //                 'answer' => isset($question['answer']) ? $question['answer'] : $question['markdown'],
    //                 'source_title' => $question['source']['title'] ?? null,
    //                 'source_link' => $question['source']['link'] ?? null,
    //                 'source_source' => $question['source']['source'] ?? null,
    //                 'source_domain' => $question['source']['domain'] ?? null,
    //                 'source_displayed_link' => $question['source']['displayed_link'] ?? null,
    //                 'source_favicon' => null,
    //                 'json' => $question ? json_encode($question, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
    //                 'date' => $question['date'] ?? null,
    //             ];
    //         }
    //         if (!empty($relatedQuestionsData)) {
    //             foreach (array_chunk($relatedQuestionsData, 300) as $chunk) {
    //                 RelatedQuestions::insert($chunk);
    //             }
    //         }
    //     }

    //     if(isset($search_data['related_searches'])){
    //         foreach ($search_data['related_searches'] as $relatedSearch) {
    //             $relatedSearchesData[] = [
    //                 'domainmanagement_id' => $request->domainmanagement_id,
    //                 'client_property_id' => $request->client_property_id,
    //                 'keyword_request_id' => $keyword_request->id,

    //                 'query' => $relatedSearch['query'] ?? null,
    //                 'link' => $relatedSearch['link'] ?? null,

    //                 'json' => $relatedSearch ? json_encode($relatedSearch, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
    //             ];
    //         }
    //         if (!empty($relatedSearchesData)) {
    //             foreach (array_chunk($relatedSearchesData, 300) as $chunk) {
    //                 RelatedSearches::insert($chunk);
    //             }
    //         }
    //     }
    //     dd($search_data, $aio_data);

    //     $html = view('keyword-analysis.keyword-results', compact('data'))->render();
    //     return response()->json(['html' => $html]);
    // }

    public function keywordAnalysis($domainmanagement_id, $client_property_id, $id)
{
    $keywordRequest = KeywordRequest::where([
        'id' => $id,
        'domainmanagement_id' => $domainmanagement_id,
        'client_property_id' => $client_property_id
    ])->first();

    $keywordplanner = KeywordPlanner::leftJoin(
        'cluster_request',
        'cluster_request.id',
        '=',
        'keyword_planner.cluster_request_id'
    )
        ->where('keyword_planner.keyword_request_id', $id)
        ->select(
            'cluster_request.date_from as cluster_request_date_from',
            'cluster_request.date_to as cluster_request_date_to',
            'keyword_planner.*',
            // Check if organic_results has a record for this keyword_planner_id
            DB::raw('EXISTS(
                SELECT 1 FROM organic_results
                WHERE organic_results.keyword_planner_id = keyword_planner.id
            ) as has_organic_results'),
            // Check if ai_overview has a record for this keyword_planner_id
            DB::raw('EXISTS(
                SELECT 1 FROM ai_overview
                WHERE ai_overview.keyword_planner_id = keyword_planner.id
            ) as has_ai_overview'),
            // Check if related_questions has a record for this keyword_planner_id
            DB::raw('EXISTS(
                SELECT 1 FROM related_questions
                WHERE related_questions.keyword_planner_id = keyword_planner.id
            ) as has_related_questions'),
            // Check if related_searches has a record for this keyword_planner_id
            DB::raw('EXISTS(
                SELECT 1 FROM related_searches
                WHERE related_searches.keyword_planner_id = keyword_planner.id
            ) as has_related_searches')
        )
        ->get();

    return view('keyword-analysis.keyword-analysis', compact('keywordRequest', 'keywordplanner'));
}

    public function extractedAioResult($keyword_planner_id, $history_log_id = null)
    {
        // dd($keyword_planner_id, $history_log_id);
        $organicResultsData = [];
        $relatedQuestionsData = [];
        $adsData = [];
        $history_log_id = ($history_log_id ==null) ? HistoryLog::where('keyword_planner_id', $keyword_planner_id)->orderByDesc('id')->first()->id ?? null : $history_log_id;

        if($history_log_id){
            $keywordplanner = KeywordPlanner::where('id', $keyword_planner_id)->first();
            // dd($keywordplanner->toArray());
            $keywordRequest = KeywordRequest::where('id', $keywordplanner->keyword_request_id)->first();

            $organicResults = OrganicResult::where('keyword_planner_id', $keyword_planner_id)->where('history_log_id', $history_log_id)->get();
            $relatedQuestions = RelatedQuestions::where('keyword_planner_id', $keyword_planner_id)->where('history_log_id', $history_log_id)->get();
            $aiOverview = AiOverview::where('keyword_planner_id', $keyword_planner_id)->where('history_log_id', $history_log_id)->get();
            // dd($aiOverview->toArray());
            $relatedSearches = RelatedSearches::where('keyword_planner_id', $keyword_planner_id)->where('history_log_id', $history_log_id)->get();
        }else{
            
            $keywordplanner = KeywordPlanner::where('id', $keyword_planner_id)->first();
            $keywordRequest = KeywordRequest::where('id', $keywordplanner->keyword_request_id)->first();
            
            $organicResults = OrganicResult::where('keyword_planner_id', $keywordplanner->id)->get();
            $relatedQuestions = RelatedQuestions::where('keyword_planner_id', $keyword_planner_id)->orderByDesc('id')->get();
            // dd($relatedQuestions->toArray());
            $aiOverview = AiOverview::where('keyword_planner_id', $keyword_planner_id)->orderBy('priority_sync', 'asc')->get();
            $relatedSearches = RelatedSearches::where('keyword_planner_id', $keyword_planner_id)->orderByDesc('id')->get();
        }

        // dd($keywordRequestdata->toArray());

        return view('keyword-analysis.aio-results', compact('keywordRequest', 'organicResults', 'relatedQuestions', 'aiOverview', 'relatedSearches', 'keywordplanner'));
    }

    public function sync_now(Request $request)
    {
        try {

            // Get keyword planner data
            $keywordplanner = KeywordPlanner::findOrFail($request->keyword_planner_id);
            $keywordRequest = KeywordRequest::findOrFail($keywordplanner->keyword_request_id);

            // Get the keyword to search
            $keyword = $keywordplanner->keyword_p;

            $searchJson = GeneralHelper::getSearchResult($keyword);
            $searchData = json_decode($searchJson, true);

            if (!$searchData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch search results'
                ], 400);
            }

            // Process AI Overview
            $ai_overview = null;
            $aio_json = null;
            if (isset($searchData['ai_overview'])) {
                if (!isset($searchData['ai_overview']['page_token'])) {
                    $ai_overview = $searchData['ai_overview'];
                } else {
                    $aio_json = GeneralHelper::getaioResult($searchData['ai_overview']['page_token']);
                    $ai_overview = json_decode($aio_json, true);
                }
            }

            // Save AI Overview data
            if ($ai_overview) {
                // Reset priority for existing AI overviews
                AiOverview::where('keyword_planner_id', $keywordplanner->id)
                    ->update(['priority_sync' => '0']);

                // Create new AI overview with priority
                AiOverview::create([
                    'domainmanagement_id' => $keywordplanner->domainmanagement_id ?? null,
                    'client_property_id' => $keywordplanner->client_property_id ?? null,
                    'keyword_request_id' => $keywordplanner->keyword_request_id,
                    'keyword_planner_id' => $keywordplanner->id,
                    'text_blocks' => isset($ai_overview['text_blocks']) ?
                        json_encode($ai_overview['text_blocks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    'json' => $ai_overview ?
                        json_encode($ai_overview, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    'markdown' => $ai_overview['markdown'] ?? null,
                    'priority_sync' => '1',
                ]);

                return response()->json([
                    'success' => true,
                    'ai_status' => true,
                    'message' => 'Data synced successfully!',
                    // 'redirect_url' => route('extracted-aio-result', ['id' => $keywordplanner->id]),
                    'data' => [
                        'keyword' => $keyword,
                        'keyword_planner_id' => $keywordplanner->id,
                    ]
                ]);
            } else {

                return response()->json([
                    'success' => true,
                    'ai_status' => false,
                    'message' => 'Ops! Seems like AI Overview data didn`t get synced.',
                    // 'redirect_url' => route('extracted-aio-result', ['id' => $keywordplanner->id]),
                    'data' => [
                        'keyword' => $keyword,
                        'keyword_planner_id' => $keywordplanner->id,
                    ]
                ]);
            }
            // dd($searchData,$aio_json);


        } catch (\Exception $e) {
            Log::error('Sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncAiOverview(Request $request)
    {
        try {

            $aiOverview = AiOverview::findOrFail($request->ai_overview_id);

            // Parse text blocks - ensure it's returned as an array, not string
            $textBlocks = [];
            if (!empty($aiOverview->text_blocks)) {
                $textBlocks = json_decode($aiOverview->text_blocks, true);

                // If json_decode returns null (for invalid JSON), try to handle it
                if (is_null($textBlocks) && !empty($aiOverview->text_blocks)) {
                    // Try to fix common JSON issues
                    $fixedJson = $this->fixJsonString($aiOverview->text_blocks);
                    $textBlocks = json_decode($fixedJson, true);
                }
            }

            // Format the response data
            $data = [
                'id' => $aiOverview->id,
                'markdown' => $aiOverview->markdown,
                'text_blocks' => $textBlocks, // Already decoded array
                'created_at' => $aiOverview->created_at->toISOString(),
                'created_at_formatted' => $aiOverview->created_at->format('M d, Y H:i'),
                'priority_sync' => $aiOverview->priority_sync
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getAiOverviewData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load AI overview data: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper method to fix common JSON issues
    private function fixJsonString($jsonString)
    {
        // Remove BOM if present
        $jsonString = preg_replace('/^\xEF\xBB\xBF/', '', $jsonString);

        // Fix common JSON issues
        $jsonString = str_replace(["\r\n", "\r"], "\n", $jsonString);
        $jsonString = preg_replace('/,\s*}/', '}', $jsonString); // Remove trailing commas
        $jsonString = preg_replace('/,\s*]/', ']', $jsonString); // Remove trailing commas in arrays

        return $jsonString;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return view('keyword-analysis.edit', compact('id'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function fetchGscUrls()
    {
        // try {
        //     // Your JSON file path
        //     $serviceAccountFile = storage_path('app/google-analytics.json');

        //     // Your domain property
        //     $propertyUri = 'https://www.aaynaclinic.com';

        //     // INIT service
        //     // $gscService = new GoogleSearchConsoleService();

        //     // Fetch indexed URLs
        //     $indexedPages = $this->gscService->getIndexedPages($propertyUri, '2025-11-18', '2025-11-18');

        //     return response()->json([
        //         'status' => true,
        //         'indexed_pages' => $indexedPages
        //     ]);

        // } catch (\Exception $e) {
        //     return response()->json([
        //         'status' => false,
        //         'error' => $e->getMessage()
        //     ]);
        // }

        $prompt = "
        You are an SEO & AIO (AI Overview) analysis expert. I will give you:

1. **My URL**
2. **AI Overview JSON**

Your task is to:

* Extract all competitor URLs appearing inside the AI Overview JSON.
* Check whether my URL appears anywhere inside the JSON.
* Compare my page vs the pages that appear in AI Overview and tell me:

  * What they are doing differently
  * What I am missing
  * What signals Google’s AI Overview is picking from them
  * What I must add, improve, or restructure to appear in AI Overview
* Give a clear list of actionable AIO optimization steps.

**Here is the format I want in the output**:

### **1. Extracted URLs from AI Overview JSON**

(Show all URLs found in “link”, in paragraphs, in lists, or inside text blocks)

### **2. Does my URL appear in AI Overview?**

(YES/NO with explanation)

### **3. Comparison: Competitor Page vs My Page**

* Content depth
* E-E-A-T signals
* Page structure
* Topical coverage
* Internal/external links
* FAQ depth
* Schema
* Images
* Page intent match

### **4. What They Are Doing Differently (Competitor Advantage)**

(List bullet points)

### **5. What My Page is Missing**

(Bullet points)

### **6. Exact Action Plan to Rank in AI Overview**

(Very detailed, step-by-step)
        ";
        $url = "https://www.kayakalpglobal.com/dr-shailendra-dhawan-best-vitiligo-doctor-in-india.php";
        $AIresponse = $this->analyzeWithGeminiText($prompt, $url);
        dd($AIresponse);
    }

    private function analyzeWithGeminiText($prompt, $text)
    {
        $accessToken = $this->getGoogleAccessToken();

        $requestPayload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt . "\n\nTranscription:\n" . $text]
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken
        ])->post($this->geminiEndpoint, $requestPayload);

        if (!$response->successful()) {
            throw new Exception("Gemini API error: " . $response->body());
        }

        $responseData = $response->json();

        if (isset($responseData['error'])) {
            throw new Exception("Gemini API error: " . $responseData['error']['message']);
        }

        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            return $responseData['candidates'][0]['content']['parts'][0]['text'];
        }

        return null;
    }
    private function getGoogleAccessToken()
    {
        if (!file_exists($this->keyFilePath)) {
            throw new Exception("Service account key file not found: " . $this->keyFilePath);
        }

        $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
        $creds = new ServiceAccountCredentials($scopes, $this->keyFilePath);
        $token = $creds->fetchAuthToken();

        if (!isset($token['access_token'])) {
            throw new Exception("Could not obtain access token");
        }

        return $token['access_token'];
    }

    public function getAioResult(Request $request)
    {
        // dd("asas");
        try {

            $organicResultsData = [];
            $relatedQuestionsData = [];
            $relatedSearchesData = [];
            $keywordplannerData = null;
            // Log::info("Hello getAioResult");


            $keyword = $request->keyword;
            $keywordData = $request->keyword_data;

            // Store keyword data if provided
            if ($keywordData && $request->keyword_request_id) {
                $keywordplannerData = $this->storeKeywordPlanner($request);
                // Log::info($keywordplannerData);
            }
            Log::info("keyword_planner_id: " . $keywordplannerData['keyword_planner_id']);
            // dd($keywordplannerData,$request->all());

            // Get AIO result
            $searchJson = GeneralHelper::getSearchResult($keyword);
            $searchData = json_decode($searchJson, true);

            $aiOverview = null;
            $hasAio = false;

            $ai_overview = null;
            if (isset($searchData['ai_overview'])) {
                if (!isset($searchData['ai_overview']['page_token'])) {
                    $ai_overview = $searchData['ai_overview'];
                    $hasAio = true;
                } else {
                    $aio_json = GeneralHelper::getaioResult($searchData['ai_overview']['page_token']);
                    $ai_overview = json_decode($aio_json, true);
                    $hasAio = true;
                }
            }


            if ($ai_overview) {
                AiOverview::create([
                    'domainmanagement_id' => $request->domainmanagement_id,
                    'client_property_id' => $request->client_property_id,
                    'keyword_request_id' => $request->keyword_request_id,
                    'keyword_planner_id' => $keywordplannerData['keyword_planner_id'],
                    'text_blocks' => isset($ai_overview['text_blocks']) ? json_encode($ai_overview['text_blocks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    'json' => $ai_overview ? json_encode($ai_overview, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    'markdown' => $ai_overview['markdown'] ?? null,
                ]);
                KeywordPlanner::where('id', $keywordplannerData['keyword_planner_id'])->update(['ai_status' => '1']);
            } else {
                KeywordPlanner::where('id', $keywordplannerData['keyword_planner_id'])->update(['ai_status' => '0']);
            }

            if (isset($searchData['organic_results'])) {
                foreach ($searchData['organic_results'] as $result) {
                    $organicResultsData[] = [
                        'domainmanagement_id' => $request->domainmanagement_id,
                        'client_property_id' => $request->client_property_id,
                        'keyword_request_id' => $request->keyword_request_id,
                        'keyword_planner_id' => $keywordplannerData['keyword_planner_id'],

                        'position' => $result['position'] ?? null,
                        'title' => $result['title'] ?? null,
                        'link' => $result['link'] ?? null,
                        'source' => $result['source'] ?? null,
                        'domain' => $result['domain'] ?? null,
                        'displayed_link' => $result['displayed_link'] ?? null,
                        'snippet' => $result['snippet'] ?? null,
                        'snippet_highlighted_word' => isset($result['snippet_highlighted_words']) ? implode(", ", $result['snippet_highlighted_words']) : null,
                        'sitelinks' => (isset($result['sitelinks'])) ? json_encode($result['sitelinks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'favicon' => null,
                        'date' => $result['date'] ?? null,
                        'json' => $result ? json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    ];
                }
                if (!empty($organicResultsData)) {
                    foreach (array_chunk($organicResultsData, 300) as $chunk) {
                        OrganicResult::insert($chunk);
                    }
                }
            }

            if (isset($searchData['related_questions'])) {

                foreach ($searchData['related_questions'] as $question) {
                    if ($question['is_ai_overview'] ?? false) {
                        continue;
                    }
                    $relatedQuestionsData[] = [
                        'domainmanagement_id' => $request->domainmanagement_id,
                        'client_property_id' => $request->client_property_id,
                        'keyword_planner_id' => $keywordplannerData['keyword_planner_id'],
                        'keyword_request_id' => $request->keyword_request_id,

                        'question' => $question['question'] ?? null,
                        'answer' => isset($question['answer']) ? $question['answer'] : $question['markdown'],
                        'source_title' => $question['source']['title'] ?? null,
                        'source_link' => $question['source']['link'] ?? null,
                        'source_source' => $question['source']['source'] ?? null,
                        'source_domain' => $question['source']['domain'] ?? null,
                        'source_displayed_link' => $question['source']['displayed_link'] ?? null,
                        'source_favicon' => null,
                        'json' => $question ? json_encode($question, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'date' => $question['date'] ?? null,
                    ];
                }
                if (!empty($relatedQuestionsData)) {
                    foreach (array_chunk($relatedQuestionsData, 300) as $chunk) {
                        RelatedQuestions::insert($chunk);
                    }
                }
            }


            if (isset($searchData['related_searches'])) {
                foreach ($searchData['related_searches'] as $relatedSearch) {
                    $relatedSearchesData[] = [
                        'domainmanagement_id' => $request->domainmanagement_id,
                        'client_property_id' => $request->client_property_id,
                        'keyword_planner_id' => $keywordplannerData['keyword_planner_id'],
                        'keyword_request_id' => $request->keyword_request_id,

                        'query' => $relatedSearch['query'] ?? null,
                        'link' => $relatedSearch['link'] ?? null,

                        'json' => $relatedSearch ? json_encode($relatedSearch, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    ];
                }
                if (!empty($relatedSearchesData)) {
                    foreach (array_chunk($relatedSearchesData, 300) as $chunk) {
                        RelatedSearches::insert($chunk);
                    }
                }
            }


            Log::info([
                'keyword_planner_id' => $keywordplannerData['keyword_planner_id'],
                'ai_status' => $hasAio,
                'redirect_url' => route('extracted-aio-result', ['id' => $keywordplannerData['keyword_planner_id']]), // Add this
                'data' => [
                    'keyword' => $keyword,
                    'keyword_planner_id' => $keywordplannerData['keyword_planner_id'],
                    'ai_overview' => $ai_overview
                ]
            ]);
            return response()->json([
                'success' => true,
                'ai_status' => $hasAio,
                'keyword_planner_id' => $keywordplannerData['keyword_planner_id'],
                'redirect_url' => route('extracted-aio-result', ['id' => $keywordplannerData['keyword_planner_id']]), // Add this
                'data' => [
                    'keyword' => $keyword,
                    'keyword_planner_id' => $keywordplannerData['keyword_planner_id'],
                    'ai_overview' => $ai_overview
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function kpkeywordStore($info, $request)
    {
        $organicResultsData = [];
        $relatedQuestionsData = [];
        $relatedSearchesData = [];
        $keywordplannerData = [];
        $search_json = GeneralHelper::getSearchResult($info['keyword']);
        $search_data = json_decode($search_json, true);

        // 1. Handle KeywordRequest Model
        $keyword_request = KeywordRequest::where([
            ['keyword', $info['keyword']],
            ['client_property_id', $info['client_property_id']],
            ['domainmanagement_id', $info['domainmanagement_id']]
        ])->first();

        if (!$keyword_request) {
            $keyword_request = KeywordRequest::create([
                'domainmanagement_id' => $info['domainmanagement_id'],
                'client_property_id' => $info['client_property_id'],
                'keyword' => $info['keyword'],
            ]);
        }
        $keyword_request_id = $keyword_request->id;

        // 2. Handle KeywordPlanner Model (for each master_keyword in request)
        $keywordPlannerIds = [];

        if (isset($request) && is_array($request)) {
            foreach ($request as $keywordplanner) {
                if (isset($keywordplanner['master_keyword'])) {
                    $keyword_planner = KeywordPlanner::where([
                        ['keyword_p', $keywordplanner['master_keyword']],
                        ['client_property_id', $info['client_property_id']],
                        ['domainmanagement_id', $info['domainmanagement_id']],
                        ['keyword_request_id' => $keyword_request_id],
                    ])->first();

                    if (!$keyword_planner) {
                        $keyword_planner = KeywordPlanner::create([
                            'domainmanagement_id' => $info['domainmanagement_id'],
                            'client_property_id' => $info['client_property_id'],
                            'keyword_request_id' => $keyword_request_id,
                            'keyword_p' => $keywordplanner['master_keyword'],
                        ]);
                    }

                    $keywordPlannerIds[] = $keyword_planner->id;

                    $keywordplannerData[] = [
                        'domainmanagement_id' => $info['domainmanagement_id'],
                        'client_property_id' => $info['client_property_id'],
                        'keyword_request_id' => $keyword_request_id,
                        'keyword_p' => $keywordplanner['master_keyword'],
                    ];
                }
            }

            // Insert additional keyword planner records if needed
            if (!empty($keywordplannerData)) {
                foreach (array_chunk($keywordplannerData, 300) as $chunk) {
                    KeywordPlanner::insertOrIgnore($chunk);
                }
            }
        }

        // Process AiOverview, OrganicResult, RelatedQuestions, and RelatedSearches for each keyword_planner_id
        foreach ($keywordPlannerIds as $keyword_planner_id) {

            // 3. Handle AiOverview Model
            $aiOverviewExists = AiOverview::where([
                ['keyword_planner_id', $keyword_planner_id],
                ['keyword_request_id', $keyword_request_id],
                ['client_property_id', $info['client_property_id']],
                ['domainmanagement_id', $info['domainmanagement_id']]
            ])->exists();

            if (!$aiOverviewExists) {
                if (isset($search_data['ai_overview']) && !isset($search_data['ai_overview']['page_token'])) {
                    AiOverview::create([
                        'domainmanagement_id' => $info['domainmanagement_id'],
                        'client_property_id' => $info['client_property_id'],
                        'keyword_request_id' => $keyword_request_id,
                        'keyword_planner_id' => $keyword_planner_id,
                        'text_blocks' => isset($search_data['ai_overview']['text_blocks']) ? json_encode($search_data['ai_overview']['text_blocks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'json' => $search_data['ai_overview'] ? json_encode($search_data['ai_overview'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'markdown' => $search_data['ai_overview']['markdown'] ?? null,
                    ]);
                } else {
                    $aio_json = GeneralHelper::getaioResult($search_data['ai_overview']['page_token'] ?? '');
                    $aio_data = json_decode($aio_json, true);
                    AiOverview::create([
                        'domainmanagement_id' => $info['domainmanagement_id'],
                        'client_property_id' => $info['client_property_id'],
                        'keyword_request_id' => $keyword_request_id,
                        'keyword_planner_id' => $keyword_planner_id,
                        'text_blocks' => isset($aio_data['text_blocks']) ? json_encode($aio_data['text_blocks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'json' => $aio_data ? json_encode($aio_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'markdown' => $aio_data['markdown'] ?? null,
                    ]);
                }
            }

            // 4. Handle OrganicResult Model
            if (isset($search_data['organic_results'])) {
                $organicResultsData = [];

                // Check if records already exist
                $existingOrganicResults = OrganicResult::where([
                    ['keyword_planner_id', $keyword_planner_id],
                    ['keyword_request_id', $keyword_request_id],
                    ['client_property_id', $info['client_property_id']],
                    ['domainmanagement_id', $info['domainmanagement_id']]
                ])->count();

                if ($existingOrganicResults === 0) {
                    foreach ($search_data['organic_results'] as $result) {
                        $organicResultsData[] = [
                            'domainmanagement_id' => $info['domainmanagement_id'],
                            'client_property_id' => $info['client_property_id'],
                            'keyword_request_id' => $keyword_request_id,
                            'keyword_planner_id' => $keyword_planner_id,
                            'position' => $result['position'] ?? null,
                            'title' => $result['title'] ?? null,
                            'link' => $result['link'] ?? null,
                            'source' => $result['source'] ?? null,
                            'domain' => $result['domain'] ?? null,
                            'displayed_link' => $result['displayed_link'] ?? null,
                            'snippet' => $result['snippet'] ?? null,
                            'snippet_highlighted_word' => isset($result['snippet_highlighted_words']) ? implode(", ", $result['snippet_highlighted_words']) : null,
                            'sitelinks' => (isset($result['sitelinks'])) ? json_encode($result['sitelinks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                            'favicon' => null,
                            'date' => $result['date'] ?? null,
                            'json' => $result ? json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (!empty($organicResultsData)) {
                        foreach (array_chunk($organicResultsData, 300) as $chunk) {
                            OrganicResult::insert($chunk);
                        }
                    }
                }
            }

            // 5. Handle RelatedQuestions Model
            if (isset($search_data['related_questions'])) {
                $relatedQuestionsData = [];

                // Check if records already exist
                $existingRelatedQuestions = RelatedQuestions::where([
                    ['keyword_planner_id', $keyword_planner_id],
                    ['keyword_request_id', $keyword_request_id],
                    ['client_property_id', $info['client_property_id']],
                    ['domainmanagement_id', $info['domainmanagement_id']]
                ])->count();

                if ($existingRelatedQuestions === 0) {
                    foreach ($search_data['related_questions'] as $question) {
                        if ($question['is_ai_overview'] ?? false) {
                            continue;
                        }
                        $relatedQuestionsData[] = [
                            'domainmanagement_id' => $info['domainmanagement_id'],
                            'client_property_id' => $info['client_property_id'],
                            'keyword_request_id' => $keyword_request_id,
                            'keyword_planner_id' => $keyword_planner_id,
                            'question' => $question['question'] ?? null,
                            'answer' => isset($question['answer']) ? $question['answer'] : $question['markdown'],
                            'source_title' => $question['source']['title'] ?? null,
                            'source_link' => $question['source']['link'] ?? null,
                            'source_source' => $question['source']['source'] ?? null,
                            'source_domain' => $question['source']['domain'] ?? null,
                            'source_displayed_link' => $question['source']['displayed_link'] ?? null,
                            'source_favicon' => null,
                            'json' => $question ? json_encode($question, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                            'date' => $question['date'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (!empty($relatedQuestionsData)) {
                        foreach (array_chunk($relatedQuestionsData, 300) as $chunk) {
                            RelatedQuestions::insert($chunk);
                        }
                    }
                }
            }

            // 6. Handle RelatedSearches Model
            if (isset($search_data['related_searches'])) {
                $relatedSearchesData = [];

                // Check if records already exist
                $existingRelatedSearches = RelatedSearches::where([
                    ['keyword_planner_id', $keyword_planner_id],
                    ['keyword_request_id', $keyword_request_id],
                    ['client_property_id', $info['client_property_id']],
                    ['domainmanagement_id', $info['domainmanagement_id']]
                ])->count();

                if ($existingRelatedSearches === 0) {
                    foreach ($search_data['related_searches'] as $relatedSearch) {
                        $relatedSearchesData[] = [
                            'domainmanagement_id' => $info['domainmanagement_id'],
                            'client_property_id' => $info['client_property_id'],
                            'keyword_request_id' => $keyword_request_id,
                            'keyword_planner_id' => $keyword_planner_id,
                            'query' => $relatedSearch['query'] ?? null,
                            'link' => $relatedSearch['link'] ?? null,
                            'json' => $relatedSearch ? json_encode($relatedSearch, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (!empty($relatedSearchesData)) {
                        foreach (array_chunk($relatedSearchesData, 300) as $chunk) {
                            RelatedSearches::insert($chunk);
                        }
                    }
                }
            }
        }

        // Return success or debug info
        return [
            'keyword_request_id' => $keyword_request_id,
            'keyword_planner_ids' => $keywordPlannerIds,
            'status' => 'Data processed successfully'
        ];
    }


    public function storeKeywordData(Request $request)
    {
        // Store data in session for Livewire to access
        session([
            'current_keyword' => $request->keyword,
            'current_keyword_data' => $request->keyword_data,
            'current_keyword_request_id' => $request->keyword_request_id,
            'current_client_property_id' => $request->client_property_id,
            'current_domainmanagement_id' => $request->domainmanagement_id,
        ]);

        // Also store in a simpler key that your Livewire component expects
        session(['keyword_data' => $request->keyword_data]);
        session(['keyword_request_id' => $request->keyword_request_id]);

        return response()->json(['success' => true]);
    }

    /**
     * Automatically fetch parent keywords from GSC and child keywords from Keyword Planner
     */
    public function autoKeywordFetch(Request $request)
    {
        try {

            // Initial load request
            $request->validate([
                'client_property_id' => 'required|integer',
                'domainmanagement_id' => 'required|integer',
                // 'domain_name' => 'required|string',
            ]);

            $clientPropertyId = $request->client_property_id;
            $domainManagementId = $request->domainmanagement_id;
            $filterType = $request->filter_type ?? 'domain'; // Get filter type
            $domainName = $request->domain_name ?? '';
            $masterKeyword = $request->master_keyword ?? '';
            $clusterType = $request->cluster_type ?? null;
            $clusterRequestID = $request->domain_cluster_request ?: $request->keyword_cluster_request;
            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;
            $limit = $request->median_limit;
            // dd($request->all());

            if ($clusterRequestID) {
                // Get cluster request
                // $clusterRequest = ClusterRequest::find($clusterRequestID);
                // $mainKeyword = $clusterRequest->keyword;
                return $this->loadFromClusterRequest($clusterRequestID, $clientPropertyId, $domainManagementId, $clusterType);
                // dd($clusterRequestID, $mainKeyword);
            }
            $mainKeyword = ($filterType === 'keyword' && $masterKeyword) ? $masterKeyword : $domainName;

            // dd($mainKeyword);
            // Store KeywordRequest
            $keywordRequest = KeywordRequest::firstOrCreate([
                'keyword' => $mainKeyword,
                'client_property_id' => $clientPropertyId,
                'domainmanagement_id' => $domainManagementId
            ], [
                'domainmanagement_id' => $domainManagementId,
                'client_property_id' => $clientPropertyId,
                'keyword' => $mainKeyword,
            ]);
            // dd($keywordRequest->toArray());
            $keywordRequestId = $keywordRequest->id;

            $clusterRequest = ClusterRequest::create([
                'domainmanagement_id' => $domainManagementId,
                'client_property_id' => $clientPropertyId,
                'keyword' => $mainKeyword,
                'date_from' => $dateFrom ?? null,
                'date_to' => $dateTo ?? null,
                'type' => $filterType,
            ]);

            if ($filterType === 'keyword' && $masterKeyword) {
                // Fetch keyword-based data
                // $parentKeywords = $this->fetchKeywordBasedData($masterKeyword);
                $keywordsData = $this->fetchKeywordPlannerKeywords(
                    $masterKeyword,
                    $keywordRequestId,
                    $clientPropertyId,
                    $domainManagementId,
                    $clusterRequest->id,
                    $filterType,
                    $dateFrom,
                    $dateTo,
                    $limit
                );
            } else {
                // For domain filter, fetch 1000 keywords from GSC
                $keywordsData = $this->fetchAndSaveGSCKeywords(
                    $domainName,
                    $keywordRequestId,
                    $clientPropertyId,
                    $domainManagementId,
                    $clusterRequest->id,
                    $filterType,
                    $dateFrom,
                    $dateTo,
                    $limit
                );
            }

            // dd($keywordsData);
            if (empty($keywordsData['keywords'])) {
                return response()->json([
                    'success' => false,
                    'message' => $filterType === 'keyword'
                        ? 'No keywords found from Keyword Planner.'
                        : 'No keywords found in Google Search Console for this domain.'
                ]);
            }
            // dd($keywordsData['keywords']);


            // if (empty($parentKeywords)) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => $filterType === 'keyword' 
            //         ? 'No data found for the specified keyword.'
            //         : 'No keywords found in Google Search Console for this domain.'
            //     ]);
            // }

            // Return with lazy load enabled

            return $this->returnKeywordsResponse(
                $keywordsData['keywords'],
                $clusterRequest,
                $keywordRequestId,
                $clientPropertyId,
                $domainManagementId,
                $mainKeyword,
                false,
                $filterType,
                $keywordsData['total_count']
            );
        } catch (\Exception $e) {
            dd($e);
            Log::error('Auto keyword fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'html' => '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>'
            ], 500);
        }
    }

    public function fetchAndSaveGSCKeywords($domainName, $keywordRequestId, $clientPropertyId, $domainManagementId, $clusterRequestId, $type, $dateFrom = null, $dateTo = null, $limit = 1000)
    {
        try {
            $keywords = [];
            $keywordPlannerData = [];
            $totalCount = 0;

            // Fetch 1000 keywords from GSC
            // $gscKeywords = $this->getEnrichedSearchAnalytics($domainName, $limit);

            // dd($gscKeywords);
            if ($dateFrom && $dateTo) {
                $gscKeywords = $this->getEnrichedSearchAnalytics($domainName, $limit, $dateFrom, $dateTo);
            } else {
                $gscKeywords = $this->getEnrichedSearchAnalytics($domainName, $limit);
                // dd('gscKeywords2',$gscKeywords);
            }
            // dd($gscKeywords);

            $count = 0;
            foreach ($gscKeywords as $gscKeyword) {
                $count++;
                $keyword = $gscKeyword['keyword'] ?? '';

                if (empty($keyword)) {
                    continue;
                }
                // dd($keywordPlannerData);

                $keywordPlanner = KeywordPlanner::create([
                    'keyword_request_id' => $keywordRequestId,
                    'cluster_request_id' => $clusterRequestId,
                    'client_property_id' => $clientPropertyId,
                    'domainmanagement_id' => $domainManagementId,
                    'keyword_p' => $keyword,
                    'monthlysearch_p' => $gscKeyword['avg_monthly_searches'] ?? 0,
                    'competition_p' => $gscKeyword['competition'] ?? '',
                    'low_bid_p' => $gscKeyword['low_top_of_page_bid_micros'] ?? 0,
                    'high_bid_p' => $gscKeyword['high_top_of_page_bid_micros'] ?? 0,
                    'clicks_p' => $gscKeyword['clicks'] ?? 0,
                    'ctr_p' => $gscKeyword['ctr'] ?? 0,
                    'impressions_p' => $gscKeyword['impressions'] ?? 0,
                    'position_p' => $gscKeyword['position'] ?? 0,
                    'ai_status' => '0',
                    'type' => $type, // Add type
                ]);
                // dd($keywordPlanner, $domainName, $keywordRequestId, $clientPropertyId, $domainManagementId, $clusterRequestId, $type);

                $keywordPlannerData[] = [
                    'id' => $keywordPlanner->id,
                    'keyword' => $keyword,
                    'keyword_request_id' => $keywordRequestId,
                    'cluster_request_id' => $clusterRequestId,
                    'client_property_id' => $clientPropertyId,
                    'domainmanagement_id' => $domainManagementId,
                    'is_gsc_keyword' => true,
                    'monthlysearch' => $gscKeyword['avg_monthly_searches'] ?? 0,
                    'competition' => $gscKeyword['competition'] ?? 0,
                    'low_bid' => $gscKeyword['low_top_of_page_bid_micros'] ?? 0,
                    'high_bid' => $gscKeyword['high_top_of_page_bid_micros'] ?? 0,
                    'clicks' => $gscKeyword['clicks'] ?? 0,
                    'ctr' => $gscKeyword['ctr'] ?? 0,
                    'impressions' => $gscKeyword['impressions'] ?? 0,
                    'position' => $gscKeyword['position'] ?? 0,
                    'type' => $type,
                    'ai_status' => '0',
                ];
                // Dispatch job to fetch additional data from keyword planner API
                // ProcessKeywordJob::dispatch(
                //     $keyword,
                //     $keywordPlanner->id,
                //     $keywordRequestId,
                //     $clientPropertyId,
                //     $domainManagementId,
                //     $clusterRequestId,
                //     true // Flag to indicate this is a GSC keyword
                // )->onQueue('keyword_processing');

                $totalCount++;

                // Small delay to avoid rate limiting
                if ($totalCount % 10 === 0) {
                    sleep(1);
                }
            }

            Log::info("Saved {$totalCount} GSC keywords directly to KeywordPlanner");
        } catch (\Exception $e) {
            dd($e);
            Log::error('Error fetching GSC keywords: ' . $e->getMessage());
        }

        return [
            'keywords' => $keywordPlannerData,
            'total_count' => $totalCount
        ];
    }

    private function fetchKeywordPlannerKeywords($masterKeyword, $keywordRequestId, $clientPropertyId, $domainManagementId, $clusterRequestId, $type, $dateFrom, $dateTo, $limit)
    {
        $keywords = [];
        $totalCount = 0;

        try {
            // Fetch related keywords from Keyword Planner
            if ($dateFrom && $dateTo) {
                $relatedKeywords = $this->kpService->searchKeywords($masterKeyword, $limit, $dateFrom, $dateTo, $clientPropertyId);
            } else {
                $relatedKeywords = $this->kpService->searchKeywords($masterKeyword, $limit, null, null, $clientPropertyId);
            }
            foreach ($relatedKeywords as $relatedKeyword) {
                $keyword = $relatedKeyword['keyword'] ?? '';

                if (empty($keyword)) {
                    continue;
                }

                $keywordPlanner = KeywordPlanner::create([
                    'keyword_request_id' => $keywordRequestId,
                    'cluster_request_id' => $clusterRequestId,
                    'client_property_id' => $clientPropertyId,
                    'domainmanagement_id' => $domainManagementId,
                    'keyword_p' => $keyword,
                    'monthlysearch_p' => $relatedKeyword['avg_monthly_searches'] ?? 0,
                    'competition_p' => $relatedKeyword['competition'] ?? '',
                    'low_bid_p' => $relatedKeyword['low_top_of_page_bid_micros'] ?? 0,
                    'high_bid_p' => $relatedKeyword['high_top_of_page_bid_micros'] ?? 0,
                    'clicks_p' => $relatedKeyword['clicks'] ?? 0,
                    'ctr_p' => $relatedKeyword['ctr'] ?? 0,
                    'impressions_p' => $relatedKeyword['impressions'] ?? 0,
                    'position_p' => $relatedKeyword['position'] ?? 0,
                    'type' => $type,
                    'ai_status' => '0',
                ]);

                // Dispatch job for AI Overview processing
                ProcessKeywordJob::dispatch(
                    $keyword,
                    $keywordPlanner->id,
                    $keywordRequestId,
                    $clientPropertyId,
                    $domainManagementId,
                    $clusterRequestId,
                    false // Not a GSC keyword
                );

                $keywords[] = [
                    'id' => $keywordPlanner->id,
                    'keyword_request_id' => $keywordRequestId,
                    'cluster_request_id' => $clusterRequestId,
                    'client_property_id' => $clientPropertyId,
                    'domainmanagement_id' => $domainManagementId,
                    'keyword' => $keyword,
                    'monthlysearch' => $relatedKeyword['avg_monthly_searches'] ?? 0,
                    'competition' => $relatedKeyword['competition'] ?? '',
                    'low_bid' => $relatedKeyword['low_top_of_page_bid_micros'] ?? 0,
                    'high_bid' => $relatedKeyword['high_top_of_page_bid_micros'] ?? 0,
                    'clicks' => $relatedKeyword['clicks'] ?? 0,
                    'ctr' => $relatedKeyword['ctr'] ?? 0,
                    'impressions' => $relatedKeyword['impressions'] ?? 0,
                    'position' => $relatedKeyword['position'] ?? 0,
                    'type' => $type,
                    'is_gsc_keyword' => false,
                    'has_planner_data' => true,
                    'type' => $type,
                    'ai_status' => '0',
                ];

                $totalCount++;

                // Small delay to avoid rate limiting
                if ($totalCount % 10 === 0) {
                    sleep(1);
                }
            }


            Log::info("Saved {$totalCount} Keyword Planner keywords");
        } catch (\Exception $e) {
            Log::error('Error fetching Keyword Planner keywords: ' . $e->getMessage());
        }

        return [
            'keywords' => $keywords,
            'total_count' => $totalCount
        ];
    }

    public function aioKeywordFetch(Request $request)
    {
        $parentKeywordsForDisplay = [];

        $childKeywordsForDisplay = [];
        $request->validate([
            'client_property_id' => 'required|integer',
            'domainmanagement_id' => 'required|integer',
            // 'domain_name' => 'required|string',
        ]);

        $clientPropertyId = $request->client_property_id;
        $domainManagementId = $request->domainmanagement_id;
        $filterType = $request->filter_type ?? 'domain'; // Get filter type
        $domainName = $request->domain_name ?? '';
        $masterKeyword = $request->master_keyword ?? '';
        $clusterType = $request->cluster_type ?? null;
        $clusterRequestID = $request->domain_cluster_request ? $request->domain_cluster_request : $request->keyword_cluster_request;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $limit = 5;
        // dd($request->all());

        $clusterRequest = ClusterRequest::find($clusterRequestID);
        $mainKeyword = $clusterRequest->keyword;
        $keywordRequest = KeywordRequest::where([
            ['keyword', $mainKeyword],
            ['client_property_id', $clientPropertyId],
            ['domainmanagement_id', $domainManagementId]
        ])->first();



        $keywordRequestId = $keywordRequest->id;

        // Check if this is a previous cluster request
        if ($clusterRequestID) {
            $clusterRequest = ClusterRequest::where('id', $clusterRequestID)->first();
            // Get parent keywords from this cluster request
            $urlPattern = '/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/.*)?$/';
            $domain = preg_match($urlPattern, $clusterRequest->keyword) ? $clusterRequest->keyword : false;
            $parentKeywords = ParentKeyword::where('cluster_request_id', $clusterRequestID)
                ->orderBy('id', 'asc')
                ->get();

            if ($parentKeywords->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No parent keywords found in this cluster request.'
                ]);
            }

            // Format parent keywords for display
            foreach ($parentKeywords as $parentKeyword) {
                $childCount = KeywordPlanner::where('parent_keyword', $parentKeyword->id)->count();

                $parentKeywordsForDisplay[] = [
                    'id' => $parentKeyword->id,
                    'keyword' => $parentKeyword->parent_keyword,
                    'clicks' => $parentKeyword->clicks,
                    'impressions' => $parentKeyword->impressions,
                    'ctr' => $parentKeyword->ctr,
                    'position' => $parentKeyword->position,
                    'child_count' => $childCount,
                    'children_loaded' => false,
                    'type' => $clusterType, // Add cluster type
                ];
                $childKeywords = KeywordPlanner::where('parent_keyword', $parentKeyword->id)
                    ->orderBy('id', 'asc')
                    ->get();

                // Get AI overview IDs for these keywords in a separate query
                $keywordIds = $childKeywords->pluck('id')->toArray();

                $aiOverviewIds = AiOverview::whereIn('keyword_planner_id', $keywordIds)
                    ->get()
                    ->keyBy('keyword_planner_id');
                // ->toArray();
                // dd($aiOverviewIds);


                $childKeywordsForDisplay[$parentKeyword->id] = $childKeywords->map(function ($keyword) use ($aiOverviewIds, $domain) {
                    // $aiOverview = $aiOverviews[$keyword->id] ?? null;
                    // $aiOverview = $aiOverviewIds[$keyword->id] ?? null;
                    $aiOverview = $aiOverviewIds->get($keyword->id);
                    // dd($aiOverviewIds);

                    return [
                        'id' => $keyword->id,
                        'keyword' => $keyword->keyword_p,
                        'clicks' => $keyword->clicks_p,
                        'impressions' => $keyword->impressions_p,
                        'ctr' => $keyword->ctr_p,
                        'position' => $keyword->position_p,
                        'monthlysearch_p' => $keyword->monthlysearch_p,
                        'competition_p' => $keyword->competition_p,
                        'low_bid_p' => $keyword->low_bid_p,
                        'high_bid_p' => $keyword->high_bid_p,
                        'monthlysearchvolume_p' => $keyword->monthlysearchvolume_p,
                        'has_ai_overview' => $aiOverviewIds->has($keyword->id),
                        'domain_available' => $domain
                            ? GeneralHelper::domainExistsInAIOverview($aiOverview, $domain)
                            : false,
                    ];
                })->toArray();
            }
            // dd($childKeywordsForDisplay);
        }
        return response()->json([
            'success' => true,
            'parent_keywords' => $parentKeywordsForDisplay,
            'child_keywords' => $childKeywordsForDisplay,
            'message' => 'Found the data keywords found in Google Search Console for this domain.'
        ]);
    }

    private function fetchKeywordBasedData($masterKeyword)
    {
        return [
            [
                'query' => $masterKeyword,
                'clicks' => 0,
                'impressions' => 0,
                'ctr' => 0,
                'position' => 0,
            ]
        ];
    }

    private function fetchAndSaveChildKeywords($parentQuery, $parentKeywordId, $keywordRequestId, $clientPropertyId, $domainManagementId, $clusterRequestId, $type = 'domain')
    {
        $children = [];

        try {
            // Fetch child keywords from Keyword Planner
            $childKeywords = $this->kpService->searchKeywords($parentQuery, 5);

            if (!empty($childKeywords)) {
                foreach ($childKeywords as $childKeyword) {
                    // Store child keyword in KeywordPlanner table
                    $keywordPlanner = KeywordPlanner::create([
                        'keyword_p' => $childKeyword['keyword'],
                        'keyword_request_id' => $keywordRequestId,
                        'parent_keyword' => $parentKeywordId,
                        'cluster_request_id' => $clusterRequestId,
                        'client_property_id' => $clientPropertyId,
                        'domainmanagement_id' => $domainManagementId,
                        'monthlysearch_p' => $childKeyword['avg_monthly_searches'] ?? 0,
                        'competition_p' => $childKeyword['competition'] ?? '',
                        'low_bid_p' => $childKeyword['low_top_of_page_bid_micros'] ?? 0,
                        'high_bid_p' => $childKeyword['high_top_of_page_bid_micros'] ?? 0,
                        'clicks_p' => $childKeyword['clicks'] ?? 0,
                        'ctr_p' => $childKeyword['ctr'] ?? 0,
                        'impressions_p' => $childKeyword['impressions'] ?? 0,
                        'position_p' => $childKeyword['position'] ?? 0,
                        'ai_status' => '0',
                        'type' => $type, // Add type
                    ]);

                    // Dispatch job for background processing (AI Overview and related data)
                    ProcessKeywordJob::dispatch(
                        $childKeyword['keyword'],
                        $keywordPlanner->id,
                        $keywordRequestId,
                        $clientPropertyId,
                        $domainManagementId,
                        $clusterRequestId,
                    )->onQueue('keyword_processing');

                    $existingAiOverview = AiOverview::where('keyword_planner_id', $keywordPlanner->id)->first();
                    $hasAiOverview = $existingAiOverview ? true : false;
                    Log::info('Keyword Planner id:', ['id' => $keywordPlanner->id, 'has_ai_overview' => $hasAiOverview, 'existingAiOverview' => $existingAiOverview]);

                    // Prepare data for immediate display
                    $children[] = [
                        'child_keyword_id' => $keywordPlanner->id,
                        'parent_keyword' => $parentQuery,
                        'keyword' => $childKeyword['keyword'],
                        'avg_monthly_searches' => $childKeyword['avg_monthly_searches'] ?? 0,
                        'competition' => $childKeyword['competition'] ?? '',
                        'low_top_of_page_bid_micros' => $childKeyword['low_top_of_page_bid_micros'] ?? 0,
                        'high_top_of_page_bid_micros' => $childKeyword['high_top_of_page_bid_micros'] ?? 0,
                        'clicks' => $childKeyword['clicks'] ?? 0,
                        'ctr' => $childKeyword['ctr'] ?? 0,
                        'impressions' => $childKeyword['impressions'] ?? 0,
                        'position' => $childKeyword['position'] ?? 0,
                        'type' => $type, // Add type to display
                    ];
                }

                Log::info('Found ' . count($childKeywords) . ' child keywords for parent: ' . $parentQuery);
            }
        } catch (\Exception $e) {
            Log::error('Error fetching child keywords for ' . $parentQuery . ': ' . $e->getMessage());
        }

        return [
            'children' => $children,
            'count' => count($children)
        ];
    }

    private function loadFromClusterRequest($clusterRequestID, $clientPropertyId, $domainManagementId, $clusterType = 'domain')
    {
        try {
            // Get the cluster request
            $clusterRequest = ClusterRequest::where('id', $clusterRequestID)->first();

            if (!$clusterRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cluster request not found.'
                ]);
            }

            // Check if this cluster request belongs to the current client/domain
            if (
                $clusterRequest->client_property_id != $clientPropertyId ||
                $clusterRequest->domainmanagement_id != $domainManagementId
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'This cluster request does not belong to the current domain.'
                ]);
            }

            // Get parent keywords from this cluster request
            $keywords = KeywordPlanner::where('cluster_request_id', $clusterRequestID)
                ->orderBy('id', 'asc')
                ->get();

            if ($keywords->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No keywords found in this cluster request.'
                ]);
            }

            $keywordsForDisplay = [];

            foreach ($keywords as $keyword) {
                $keywordsForDisplay[] = [
                    'id' => $keyword->id,
                    'keyword' => $keyword->keyword_p,
                    'monthlysearch' => $keyword->monthlysearch_p,
                    'competition' => $keyword->competition_p,
                    'low_bid' => $keyword->low_bid_p,
                    'high_bid' => $keyword->high_bid_p,
                    'clicks' => $keyword->clicks_p,
                    'ctr' => $keyword->ctr_p,
                    'impressions' => $keyword->impressions_p,
                    'position' => $keyword->position_p,
                    'ai_status' => $keyword->ai_status,
                ];
            }

            // Check if we need to calculate median data
            $isMedianView = request()->has('median') && request()->median == 'true';
            $medianLimit = request()->has('median_limit') ? intval(request()->median_limit) : 20;

            if ($isMedianView) {
                $medianData = $this->calculateMedianKeywords($keywordsForDisplay, $medianLimit);
                $keywordsForDisplay = $medianData['keywords'];
            }

            return $this->returnKeywordsResponse(
                $keywordsForDisplay,
                $clusterRequest,
                $keywords->first()->keyword_request_id,
                $clientPropertyId,
                $domainManagementId,
                $clusterRequest->keyword,
                true,
                $clusterType,
                $keywords->count(),
                $isMedianView,
                $medianLimit
            );
        } catch (\Exception $e) {
            Log::error('Load from cluster request error: ' . $e->getMessage());
            throw $e;
        }
    }

    // Add this new method to calculate median keywords
    private function calculateMedianKeywords($keywords, $limit = 20)
    {
        // Filter out keywords with no monthly search data
        $validKeywords = array_filter($keywords, function ($keyword) {
            return isset($keyword['monthlysearch']) && is_numeric($keyword['monthlysearch']);
        });

        if (empty($validKeywords)) {
            return [
                'keywords' => $keywords,
                'median_value' => 0,
                'average' => 0
            ];
        }

        // FIRST: Sort all keywords by monthly search in ascending order
        usort($validKeywords, function ($a, $b) {
            return $a['monthlysearch'] <=> $b['monthlysearch'];
        });

        // Calculate average
        $monthlySearches = array_column($validKeywords, 'monthlysearch');
        $average = array_sum($monthlySearches) / count($monthlySearches);

        // Find the position where average falls in the sorted array
        $averagePosition = 0;
        foreach ($validKeywords as $index => $keyword) {
            if ($keyword['monthlysearch'] >= $average) {
                $averagePosition = $index;
                break;
            }
        }

        // If no keyword >= average, use last position
        if ($averagePosition === 0 && end($validKeywords)['monthlysearch'] < $average) {
            $averagePosition = count($validKeywords) - 1;
        }

        // Calculate how many keywords to take below and above
        $halfLimit = intval($limit / 2); // Should be 10 for limit=20

        // Get keywords below average (lower half)
        $startIndex = max(0, $averagePosition - $halfLimit);
        $lowerKeywords = array_slice($validKeywords, $startIndex, $halfLimit);

        // Get keywords above average (upper half)
        $upperKeywords = array_slice($validKeywords, $averagePosition, $halfLimit);

        // Combine both halves
        $medianKeywords = array_merge($lowerKeywords, $upperKeywords);

        // If we have less than limit, take more from available pool
        if (count($medianKeywords) < $limit) {
            $needed = $limit - count($medianKeywords);

            // Get additional keywords from the beginning if we need more
            if ($needed > 0) {
                $additionalKeywords = array_slice($validKeywords, 0, $needed);
                $medianKeywords = array_merge($additionalKeywords, $medianKeywords);
            }

            // Sort again by monthly search
            usort($medianKeywords, function ($a, $b) {
                return $a['monthlysearch'] <=> $b['monthlysearch'];
            });
        }

        // Ensure we have exactly $limit keywords
        $medianKeywords = array_slice($medianKeywords, 0, $limit);

        return [
            'keywords' => $medianKeywords,
            'median_value' => $medianKeywords[floor($limit / 2)]['monthlysearch'] ?? 0,
            'average' => $average,
            'average_position' => $averagePosition,
            'total_keywords' => count($validKeywords)
        ];
    }

    private function returnKeywordsResponse($keywords, $clusterRequest, $keywordRequestId, $clientPropertyId, $domainManagementId, $mainKeyword, $isPreviousRequest = false, $filterType = 'domain', $totalCount = 0, $isMedianView = false, $medianLimit = 20)
    {
        $totalKeywords = count($keywords);
        $isFullResults = ($totalKeywords >= 1000);
        $remainingKeywords = 1000 - $totalKeywords;

        session(['temp_keyplan' => $keywords]);
        session(['temp_info' => $keywords]);
        session(['is_full_results' => $isFullResults]);
        session(['remaining_keywords' => $remainingKeywords]);

        // Generate HTML with parent keywords only
        $html = view('keyword-analysis.keyword-results-auto', [
            'keywords' => $keywords,
            'domain_name' => $mainKeyword,
            'cluster_request_status' => $isPreviousRequest,
            'cluster_request' => $clusterRequest,
            'keywords_count' => count($keywords),
            'total_count' => $totalCount,
            'keyword_request_id' => $keywordRequestId,
            'client_property_id' => $clientPropertyId,
            'domainmanagement_id' => $domainManagementId,
            'lazy_load' => true,
            'auto_load' => true,
            'filter_type' => $filterType,
            'is_full_results' => $isFullResults,
            'remaining_keywords' => $remainingKeywords,
            'is_median_view' => $isMedianView,
            'median_limit' => $medianLimit,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'data' => [
                'keywords_count' => count($keywords),
                'keyword_request_id' => $keywordRequestId,
                'cluster_request_id' => $clusterRequest->id,
                'is_previous_request' => $isPreviousRequest,
                'lazy_load' => true,
                'auto_load' => true,
                'filter_type' => $filterType,
                'is_median_view' => $isMedianView,
                'median_limit' => $medianLimit,
                'keywords' => array_map(function ($keyword, $index) {
                    return [
                        'id' => $index + 1,
                        'keyword' => $keyword['keyword'],
                        'type' => $keyword['type'] ?? null,
                        'is_gsc_keyword' => $keyword['is_gsc_keyword'] ?? '',
                    ];
                }, $keywords, array_keys($keywords))
            ],
            'is_full_results' => $isFullResults,
            'remaining_keywords' => $remainingKeywords,
            'message' => $isMedianView
                ? 'Showing median analysis with ' . count($keywords) . ' keywords.'
                : ($isPreviousRequest
                    ? 'Loading ' . count($keywords) . ' parent keywords...'
                    : 'Successfully fetched and saved ' . count($keywords) . ' keywords.')
        ]);
    }


    private function lazyLoadChildKeywords(Request $request)
    {
        try {
            $request->validate([
                'parent_keyword_id' => 'required|integer',
                'keyword_request_id' => 'required|integer',
                'client_property_id' => 'required|integer',
                'domainmanagement_id' => 'required|integer',
            ]);

            $parentKeywordId = $request->parent_keyword_id;
            $clientPropertyId = $request->client_property_id;
            $domainManagementId = $request->domainmanagement_id;
            $keywordRequestId = $request->keyword_request_id;
            $clusterRequestId = $request->cluster_request_id;
            $isPreviousRequest = $request->is_previous_request ?? false;

            // Get parent keyword
            $parentKeyword = ParentKeyword::find($parentKeywordId);
            if (!$parentKeyword) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent keyword not found.'
                ]);
            }

            $parentQuery = $parentKeyword->parent_keyword;

            // Check if child keywords already exist
            $existingChildren = KeywordPlanner::where([
                'client_property_id' => $clientPropertyId,
                'domainmanagement_id' => $domainManagementId,
                'cluster_request_id' => $clusterRequestId,
                'parent_keyword' => $parentKeywordId,
            ])->get();


            $childKeywordsForDisplay = [];

            if ($existingChildren->isEmpty() && !$isPreviousRequest) {
                // Only fetch new child keywords if NOT from previous request
                $childKeywords = $this->kpService->searchKeywords($parentQuery, 5);

                foreach ($childKeywords as $childKeyword) {
                    // Store child keyword
                    $keywordPlanner = KeywordPlanner::create([
                        'keyword_p' => $childKeyword['keyword'],
                        'keyword_request_id' => $keywordRequestId,
                        'parent_keyword' => $parentKeywordId,
                        'cluster_request_id' => $clusterRequestId,
                        'client_property_id' => $clientPropertyId,
                        'domainmanagement_id' => $domainManagementId,
                        'monthlysearch_p' => $childKeyword['avg_monthly_searches'] ?? 0,
                        'competition_p' => $childKeyword['competition'] ?? '',
                        'low_bid_p' => $childKeyword['low_top_of_page_bid_micros'] ?? 0,
                        'high_bid_p' => $childKeyword['high_top_of_page_bid_micros'] ?? 0,
                        'clicks_p' => $childKeyword['clicks'] ?? 0,
                        'ctr_p' => $childKeyword['ctr'] ?? 0,
                        'impressions_p' => $childKeyword['impressions'] ?? 0,
                        'position_p' => $childKeyword['position'] ?? 0,
                        'ai_status' => '0',
                    ]);

                    // Dispatch job for background processing
                    ProcessKeywordJob::dispatch(
                        $childKeyword['keyword'],
                        $keywordPlanner->id,
                        $keywordRequestId,
                        $clientPropertyId,
                        $domainManagementId,
                        $clusterRequestId
                    )->onQueue('keyword_processing');

                    $childKeywordsForDisplay[] = [
                        'child_keyword_id' => $keywordPlanner->id,
                        'aioStatus' => false,
                        'parent_keyword' => $parentQuery,
                        'keyword' => $childKeyword['keyword'],
                        'avg_monthly_searches' => $childKeyword['avg_monthly_searches'] ?? 0,
                        'competition' => $childKeyword['competition'] ?? '',
                        'low_top_of_page_bid_micros' => $childKeyword['low_top_of_page_bid_micros'] ?? 0,
                        'high_top_of_page_bid_micros' => $childKeyword['high_top_of_page_bid_micros'] ?? 0,
                        'clicks' => $childKeyword['clicks'] ?? 0,
                        'ctr' => $childKeyword['ctr'] ?? 0,
                        'impressions' => $childKeyword['impressions'] ?? 0,
                        'position' => $childKeyword['position'] ?? 0,
                    ];
                }
            } else {
                // dd($existingChildren);
                foreach ($existingChildren as $childKeyword) {
                    $aiOverview_response = AiOverview::where([
                        'domainmanagement_id' => $domainManagementId,
                        'client_property_id' => $clientPropertyId,
                        'keyword_request_id' => $keywordRequestId,
                        'cluster_request_id' => $clusterRequestId,
                        'keyword_planner_id' => $childKeyword->id,
                    ])->exists();

                    $childKeywordsForDisplay[] = [
                        'child_keyword_id' => $childKeyword->id,
                        'aioStatus' => $aiOverview_response,
                        'parent_keyword' => $parentQuery,
                        'keyword' => $childKeyword->keyword_p,
                        'avg_monthly_searches' => $childKeyword->monthlysearch_p,
                        'competition' => $childKeyword->competition_p,
                        'low_top_of_page_bid_micros' => $childKeyword->low_bid_p,
                        'high_top_of_page_bid_micros' => $childKeyword->high_bid_p,
                        'clicks' => $childKeyword->clicks_p,
                        'ctr' => $childKeyword->ctr_p,
                        'impressions' => $childKeyword->impressions_p,
                        'position' => $childKeyword->position_p,
                    ];

                    // dd($aiOverview_response, $childKeywordsForDisplay);
                }
            }

            // Generate HTML for child keywords table
            $html = view('keyword-analysis.child-keywords-table', [
                'parent_keyword' => $parentQuery,
                'parent_keyword_id' => $parentKeywordId,
                'keywords' => $childKeywordsForDisplay,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'parent_keyword_id' => $parentKeywordId,
                'child_count' => count($childKeywordsForDisplay),
                'message' => 'Loaded ' . count($childKeywordsForDisplay) . ' child keywords for "' . $parentQuery . '"'
            ]);
        } catch (\Exception $e) {
            Log::error('Lazy load child keywords error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading child keywords: ' . $e->getMessage()
            ], 500);
        }
    }

    private function fetchAndStoreKeywordData($keyword, $keywordPlannerId, $keywordRequestId, $clientPropertyId, $domainManagementId, $clusterRequest_id)
    {
        try {
            // Fetch search results
            $searchJson = GeneralHelper::getSearchResult($keyword);
            $searchData = json_decode($searchJson, true);

            if (!$searchData) {
                throw new \Exception("Failed to fetch search results for keyword: {$keyword}");
            }

            // Process AI Overview
            $aiOverview = null;
            $hasAio = false;

            if (isset($searchData['ai_overview'])) {
                if (!isset($searchData['ai_overview']['page_token'])) {
                    $aiOverview = $searchData['ai_overview'];
                    $hasAio = true;
                } else {
                    $aioJson = GeneralHelper::getaioResult($searchData['ai_overview']['page_token']);
                    $aiOverview = json_decode($aioJson, true);
                    $hasAio = true;
                }
            }

            // Store AI Overview
            Log::info('Store AI Overview: AIOverview');
            if ($aiOverview) {
                AiOverview::create([
                    'domainmanagement_id' => $domainManagementId,
                    'client_property_id' => $clientPropertyId,
                    'keyword_request_id' => $keywordRequestId,
                    'keyword_planner_id' => $keywordPlannerId,
                    'cluster_request_id' => $clusterRequest_id,
                    'text_blocks' => isset($aiOverview['text_blocks']) ?
                        json_encode($aiOverview['text_blocks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    'json' => $aiOverview ?
                        json_encode($aiOverview, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    'markdown' => $aiOverview['markdown'] ?? null,
                ]);

                // Update keyword planner AI status
                Log::info("Status 1: {$keywordPlannerId}");
                KeywordPlanner::where('id', $keywordPlannerId)->update(['ai_status' => '1']);
            } else {
                Log::info("Status 0: {$keywordPlannerId}");
                KeywordPlanner::where('id', $keywordPlannerId)->update(['ai_status' => '0']);
            }
            Log::info('Store Organic Results: OrganicResult');

            // Store Organic Results
            if (isset($searchData['organic_results'])) {
                $organicResultsData = [];
                foreach ($searchData['organic_results'] as $result) {
                    $organicResultsData[] = [
                        'domainmanagement_id' => $domainManagementId,
                        'client_property_id' => $clientPropertyId,
                        'keyword_request_id' => $keywordRequestId,
                        'cluster_request_id' => $clusterRequest_id,
                        'keyword_planner_id' => $keywordPlannerId,
                        'position' => $result['position'] ?? null,
                        'title' => $result['title'] ?? null,
                        'link' => $result['link'] ?? null,
                        'source' => $result['source'] ?? null,
                        'domain' => $result['domain'] ?? null,
                        'displayed_link' => $result['displayed_link'] ?? null,
                        'snippet' => $result['snippet'] ?? null,
                        'snippet_highlighted_word' => isset($result['snippet_highlighted_words']) ?
                            implode(", ", $result['snippet_highlighted_words']) : null,
                        'sitelinks' => (isset($result['sitelinks'])) ?
                            json_encode($result['sitelinks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'favicon' => null,
                        'date' => $result['date'] ?? null,
                        'json' => $result ? json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($organicResultsData)) {
                    foreach (array_chunk($organicResultsData, 100) as $chunk) {
                        OrganicResult::insert($chunk);
                    }
                }
            }
            Log::info('Store: related_questions');

            // Store Related Questions
            if (isset($searchData['related_questions'])) {
                $relatedQuestionsData = [];
                foreach ($searchData['related_questions'] as $question) {
                    if ($question['is_ai_overview'] ?? false) {
                        continue;
                    }
                    $relatedQuestionsData[] = [
                        'domainmanagement_id' => $domainManagementId,
                        'client_property_id' => $clientPropertyId,
                        'keyword_planner_id' => $keywordPlannerId,
                        'keyword_request_id' => $keywordRequestId,
                        'cluster_request_id' => $clusterRequest_id,
                        'question' => $question['question'] ?? null,
                        'answer' => isset($question['answer']) ? $question['answer'] : ($question['markdown'] ?? null),
                        'source_title' => $question['source']['title'] ?? null,
                        'source_link' => $question['source']['link'] ?? null,
                        'source_source' => $question['source']['source'] ?? null,
                        'source_domain' => $question['source']['domain'] ?? null,
                        'source_displayed_link' => $question['source']['displayed_link'] ?? null,
                        'source_favicon' => null,
                        'json' => $question ? json_encode($question, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'date' => $question['date'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($relatedQuestionsData)) {
                    foreach (array_chunk($relatedQuestionsData, 100) as $chunk) {
                        RelatedQuestions::insert($chunk);
                    }
                }
            }

            // Store Related Searches
            Log::info('Store: related_searches');

            if (isset($searchData['related_searches'])) {
                $relatedSearchesData = [];
                foreach ($searchData['related_searches'] as $relatedSearch) {
                    $relatedSearchesData[] = [
                        'domainmanagement_id' => $domainManagementId,
                        'client_property_id' => $clientPropertyId,
                        'keyword_planner_id' => $keywordPlannerId,
                        'keyword_request_id' => $keywordRequestId,
                        'cluster_request_id' => $clusterRequest_id,
                        'query' => $relatedSearch['query'] ?? null,
                        'link' => $relatedSearch['link'] ?? null,
                        'json' => $relatedSearch ? json_encode($relatedSearch, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($relatedSearchesData)) {
                    foreach (array_chunk($relatedSearchesData, 100) as $chunk) {
                        RelatedSearches::insert($chunk);
                    }
                }
            }

            Log::info("Successfully stored data for keyword: {$keyword}");
            return [
                'aioStatus' => $hasAio,
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching and storing data for keyword {$keyword}: " . $e->getMessage());
            throw $e; // Re-throw to be handled by caller
        }
    }

    public function checkChildKeywords($parentKeywordId, Request $request)
    {
        try {
            $parentKeyword = ParentKeyword::find($parentKeywordId);

            if (!$parentKeyword) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent keyword not found.'
                ]);
            }

            $childCount = KeywordPlanner::where('parent_keyword', $parentKeywordId)->count();

            return response()->json([
                'success' => true,
                'child_count' => $childCount,
                'has_children' => $childCount > 0
            ]);
        } catch (\Exception $e) {
            Log::error('Check child keywords error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error checking child keywords'
            ], 500);
        }
    }

    public function queue()
    {
        Log::error("123");

        // Run the artisan command directly
        // For Laravel 7.x and above
        Artisan::call('optimize:clear');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('queue:restart');
        Artisan::call('queue:clear');
        Artisan::call('queue:work');

        $output = Artisan::output();

        $logMessage = date('Y-m-d H:i:s') . ' - ' . $output . "\n";
        Log::info($logMessage);
        file_put_contents(storage_path('logs/queue_log.txt'), $logMessage, FILE_APPEND);

        return response()->json(['status' => 'success', 'output' => $output]);
    }
    
    public function syncQueue(Request $request)
    {
        try {
            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--timeout'         => 360,
                '--tries'           => 3,
            ]);

            $output = Artisan::output();

            Log::info('Queue sync triggered via HTTP. Output: ' . $output);

            return response()->json([
                'success' => true,
                'message' => 'Queue sync completed successfully.',
                'output'  => $output,
            ]);

        } catch (\Exception $e) {
            Log::error('Queue sync error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Queue sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public static function printAllTables()
    {
        // DB::table('keyword_planner')->where('keyword_request_id', 7)->delete();
        // DB::table('median-fetch')->delete();
        // DB::table('median_info')->delete();
        // MedianFetch
        // dd("que");

        
        $jobs1 = DB::table('median-fetch')->get();
        foreach ($jobs1 as $job) {
            echo "<pre>";
            print_r($job);
            echo "</pre>";
        }

        // $jobs = DB::table('median_info')->get();
        // foreach ($jobs as $job) {
        //     echo "<pre>";
        //     print_r($job);
        //     echo "</pre>";
        // }
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $key = 'Tables_in_' . $databaseName;

        echo "<style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .table-container { margin-bottom: 30px; }
            .table-name { background: #3498db; color: white; padding: 10px; font-weight: bold; }
            .attributes-table { width: 100%; border-collapse: collapse; }
            .attributes-table th { background: #f2f2f2; padding: 8px; text-align: left; }
            .attributes-table td { padding: 8px; border-bottom: 1px solid #ddd; }
        </style>";

        foreach ($tables as $table) {
            $tableName = $table->{$key};

            echo "<div class='table-container'>";
            echo "<div class='table-name'>Table: {$tableName}</div>";

            // Get columns information
            $columns = DB::select("DESCRIBE `{$tableName}`");

            echo "<table class='attributes-table'>";
            echo "<tr><th>Field</th></tr>";

            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column->Field}</td>";
                echo "</tr>";
            }

            echo "</table>";
            echo "</div>";
        }
    }

    // Alternative: Console command version
    public static function printTablesToConsole()
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $key = 'Tables_in_' . $databaseName;

        echo "Database: {$databaseName}\n";
        echo "========================\n\n";

        foreach ($tables as $table) {
            $tableName = $table->{$key};

            echo "Table: {$tableName}\n";
            echo str_repeat("-", 50) . "\n";

            $columns = DB::select("DESCRIBE `{$tableName}`");

            printf(
                "%-20s %-15s %-5s %-5s %-10s %-10s\n",
                'Field',
                'Type',
                'Null',
                'Key',
                'Default',
                'Extra'
            );
            echo str_repeat("-", 70) . "\n";

            foreach ($columns as $column) {
                printf(
                    "%-20s %-15s %-5s %-5s %-10s %-10s\n",
                    $column->Field,
                    $column->Type,
                    $column->Null,
                    $column->Key,
                    $column->Default ?? 'NULL',
                    $column->Extra
                );
            }
            echo "\n\n";
        }
    }

    // Controller
    public function checkAIStatus(Request $request)
    {
        // Get all child_keyword_ids from the request
        $childKeywordIds = $request->input('child_keyword_ids', []);

        // If no IDs provided, return empty response
        if (empty($childKeywordIds)) {
            return response()->json([]);
        }

        $results = [];

        // Check status for each child_keyword_id
        foreach ($childKeywordIds as $childKeywordId) {
            $organicResult = OrganicResult::where('keyword_planner_id', $childKeywordId)->exists();
            $aiOverview = AiOverview::where('keyword_planner_id', $childKeywordId)->exists();

            $results[$childKeywordId] = [
                'success' => !$organicResult ? 0 : ($aiOverview ? 1 : 2),
                'hasAIOverview' => !$organicResult ? 0 : ($aiOverview ? 1 : 2),
            ];
        }

        return response()->json($results);
    }
    /**
     * Fetch GSC data for a given URL and display it
     */
    public function fetchGscData()
    {
        $url = 'https://www.aaynaclinic.com';
        $limit = 20;
        // $dateFrom = null;
        // $dateTo = null;
        $search_in_aio = "aayna,ayana clinic, Omega-3s, Vitamin , ayana, simal soin, aayna clinic";
        $searchTerms = array_map('trim', explode(',', $search_in_aio));

        // Generate a unique session ID for this page load
        $sessionId = session()->getId() . '_' . Str::random(10);
        session()->put('gsc_session_id', $sessionId);

        // Fetch GSC data

        $gscData = $this->gscService->getSearchAnalytics($url, $limit);

        // Check if data was fetched
        if (empty($gscData)) {
            return response()->json([
                'success' => false,
                'message' => 'No data found for the specified URL.',
                'html' => '<div class="alert alert-warning">No data found for the specified URL.</div>'
            ]);
        }

        // Process data
        $processedData = [];
        $keywordsToProcess = [];

        // Clear any old cache for this session first
        foreach (range(0, $limit - 1) as $index) {
            $cacheKey = "gsc_aio_result_{$sessionId}_{$index}";
            cache()->forget($cacheKey);
        }

        foreach ($gscData as $index => $item) {
            $keyword = $item['query'] ?? '';

            // Always set to Processing initially - FIXED
            // We'll dispatch jobs for ALL keywords
            $processedData[] = [
                'query' => $keyword,
                'clicks' => $item['clicks'] ?? 0,
                'impressions' => $item['impressions'] ?? 0,
                'ctr' => $item['ctr'] ?? 0,
                'position' => $item['position'] ?? 0,
                'aio_status' => 'Processing',
                'has_aio' => false,
                'client_mentioned' => 'Processing',
                'processed' => false,
                'index' => $index,
            ];

            // Add ALL to queue list

            ProcessGscKeywordJob::dispatch(
                $keyword,
                $index,
                $searchTerms,
                $sessionId
            )->onQueue('gsc_aio')->delay(now()->addSeconds(rand(1, 5))); // Stagger jobs
        }

        // Store session data for frontend polling
        // session()->put('gsc_processing_status', [
        //     'session_id' => $sessionId,
        //     'total_keywords' => count($processedData),
        //     'queued_count' => count($keywordsToProcess),
        //     'started_at' => now()->toDateTimeString()
        // ]);

        return view('gsc-data-display', compact('processedData', 'sessionId'));
    }

    /**
     * Check processing status
     */
    public function checkProcessingStatus(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|string',
                'indexes' => 'nullable|array'
            ]);

            $sessionId = $request->input('session_id');
            $indexes = $request->input('indexes', []);

            $results = [];

            if (!empty($indexes)) {
                // Check specific indexes
                foreach ($indexes as $index) {
                    $cacheKey = "gsc_aio_result_{$sessionId}_{$index}";
                    $result = cache()->get($cacheKey);

                    if ($result) {
                        $results[$index] = $result;
                    } else {
                        $results[$index] = [
                            'index' => $index,
                            'processed' => false,
                            'aio_status' => 'Processing',
                            'client_mentioned' => 'Processing'
                        ];
                    }
                }
            } else {
                // Check all indexes (for initial load)
                $status = session()->get('gsc_processing_status', []);
                if ($status && $status['session_id'] === $sessionId) {
                    $results = ['status' => $status];
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking processing status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync single keyword (manual sync)
     */
    public function syncGscAio(Request $request)
    {
        try {
            $request->validate([
                'keyword' => 'required|string',
                'index' => 'required|integer'
            ]);

            $keyword = $request->input('keyword');
            $index = $request->input('index');
            $search_in_aio = "aayna, clinic, aayna clinic";
            $searchTerms = array_map('trim', explode(',', $search_in_aio));
            $sessionId = session()->get('gsc_session_id', Str::random(20));

            $cacheKey = "gsc_aio_result_{$sessionId}_{$index}";
            cache()->forget($cacheKey);
            // Set status to processing

            $processingResult = [
                'index' => $index,
                'keyword' => $keyword,
                'aio_status' => 'Processing',
                'has_aio' => false,
                'client_mentioned' => 'Processing',
                'processed' => false,
                'processed_at' => now()->toDateTimeString(),
            ];
            cache()->put($cacheKey, $processingResult, now()->addHours(24));

            // Dispatch job for immediate processing
            ProcessGscKeywordJob::dispatch(
                $keyword,
                $index,
                $searchTerms,
                $sessionId
            )->onQueue('gsc_aio')->delay(now()->addSeconds(1));

            return response()->json([
                'success' => true,
                'message' => 'Keyword queued for processing',
                'data' => [
                    'index' => $index,
                    'keyword' => $keyword,
                    'session_id' => $sessionId
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error queuing sync for keyword {$keyword}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cached results for specific indexes
     */
    public function getCachedResults(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|string',
                'indexes' => 'required|array'
            ]);

            $sessionId = $request->input('session_id');
            $indexes = $request->input('indexes');

            $results = [];

            foreach ($indexes as $index) {
                // Fix any inconsistent data first
                $cacheKey = "gsc_aio_result_{$sessionId}_{$index}";
                $result = cache()->get($cacheKey);

                if ($result) {
                    // Fix inconsistent data
                    if (($result['aio_status'] ?? 'No') === 'No') {
                        $result['client_mentioned'] = 'No';
                    }
                    $results[$index] = $result;
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting cached results: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function keywordStoreMore(Request $request)
    {
        $url = $request->domain_name;
        $keyword = $request->master_keyword;
        $remaininglimit = (int) $request->remaining_limit;
        $currentextracted = (int) $request->current_extracted;
        Log::info($remaininglimit." + ".$currentextracted);
        $info = [
            'client_property_id' => $request->client_property_id,
            'domainmanagement_id' => $request->domainmanagement_id,
            'keyword' => $request->master_keyword ?? $url, // Use master_keyword if available, otherwise use URL
            'domain_name' => $request->domain_name,
            'master_keyword' => $request->master_keyword ?? null,
        ];

        $keyword_request_id = session('keyword_request_id');
        $sessionId = session()->getId();

        // Fetch additional keywords (use SAME source as keywordStore)
        if (!empty($url) && empty($keyword)) {
            $additionalKeywords = $this->enrichWithGscData(
                null,
                $url,
                $request->date_from ?? null,
                $request->date_to ?? null,
                $remaininglimit + $currentextracted
            );
        } else {
            $additionalKeywords = $this->kpService->searchKeywords(
                $keyword,
                $remaininglimit + $currentextracted,
                null,
                null,
                $request->client_property_id
            );
        }
        $storedKeywords = [];
        foreach ($additionalKeywords as $keywordData) {
            // Check if the keyword already exists for this request
            $existingKeywordPlanner = KeywordPlanner::where('keyword_p', $keywordData['keyword'])
                ->where('keyword_request_id', $keyword_request_id)
                ->first();
            
            if($existingKeywordPlanner){
            // Update existing record
                $existingKeywordPlanner->update([
                    'monthlysearch_p' => $keywordData['avg_monthly_searches'] ?? $existingKeywordPlanner->monthlysearch_p,
                    'competition_p' => $keywordData['competition'] ?? $existingKeywordPlanner->competition_p,
                    'low_bid_p' => $keywordData['low_top_of_page_bid'] ?? $existingKeywordPlanner->low_bid_p,
                    'high_bid_p' => $keywordData['high_top_of_page_bid'] ?? $existingKeywordPlanner->high_bid_p,
                    'monthlysearchvolume_p' => null, 
                    'clicks_p' => $keywordData['clicks'] ?? $existingKeywordPlanner->clicks_p,
                    'ctr_p' => $keywordData['ctr'] ?? $existingKeywordPlanner->ctr_p,
                    'impressions_p' => $keywordData['impressions'] ?? $existingKeywordPlanner->impressions_p,
                    'position_p' => $keywordData['position'] ?? $existingKeywordPlanner->position_p,
                ]);
            }else{

                KeywordPlanner::create([
                    'domainmanagement_id' => $info['domainmanagement_id'],
                    'client_property_id' => $info['client_property_id'],
                    'keyword_request_id' => $keyword_request_id,
                    'keyword_p' => $keywordData['keyword'],
                    'monthlysearch_p' => $keywordData['avg_monthly_searches'] ?? null,
                    'competition_p' => $keywordData['competition'] ?? null,
                    'low_bid_p' => $keywordData['low_top_of_page_bid'] ?? null,
                    'high_bid_p' => $keywordData['high_top_of_page_bid'] ?? null,
                    'monthlysearchvolume_p' => null,
                    'clicks_p' => $keywordData['clicks'] ?? null,
                    'ctr_p' => $keywordData['ctr'] ?? null,
                    'impressions_p' => $keywordData['impressions'] ?? null,
                    'position_p' => $keywordData['position'] ?? null,
                ]);
            }
                
                $storedKeywords[] = $existingKeywordPlanner;
        }
        // dd($additionalKeywords);
        Log::info("additionalKeywords: ".count($additionalKeywords));

        // Apply filters
        $filters = $request->only([
            'min_searches', 'max_searches', 'competition',
            'min_bid', 'max_bid', 'date_from', 'date_to',
            'min_clicks', 'max_clicks', 'min_ctr', 'max_ctr',
            'min_impressions', 'max_impressions'
        ]);

        if (!empty(array_filter($filters))) {
            $additionalKeywords = $this->applyFilters($additionalKeywords, $filters);
        }

        // Already extracted keywords
        $alreadyExtracted = session('extracted_keywords', []);
        Log::info("alreadyExtracted: ".count($alreadyExtracted));

        $newKeywords = [];
        $newKeywordTexts = [];

        foreach ($additionalKeywords as $keywordData) {
            $keywordText = $keywordData['keyword'] ?? $keywordData['query'] ?? '';
            // dd($keywordText, !in_array($keywordText, $alreadyExtracted), $keywordData);
            if ($keywordText && !in_array($keywordText, $alreadyExtracted)) {
                $newKeywords[] = $keywordData;
                $newKeywordTexts[] = $keywordText;
            }

            if (count($newKeywords) >= $remaininglimit) {
                break;
            }
        }
        // dd($additionalKeywords, $newKeywordTexts, $alreadyExtracted);

        // Update extracted keywords session
        Log::info("newKeywordTexts: ".count($newKeywordTexts));
        $allExtracted = array_merge($alreadyExtracted, $newKeywordTexts);
        Log::info("allExtracted: ".count($allExtracted));
        session(['extracted_keywords' => $allExtracted]);
        session(['new_keywords' => $newKeywords]);
        // dd($newKeywords, $allExtracted, $alreadyExtracted, $newKeywordTexts);
        /**
         * AI Overview status (same logic as keywordStore)
         */
        $ai_status = false;
        if ($keyword_request_id) {
            $planner = KeywordPlanner::where('keyword_request_id', $keyword_request_id)->first();
            if ($planner) {
                $ai_status = AiOverview::where('keyword_planner_id', $planner->id)->exists();
            }
        }

        /**
         * Initialize processing cache for new keywords
         */
        foreach ($newKeywords as $index => $keyword_item) {
            $keyword = $keyword_item['keyword'] ?? $keyword_item['query'];
            $offsetIndex = 1000 + $index;
            $cacheKey = "keyword_status_{$sessionId}_{$offsetIndex}";
            Log::info("Initializing cache for keyword: {$keyword} with key: {$cacheKey}");
            cache()->put($cacheKey, [
                'keyword' => $keyword,
                'search_api_status' => 'Processing',
                'aio_status' => 'Processing',
                'client_mentioned_status' => 'Processing',
                'processed' => false,
                'index' => $offsetIndex,
                'original_index' => $index
            ], now()->addHours(24));

            
            // Dispatch the job
            ProcessKeywordStatusJob::dispatch(
                $keyword,
                $keyword_request_id,
                $request->client_property_id,
                $request->domainmanagement_id,
                $sessionId,
                $offsetIndex,
                null
            );
        }

        $totalExtracted = count($allExtracted);
        $maxLimit = 1000;

        $html = view('keyword-analysis.keyword-remaining-results', [
            'keywords' => $newKeywords,
            'domain_name' => $url,
            'ai_status' => $ai_status,
            'total_count' => count($newKeywords),
            'original_count' => count($alreadyExtracted),
            'new_count' => count($newKeywords),
            'keyword_request_id' => $keyword_request_id,
            'client_property_id' => $request->client_property_id,
            'domainmanagement_id' => $request->domainmanagement_id,
            'is_full_results' => ($totalExtracted >= $maxLimit),
            'remaining_keywords' => max(0, $maxLimit - $totalExtracted),
            'session_id' => $sessionId, // Pass session ID to view
            'index_offset' => 1000 // Pass offset to view
        ])->render();

        return response()->json([
            'html' => $html,
            'new_keywords' => count($newKeywords),
            'filtered_out' => count($additionalKeywords) - count($newKeywords),
            'total_extracted' => $totalExtracted,
            'session_id' => $sessionId
        ]);
    }

    public function keywordStoreMore1(Request $request)
    {
        // Get the original parameters
        $url = $request->domain_name;
        $keyword = $request->master_keyword;
        $remaininglimit = $request->remaining_limit; // How many more to fetch
        $currentextracted = $request->current_extracted; // How many more to fetch
        Log::info($remaininglimit." + ".$currentextracted);

        // Fetch additional keywords from keyword planner
        if (!empty($url) && empty($keyword)) {
            // For domain-based searches
            $additionalKeywords = $this->getEnrichedSearchAnalytics($url, $remaininglimit + $currentextracted, $request->date_from, $request->date_to);
        } else {
            // For keyword-based searches
            $additionalKeywords = $this->kpService->searchKeywords($keyword, $remaininglimit + $currentextracted, $request->date_from, $request->date_to, $request->client_property_id);
        }

        // Apply filters if provided
        $filters = $request->only(['min_searches', 'max_searches', 'competition', 'min_bid', 'max_bid', 'date_from', 'date_to', 'min_clicks', 'max_clicks', 'min_ctr', 'max_ctr', 'min_impressions', 'max_impressions']);

        if (!empty($filters)) {
            $additionalKeywords = $this->applyFilters($additionalKeywords, $filters);
        }

        // Get already extracted keywords from session
        $alreadyExtracted = session('extracted_keywords', []);

        // Filter out keywords that have already been extracted
        $newKeywords = [];
        $newKeywordTexts = [];

        foreach ($additionalKeywords as $keywordData) {
            $keywordText = $keywordData['keyword'] ?? $keywordData['query'] ?? '';

            // Only include if NOT already extracted
            if (!in_array($keywordText, $alreadyExtracted)) {
                $newKeywords[] = $keywordData;
                $newKeywordTexts[] = $keywordText;
            }
        }
        Log::info("newKeywordTexts: ".count($newKeywordTexts));

        $allExtracted = array_merge($alreadyExtracted, $newKeywordTexts);
        Log::info("allExtracted: ".count($allExtracted));

        // dd($alreadyExtracted, $newKeywordTexts, $allExtracted, $newKeywords);
        session(['extracted_keywords' => $allExtracted]);

        // Store new keywords separately for display
        session(['new_keywords' => $newKeywords]);
        Log::info("newKeywords: ".count($newKeywords));


        // Get info from session
        $info = session('temp_info', []);
        $keyword_request_id = session('keyword_request_id');
        $html = view('keyword-analysis.keyword-remaining-results', [
            'keywords' => $newKeywords,
            'domain_name' => $url,
            'ai_status' => false, // You might need to check this
            'total_count' => count($newKeywords),
            'original_count' => count($alreadyExtracted),
            'new_count' => count($newKeywords),
            'keyword_request_id' => $keyword_request_id,
            'client_property_id' => $request->client_property_id,
            'domainmanagement_id' => $request->domainmanagement_id,
            'is_full_results' => ((count($alreadyExtracted) + count($newKeywords)) >= 1000),
            'remaining_keywords' => max(0, 1000 - (count($alreadyExtracted) + count($newKeywords))),
        ])->render();

        return response()->json([
            'html' => $html,
            'new_keywords' => count($newKeywords),
            'filtered_out' => count($additionalKeywords) - count($newKeywords),
            'total_extracted' => count($allExtracted),
        ]);
    }
    public function keywordmediansave(Request $request)
    {
 
        $rows = $request->input('rows');
 
        if (!$rows || !is_array($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'No data received'
            ]);
        }
 
        try {
            $newMedianName = $request->median_name;
            $medianInfoId = Cookie::get('median_info_id');
 
            // Find the most recent "Unsaved Bucket N" MedianInfo record for this
            // client/domain/request — this is the one we are about to officially name.
            $medianInfo = MedianInfo::find($medianInfoId);

            if (!$medianInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'MedianInfo not found.',
                    'message' => 'No unsaved bucket found to rename. Please trigger an auto-save first.',
                ]);
            }
 
            // The MedianFetch rows link back via median_name = median_info.id (as string).
            // Renaming MedianInfo does NOT require touching MedianFetch.median_name at all —
            // the ID stays the same, so the link remains intact.
            $medianInfo->update(['median_name' => $newMedianName]);

            foreach ($rows as $row) {
 
                $keywordplanner = KeywordPlanner::where([
                    ['keyword_p', $row['keyword']],
                    ['keyword_request_id', $request->keyword_request_id],
                    ['client_property_id', $request->client_property_id],
                    ['domainmanagement_id', $request->domainmanagement_id]
                ])->first();
 
                if (!$keywordplanner) continue;
 
                // Reset bucket for all MedianFetch rows belonging to this MedianInfo
                MedianFetch::where([
                    'client_property_id'  => $request->client_property_id,
                    'domainmanagement_id' => $request->domainmanagement_id,
                    'keyword_request_id'  => $request->keyword_request_id,
                    'keyword_p'           => $keywordplanner->id,
                    'median_name'         => (string) $medianInfoId,   // matches via median_info.id
                ])->update(['bucket' => 0]);
 
                // Mark the selected keyword as bucket=1
                MedianFetch::where([
                    'client_property_id'  => $request->client_property_id,
                    'domainmanagement_id' => $request->domainmanagement_id,
                    'keyword_request_id'  => $request->keyword_request_id,
                    'keyword_p'           => $keywordplanner->id,
                    'median_name'         => (string) $medianInfoId,
                ])->update(['bucket' => 1]);
            }
 
            // No sweep needed: MedianFetch.median_name stores the MedianInfo.id,
            // which never changes — only MedianInfo.median_name was renamed above.
 
            return response()->json([
                'success'        => true,
                'median_name'    => $newMedianName,
                'median_info_id' => (int) $medianInfoId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function autokeywordmediansave(Request $request)
    {
        $rows = $request->input('rows');
        if (!$rows || !is_array($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'No data received'
            ]);
        }
 
        try {
            // Count existing "Unsaved Bucket N" records for this client/domain/request
            // and create the next one in the sequence (N+1).
            // firstOrCreate is used so that a retry with the same name never duplicates.
            $existingCount = MedianInfo::where([
                'client_property_id'  => $request->client_property_id,
                'domainmanagement_id' => $request->domainmanagement_id,
                'keyword_request_id'  => $request->keyword_request_id,
            ])->where('median_name', 'LIKE', 'Unsaved Bucket%')->count();
 
            $tempName = 'Unsaved Bucket ' . ($existingCount + 1);
 
            // firstOrCreate: if this exact temp name already exists (e.g. retry), reuse it;
            // otherwise create a fresh MedianInfo row — never duplicate, never overwrite.
            $median_info = MedianInfo::firstOrCreate([
                'client_property_id'  => $request->client_property_id,
                'domainmanagement_id' => $request->domainmanagement_id,
                'keyword_request_id'  => $request->keyword_request_id,
                'median_name'         => $tempName,
            ], [
                'date_from' => $request->date_from,
                'date_to'   => $request->date_to,
            ]);
 
            // Store median_info.id in MedianFetch.median_name to act as the FK link.
            // This way renaming MedianInfo later never requires touching these rows.
            $medianInfoId = (string) $median_info->id;
 
            foreach ($rows as $row) {
                $keywordplanner = KeywordPlanner::where([
                    ['keyword_p', $row['keyword']],
                    ['keyword_request_id', $request->keyword_request_id],
                    ['client_property_id', $request->client_property_id],
                    ['domainmanagement_id', $request->domainmanagement_id]
                ])->first();
 
                if (!$keywordplanner) {
                    Log::warning("autokeywordmediansave: keyword_planner not found for keyword: " . ($row['keyword'] ?? 'N/A'));
                    continue;
                }
 
                MedianFetch::create([
                    'client_property_id'  => $request->client_property_id,
                    'domainmanagement_id' => $request->domainmanagement_id,
                    'keyword_request_id'  => $request->keyword_request_id,
                    'keyword_p'           => $keywordplanner->id,
                    'median_name'     => $medianInfoId,
                    'date_from'       => $request->date_from,
                    'date_to'         => $request->date_to,
                    'bucket'          => 0,
                    'monthlysearch_p' => $row['monthly_search'] ?? 0,
                    'competition_p'   => $row['competition'] ?? null,
                    'low_bid_p'       => $row['low_bid'] ?? 0,
                    'high_bid_p'      => $row['high_bid'] ?? 0,
                    'clicks_p'        => $row['clicks'] ?? 0,
                    'ctr_p'           => $row['ctr'] ?? 0,
                    'impressions_p'   => $row['impressions'] ?? 0,
                    'position_p'      => $row['position'] ?? 0,
                ]);
            }
 
            return response()->json([
                'success'        => true,
                'temp_name'      => $tempName,
                'median_info_id' => $median_info->id,
            ])->cookie('median_info_id', $median_info->id, 1440);;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }



    public function getEnrichedSearchAnalytics($propertyUrl, $limit, $startDate = null, $endDate = null)
    {
        try {
            // 1. Get GSC Performance Data

            if ($startDate === null && $endDate == null) {
                $gscKeywords = $this->gscService->getKeywordsByClicks($propertyUrl, $limit);
            } else {
                $gscKeywords = $this->gscService->getKeywordsByClicks($propertyUrl, $limit, $startDate, $endDate);
            }

            // Extract just the keywords from GSC results
            $keywords = array_map(function ($item) {
                return $item['query'] ?? $item['keyword'];
            }, $gscKeywords);

            // 2. Get Keyword Planner Data in batches
            // $batchSize = 100; // Google Ads API supports up to 100 keywords per request
            // $keywordBatches = array_chunk($keywords, $batchSize);

            // Store keyword planner data
            $keywordPlannerData = [];

            // Process batch of keywords
            if ($startDate === null && $endDate == null) {
                $keywords = $this->kpService->getBulkKeywordData($propertyUrl, $keywords);
            } else {
                $keywords = $this->kpService->getBulkKeywordData($propertyUrl, $keywords, 1, $startDate, $endDate);
            }
            // dd($keywords);
            // Merge results
            $keywordPlannerData = array_merge($keywordPlannerData, $keywords);

            // 3. Combine GSC data with Keyword Planner data
            $enrichedResults = [];

            foreach ($gscKeywords as $gscKeyword) {
                $seedKeyword = $gscKeyword['query'] ?? $gscKeyword['keyword'];

                // Find matching keyword planner data
                $kpData = $keywordPlannerData[$seedKeyword] ?? null;

                if ($kpData) {
                    $enrichedResults[] = [
                        // Data from GSC
                        'keyword' => $seedKeyword,
                        'clicks' => $gscKeyword['clicks'],
                        'impressions' => $gscKeyword['impressions'],
                        'ctr' => round($gscKeyword['ctr'], 2),
                        'position' => round($gscKeyword['position'], 2),
                        // Enriched from Keyword Planner
                        'avg_monthly_searches' => $kpData['avg_monthly_searches'] ?? 0,
                        'competition' => $kpData['competition'] ?? 'UNKNOWN',
                        'low_top_of_page_bid_micros' => $kpData['low_top_of_page_bid_micros'] ?? 0,
                        'high_top_of_page_bid_micros' => $kpData['high_top_of_page_bid_micros'] ?? 0,
                        'low_top_of_page_bid' => $kpData['low_top_of_page_bid'] ?? 0,
                        'high_top_of_page_bid' => $kpData['high_top_of_page_bid'] ?? 0,
                        'competition_index' => $kpData['competition_index'] ?? 0,
                    ];
                } else {
                    // If no keyword planner data found, just include GSC data
                    $enrichedResults[] = [
                        'keyword' => $seedKeyword,
                        'clicks' => $gscKeyword['clicks'],
                        'impressions' => $gscKeyword['impressions'],
                        'ctr' => round($gscKeyword['ctr'], 2),
                        'position' => round($gscKeyword['position'], 2),
                        'avg_monthly_searches' => 0,
                        'competition' => 'UNKNOWN',
                        'low_top_of_page_bid_micros' => 0,
                        'high_top_of_page_bid_micros' => 0,
                        'low_top_of_page_bid' => 0,
                        'high_top_of_page_bid' => 0,
                        'competition_index' => 0,
                    ];
                }
            }

            return $enrichedResults;
        } catch (Exception $e) {
            throw new Exception('Error fetching enriched keyword data: ' . $e->getMessage());
        }
    }
    public function hello()
    {
        $customerId = 4652169644;
        $managerId = 8177415982;
        $kpService = new KeywordPlannerService($customerId, $managerId);
        return $kpService->searchKeywordsByUrl('https://www.aaynaclinic.com', $customerId, $managerId);
    }
    
    public function checkKeywordStatus(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $indexes = $request->input('indexes', []);
            
            $results = [];
            
            foreach ($indexes as $index) {
                $cacheKey = "keyword_status_{$sessionId}_{$index}";
                $status = cache()->get($cacheKey);
                if ($status) {
                    $results[$index] = $status;
                } else {
                    $results[$index] = [
                        'search_api_status' => 'Processing',
                        'aio_status' => 'Processing',
                        'client_mentioned_status' => 'Processing',
                        'processed' => false,
                        'keyword_planner_id' => null,
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error checking keyword status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
public function fetchKeywordPlannerKeywordsdata(Request $request)
{
    try {
        $request->validate([
            'domain_name' => 'required|string',
            'limit' => 'required|integer|min:1|max:100',
        ]);
        
        // $customerId = DomainManagementModel::where('id', $request->domainmanagement_id)->value('customer_id');
        // $managerId = DomainManagementModel::where('id', $request->domainmanagement_id)->value('manager_id');

        // Get keyword planner service
        // $keywordPlannerService = new KeywordPlannerService($customerId, $managerId);
        
        // Fetch keywords from keyword planner
        $keywords = $this->kpService->searchKeywordsByUrl(
            $request->domain_name,
            $request->limit,
            $request->date_from ?? now()->subMonths(12)->format('Y-m-d'),
            $request->date_to ?? now()->format('Y-m-d')
        );

        if (isset($keywords['error'])) {
            return response()->json([
                'success' => false,
                'message' => $keywords['error']
            ]);
        }

        // Store keywords temporarily (you might want to save them to a temporary table)
        $processedKeywords = [];
        foreach ($keywords as $keywordData) {
            $keywordplanner = KeywordPlanner::where([
                ['keyword_p', $keywordData['keyword']],
                ['keyword_request_id', $request->keyword_request_id],
                ['client_property_id', $request->client_property_id],
                ['domainmanagement_id', $request->domainmanagement_id]
            ])->first();
            $processedKeywords[] = [
                'id' => $keywordplanner ? $keywordplanner->id : null,
                'keyword' => $keywordData['keyword'] ?? '',
                'avg_monthly_searches' => $keywordData['avg_monthly_searches'] ?? 0,
                'competition' => $keywordData['competition'] ?? '',
                'low_top_of_page_bid' => $keywordData['low_top_of_page_bid'] ?? 0,
                'high_top_of_page_bid' => $keywordData['high_top_of_page_bid'] ?? 0,
                'clicks' => $keywordData['clicks'] ?? 0,
                'ctr' => $keywordData['ctr'] ?? 0,
                'impressions' => $keywordData['impressions'] ?? 0,
                'position' => $keywordData['position'] ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'keywords' => $processedKeywords,
            'keyword_request_id' => $request->keyword_request_id,
            'message' => 'Keywords fetched successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
public function processKeywordPlannerKeywords(Request $request)
{
    try {
        $request->validate([
            'keywords' => 'required',
            'keyword_request_id' => 'required',
            'client_property_id' => 'required',
            'domainmanagement_id' => 'required'
        ]);

        $clusterRequestId = null; // Adjust as needed
        // dd($request->all(), $request->keywords);

        foreach ($request->keywords as $keywordData) {
            $keywordPlanner = KeywordPlanner::create([
                'keyword_request_id' => $request->keyword_request_id,
                'cluster_request_id' => $clusterRequestId,
                'client_property_id' => $request->client_property_id,
                'domainmanagement_id' => $request->domainmanagement_id,
                'keyword_p' => $keywordData['keyword'],
                'monthlysearch_p' => $keywordData['avg_monthly_searches'] ?? 0,
                'competition_p' => $keywordData['competition'] ?? '',
                'low_bid_p' => $keywordData['low_top_of_page_bid'] ?? 0,
                'high_bid_p' => $keywordData['high_top_of_page_bid'] ?? 0,
                'clicks_p' => $keywordData['clicks'] ?? 0,
                'ctr_p' => $keywordData['ctr'] ?? 0,
                'impressions_p' => $keywordData['impressions'] ?? 0,
                'position_p' => $keywordData['position'] ?? 0,
                'type' => 'keyword_planner',
                'ai_status' => '0',
            ]);

            // Dispatch job for AI Overview processing
            ProcessKeywordJob::dispatch(
                $keywordData['keyword'],
                $keywordPlanner->id,
                $request->keyword_request_id,
                $request->client_property_id,
                $request->domainmanagement_id,
                $clusterRequestId,
                false // Not a GSC keyword
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Keywords sent for processing'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

public function checkKeywordPlannerStatus(Request $request)
{
    try {
        $request->validate([
            'keyword_request_id' => 'required'
        ]);

        $keywordPlanners = KeywordPlanner::where('keyword_request_id', $request->keyword_request_id)
            ->where('type', 'keyword_planner')
            ->get();

        $statuses = [];
        $allProcessed = true;

        foreach ($keywordPlanners as $index => $keywordPlanner) {
            $status = [
                'search_api_status' => 'Done', // Assuming Search API is done when saved
                'aio_status' => $keywordPlanner->ai_status == '1' ? 'Yes' : ($keywordPlanner->ai_status == '0' ? 'No' : 'Processing'),
                'client_mentioned_status' => 'Processing' // Adjust based on your logic
            ];

            $statuses[$index] = $status;

            if ($keywordPlanner->ai_status == '0' || $status['client_mentioned_status'] == 'Processing') {
                $allProcessed = false;
            }
        }

        return response()->json([
            'success' => true,
            'statuses' => $statuses,
            'all_processed' => $allProcessed
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
    public function aio_domain_api(Request $request)
{
    try {
        $domain = $request->input('domain');
        $keyword = $request->input('keyword');
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        
        // Validate that either domain or keyword is provided
        if (!$domain && !$keyword) {
            return response()->json([
                'success' => false,
                'message' => 'Either domain or keyword parameter is required'
            ], 400);
        }
                
        $client_property_id = $domain ? Client_propertiesModel::where('domain','LIKE', "%".$request->input('domain')."%")->value('id') : $request->input('client_property_id');
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Generate a unique session ID for this request
        $sessionId = uniqid('aio_domain_', true);
        
        $keywords = [];
        $totalKeywords = 0;
        $sourceType = ($domain && !$keyword) ? 'domain' : 'keyword';
        
        if ($domain && !$keyword) {
            // Fetch keywords from GSC for domain
            Log::info("Fetching GSC keywords for domain: {$domain}");
            $gscKeywords = $this->gscService->getKeywordsByClicks($domain, $limit + $offset);
            
            if (empty($gscKeywords)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No keywords found in Google Search Console for this domain'
                ], 404);
            }

            // Get total count for pagination
            $totalKeywords = count($gscKeywords);
            
            // Extract keywords with UTF-8 cleaning
            $allKeywords = array_map(function($item) {
                $keyword = $item['query'] ?? $item['keyword'] ?? '';
                return $this->cleanUtf8String($keyword);
            }, $gscKeywords);
            
            // Apply pagination - get only the keywords for current page
            $keywords = array_slice($allKeywords, $offset, $limit);
            
        } else {
            // Fetch related keywords from Keyword Planner for the given keyword
            Log::info("Fetching related keywords for keyword: {$keyword}");
            
            // Get related keywords from Keyword Planner service
            // dd($client_property_id);
            $relatedKeywords = $this->kpService->searchKeywords($keyword, $limit + $offset, null, null, ['2840'], '1000', $client_property_id);
            if (empty($relatedKeywords)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No related keywords found for the provided keyword'
                ], 404);
            }

            // Get total count for pagination
            $totalKeywords = count($relatedKeywords);
            
            // Extract keywords with UTF-8 cleaning
            $allKeywords = array_map(function($item) {
                $keyword = $item['keyword'] ?? '';
                return $this->cleanUtf8String($keyword);
            }, $relatedKeywords);
            
            // Apply pagination - get only the keywords for current page
            $keywords = array_slice($allKeywords, $offset, $limit);
        }
        
        // If no keywords for this page
        if (empty($keywords)) {
            return response()->json([
                'success' => false,
                'message' => "No keywords found for page {$page}",
                'source_type' => $sourceType,
                'source_value' => $domain ?: $keyword,
                'pagination' => $this->generatePagination($domain, $keyword, $page, $limit, $totalKeywords)
            ], 404);
        }

        Log::info("Processing page {$page} with " . count($keywords) . " keywords (offset: {$offset}) from {$sourceType}: " . ($domain ?: $keyword));

        // Initialize results array
        $results = [];
        foreach ($keywords as $index => $kw) {
            $globalIndex = (int)$offset + (int)$index;
            $results[$index] = [
                'serial_no' => $globalIndex,
                'keyword' => $this->cleanUtf8String($kw),
                'search_api_status' => 'Processing',
                'aio_status' => 'Processing',
                'client_mentioned_status' => 'Processing',
            ];
        }

        // Start processing with timeout
        $startTime = time();
        $processedCount = 0;
        $totalKeywordsOnPage = count($keywords);

        // Process keywords with timeout
        foreach ($keywords as $index => $kw) {
            try {
                Log::info("Processing keyword {$index} on page {$page}: {$kw}");
                
                // Fetch search results
                $searchJson = GeneralHelper::getSearchResult($kw);
                $searchData = json_decode($searchJson, true);
                
                if (!$searchData) {
                    $results[$index]['search_api_status'] = 'Error';
                    $results[$index]['aio_status'] = 'Error';
                    $results[$index]['client_mentioned_status'] = 'Error';
                    $results[$index]['error'] = 'Failed to fetch search results';
                    $processedCount++;
                    continue;
                }

                // Clean search data
                $searchData = $this->cleanUtf8Array($searchData);

                // Check for AI Overview
                $hasAio = false;
                $aiOverviewData = null;
                
                if (isset($searchData['ai_overview'])) {
                    if (!isset($searchData['ai_overview']['page_token'])) {
                        $aiOverviewData = $searchData['ai_overview'];
                        $hasAio = !empty($aiOverviewData['markdown']) || !empty($aiOverviewData['text_blocks']);
                    } else {
                        // Fetch full AIO data if there's a page token
                        $aioJson = GeneralHelper::getaioResult($searchData['ai_overview']['page_token']);
                        $aiOverviewData = json_decode($aioJson, true);
                        $hasAio = !empty($aiOverviewData['markdown']) || !empty($aiOverviewData['text_blocks']);
                    }
                    
                    // Clean AI overview data
                    if ($aiOverviewData) {
                        $aiOverviewData = $this->cleanUtf8Array($aiOverviewData);
                    }
                }

                // Check if client is mentioned in AI Overview
                $clientMentioned = false;
                if ($hasAio && $aiOverviewData && $domain) {
                    // Only check client mention if domain is provided
                    // Extract domain from URL for comparison
                    $parsedDomain = parse_url($domain, PHP_URL_HOST);
                    if (!$parsedDomain) {
                        $parsedDomain = $domain;
                    }
                    
                    // Check if domain appears in AI Overview content
                    $aioContent = json_encode($aiOverviewData);
                    $clientMentioned = stripos($aioContent, $parsedDomain) !== false;
                    
                    // Also check in markdown if available
                    if (!$clientMentioned && isset($aiOverviewData['markdown'])) {
                        $clientMentioned = stripos($aiOverviewData['markdown'], $parsedDomain) !== false;
                    }
                    
                    // Check in text blocks if available
                    if (!$clientMentioned && isset($aiOverviewData['text_blocks']) && is_array($aiOverviewData['text_blocks'])) {
                        foreach ($aiOverviewData['text_blocks'] as $block) {
                            if (is_string($block) && stripos($block, $parsedDomain) !== false) {
                                $clientMentioned = true;
                                break;
                            }
                        }
                    }
                }

                // Update results
                $results[$index]['search_api_status'] = 'Done';
                $results[$index]['aio_status'] = $hasAio ? 'Yes' : 'No';
                $results[$index]['client_mentioned_status'] = $domain ? ($clientMentioned ? 'Yes' : 'No') : 'N/A';
                $results[$index]['has_ai_overview'] = $hasAio;
                $results[$index]['client_mentioned'] = $clientMentioned;
                
                // Include a preview of AI Overview if available
                if ($hasAio && $aiOverviewData) {
                    $results[$index]['ai_preview'] = [
                        'has_markdown' => !empty($aiOverviewData['markdown']),
                        'text_blocks_count' => isset($aiOverviewData['text_blocks']) ? count($aiOverviewData['text_blocks']) : 0,
                        'preview' => isset($aiOverviewData['markdown']) 
                            ? $this->cleanUtf8String(substr($aiOverviewData['markdown'], 0, 200) . '...') 
                            : (isset($aiOverviewData['text_blocks'][0]) 
                                ? $this->cleanUtf8String(substr($aiOverviewData['text_blocks'][0], 0, 200) . '...') 
                                : null)
                    ];
                }

                $processedCount++;
                
                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 second delay between requests

            } catch (\Exception $e) {
                Log::error("Error processing keyword {$kw}: " . $e->getMessage());
                $results[$index]['search_api_status'] = 'Error';
                $results[$index]['aio_status'] = 'Error';
                $results[$index]['client_mentioned_status'] = 'Error';
                $results[$index]['error'] = $this->cleanUtf8String($e->getMessage());
                $processedCount++;
            }
        }
        
        $aioCount = count(array_filter($results, function($item) {
            return ($item['aio_status'] ?? '') === 'Yes';
        }));
        
        $clientMentionedCount = count(array_filter($results, function($item) {
            return ($item['client_mentioned_status'] ?? '') === 'Yes';
        }));

        // Generate pagination URLs
        $pagination = $this->generatePagination($domain, $keyword, $page, $limit, $totalKeywords);

        // Clean the entire response data before returning
        $responseData = [
            'success' => true,
            'source_type' => $sourceType,
            'source_value' => $this->cleanUtf8String($domain ?: $keyword),
            'domain' => $domain ? $this->cleanUtf8String($domain) : null,
            'keyword' => $keyword ? $this->cleanUtf8String($keyword) : null,
            'page' => $page,
            'limit' => $limit,
            'total_keywords_available' => $totalKeywords,
            'aio_found_on_page' => $aioCount,
            'client_mentioned_on_page' => $clientMentionedCount,
            'results' => $this->cleanUtf8Array($results),
            'summary' => [
                'keywords_with_aio' => $aioCount,
                'keywords_with_client' => $clientMentionedCount,
            ],
            'pagination' => $pagination
        ];

        return response()->json($responseData);

    } catch (\Exception $e) {
        Log::error('AIO Domain API Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $this->cleanUtf8String($e->getMessage())
        ], 500);
    }
}

// Add these helper methods to your controller class

/**
 * Clean UTF-8 string to remove malformed characters
 */
private function cleanUtf8String($string)
{
    if (!is_string($string)) {
        return $string;
    }
    
    // Remove invalid UTF-8 characters
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    
    // Alternatively, use this more aggressive cleaning if needed:
    // $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);
    
    // Remove control characters except for newlines and tabs
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);
    
    return $string;
}

/**
 * Recursively clean UTF-8 strings in an array
 */
private function cleanUtf8Array($data)
{
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = $this->cleanUtf8Array($value);
        }
        return $data;
    } elseif (is_string($data)) {
        return $this->cleanUtf8String($data);
    }
    return $data;
}

    /**
     * Generate pagination URLs
     */
    private function generatePagination($domain, $keyword, $currentPage, $limit, $totalKeywords)
    {
        $baseUrl = url('/api/aio_domain_api');
        
        // Build query parameters based on whether domain or keyword is provided
        $queryParams = [];
        if ($domain) {
            $queryParams['domain'] = $domain;
        } else {
            $queryParams['keyword'] = $keyword;
        }
        $queryParams['page'] = $currentPage;
        $queryParams['limit'] = $limit;
        
        $buildUrl = function($page) use ($baseUrl, $domain, $keyword, $limit) {
            $params = [];
            if ($domain) {
                $params['domain'] = $domain;
            } else {
                $params['keyword'] = $keyword;
            }
            $params['page'] = $page;
            $params['limit'] = $limit;
            
            return $baseUrl . '?' . http_build_query($params);
        };
        
        $pagination = [
            'current_page' => $currentPage,
        ];
        
        // Previous page URL
        if ($currentPage > 1) {
            $pagination['prev_page_url'] = $buildUrl($currentPage - 1);
        } else {
            $pagination['prev_page_url'] = null;
        }
        
        $pagination['next_page_url'] = $buildUrl($currentPage + 1);
        
        return $pagination;
    }

}
