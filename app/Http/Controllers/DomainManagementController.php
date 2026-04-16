<?php

namespace App\Http\Controllers;

use App\Models\Client_landing_page_urlModel;
use Illuminate\Http\Request;
use App\Models\DomainManagementModel;
use App\Models\Client_propertiesModel;
use App\Models\User;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Support\Facades\Hash;
class DomainManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $gscService;
    public function __construct(Request $request)
    {
        $customerId = DomainManagementModel::where('id', $request->domainmanagement_id)->value('customer_id');
        $managerId = DomainManagementModel::where('id', $request->domainmanagement_id)->value('manager_id');

        $this->gscService = new GoogleSearchConsoleService($customerId,$managerId);
        // $this->gscService = $gscService;
    }

    public function index()
    {
        $clients = DomainManagementModel::orderBy('id', 'desc')->get();
        return view("clients.index", compact("clients"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("clients.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $data = [];
        $client = new DomainManagementModel;
        $data = $request->all();

        $client->name = $data["name"];
        $client->phone = $data["phone"];
        $client->email = $data["email"];
        $client->industry = $data["industry"];
        $client->customer_id = $data['customer_id'];
        $client->manager_id = $data['manager_id'];
        $scheduledSlug = '"' . str_replace(',', '","', $data["scheduled"]) . '"';
        $client->scheduled_slug = $scheduledSlug;
        $visitedSlug = '"' . str_replace(',', '","', $data["visited"]) . '"';
        $client->visited_slug = $visitedSlug;
        $missedSlug = '"' . str_replace(',', '","', $data["missed"]) . '"';
        $client->missed_slug = $missedSlug;
        $interestedSlug = '"' . str_replace(',', '","', $data["interested"]) . '"';
        $client->interested_slug = $interestedSlug;
        $client->city = $data["city"];
        $client->zip = $data["zip"];
        $client->status = $data["status"];
        $client->save();

        $user = new User;
        $user->name = $data["name"];
        $user->email = $data["email"];
        $user->type = $data["type"];
        $user->password = Hash::make($request->password); // Hashing password
        $user->domainmanagement_id = $client->id;
        $user->save();


        if ($client->save() && $user->save()) {

            // $statusController = new StatusController();
            // $data = "New Client Added - " . $client->name;
            // $statusController->store($data,$client->id);

            $request->session()->flash("message", "Client has been added successfully");
            return redirect('/add-client');
        } else {
            $request->session()->flash("error", "Unable to add client. Please try again later");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        unset($_SESSION['lms_client_check']);
        $client_data = DomainManagementModel::with('Client_properties')->where("id", $id)->get();

        return view("clients.show", compact("client_data"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = DomainManagementModel::find($id);
        $data1 = User::where('domainmanagement_id',$id)->first();

        $s1 = trim($data->scheduled_slug, '"');
        $scheduled_slug = str_replace('","', ',', $s1);

        $s1 = trim($data->visited_slug, '"');
        $visited_slug = str_replace('","', ',', $s1);

        $s1 = trim($data->missed_slug, '"');
        $missed_slug = str_replace('","', ',', $s1);

        $s1 = trim($data->interested_slug, '"');
        $interested_slug = str_replace('","', ',', $s1);

        // dd($id);
        return view("clients.edit", compact("data","data1","scheduled_slug","visited_slug","missed_slug","interested_slug"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        
        // dd($request);

        $data = [];
        $data = $request->all();

        $client = DomainManagementModel::find($data['id']);

        $user = User::find($data['userid']);
        $client->customer_id = $data['customer_id'];
        $client->manager_id = $data['manager_id'];


        if(isset($request->name_update)){
            $client->name = $data["name_update"];
            $user->name = $data["name_update"];
        }else{
            $client->name = $data["name"];
            $user->name = $data["name"];
        }

        if(isset($request->phone_update)){
            $client->phone = $data["phone_update"];
        }else{
            $client->phone = $data["phone"];
        }

        if(isset($request->email_update)){
            $client->email = $data["email_update"];
            $user->email = $data["email_update"];
        }else{
            $client->email = $data["email"];
            $user->email = $data["email"];
        }

        if(isset($request->industry_update)){
            $client->industry = $data["industry_update"];
        }else{
            $client->industry = $data["industry"];
        }

        if(isset($request->scheduled_update)){
            $scheduledSlug = '"' . str_replace(',', '","', $data["scheduled_update"]) . '"';
            $client->scheduled_slug = $scheduledSlug;
        }else{
            $scheduledSlug = '"' . str_replace(',', '","', $data["scheduled"]) . '"';
            $client->scheduled_slug = $scheduledSlug;
        }
        
        if(isset($request->visited_update)){
            $visitedSlug = '"' . str_replace(',', '","', $data["visited_update"]) . '"';
            $client->visited_slug = $visitedSlug;
        }else{
            $visitedSlug = '"' . str_replace(',', '","', $data["visited"]) . '"';
            $client->visited_slug = $visitedSlug;
        }
       
        if(isset($request->missed_update)){
            $missedSlug = '"' . str_replace(',', '","', $data["missed_update"]) . '"';
            $client->missed_slug = $missedSlug;
        }else{
            $missedSlug = '"' . str_replace(',', '","', $data["missed"]) . '"';
            $client->missed_slug = $missedSlug;
        }

        if(isset($request->interested_update)){
            $interestedSlug = '"' . str_replace(',', '","', $data["interested_update"]) . '"';
            $client->interested_slug = $interestedSlug;
        }else{
            $interestedSlug = '"' . str_replace(',', '","', $data["interested"]) . '"';
            $client->interested_slug = $interestedSlug;
        }
       
        if(isset($request->city_update)){
            $client->city = $data["city_update"];
        }else{
            $client->city = $data["city"];
        }

        if(isset($request->zip_update)){
            $client->zip = $data["zip_update"];
        }else{
            $client->zip = $data["zip"];
        }

        if(isset($request->status_update)){
            $client->status = $data["status_update"];
        }else{
            $client->status = $data["status"];
        }

        if(isset($request->type_update)){
            $user->type = $data["type_update"];
        }else{
            $user->type = $data["type"];
        }

        if(isset($request->password_update)){

            $user->password = Hash::make($data["password_update"]);
            // dd(Hash::make($data["password_update"]));
        }else{
            $user->password = Hash::make($data["password"]);
        }
       
        $field[] = '';

        if ($client->save() && $user->save()) {

            foreach($request->request as $key => $data ){
                if(strpos($key, '_update')){
                    $field[] = $key;
                }
            }

            $request->session()->flash("message", "Client has been Updated successfully");
            return redirect('/clients');
        } else {
            $request->session()->flash("error", "Unable to update client. Please try again later");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    { 
        if (!empty($id)) {
            $res = User::where('id',$id)->delete();
            if($res)   {
                $request->session()->flash("message", "User has been deleted successfully");
            } else {
                $request->session()->flash("error", "Unable to delete this user. Please try again later");
            }
        } else {
            $request->session()->flash("error", "You are not authorised to access this location");
        }
        return redirect()->route("users");
    }

    public function showproperties(string $id)
    {
        $client = DomainManagementModel::where("id", $id)->get();
        $client_data = Client_propertiesModel::where("domainmanagement_id", $id)->get();
        // dd($client_data);
        return view("clientsProperties.index", compact("client_data","client"));
    }

    public function createproperties(string $id)
    {
        return view("clientsProperties.create", compact("id"));
    }

    public function storeproperties(Request $request)
    {
        $insertData = [];
        $data = [];

        $data = $request->all();

        $indexedPages = $this->fetchGscUrls($data["domain"]);
        if ($indexedPages instanceof \Illuminate\Http\JsonResponse) {
            $indexedPages = json_decode($indexedPages->getContent(), true);
        }

        if (isset($indexedPages['status']) && $indexedPages['status'] == false) {
            $errorMessage = 'Something went wrong';
            if (!empty($indexedPages['error'])) {
                $cleanError = str_replace('Error: ', '', $indexedPages['error']);
                $decodedError = json_decode($cleanError, true);
                if (isset($decodedError['error']['message'])) {
                    $errorMessage = $decodedError['error']['message'];
                }
            }
            return redirect('/clients')->with('error', $errorMessage);
        }
        
        $client_propertie = new Client_propertiesModel;
        $client_propertie->type = $data["type"];
        $client_propertie->domainmanagement_id = $data["domainmanagement_id"];
        $client_propertie->domain = $data["domain"];
        $client_propertie->keyword_mentioned_array = $data["keyword_mentioned_array"];
        // $client_propertie->customer_id = $data['customer_id'];
        // $client_propertie->manager_id = $data['manager_id'];
        $client_propertie->frequency = $data["frequency_update"];

        if ($client_propertie->save()) {
            foreach($indexedPages as $page) {
                $insertData[] = [
                    'client_properties_id'  => $client_propertie->id,
                    'domainmanagement_id' => $data['domainmanagement_id'],
                    'lms_url'             => $data['domain'],
                    'url'            => $page['url'],
                    'impression'   => $page['impressions'] ?? 0,
                    'position' => $page['position'] ?? 0,
                    'click'        => $page['clicks'] ?? 0,
                    'ctr'          => $page['ctr'] ?? 0,
                ];
            }
            if (!empty($insertData)) {
                foreach (array_chunk($insertData, 300) as $chunk) {
                    Client_landing_page_urlModel::insert($chunk);
                }
            }
            $request->session()->flash("message", "Client Property has been added successfully");
            return redirect('/clients');
        } else {
            $request->session()->flash("error", "Unable to add client property. Please try again later");
        }
    }
    public function fetchGscUrls($propertyUri)
    {
        try {
            // $serviceAccountFile = storage_path('app/google-analytics.json');
            // $propertyUri = 'https://www.aaynaclinic.com';
            $indexedPages = $this->gscService->getIndexedPages($propertyUri, '2025-11-18', '2025-11-18');

            return $indexedPages;

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function editproperties(string $id)
    {
        $client_data = Client_propertiesModel::where("id", $id)->get();
        $client = DomainManagementModel::where("id", $client_data[0]->domainmanagement_id)->get();
        return view("clientsProperties.edit", compact("client_data","client"));
    }

    public function updateproperties(Request $request)
    {
        $data = [];
        $data = $request->all();
        $indexedPages = $this->fetchGscUrls($data["domain"]);
        if ($indexedPages instanceof \Illuminate\Http\JsonResponse) {
            $indexedPages = json_decode($indexedPages->getContent(), true);
        }

        if (isset($indexedPages['status']) && $indexedPages['status'] == false) {
            $errorMessage = 'Something went wrong';
            if (!empty($indexedPages['error'])) {
                $cleanError = str_replace('Error: ', '', $indexedPages['error']);
                $decodedError = json_decode($cleanError, true);
                if (isset($decodedError['error']['message'])) {
                    $errorMessage = $decodedError['error']['message'];
                }
            }
            return redirect('/clients')->with('error', $errorMessage);
        }
        $client_propertie = Client_propertiesModel::where("id", $data['id'])->first();
        
        $client_propertie->type = $data["type"];
        $client_propertie->domain = $data["domain"];
        $client_propertie->keyword_mentioned_array = $data["keyword_mentioned_array"];
        // $client_propertie->customer_id = $data['customer_id'];
        // $client_propertie->manager_id = $data['manager_id'];
        $client_propertie->frequency = $data["frequency_update"];

        if ($client_propertie->save()) {
            $request->session()->flash("message", "Client Property has been updated successfully");
            return redirect('/clients');
        } else {
            $request->session()->flash("error", "Unable to Update client property. Please try again later");
        }
    }
}
