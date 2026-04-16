<?php

namespace App\Services;

use App\Helpers\GeneralHelper;
use App\Models\Client_propertiesModel;
use DateTime;
use DateTimeZone;
use Exception;
use Google\Ads\GoogleAds\Lib\V21\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V21\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V21\GoogleAdsException;
use Google\Ads\GoogleAds\Util\V21\ResourceNames;
use Google\Ads\GoogleAds\V21\Enums\KeywordPlanNetworkEnum\KeywordPlanNetwork;
use Google\Ads\GoogleAds\V21\Services\GenerateKeywordIdeasRequest;
use Google\Ads\GoogleAds\V21\Services\KeywordAndUrlSeed;
use Google\Ads\GoogleAds\V21\Services\KeywordSeed;
use Google\Ads\GoogleAds\V21\Services\UrlSeed;
use Google\ApiCore\ApiException;
use Google\Ads\GoogleAds\V21\Common\HistoricalMetricsOptions;
use Google\Ads\GoogleAds\V21\Common\YearMonthRange;
use Google\Ads\GoogleAds\V21\Common\YearMonth;
use Google\Ads\GoogleAds\V21\Enums\MonthEnum\Month;
use Google\Ads\GoogleAds\V21\Enums\MonthOfYearEnum\MonthOfYear as MonthOfYearEnumMonthOfYear;
use Google\Ads\GoogleAds\V21\Services\GenerateKeywordHistoricalMetricsRequest;
use GPBMetadata\Google\Ads\GoogleAds\V21\Enums\MonthOfYear;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\FacadesLog;
use ReflectionClass;

class KeywordPlannerService
{
    private $googleAdsClient;
    private $customerId;
    private $developerToken;
    private $managerId;
    
    public function __construct($customerId=null,$managerId=null,$configPath = null)
    {
        if ($configPath === null) {
            $configPath = __DIR__ . '/google_ads_php.ini';
        }
        
        $this->managerId = $managerId;
        $this->customerId = $customerId;
        $config = parse_ini_file($configPath, true);
        // Load configuration from environment or config file
        $this->developerToken = $config['GOOGLE_ADS']['developerToken'];
        
        // Build the client
        $this->googleAdsClient = $this->buildClient();
    }
    
    private function buildClient()
    {
        try {
            // Path to your service account JSON file - adjust this path
            $serviceAccountJsonPath = storage_path('app/google-ads/service-account-key.json');
            
            if (!file_exists($serviceAccountJsonPath)) {
                throw new Exception("Service account file not found at: $serviceAccountJsonPath");
            }
            
            // Create OAuth2 token using service account
            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withJsonKeyFilePath($serviceAccountJsonPath)
                ->withScopes(['https://www.googleapis.com/auth/adwords'])
                ->build();
                
            // Build the Google Ads client
            return (new GoogleAdsClientBuilder())
                ->withDeveloperToken($this->developerToken)
                ->withLoginCustomerId($this->managerId)
                ->withOAuth2Credential($oAuth2Credential)
                ->build();
                
        } catch (\Exception $e) {
            throw new Exception("Failed to build Google Ads client: " . $e->getMessage());
        }
    }
    
