<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

use GuzzleHttp\Psr7\Utils;

class EsignApiController extends Controller
{
    private function featureEnabled()
    {
        // use existing helper if available
        return function_exists('admin_setting') ? admin_setting('enable_authbridge_feature') : env('ENABLE_AUTHBRIDGE_FEATURE', false);
    }

    private function baseUrl()
    {
        return env('AUTHBRIDGE_BASE_URL', 'https://signdrive-uat.authbridge.com');
    }

    /**
     * Step 1: Get token
     * GET /esign/token
     * Accepts optional query params client_id, client_secret, username, password
     */
    public function getToken(Request $request)
    {
        if (!$this->featureEnabled()) {
            return response()->json(['error' => 'Authbridge feature is disabled'], 403);
        }

        $clientId = $request->input('client_id', env('AUTHBRIDGE_CLIENT_ID'));
        $clientSecret = $request->input('client_secret', env('AUTHBRIDGE_CLIENT_SECRET'));
        $username = $request->input('username', env('AUTHBRIDGE_USERNAME'));
        $password = $request->input('password', env('AUTHBRIDGE_PASSWORD'));
        $grantType = $request->input('grant_type', 'password');

        if (!$clientId || !$clientSecret || !$username || !$password) {
            return response()->json(['error' => 'Missing credentials'], 422);
        }

        $url = rtrim($this->baseUrl(), '/') . '/signdrive/msbapi/public/access_token';

        try {
            $res = Http::get($url, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => $grantType,
                'username' => $username,
                'password' => $password,
            ]);

            if ($res->successful()) {
                return response()->json($res->json());
            }

            return response()->json(['error' => 'Failed to obtain token', 'detail' => $res->body()], $res->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Request failed', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Step 2: Compose ePak (create envelope)
     * POST /esign/compose
     * Headers: access_token (msb_token or access_token), tenant_id
     * Body: multipart: files (pdf) and epakData JSON
     */
    public function composeEpak(Request $request)
    {
        if (!$this->featureEnabled()) {
            return response()->json(['error' => 'Authbridge feature is disabled'], 403);
        }

        $msbToken = $request->header('access_token') ?? $request->input('msb_token');
        $tenantId = $request->header('tenant_id') ?? $request->input('tenant_id');
        $epakData = $request->input('epakData') ?? $request->input('epakdata') ?? null;

        if (!$msbToken || !$tenantId || !$epakData) {
            return response()->json(['error' => 'Missing required fields: msb_token/tenant_id/epakData'], 422);
        }

        $url = rtrim($this->baseUrl(), '/') . '/signdrive/msbapi/v2/compose/epak';

        try {
            $client = new Client();
            $multipart = [];

            // Files: support single file upload or files[]
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $multipart[] = [
                        'name' => 'files',
                        'contents' => fopen($file->getRealPath(), 'r'),
                        'filename' => $file->getClientOriginalName(),
                    ];
                }
            } elseif ($request->hasFile('file')) {
                $file = $request->file('file');
                $multipart[] = [
                    'name' => 'files',
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                ];
            }

            // If a storage path is provided (e.g., contract id), allow a path value to be passed that points to an existing file
            if ($request->filled('file_path') && empty($multipart)) {
                // attempt to read from storage
                $filePath = $request->input('file_path');
                if (Storage::exists($filePath)) {
                    $temp = tmpfile();
                    $meta = stream_get_meta_data($temp);
                    $tmpPath = $meta['uri'];
                    fwrite($temp, Storage::get($filePath));
                    $multipart[] = [
                        'name' => 'files',
                        'contents' => fopen($tmpPath, 'r'),
                        'filename' => basename($filePath),
                    ];
                }
            }

            // Add epakData as a field
            $multipart[] = [
                'name' => 'epakData',
                'contents' => is_string($epakData) ? $epakData : json_encode($epakData),
            ];

            $res = $client->request('POST', $url, [
                'headers' => [
                    'access_token' => $msbToken,
                    'tenant_id' => $tenantId,
                ],
                'multipart' => $multipart,
                'timeout' => 60,
            ]);

            $body = (string)$res->getBody();
            $json = json_decode($body, true);
            return response()->json($json ?? ['raw' => $body], $res->getStatusCode());
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $body = $e->getResponse() ? (string)$e->getResponse()->getBody() : $e->getMessage();
            return response()->json(['error' => 'Compose failed', 'detail' => $body], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Compose failed', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Step 3: Get EasySign links
     * GET /esign/{epakId}/links
     * Headers: access_token, tenant_id
     */
    public function getEasySignLinks(Request $request, $epakId)
    {
        if (!$this->featureEnabled()) {
            return response()->json(['error' => 'Authbridge feature is disabled'], 403);
        }

        $msbToken = $request->header('access_token') ?? $request->input('msb_token');
        $tenantId = $request->header('tenant_id') ?? $request->input('tenant_id');

        if (!$msbToken || !$tenantId) {
            return response()->json(['error' => 'Missing headers: access_token/tenant_id'], 422);
        }

        $url = rtrim($this->baseUrl(), '/') . '/signdrive/msbapi/v2/epaks/easysignlinks/' . $epakId;
        try {
            $res = Http::withHeaders([
                'access_token' => $msbToken,
                'tenant_id' => $tenantId,
            ])->get($url);

            if ($res->successful()) {
                return response()->json($res->json());
            }

            return response()->json(['error' => 'Failed to fetch easy sign links', 'detail' => $res->body()], $res->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Request failed', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Step 4: Download signed PDF
     * GET /esign/download?docId=...&filename=...
     * Headers: access_token, tenant_id
     */
    public function downloadDocument(Request $request, $contractId=0)
    {
        if (!$this->featureEnabled()) {
            return response()->json(['error' => 'Authbridge feature is disabled'], 403);
        }

        $docId = $request->input('docId');
        $msbToken = $request->header('access_token') ?? $request->input('msb_token');
        $tenantId = $request->header('tenant_id') ?? $request->input('tenant_id');
        $filename = $request->input('filename') ?? ('signed_' . Str::random(6) . '.pdf');

        if (!$docId || !$msbToken || !$tenantId) {
            return response()->json(['error' => 'Missing required parameters/docId or access headers'], 422);
        }

        $url = rtrim($this->baseUrl(), '/') . '/signdrive/msbapi/v1/document/download';

        try {
            $client = new Client(['timeout' => 120]);
            $res = $client->request('GET', $url, [
                'headers' => [
                    'access_token' => $msbToken,
                    'tenant_id' => $tenantId,
                ],
                'query' => [
                    'docId' => $docId,
                    'docType' => 'Current'
                ],
                'sink' => fopen(sys_get_temp_dir() . '/' . $filename, 'w')
            ]);

            if ($res->getStatusCode() >= 200 && $res->getStatusCode() < 300) {
                $tempPath = sys_get_temp_dir() . '/' . $filename;
                
                $storageController = fileStorageTypeController();
                $destFolder = $storageController->get_file_path($contractId);
                // Store according to configured storage
                if (fileStorageType() != 'Local') {
                    $destPath = $storageController->storeContent($tempPath, $destFolder, $filename);
                } else {
                    if (!Storage::exists($destFolder)) Storage::makeDirectory($destFolder);
                    $destPath = $destFolder . '/' . $filename;
                    Storage::put($destPath, file_get_contents($tempPath));
                }

                // cleanup temp file
                @unlink($tempPath);

                return response()->json(['success' => true, 'path' => $destPath, 'filename' => $filename]);
            }

            return response()->json(['error' => 'Failed to download document'], $res->getStatusCode());
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $body = $e->getResponse() ? (string)$e->getResponse()->getBody() : $e->getMessage();
            return response()->json(['error' => 'Download failed', 'detail' => $body], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Download failed', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Orchestrator: create epak for contract and notify approvers
     */
    public function sendEpakAndNotify(Request $request, $contractId)
    {
        if (!$this->featureEnabled()) {
            return response()->json(['error' => 'Authbridge feature is disabled'], 403);
        }

        $contract = \App\Models\Contract::find($contractId);
        if (!$contract) return response()->json(['error' => 'Invalid contract id'], 404);

        // Determine file path to send - prefer contract_attachment
        $filePath = $contract->contract_attachment ?? null;
        if (!$filePath) return response()->json(['error' => 'Contract has no attachment to send for eSign'], 422);

        // Get token
        $tokenRes = $this->getToken(new Request());
        if ($tokenRes->getStatusCode() !== 200) {
            return response()->json(['error' => 'Failed to obtain token', 'detail' => $tokenRes->getContent()], $tokenRes->getStatusCode());
        }
        $tokenBody = json_decode($tokenRes->getContent(), true);
        $msbToken = $tokenBody['msb_token'] ?? ($tokenBody['access_token'] ?? null);
        if (!$msbToken) return response()->json(['error' => 'msb_token not present in token response'], 500);

        $tenantId = 'APOLLO_HOSPITALS';

        // Build epakData: prefer explicit signatory rows (approver_type_row = 'signatory') when present
        $signatoryRows = \App\Models\ApprovalContracts::where('contract_id', $contractId)->where('approver_type_row', 'signatory')->get()->filter(function ($r){
            try { return strtolower(trim(decryptString($r->approval_status, 'approval_status'))) === 'pending'; } catch (\Throwable $e) { return strtolower(trim($r->approval_status ?? '')) === 'pending'; }
        });
        
        

        if ($signatoryRows->isNotEmpty()) {
            $pending = $signatoryRows;
        } else {
            // fallback: active flag rows or any signatory rows, otherwise all pending rows
            $pending = \App\Models\ApprovalContracts::where('contract_id', $contractId)->where(function($q){ $q->where('flag', 1)->orWhere('approver_type_row', 'signatory'); })->get();
            if ($pending->isEmpty()) {
                $pending = \App\Models\ApprovalContracts::where('contract_id', $contractId)->get()->filter(function ($r){
                    try { return strtolower(trim(decryptString($r->approval_status, 'approval_status'))) === 'pending'; } catch (\Throwable $e) { return strtolower(trim($r->approval_status ?? '')) === 'pending'; }
                });
            }
        }

        $docName = pathinfo($filePath, PATHINFO_BASENAME);


        $signers = [];
        $signerIndex = 0;
        
        foreach ($pending as $p) {
            $email = $p->approver_email ?? strtolower(json_decode(decryptString($p->username, 'username'), true)['email'] ?? '');
            $name = $p->approver_name ?? json_decode(decryptString($p->username, 'username'), true)['name'] ?? '';
            if (!$email) continue;
            $parts = explode(' ', trim($name));
            $firstName = $parts[0] ?? '';
            $email = "jeevanantham@legalitysimplified.com";
            $lastName = count($parts) > 1 ? implode(' ', array_slice($parts,1)) : '';
            
            $signatureX = 50 + ($signerIndex * 100);
            
            $signers[] = [
                'email' => $email,
                'firstName' => $firstName,
                'middleName' => '',
                'lastName' => $lastName,
                'phoneNumber' => '',
                'countryCode' => 'IN',
                'tagLocationData' => [
                    ['pageNumber' => '1', 'x' => strval($signatureX), 'y' => '750', 'height' => '7.1', 'width' => '65', 'replicateTag' => 'ALL']
                ]
            ];
            $signerIndex++;
        }

        if (count($signers) === 0) return response()->json(['error' => 'No approvers found to send eSign link'], 422);



        // Call compose API
        try {
            $client = new Client(['timeout' => 120]);
            $multipart = [];
            
            $fileStorageController = fileStorageTypeController();
            $file_name = pathinfo($contract->contract_attachment_filename ?? $filePath, PATHINFO_BASENAME);
            $storagePath = $filePath;
            
            if (fileStorageType() != "Local" && strtolower(pathinfo($contract->contract_attachment_filename, PATHINFO_EXTENSION)) == 'pdf') {
                
                $file_name = 'signing_' . strtotime(date('y-m-d h:i:s')) . '.pdf';
    
                $contentPdf = $fileStorageController->downloadUrl($contract->contract_attachment, $file_name);
    
                $file_path = 'contracts/tempDocs/';
    
                $fileStored = Storage::disk('local')->put($file_path . $file_name, $contentPdf);
                
                $storagePath = $file_path . $file_name;
                
                $filePath = base_path() . '/storage/app/' . $storagePath;

            }            

            // Provide file contents: read from storage
            if (Storage::exists($storagePath)) {
                $stream = fopen($filePath, 'r');
                $multipart[] = ['name' => 'files', 'contents' => $stream, 'filename' => $file_name];
            } else {
                return response()->json(['error' => 'File path not found in storage or not a remote URL']);
            }
            
            $epakData = [
                'workflowData' => [[
                    'wfStateOrder' => 1,
                    'action' => 'SIGN',
                    'signingPolicy' => 'QUICKSIGN',
                    'docTagsData' => [[
                        'docName' => $file_name,
                        'signatureTagData' => $signers
                    ]]
                ]],
                'useAutoAppend' => false
            ];            

            $multipart[] = ['name' => 'epakData', 'contents' => json_encode($epakData), 'headers' => ['Content-Type' => 'application/json']];
            
            // print_r($multipart);
            // die;

            $response = $client->request('POST', rtrim($this->baseUrl(), '/') . '/signdrive/msbapi/v2/compose/epak', [
                'headers' => ['access_token' => $msbToken, 'tenant_id' => $tenantId],
                'multipart' => $multipart,
                'verify' => false,
                'timeout' => 120,
            ]);

            $body = json_decode((string)$response->getBody()->getContents(), true);
            
            
            
            // $body = $response->getBody();
            
            // if ($body->isSeekable()) {
            //     $body->rewind();
            // }
            
            // $content = $body->getContents(); 
            
            $epakId = $body['data'] ?? null;
            
            $metadata = $body['metadata'] ?? [];

            if (!$epakId) return response()->json(['error' => 'Esign Server Error/Down', 'detail'=>$body], 500);

            // Get easy sign links
            $linksRes = Http::withHeaders(['access_token'=>$msbToken, 'tenant_id'=>$tenantId])->get(rtrim($this->baseUrl(), '/') . '/signdrive/msbapi/v2/epaks/easysignlinks/' . $epakId);
            $linksJson = $linksRes->json();

            // Notify users
            $emailCtrl = new ContractNotificationController();
            $sent = [];
            foreach ($linksJson['data'][0]['easySignInfo'] ?? [] as $info) {
                $to = $info['userEmail'] ?? null;
                $link = $info['easySignLink'] ?? null;
                if ($to && $link) {
                    // Use sendEmail to send the link in message
                    $desc = "eSign link: $link";
                    $shortDesc = 'eSign Invitation';
                    $emailCtrl->sendEmail($contractId, $desc, $shortDesc, $to, 'Signing', [], [], 'notiMail');
                    $sent[] = ['email'=>$to,'link'=>$link];
                }
            }

            // Save full compose response (epakId + metadata) into esign_resposnse table
            \App\Models\EsignResposnse::create([
                'contract_id'  => $contractId,
                'esignresponse' => json_encode($body),
                'status'       => 1,
            ]);

            // Update contract status to signing/progress
            $contract->update([
                'contract_status' => 'Signing',
                'substatus'       => 'Progress',
            ]);

            return response()->json(['success'=>true,'epak'=>$epakId,'sent'=>$sent,'metadata'=>$metadata]);
        } catch (\Exception $e) {
            return response()->json(['error'=>'Compose/notify failed','message'=>$e->getMessage(),'line'=>$e->getLine(),'file'=>$e->getFile()],500);
        }
    }

    /**
     * TruthScreen eSign API
     * POST /esign/truthscreen/{contractId}
     * Calls https://www.truthscreen.com/api/v2.2/esignapi and returns the HTML response to be displayed in a modal.
     */
    public function sendTruthScreenEsign(Request $request, $contractId)
    {
        $contract = \App\Models\Contract::find($contractId);
        if (!$contract) {
            return response()->json(['error' => 'Invalid contract id'], 404);
        }

        // Get the contract PDF file
        $filePath = $contract->contract_attachment ?? null;
        if (!$filePath) {
            return response()->json(['error' => 'Contract has no attachment to send for eSign'], 422);
        }

        // Resolve file to a local temp path
        $fileStorageController = fileStorageTypeController();
        $fileName = $contract->contract_attachment_filename ?? basename($filePath);

        if (fileStorageType() != 'Local' && strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) == 'pdf') {
            $tempFileName = 'esign_ts_' . strtotime(date('y-m-d h:i:s')) . '.pdf';
            $contentPdf = $fileStorageController->downloadUrl($contract->contract_attachment, $tempFileName);
            $tempDir = 'contracts/tempDocs/';
            Storage::disk('local')->put($tempDir . $tempFileName, $contentPdf);
            $localFilePath = storage_path('app/' . $tempDir . $tempFileName);
        } else {
            // Local storage
            $localFilePath = storage_path('app/' . $filePath);
            if (!file_exists($localFilePath)) {
                $localFilePath = base_path('storage/app/' . $filePath);
            }
        }

        if (!file_exists($localFilePath)) {
            return response()->json(['error' => 'Contract file not found on server'], 422);
        }

        // Count PDF pages using a simple approach
        $pdfPages = 1;
        try {
            $pdfContent = file_get_contents($localFilePath);
            $pageCount = preg_match_all("/\/Type\s*\/Page[^s]/i", $pdfContent, $matches);
            if ($pageCount && $pageCount > 0) {
                $pdfPages = $pageCount;
            }
        } catch (\Throwable $e) {
            $pdfPages = 1;
        }

        // Get signatory/approver info from the contract
        $signatoryRows = \App\Models\ApprovalContracts::where('contract_id', $contractId)
            ->where('approver_type_row', 'signatory')
            ->get()
            ->filter(function ($r) {
                try {
                    return strtolower(trim(decryptString($r->approval_status, 'approval_status'))) === 'pending';
                } catch (\Throwable $e) {
                    return strtolower(trim($r->approval_status ?? '')) === 'pending';
                }
            });

        if ($signatoryRows->isEmpty()) {
            $signatoryRows = \App\Models\ApprovalContracts::where('contract_id', $contractId)
                ->where('flag', 1)
                ->get();
        }

        // Use first signatory or request params for signer details
        $firstSigner = $signatoryRows->first();
        $firstName = $request->input('firstName', $firstSigner->approver_name ?? 'Signer');
        $lastName = $request->input('lastName', '');
        if ($firstSigner && !$request->filled('firstName')) {
            $parts = explode(' ', trim($firstSigner->approver_name ?? ''));
            $firstName = $parts[0] ?? 'Signer';
            $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
        }
        $location = $request->input('location', 'Chennai');

        // Build page_no, x_cordinate, y_cordinate arrays based on page count
        $allPageNos = $request->input('page_no');
        $allXCoords = $request->input('x_cordinate');
        $allYCoords = $request->input('y_cordinate');

        if (!$allPageNos) {
            // Default: all pages
            $pageNos = [];
            $xCoords = [];
            $yCoords = [];
            for ($i = 1; $i <= $pdfPages; $i++) {
                $pageNos[] = $i;
                $xCoords[] = 20;
                $yCoords[] = 30;
            }
            $allPageNos = implode(',', $pageNos);
            $allXCoords = implode(',', $xCoords);
            $allYCoords = implode(',', $yCoords);
        }

        // TruthScreen API credentials
        $tsUsername = env('TRUTHSCREEN_USERNAME', $request->input('ts_username', ''));
        $transId = $request->input('transID', 'TS-' . strtoupper(Str::random(8)));

        if (!$tsUsername) {
            return response()->json(['error' => 'TruthScreen username not configured. Set TRUTHSCREEN_USERNAME in .env'], 422);
        }

        try {
            $client = new Client(['timeout' => 120, 'verify' => false]);

            $multipart = [
                ['name' => 'transID', 'contents' => $transId],
                ['name' => 'docType', 'contents' => $request->input('docType', '42')],
                ['name' => 'pages', 'contents' => (string) $pdfPages],
                ['name' => 'firstName', 'contents' => $firstName],
                ['name' => 'lastName', 'contents' => $lastName],
                ['name' => 'location', 'contents' => $location],
                ['name' => 'page_no', 'contents' => $allPageNos],
                ['name' => 'x_cordinate', 'contents' => $allXCoords],
                ['name' => 'y_cordinate', 'contents' => $allYCoords],
                ['name' => 'authmode', 'contents' => $request->input('authmode', 'OTP')],
                ['name' => 'file', 'contents' => fopen($localFilePath, 'r'), 'filename' => $fileName],
            ];

            // Optional fields
            if ($request->filled('reason')) {
                $multipart[] = ['name' => 'reason', 'contents' => $request->input('reason')];
            }
            if ($request->filled('email')) {
                $multipart[] = ['name' => 'email', 'contents' => $request->input('email')];
            }

            $response = $client->request('POST', 'https://www.truthscreen.com/api/v2.2/esignapi', [
                'headers' => [
                    'username' => $tsUsername,
                ],
                'multipart' => $multipart,
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            // TruthScreen returns HTML response - return it as-is for modal display
            return response()->json([
                'success' => true,
                'html' => $body,
                'transID' => $transId,
                'statusCode' => $statusCode,
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $errBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
            return response()->json(['error' => 'TruthScreen API failed', 'detail' => $errBody], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'TruthScreen API request failed', 'message' => $e->getMessage()], 500);
        }
    }
    
    
    /**
     * Store eSign document response callback (no auth required)
     * POST /contracts/esign/docresponse
     */
    public function docResponse(Request $request)
    {
        $esign = new \App\Models\EsignResposnse();
        $esign->esignresponse = json_encode($request->all());
        $esign->save();

        return response()->json(['success' => true, 'message' => 'Response stored successfully'], 200);
    }    
}