    /**
     * Generate keyword ideas based on seed keywords
     */
    public function searchKeywords($keyword, $limit, $startDate = null, $endDate = null, $locationIds = ['2840'], $languageId = '1000', $client_property_id = null)
    {
        try {

            if($client_property_id !=null){
                $this->customerId = Client_propertiesModel::where('id', $client_property_id)->value('customer_id');
                $this->managerId = Client_propertiesModel::where('id', $client_property_id)->value('manager_id');
            }
            $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();
            
            $keywords = [$keyword];
            
            // Create geo target constants
            $geoTargetConstants = array_map(function ($locationId) {
                return ResourceNames::forGeoTargetConstant($locationId);
            }, $locationIds);
            
            if (!$startDate || !$endDate) {
                // For end date: last day of previous month (as you want)
                $endDateObj = new DateTime('last day of previous month', new DateTimeZone('UTC'));
                
                // For start date: first day of the month 11 months before end date
                $startDateObj = clone $endDateObj;
                $startDateObj->modify('first day of -11 months');
            } else {
                $startDateObj = new DateTime($startDate);
                $endDateObj = new DateTime($endDate);
            }
            
            // $startMonthName = $startDateObj->format('F'); // Full month name
            // $startMonthNumber = (int)$startDateObj->format('n'); // Month number (1-12)
            // $startYear = (int)$startDateObj->format('Y');

            // $endMonthName = $endDateObj->format('F'); // Full month name
            // $endMonthNumber = (int)$endDateObj->format('n'); // Month number (1-12)
            // $endYear = (int)$endDateObj->format('Y');

            
            
            $startYearMonth = (new YearMonth())
                ->setYear((int)$startDateObj->format('Y'))
                ->setMonth((int)$startDateObj->format('n')+1); // Use the enum name
                
            $endYearMonth = (new YearMonth())
                ->setYear((int)$endDateObj->format('Y'))
                ->setMonth((int)$endDateObj->format('n')+1);
            
            $yearMonthRange = (new YearMonthRange())
                ->setStart($startYearMonth)
                ->setEnd($endYearMonth);
                
            $historicalMetricsOptions = (new HistoricalMetricsOptions())
                ->setYearMonthRange($yearMonthRange);
                
            // Prepare the request with historical metrics
            $request = (new GenerateKeywordIdeasRequest())
                ->setCustomerId($this->customerId)
                ->setLanguage(ResourceNames::forLanguageConstant($languageId))
                ->setGeoTargetConstants($geoTargetConstants)
                ->setKeywordPlanNetwork(KeywordPlanNetwork::GOOGLE_SEARCH)
                ->setKeywordSeed((new KeywordSeed())->setKeywords($keywords))
                ->setIncludeAdultKeywords(false)
                ->setPageSize($limit)
                ->setHistoricalMetricsOptions($historicalMetricsOptions);
                
            // dd($request); // Remove or comment this out too after testing
            
            $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas($request);
            
            $results = [];
            $count = 0;
            // if (!$startDate || !$endDate) {
            //     // End date: last day of previous month
            //     $gscEndDate = date('Y-m-d', strtotime('last day of previous month'));

            //     // Start date: first day of the month 11 months before the end date
            //     $gscStartDate = date(
            //         'Y-m-d',
            //         strtotime('first day of -11 months', strtotime($gscEndDate))
            //     );
            // } else {
            //     $gscStartDate = date('Y-m-d', strtotime($startDate));
            //     $gscEndDate   = date('Y-m-d', strtotime($endDate));
            // }
            foreach ($response->iterateAllElements() as $result) {
                $keywordText = $result->getText();
                $metrics = $result->getKeywordIdeaMetrics();
                
                $avgMonthlySearches = $metrics ? $metrics->getAvgMonthlySearches() : 0;
                
                // Calculate estimated metrics
                $estimatedCtr = $this->estimateCtr($metrics ? $metrics->getCompetition() : 0);
                $estimatedImpressions = $this->estimateImpressions($avgMonthlySearches);
                $estimatedClicks = round(($estimatedImpressions * $estimatedCtr) / 100, 2);
                $estimatedPosition = $this->estimatePosition($metrics ? $metrics->getCompetition() : 0);
                
                // Skip keywords with 0 clicks
                if ($estimatedClicks <= 0) {
                    continue;
                }
                $results[] = [
                    'keyword' => $keywordText,
                    'avg_monthly_searches' => $avgMonthlySearches,
                    'monthly_search_volumes' => $metrics ? 
                        array_map(function($volume) {
                            return [
                                'year' => $volume->getYear(),
                                'month' => $volume->getMonth(),
                                'monthly_searches' => $volume->getMonthlySearches()
                            ];
                        }, iterator_to_array($metrics->getMonthlySearchVolumes())) : 
                        [],
                    'competition' => $metrics ? $this->getCompetitionLevel($metrics->getCompetition()) : 'UNKNOWN',
                    'competition_value' => $metrics ? $metrics->getCompetition() : 0,
                    'low_top_of_page_bid' => $metrics ? $this->microsToCurrency($metrics->getLowTopOfPageBidMicros()) : 0,
                    'high_top_of_page_bid' => $metrics ? $this->microsToCurrency($metrics->getHighTopOfPageBidMicros()) : 0,
                    'low_top_of_page_bid_micros' => $metrics ? $metrics->getLowTopOfPageBidMicros() : 0,
                    'high_top_of_page_bid_micros' => $metrics ? $metrics->getHighTopOfPageBidMicros() : 0,
                    // Performance metrics (estimated from Google Ads data)
                    'clicks' => $estimatedClicks,
                    'ctr' => $estimatedCtr,
                    'impressions' => $estimatedImpressions,
                    'position' => $estimatedPosition,
                    'date' => date('Y-m-d'), // For filtering
                ];
                
                $count++;
                if ($count >= $limit) {
                    break;
                }
                
            }
            return $results;
            
        } catch (GoogleAdsException $googleAdsException) {
            $errorMessages = [];
            foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                $errorMessages[] = sprintf(
                    "Error: %s: %s",
                    $error->getErrorCode()->getErrorCode(),
                    $error->getMessage()
                );
            }
            return ['error' => implode(', ', $errorMessages)];
        } catch (ApiException $apiException) {
            return ['error' => "API Exception: " . $apiException->getMessage()];
        } catch (\Exception $e) {
            return ['error' => "General Exception: " . $e->getMessage()];
        }
    }
    
    /**
     * Estimate CTR based on competition level
     * Note: These are example values - adjust based on your industry/experience
     */
    private function estimateCtr($competitionValue)
    {
        switch ($competitionValue) {
            case 2: // LOW
                return rand(3, 5); // 3-5% CTR for low competition
            case 3: // MEDIUM
                return rand(2, 4); // 2-4% CTR for medium competition
            case 4: // HIGH
                return rand(1, 3); // 1-3% CTR for high competition
            default:
                return rand(2, 4); // Default 2-4%
        }
    }
    
    /**
     * Estimate impressions based on monthly search volume
     * This is a simplified estimation
     */
    private function estimateImpressions($monthlySearches)
    {
        if ($monthlySearches <= 0) return 0;
        
        // Estimate impressions as 3x search volume (accounts for multiple SERP views)
        return $monthlySearches * 3;
    }
    
    /**
     * Estimate average position based on competition
     */
    private function estimatePosition($competitionValue)
    {
        switch ($competitionValue) {
            case 2: // LOW competition
                return rand(1, 3); // Positions 1-3
            case 3: // MEDIUM competition
                return rand(4, 7); // Positions 4-7
            case 4: // HIGH competition
                return rand(8, 10); // Positions 8-10
            default:
                return rand(5, 9); // Default positions
        }
    }
    
    public function extractKeywordMetrics($domainUrl, $seedKeyword, $limit =1, $startDate = null, $endDate = null)
    {
        // 1️⃣ Get keyword ideas data (already implemented by you)
        if ($startDate === null && $endDate === null) {
            $kpkeyword = $this->searchKeywords($seedKeyword, $limit);
        } else {
            $kpkeyword = $this->searchKeywords($seedKeyword, $limit, $startDate, $endDate);
        }

        if (empty($kpkeyword)) {
            return null;
        }

        // dd($kpkeyword);
        // 2️⃣ Loop through results to find EXACT match of seed keyword
        foreach ($kpkeyword as $idea) {

            // Keyword text returned by Google
            dd($idea);
            $text = $idea->getText();

            if (strtolower(trim($text)) === strtolower(trim($seedKeyword))) {

                $metrics = $idea->getKeywordIdeaMetrics();

                if (!$metrics) {
                    return null;
                }

                // 3️⃣ Extract required metrics
                $avgMonthlySearches = $metrics->getAvgMonthlySearches();
                $competitionEnum    = $metrics->getCompetition();
                $competitionValue   = $competitionEnum ? $competitionEnum->getValue() : null;

                $lowBidMicros  = $metrics->getLowTopOfPageBidMicros();
                $highBidMicros = $metrics->getHighTopOfPageBidMicros();

                // Convert micros → currency
                $lowBid  = $lowBidMicros ? $lowBidMicros / 1_000_000 : null;
                $highBid = $highBidMicros ? $highBidMicros / 1_000_000 : null;

                return [
                    'keyword'                       => $text,
                    'avg_monthly_searches'           => $avgMonthlySearches,
                    'competition'                    => $competitionEnum ? $competitionEnum->name() : 'UNKNOWN',
                    'competition_value'              => $competitionValue,
                    'low_top_of_page_bid_micros'     => $lowBidMicros,
                    'high_top_of_page_bid_micros'    => $highBidMicros,
                    'low_top_of_page_bid'            => $lowBid,
                    'high_top_of_page_bid'           => $highBid,
                ];
            }
        }

        // If Google didn't return your seed keyword in ideas
        return null;
    }

    /**
     * Get keyword data for a specific seed keyword and domain URL
     * 
     * @param string $domainUrl The domain URL to analyze
     * @param string $seedKeyword The seed keyword to get data for
     * @param int $includeAdultKeywords Whether to include adult keywords (0 or 1)
     * @param string|null $startDate Start date in YYYY-MM-DD format
     * @param string|null $endDate End date in YYYY-MM-DD format
     * @return array|null Returns keyword metrics or null if not found
    */
    public function getBulkKeywordData($domainUrl, array $keywords, $includeAdultKeywords = 0, $startDate = null, $endDate = null)
    {
        try {
            $keywordPlanIdeaService = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();
           
            $request = new GenerateKeywordHistoricalMetricsRequest([
                'customer_id' => $this->customerId,
                'keywords' => $keywords,
                // 'keyword_plan_network' => KeywordPlanNetwork::GOOGLE_SEARCH,
                'language' => ResourceNames::forLanguageConstant(1000), // English
                'geo_target_constants' => [
                    ResourceNames::forGeoTargetConstant(2356) // India
                ],
                'include_adult_keywords' => $includeAdultKeywords == 1,
                'keyword_plan_network' => KeywordPlanNetwork::GOOGLE_SEARCH_AND_PARTNERS,
            ]);

            if ($startDate !== null && $endDate !== null) {
                $historicalMetricsOptions = $this->buildHistoricalMetricsOptions($startDate, $endDate);
                $request->setHistoricalMetricsOptions($historicalMetricsOptions);
            }

            $response = $keywordPlanIdeaService->generateKeywordHistoricalMetrics($request);
            $keywordData = [];
                
            foreach ($response->getResults() as $result) {
                $metrics = $result->getKeywordMetrics();
                $keywordText = $result->getText();
                    
                $keywordData[$keywordText] = [
                    'keyword' => $keywordText,
                    'avg_monthly_searches' => $metrics?->getAvgMonthlySearches() ?? 0,
                    'competition' => $this->getCompetitionLevel($metrics?->getCompetition() ?? 'UNKNOWN'),
                    'competition_index' => $metrics?->getCompetitionIndex() ?? 0,
                    'low_top_of_page_bid_micros' => $metrics?->getLowTopOfPageBidMicros() ?? 0,
                    'high_top_of_page_bid_micros' => $metrics?->getHighTopOfPageBidMicros() ?? 0,
                    'low_top_of_page_bid' => $this->microsToCurrency($metrics?->getLowTopOfPageBidMicros() ?? 0),
                    'high_top_of_page_bid' => $this->microsToCurrency($metrics?->getHighTopOfPageBidMicros() ?? 0),
                ];
            }
                
            return $keywordData;


            // // Add historical metrics options if dates are provided
            
            
            // // Execute the request
            // $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas($request);
        } catch (GoogleAdsException $googleAdsException) {
            Log::error('Google Ads API Error (Bulk): ' . $googleAdsException->getMessage());
            throw new Exception("Google Ads API Error: " . $googleAdsException->getBasicMessage());
        } catch (ApiException $apiException) {
            Log::error('API Exception (Bulk): ' . $apiException->getMessage());
            
            // If rate limited, wait and retry once
            if (strpos($apiException->getMessage(), 'RESOURCE_EXHAUSTED') !== false) {
                sleep(4);
                return $this->getBulkKeywordData($domainUrl, $keywords, $includeAdultKeywords, $startDate, $endDate);
            }
            
            throw new Exception("API Exception: " . $apiException->getMessage());
        } catch (Exception $e) {
            Log::error('Error getting bulk keyword data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build historical metrics options from date range
     * 
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     * @return HistoricalMetricsOptions
     */
    private function buildHistoricalMetricsOptions($startDate, $endDate)
    {
        $startDateTime = new DateTime($startDate, new DateTimeZone('UTC'));
        $endDateTime = new DateTime($endDate, new DateTimeZone('UTC'));
        
        $yearMonthRange = new YearMonthRange([
            'start' => new YearMonth([
                'year' => (int)$startDateTime->format('Y'),
                'month' => $this->getMonthEnum((int)$startDateTime->format('n'))
            ]),
            'end' => new YearMonth([
                'year' => (int)$endDateTime->format('Y'),
                'month' => $this->getMonthEnum((int)$endDateTime->format('n'))
            ])
        ]);
        
        return new HistoricalMetricsOptions([
            'year_month_range' => $yearMonthRange
        ]);
    }

    /**
     * Convert month number to Month enum
     * 
     * @param int $monthNumber Month number (1-12)
     * @return int Month enum value
     */
    private function getMonthEnum($monthNumber)
    {
        $months = [
            1 => MonthOfYearEnumMonthOfYear::JANUARY,
            2 => MonthOfYearEnumMonthOfYear::FEBRUARY,
            3 => MonthOfYearEnumMonthOfYear::MARCH,
            4 => MonthOfYearEnumMonthOfYear::APRIL,
            5 => MonthOfYearEnumMonthOfYear::MAY,
            6 => MonthOfYearEnumMonthOfYear::JUNE,
            7 => MonthOfYearEnumMonthOfYear::JULY,
            8 => MonthOfYearEnumMonthOfYear::AUGUST,
            9 => MonthOfYearEnumMonthOfYear::SEPTEMBER,
            10 => MonthOfYearEnumMonthOfYear::OCTOBER,
            11 => MonthOfYearEnumMonthOfYear::NOVEMBER,
            12 => MonthOfYearEnumMonthOfYear::DECEMBER,
        ];
        
        return $months[$monthNumber] ?? MonthOfYearEnumMonthOfYear::UNSPECIFIED;
    }

    /**
     * Get human-readable competition level
     * 
     * @param int $competition Competition enum value
     * @return string Competition level (LOW, MEDIUM, HIGH, UNSPECIFIED)
     */
    private function getCompetitionLevel($competition)
    {
        $levels = [
            1 => 'UNSPECIFIED',
            2 => 'UNKNOWN',
            3 => 'LOW',
            4 => 'MEDIUM',
            5 => 'HIGH',
        ];
        
        return $levels[$competition] ?? 'UNSPECIFIED';
    }

    /**
     * Convert micros to currency (divide by 1,000,000)
     * 
     * @param int|null $micros Amount in micros
     * @return float|null Amount in currency units
     */
    private function microsToCurrency($micros)
    {
        if ($micros === null) {
            return null;
        }
        
        return round($micros / 1000000, 2);
    }
    public function searchKeywordsByUrl($url, $limit = 20, $startDate = null, $endDate = null, $locationIds = ['2840'], $languageId = '1000')
    {
        try {
            // Ensure URL has protocol
            if (!preg_match('/^https?:\/\//', $url)) {
                $url = 'https://' . $url;
            }
            
            $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();
            // dd($keywordPlanIdeaServiceClient);
            // Create geo target constants
            $geoTargetConstants = array_map(function ($locationId) {
                return ResourceNames::forGeoTargetConstant($locationId);
            }, $locationIds);
            
            // Create YearMonth objects for historical metrics
            if (!$startDate || !$endDate) {
                // For end date: last day of previous month (as you want)
                $endDateObj = new DateTime('last day of previous month', new DateTimeZone('UTC'));
                
                // For start date: first day of the month 11 months before end date
                $startDateObj = clone $endDateObj;
                $startDateObj->modify('first day of -11 months');
            } else {
                $startDateObj = new DateTime($startDate);
                $endDateObj = new DateTime($endDate);
            }
            $startYearMonth = (new YearMonth())
                ->setYear((int)$startDateObj->format('Y'))
                ->setMonth((int)$startDateObj->format('n')+1); // Use the enum name
                
            $endYearMonth = (new YearMonth())
                ->setYear((int)$endDateObj->format('Y'))
                ->setMonth((int)$endDateObj->format('n')+1);
            
            $yearMonthRange = (new YearMonthRange())
                ->setStart($startYearMonth)
                ->setEnd($endYearMonth);
                
            $historicalMetricsOptions = (new HistoricalMetricsOptions())
                ->setYearMonthRange($yearMonthRange);
        
            
            // Prepare the request with URL seed
            $request = (new GenerateKeywordIdeasRequest())
                ->setLanguage(ResourceNames::forLanguageConstant($languageId))
                ->setCustomerId($this->customerId)
                ->setGeoTargetConstants($geoTargetConstants)
                ->setKeywordPlanNetwork(KeywordPlanNetwork::GOOGLE_SEARCH)
                ->setIncludeAdultKeywords(false)
                ->setUrlSeed((new UrlSeed())->setUrl($url))
                ->setHistoricalMetricsOptions($historicalMetricsOptions)
                ->setPageSize($limit);

                // dd($request);
            $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas($request);
            
            $results = [];
            $count = 0;
            
            // Initialize GSC Service for getting keyword metrics
            // $gscService = app(\App\Services\GoogleSearchConsoleService::class);
            
            foreach ($response->iterateAllElements() as $result) {
                $keywordText = $result->getText();
                $metrics = $result->getKeywordIdeaMetrics();
                
                // 1. Get Keyword Planner metrics
                $keywordPlannerData = [
                    'keyword' => $keywordText,
                    'avg_monthly_searches' => $metrics ? $metrics->getAvgMonthlySearches() : 0,
                    'competition' => $metrics ? $this->getCompetitionLevel($metrics->getCompetition()) : 'UNKNOWN',
                    'competition_value' => $metrics ? $metrics->getCompetition() : 0,
                    'competition_index' => $metrics ? $this->calculateCompetitionIndex($metrics->getCompetition()) : 0,
                    'monthly_search_volumes' => $metrics ? 
                        array_map(function($volume) {
                            return [
                                'year' => $volume->getYear(),
                                'month' => $volume->getMonth(),
                                'monthly_searches' => $volume->getMonthlySearches()
                            ];
                        }, iterator_to_array($metrics->getMonthlySearchVolumes())) : 
                        [],
                    'low_top_of_page_bid_micros' => $metrics ? $metrics->getLowTopOfPageBidMicros() : 0,
                    'high_top_of_page_bid_micros' => $metrics ? $metrics->getHighTopOfPageBidMicros() : 0,
                    'low_top_of_page_bid' => $metrics ? $this->microsToCurrency($metrics->getLowTopOfPageBidMicros()) : 0,
                    'high_top_of_page_bid' => $metrics ? $this->microsToCurrency($metrics->getHighTopOfPageBidMicros()) : 0,
                ];

                
                // 2. Get KP metrics for this keyword
                $kpMetrics = [];
                $parsedUrl = parse_url($url);
                $domain = $parsedUrl['host'] ?? $url;
                $propertyUrl = 'sc-domain:' . $domain;
                    
                // Get last 90 days of data for this keyword
                $kpData = $this->searchKeywords(
                    $keywordText,
                    1
                );

                    
                $kpMetrics = [
                    'clicks' => $kpData[0]['clicks'] ?? 0,
                    'impressions' => $kpData[0]['impressions'] ?? 0,
                    'ctr' => $kpData[0]['ctr'] ?? 0,
                    'position' => $kpData[0]['position'] ?? 0
                ];
                // dd($keywordPlannerData, $kpMetrics);
                
                // 3. Merge both datasets
                $mergedData = array_merge($keywordPlannerData, $kpMetrics);
                $mergedData['date'] = date('Y-m-d'); // For filtering
                
                $results[] = $mergedData;
                
                $count++;
                if ($count >= $limit) {
                    break;
                }
                
                // Add a small delay to avoid rate limiting
                if ($count % 5 === 0) {
                    usleep(100000); // 100ms delay
                }
            }
            
            return $results;
            
        } catch (GoogleAdsException $googleAdsException) {
            $errorMessages = [];
            foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                $errorMessages[] = sprintf(
                    "Error: %s: %s",
                    $error->getErrorCode()->getErrorCode(),
                    $error->getMessage()
                );
            }
            return ['error' => implode(', ', $errorMessages)];
        } catch (ApiException $apiException) {
            return ['error' => "API Exception: " . $apiException->getMessage()];
        } catch (\Exception $e) {
            return ['error' => "General Exception: " . $e->getMessage()];
        }
    }

/**
 * Calculate competition index based on competition value
 * You can adjust this formula based on your needs
 */
private function calculateCompetitionIndex($competitionValue)
{
    // Competition value is typically between 0 and 1
    // Convert to percentage (0-100) for competition_index
    return $competitionValue * 100;
}
}