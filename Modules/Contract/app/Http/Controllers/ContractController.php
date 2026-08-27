<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Contract\Http\Controllers\GoogleDriveController;
use Modules\Contract\Http\Controllers\ContractNotificationController;
use Modules\Contract\Http\Controllers\LocalDriveController;
use Modules\Contract\Http\Controllers\MicrosoftDriveController;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use DateTime;
use Carbon\Carbon;
use stdClass;
use GuzzleHttp\Client;

use Illuminate\Support\Facades\Validator;
use App\Models\AddUsers;
use App\Models\AddUsersSel;
use App\Models\ApprovalContracts;
use App\Models\Branch;
use App\Models\BranchUser;
use App\Models\GeographicalHierarchy;
use App\Models\Category;
use App\Models\ContractParties;
use App\Models\ContractPartiesRepresentative;
use App\Models\ContractType;
use App\Models\CustomFields;
use App\Models\CustomFieldsHistory;
use App\Models\CustomFieldsTimeline;
use App\Models\Contract;
use App\Models\AnnexureMaster;
use App\Models\ContractAnnexure;
use App\Models\ContractHistory;
use App\Models\CustomFieldsData;
use App\Models\ContractPartyData;
use App\Models\ContractPartyDataHistory;
use App\Models\ContractCategories;
use App\Models\EntityBusiness;
use App\Models\EntityMain;
use App\Models\Country;
use App\Models\ContractPartiesLabel;
use App\Models\State;
use App\Models\FinancialLimit;
use App\Models\ContractObligations;
use App\Models\ReminderSettings;
use App\Models\OtpActions;
use App\Models\ExternalTempUser;
use App\Models\ClausesContractsLink;
use App\Models\FlowActivity;
use App\Models\Tasks;
use App\Models\CustomVarDocs;
use App\Models\ClausesCategory;
use App\Models\UserActionLog;
use App\Models\Companyprofile;
use App\Models\EsignResposnse;
use App\Models\ContractStatusTexts;
use App\Models\AiResponse;
use App\Models\LegalAdvisor;
use App\Helpers\Helpers;
use LaravelFileViewer;
use PhpOffice\PhpWord\IOFactory as PhpWordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use Spatie\PdfToText\Pdf as SpatiePdf;
use App\Helpers\EsignPdf;
use App\Helpers\PdfSignerv1;

class ContractController extends Controller
{
    /**
     * Where storeContract() bounces back to on a validation failure. storeContractV3()
     * points this at the V3 page so its users keep their input on the form they started on.
     */
    protected $createRedirectPath = 'contracts/create';

    public function __construct()
    {
        if (Controller::checkCurrentAuth("Contracts") != 1) {
            return abort('404');
        }
    }

    public function noAccess(Request $request)
    {
        return view('noaccess.noaccess');
    }

    public function invalidFileAccess(Request $request)
    {
        return view('noaccess.invalidfile');
    }

    public function eventgroup(Request $request)
    {
        return view('contract::contract.eventGroup')->with('contracts', []);
    }

    public function contractRequest(Request $request)
    {
        $contractTypes = ContractType::get();
        $branchs = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();
        return view('contract::contract.contractRequest', compact('branchs'));
    }

    public function fileVersion(Request $request)
    {
        $contractTypes = ContractType::get();
        return view('contract::contract.fileVersion', compact('contractTypes'));
    }

    public function documentViewer(Request $request, $conid)
    {
        $contracts = Contract::select('*')->where('id', $conid)->get();

        $ContractsFinal = $this->availableContracts($contracts, true);

        if (count($ContractsFinal) == 0) {
            return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract Access')->with('alert-class', 'alert-danger');
        }

        $contractFileName = $ContractsFinal[0]->contract_attachment_filename;

        $filename = $contractFileName;
        $file_url = fileViewUrl($ContractsFinal[0]->contract_attachment, true);
        $filepath = $ContractsFinal[0]->contract_attachment;
        $extFile = pathinfo($filename, PATHINFO_EXTENSION);

        $showInGoogleDocs = ['doc', 'docx'];

        if (fileStorageType() != "Local") {
            $getUrl = get_google_drive_doc_link($contractFileName, $ContractsFinal[0]->contract_attachment, 'view', 'test');
            if (!str_contains($getUrl, 'invalidfile')) {
                $docAlertBox = Helpers::getDocumentDisplaySection($getUrl);
                return $docAlertBox;
            } else {
                return '<div class="alert alert-danger mx-2">Invalid User/File Access</div>';
            }
        }
        
        //$file_url=$filepath;

        $file_data = [
            [
                'label' => __('Label'),
                'value' => "Value"
            ]
        ];
        $file_data = [
            [
                'label' => __('Label'),
                'value' => "Value"
            ]
        ];

        return LaravelFileViewer::show($filename, $filepath, $file_url, $file_data);
    }

    public function deleteContract(Request $request, $id)
    {
        if (Helpers::userInfo()->email == 'admin@legalitysimplified.com' || session()->get('contractSessionUserRole') == 'Super Admin') {
            Contract::where('id', $id)->update(['status' => 0]);
            return response()->json(['message' => 'Contract Deleted', 'errClass' => 'success'], 200);
        } else {
            return response()->json(['message' => 'Invalid Operation Contact Admin', 'errClass' => 'danger'], 200);
        }
    }



    public function updateflow(Request $request)
    {

        $approverIds = $request->approver;



        $users = AddUsers::select('id',  decrypt_data('FirstName', 'AddUsers'))

            ->whereIn('id', $approverIds)

            ->orderByRaw("FIELD(id, " . implode(',', $approverIds) . ")")

            ->get();



        $contracts = Contract::select('*')->where('id', $request->id)->first();

        $appArr = json_decode(trim($contracts->rules_id));
        $appArrold = json_decode(trim($contracts->rules_id));


        $appArr[0]->approver = json_encode($users);

        FlowActivity::create([
            'contract_id' => $request->id,
            'current_data' => json_encode($appArrold),
            'updated_data' => json_encode($appArr),
            'created_by' => 1
        ]);



        $contracts = Contract::select('*')->where('id', $request->id)->update([
            'rules_id' => $appArr,
        ]);


        return response()->json($appArr, 200);
    }

    public function signWithPKCS7($documentPath, $certPath, $keyPath, $keyPass, $outputSignedPath)
    {
        $tempDir = storage_path('app/temp_signing');
        if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);
    
        $inputFile = storage_path("app/$documentPath");
        $outputFile = "$tempDir/signed.p7s";
    
        // Build OpenSSL command
        $cmd = "openssl smime -sign -binary -in \"$inputFile\" -signer \"$certPath\" -inkey \"$keyPath\" -out \"$outputFile\" -outform DER -nodetach";
    
        if ($keyPass) {
            $cmd .= " -passin pass:$keyPass";
        }
    
        exec($cmd, $output, $returnVar);
    
        if ($returnVar !== 0) {
            throw new \Exception("Signing failed: " . implode("\n", $output));
        }
    
        // Store the signed file
        Storage::put($outputSignedPath, file_get_contents($outputFile));
        return $outputSignedPath;
    }

    /**
     * How deep the contract family tree is allowed to be walked, up or down.
     *
     * The seeded set is 3 deep and real renewal chains are short. The cap is here to stop a
     * cycle: a contract whose parent chain points back at itself makes a recursive query run
     * until MariaDB's max_recursive_iterations stops it, which on this server is millions of
     * passes. 32 is far above any real chain and it ends a bad one at once.
     */
    private const FAMILY_TREE_MAX_DEPTH = 32;

    /**
     * The recursive walk up the parentcontract chain, as one SQL fragment.
     *
     * Both family-tree queries need it, so it lives in one place: the Parent Contracts table
     * reads it on its own, and the Subsequent Contracts walk uses it to find the top of the
     * tree before it walks down. The two walks differ only in which side of the join carries
     * parentcontract, so nothing else is shared and nothing is copied.
     *
     * The fragment names a common table expression called ancestry with two columns, pid and
     * depth. Row 1 is the contract's own parent, row 2 its grandparent, and so on. It takes one
     * binding, the contract id.
     */
    private function ancestryCte(): string
    {
        return "ancestry (pid, depth) AS (
                    SELECT c.parentcontract, 1
                      FROM contracts c
                     WHERE c.id = ? AND c.parentcontract > 0
                    UNION ALL
                    SELECT p.parentcontract, a.depth + 1
                      FROM contracts p
                      JOIN ancestry a ON p.id = a.pid
                     WHERE p.parentcontract > 0 AND a.depth < " . self::FAMILY_TREE_MAX_DEPTH . "
                )";
    }

    /**
     * The ids of this contract's ancestors, as a query - what the Parent Contracts table on the
     * Details tab lists.
     *
     * It returns a query, not an array, and that is the point: the caller passes it straight to
     * whereIn(), so the ids stay inside the database and nothing is bound. The rule is in
     * CLAUDE.md, "Query rules": never pass a list of ids into whereIn. On this stack a whereIn
     * with 1,000 or more bound values returns zero rows with no error, so the Parent Contracts
     * table would go blank on a deep tree and look like missing data.
     *
     * A root contract makes the query return no rows, so the caller gets an empty list.
     */
    private function ancestorContractIds($id): QueryBuilder
    {
        // fromRaw, not Eloquent, and this is the documented exception in CLAUDE.md: the query is
        // a WITH RECURSIVE, and Eloquent has no expression for a common table expression that
        // refers to itself. Everything around it is Eloquent - the caller is a Contract query and
        // this is the subquery its whereIn reads.
        //
        // MariaDB accepts a WITH clause inside a derived table, so the recursive walk sits in the
        // FROM of a normal one-column select. That is what lets whereIn take it.
        //
        // The old shape walked the table with the session variable @idlist and FIND_IN_SET. It
        // read every row of the table and sorted them, so it cost 222-255 ms on 3,018 rows, and
        // no index could help it. It was also wrong: see ticket 21.
        //
        // The one binding is this contract id.
        return DB::query()
            ->select('pid')
            ->fromRaw(
                '(WITH RECURSIVE ' . $this->ancestryCte() . ' SELECT pid FROM ancestry) AS ancestry_ids',
                [$id]
            );
    }

    /**
     * The ids of every contract in this contract's family tree below the top of it - what the
     * Subsequent Contracts table on the Details tab lists.
     *
     * The walk goes up the parentcontract chain to the top, then down the whole tree from
     * there, so a contract sees its siblings and cousins as well as its own renewals. That is
     * what the old query produced: it ran one downward walk for each ancestor, and the walk
     * from the highest ancestor already covers every lower one.
     *
     * It returns a query, not an array, the same shape as ancestorContractIds() above and for
     * the same reason: the caller passes it straight to whereIn(), so the ids stay inside the
     * database and nothing is bound. On this stack a whereIn with 1,000 or more bound values
     * returns zero rows with no error, so the Subsequent Contracts table would go blank on the
     * exact contract where it matters most - a master agreement with a thousand children.
     */
    private function subsequentContractIds($id): QueryBuilder
    {
        // fromRaw, not Eloquent, and this is the documented exception in CLAUDE.md: the query
        // is a WITH RECURSIVE. Eloquent has no expression for a common table expression that
        // refers to itself, and a tree of unknown depth cannot be read in one query any other
        // way. MariaDB has WITH RECURSIVE from 10.2; this server is 10.4.24.
        //
        // The old shape walked the tree with the session variable @pv and FIND_IN_SET. It read
        // the whole contracts table once for every row of the table, so it cost 2.0-5.4 s on
        // 3,018 rows and it gets slower with the square of the contract count.
        //
        // Both bindings are this one contract id, so no list of ids crosses the wire.
        return DB::query()
            ->select('id')
            ->fromRaw(
                '(WITH RECURSIVE ' . $this->ancestryCte() . ",
                 descendants (id, depth) AS (
                     SELECT c.id, 1
                       FROM contracts c
                      WHERE c.parentcontract = COALESCE(
                                (SELECT pid FROM ancestry ORDER BY depth DESC LIMIT 1), ?)
                     UNION ALL
                     SELECT c.id, d.depth + 1
                       FROM contracts c
                       JOIN descendants d ON c.parentcontract = d.id
                      WHERE d.depth < " . self::FAMILY_TREE_MAX_DEPTH . '
                 )
                 SELECT DISTINCT id FROM descendants) AS descendant_ids',
                [$id, $id]
            );
    }

    /**
     * Build the four Related Contracts lists the Details tab shows.
     *
     * This code came out of viewContract(). It is one concern - the contracts that relate to this
     * one - and only the Details tab renders it. The three queries inside scan the whole
     * contracts table, so viewContract() calls this method only when the open tab shows the
     * region.
     *
     * Returns the four lists the blade loops. Each one is empty when there is nothing to show.
     *
     * $id is the contract id from the URL. $contracts is the contract row the page shows.
     */
    private function relatedContractLists($id, $contracts): array
    {
        // Three columns is all this row is read for, below. It is also the guard the page needs:
        // the global scopes make it null for a contract the user may not see, and then the block
        // is skipped.
        $contractsold = Contract::withoutGlobalScope('accessLevelSelect')
            ->without('contractPartyList')
            ->select(['catgoery_id', 'department_id', 'contract_type'])
            ->where('id', $id)
            ->first();

        // The blade loops $contractsoldothers with no guard, so it always needs a value.
        // Without this default a missing contract row throws the page.
        $contractsoldothers = collect();

        if ($contractsold) {
            // Seven columns, and they are every column the Category Previous Contracts table
            // reads (viewDetailContract.blade.php:2253). A contracts row is 9,390 bytes wide,
            // so select * read the whole row for five printed cells.
            //
            // withoutGlobalScope('accessLevelSelect') is required, not tidiness: Contract::boot()
            // adds a global scope that calls select('*') and it runs after this select(), so it
            // overwrites it (app/Models/Contract.php:114). ContractRoledBasedScope stays - that
            // one is the visibility rule.
            //
            // without('contractPartyList') drops the $with eager load. The table shows no party
            // data, and the eager load is one more query.
            $contractsoldothers = Contract::withoutGlobalScope('accessLevelSelect')
                ->without('contractPartyList')
                ->select([
                    'id',
                    'contract_name',
                    'signing_date',
                    'currency',
                    'currency_value',
                    'fixed_date',
                    'contract_end_date',
                ])
                ->where([
                    ['catgoery_id', $contractsold->catgoery_id],
                    ['department_id', $contractsold->department_id],
                    ['contract_type', $contractsold->contract_type],
                ])->whereNot('id', $id)->get();
        }

        // The Other Contracts With Parties table: contracts that share a branch AND an external
        // party with this one. Five queries used to build it - two plucks read this contract's
        // branches and parties, two whereIns fanned out to every contract sharing them, and PHP
        // intersected the two lists before a fifth query turned the intersect into rows. The
        // party fan-out alone dragged every contract id across the wire (3,018 on this seed).
        //
        // One query now. The two whereIn subqueries ARE the intersect: id IN A AND id IN B.
        // Nothing is bound, so the table cannot silently go blank when 1,000 or more contracts
        // share a branch and a party - the whereIn 1,000-binding bug in CLAUDE.md. The subqueries
        // are ContractPartyData, which has no global scope, so no accessLevelSelect trap here.
        //
        // orderBy('id') keeps the render order the bound list produced, same as the two
        // family-tree queries below.
        //
        // with('contractParent'): the Other Contracts With Parties table reads
        // $contractsoldother->contractParent once per row, to decide whether to draw the Link
        // button (viewDetailContract.blade.php:2431). Lazy-loaded that was 55 of the 368 queries
        // this tab ran. The blade only tests it for truth, so one child row per contract is all
        // the eager load has to return.
        $contractspartsList = Contract::with('contractParent')->select('*')
            ->whereIn('id', ContractPartyData::select('custom_field_group_id')
                ->whereIn('contract_party_location_id', ContractPartyData::select('contract_party_location_id')
                    ->where('custom_field_group_id', $contracts->id)->where('contract_party_type', 'internal')))
            ->whereIn('id', ContractPartyData::select('custom_field_group_id')
                ->whereIn('contract_party_exe_id', ContractPartyData::select('contract_party_exe_id')
                    ->where('custom_field_group_id', $contracts->id)->where('contract_party_type', 'External')))
            ->where('id', '<>', $id)->where('status', 1)
            ->orderBy('id')
            ->get();

        $contractspartsList = $this->availableContracts($contractspartsList, true);


        //Get Parent Contracts
        // whereIn reads the ancestor walk as a subquery, so no id is bound and no id crosses the
        // wire. The rule is in CLAUDE.md: on this stack a whereIn with 1,000 or more bound values
        // returns zero rows with no error, and this table would go blank on a deep tree.
        //
        // Two queries became one. The walk used to run on its own and hand its ids back to PHP.
        //
        // orderBy('id') is not new behaviour, it is the old behaviour written down. A whereIn on a
        // bound list returned the rows in id order because that is the index order; the subquery
        // makes MariaDB read the walk first, which returns the nearest parent first. The table has
        // always printed the lowest id first, so the sort keeps the page as it is.
        //
        // The columns stay at select('*'). availableContracts() reads at least a dozen of them and
        // decides by isset(), so a column left out changes the rows it returns without saying so,
        // and the blade loops $contractsoldother->contractPartyList. Ticket 20's narrow select was
        // safe because that query has no availableContracts() pass; this one does.
        $contractsparentList = Contract::select('*')
            ->whereIn('id', $this->ancestorContractIds($id))
            ->orderBy('id')
            ->get();

        Log::debug('Contract detail page read the parent contracts', [
            'contract_id' => $id,
            'ancestors' => $contractsparentList->count(),
        ]);

        $contractsparentList = $this->availableContracts($contractsparentList, true);


        //Get Susequesnt Contracts
        // whereIn reads the family-tree walk as a subquery, the same shape as the Parent
        // Contracts query above, so no id is bound and no id crosses the wire. orderBy('id')
        // keeps the render order the bound list produced: a whereIn on a bound list read the
        // primary key in ascending order, and the subquery version follows the walk instead.
        $contractsSubseqList = Contract::select('*')
            ->whereIn('id', $this->subsequentContractIds($id))
            ->where('id', '<>', $id)->where('status', 1)
            ->orderBy('id')
            ->get();

        Log::debug('Contract detail page read the subsequent contracts', [
            'contract_id' => $id,
            'descendants' => $contractsSubseqList->count(),
        ]);

        $contractsSubseqList = $this->availableContracts($contractsSubseqList, true);

        return [
            'contractsoldothers' => $contractsoldothers,
            'contractsparentList' => $contractsparentList,
            'contractsSubseqList' => $contractsSubseqList,
            'contractspartsList' => $contractspartsList,
        ];
    }

    public function viewContract(Request $request, $id)
    {
        
        $contracts = Contract::select('*')->where('id', $id)->where('status', 1)->get();

        // availableContracts() writes decrypted names, formatted dates and label text back onto
        // the model it is given, so the row cannot be read raw again afterwards. Keep an
        // untouched copy now. It is what the live-contract branch further down reads, instead of
        // fetching the same row a second time. clone copies the attribute array by value, so the
        // decrypt pass below cannot reach it.
        $subjectContractRow = $contracts->first() ? clone $contracts->first() : null;

        $ContractsFinal = $this->availableContracts($contracts, true);

        // availableContracts() returns an empty list when the user may not see this contract,
        // and also when the contract points at a business unit that no longer exists. The line
        // below used to read element 0 of that empty list and the page threw
        // "Undefined array key 0". This redirect sat 122 lines further down, after the eSign
        // block had already run, so it never got the chance. It is the first thing now.
        if (count($ContractsFinal) == 0) {
            Log::warning('Contract detail page cannot show this contract', ['contract_id' => $id]);

            return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract/Access Restricted')->with('alert-class', 'alert-danger');
        }

        // ------------------------------------------------------------------
        // If contract is in Signing / Progress, look up the stored
        // eSign compose response, call getEasySignLinks to check status.
        // If all signed → download the signed PDF, update the contract
        // attachment, and move to Executed / Signed.
        // ------------------------------------------------------------------
        $easySignInfo = [];
        $contract = $ContractsFinal[0];
        if (
            strtolower($contract->contract_status ?? '') === 'signing'
            && strtolower($contract->substatus ?? '') === 'progress'
        ) {
            try {
                // Retrieve stored compose response
                $esignRecord = \App\Models\EsignResposnse::where('contract_id', $contract->id)
                    ->where('status', 1)
                    ->latest()
                    ->first();

                if ($esignRecord) {
                    $composeBody = json_decode($esignRecord->esignresponse, true);
                    $epakId      = $composeBody['data'] ?? null;
                    $metadata    = $composeBody['metadata'] ?? [];

                    if ($epakId) {
                        $esignCtrl = new \Modules\Contract\Http\Controllers\EsignApiController();

                        // Obtain a fresh token
                        $tokenRes  = $esignCtrl->getToken(new \Illuminate\Http\Request());
                        $tokenBody = json_decode($tokenRes->getContent(), true);
                        $msbToken  = $tokenBody['msb_token'] ?? ($tokenBody['access_token'] ?? null);
                        $tenantId  = 'APOLLO_HOSPITALS';

                        if ($msbToken) {
                            // Build a request with the required headers
                            $linksRequest = new \Illuminate\Http\Request();
                            $linksRequest->headers->set('access_token', $msbToken);
                            $linksRequest->headers->set('tenant_id', $tenantId);

                            $linksResponse = $esignCtrl->getEasySignLinks($linksRequest, $epakId);
                            $linksData     = json_decode($linksResponse->getContent(), true);

                            if (
                                isset($linksData['data'][0]['easySignInfo'])
                                && is_array($linksData['data'][0]['easySignInfo'])
                            ) {
                                $easySignInfo = $linksData['data'][0]['easySignInfo'];

                                // Check whether every signer has status "Signed"
                                $allSigned = count($easySignInfo) > 0 && collect($easySignInfo)->every(function ($info) {
                                    return strtolower($info['status'] ?? '') === 'signed';
                                });

                                if ($allSigned) {
                                    // Download signed PDF using metadata (filename => docId)
                                    $downloadedPath     = null;
                                    $downloadedFilename = null;

                                    foreach ($metadata as $filename => $docId) {
                                        $dlRequest = new \Illuminate\Http\Request();
                                        $dlRequest->headers->set('access_token', $msbToken);
                                        $dlRequest->headers->set('tenant_id', $tenantId);
                                        $dlRequest->merge([
                                            'docId'    => $docId,
                                            'filename' => $filename,
                                            'msb_token' => $msbToken,
                                            'tenant_id' => $tenantId,
                                        ]);

                                        $dlResponse = $esignCtrl->downloadDocument($dlRequest, $contract->id);
                                        $dlData     = json_decode($dlResponse->getContent(), true);

                                        if (!empty($dlData['success'])) {
                                            $downloadedPath     = $dlData['path'];
                                            $downloadedFilename = $dlData['filename'] ?? $filename;
                                        }
                                    }
                                    


                                    // Update contract attachment with the signed document
                                    $updateData = [
                                        'contract_status' => 'executed',
                                        'substatus'       => 'active',
                                    ];
                                    
                                    
                                    // ==== Update parent contract status to executed/renewed ====
                                    if ($contract->renewal_type == 'renewal' && !empty($contract->parentcontract)) {
                                        $parentContract = Contract::find($contract->parentcontract);
                                        if ($parentContract) {
                                            $updateDataPar = [
                                                'substatus' => 'renewed',
                                            ];
                                            $parentContract->update($updateDataPar);
                                            $this->createContractSnapshot($parentContract, 
                                                'Status changed to renewed — child renewal # (contract #' . $contract->id . ') signed'
                                            );
                                            \Log::info("Parent contract {$parentContract->id} substatus changed to 'renewed' and after {$contract->id} was signed");
                                        }
                                    }                                          

                                    if ($downloadedPath) {
                                        $updateData['contract_attachment']          = $downloadedPath;
                                        $updateData['contract_attachment_filename'] = $downloadedFilename;
                                    }
                                    
                                   
                                    //contract->update($updateData)->except(['contract_name_decrypted']);
                                    Contract::where('id', $contract->id)->update($updateData);                                        

                                    // Mark esign record as completed
                                    $esignRecord->update(['status' => 0]);

                                    $contract->refresh();
                                    $this->sendExecutedActiveNotifications($contract);
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Silently continue – the page should still render
                //echo ;
                \Log::error("Failed to Download Esigned File" . $e->getMessage()."--".$e->getLine());
                //die;
            }
        }

        if (env('update_doc_vars')) {
            $this->wordDocumentReaderActions($ContractsFinal[0], true, true, true);
            //die;
        }

        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();

        $contractTypes = ContractType::get();


        $contractHistory = ContractHistory::where('id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetching users with decrypted data
        $users = AddUsers::select(
            'id',
            decrypt_data('Salutation', 'AddUsers'),
            decrypt_data('FirstName', 'AddUsers'),
            decrypt_data('LastName', 'AddUsers'),
            decrypt_data('Email', 'AddUsers')
        )->get();
        
        $usersSel = AddUsersSel::select(
            'id',  
            decrypt_data('Salutation', 'AddUsers'), 
            decrypt_data('FirstName', 'AddUsers'), 
            decrypt_data('LastName', 'AddUsers'), 
            decrypt_data('AccessScope', 'AddUsers'), 
            decrypt_data('Email', 'AddUsers')
        )->get();
        $legalAdvisors = LegalAdvisor::where('status', 1)->orderBy('name')->get();
        
        $historyIds = [];
        // Attach user data to contract history
        foreach ($contractHistory as $history) {
            $historyIds[] = $history->created_by;
            
            $user = $users->where('id', $history->created_by)->first();
            if ($user) {
                $history->user_name = $user;
            } else {
                $ukUserObject = new stdClass();
                $ukUserObject->Salutation = '';
                $ukUserObject->FirstName = $history->created_by <= 0 ? 'External' : 'User';
                $ukUserObject->LastName = $history->created_by <= 0 ? 'User' : 'InActive';
                $history->user_name = $ukUserObject; // In case no matching user is found
            }
        }


        // ?history=<id> asks for a past version of this contract, and the Historical tab shows
        // it. The row can be gone - the id comes from a link the user kept, or from the
        // 60-minute cookie the blade writes - and then the page read contract_status off null and
        // threw. It falls back to the live contract, the same as a load with no ?history=.
        $contracts = null;
        $historyId = $_GET['history'] ?? '';

        if ($historyId !== '') {
            $contracts = ContractHistory::where('history_id', $historyId)->first();
            $contractParty = ContractPartyDataHistory::where('history_id', $historyId)->get();

            if ($contracts === null) {
                Log::warning('Contract detail page found no history snapshot', [
                    'contract_id' => $id,
                    'history_id' => $historyId,
                ]);
            }
        }

        if ($contracts === null) {
            // The snapshot is gone, so every later read shows the live contract too.
            $historyId = '';
            // The untouched copy taken at the top of this method, not a second read of the same
            // row. The query above it has already proved the row exists and has status 1,
            // because an empty result redirects long before this line.
            $contracts = $subjectContractRow;
            $contractParty = ContractPartyData::where('custom_field_group_id', $id)->get();
        }

        $approvalsArr = ApprovalContracts::select('*')->where('contract_id', $id)->orderBy('id', 'DESC')/*->where('flag', 1)*/
            ->where('flag', '<>', -1)
            // Pre-approval flow rows (review/negotiation/finalization) belong to the
            // Pre-Approval tab, not the timeline. Exclude them and any superseded rows.
            ->where('superseded', 0)
            ->where(function ($q) {
                $q->where('flow_type', '<>', 'preapproval')->orWhereNull('flow_type');
            })
            ->get()
            ->map(function ($task) {
                $task->username = decryptString($task->username, 'username');
                $task->status = decryptString($task->status, 'status');
                $task->previous_status = decryptString($task->previous_status, 'previous_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                $task->next_status = decryptString($task->next_status, 'next_status');
                $task->signed_png = $task->signed_png;
                $task->updated_on = $task->updated_on;
                // Decode username JSON
                $userData = json_decode($task->username, true);
                $task->approver_email = $userData['email'] ?? '';
                $task->approver_name = $userData['name'] ?? '';                
                return $task;
            })
            ->groupBy('unique_id')
            ->reverse();

        $approvalsArrExternal = ApprovalContracts::select('*')->where('contract_id', $id)->orderBy('id', 'DESC')/*->where('flag', 1)*/
            //->where('flag', -1)
            ->get()
            ->map(function ($task) {
                $task->username = decryptString($task->username, 'username');
                $task->status = decryptString($task->status, 'status');
                $task->previous_status = decryptString($task->previous_status, 'previous_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                $task->next_status = decryptString($task->next_status, 'next_status');
                // Decode username JSON
                $userData = json_decode($task->username, true);
                

                $task->approver_email = $userData['email'] ?? '';
                $task->approver_name = $userData['name'] ?? '';                
                return $task;
            })
            ->groupBy('unique_id')
            ->reverse();

        //Set Signed Symbol                                  
        $partySigned = [];
        $partyMails = [];
        $internalSigned = 0;
        if (in_array(strtolower($contracts->contract_status), ['executed', 'signing'])) {
            foreach ($approvalsArrExternal as $key => $approvalsData) {
                if ($approvalsData[0]->button_text == 'external') {
                    if ($approvalsData[0]->signed_png !== null) {
                        $partySigned[] = true;
                        $partyMails[] = false;
                    } else {
                        $partyMails[] = json_decode($approvalsData[0]->username, true);
                        $partySigned[] = false;
                        //die;
                    }
                } else {
                    if ($approvalsData[0]->button_text != 'external' && $approvalsData[0]->signed_png !== null) {
                        $internalSigned++;
                    }
                }
            }
        }

        $externalSigned = 0;
        
        if(strtolower($contracts->contract_status) == 'signing' && strtolower($contracts->substatus) == 'approved'){
            $this->crudUserActionLog($contracts->id, 'approval', 'signing-email', 0, 1, Helpers::userInfo()->email, true);
        }

        $branchFirst = [];

        // One read for all six entity names, instead of one query per party row. The loop below
        // asked for the same entity twice on the test contract.
        $entityNames = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'))
            ->get()
            ->keyBy('id');

        foreach ($contractParty as $contractPart) {
            $entities = $entityNames[$contractPart->contract_party_id] ?? null;

            if (isset($entities->Nameoftheentity)) {
                $contractPart->Nameoftheentity = $entities->Nameoftheentity;
            }

            $contractPart->signed = false;
            $contractPart->mails = false;
            if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal') {

                if (in_array(true, $partySigned) || $internalSigned > 0) {
                    $contractPart->signed = true;
                }
                
                if (empty($branchFirst)) {
                    $branchFirst = BranchUser::select(
                        'id',
                        decrypt_data('BranchName', 'branch'),
                        decrypt_data('branchstatus', 'branch'),
                        decrypt_data('Doorno', 'branch'),
                        decrypt_data('StreetName', 'branch'),
                        decrypt_data('AreaName', 'branch'),
                        decrypt_data('Landmark', 'branch'),
                        decrypt_data('PinCode', 'branch'),
                        decrypt_data('ContactNumber', 'branch'),
                        decrypt_data('branchheadname', 'branch'),
                        decrypt_data('departments', 'branch'),
                        decrypt_data('LegalName', 'branch')
                    )->where('id', $contractPart->contract_party_location_id)->first();


                    $contractPart->contract_party = $branchFirst;
                }
                $externalSigned++;
            }

            if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Intergroup') {

                if (isset($partySigned[$externalSigned])) {
                    $contractPart->signed = $partySigned[$externalSigned];
                    $contractPart->mails = $partyMails[$externalSigned];
                }
                $externalSigned++;
            }
            if ($contractPart->contract_party_exe_id == !null && $contractPart->contract_party_type == 'External') {

                $contractParties =  ContractParties::select('*')->where('id', $contractPart->contract_party_exe_id)->get();

                $contractPart->contract_party_id_exe = $contractParties;
                //Set Signed Symbol

                if (isset($partySigned[$externalSigned])) {
                    $contractPart->signed = $partySigned[$externalSigned];
                    $contractPart->mails = $partyMails[$externalSigned];
                }
                $externalSigned++;

                // ContractParties carries the PartiesRoleBasedScope global scope, so this read
                // comes back empty for a party the user may not see, and for a party row that no
                // longer exists. Element 0 of an empty collection threw and the page did not
                // render, so the name is empty instead.
                $externalParty = $contractParties->first();
                if ($externalParty === null) {
                    Log::warning('Contract detail page found no external party row', [
                        'contract_id' => $contracts->id,
                        'contract_party_exe_id' => $contractPart->contract_party_exe_id,
                    ]);
                }
                $contractPart->Nameoftheentity = $externalParty === null
                    ? ''
                    : decryptString($externalParty->company_name, 'company_name');
            }
        }

        // The three lookups below swap an id for the name the page prints. Each one threw when
        // the row was missing, and then the page did not render at all: an id that points at a
        // deleted category, business or contract type is enough to stop it. The id stays in the
        // column beside it, so a missing name now shows as empty and the page still loads.
        if (isset($contracts->catgoery_id)) {
            $Categoryname = ContractCategories::where('id', $contracts->catgoery_id)->first();
            $contracts->catgoery_identity = $contracts->catgoery_id;
            $contracts->catgoery_id = $Categoryname->name ?? '';
            if ($Categoryname === null) {
                Log::warning('Contract detail page found no contract category row', [
                    'contract_id' => $contracts->id,
                    'catgoery_id' => $contracts->catgoery_identity,
                ]);
            }
        }

        if (isset($contracts->department_id)) {
            $EntityBusinessName = EntityBusiness::where('id', $contracts->department_id)->first();
            $contracts->department_identity = $contracts->department_id;
            $contracts->department_id = $EntityBusinessName->name ?? '';
            if ($EntityBusinessName === null) {
                Log::warning('Contract detail page found no entity business row', [
                    'contract_id' => $contracts->id,
                    'department_id' => $contracts->department_identity,
                ]);
            }
        }

        if (isset($contracts->contract_type)) {
            $contracts->contract_type_id = $contracts->contract_type;
            $contractTypeRow = ContractType::where('contract_type_id', $contracts->contract_type)->first();
            $contracts->contract_type = $contractTypeRow->contract_type ?? '';
            if ($contractTypeRow === null) {
                Log::warning('Contract detail page found no contract type row', [
                    'contract_id' => $contracts->id,
                    'contract_type_id' => $contracts->contract_type_id,
                ]);
            }
        }

        $approvalsAttach = ApprovalContracts::select('*')
            ->where('contract_id', $id)
            ->where('flag', 1)
            ->orderBy('id', 'desc') // Order by created_at in descending order
            ->get()
            ->map(function ($task) {
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                // Decode username JSON
                $userData = json_decode($task->username, true);
                

                $task->approver_email = $userData['email'] ?? '';
                $task->approver_name = $userData['name'] ?? '';                
                return $task;
            });

        $approvalsHistory = ApprovalContracts::select('*')
            ->where('contract_id', $id)
            ->where('flag', 0)
            ->orderBy('id', 'desc') // Order by created_at in descending order
            ->get()
            ->map(function ($task) {
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_status = decryptString($task->next_status, 'next_status');
                $task->status = decryptString($task->status, 'status');
                $task->username = decryptString($task->username, 'username');
                $task->created_by = $task->created_by;
                $task->updated_by = $task->updated_by;
                $task->updated_at = $task->updated_at;
                $task->updated_on = $task->updated_on;
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                // Decode username JSON
                $userData = json_decode($task->username, true);
                

                $task->approver_email = $userData['email'] ?? '';
                $task->approver_name = $userData['name'] ?? '';                
                return $task;
            });

        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();
        // $categorys used to be read here and handed to the view. No blade this page renders
        // reads it; the pages that do - createfield, partyCustomField and the Contractsetup
        // views - are fed by their own controllers.
        $contractTypes = ContractType::get();

        $branchs = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $branchsAll = Branch::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $entities = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'))
            ->get();


        $contractParties =  ContractParties::select('*')->get();

        $catego =  ContractCategories::select('*')->get();

        $ent = EntityBusiness::select('*')->get();


        if ($historyId !== '') {
            $ContractPartyData = ContractPartyDataHistory::where('history_id', $historyId)->get();
        } else {
            $ContractPartyData = ContractPartyData::where('custom_field_group_id', $id)->get();
        }


        // The four Related Contracts tables only render on the Details tab. Three whole-table
        // scans fill them and they cost about 3,400 ms, so every other tab used to pay for a
        // region it never shows. contract_detail_current_tab() and
        // contract_detail_shows_related_contracts() hold the rule, and the blade reads the same
        // two helpers. The empty lists keep the blade loops safe on every other tab.
        $currentTab = contract_detail_current_tab($contracts);

        $relatedContracts = [
            'contractsoldothers' => collect(),
            'contractsparentList' => collect(),
            'contractsSubseqList' => collect(),
            'contractspartsList' => collect(),
        ];

        if (contract_detail_shows_related_contracts($currentTab)) {
            $relatedContracts = $this->relatedContractLists($id, $contracts);
        } else {
            Log::debug('Contract detail page skips the Related Contracts queries', [
                'contract_id' => $id,
                'tab' => $currentTab,
            ]);
        }

        $contractsoldothers = $relatedContracts['contractsoldothers'];
        $contractsparentList = $relatedContracts['contractsparentList'];
        $contractsSubseqList = $relatedContracts['contractsSubseqList'];
        $contractspartsList = $relatedContracts['contractspartsList'];


        // Required fields with labels
        $reqfieldsText = [
            'currency_value' => 'Contract Value',
            'payment_schedule' => 'Contract Value: Payment Schedule',
            'fixed_date' => 'Fixed Date',
            'contract_end_date' => 'Contract End Date',
            'termination_date' => 'Termination - Date',
            'signing_date' => 'Signing Date',
            'end_contract_type' => 'Contract Type',
            'contract_priority' => 'Contract Priority'
        ];

        $reqFieldsOptions = [
            'text' => [
                'end_contract_type' => 'One time,Fixed Term,Evergreen',
                'contract_priority' => 'Low,Medium,High'
            ],
            'value' => [
                'end_contract_type' => 'onetimeContract,fixedTerm,evergreen',
                'contract_priority' => 'low,medium,high'
            ]
        ];
        $reqfieldsInpType = [
            'currency_value' => 'text',
            'payment_schedule' => 'text',
            'fixed_date' => 'date',
            'contract_end_date' => 'date',
            'termination_date' => 'date',
            'signing_date' => 'date',
            'end_contract_type' => 'radio',
            'contract_priority' => 'select'
        ];

        $reqfieldsInpField = [
            'currency_value' => 'approvalInps',
            'payment_schedule' => 'approvalInps',
            'fixed_date' => 'approvalInps',
            'contract_end_date' => 'approvalInps',
            'termination_date' => 'approvalInps',
            'signing_date' => 'approvalInps',
            'end_contract_type' => 'approvalInps',
            'contract_priority' => 'approvalInps'
        ];

        $reqfieldsInpEdit = [
            'currency_value' => false,
            'payment_schedule' => false,
            'fixed_date' => false,
            'contract_end_date' => false,
            'termination_date' => false,
            'signing_date' => false,
            'end_contract_type' => false,
            'contract_priority' => false
        ];

        $reqfieldsVal = [
            'fixed_date' => true,
            'contract_end_date' => true,
            'end_contract_type' => true
        ];

        // Dynamically add fields based on specific conditions
        $commencement_type = decryptString($contracts->commencement_type, 'commencement_type');
        $end_contract_type = decryptString($contracts->end_contract_type, 'end_contract_type');
        $contract_value = decryptString($contracts->currency_value, 'currency_value');
        $payment_schedule = decryptString($contracts->payment_schedule, 'payment_schedule');

        $reqfieldsVals = [
            'currency_value' => $contract_value,
            'payment_schedule' => $payment_schedule,
            'fixed_date' => $contracts->fixed_date,
            'contract_end_date' => $contracts->contract_end_date,
            'termination_date' => $contracts->termination_date,
            'signing_date' => $contracts->signing_date,
            'end_contract_type' => $end_contract_type,
            'contract_priority' => $contracts->contract_priority
        ];

        if ($commencement_type == 'FixedDate' && empty($contracts->fixed_date)) {
            $reqfieldsVal['fixed_date'] = false;
        }

        if ($end_contract_type == 'onetimeContract' && empty($contracts->contract_end_date)) {
            $reqfieldsVal['contract_end_date'] = false;
        }

        if ($end_contract_type == 'fixedTerm' && empty($contracts->contract_end_date)) {
            $reqfieldsVal['contract_end_date'] = false;
        }
        
        if ($end_contract_type == 'evergreen' && empty($contracts->contract_end_date)) {
            unset($reqfieldsVal['contract_end_date']);
        }

        if ($end_contract_type == 'termination' && empty($contracts->termination_date)) {
            $reqfieldsVal['termination_date'] = false;
        }

        // if (empty($contract_value) && $reqfieldsVal['currency_value']) {
        //     $reqfieldsVal['currency_value'] = false;
        // }

        if ((strtolower($contracts->contract_status) == 'signing')) {
            $reqfieldsVal['signing_date'] = empty($contracts->signing_date) ? false : true;
            $reqfieldsVals['signing_date'] = 'signing_date';
            $reqfieldsInpEdit['signing_date'] = !$reqfieldsVal['signing_date'];
        }

        $skipValidationReqInps = ['negotiation', 'signing', 'approval'];
        $enableEditReqInps = ['negotiation', 'approval'];


        foreach ($customFields as $cusField) {
            
            if ($cusField->required == 1 && $cusField->contract_type == $contracts->contract_type_id) {
                $customFieldReq = true;
                if (!empty(dataCustomFields($contracts->id, $cusField->custom_field_id))) {
                    $customFieldReq = false;
                }
                $reqfieldsVal[$cusField->custom_field_id] = $customFieldReq;
                $reqfieldsText[$cusField->custom_field_id] = $cusField->field_name;
                $reqfieldsInpType[$cusField->custom_field_id] = $cusField->field_type;
                $reqfieldsVals[$cusField->custom_field_id] = (string) dataCustomFields($contracts->id, $cusField->custom_field_id);
                $reqfieldsInpField[$cusField->custom_field_id] = 'customFields';
                $reqfieldsInpEdit[$cusField->custom_field_id] = true;
            }
        }

        if (!in_array(strtolower($contracts->contract_status), $skipValidationReqInps)) {
            $reqfieldsVal = [];
        }

        if (in_array(strtolower($contracts->contract_status), $enableEditReqInps)) {
            $reqfieldsInpEdit['fixed_date'] = true;
            $reqfieldsInpEdit['contract_end_date'] = true;
            $reqfieldsInpEdit['termination_date'] = true;
            if (env('enable_contract_priority')) {
                $reqfieldsVal['contract_priority'] = true;
            }
        }


        $ContractObligations = ContractObligations::where('contract_id', $id)->where('flag', 1)
            ->get();
            
        // $signedHistory used to be read here - getSignedHistory() runs an unindexed read of
        // user_action_log - and handed to the view. Nothing in the repo reads the name: no
        // blade, no PHP, no JS. getSignedHistory() itself stays; it has other callers.
        
        // Get approvals (timeline). Exclude pre-approval flow rows (shown on the
        // Pre-Approval tab) and superseded rows.
        $approvals = ApprovalContracts::select('*')->where('contract_id', $id)->orderBy('id', 'DESC')
            ->where('flag', '<>', -1)
            ->where('superseded', 0)
            // ->where(function ($q) {
            //     $q->where('flow_type', '<>', 'preapproval')->orWhereNull('flow_type');
            // })
            ->get()
            ->map(function ($task) {
                $task->username = decryptString($task->username, 'username');
                $task->status = decryptString($task->status, 'status');
                $task->previous_status = decryptString($task->previous_status, 'previous_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                $task->next_status = decryptString($task->next_status, 'next_status');
                // Decode username JSON
                $userData = json_decode($task->username, true);
                $task->approver_email = $userData['email'] ?? '';
                $task->approver_name = $userData['name'] ?? '';
                return $task;
            });

        // Chart View reads the same rows as the timeline above. The query and the
        // decrypt loop were written out a second time here, word for word, so the
        // page paid for both twice. Reuse the result. The two lists hold the same
        // rows either way, and contractFlow.blade.php only reads them.
        $chartApprovals = $approvals;

        $userInfo = Helpers::userInfo();

        // $currentApproval and $isCurrentApprover were worked out here and handed to the view.
        // No blade this page renders reads either name. The two blades in the repo that do -
        // contract-custom/approvals/view.blade.php - are rendered by ContractCustomController,
        // which builds its own copy. Checked across every blade, every module and every JS file.

        // Locked groups: any group that has at least one approved completed member
        $lockedGroups = ApprovalContracts::select('*')->where('contract_id', $id)->get()->filter(function($a){
            try {
                return (function_exists('decryptString') ? @decryptString($a->approval_status, 'approval_status') : $a->approval_status) === 'approved' && (int)$a->flag === 0;
            } catch (\Throwable $e){
                return false;
            }
        })->pluck('approver_type_row')->unique()->values()->toArray();        

        // $attachmentUrl was built here and handed to the view. No blade this page renders reads
        // it. The three that do - contract-custom/approvals/view, contracts/negotiationReview and
        // legal-review/show - are rendered by other controllers, which build their own.

        $userCanGate = $this->approvalActorIsOwnerOrAdmin($contracts);

        // The list is worked out exactly as before and only its count is read. It is no longer
        // handed to the view as $waitingGateGroupIds, because no blade reads that name. The
        // query is left alone on purpose: it is one query either way, so rewriting it as an
        // exists() would buy nothing and could change which rows count.
        $waitingGateGroupIds = ApprovalContracts::where('contract_id', $id)
            ->where('awaiting_owner_trigger', 1)
            ->pluck('unique_id')
            ->filter()
            ->unique()
            ->values();
        $canAdvanceNext = $userCanGate && $waitingGateGroupIds->count() > 0;
        $externalRepresentativeOptions = $userCanGate ? $this->getExternalRepresentativeOptions($id) : collect();
        $dynamicApproverOptions = $userCanGate
            ? AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => (int)($user->id ?? 0),
                        'name' => trim(((string)($user->FirstName ?? '')) . ' ' . ((string)($user->LastName ?? ''))),
                        'email' => (string)($user->Email ?? ''),
                    ];
                })
                ->filter(function ($user) {
                    return !empty($user['email']);
                })
                ->values()
            : collect();
        
        $preApprovalSteps = ApprovalContracts::where('contract_id', $contract->id)
            ->where('flow_type', 'preapproval')
            ->orderBy('orderval', 'asc')
            ->get()
            ->map(function ($step) {
                // Expose approver identity for the blade. Keep encrypted fields
                // (approval_status, next_action_description) intact because the
                // pre-approval blade decrypts them itself.
                $userData = [];
                try {
                    $userData = json_decode(decryptString($step->username, 'username'), true) ?: [];
                } catch (\Throwable $e) {
                    $userData = [];
                }
                $step->approver_email = $userData['email'] ?? '';
                $step->approver_name = $userData['name'] ?? '';
                return $step;
            });

        return view('contract::contract.viewDetailContract', compact('branchFirst', 'reqfieldsVal', 'reqfieldsText', 'reqfieldsVals', 'reqfieldsInpType', 'reqfieldsInpField', 'reqFieldsOptions', 'reqfieldsInpEdit', 'contractHistory', 'approvalsAttach', 'contractParties', 'contractspartsList', 'contractsparentList', 'contractsSubseqList', 'contractsoldothers', 'ent', 'catego', 'contractParties', 'entities', 'branchs', 'branchsAll', 'customFields', 'contractTypes', 'users', 'usersSel','approvals', 'chartApprovals', 'userInfo', 'lockedGroups', 'legalAdvisors', 'preApprovalSteps'))->with('contractPartys', $ContractPartyData)
            ->with('contract', $contracts)
            ->with('contractPartyData', $contractParty)
            ->with('approvalsArr', $approvalsArr)
            ->with('approvalsHistory', $approvalsHistory)
            ->with('ContractObligations', $ContractObligations)
            ->with('userCanGate', $userCanGate)
            ->with('canAdvanceNext', $canAdvanceNext)
            ->with('externalRepresentativeOptions', $externalRepresentativeOptions)
            ->with('dynamicApproverOptions', $dynamicApproverOptions);
    }
    
    public function signContract(Request $request, $id){
        
        //if(env('test_digital_sign')){
            
            $file_path = 'contracts/tempDocs/';

            $contracts = Contract::select('*')->where('id', $id)->get();
    
            $ContractsFinal = $this->availableContracts($contracts, true);
    
            if (count($ContractsFinal) == 0) {
                return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract Access')->with('alert-class', 'alert-danger');
            }
            
            $javaJar = base_path() . '/storage/app/' . $file_path .'esign.jar';
            
            if(!file_exists($javaJar)){
                return redirect("/contracts/$id?tab=timeline")->with('message', 'Oops! Signing Configuration Missing')->with('alert-class', 'alert-danger');
            }
            
            if ($request->input('approvalInps') && $request->input('approvalInps') !== null) {
                Contract::where(['id' => $id])->update($request->input('approvalInps'));
            }            
            
            $controller =  fileStorageTypeController();
            
            $contracts = $ContractsFinal[0];
        
            $counterParties = $contracts->contractPartyList->all();
            
            $appId = $request->input('appId');
            
            $unlinkCloudFile = false;
            
            $approvalsSigned = ApprovalContracts::select('id', 'username', 'status', 'previous_status', 'contract_id', 'next_action_item', 'next_action_description', 'button_text', 'attachments', 'approval_status', 'updated_at', 'created_at', 'orderval', 'unique_id', 'flag')
                            ->where('id', $appId)
                            ->first();
            
            $currentSignerEmail = json_decode(decryptString($approvalsSigned->username, 'username'))->email;
            $currentSignerName = json_decode(decryptString($approvalsSigned->username, 'username'))->name;
            
            $this->crudUserActionLog($id, 'approval', 'internal-signed', $appId, 1, $currentSignerEmail, false, $currentSignerName,  '');
            
            if(strtolower(pathinfo($contracts->contract_attachment_filename, PATHINFO_EXTENSION)) == 'docx'){

                if (fileStorageType() != "Local") {
                    
                    $file_name = 'doc_' . strtotime(date('y-m-d h:i:s')) . '.docx';
                    
                    $contentDocx = $controller->downloadUrl($contracts->contract_attachment, $file_name);
                    
                    $filePath = Storage::disk('local')->put($file_path . $file_name, $contentDocx);
                    
                    $unlinkCloudFile = true;
                    
                    $storedWordFile = base_path() . '/storage/app/' . $file_path . $file_name;
                    
                }else{
                    $file_name = $contracts->contract_attachment_filename;
                    $storedWordFile = base_path() . '/storage/app/' . $contracts->contract_attachment;
                }
            
                
                $htmlDoc = $this->convertWordToHtmlBuffer($storedWordFile);
                
                if($unlinkCloudFile){
                    unlink($storedWordFile);
                }
                
                $generatedPdfDocumentFinalName = 'prepared_sign_doc_'.$contracts->contract_unique_id.'.pdf';
                
                $pdf = new EsignPdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                
                $pdf->AddPage();
                
                $pdf->writeHTML($htmlDoc, true, false, true, false, '');

                $doc_path = base_path() . '/storage/app/' . $file_path . $generatedPdfDocumentFinalName;
                
                $file = $pdf->my_output($doc_path, 'F');
                
                //$pdf_byte_range = $pdf->pdf_byte_range;
                
                $pdf->_destroy();
            
            }else{
                
                if (fileStorageType() != "Local") {
                    
                    $file_name = 'prepared_sign_doc_'.$contracts->contract_unique_id.'.pdf';
                    
                    $contentDocx = $controller->downloadUrl($contracts->contract_attachment, $file_name);
                    
                    $filePath = Storage::disk('local')->put($file_path . $file_name, $contentDocx);
                    
                    $doc_path = base_path() . '/storage/app/' . $file_path . $file_name;
                    
                    $unlinkCloudFile = true;
                    
                }else{
                    $file_name = $contracts->contract_attachment_filename;
                    
                    $doc_path = base_path() . '/storage/app/' . $contracts->contract_attachment;
                }
                
                $generatedPdfDocumentFinalName = $file_name;
                
                
            }
            
            $inputPdf = $doc_path;
            
            // echo $inputPdf;
            
            // die;
            
            $reason = 'Document Approval';
            
            $outputPdf = base_path() . '/storage/app/' . $file_path .'sign_holder_added_doc_'.$contracts->contract_unique_id.'.pdf';
            
            $javaJar = base_path() . '/storage/app/' . $file_path .'esign.jar';
            
            // Escape arguments to prevent shell injection
            $signActionEsc = escapeshellarg('generate-hash');
            $inputPdfEsc = escapeshellarg($inputPdf);
            $outputPdfEsc = escapeshellarg($outputPdf);
            $signerNameEsc = escapeshellarg($currentSignerName);
            $signerLocationEsc = escapeshellarg($reason);
            $typeCount = escapeshellarg(count($counterParties));
            
            // Command to run the JAR
            $command = "java -jar $javaJar $signActionEsc $inputPdfEsc $outputPdfEsc $signerNameEsc $signerLocationEsc $typeCount";
            
            // Run it
            $output_array = [];
            
            $file_hash = exec($command, $output_array, $return_code);
            
        // echo "Last line of output: " . $file_hash . "<br/>";
        
        // echo "All output lines:<br/>";
        // foreach ($output_array as $line) {
        //     echo $line . "<br/>";
        // }
        
        // echo "Return code: " . $return_code . "<br/>";  
        
        // echo $file_hash;
        //     die;
            if($unlinkCloudFile){
                unlink($inputPdf);
            }
            $doc = new \DOMDocument();
    
            //Config Start
            $cdacKey = base_path() . '/storage/app/certs/'.env('esign_private_key');
            $ESIGN_URL = env('esign_api_link_url');
            $ASPID = env('esign_api_link_id');
            
            $RESPONSE_URL = url('/contracts/setasign/' . $id);
            //Config End
            
    
            //randome number gerator rand(1,9)
            $txn = rand(111111111111,999999999999)."----".$appId."----".Helpers::userInfo()->id; //$pdf_byte_range signature space location
            
            $minus5min = strtotime('-5 minutes');
            
            $ts = date('Y-m-d\TH:i:s', $minus5min);
            
            $doc_info = $generatedPdfDocumentFinalName;
            
            $xmlstr = '<Esign AuthMode="1" aspId="' . $ASPID . '" ekycId="" ekycIdType="A" responseSigType="pkcs7" responseUrl="' . $RESPONSE_URL . '" sc="y" ts="' . $ts . '" txn="' . $txn . '" ver="2.1"><Docs><InputHash docInfo="' . $txn . '" hashAlgorithm="SHA256" id="1">' . $file_hash . '</InputHash></Docs></Esign>';
            
            $doc->loadXML($xmlstr); //parser
            
            // Create a new Security object 
            $objDSig = new \App\Helpers\XMLSecurityDSig();
            // Use the c14n exclusive canonicalization
            $objDSig->setCanonicalMethod(\App\Helpers\XMLSecurityDSig::C14N);
            // Sign using SHA-256
            $objDSig->addReference(
                    $doc,
                    \App\Helpers\XMLSecurityDSig::SHA1,
                    array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
                    array('force_uri' => true)
            );
            
            // Create a new (private) Security key
            $objKey = new \App\Helpers\XMLSecurityKey(\App\Helpers\XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
            
            //If key has a passphrase, set it using
            $objKey->passphrase = '';
            
            if (!file_exists($cdacKey)) {
              echo "Esign Certificate Not Exist";
              die;
            }
            // Load the private key
            $objKey->loadKey($cdacKey, TRUE);
            
            // Sign the XML file
            $objDSig->sign($objKey);
            // Append the signature to the XML
            $objDSig->appendSignature($doc->documentElement);
            
            $signXML = $doc->saveXML();
            $signXML = str_replace('<?xml version="1.0"?>', '', $signXML);
            
            
            
            return view('contract::contract.esign', compact('signXML', 'txn', 'ESIGN_URL'));
    }
    
    public function signExContract(Request $request, $id){
        
            //$id = $request->input('contactId');
            
            $updateHistory = false;
    
            $currentSign = $request->input('currentSign');
    
            $encId = $id;
    
            $id_ = $this->checkExternalUser($id, false);
    
            if (!$id_) {
                return response()->json(['success' => false, 'message' => 'Invalid Access'], 200);
            }else{
                $id = $id_[0];
                $emailCheck = $id_[1];
            }
    
            $contracts = Contract::select('*')->where('id', $id)->get();
    
            if (count($contracts) == 0) {
                return response()->json(['message' => 'Invalid Contract'], 200);
            } else {
                $contracts = $contracts[0];
            }
            
            $file_path = 'contracts/tempDocs/';

            
            $controller =  fileStorageTypeController();
        
            $counterParties = $contracts->contractPartyList->all();
            
            $appId = $request->input('appId');
            
            $unlinkCloudFile = false;
            
            $approvalsSigned = ApprovalContracts::select('id', 'username', 'status', 'previous_status', 'contract_id', 'next_action_item', 'next_action_description', 'button_text', 'attachments', 'approval_status', 'updated_at', 'created_at', 'orderval', 'unique_id', 'flag')
                            ->where('id', $appId)
                            ->first();
            
            $currentSignerEmail = json_decode(decryptString($approvalsSigned->username, 'username'))->email;
            $currentSignerName = json_decode(decryptString($approvalsSigned->username, 'username'))->name;
            // $this->crudUserActionLog($id, 'approval', 'external-signed', $appId, 1, $currentSignerEmail, false, $currentSignerName,  '');
            // $this->crudUserActionLog($id, 'approval', 'external-signed', 0, 0, $currentSignerEmail, false, '', $signPngLoc);
            
            if(strtolower(pathinfo($contracts->contract_attachment_filename, PATHINFO_EXTENSION)) == 'docx'){

                if (fileStorageType() != "Local") {
                    
                    $file_name = 'doc_' . strtotime(date('y-m-d h:i:s')) . '.docx';
                    
                    $contentDocx = $controller->downloadUrl($contracts->contract_attachment, $file_name);
                    
                    $filePath = Storage::disk('local')->put($file_path . $file_name, $contentDocx);
                    
                    $unlinkCloudFile = true;
                    
                    $storedWordFile = base_path() . '/storage/app/' . $file_path . $file_name;
                    
                }else{
                    $file_name = $contracts->contract_attachment_filename;
                    $storedWordFile = base_path() . '/storage/app/' . $contracts->contract_attachment;
                }
            
                
                $htmlDoc = $this->convertWordToHtmlBuffer($storedWordFile);
                
                if($unlinkCloudFile){
                    unlink($storedWordFile);
                }
                
                $generatedPdfDocumentFinalName = 'prepared_sign_doc_'.$contracts->contract_unique_id.'.pdf';
                
                $pdf = new EsignPdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                
                $pdf->AddPage();
                
                $pdf->writeHTML($htmlDoc, true, false, true, false, '');

                $doc_path = base_path() . '/storage/app/' . $file_path . $generatedPdfDocumentFinalName;
                
                $file = $pdf->my_output($doc_path, 'F');
                
                //$pdf_byte_range = $pdf->pdf_byte_range;
                
                $pdf->_destroy();
            
            }else{
                
                if (fileStorageType() != "Local") {
                    
                    $file_name = 'prepared_sign_doc_'.$contracts->contract_unique_id.'.pdf';
                    
                    $contentDocx = $controller->downloadUrl($contracts->contract_attachment, $file_name);
                    
                    $filePath = Storage::disk('local')->put($file_path . $file_name, $contentDocx);

                    $doc_path = base_path() . '/storage/app/' . $file_path . $file_name;
                    
                    $unlinkCloudFile = true;
                    
                }else{
                    $file_name = $contracts->contract_attachment_filename;
                    
                    $doc_path = base_path() . '/storage/app/' . $contracts->contract_attachment;
                }
                
                $generatedPdfDocumentFinalName = $file_name;
                
            }
            
            $inputPdf = $doc_path;
            
            $reason = 'Document Approval';
            
            $outputPdf = base_path() . '/storage/app/' . $file_path .'sign_holder_added_doc_'.$contracts->contract_unique_id.'.pdf';
            
            $javaJar = base_path() . '/storage/app/' . $file_path .'esign.jar';
            
            //for($i=0; $i<count($counterParties); $i++){
                // Escape arguments to prevent shell injection
                $signActionEsc = escapeshellarg('generate-hash');
                $inputPdfEsc = escapeshellarg($inputPdf);
                $outputPdfEsc = escapeshellarg($outputPdf);
                $signerNameEsc = escapeshellarg($currentSignerName);
                $signerLocationEsc = escapeshellarg($reason);
                $typeCount = escapeshellarg(count($counterParties));
                
                // Command to run the JAR
                $command = "java -jar $javaJar $signActionEsc $inputPdfEsc $outputPdfEsc $signerNameEsc $signerLocationEsc $typeCount";
                
                // Run it
                
                $output_array = [];
                
                $file_hash = exec($command, $output_array, $return_code);
                
            if($unlinkCloudFile){
                unlink($inputPdf);
            }                
                
                //echo $file_hash."<br/>";
           //}
            //die;
            $doc = new \DOMDocument();
    
            //Config Start
            $cdacKey = base_path() . '/storage/app/certs/'.env('esign_private_key');
            $ESIGN_URL = env('esign_api_link_url');
            $ASPID = env('esign_api_link_id');
            
            $RESPONSE_URL = url('/contracts/external/setasign/' . $encId);
            //Config End
    
            //randome number gerator rand(1,9)
            $txn = rand(111111111111,999999999999)."----".$appId."----".'external'; //$pdf_byte_range signature space location
            
            $minus5min = strtotime('-5 minutes');
            
            $ts = date('Y-m-d\TH:i:s', $minus5min);
            
            $doc_info = $generatedPdfDocumentFinalName;
            
            $xmlstr = '<Esign AuthMode="1" aspId="' . $ASPID . '" ekycId="" ekycIdType="A" responseSigType="pkcs7" responseUrl="' . $RESPONSE_URL . '" sc="y" ts="' . $ts . '" txn="' . $txn . '" ver="2.1"><Docs><InputHash docInfo="' . $txn . '" hashAlgorithm="SHA256" id="1">' . $file_hash . '</InputHash></Docs></Esign>';
            
            $doc->loadXML($xmlstr); //parser
            
            // Create a new Security object 
            $objDSig = new \App\Helpers\XMLSecurityDSig();
            // Use the c14n exclusive canonicalization
            $objDSig->setCanonicalMethod(\App\Helpers\XMLSecurityDSig::C14N);
            // Sign using SHA-256
            $objDSig->addReference(
                    $doc,
                    \App\Helpers\XMLSecurityDSig::SHA1,
                    array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
                    array('force_uri' => true)
            );
            
            // Create a new (private) Security key
            $objKey = new \App\Helpers\XMLSecurityKey(\App\Helpers\XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
            
            //If key has a passphrase, set it using
            $objKey->passphrase = '';
            
            if (!file_exists($cdacKey)) {
              echo "Esign Certificate Not Exist";
              die;
            }
            // Load the private key
            $objKey->loadKey($cdacKey, TRUE);
            
            // Sign the XML file
            $objDSig->sign($objKey);
            // Append the signature to the XML
            $objDSig->appendSignature($doc->documentElement);
            
            $signXML = $doc->saveXML();
            $signXML = str_replace('<?xml version="1.0"?>', '', $signXML);
            
            
            
            return view('contract::contract.esign', compact('signXML', 'txn', 'ESIGN_URL'));
    }
    
    public function setupSignaturePdf(Request $request, $id)
    {
        
        $file_path = 'contracts/tempDocs/';
        
        $storagePath = '/storage/app/';
        
        $currentContract = Contract::find($id);

        $generatedPdfDocumentFinalName = 'sign_holder_added_doc_'.$currentContract->contract_unique_id.'.pdf';
        
        $generatedSignedPdfDocument = 'signed_'.$currentContract->contract_unique_id.'.pdf';
        
        $unsigned_file_path = base_path() . '/storage/app/' . $file_path . $generatedPdfDocumentFinalName;
        
        $signed_file_path = base_path() . '/storage/app/' . $file_path .$generatedSignedPdfDocument;
        
        
        $xmldata = (array) simplexml_load_string(filter_input(INPUT_POST, 'eSignResponse')) or die("Failed to load");
        // echo '<pre>';
        // print_r($xmldata);
        
        EsignResposnse::create([
            'contract_id' => $id,
            'approval_id' => $id,
            'esignresponse' => json_encode($xmldata),
            'status' => '0'
        ]);
        
        if ($xmldata["@attributes"]["errCode"] != 'NA') {
            $msg = $xmldata ["@attributes"]["errMsg"];
            if(empty($msg)){
                $msg ='eSign Request Canceled.[#'.$xmldata["@attributes"]["errCode"].']';
            }
            print($msg);
            exit();
        }
        
        //$unsigned_file = file_get_contents($unsigned_file_path);
        
        $txn = $xmldata ["@attributes"]["txn"];
        $txn_array = explode('----', $txn);
        $appId = $txn_array[1] ?? 0;
        $userId = $txn_array[2] ?? 0;
        
        if($appId != 0 && $userId != 0){
            $currentApproval = ApprovalContracts::where('id',$appId)->get()
                    ->map(function ($task) {
                        $task->username = decryptString($task->username, 'username');
                        $task->status = decryptString($task->status, 'status');
                        $task->previous_status = decryptString($task->previous_status, 'previous_status');
                        $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                        $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                        $task->approval_status = decryptString($task->approval_status, 'approval_status');
                        return $task;
                    });
            
            if(count($currentApproval) == 1){
                $currFlag = $currentApproval[0]->flag;
                if($currFlag == 0){
                    //echo "Already Signed";
                    return redirect('contracts/' . $currentApproval->contract_id . '?tab=timeline')->withErrors(['Already Signed'])->withInput();
                    die;                
                }
            }else{
                //echo "Invalid Approval";
                return redirect('contracts/' . $currentApproval->contract_id . '?tab=timeline')->withErrors(['Invalid Approval'])->withInput();
                die;
            }
        }else{
            //echo "Invalid Request";
            return redirect('contracts/' . $currentApproval->contract_id . '?tab=timeline')->withErrors(['Invalid Approval'])->withInput();
            die;
        }
        
        $pkcs7 = (array) $xmldata['Signatures'];
        $pkcs7_value = $pkcs7['DocSignature'];
        $cer_value = $xmldata['UserX509Certificate'];
        
        
        $beginpem = "-----BEGIN CERTIFICATE-----\n";
        $endpem = "-----END CERTIFICATE-----\n";
        $pemdata = $beginpem . trim($cer_value) . "\n" . $endpem;
        
        
        $outputPdf = '';
        
        $cleaned = str_replace("\\", "", $pkcs7_value);
        $cleaned = preg_replace('/\s+/', '', $cleaned);
        
        // Step 2: Ensure it's valid Base64 (optional check)
        if (base64_decode($cleaned, true) === false) {
            die("❌ Invalid Base64 after cleaning");
        } 
        
        $pkcs7_value = $cleaned;

        
        $javaJar = base_path() . '/storage/app/' . $file_path .'esign.jar';
        
        // Escape arguments to prevent shell injection
        $signActioEsc = escapeshellarg('apply-signature');
        $inputPdfEsc = escapeshellarg($unsigned_file_path);
        $outputPdfEsc = escapeshellarg($signed_file_path);
        $pkcs7Esc = escapeshellarg($pkcs7_value);
        $partyCountEsc = escapeshellarg('Party1');
        
        // Command to run the JAR
        $command = "java -jar $javaJar $signActioEsc $inputPdfEsc $outputPdfEsc $partyCountEsc $pkcs7Esc";
        
        // Run it
        
        $output_array = [];
        
        $file_hash = exec($command, $output_array, $return_code);    
        
        // echo "Last line of output: " . $file_hash . "<br/>";
        
        // echo "All output lines:<br/>";
        // foreach ($output_array as $line) {
        //     echo $line . "<br/>";
        // }
        
        // echo "Return code: " . $return_code . "<br/>";  
        
        // echo $file_hash;

        
        $file = $signed_file_path;
        
        //$filename = file_name($file);
        $storageController =  fileStorageTypeController();
        
        $generatePdfPath = $storageController->get_file_path($id);
        
        if (fileStorageType() != "Local") {
            $filePath = $storageController->storeContent($file, $generatePdfPath, $generatedSignedPdfDocument);
        }else{
            $generatedPdfDocumentFinal = $generatePdfPath .'/'. $generatedSignedPdfDocument;
            Storage::put($generatedPdfDocumentFinal, file_get_contents($file));
            $filePath = $generatedPdfDocumentFinal;
        }
        
        // $finalNotifiers = "";
        
        // //For Getting Notifiers List
        // if(isset($signatory_data_decoded['notify']) && count($signatory_data_decoded['notify']) > 0){
        //     $finalNotifiers = $signatory_data_decoded['notify'];
        //     $finalNotifiers = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $finalNotifiers)->pluck('Email')->toArray();
        // }
        
        // if(isset($all_approvers) && count($all_approvers) > 0){

        //     $approversArr = [];
        //     foreach($all_approvers as $app_data){
        //         $approversArr[] = $app_data->id;
        //     }
            
        //     $approversArr = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $approversArr)->pluck('Email')->toArray();
            
        //     if($finalNotifiers == ""){
        //         $finalNotifiers = [];
        //     } 
            
        //     $finalNotifiers = array_unique(array_merge($finalNotifiers, $approversArr));
        // }

        // if ($nextAprroverEmail != "") {
        //     $controller->changePermission($filePath, $finalNotifiers, $nextAprroverEmail);
        //     $MailSent = $emailTrigger->sendEmail($contract->id, '', '', $nextAprroverEmail, 'New Contract Created Alert', $senattment['filename'],  $senattment['filurl'], 'newContract');
        // }
        Contract::where('id', $id)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $generatedSignedPdfDocument]);        
        
        $finalRequest = new Request();
        $requsetNew = [];
        $requestNew['contactId'] = $id;
        $requestNew['indexId'] = 0;
        $requestNew['nextActionItem0'] = 'Internal Signed';
        $requestNew['appId'] = $appId;
        $requestNew['nextAction0'] = 'Signed Successfully';
        $requestNew['appType'] = 'approved';
        $requestNew['appStatus'] = $currentApproval[0]->status;
        $requestNew['appPreStatus'] = $currentApproval[0]->previous_status;
        $requestNew['orderval'] = $currentApproval[0]->orderval;
        $requestNew['unique_id'] = $currentApproval[0]->unique_id;
        $requestNew['actionBtntext'] = 'To Sign';
        $requestNew['skipDocument'] = 'true';
        $requestNew['signPng'] = $pkcs7_value;
        $requestNew['signPngLoc'] = '';
        $requestNew['signType'] = 'esign';
        
        $finalRequest->merge($requestNew);
        
        unlink($unsigned_file_path);
        unlink($signed_file_path);
        
        $result = $this->contractApprovals($finalRequest, $userId);
        
        return redirect('/contracts/' . $id . '?tab=timeline');

    }    

    public function setupExSignaturePdf(Request $request, $id)
    {
        
        $updateHistory = false;

        $currentSign = $request->input('currentSign');

        $encId = $id;

        $id_ = $this->checkExternalUser($id, false);

        if (!$id_) {
            return response()->json(['success' => false, 'message' => 'Invalid Access'], 200);
        }else{
            $id = $id_[0];
            $emailCheck = $id_[1];
        }

        $contracts = Contract::select('*')->where('id', $id)->get();

        if (count($contracts) == 0) {
            return response()->json(['message' => 'Invalid Contract'], 200);
        } else {
            $contracts = $contracts[0];
        }
        
        $file_path = 'contracts/tempDocs/';
        
        $currentContract = Contract::find($id);

        $generatedPdfDocumentFinalName = 'sign_holder_added_doc_'.$currentContract->contract_unique_id.'.pdf';
        
        $generatedSignedPdfDocument = 'signed_'.$currentContract->contract_unique_id.'.pdf';
        
        $unsigned_file_path = base_path() . '/storage/app/' . $file_path . $generatedPdfDocumentFinalName;
        $signed_file_path = base_path() . '/storage/app/' . $file_path .$generatedSignedPdfDocument;
        
        
        $xmldata = (array) simplexml_load_string(filter_input(INPUT_POST, 'eSignResponse')) or die("Failed to load");
        // echo '<pre>';
        // print_r($xmldata);
        
        EsignResposnse::create([
            'contract_id' => $id,
            'approval_id' => $id,
            'esignresponse' => json_encode($xmldata),
            'status' => '0'
        ]);
        
        if ($xmldata["@attributes"]["errCode"] != 'NA') {
            $msg = $xmldata ["@attributes"]["errMsg"];
            if(empty($msg)){
                $msg ='eSign Request Canceled.[#'.$xmldata["@attributes"]["errCode"].']';
            }
            // print($msg);
            // exit();
            return redirect('/contracts/external/approval/'.$encId)->with('message', $msg)->with('alert-class', 'alert-warning');
        }
        
        //$unsigned_file = file_get_contents($unsigned_file_path);
        
        $txn = $xmldata ["@attributes"]["txn"];
        $txn_array = explode('----', $txn);
        $appId = $txn_array[1] ?? 0;
        $userId = $txn_array[2] ?? 0;
        
        if($appId != 0 && $userId != 0){
            $currentApproval = ApprovalContracts::where('id',$appId)->get()
                    ->map(function ($task) {
                        $task->username = decryptString($task->username, 'username');
                        $task->status = decryptString($task->status, 'status');
                        $task->previous_status = decryptString($task->previous_status, 'previous_status');
                        $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                        $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                        $task->approval_status = decryptString($task->approval_status, 'approval_status');
                        return $task;
                    });
            
            if(count($currentApproval) == 1){
                $currFlag = $currentApproval[0]->flag;
                if($currFlag == 1){
                    //echo "Already Signed";
                    return redirect('/contracts/external/approval/'.$encId)->with('message', 'Already Signed')->with('alert-class', 'alert-warning');
                    die;                
                }
            }else{
                //echo "Invalid Approval";
                return redirect('/contracts/external/approval/'.$encId)->with('message', 'Invalid Approval')->with('alert-class', 'alert-warning');
                die;
            }
        }else{
            //echo "Invalid Request";
            return redirect('/contracts/external/approval/'.$encId)->with('message', 'Invalid Request')->with('alert-class', 'alert-warning');
            die;
        }
        
        $pkcs7 = (array) $xmldata['Signatures'];
        $pkcs7_value = $pkcs7['DocSignature'];
        $cer_value = $xmldata['UserX509Certificate'];
        
        
        $beginpem = "-----BEGIN CERTIFICATE-----\n";
        $endpem = "-----END CERTIFICATE-----\n";
        $pemdata = $beginpem . trim($cer_value) . "\n" . $endpem;
        
        
        $outputPdf = '';
        
        $cleaned = str_replace("\\", "", $pkcs7_value);
        $cleaned = preg_replace('/\s+/', '', $cleaned);
        
        // Step 2: Ensure it's valid Base64 (optional check)
        if (base64_decode($cleaned, true) === false) {
            die("❌ Invalid Base64 after cleaning");
        } 
        
        $pkcs7_value = $cleaned;        
        
        $javaJar = base_path() . '/storage/app/' . $file_path .'esign.jar';
        
        // Escape arguments to prevent shell injection
        $signActioEsc = escapeshellarg('apply-signature');
        $inputPdfEsc = escapeshellarg($unsigned_file_path);
        $outputPdfEsc = escapeshellarg($signed_file_path);
        $pkcs7Esc = escapeshellarg($pkcs7_value);
        $partyCountEsc = escapeshellarg('Party'.($currentApproval[0]->orderval + 1));
        
        // Command to run the JAR
        $command = "java -jar $javaJar $signActioEsc $inputPdfEsc $outputPdfEsc $partyCountEsc $pkcs7Esc";
        
        // Run it
        $output_array = [];
        $file_hash = exec($command, $output_array, $return_code);    
        
        // echo "Last line of output: " . $file_hash . "<br/>";
        
        // echo "All output lines:<br/>";
        // foreach ($output_array as $line) {
        //     echo $line . "<br/>";
        // }
        
        // echo "Return code: " . $return_code . "<br/>";  
        
        // echo $file_hash;
        
        $file = $signed_file_path;

        $storageController =  fileStorageTypeController();
        
        $generatePdfPath = $storageController->get_file_path($id);
        
        if (fileStorageType() != "Local") {
            $filePath = $storageController->storeContent($file, $generatePdfPath, $generatedSignedPdfDocument);
        }else{
            $generatedPdfDocumentFinal = $generatePdfPath .'/'. $generatedSignedPdfDocument;
            Storage::put($generatedPdfDocumentFinal, file_get_contents($file));
            $filePath = $generatedPdfDocumentFinal;
        }
        
        Contract::where('id', $id)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $generatedSignedPdfDocument]);        
        
        $finalRequest = new Request();
        $requsetNew = [];
        $requestNew['contactId'] = $encId;
        $requestNew['indexId'] = 0;
        //$requestNew['nextActionItem0'] = 'External Signed';
        $requestNew['appId'] = $appId;
        //$requestNew['nextAction0'] = 'Signed Successfully';
        $requestNew['appType'] = 'approved';
        $requestNew['appStatus'] = $currentApproval[0]->status;
        $requestNew['appPreStatus'] = $currentApproval[0]->previous_status;
        $requestNew['orderval'] = $currentApproval[0]->orderval;
        $requestNew['unique_id'] = $currentApproval[0]->unique_id;
        $requestNew['actionBtntext'] = 'To Sign';
        $requestNew['skipDocument'] = 'true';
        $requestNew['signPng'] = $pkcs7_value;
        $requestNew['signPngLoc'] = '';
        
        $finalRequest->merge($requestNew);
        
        $result = $this->contractExApprovals($finalRequest, $userId);
        
        return redirect('/contracts/external/approval/' . $encId . '?tab=timeline');
            
    }    



    public function listContractData(Request $request)
    {
        
        $this->getFilterSetData($request);
        $partyIdFilter = (int)($request->input('party_id') ?? 0);
        
        $contracts = [];
        
        $contracts_query = "";
        
        if (isset($_POST['status']) && $_POST['status'] !== 'all') {
            $contracts_query = Contract::select(
                'contract_name',
                'id',
                'currency',
                'currency_value',
                'end_contract_type',
                'contract_status',
                'substatus',
                'fixed_date',
                'contract_end_date',
                'onetime_end_date',
                'contract_type',
                'catgoery_id'
            );
        } else if (isset($_POST['status']) && $_POST['status'] === 'all') {
            $contracts_query = Contract::select('contract_name', 'id', 'currency', 'currency_value', 'end_contract_type', 'contract_status', 'substatus', 'fixed_date', 'onetime_end_date', 'contract_end_date', 'contract_type', 'catgoery_id');
        }
        
        // Block timers for the perf log (local only; see App\Perf\PerfRecorder).
        $perfProbe = function (string $name, float $sinceUs) {
            if (class_exists(\App\Perf\PerfRecorder::class)) {
                \App\Perf\PerfRecorder::probe($name, round((microtime(true) - $sinceUs) * 1000, 2));
            }
        };
        $perfT = microtime(true);

        if(!empty($contracts_query)){
            $contracts_query->where('status', 1);
            $contracts_query->orderBy('id', 'desc');
            $contypes = isset($_POST['contype']) ? json_decode($_POST['contype']) : null;
            if (is_array($contypes) && count($contypes) > 0) {
                $contracts_query->whereIn('contract_type', $contypes);
            }
            $concates = isset($_POST['concates']) ? json_decode($_POST['concates']) : null;
            if (is_array($concates) && count($concates) > 0) {
                $contracts_query->whereIn('catgoery_id', $concates);
            }

            $contracts = $contracts_query->get();

        }
        $perfProbe('list_fetch_ms', $perfT);

        if (isset($_POST['status'])) {
            setcookie('filterStatus', $_POST['status'], time() + (86400 * 30), "/");
        }

        $perfT = microtime(true);
        $ContractsFinal = $this->availableContracts($contracts, true);
        $perfProbe('list_available_contracts_ms', $perfT);

        $contract_all_total                = 0;
        $contract_draft_total              = 0;
        $contract_review_total             = 0;
        $contract_finalization_total       = 0;
        $contract_negotiation_total        = 0;
        $contract_approval_total           = 0;
        $contract_approved_total           = 0;
        $contract_signing_total            = 0;
        $contract_executable_total         = 0;
        $contract_executable_active_total  = 0;
        $contract_executable_expired_total = 0;
        $contract_executable_pending_total = 0;
        $contract_executable_renewed_total = 0;
        $contract_executable_amended_total = 0;
        $contract_executable_termina_total = 0;
        $contract_executable_comp_total    = 0;
        $under_revision_total = 0;
        $initial_draft_total = 0;
        
        $contractIds = [];
        $checkMyContracts = 0;
        $perfT = microtime(true);
        if (isset($_COOKIE['myFilterStatus'])) {
            $checkMyContracts = 1;

            // Both whereIn calls take a query, never a list of ids. A list with 1,000 or more
            // bound ids silently returns zero rows on this stack
            // (.scratch/wherein-1000-bug/spec.md) - the old code bound ~2,508 ids here and
            // "My contracts" came back empty. Contracts the user cannot see are dropped by the
            // in_array($contract->id, $contractIds) test in the loop below, same as before.
            //
            // approval_status is plain text now, so the unique_id subquery keeps only the
            // groups that hold a pending row. Every row of those groups is fetched so the
            // group's leading row (highest id) still names the contract, as the old walk did.
            $approvalsArr = ApprovalContracts::select('id', 'unique_id', 'contract_id', 'username', 'approval_status')
                ->whereIn('unique_id', ApprovalContracts::select('unique_id')->where('approval_status', 'pending'))
                ->whereIn('contract_id', Contract::withoutGlobalScope('accessLevelSelect')->select('id')->where('status', 1))
                ->orderBy('id', 'DESC')
                ->get()
                ->groupBy('unique_id');

            $contractIds = [];
            foreach ($approvalsArr as $appr) {
                foreach ($appr as $appr_) {
                    if ($appr_->approval_status != 'pending') {
                        continue;
                    }
                    // username stays encrypted (dev call 2026-08-21). Decrypt it only for
                    // pending rows; nothing reads the other four encrypted columns here.
                    $email = json_decode(decryptString($appr_->username, 'username'))->email ?? '';
                    if (Helpers::accessInfo($email, false)) {
                        $contractIds[] = $appr[0]->contract_id;
                    }
                }
            }
        }
        $perfProbe('list_my_approvals_ms', $perfT);

        $perfT = microtime(true);
        $finalFilteredResult = [];
        foreach ($ContractsFinal as $contract) {
            if ($partyIdFilter > 0) {
                $hasPartyMatch = false;
                foreach ($contract->contractParty as $contractPart) {
                    if ((int)($contractPart->contract_party_exe_id ?? 0) === $partyIdFilter) {
                        $hasPartyMatch = true;
                        break;
                    }
                }

                if (!$hasPartyMatch) {
                    continue;
                }
            }

            if ($checkMyContracts > 0) {
                if (!in_array($contract->id, $contractIds)) {
                    continue;
                }
            }
            $applicable = true;
            if (isset($_POST['locations']) && $_POST['locations'] != 0) {
                $applicable = false;
                $contractParty = $contract->contractParty;
                foreach ($contractParty as $contractPart) {
                    
                    $contractLocation = json_decode($_POST['locations']);
                    
                    if(is_array($contractLocation) && !empty($contractLocation)){
                        //Check Branches Accessible for the User
                        if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal' && in_array($contractPart->contract_party_location_id, $contractLocation)) {
                            $applicable = true;
                        }
                    }else{
                      $applicable = true;  
                    }
                }
            }

            if ($applicable) {
                
                $contract->currency_value_converted = "-";
                if ($contract->currency_value > 0) {
                    $contract->currency_value_converted = currency_formatter(env('default_currency'), $contract->currency_value);
                }

                if (isset($_POST['status']) && $_POST['status'] !== 'all') {
                    
                    if (str_contains($_POST['status'], 'executed_') && contractStatusKey($contract->contract_status) == 'executed' && strtolower($contract->substatus) == strtolower(explode('_', $_POST['status'])[1])) {
                        $finalFilteredResult[] = $contract;
                    } else if (str_contains($_POST['status'], 'draft_initial') && $contract->contract_status == 'Draft' && $contract->substatus == 'Initial Draft') {
                        $finalFilteredResult[] = $contract;
                    } else if (str_contains($_POST['status'], 'draft_under_revision') && $contract->contract_status == 'Draft' && $contract->substatus == 'Under Revision') {
                        $finalFilteredResult[] = $contract;
                    } else if(contractStatusKey($contract->contract_status) == $_POST['status']){
                        // contractStatusKey() groups the internal 'Pre-Approval' status under
                        // 'review', so the Review filter also returns pre-approval contracts.
                        //$contracts_query->where('contract_status', $_POST['status']);
                        $finalFilteredResult[] = $contract;
                    }
                
                }else{

                    $finalFilteredResult[] = $contract;
                }  
                    

                //$finalFilteredResult[] = $contract;
                // Group by the user-facing status key ('Pre-Approval' -> 'review').
                switch (contractStatusKey($contract->contract_status)) {
                case 'executed':
                    $contract_executable_total++;
                    $contract_all_total++;
                    switch ($contract->substatus) {
                        case 'active':
                            $contract_executable_active_total++;
                            break;
                        case 'expired':
                            $contract_executable_expired_total++;
                            break;
                        case 'pending':
                            $contract_executable_pending_total++;
                            break;
                        case 'renewed':
                            $contract_executable_renewed_total++;
                            break;
                        case 'amended':
                            $contract_executable_amended_total++;
                            break;
                        case 'Terminated':
                            $contract_executable_termina_total++;
                            break;
                        case 'completed':
                            $contract_executable_comp_total++;
                            break;
                    }
                    break;
                case 'draft':
                    $contract_draft_total++;
                    $contract_all_total++;

                    switch ($contract->substatus) {
                        case 'Under Revision':
                            $under_revision_total++;
                            break;
                        case 'Initial Draft':
                            $initial_draft_total++;
                            break;
                    }

                    break;

                // Also covers the internal 'Pre-Approval' status via contractStatusKey().
                case 'review':
                    $contract_review_total++;
                    $contract_all_total++;
                    break;
                // Pre-approval flow stage (grouped flow): previously uncounted, so such
                // contracts were missing from every tab incl. "All".
                case 'finalization':
                    $contract_finalization_total++;
                    $contract_all_total++;
                    break;
                case 'negotiation':
                    $contract_negotiation_total++;
                    $contract_all_total++;
                    break;
                case 'approval':
                    $contract_approval_total++;
                    $contract_all_total++;
                    break;
                case 'approved':
                    $contract_approved_total++;
                    $contract_all_total++;
                    break;
                case 'signing':
                    $contract_signing_total++;
                    $contract_all_total++;
                    break;
            }
            }
        }

        $stus = array(
            'all' => $contract_all_total,
            'draft' => $contract_draft_total,
            'draft_initial' => $contract_draft_total,
            'draft_under_revision' => $contract_draft_total,
            'review' => $contract_review_total,
            'finalization' => $contract_finalization_total,
            'negotiation' => $contract_negotiation_total,
            'approval' => $contract_approval_total,
            'approved' => $contract_approved_total,
            'signing' => $contract_signing_total,
            'executed' => $contract_executable_total,
            'executed_active' => $contract_executable_active_total,
            'executed_expired' => $contract_executable_expired_total,
            'executed_pending' => $contract_executable_pending_total,
            'executed_renewed' => $contract_executable_renewed_total,
            'executed_amended' => $contract_executable_amended_total,
            'executed_terminated' => $contract_executable_termina_total,
            'executed_completed' => $contract_executable_comp_total,
            'under_revision' => $under_revision_total,
            'initial_draft' => $initial_draft_total
        );

        $perfProbe('list_filter_count_loop_ms', $perfT);

        $perfT = microtime(true);
        // json_encode of the whole row set happens inside the JsonResponse
        // constructor, so this timer covers the serialisation.
        $response = response()->json([
            'data' => $finalFilteredResult,
            'draw' => $request->input('draw') ?? 1,
            'recordsTotal' => count($finalFilteredResult),
            'recordsFiltered' => count($finalFilteredResult),
            'counts'=>$stus
        ]);
        $perfProbe('list_json_encode_ms', $perfT);

        return $response;
    }

    public function listContract(Request $request)
    {
        
        $this->getFilterSetData($request);

        //$available_branches = BranchUser::pluck('id','BranchName')->toArray();

        $branchs_query = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        );
        
        $branchs = $branchs_query->get();
        
        $contractTypes = ContractType::get();
        
        $contractStatus = ContractStatusTexts::get();
        
        $ContractCategories = ContractCategories::get();        

        $contract_all_total                = 0;
        $contract_draft_total              = 0;
        $contract_review_total             = 0;
        $contract_finalization_total       = 0;
        $contract_negotiation_total        = 0;
        $contract_approval_total           = 0;
        $contract_approved_total           = 0;
        $contract_signing_total            = 0;
        $contract_executable_total         = 0;
        $contract_executable_active_total  = 0;
        $contract_executable_expired_total = 0;
        $contract_executable_pending_total = 0;
        $contract_executable_renewed_total = 0;
        $contract_executable_amended_total = 0;
        $contract_executable_termina_total = 0;
        $contract_executable_comp_total    = 0;
        $under_revision_total = 0;
        $initial_draft_total = 0;

        $stus = array(
            'all' => $contract_all_total,
            'draft' => $contract_draft_total,
            'review' => $contract_review_total,
            'finalization' => $contract_finalization_total,
            'negotiation' => $contract_negotiation_total,
            'approval' => $contract_approval_total,
            'approved' => $contract_approved_total,
            'signing' => $contract_signing_total,
            'executed' => $contract_executable_total,
            'executed_active' => $contract_executable_active_total,
            'executed_expired' => $contract_executable_expired_total,
            'executed_pending' => $contract_executable_pending_total,
            'executed_renewed' => $contract_executable_renewed_total,
            'executed_amended' => $contract_executable_amended_total,
            'executed_terminated' => $contract_executable_termina_total,
            'executed_completed' => $contract_executable_comp_total,
            'under_revision' => $under_revision_total,
            'initial_draft' => $initial_draft_total
        );

        return view('contract::contract.contractList', compact('branchs', 'contractTypes', 'ContractCategories', 'contractStatus'))->with('counts', $stus)
        ->with('sellocal', json_decode($_POST['locations'] ?? '') ?? [])
        ->with('selcate', json_decode($_POST['concates'] ?? '') ?? [])
        ->with('selstatus', $_COOKIE['filterStatus'] ?? '')
        ->with('selcontype', json_decode($_POST['contype'] ?? '') ?? []);
    }
    
    public function getFilterSetData($request){
        $filterSetArray = [
            'contractlocs' => ['table'=>'branch', 'column' => 'branchName', 'where' => 'id', 'req' => 'locations', 'encode'=> true],
            'contracttype' => ['table'=>'contract_type', 'column' => 'contract_type', 'where' => 'contract_type_id', 'req' => 'contype', 'encode'=> true],
            'contractcates' => ['table'=>'contract_categories', 'column' => 'name', 'where' => 'id', 'req' => 'concates', 'encode'=> true],
            'contractprior' => ['table'=>[], 'column' => 'contract_priority', 'where' => 'id', 'req' => 'contract_priority', 'encode'=> true],
            'contractstats' => ['table'=>[], 'column' => 'contract_status', 'where' => 'id', 'req' =>'status', 'encode'=> false]
        ];
        
        
        if(isset($_COOKIE['filterSet'])){
            $allFilters = json_decode($_COOKIE['filterSet']);
            if (!is_object($allFilters) && !is_array($allFilters)) {
                return;
            }
            foreach($allFilters as $allFilt => $allFiltVal){
                //$field = explode('_', $allFilt);
                $field = $allFilt;
                $finalField = $filterSetArray[$field] ?? false;
                if($finalField){
                    if(empty($_POST[$finalField['req']])){
                        $_POST[$finalField['req']] = ($finalField['encode'] ? json_encode($allFiltVal) : $allFiltVal);
                    }
                }
            }
        }        
    }

    public function storeContract(Request $request)
    {
        
        $rulesContract = [
            "contractMode" => 'required',
            "owner" => 'required',
            "legal_advisor_id" => 'nullable|integer|exists:legal_advisors,id',
            "contact_legal_now" => 'nullable|in:1',
            "legal_contact_comment" => 'nullable|string|max:2000',
            //"signatory" => 'required', 
            "BasicContract.contractType" => 'required',
            "BasicContract.catgoeryType" => 'required',
            "BasicContract.DepartmentType" => 'required',
            //"file" => "nullable|mimes:docx,pdf"
        ];

        $messages = [
            "required" => 'Please Fill Mandatory Fields :attribute',
            "owner.required" => 'Please Choose Co-Ordinator in Ownership',
            //"signatory.required" => 'Please Choose Signatory in Ownership',
            "BasicContract.contractType.required" => 'Please Choose Contract Type in Basic Contract Information Section',
            "BasicContract.catgoeryType.required" => 'Please Choose Category in Basic Contract Information Section',
            "BasicContract.DepartmentType.required" => 'Please Choose Department in Basic Contract Information Section',
            "legal_contact_comment.max" => 'Legal contact comment should not exceed 2000 characters.',
            //"file.required" => 'Please Upload Contract Document in Attachments',
            //"file.mimes" => 'Contract Document Must Be A File Of Type: Docx, Pdf',
            "Partygroup.party.required" => 'Please Choose Any Party',
        ];
        
        $needAttachment = false;

        if (strtolower($request->input('attachments_type')) == 'upload') {
            $rulesContract['file'] = "required|mimes:docx";
            $messages['file.required'] = "Please Upload Contract Document in Attachments";
            $messages['file.mimes'] = "Please upload the new contract document in DOCX format";
            $needAttachment = true;
        }


        if ($request->contractMode == "old") {
            $rulesContract["Duration.signingDate"] = 'required';
            $messages["Duration.signingDate.required"] = 'Please Fill Signing Date in Ownership';

            if ($request->Duration["commencementDate"] == 'FixedDate') {
                $rulesContract["Duration.fixedDate"] = 'required';
                $messages["Duration.fixedDate.required"] = 'Please Fill Start Date in Contract Duration Section';
            }

            switch ($request->Duration["effectiveDate"]) {
                case 'fixedTerm':
                    $rulesContract["Duration.fixedtimeEndDateofContract"] = 'required'; 
                    $messages["Duration.fixedtimeEndDateofContract.required"] = 'Please Fill End date of contract in Contract Duration Section';
                    break;
                case 'termination':
                    $rulesContract["Duration.terminationDate"] = 'required';
                    $messages["Duration.terminationDate.required"] = 'Please Fill Termination in Contract Duration Section';
                    break;
                case 'onetimeContract':
                    $rulesContract["Duration.onetimeEndDateofContract"] = 'required';
                    $messages["Duration.onetimeEndDateofContract.required"] = 'Please Fill End date of contract in Contract Duration Section';
                    break;
            }

            
            $rulesContract['file'] = "required|mimes:pdf";
            $messages['file.required'] = "Please Upload Contract Document in Attachments";
            $messages['file.mimes'] = "Please upload the Legacy Contract document in PDF format";            
        }

        if ($request->Duration["fixedDate"] !== null) {
            switch ($request->Duration["effectiveDate"]) {
                case 'fixedTerm':
                    $rulesContract["Duration.fixedtimeEndDateofContract"] = 'nullable|date|after_or_equal:Duration.fixedDate'; 
                    $messages["Duration.fixedtimeEndDateofContract.required"] = 'Please Fill End date of contract in Contract Duration Section';
                    $messages["Duration.fixedtimeEndDateofContract.after_or_equal"] = 'End Date Must Be Greater Or Equal To Start Date';
                    break;
                case 'termination':
                    $rulesContract["Duration.terminationDate"] = 'nullable|date|after_or_equal:Duration.fixedDate';
                    $messages["Duration.terminationDate.required"] = 'Please Fill Termination in Contract Duration Section';
                    $messages["Duration.terminationDate.after_or_equal"] = 'End Date Must Be Greater Or Equal To Start Date';
                    break;
                case 'onetimeContract':
                    $rulesContract["Duration.onetimeEndDateofContract"] = 'nullable|date|after_or_equal:Duration.fixedDate';
                    $messages["Duration.onetimeEndDateofContract.required"] = 'Please Fill End date of contract in Contract Duration Section';
                    $messages["Duration.onetimeEndDateofContract.after_or_equal"] = 'End Date Must Be Greater Or Equal To Start Date';
                    break;
            }
        }

        if ($request->Duration["commencementDate"] == 'Eventbased') {
            if (count($request->input('Duration.task')) > 0) {
                $rulesContract["Duration.task.*.start_date"] = 'required';
                $rulesContract["Duration.task.*.end_date"] = 'required|date|after_or_equal:Duration.task.*.start_date';
                $messages["Duration.task.*.start_date.required"] = 'Please Fill Task Start Date';
                $messages["Duration.task.*.end_date.required"] = 'Please Fill Task End Date';
                $messages["Duration.task.*.end_date"] = 'Task End Date Must Be Greater Or Equal To Start Date';
            }
        }

        // V3 annexures. Only present when posted from the V3 page; the rules are no-ops
        // otherwise. Annexures are appended to the generated PDF, so Word files only.
        if ($request->has('annexures') || $request->has('custom_annexures')) {
            $rulesContract['annexures.*.file'] = 'nullable|file|mimes:doc,docx|max:20480';
            $rulesContract['custom_annexures.*.file'] = 'nullable|file|mimes:doc,docx|max:20480';
            $rulesContract['custom_annexures.*.annexure_name'] = 'required_with:custom_annexures.*.file|nullable|string|max:255';
            $messages['annexures.*.file.mimes'] = 'Annexures must be Word documents (.doc or .docx)';
            $messages['custom_annexures.*.file.mimes'] = 'Annexures must be Word documents (.doc or .docx)';
            $messages['custom_annexures.*.annexure_name.required_with'] = 'Please provide a name for each annexure you upload';
        }

        $validator =  Validator::make($request->all(), $rulesContract, $messages);

        // mimes: trusts the reported MIME type, so the extension is re-checked server side.
        $validator->after(function ($validator) use ($request) {
            foreach (['annexures', 'custom_annexures'] as $inputKey) {
                foreach ((array) $request->file($inputKey, []) as $rowKey => $row) {
                    $file = is_array($row) ? ($row['file'] ?? null) : $row;
                    if (!$file) {
                        continue;
                    }
                    $extension = strtolower($file->getClientOriginalExtension());
                    if (!in_array($extension, ['doc', 'docx'], true)) {
                        $validator->errors()->add(
                            $inputKey . '.' . $rowKey . '.file',
                            'Annexures must be Word documents (.doc or .docx)'
                        );
                    }
                }
            }
        });

        if ($validator->fails()) {
            $errors = $validator->errors();
            if (!$validator->errors()->has('file')) {
                $validator->errors()->add('file', 'On Behalf Above Validation Errors File was cleared Please Upload Contract Document in Attachments');
            }
            return redirect($this->createRedirectPath)->withErrors($validator)->withInput();
        }

        if ((string) $request->input('contact_legal_now') === '1') {
            if (!$request->filled('legal_advisor_id')) {
                return redirect($this->createRedirectPath)->withErrors(['Please select Legal Advisor in Contact Legal before submitting.'])->withInput();
            }
            if (trim((string) $request->input('legal_contact_comment')) === '') {
                return redirect($this->createRedirectPath)->withErrors(['Please enter Legal comment in Contact Legal before submitting.'])->withInput();
            }
        }

        $controller =  fileStorageTypeController();
        

        $locals = $request->input('Partygroup.party');

        $totalInternals = 0;
        $totalExternals = 0;
        $sameExternals = [];
        $sameInternals = [];
        
        $fileError = ['On Behalf Below Validation Errors File was cleared Please Upload Contract Document in Attachments'];
        
        if(!$needAttachment){
            $fileError = [];
        }        

        //To Get First Internal Party Location
        $internal_first_location = "";
        //$external_first_exist = "";
        foreach ($locals as $partyLoc) {
            if (isset($partyLoc['location']) && $partyLoc['location'] != "" && $partyLoc['mode'] == "Internal") {
                if ($totalInternals == 0) {
                    $internal_first_location = $partyLoc;
                }
                $totalInternals++;
                if (!in_array($partyLoc['location'], $sameInternals)) {
                    $sameInternals[] = $partyLoc['location'];
                }else{
                    $invalid_location_error = array('Duplicate internal/intergroup party with location in Party Details');
                    return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, $invalid_location_error))->withInput();                    
                }                
            }
            if (isset($partyLoc['mode']) && $partyLoc['mode'] == "External" && isset($partyLoc['external_name'])) {
                $totalExternals++;
                if (!in_array($partyLoc['external_name'], $sameExternals)) {
                    $sameExternals[] = $partyLoc['external_name'];
                }else{
                    $invalid_external_error = array('Duplicate external party in Party Details');
                    return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, $invalid_external_error))->withInput();                    
                }                
            }
            if (isset($partyLoc['mode']) && $partyLoc['mode'] == "Intergroup" && isset($partyLoc['location_grp'])) {
                $totalExternals++;
                if (!in_array($partyLoc['location_grp'], $sameInternals)) {
                    $sameInternals[] = $partyLoc['location_grp'];
                }else{
                    $invalid_location_error = array('Duplicate internal/intergroup party with location in Party Details');
                    return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, $invalid_location_error))->withInput();                    
                }
            }
        }


        if ($internal_first_location == "") {
            $invalid_location_error = array('Please Choose one internal party with location in Party Details');
            return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, $invalid_location_error))->withInput();
        }

        if ($totalInternals > 1) {
            $invalid_location_error = array('Please Choose Only One internal party with location in Party Details');
            return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, $invalid_location_error))->withInput();
        }

        if ($totalExternals == 0) {
            $missing_external_error = array('Please Choose atleast One external/intergroup party in Party Details');
            return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, $missing_external_error))->withInput();
        }
        
        
        $endContractDate = null;

        switch ($request->input("Duration.effectiveDate")) {
            case 'fixedTerm':
                $endContractDate = $request->input('Duration.fixedtimeEndDateofContract');
                break;
            case 'onetimeContract':
                $endContractDate = $request->input('Duration.onetimeEndDateofContract');
                break;
        }


        if ($request->input('Duration.fixedDate') != null && $endContractDate != null) {
            if (strtotime($request->input('Duration.fixedDate')) > strtotime($endContractDate)) {
                return redirect($this->createRedirectPath)->withErrors(array_merge($fileError,['Contract end date must be greater than Start Date']))->withInput();
            }
        }
        
        if ($request->input('Duration.signingDate') != null && $request->input('Duration.fixedDate') != null) {
            if (strtotime($request->input('Duration.signingDate')) < strtotime($request->input('Duration.fixedDate'))) {
                //return redirect($this->createRedirectPath)->withErrors(array_merge($fileError,['Signing date must be greater than/Equal to Start Date']))->withInput();
            }            
        }
        if ($request->input('Duration.signingDate') != null && $endContractDate != null) {
            if (strtotime($request->input('Duration.signingDate')) > strtotime($endContractDate)) {
                return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, ['Signing date must be less than/Equal to End Date']))->withInput();
            }            
        }



        $approval_user_column = "approval_required_users";
        $approvalTypeGlobal = "0";
        if ($request->input('contractMode') != 'new') {
            $approval_user_column = "approval_required_users_legacy";
            $approvalTypeGlobal = "legacy";
        }

        //Check Contract Duplicates Start
        
        $existContract = $this->checkDuplicateContracts($locals, $request, $endContractDate);
        
        if($existContract){
            return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, ['Duplicate contract details detected — contract already exists <a href="' . url('/contracts/' . $existContract->id) . '" target="new">'.$existContract->contract_unique_id.'</a>']))->withInput();
        }
        
        
        //Check Contract Duplicates End        

        $financialLimit = $this->financialLimit(
            $internal_first_location['location'],
            $request->input('BasicContract.DepartmentType'),
            $request->input('BasicContract.catgoeryType'),
            $request->input('BasicContract.contractType'),
            $request->input('ContractValue.value'),
            $approval_user_column
        );

        $financialLimitDecoded = json_decode($financialLimit)[0];

        $signatory_data_decoded = (array)json_decode($financialLimitDecoded->signatory);
        $app_type_data_decoded = (array)json_decode($financialLimitDecoded->approval_type);
        $app_status_data_decoded = (array)json_decode($financialLimitDecoded->approval_status);

        $signatory_array = (array)($signatory_data_decoded['sign']);
        $owner_array = (array)($signatory_data_decoded['owner']);
        $notifier_array = ((array)($signatory_data_decoded['notify'] ?? [])) ?? [];
        $utf_array = ((array)($signatory_data_decoded['signutform'] ?? null)) ?? null;
        $signatory_data_decoded = [];
        $signatory_data_decoded['sign'] = $signatory_array[$approvalTypeGlobal];
        $signatory_data_decoded['owner'] = $owner_array[$approvalTypeGlobal];
        $signatory_data_decoded['notify'] = $notifier_array[$approvalTypeGlobal] ?? [];
        $signatory_data_decoded['signutform'] = $utf_array[$approvalTypeGlobal] ?? [];
        $financialLimitDecoded->signatory = json_encode($signatory_data_decoded);
        $financialLimitDecoded->approval_type = $app_type_data_decoded[$approvalTypeGlobal];
        $financialLimitDecoded->approval_status = $app_status_data_decoded[$approvalTypeGlobal];
        
        $signatory = $request->signatory ?? false;

        if (!$request->signatory) {
            //Signatory Validation
            $final_signatory = explode(":", $signatory_data_decoded['sign']);

            $signatory = $final_signatory[0] ?? 0;

            if ($signatory < 1) {
                $invalid_signatory_error = array('Signatory Not Added In Approval Rules Please Add one');
                return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, $invalid_signatory_error))->withInput();
            }
        }


        //Owner/Initiator Validation
        $owner_initiator = session()->get('contractSessionUser');

        $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
            ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
            ->first();
        if (!$initiatior_exists) {
            $invalid_owner_error = array('Owner Not Available Please Contact Administrator');
            return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, $invalid_owner_error))->withInput();
        }
        
        if (!$request->owner) {
            $owner_initiator_id = $initiatior_exists->id;
        } else {
            $owner_initiator_id = $request->owner;
        }
        
        if ($request->userNotify) {
            //Notifiers Validation
            $signatory_data_decoded['notify'] = $request->userNotify;

        }        

        $selectedLegalAdvisor = null;
        if ($request->filled('legal_advisor_id')) {
            $selectedLegalAdvisor = LegalAdvisor::where('id', (int) $request->input('legal_advisor_id'))->where('status', 1)->first();
            if (!$selectedLegalAdvisor) {
                return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, ['Selected legal advisor is invalid or inactive.']))->withInput();
            }
        }

        $triggerLegalContact = ((string) $request->input('contact_legal_now') === '1') && $selectedLegalAdvisor;

        $legalContactComment = trim((string) $request->input('legal_contact_comment'));
        $requesterInfo = Helpers::userInfo();
        $legalRequesterEmail = (string) ($requesterInfo->email ?? '');
        $legalRequesterName = trim((string) (($requesterInfo->FirstName ?? '') . ' ' . ($requesterInfo->LastName ?? '')));
        if ($legalRequesterName === '') {
            $legalRequesterName = (string) ($requesterInfo->FirstName ?? $legalRequesterEmail);
        }
        
        $signatory_data_decoded['sign'] = $signatory;
        $signatory_data_decoded['owner'] = $owner_initiator_id;
        
        $financialLimitDecoded->signatory = json_encode($signatory_data_decoded);
        
        $all_approvers = json_decode($financialLimitDecoded->approver, true);

        $approvers_grouped = (is_array($all_approvers) && isset($all_approvers[0]) && isset($all_approvers[0]['approvers']));


        $branchHeads = BranchUser::select(
            'id',
            decrypt_data('branchheadname', 'branch'),
            'Branchhead',
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->where('id', $internal_first_location['location'])->first();


        $branchHeadsError = [];
        // foreach ($all_approvers as $ap_data) {
        //     if ($ap_data->type == 'designation') {
        //         if ($ap_data->name == 'branch_head') {
        //             $branchHeadId = $branchHeads->Branchhead;
        //             if ($branchHeadId == null) {
        //                 $branchHeadsError[] = "Branch Head Not Added in your selected Branch Please Update In Goal Portal";
        //             }
        //             $ap_data->id = $branchHeadId;
        //         }
        //         if ($ap_data->name == 'branch_dep_head') {
        //             $branchDeptData = unserialize($branchHeads->departments);
        //             //print_r($branchDeptData);
        //             if (!isset($branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')])) {
        //                 $branchHeadsError[] = "Branch Department Head Not Added in your selected Branch Please Update In Goal Portal";
        //             } else {
        //                 $ap_data->id = $branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')];
        //             }
        //         }
        //         if ($ap_data->name == 'overall_dept_head') {
        //             $entityDeptHead = EntityBusiness::select('overall_dept_head')->where('id', $request->input('BasicContract.DepartmentType'))->first();
        //             if (!$entityDeptHead || !$entityDeptHead->overall_dept_head) {
        //                 $branchHeadsError[] = "Department Over All Head Not Added in your Entity Business Please Update In Goal Portal";
        //             } else {
        //                 $ap_data->id = $entityDeptHead->overall_dept_head;
        //             }
        //         }
        //     }
        // }
        
        
        // If approvers are grouped, map designation placeholders to actual ids now that branch info is available
        if (!empty($approvers_grouped)) {
            foreach ($all_approvers as $gIdx => $group) {
                if (!isset($group->approvers) || !is_array($group->approvers)) continue;
                foreach ($group->approvers as $aIdx => $ap_data) {
                    if (isset($ap_data->type) && $ap_data->type === 'designation') {
                        if ($ap_data->name === 'branch_head') {
                            $branchHeadId = $branchHeads->Branchhead;
                            $all_approvers[$gIdx]->approvers[$aIdx]->id = $branchHeadId;
                        }
                        if ($ap_data->name === 'branch_dep_head') {
                            $branchDeptData = unserialize($branchHeads->departments);
                            if (isset($branchDeptData["departmentheadid"]) && isset($branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')])) {
                                $all_approvers[$gIdx]->approvers[$aIdx]->id = $branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')];
                            }
                        }
                        if ($ap_data->name === 'overall_dept_head') {
                            $entityDeptHead = EntityBusiness::select('overall_dept_head')->where('id', $request->input('BasicContract.DepartmentType'))->first();
                            if ($entityDeptHead && $entityDeptHead->overall_dept_head) {
                                $all_approvers[$gIdx]->approvers[$aIdx]->id = $entityDeptHead->overall_dept_head;
                            }
                        }
                    }
                }
            }
        }
        

        $financialLimitDecoded->approver = json_encode($all_approvers);

        $financialLimit = json_encode([$financialLimitDecoded]);


        if (count($branchHeadsError) > 0) {
            return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, $branchHeadsError))->withInput();
        }



        $emailTrigger = new ContractNotificationController();
        $senattment = [];
        $senattment['filename'] = [];
        $senattment['filurl'] = [];

        DB::beginTransaction();
        
        $contract = Contract::create([
            'contract_mode' => encryptString($request->input('contractMode'), 'contract_mode'),
            'contract_type' => $request->input('BasicContract.contractType'),
            'contract_tags' => json_encode($request->input('BasicContract.contractTypeTags') ?? []),
            'contract_description' => encryptString($request->input('BasicContract.contractDescription'), 'contract_description'),
            'contract_priority' => $request->input('priority'),

            'department_id' => $request->input('BasicContract.DepartmentType'),
            'catgoery_id' => $request->input('BasicContract.catgoeryType'),

            'signatory' => $signatory,
            'owner' => $owner_initiator_id,
            'legal_advisor_id' => $selectedLegalAdvisor->id ?? null,
            'legal_advisor_email' => $selectedLegalAdvisor->email_id ?? null,
            'legal_contact_comment' => $triggerLegalContact ? encryptString($legalContactComment, 'legal_contact_comment') : null,
            'legal_requested_by_name' => $triggerLegalContact ? encryptString($legalRequesterName, 'legal_requested_by_name') : null,
            'legal_requested_by_email' => $triggerLegalContact ? encryptString($legalRequesterEmail, 'legal_requested_by_email') : null,
            'legal_requested_at' => $triggerLegalContact ? now() : null,
            'legal_contact_status' => $triggerLegalContact ? 'contacted' : 'not_contacted',
            'legal_response_comment' => null,
            'legal_responded_by_name' => null,
            'legal_responded_by_email' => null,
            'legal_responded_at' => null,


            'confidentialityagreement' => $request->input('BasicContract.Confidentialityagreement'),
            'exclusivity' => encryptString($request->input('BasicContract.Exclusivity'), 'exclusivity'),

            // Contract Duration
            'signing_date' => $request->input('Duration.signingDate'),
            'commencement_type' => encryptString($request->input('Duration.commencementDate'), 'commencement_type'), // corrected key
            'fixed_date' => $request->input('Duration.fixedDate'),
            'event_name' => encryptString($request->input('Duration.eventDetails'), 'event_name'),
            'end_contract_type' => encryptString($request->input('Duration.effectiveDate'), 'end_contract_type'),
            'contract_end_date' => $endContractDate,
            'renewal_type' => encryptString($request->input('Duration.typeRenewal'), 'renewal_type'),
            'period_auto_renewal' => $request->input('Duration.periodAutoRenewal'),
            'period_auto_renewal_unit' => encryptString($request->input('Duration.periodAutoRenewalPeriod'), 'period_auto_renewal_unit'),
            'auto_renewal_date' => $request->input('Duration.autoRenewalDate'),
            'manual_renewal_date' => $request->input('Duration.autoManualRenewalDate'),
            'evergreen_condition' => encryptString($request->input('Duration.conditionEndContract'), 'evergreen_condition'),
            'termination_date' => $request->input('Duration.terminationDate'),
            'termination_reason' => encryptString($request->input('Duration.reasonTermination'), 'termination_reason'),

            // V3 only. Absent on the other create pages, so these stay null there.
            // Tenure is derived on the client from the start/end dates and stored as the
            // human readable string that was shown to the user (e.g. "2 years 3 months").
            'tenure' => $request->input('tenure'),
            'price_revision_type' => $request->input('price_revision_type'),
            'price_revision_value' => $request->input('price_revision_value'),



            // Contract Value
            'currency' => encryptString($request->input('ContractValue.currency'), 'currency'),
            'billing_value' => encryptString($request->input('ContractValue.billingvalue'), 'billing_value'),
            'currency_value' => encryptString($request->input('ContractValue.value'), 'currency_value'),
            'total_value' => encryptString($request->input('ContractValue.totalvalue'), 'total_value'),
            'payment_schedule' => encryptString($request->input('ContractValue.paymentSchedule'), 'payment_schedule'),
            'currency_contract' => encryptString($request->input('ContractValue.currencyContract'), 'currency_contract'),
            'payment_terms' => encryptString($request->input('ContractValue.paymentTerms'), 'payment_terms'),
            'billing_frequency' => encryptString($request->input('ContractValue.billingFrequency'), 'billing_frequency'),
            'taxes' => encryptString($request->input('ContractValue.taxes'), 'taxes'),
            'escalation_clauses' => encryptString($request->input('ContractValue.escalationClauses'), 'escalation_clauses'),
            'discounts' => encryptString($request->input('ContractValue.discounts'), 'discounts'),
            'retention' => encryptString($request->input('ContractValue.retention'), 'retention'),
            'payment_escrow' => encryptString($request->input('ContractValue.payment_escrow'), 'payment_escrow'),
            'financial_guarantees' => encryptString($request->input('ContractValue.financialGuarantees'), 'financial_guarantees'),
            'currency_conversion' => encryptString($request->input('ContractValue.currencyConversion'), 'currency_conversion'),

            // Reminder Value
            'reminder_enable' => encryptString($request->input('Duration.reminderEnable'), 'reminder_enable') ?? null,
            'reminder_first_alert' => encryptString($request->input('Duration.Reminder.first.alertMe'), 'reminder_first_alert'),
            'reminder_first_alertMeOn' => encryptString($request->input('Duration.Reminder.first.alertMeDay') . ' ' . $request->input('Duration.Reminder.first.alertMePrior') . ' ' . $request->input('Duration.Reminder.first.alertMeType'), 'reminder_first_alertMeOn'),
            'reminder_first_alert_repeats' => encryptString($request->input('Duration.Reminder.first.repeats'), 'reminder_first_alert_repeats'),
            'reminder_second_alert' => encryptString($request->input('Duration.Reminder.second.alertMe'), 'reminder_second_alert'),
            'reminder_second_alertMeOn' => encryptString($request->input('Duration.Reminder.second.alertMeDay') . ' ' . $request->input('Duration.Reminder.second.alertMePrior') . ' ' . $request->input('Duration.Reminder.second.alertMeType'), 'reminder_second_alertMeOn'),
            'reminder_second_alert_repeats' => encryptString($request->input('Duration.Reminder.second.repeats'), 'reminder_second_alert_repeats'),
            'reminder_escalation_alert' => encryptString($request->input('Duration.Reminder.escalation.alertMe'), 'reminder_escalation_alert'),
            'reminder_escalation_alertMeOn' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay') . ' ' . $request->input('Duration.Reminder.escalation.alertMePrior') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType'), 'reminder_escalation_alertMeOn'),
            'reminder_escalation_alert_repeats' => encryptString($request->input('Duration.Reminder.escalation.repeats'), 'reminder_escalation_alert_repeats'),
            'reminder_escalation_alert_after' => encryptString($request->input('Duration.Reminder.escalation.alertMe_after'), 'reminder_escalation_alert_after'),
            'reminder_escalation_alertMeOn_after' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay_after') . ' ' . $request->input('Duration.Reminder.escalation.alertMeAfter') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType_after'), 'reminder_escalation_alertMeOn_after'),
            'reminder_escalation_alert_repeats_after' => encryptString($request->input('Duration.Reminder.escalation.repeats_after'), 'reminder_escalation_alert_repeats_after'),

            'rules_id' => $financialLimit,

            'custom_fields_data' => json_encode($request->input('customFields')),

            'created_by' => $initiatior_exists->id

        ]);

        if ($request->has('customFields')) {
            foreach ($request->input('customFields') as $customField) {
                if (isset($customField)) {

                    if (isset($customField['id']) && isset($customField['value']) && isset($contract->id)) {
                        CustomFieldsData::create([
                            'custom_field_id' => $customField['id'],
                            'custom_field_group' => 'contracts',
                            'custom_field_value' => $customField['value'],
                            'custom_field_group_id' => $contract->id
                        ]);
                    }
                }
            }
        }
        $contractTypeName = ContractType::where('contract_type_id', $request->input('BasicContract.contractType'))->first();

        $namePartygroup =  $contractTypeName->contract_type;

        // 
        if (is_array($request->input('Duration.task')) || is_object($request->input('Duration.task'))) {
            foreach ($request->input('Duration.task') as $ke => $tasks) {

                if (isset($tasks['name_of_task'])) {
                    Tasks::create([
                        'name_of_task' => encryptString($tasks['name_of_task'], 'name_of_task'),
                        'priority' => encryptString($tasks['priority'], 'priority'),
                        'start_date' => encryptString($tasks['start_date'], 'start_date'),
                        'end_date' => encryptString($tasks['end_date'], 'end_date'),
                        'description' => encryptString($tasks['description'], 'description'),
                        'task_owner' => $request->input('owner'),
                        'task_reviewer' => $request->input('BasicContract.signatory'),
                        'branch' => $internal_first_location['location'],
                        'status' => $tasks['status'],
                        'contract_id' => $contract->id
                    ]);
                }
            }
        }

        $contracthis = Contract::select('*')->where('id', $contract->id)->first();

        $contractHistory = ContractHistory::create([
            'contract_name' => $contracthis->contract_name,
            'id' => $contract->id,
            'contract_mode' => $contracthis->contract_mode,
            'contract_type' => $contracthis->contract_type,
            'contract_description' => $contracthis->contract_description,
            'contract_priority' => $request->input('priority'),

            'department_id' => $contracthis->department_id,
            'catgoery_id' => $contracthis->catgoery_id,

            'signatory' => $contracthis->signatory,
            'owner' => $contracthis->owner,


            'confidentialityagreement' => $contracthis->confidentialityagreement,
            'exclusivity' => $contract->exclusivity,

            // Contract Duration
            'signing_date' => $contracthis->signing_date,
            'commencement_type' => $contracthis->commencement_type,
            'fixed_date' => $contracthis->fixed_date,
            'event_name' => $contracthis->event_name,
            'end_contract_type' => $contracthis->end_contract_type,
            'contract_end_date' => $contracthis->contract_end_date,
            'renewal_type' => $contracthis->renewal_type,
            'period_auto_renewal' => $contracthis->period_auto_renewal,
            'period_auto_renewal_unit' => $contracthis->period_auto_renewal_unit,
            'auto_renewal_date' => $contracthis->auto_renewal_date,
            'manual_renewal_date' => $contracthis->manual_renewal_date,
            'evergreen_condition' => $contracthis->evergreen_condition,
            'termination_date' => $contracthis->termination_date,
            'termination_reason' => $contracthis->termination_reason,


            // Contract Value
            'currency' => $contracthis->currency,
            'billing_value' => $contracthis->billing_value,
            'currency_value' => $contracthis->currency_value,
            'total_value' => $contracthis->total_value,
            'payment_schedule' => $contracthis->payment_schedule,
            'currency_contract' => $contracthis->currency_contract,
            'payment_terms' => $contracthis->payment_terms,
            'billing_frequency' => $contracthis->billing_frequency,
            'taxes' => $contracthis->taxes,
            'escalation_clauses' => $contracthis->escalation_clauses,
            'discounts' => $contracthis->discounts,
            'retention' => $contracthis->retention,
            'payment_escrow' => $contracthis->payment_escrow,
            'financial_guarantees' => $contracthis->financial_guarantees,
            'currency_conversion' => $contracthis->currency_conversion,

            // Reminder Value
            'reminder_first_alert' => $contracthis->reminder_first_alert,
            'reminder_first_alertMeOn' => $contracthis->reminder_first_alertMeOn,
            'reminder_first_alert_repeats' => $contracthis->reminder_first_alert_repeats,
            'reminder_second_alert' => $contracthis->reminder_second_alert,
            'reminder_second_alertMeOn' => $contracthis->reminder_second_alertMeOn,
            'reminder_second_alert_repeats' => $contracthis->reminder_second_alert_repeats,
            'reminder_escalation_alert' => $contracthis->reminder_escalation_alert,
            'reminder_escalation_alertMeOn' => $contracthis->reminder_escalation_alertMeOn,
            'reminder_escalation_alert_repeats' => $contracthis->reminder_escalation_alert_repeats,
            'reminder_escalation_alert_after' => $contracthis->reminder_escalation_alert_after,
            'reminder_escalation_alertMeOn_after' => $contracthis->reminder_escalation_alertMeOn_after,
            'reminder_escalation_alert_repeats_after' => $contracthis->reminder_escalation_alert_repeats_after,
            'contract_status' => $contracthis->contract_status,
            'substatus' => $contracthis->substatus,  
            'rules_id' => $contracthis->rules_id,

            'custom_fields_data' => $contracthis->custom_fields_data,
            'contract_attachment' => $contracthis->contract_attachment,
            'contract_attachment_filename' => $contracthis->contract_attachment_filename,
            'created_by' => $initiatior_exists->id
        ]);

        foreach ($request->input('Partygroup.party') as $ke => $customField) {

            if (isset($customField)) {

                $mode = $customField['mode'] ?? null;
                $externalType = $customField['external_type'] ?? null;
                $internalName = $customField['internal_name'] ?? null;
                $externalName = $customField['external_name'] ?? null;
                $locationId = null;

                if ($mode !== 'External') {
                    $locationId = $mode === 'Internal'
                        ? ($customField['location'] ?? null)
                        : ($customField['location_grp'] ?? null);
                }


                // V3 only: a per-contract snapshot of the external party's vendor code,
                // address and contact. Absent on the other create pages, so these stay null.
                $vendorCode = $mode === 'External' ? ($customField['vendor_code'] ?? null) : null;
                $partyAddress = $mode === 'External' ? ($customField['party_address'] ?? null) : null;
                $contactDetails = $mode === 'External' ? ($customField['contact_details'] ?? null) : null;

                $ContractPartyData = ContractPartyData::create([
                    'custom_field_group_id' => $contract->id,
                    'contract_party_type' => $mode,
                    'party_sub_type' => $mode === 'External' ? $externalType : 'Internal',
                    'contract_party_id' => $internalName,
                    'contract_party_exe_id' => $externalName,
                    'contract_party_location_id' => $locationId,
                    'vendor_code' => $vendorCode,
                    'party_address' => $partyAddress,
                    'contact_details' => $contactDetails,
                ]);

                ContractPartyDataHistory::create([
                    'history_id' => $contractHistory->id,
                    'id' => $ContractPartyData->id,
                    'custom_field_group_id' => $contract->id,
                    'contract_party_type' => $mode,
                    'party_sub_type' => $mode === 'External' ? $externalType : 'Internal',
                    'contract_party_id' => $internalName,
                    'contract_party_location_id' => $locationId,
                    'vendor_code' => $vendorCode,
                    'party_address' => $partyAddress,
                    'contact_details' => $contactDetails,
                ]);

                //if ($ke < 2) {

                    $namePartygroup .= '-';
                    if ($mode === 'External' && !empty($externalName)) {
                        $party = ContractParties::select('company_name')->where('id', $externalName)->first();
                        if ($party && !empty($party->company_name)) {
                            $namePartygroup .= decryptString($party->company_name, 'company_name');
                        }
                    } else {
                        $namePartygroup .= DB::table('entity')
                            ->select('Nameoftheentity', decrypt_data('Nameoftheentity', 'entity'))
                            ->where('id', $internalName)
                            ->first()->Nameoftheentity;
                    }
                //}
            }
        }

        $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $owner_initiator_id)->get();

        $nextAprroverEmail = "";

        //Creating Approval Flow
        //if($request->input('contractMode') == 'new'){
        $appArr = json_decode(trim($financialLimit));
        $randNo = rand(0, 99999);
        if (is_array($appArr) && count($appArr) > 0 && isset($users[0])) {
            $approval_type = $appArr[0]->approval_type;
            $approval_status = $appArr[0]->approval_status;
            $approvalArr = $appArr[0]->approval_status;

            //if ($approval_type == 'sequential') {

                $unique_id = $contract->id . $randNo;

                if ($approval_status == 'required') {
                    $statusPreApprvr = 'Draft';
                    $statusApprvr = 'Draft';
                    $subStatusApprvr = 'Initial Draft';
                    if ($request->input('contractMode') == 'old') {
                        $statusPreApprvr = 'Negotiation';
                        $statusApprvr = 'Approval';
                        $subStatusApprvr = 'Pending Approval';
                        $approvalArr = json_decode($appArr[0]->approver);
                        foreach ($approvalArr as $key => $appVal) {
                            $approver_id = $appVal->id;
                            $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                            ApprovalContracts::create([
                                'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                'status' => encryptString($statusApprvr, 'status'),
                                'contract_id' => $contract->id,
                                'orderval' => $key,
                                'unique_id' => $unique_id,
                                'flag' => 1,
                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                'stage_name' => $statusApprvr,
                            ]);
                            if ($approval_type == 'sequential'){
                                $nextAprroverEmail = $users[0]->Email;
                                break;
                            }else{
                                if($nextAprroverEmail == ""){
                                    $nextAprroverEmail = [];
                                }
                                $multipleNextApprovers = true;
                                $nextAprroverEmail[] = $users[0]->Email;
                            }
                        }
                    }
                } else {
                    $statusPreApprvr = 'Approval';
                    $statusApprvr = 'Signing';
                    $subStatusApprvr = 'Approved';

                    $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $signatory)->get();
                    
                    if ($request->input('contractMode') == 'old') {
                        $statusPreApprvr = 'Approval';
                        $statusApprvr = 'executed';
                        $subStatusApprvr = 'active';  
                        
                        $cur_date = date('Y-m-d');
                        
                        $end_date_of_contract = $endContractDate;
                        $contract_end_type = $request->input('Duration.effectiveDate');
                        
                        if (strtotime($cur_date) > strtotime($end_date_of_contract) && $subStatusApprvr == 'active') {
                            if( $subStatusApprvr == 'active'){
                                if($contract_end_type == 'onetimeContract'){
                                    $subStatusApprvr = 'completed';
                                }
                                if($contract_end_type == 'fixedTerm'){
                                    $subStatusApprvr = 'expired';
                                }
                            }
                        }                         
                    }
                }
                
                //For Sequential Approval Flow (new contracts)
                if ($request->input('contractMode') != 'old'){

                    $base_unique = $unique_id; // group base id for chunking groups
                    // 1) Insert owner as first approver so owner must act first
                    $ownerRow = ApprovalContracts::create([
                        'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                        'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                        'status' => encryptString($statusApprvr, 'status'),
                        'contract_id' => $contract->id,
                        'orderval' => 0,
                        'unique_id' => $base_unique . '_g0',
                        'flag' => 1,
                        'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                        'approval_type_main' => $approval_type,
                        'approval_type_row' => 'sequential',
                        'approver_type_row' => 'Owner',
                        'stage_name' => 'draft',
                    ]);

                    $nextAprroverEmail = $users[0]->Email;

                    // Admin setting decides whether to insert the entire approval flow
                    // upfront or only the owner/user (rest of the flow added later).
                    $createFullApproval = filter_var(admin_setting('create_full_approval'), FILTER_VALIDATE_BOOLEAN);

                    // 2) Persist the remaining approval flow (save whole flow instead of single user)
                    if ($createFullApproval) {
                    $approversJson = $appArr[0]->approver;
                    $approvalArrFull = is_array($approversJson) ? $approversJson : json_decode($approversJson, true);
                    $ord = 1;

                    // If the rules are grouped (array of groups with 'approvers'), iterate all groups
                    if (is_array($approvalArrFull) && count($approvalArrFull) > 0) {
                        // Check if this is the parent-grouped structure
                        $isParentGrouped = false;
                        $parentGroups = $approvalArrFull;
                        $parentRouting = [];
                        
                        // Extract parent routing if present
                        if (isset($approvalArrFull['_parent_routing'])) {
                            $parentRouting = $approvalArrFull['_parent_routing'];
                        }
                        
                        // Check if array has parent-level keys (review, approval, signatory, etc.)
                        $keys = array_keys($approvalArrFull);
                        $parentKeys = ['review', 'negotiation', 'finalization', 'approval', 'signatory'];
                        $hasParentKeys = !empty(array_intersect($keys, $parentKeys));
                        
                        if ($hasParentKeys) {
                            $isParentGrouped = true;
                        }
                        
                        if ($isParentGrouped) {
                            // Process parent-grouped structure: {review: [...], approval: [...], signatory: [...]}
                            $groupIndex = 1;
                            $parentOrder = ['review', 'negotiation', 'finalization', 'approval', 'signatory'];
                            foreach ($parentOrder as $parentType) {
                                if (!isset($approvalArrFull[$parentType]) || !is_array($approvalArrFull[$parentType])) {
                                    continue;
                                }
                                
                                // Get parent-level routing for this parent type
                                $routing = $parentRouting[$parentType] ?? [];
                                $onApprove = $routing['on_approve'] ?? '';
                                $onReject = $routing['on_reject'] ?? '';
                                
                                foreach ($approvalArrFull[$parentType] as $group) {
                                    $groupData = is_array($group) ? $group : (array)$group;
                                    $groupUniqueId = $base_unique . '_g' . $groupIndex;
                                    $groupType = $groupData['approval_type'] ?? $approval_type;
                                    $groupRole = $groupData['role'] ?? 'Approver';
                                    $groupDynamicApproverEnabled = (int)($groupData['dynamic_approver_enabled'] ?? 0) === 1 ? 1 : 0;
                                    $groupApproversRaw = $groupData['approvers'] ?? [];
                                    $groupApprovers = is_array($groupApproversRaw) ? $groupApproversRaw : (json_decode((string)$groupApproversRaw, true) ?: []);

                                    $isFirstGroup = ($groupIndex === 1);
                                    $groupUniqueId = $base_unique . '_g' . $groupIndex;
                                    $unique_id = $groupUniqueId;

                                    if (strtolower($groupType) === 'parallel') {
                                        foreach ($groupApprovers as $ap) {
                                            $approver_id = $ap->id ?? $ap['id'] ?? null;
                                            if (!$approver_id) continue;
                                            $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $approver_id)->first();
                                            if (!isset($apUser->Email)) continue;

                                            ApprovalContracts::create([
                                                'username' => encryptString(json_encode(['email' => $apUser->Email, 'name' => $apUser->FirstName]), 'username'),
                                                'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                                'status' => encryptString($statusApprvr, 'status'),
                                                'contract_id' => $contract->id,
                                                'orderval' => $ord,
                                                'unique_id' => $unique_id,
                                                'flag' => ($isFirstGroup ? 1 : 0),
                                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                                'approval_type_main' => $approval_type,
                                                'approval_type_row' => $groupType,
                                                'approver_type_row' => $groupRole,
                                                'next_group_on_approve' => $onApprove,
                                                'next_group_on_reject' => $onReject,
                                                'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                            ]);

                                            $ord++;
                                        }
                                    } else {
                                        $firstInGroup = true;
                                        foreach ($groupApprovers as $ap) {
                                            $approver_id = $ap->id ?? $ap['id'] ?? null;
                                            if (!$approver_id) continue;
                                            $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $approver_id)->first();
                                            if (!isset($apUser->Email)) continue;

                                            ApprovalContracts::create([
                                                'username' => encryptString(json_encode(['email' => $apUser->Email, 'name' => $apUser->FirstName]), 'username'),
                                                'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                                'status' => encryptString($statusApprvr, 'status'),
                                                'contract_id' => $contract->id,
                                                'orderval' => $ord,
                                                'unique_id' => $unique_id,
                                                'flag' => ($isFirstGroup && $firstInGroup ? 1 : 0),
                                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                                'approval_type_main' => $approval_type,
                                                'approval_type_row' => $groupType,
                                                'approver_type_row' => $groupRole,
                                                'next_group_on_approve' => $onApprove,
                                                'next_group_on_reject' => $onReject,
                                                'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                            ]);

                                            $ord++;
                                            $firstInGroup = false;
                                        }
                                    }

                                    $groupIndex++;
                                }
                            }
                        } else {
                            // Legacy flat array structure with 'role' field
                            $groupIndex = 1;
                            foreach ($approvalArrFull as $group) {
                                $groupData = is_array($group) ? $group : (array)$group;
                                $groupUniqueId = $base_unique . '_g' . $groupIndex;
                                $groupType = $groupData['approval_type'] ?? $approval_type;
                                $groupRole = $groupData['role'] ?? 'Approver';
                                $groupDynamicApproverEnabled = (int)($groupData['dynamic_approver_enabled'] ?? 0) === 1 ? 1 : 0;
                                $groupApproversRaw = $groupData['approvers'] ?? [];
                                $groupApprovers = is_array($groupApproversRaw) ? $groupApproversRaw : (json_decode((string)$groupApproversRaw, true) ?: []);

                                $isFirstGroup = ($groupIndex === 1);
                                $groupUniqueId = $base_unique . '_g' . $groupIndex;
                                $unique_id = $groupUniqueId;

                                if (strtolower($groupType) === 'parallel') {
                                    foreach ($groupApprovers as $ap) {
                                        $approver_id = $ap->id ?? $ap['id'] ?? null;
                                        if (!$approver_id) continue;
                                        $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $approver_id)->first();
                                        if (!isset($apUser->Email)) continue;

                                        ApprovalContracts::create([
                                            'username' => encryptString(json_encode(['email' => $apUser->Email, 'name' => $apUser->FirstName]), 'username'),
                                            'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                            'status' => encryptString($statusApprvr, 'status'),
                                            'contract_id' => $contract->id,
                                            'orderval' => $ord,
                                            'unique_id' => $unique_id,
                                            'flag' => ($isFirstGroup ? 1 : 0),
                                            'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                            'approval_type_main' => $approval_type,
                                            'approval_type_row' => $groupType,
                                            'approver_type_row' => $groupRole,
                                            'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                        ]);

                                        $ord++;
                                    }
                                } else {
                                    // sequential group
                                    $firstInGroup = true;
                                    foreach ($groupApprovers as $ap) {
                                        $approver_id = $ap->id ?? $ap['id'] ?? null;
                                        if (!$approver_id) continue;
                                        $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $approver_id)->first();
                                        if (!isset($apUser->Email)) continue;

                                        ApprovalContracts::create([
                                            'username' => encryptString(json_encode(['email' => $apUser->Email, 'name' => $apUser->FirstName]), 'username'),
                                            'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                            'status' => encryptString($statusApprvr, 'status'),
                                            'contract_id' => $contract->id,
                                            'orderval' => $ord,
                                            'unique_id' => $unique_id,
                                            'flag' => ($isFirstGroup && $firstInGroup ? 1 : 0),
                                            'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                            'approval_type_main' => $approval_type,
                                            'approval_type_row' => $groupType,
                                            'approver_type_row' => $groupRole,
                                            'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                        ]);

                                        $ord++;
                                        $firstInGroup = false;
                                    }
                                }

                                $groupIndex++;
                            }
                        }
                    } else {
                        // Legacy simple approver array: activate only first approver (group g1)
                        $groupUniqueId = $base_unique . '_g1';
                        $first = true;
                        foreach ($approvalArrFull as $apVal) {
                            $approver_id = $apVal->id ?? $apVal;
                            if (!$approver_id) continue;
                            $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $approver_id)->first();
                            if (!isset($apUser->Email)) continue;

                            ApprovalContracts::create([
                                'username' => encryptString(json_encode(['email' => $apUser->Email, 'name' => $apUser->FirstName]), 'username'),
                                'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                'status' => encryptString($statusApprvr, 'status'),
                                'contract_id' => $contract->id,
                                'orderval' => $ord,
                                'unique_id' => $groupUniqueId,
                                'flag' => ($first ? 1 : 0),
                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                'approval_type_main' => $approval_type,
                                'approval_type_row' => 'sequential',
                                'approver_type_row' => 'Approver',
                            ]);

                            $ord++;
                            $first = false;
                        }
                    }
                    } // end if ($createFullApproval)

                    Contract::where('id', $contract->id)->update(['contract_status' => $statusApprvr, 'substatus' => $subStatusApprvr]);
                }
            //}
        }
        //}

        $namePartygroup .= '-' . date("Y");

        $namePartygroup = encryptString($namePartygroup, 'contract_name');

        //Unique Code

        $con_code = sprintf('%04d', $contract->id);
        $unique_code = "CON" . $internal_first_location['internal_name'] . $request->input('BasicContract.DepartmentType') . $request->input('BasicContract.catgoeryType') . $internal_first_location['location'] . $con_code;

        Contract::where('id', $contract->id)->update(['contract_name' => $namePartygroup, 'contract_unique_id' => $unique_code]);


        if ($request->hasFile('file')) {

            $file = $request->file('file');
            $filename = file_name($file);
            $filePath = $controller->storeFile($file, '', $contract->id, $filename);
            if(!$filePath){
                return redirect($this->createRedirectPath)->withErrors(array_merge($fileError, ['Storage Server Down/Token Expire']))->withInput();
            }            
            $senattment['filename'][] = $filename;
            $senattment['filurl'][] = $filePath;
            
            $finalNotifiers = "";
            
            //For Getting Notifiers List
            if(isset($signatory_data_decoded['notify']) && count($signatory_data_decoded['notify']) > 0){
                $finalNotifiers = $signatory_data_decoded['notify'];
                $finalNotifiers = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $finalNotifiers)->pluck('Email')->toArray();
            }
            
            if(isset($all_approvers) && count($all_approvers) > 0){

                $approverIds = $this->collectApproverIdsFromJson($all_approvers);
                $approversArr = [];
                if (count($approverIds) > 0) {
                    $approversArr = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $approverIds)->pluck('Email')->toArray();
                }
                
                if($finalNotifiers == ""){
                    $finalNotifiers = [];
                } 
                
                $finalNotifiers = array_unique(array_merge($finalNotifiers, $approversArr));
            }

            if ($nextAprroverEmail != "") {
                $controller->changePermission($filePath, $finalNotifiers, $nextAprroverEmail);
                $MailSent = $emailTrigger->sendEmail($contract->id, '', '', $nextAprroverEmail, 'New Contract Created Alert', $senattment['filename'],  $senattment['filurl'], 'newContract');
            }
            Contract::where('id', $contract->id)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $filename]);
            ContractHistory::where('history_id', $contractHistory->history_id)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $filename]);
            
            $contracts = Contract::select('*')->where('id', $contract->id)->get();
            $ContractsFinal = $this->availableContracts($contracts, true);
            
            if(env('replace_doc_var_upload_docx')){
                $this->wordDocumentReaderActions($ContractsFinal[0], true, true);
            }
            //DB::commit();
        }

        //Create Document From Agreement Template
        if ($request->input('attachments_type') !== null && $request->input('attachments_type') == 'template' && !$request->hasFile('file')) {

            $storagePath = '/storage/app/';
            $generateDocPath = $controller->get_file_path($contract->id);

            $agreementTemplateId = $request->input('agreement_template_id');
            $template = \App\Models\AgreementTemplate::find($agreementTemplateId);

            if (!$template || empty($template->source_docx_path)) {
                DB::rollBack();
                return redirect($this->createRedirectPath)->withErrors(['Agreement template not found or has no DOCX file.'])->withInput();
            }

            // Get source DOCX path
            $sourceDocxPath = Storage::disk('local')->path($template->source_docx_path);
            if (!file_exists($sourceDocxPath)) {
                DB::rollBack();
                return redirect($this->createRedirectPath)->withErrors(['Template DOCX file not found.'])->withInput();
            }

            $generatedDocumentName = 'drafted_contract_' . strtotime(date('d-m-y h:i:s')) . '.docx';
            $senattment['filename'][] = $generatedDocumentName;

            // Copy template DOCX to temp, replace variables, then store
            $tempPath = base_path() . '/storage/app/contracts/tempDocs/' . $generatedDocumentName;
            $tempDir = dirname($tempPath);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            copy($sourceDocxPath, $tempPath);

            // Replace variables in the DOCX file using TemplateProcessor
            try {
                $phpWord_process = new \PhpOffice\PhpWord\TemplateProcessor($tempPath);

                $allCustomVars = \App\Models\CustomVarDocs::where('status', 1)->get();
                $dataFetchedArray = $this->dataFetchForCustomVars($contract);

                $templateVars = [];
                foreach ($allCustomVars as $cusVars) {
                    $replaceText = $dataFetchedArray[$cusVars->var_table][$cusVars->var_field] ?? $cusVars->var_var;
                    $inVar = $cusVars->var_var;
                    $outVar = preg_replace('/^\$\{(.+)\}$/', '$1', $inVar);
                    $templateVars[$outVar] = $replaceText;
                }

                $phpWord_process->setValues($templateVars);
                $phpWord_process->saveAs($tempPath);
            } catch (\Throwable $e) {
                \Log::error('Template variable replacement error: ' . $e->getMessage());
            }

            // Store the processed file
            if (fileStorageType() == "Local") {
                $finalPath = base_path() . $storagePath . $generateDocPath . '/' . $generatedDocumentName;

                $dir = dirname($finalPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                copy($tempPath, $finalPath);
                $finalFilePathName = $generateDocPath . '/' . $generatedDocumentName;
                unlink($tempPath);
            } else {
                $finalFilePathName = $controller->storeContent($tempPath, $generateDocPath, $generatedDocumentName);
                unlink($tempPath);

                if(!$finalFilePathName){
                    DB::rollBack();
                    return redirect($this->createRedirectPath)->withErrors(['File Upload Issue'])->withInput();
                }
            }

            $senattment['filurl'][] = $finalFilePathName;
            
            $finalNotifiers = "";
            
            //For Getting Notifiers List
            if(isset($signatory_data_decoded['notify']) && count($signatory_data_decoded['notify']) > 0){

                $finalNotifiers = $signatory_data_decoded['notify'];
                $finalNotifiers = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $finalNotifiers)->pluck('Email')->toArray();                

            }
            
            $finalNotifiers = [];
            
            if(isset($all_approvers) && count($all_approvers) > 0){

                $approverIds = $this->collectApproverIdsFromJson($all_approvers);
                $approversArr = [];
                if (count($approverIds) > 0) {
                    $approversArr = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $approverIds)->pluck('Email')->toArray();
                }
                
                $finalNotifiers = array_unique(array_merge($finalNotifiers, $approversArr));
            }            

            if ($nextAprroverEmail != "") {
                $controller->changePermission($finalFilePathName, $finalNotifiers, $nextAprroverEmail);
                $MailSent = $emailTrigger->sendEmail($contract->id, '', '', $nextAprroverEmail, 'New Contract Created Alert', $senattment['filename'],  $senattment['filurl'], 'newContract');
            }

            Contract::where('id', $contract->id)->update(['contract_attachment' => $finalFilePathName, 'contract_attachment_filename' => $generatedDocumentName]);

            ContractHistory::where('history_id', $contractHistory->history_id)->update(['contract_attachment' => $finalFilePathName, 'contract_attachment_filename' => $generatedDocumentName]);

        }
        
        $contract->refresh();

        // V3 annexures. No-op unless the request carries annexure uploads.
        $this->storeContractAnnexures($request, $contract, $controller, $initiatior_exists->id ?? null);

        if ($triggerLegalContact && $selectedLegalAdvisor && $legalContactComment !== '') {
            $legalViewLink = route('contracts.legal.view', ['id' => $contract->id]);
            $description = 'Contract ' . ($contract->contract_unique_id)
                . ' has been shared for legal information/advice.'
                . ' <br/><b>Comment</b>: ' . $legalContactComment
                . ' <br/><b>Requested from</b>: ' . $legalRequesterName . ' (' . $legalRequesterEmail . ')'
                . ' <b>Review link</b>: ' . $legalViewLink;

            $emailTrigger->sendEmail(
                $contract->id,
                $description,
                'Legal Information Advise Request',
                $selectedLegalAdvisor->email_id,
                'Information Request',
                [],
                [],
                'legalMail'
            );
        }

        //Create User Action Log
        $this->crudUserActionLog($contract->id,'contract','create', $contract->id, 1, $users[0]->Email ?? '',false, ($users[0]->FirstName ?? '')." ".($users[0]->LastName ?? ''));
        
        DB::commit();

        return redirect('/contracts/list')->with('message', 'Contract Created Successfully Click <a href="' . url('/contracts/' . $contract->id) . '">Here</a> to view')->with('alert-class', 'alert-success');
    }

    public function updateContract(Request $request)
    {
        
        $rulesContract = [
            "contractMode" => 'required',
            "legal_advisor_id" => 'nullable|integer|exists:legal_advisors,id',
            "BasicContract.contractType" => 'required',
            "BasicContract.catgoeryType" => 'required',
            "BasicContract.DepartmentType" => 'required',
        ];

        $messages = [
            "required" => 'Please Fill Mandatory Fields :attribute',
            "BasicContract.contractType.required" => 'Please Choose Contract Type in Basic Contract Information Section',
            "BasicContract.catgoeryType.required" => 'Please Choose Category in Basic Contract Information Section',
            "BasicContract.DepartmentType.required" => 'Please Choose Department in Basic Contract Information Section',
        ];        
        $validator =  Validator::make($request->all(), $rulesContract, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if (!$validator->errors()->has('file')) {
                $validator->errors()->add('file', 'On Behalf Above Validation Errors File was cleared Please Upload Contract Document in Attachments');
            }
            return redirect('contracts/' . $request->contractid . '?tab=edit')->withErrors($validator)->withInput();
        }
        $contracts = Contract::select('*')->where('id', $request->contractid)->get();

        $ContractsFinal = $this->availableContracts($contracts, true);

        if (count($ContractsFinal) == 0) {
            return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract Access')->with('alert-class', 'alert-danger');
        }
        
        if (strtolower($ContractsFinal[0]->substatus) == 'renewed'){
            return redirect('/contracts/list')->with('message', 'Modification is not permitted; the contract has been renewed.')->with('alert-class', 'alert-danger');
        }       
        
        $endContractDate = null;

        switch ($request->input("Duration.effectiveDate")) {
            case 'fixedTerm':
                $endContractDate = $request->input('Duration.fixedtimeEndDateofContract');
                break;
            case 'onetimeContract':
                $endContractDate = $request->input('Duration.onetimeEndDateofContract');
                break;
        }


        $locals = $request->input('Partygroup.party');
        
        $totalInternals = 0;
        $totalExternals = 0;
        $sameExternals = [];
        $sameInternals = [];

        
        $fileError = [];
        
        if ($request->hasFile('file')){
            $fileError = ['On Behalf Below Validation Errors File was cleared Please Upload Contract Document in Attachments'];
        }         
        

        //To Get First Internal Party Location
        $internal_first_location = "";
        //$external_first_exist = "";
        foreach ($locals as $partyLoc) {
            if (isset($partyLoc['location']) && $partyLoc['location'] != "" && $partyLoc['mode'] == "Internal") {
                if ($totalInternals == 0) {
                    $internal_first_location = $partyLoc;
                }
                $totalInternals++;
                if (!in_array($partyLoc['location'], $sameInternals)) {
                    $sameInternals[] = $partyLoc['location'];
                }else{
                    $invalid_location_error = array('Duplicate internal/intergroup party with location in Party Details');
                    return redirect('contracts/' . $request->contractid . '?tab=edit')->withErrors(array_merge($fileError, $invalid_location_error))->withInput();                    
                }                
            }
            if (isset($partyLoc['mode']) && $partyLoc['mode'] == "External" && isset($partyLoc['external_name'])) {
                $totalExternals++;
                if (!in_array($partyLoc['external_name'], $sameExternals)) {
                    $sameExternals[] = $partyLoc['external_name'];
                }else{
                    $invalid_external_error = array('Duplicate external party in Party Details');
                    return redirect('contracts/' . $request->contractid . '?tab=edit')->withErrors(array_merge($fileError, $invalid_external_error))->withInput();                    
                }                
            }
            if (isset($partyLoc['mode']) && $partyLoc['mode'] == "Intergroup" && isset($partyLoc['location_grp'])) {
                $totalExternals++;
                if (!in_array($partyLoc['location_grp'], $sameInternals)) {
                    $sameInternals[] = $partyLoc['location_grp'];
                }else{
                    $invalid_location_error = array('Duplicate internal/intergroup party with location in Party Details');
                    return redirect('contracts/' . $request->contractid . '?tab=edit')->withErrors(array_merge($fileError, $invalid_location_error))->withInput();                    
                }
            }
        }

        if ($internal_first_location == "") {
            $invalid_location_error = array('Please Choose one internal party with location in Party Details');
            return redirect('contracts/' . $request->contractid . '?tab=edit')->withErrors(array_merge($fileError, $invalid_location_error))->withInput();
        }

        if ($totalInternals > 1) {
            $invalid_location_error = array('Please Choose Only One internal party with location in Party Details');
            return redirect('contracts/' . $request->contractid . '?tab=edit')->withErrors(array_merge($fileError, $invalid_location_error))->withInput();
        }

        if ($totalExternals == 0) {
            $missing_external_error = array('Please Choose atleast One external/intergroup party in Party Details');
            return redirect('contracts/' . $request->contractid . '?tab=edit')->withErrors(array_merge($fileError, $missing_external_error))->withInput();
        }

        $approval_user_column = "approval_required_users_edit";
        $approvalTypeGlobal = "edit";
        if ($request->input('contractMode') != 'new') {
            $approval_user_column = "approval_required_users_legacy_edit";
            $approvalTypeGlobal = "legacy_edit";
        }

        //For Renewal/Addendum Edits
        if ($ContractsFinal[0]->parentcontract > 0) {
            if ($ContractsFinal[0]->renewtype == 'renewed') {
                $approval_user_column = "approval_required_users_renewed";
                $approvalTypeGlobal = "renewed";
            } else {
                $approval_user_column = "approval_required_users_addendum";
                $approvalTypeGlobal = "addendum";
            }
        }
        
        
        //Check Contract Duplicates Start
        
        $existContract = $this->checkDuplicateContracts($locals, $request, $endContractDate, false, $request->contractid);
        
        if($existContract){
            return redirect('contracts/' . $request->contractid . '?tab=edit')->withErrors(array_merge($fileError, ['Duplicate contract details detected — contract already exists <a href="' . url('/contracts/' . $existContract->id) . '" target="new">'.$existContract->contract_unique_id.'</a>']))->withInput();
        }
        
        
        //Check Contract Duplicates End         

        $financialLimit = $this->financialLimit(
            $internal_first_location['location'],
            $request->input('BasicContract.DepartmentType'),
            $request->input('BasicContract.catgoeryType'),
            $request->input('BasicContract.contractType'),
            $request->input('ContractValue.value'),
            $approval_user_column
        );

        $financialLimitDecoded = json_decode($financialLimit)[0];

        $signatory_data_decoded = (array)json_decode($financialLimitDecoded->signatory);
        $app_type_data_decoded = (array)json_decode($financialLimitDecoded->approval_type);
        $app_status_data_decoded = (array)json_decode($financialLimitDecoded->approval_status);

        $signatory_array = (array)($signatory_data_decoded['sign']);
        $owner_array = (array)($signatory_data_decoded['owner']);
        $signatory_data_decoded = [];
        $signatory_data_decoded['sign'] = $signatory_array[$approvalTypeGlobal];
        $signatory_data_decoded['owner'] = $owner_array[$approvalTypeGlobal];
        $financialLimitDecoded->signatory = json_encode($signatory_data_decoded);
        $financialLimitDecoded->approval_type = $app_type_data_decoded[$approvalTypeGlobal];
        $financialLimitDecoded->approval_status = $app_status_data_decoded[$approvalTypeGlobal];


        $all_approvers = json_decode($financialLimitDecoded->approver, true);




        $branchHeads = BranchUser::select(
            'id',
            decrypt_data('branchheadname', 'branch'),
            'Branchhead',
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->where('id', $internal_first_location['location'])->first();


        $branchHeadsError = [];
        foreach ($all_approvers as $ap_data) {
            if ($ap_data->type == 'designation') {
                if ($ap_data->name == 'branch_head') {
                    $branchHeadId = $branchHeads->Branchhead;
                    if ($branchHeadId == null) {
                        $branchHeadsError[] = "Branch Head Not Added in your selected Branch Please Update In Goal Portal";
                    }
                    $ap_data->id = $branchHeadId;
                }
                if ($ap_data->name == 'branch_dep_head') {
                    $branchDeptData = unserialize($branchHeads->departments);
                    //print_r($branchDeptData);
                    if (!isset($branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')])) {
                        $branchHeadsError[] = "Branch Department Head Not Added in your selected Branch Please Update In Goal Portal";
                    } else {
                        $ap_data->id = $branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')];
                    }
                }
                if ($ap_data->name == 'overall_dept_head') {
                    $entityDeptHead = EntityBusiness::select('overall_dept_head')->where('id', $request->input('BasicContract.DepartmentType'))->first();
                    if (!$entityDeptHead || !$entityDeptHead->overall_dept_head) {
                        $branchHeadsError[] = "Department Over All Head Not Added in your Entity Business Please Update In Goal Portal";
                    } else {
                        $ap_data->id = $entityDeptHead->overall_dept_head;
                    }
                }
            }
        }

        $financialLimitDecoded->approver = json_encode($all_approvers);

        $financialLimit = json_encode([$financialLimitDecoded]);

        $contracts = Contract::select('*')->where('id', $request->contractid)->get();

        $ContractsFinal = $this->availableContracts($contracts, true);

        if (count($ContractsFinal) == 0) {
            return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract Access')->with('alert-class', 'alert-danger');
        }


        if (strtolower($ContractsFinal[0]->substatus) == 'renewed') {
            return redirect('contracts/' . $request->contractid . '?tab=edit')->withErrors(['Edit Not Allowed For This Contract because its renewed.'])->withInput();
        }

        $selectedLegalAdvisor = null;
        if ($request->filled('legal_advisor_id')) {
            $selectedLegalAdvisor = LegalAdvisor::where('id', (int) $request->input('legal_advisor_id'))->where('status', 1)->first();
            if (!$selectedLegalAdvisor) {
                return redirect('contracts/' . $request->contractid . '?tab=edit')->withErrors(array_merge($fileError, ['Selected legal advisor is invalid or inactive.']))->withInput();
            }
        }
        
        $contract = Contract::where('id', $request->contractid)->update([

            'contract_mode' => encryptString($request->input('contractMode'), 'contract_mode'),
            'contract_tags' => json_encode($request->input('BasicContract.contractTypeTags') ?? []),
            'contract_description' => encryptString($request->input('BasicContract.contractDescription'), 'contract_description'),

            'owner' => $request->input('owner'),
            'legal_advisor_id' => $selectedLegalAdvisor->id ?? null,
            'legal_advisor_email' => $selectedLegalAdvisor->email_id ?? null,
            'contract_priority' => $request->input('priority'),
            'confidentialityagreement' => $request->input('BasicContract.Confidentialityagreement'),
            'exclusivity' => encryptString($request->input('BasicContract.Exclusivity'), 'exclusivity'),



            // Contract Duration
            'signing_date' => $request->input('Duration.signingDate'),
            'commencement_type' => encryptString($request->input('Duration.commencementDate'), 'commencement_type'), // corrected key
            'fixed_date' => $request->input('Duration.fixedDate'),
            'event_name' => encryptString($request->input('Duration.eventDetails'), 'event_name'),
            'end_contract_type' => encryptString($request->input('Duration.effectiveDate'), 'end_contract_type'),
            // 'onetime_end_date' => $request->input('Duration.onetimeEndDateofContract'),
            // 'fixedterm_end_date' => $request->input('Duration.fixedtimeEndDateofContract'),
            'contract_end_date' => $endContractDate,
            'renewal_type' => encryptString($request->input('Duration.typeRenewal'), 'renewal_type'),
            'period_auto_renewal' => $request->input('Duration.periodAutoRenewal'),
            'period_auto_renewal_unit' => encryptString($request->input('Duration.periodAutoRenewalPeriod'), 'period_auto_renewal_unit'),
            'auto_renewal_date' => $request->input('Duration.autoRenewalDate'),
            'manual_renewal_date' => $request->input('Duration.autoManualRenewalDate'),
            'evergreen_condition' => encryptString($request->input('Duration.conditionEndContract'), 'evergreen_condition'),
            'termination_date' => $request->input('Duration.terminationDate'),
            'termination_reason' => encryptString($request->input('Duration.reasonTermination'), 'termination_reason'),


            // Contract Value

            'currency' => encryptString($request->input('ContractValue.currency'), 'currency'),
            'billing_value' => encryptString($request->input('ContractValue.billingvalue'), 'billing_value'),
            'currency_value' => encryptString($request->input('ContractValue.value'), 'currency_value'),
            'total_value' => encryptString($request->input('ContractValue.totalvalue'), 'total_value'),
            'payment_schedule' => encryptString($request->input('ContractValue.paymentSchedule'), 'payment_schedule'),
            'currency_contract' => encryptString($request->input('ContractValue.currencyContract'), 'currency_contract'),
            'payment_terms' => encryptString($request->input('ContractValue.paymentTerms'), 'payment_terms'),
            'billing_frequency' => encryptString($request->input('ContractValue.billingFrequency'), 'billing_frequency'),
            'taxes' => encryptString($request->input('ContractValue.taxes'), 'taxes'),
            'escalation_clauses' => encryptString($request->input('ContractValue.escalationClauses'), 'escalation_clauses'),
            'discounts' => encryptString($request->input('ContractValue.discounts'), 'discounts'),
            'retention' => encryptString($request->input('ContractValue.retention'), 'retention'),
            'payment_escrow' => encryptString($request->input('ContractValue.paymentEscrow'), 'payment_escrow'),
            'financial_guarantees' => encryptString($request->input('ContractValue.financialGuarantees'), 'financial_guarantees'),
            'currency_conversion' => encryptString($request->input('ContractValue.currencyConversion'), 'currency_conversion'),

            'reminder_enable' => encryptString($request->input('Duration.reminderEnable'), 'reminder_enable') ?? null,
            'reminder_first_alert' => encryptString($request->input('Duration.Reminder.first.alertMe'), 'reminder_first_alert'),
            'reminder_first_alertMeOn' => encryptString($request->input('Duration.Reminder.first.alertMeDay') . ' ' . $request->input('Duration.Reminder.first.alertMePrior') . ' ' . $request->input('Duration.Reminder.first.alertMeType'), 'reminder_first_alertMeOn'),
            'reminder_first_alert_repeats' => encryptString($request->input('Duration.Reminder.first.repeats'), 'reminder_first_alert_repeats'),
            'reminder_second_alert' => encryptString($request->input('Duration.Reminder.second.alertMe'), 'reminder_second_alert'),
            'reminder_second_alertMeOn' => encryptString($request->input('Duration.Reminder.second.alertMeDay') . ' ' . $request->input('Duration.Reminder.second.alertMePrior') . ' ' . $request->input('Duration.Reminder.second.alertMeType'), 'reminder_second_alertMeOn'),
            'reminder_second_alert_repeats' => encryptString($request->input('Duration.Reminder.second.repeats'), 'reminder_second_alert_repeats'),
            'reminder_escalation_alert' => encryptString($request->input('Duration.Reminder.escalation.alertMe'), 'reminder_escalation_alert'),
            'reminder_escalation_alertMeOn' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay') . ' ' . $request->input('Duration.Reminder.escalation.alertMePrior') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType'), 'reminder_escalation_alertMeOn'),
            'reminder_escalation_alert_repeats' => encryptString($request->input('Duration.Reminder.escalation.repeats'), 'reminder_escalation_alert_repeats'),
            'reminder_escalation_alert_after' => encryptString($request->input('Duration.Reminder.escalation.alertMe_after'), 'reminder_escalation_alert_after'),
            'reminder_escalation_alertMeOn_after' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay_after') . ' ' . $request->input('Duration.Reminder.escalation.alertMeAfter') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType_after'), 'reminder_escalation_alertMeOn_after'),
            'reminder_escalation_alert_repeats_after' => encryptString($request->input('Duration.Reminder.escalation.repeats_after'), 'reminder_escalation_alert_repeats_after'),

            'rules_id' => $financialLimit,


        ]);


        $ContractHistory = ContractHistory::create([
            'id' => $request->contractid,
            'contract_mode' => encryptString($request->input('contractMode'), 'contract_mode'),
            'contract_type' => $request->input('BasicContract.contractType'),
            'contract_description' => encryptString($request->input('BasicContract.contractDescription'), 'contract_description'),
            'department_id' => $request->input('BasicContract.DepartmentType'),
            'catgoery_id' => $request->input('BasicContract.catgoeryType'),
            'contract_priority' => $request->input('priority'),
            'signatory' => $ContractsFinal[0]->signatory,
            'owner' => $ContractsFinal[0]->owner,
            'confidentialityagreement' => $request->input('BasicContract.Confidentialityagreement'),
            'exclusivity' => encryptString($request->input('BasicContract.Exclusivity'), 'exclusivity'),



            // Contract Duration
            'signing_date' => $request->input('Duration.signingDate'),
            'commencement_type' => encryptString($request->input('Duration.commencementDate'), 'commencement_type'),
            'fixed_date' => $request->input('Duration.fixedDate'),
            'event_name' => encryptString($request->input('Duration.eventDetails'), 'event_name'),
            'end_contract_type' => encryptString($request->input('Duration.effectiveDate'), 'end_contract_type'),
            // 'onetime_end_date' => $request->input('Duration.onetimeEndDateofContract'),
            // 'fixedterm_end_date' => $request->input('Duration.fixedtimeEndDateofContract'),
            'contract_end_date' => $endContractDate,
            'renewal_type' => encryptString($request->input('Duration.typeRenewal'), 'renewal_type'),
            'period_auto_renewal' => $request->input('Duration.periodAutoRenewal'),
            'period_auto_renewal_unit' => encryptString($request->input('Duration.periodAutoRenewalPeriod'), 'period_auto_renewal_unit'),
            'auto_renewal_date' => $request->input('Duration.autoRenewalDate'),
            'manual_renewal_date' => $request->input('Duration.autoManualRenewalDate'),
            'evergreen_condition' => encryptString($request->input('Duration.conditionEndContract'), 'evergreen_condition'),
            'termination_date' => $request->input('Duration.terminationDate'),
            'termination_reason' => encryptString($request->input('Duration.reasonTermination'), 'termination_reason'),

            // Contract Value
            'currency' => encryptString($request->input('ContractValue.currency'), 'currency'),
            'billing_value' => encryptString($request->input('ContractValue.billingvalue'), 'billing_value'),
            'currency_value' => encryptString($request->input('ContractValue.value'), 'currency_value'),
            'total_value' => encryptString($request->input('ContractValue.totalvalue'), 'total_value'),
            'payment_schedule' => encryptString($request->input('ContractValue.paymentSchedule'), 'payment_schedule'),
            'currency_contract' => encryptString($request->input('ContractValue.currencyContract'), 'currency_contract'),
            'payment_terms' => encryptString($request->input('ContractValue.paymentTerms'), 'payment_terms'),
            'billing_frequency' => encryptString($request->input('ContractValue.billingFrequency'), 'billing_frequency'),
            'taxes' => encryptString($request->input('ContractValue.taxes'), 'taxes'),
            'escalation_clauses' => encryptString($request->input('ContractValue.escalationClauses'), 'escalation_clauses'),
            'discounts' => encryptString($request->input('ContractValue.discounts'), 'discounts'),
            'retention' => encryptString($request->input('ContractValue.retention'), 'retention'),
            'payment_escrow' => encryptString($request->input('ContractValue.paymentEscrow'), 'payment_escrow'),
            'financial_guarantees' => encryptString($request->input('ContractValue.financialGuarantees'), 'financial_guarantees'),
            'currency_conversion' => encryptString($request->input('ContractValue.currencyConversion'), 'currency_conversion'),

            'reminder_enable' => encryptString($request->input('Duration.reminderEnable'), 'reminder_enable') ?? null,
            'reminder_first_alert' => encryptString($request->input('Duration.Reminder.first.alertMe'), 'reminder_first_alert'),
            'reminder_first_alertMeOn' => encryptString($request->input('Duration.Reminder.first.alertMeDay') . ' ' . $request->input('Duration.Reminder.first.alertMePrior') . ' ' . $request->input('Duration.Reminder.first.alertMeType'), 'reminder_first_alertMeOn'),
            'reminder_first_alert_repeats' => encryptString($request->input('Duration.Reminder.first.repeats'), 'reminder_first_alert_repeats'),
            'reminder_second_alert' => encryptString($request->input('Duration.Reminder.second.alertMe'), 'reminder_second_alert'),
            'reminder_second_alertMeOn' => encryptString($request->input('Duration.Reminder.second.alertMeDay') . ' ' . $request->input('Duration.Reminder.second.alertMePrior') . ' ' . $request->input('Duration.Reminder.second.alertMeType'), 'reminder_second_alertMeOn'),
            'reminder_second_alert_repeats' => encryptString($request->input('Duration.Reminder.second.repeats'), 'reminder_second_alert_repeats'),
            'reminder_escalation_alert' => encryptString($request->input('Duration.Reminder.escalation.alertMe'), 'reminder_escalation_alert'),
            'reminder_escalation_alertMeOn' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay') . ' ' . $request->input('Duration.Reminder.escalation.alertMePrior') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType'), 'reminder_escalation_alertMeOn'),
            'reminder_escalation_alert_repeats' => encryptString($request->input('Duration.Reminder.escalation.repeats'), 'reminder_escalation_alert_repeats'),
            'reminder_escalation_alert_after' => encryptString($request->input('Duration.Reminder.escalation.alertMe_after'), 'reminder_escalation_alert_after'),
            'reminder_escalation_alertMeOn_after' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay_after') . ' ' . $request->input('Duration.Reminder.escalation.alertMeAfter') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType_after'), 'reminder_escalation_alertMeOn_after'),
            'reminder_escalation_alert_repeats_after' => encryptString($request->input('Duration.Reminder.escalation.repeats_after'), 'reminder_escalation_alert_repeats_after')

        ]);

        foreach ($request->input('customFields') ?? [] as $customField) {
            if (isset($customField)) {

                if (isset($customField['id']) && isset($customField['value']) && isset($request->contractid)) {
                    CustomFieldsData::updateOrCreate(
                        [
                           'custom_field_id' => $customField['id'],
                           'custom_field_group_id' => $request->contractid
                        ],
                        [
                        'custom_field_id' => $customField['id'],
                        'custom_field_group' => 'contracts',
                        'custom_field_value' => $customField['value'],
                        'custom_field_group_id' => $request->contractid
                        ]
                    );
                }
            }
        }

        $namePartygroup = "";


        $contractTypeName = ContractType::where('contract_type_id', $request->input('BasicContract.contractType'))->first();

        $namePartygroup =  $contractTypeName->contract_type;
        
        if ($request->input('Partygroup.party')) {
            foreach ($request->input('Partygroup.party') as $ke => $customField) {
                if (isset($customField)) {

                    $mode = $customField['mode'] ?? null;
                    $externalType = $customField['external_type'] ?? null;
                    $internalName = $customField['internal_name'] ?? null;
                    $externalName = $customField['external_name'] ?? null;
                    $locationId = null;

                    if ($mode !== 'External') {
                        $locationId = $mode === 'Internal'
                            ? ($customField['location'] ?? null)
                            : ($customField['location_grp'] ?? null);
                    }

                    if (isset($customField['id'])) {

                        ContractPartyData::where('id', $customField['id'])->update([
                            'custom_field_group_id' => $request->contractid,
                            'contract_party_type' => $mode,
                            'party_sub_type' => $mode === 'External' ? $externalType : 'Internal',
                            'contract_party_id' => $internalName,
                            'contract_party_exe_id' => $externalName,
                            'contract_party_location_id' => $locationId,
                        ]);
                        ContractPartyDataHistory::create([
                            'history_id' => $ContractHistory->id,
                            'id' => $customField['id'],
                            'custom_field_group_id' => $request->contractid,
                            'contract_party_type' => $mode,
                            'party_sub_type' => $mode === 'External' ? $externalType : 'Internal',
                            'contract_party_id' => $internalName,
                            'contract_party_exe_id' => $externalName,
                            'contract_party_location_id' => $locationId,
                        ]);
                    } else {

                        $ContractPartyDatanew = ContractPartyData::create([
                            'custom_field_group_id' => $request->contractid,
                            'contract_party_type' => $mode,
                            'party_sub_type' => $mode === 'External' ? $externalType : 'Internal',
                            'contract_party_id' => $internalName,
                            'contract_party_exe_id' => $externalName,
                            'contract_party_location_id' => $locationId,
                        ]);
                        ContractPartyDataHistory::create([
                            'history_id' => $ContractHistory->id,
                            'id' => $ContractPartyDatanew->id,
                            'custom_field_group_id' => $request->contractid,
                            'contract_party_type' => $mode,
                            'party_sub_type' => $mode === 'External' ? $externalType : 'Internal',
                            'contract_party_id' => $internalName,
                            'contract_party_exe_id' => $externalName,
                            'contract_party_location_id' => $locationId,
                        ]);
                    }
                    
                    $namePartygroup .= '-';
                    if ($mode === 'External' && !empty($externalName)) {
                        $party = ContractParties::select('company_name')->where('id', $externalName)->first();
                        if ($party && !empty($party->company_name)) {
                            $namePartygroup .= decryptString($party->company_name, 'company_name');
                        }
                    } else {
                        $namePartygroup .= DB::table('entity')
                            ->select('Nameoftheentity', decrypt_data('Nameoftheentity', 'entity'))
                            ->where('id', $internalName)
                            ->first()->Nameoftheentity;
                    }                    
                }
            }
        }


        $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->get();

        $namePartygroup .= '-' . date("Y");

        if ($request->input("Duration.effectiveDate") == 'evergreen' && $request->input('contractMode') == 'old') {
            Contract::where('id', $request->contractid)->update([
                'contract_status' => 'executed',
                'substatus' => 'active'
            ]);
        }
        
        $namePartygroup = encryptString($namePartygroup, 'contract_name');
        
        Contract::where('id', $request->contractid)->update(['contract_name' => $namePartygroup]);
        ContractHistory::where('history_id', $ContractHistory->id)->update(['contract_name' => $namePartygroup]);
        
        

        $appArr = json_decode(trim($financialLimit));

        $nextAprroverEmail = "";
        if (strtolower($ContractsFinal[0]->contract_status) == 'executed' && env('approval_rules_on_edit')) {
            $nextAprroverEmail = $this->approverInsertOnContractActions($appArr, $ContractsFinal[0], true);
        }else{
            $randNo = rand(0, 99999);
            if (is_array($appArr) && count($appArr) > 0 && isset($users[0])) {
                $approval_type = $appArr[0]->approval_type;
                $approval_status = $appArr[0]->approval_status;
                $approvalArr = $appArr[0]->approval_status;
                
                $contract = $ContractsFinal[0];
                
                $signatory = $contract->signatory;
                
                $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $signatory)->get();
    
                //if ($approval_type == 'sequential') {
    
                    $unique_id = $contract->id . $randNo;
                    
    
                    if ($approval_status != 'required') {
                        $statusPreApprvr = 'Approval';
                        $statusApprvr = 'Signing';
                        $subStatusApprvr = 'Approved';
                        
                        if ($request->input('contractMode') == 'old') {
                            $statusPreApprvr = 'Approval';
                            $statusApprvr = 'executed';
                            $subStatusApprvr = 'active';  
                            
                            $cur_date = date('Y-m-d');
                            
                            $end_date_of_contract = $endContractDate;
                            $contract_end_type = $request->input('Duration.effectiveDate');
                            
                            if (strtotime($cur_date) > strtotime($end_date_of_contract) && $subStatusApprvr == 'active') {
                                if( $subStatusApprvr == 'active'){
                                    if($contract_end_type == 'onetimeContract'){
                                        $subStatusApprvr = 'completed';
                                    }
                                    if($contract_end_type == 'fixedTerm'){
                                        $subStatusApprvr = 'expired';
                                    }
                                }
                            }                         
                        }
                        Contract::where('id', $contract->id)->update(['contract_status' => $statusApprvr, 'substatus' => $subStatusApprvr]);
                    }
    
                //}
            }            
        }


        $fileController =  fileStorageTypeController();
        $emailTrigger = new ContractNotificationController();

        if ($request->hasFile('file')) {
            $senattment = [];
            $senattment['filename'] = [];
            $senattment['filurl'] = [];
            $file = $request->file('file');
            $filePath = $fileController->storeFile($file, $namePartygroup . '-' . $request->contractid, $request->contractid);
            $filename = file_name($file);
            if ($nextAprroverEmail != "") {
                $MailSent = $emailTrigger->sendEmail($contract->id, '', '', $nextAprroverEmail, 'Contract Updates Alert', $senattment['filename'],  $senattment['filurl'], 'updateContract');
                $controller->changePermission($filePath, "", $nextAprroverEmail);
            }
            Contract::where('id', $request->contractid)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $filename]);
        }

        return redirect('/contracts/list')->with('message', 'Contract Updated Successfully Click <a href="' . url('/contracts/' . $request->contractid) . '">Here</a> to view')->with('alert-class', 'alert-success');
    }
    
    public function storeCustomContract(Request $request){
        
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'renew' => 'required|boolean',
            'new_contract' => 'required|array',
            'old_contract' => 'required_if:renew,true|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $result = [
                'old_contract_id' => null,
                'new_contract_id' => null,
            ];

            if (!empty($payload['renew']) && $payload['renew'] === true) {
                $oldContractPayload = $payload['old_contract'];
                $result['old_contract_id'] = $this->createContractFromPayload($oldContractPayload, $payload['legacy_files'] ?? []);
            }

            $newContractPayload = $payload['new_contract'];
            $parentId = $result['old_contract_id'];
            $result['new_contract_id'] = $this->createContractFromPayload($newContractPayload, $payload['legacy_files'] ?? [], $parentId);

            DB::commit();

            return response()->json(['success' => true, 'data' => $result], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to store contract(s): ' . $e->getMessage(),
            ], 500);
        }        
        
    }

    public function terminateContract(Request $request)
    {
        $id = $request->input('contractId');
        $terminationReason = $request->input('terminationReason');
        $terminationDate = $request->input('terminationDate');
        $terminationRemarks = $request->input('terminationRemarks');

        $contracts = Contract::select('*')->where('id', $id)->get();

        $ContractsFinal = $this->availableContracts($contracts, true);

        if (count($ContractsFinal) == 0) {
            return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract Access')->with('alert-class', 'alert-danger');
        }

        if (strtolower($ContractsFinal[0]->substatus) == 'terminated') {
            return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract To Terminate')->with('alert-class', 'alert-danger');
        }

        Contract::where(['id' => $id])->update([
            'termination_reason' => $terminationReason,
            'contract_status' => 'executed',
            'substatus' => 'Terminated',
            'termination_remarks' => $terminationRemarks,
            'termination_date' => $terminationDate,
        ]);

        return redirect('/contracts/list');
    }


    public function renewContract(Request $request, $id)
    {
        $contracts = Contract::select('*')->where('id', $id)->get();

        $ContractsFinal = $this->availableContracts($contracts, true);

        if (count($ContractsFinal) == 0) {
            return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract Access')->with('alert-class', 'alert-danger');
        }

        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();

        $contractTypes = ContractType::get();
        
        $branchFirst = [];

        $branchs = Branch::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $branchsAll = Branch::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();


        $contractParty = ContractPartyData::where('custom_field_group_id', $id)->get();



        foreach ($contractParty as $contractPart) {
            $entities = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'))
                ->where('id', $contractPart->contract_party_id)
                ->first();

            if (isset($entities->Nameoftheentity)) {
                $contractPart->Nameoftheentity = $entities->Nameoftheentity;
            }


            if ($contractPart->contract_party_location_id == !null) {
                if (empty($branchFirst)) {
                    $branchFirst = BranchUser::select(
                        'id',
                        decrypt_data('BranchName', 'branch'),
                        decrypt_data('branchstatus', 'branch'),
                        decrypt_data('Doorno', 'branch'),
                        decrypt_data('StreetName', 'branch'),
                        decrypt_data('AreaName', 'branch'),
                        decrypt_data('Landmark', 'branch'),
                        decrypt_data('PinCode', 'branch'),
                        decrypt_data('ContactNumber', 'branch'),
                        decrypt_data('branchheadname', 'branch'),
                        decrypt_data('departments', 'branch'),
                        decrypt_data('LegalName', 'branch')
                    )->where('id', $contractPart->contract_party_location_id)->first();


                    $contractPart->contract_party = $branchFirst;
                }
            }
            if ($contractPart->contract_party_exe_id == !null) {

                $contractParties =  ContractParties::select('*')->where('id', $contractPart->contract_party_exe_id)->get();

                $contractPart->contract_party_id_exe = $contractParties;
            }
        }




        $contracts = Contract::where('id', $id)->first();


        if (isset($contracts->catgoery_id)) {
            $Categoryname = ContractCategories::where('id', $contracts->catgoery_id)->first();
            $contracts->catgoery_identity = $contracts->catgoery_id;
            $contracts->catgoery_id = $Categoryname->name;
        }

        if (isset($contracts->department_id)) {
            $EntityBusinessName = EntityBusiness::where('id', $contracts->department_id)->first();
            $contracts->department_identity = $contracts->department_id;
            $contracts->department_id = $EntityBusinessName->name;
        }

        if (isset($contracts->contract_type)) {
            $contracts->contract_type_id = $contracts->contract_type;
            $contracts->contract_type = ContractType::where('contract_type_id', $contracts->contract_type)->first()->contract_type;
        }

        if (decryptString($contracts->end_contract_type, 'end_contract_type') !== 'fixedTerm') {
            return redirect('/contracts/list')->with('message', 'Oops! Invalid Renewable Contract')->with('alert-class', 'alert-danger');
        }

        $approvalsArr = ApprovalContracts::select('*')->where('contract_id', $id)->where('flag', 1)
            ->get()
            ->map(function ($task) {
                $task->username = decryptString($task->username, 'username');
                $task->status = decryptString($task->status, 'status');
                $task->previous_status = decryptString($task->previous_status, 'previous_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                return $task;
            })
            ->groupBy('unique_id')
            ->reverse();

        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();
        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();

        $entities = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'))
            ->get();

        $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'), decrypt_data('Email', 'AddUsers'))->get();
        $usersSel = AddUsersSel::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'), decrypt_data('AccessScope', 'AddUsers'), decrypt_data('Email', 'AddUsers'))->get();
        $legalAdvisors = LegalAdvisor::where('status', 1)->orderBy('name')->get();

        $contractParties =  ContractParties::select('*')->get();

        $catego =  ContractCategories::select('*')->get();

        $ent = EntityBusiness::select('*')->get();

        $ContractPartyData = ContractPartyData::where('custom_field_group_id', $contracts->id)->get();


        $contractsold = Contract::where('id', $id)->first();

        if ($contractsold) {
            $contractsoldothers = Contract::where([
                ['catgoery_id', $contractsold->catgoery_id],
                ['department_id', $contractsold->department_id],
                ['contract_type', $contractsold->contract_type],
            ])->whereNot('id', $id)->get();
        }

        $contract_party_id = ContractPartyData::where('custom_field_group_id', $contracts->id)->where('contract_party_type', 'internal')->pluck('contract_party_id');

        $ContractPartyDataList = ContractPartyData::whereIn('contract_party_id', $contract_party_id)->pluck('custom_field_group_id');

        $contractspartsList = Contract::whereIn('id', $ContractPartyDataList)->get();


        $contractParties =  ContractParties::select('*')->get();

        return view('contract::contract.renewDetailContract', compact('contractParties', 'contractspartsList', 'contractsoldothers', 'ent', 'catego', 'contractParties', 'entities', 'branchs', 'branchsAll', 'customFields', 'categorys', 'contractTypes', 'users', 'usersSel', 'legalAdvisors'))->with('contractPartys', $ContractPartyData)->with('contract', $contracts)->with('contractPartyData', $contractParty)->with('approvalsArr', $approvalsArr);
    }


    public function renewCreateContract(Request $request)
    {
        
        $contracts = Contract::select('*')->where('id', $request->contractid)->get();

        $ContractsFinal = $this->availableContracts($contracts, true);

        if (count($ContractsFinal) == 0) {
            return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract Access')->with('alert-class', 'alert-danger');
        }
        
        
        $parentContractData = $ContractsFinal[0];
        
        $rulesContract = [
            "contractMode" => 'required',
            "owner" => 'required',
            "signatory" => 'required', 
            "BasicContract.catgoeryType" => 'required',
            "BasicContract.DepartmentType" => 'required',
            //"file" => "nullable|mimes:docx,pdf"
        ];

        $messages = [
            "required" => 'Please Fill Mandatory Fields :attribute',
            "owner.required" => 'Please Choose Co-Ordinator in Ownership',
            "signatory.required" => 'Please Choose Signatory in Ownership',
            "BasicContract.catgoeryType.required" => 'Please Choose Category in Basic Contract Information Section',
            "BasicContract.DepartmentType.required" => 'Please Choose Department in Basic Contract Information Section',
            //"file.required" => 'Please Upload Contract Document in Attachments',
            //"file.mimes" => 'Contract Document Must Be A File Of Type: Docx, Pdf',
            "Partygroup.party.required" => 'Please Choose Any Party',
        ];

        if (strtolower($request->input('attachments_type')) == 'upload') {
            $rulesContract['file'] = "required|mimes:docx";
            $messages['file.required'] = "Please Upload Renewable Contract Document In Attachments";
            $messages['file.mimes'] = "Please upload the new contract document in DOCX format";
        }


        if ($request->contractMode == "old") {
            $rulesContract["Duration.signingDate"] = 'required';
            $messages["Duration.signingDate.required"] = 'Please Fill Signing Date in Ownership';
            // $rulesContract["ContractValue.retention"] = 'required';
            // $messages["ContractValue.retention.required"] = 'Please Fill Retention or Holdbacks in Contract Value';
            // $rulesContract["ContractValue.payment_escrow"] = 'required';
            // $messages["ContractValue.payment_escrow.required"] = 'Please Fill Payment Escrow in Contract Value';
            // $rulesContract["ContractValue.value"] = 'required';
            // $messages["ContractValue.value.required"] = 'Please Fill Billing Value in Contract Value Section';

            if ($request->Duration["commencementDate"] == 'FixedDate') {
                $rulesContract["Duration.fixedDate"] = 'required';
                $messages["Duration.fixedDate.required"] = 'Please Fill Start Date in Contract Duration Section';
            }

            switch ($request->Duration["effectiveDate"]) {
                case 'fixedTerm':
                    $rulesContract["Duration.fixedtimeEndDateofContract"] = 'required'; 
                    $messages["Duration.fixedtimeEndDateofContract.required"] = 'Please Fill End date of contract in Contract Duration Section';
                    break;
                case 'termination':
                    $rulesContract["Duration.terminationDate"] = 'required';
                    $messages["Duration.terminationDate.required"] = 'Please Fill Termination in Contract Duration Section';
                    break;
                case 'onetimeContract':
                    $rulesContract["Duration.onetimeEndDateofContract"] = 'required';
                    $messages["Duration.onetimeEndDateofContract.required"] = 'Please Fill End date of contract in Contract Duration Section';
                    break;
            }

            
            $rulesContract['file'] = "required|mimes:pdf";
            $messages['file.required'] = "Please Upload Renewable Contract Document In Attachments";
            $messages['file.mimes'] = "Please upload the Legacy Contract document in PDF format";            
        }

        if ($request->Duration["fixedDate"] !== null) {
            switch ($request->Duration["effectiveDate"]) {
                case 'fixedTerm':
                    $rulesContract["Duration.fixedtimeEndDateofContract"] = 'nullable|date|after_or_equal:Duration.fixedDate'; 
                    $messages["Duration.fixedtimeEndDateofContract.required"] = 'Please Fill End date of contract in Contract Duration Section';
                    $messages["Duration.fixedtimeEndDateofContract.after_or_equal"] = 'End Date Must Be Greater Or Equal To Start Date';
                    break;
                case 'termination':
                    $rulesContract["Duration.terminationDate"] = 'nullable|date|after_or_equal:Duration.fixedDate';
                    $messages["Duration.terminationDate.required"] = 'Please Fill Termination in Contract Duration Section';
                    $messages["Duration.terminationDate.after_or_equal"] = 'End Date Must Be Greater Or Equal To Start Date';
                    break;
                case 'onetimeContract':
                    $rulesContract["Duration.onetimeEndDateofContract"] = 'nullable|date|after_or_equal:Duration.fixedDate';
                    $messages["Duration.onetimeEndDateofContract.required"] = 'Please Fill End date of contract in Contract Duration Section';
                    $messages["Duration.onetimeEndDateofContract.after_or_equal"] = 'End Date Must Be Greater Or Equal To Start Date';
                    break;
            }
        }

        if ($request->Duration["commencementDate"] == 'Eventbased') {
            if (count($request->input('Duration.task')) > 0) {
                $rulesContract["Duration.task.*.start_date"] = 'required';
                $rulesContract["Duration.task.*.end_date"] = 'required|date|after_or_equal:Duration.task.*.start_date';
                $messages["Duration.task.*.start_date.required"] = 'Please Fill Task Start Date';
                $messages["Duration.task.*.end_date.required"] = 'Please Fill Task End Date';
                $messages["Duration.task.*.end_date"] = 'Task End Date Must Be Greater Or Equal To Start Date';
            }
        }

        $validator =  Validator::make($request->all(), $rulesContract, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if (!$validator->errors()->has('file')) {
                $validator->errors()->add('file', 'On Behalf Above Validation Errors File was cleared Please Upload Contract Document in Attachments');
            }
            return redirect('contracts/renew/'.$request->contractid)->withErrors($validator)->withInput();
        }

        $controller =  fileStorageTypeController();

        $locals = $request->input('Partygroup.party');

        $totalInternals = 0;
        $totalExternals = 0;
        $sameExternals = [];
        $sameInternals = [];
        
        $fileError = [];
        
        if ($request->hasFile('file')){
            $fileError = ['On Behalf Below Validation Errors File was cleared Please Upload Contract Document in Attachments'];
        }         
        

        //To Get First Internal Party Location
        $internal_first_location = "";
        foreach ($locals as $partyLoc) {
            if (isset($partyLoc['location']) && $partyLoc['location'] != "" && $partyLoc['mode'] == "Internal") {
                if ($totalInternals == 0) {
                    $internal_first_location = $partyLoc;
                }
                $totalInternals++;
                if (!in_array($partyLoc['location'], $sameInternals)) {
                    $sameInternals[] = $partyLoc['location'];
                }else{
                    $invalid_location_error = array('Duplicate internal/intergroup party with location in Party Details');
                    return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, $invalid_location_error))->withInput();                    
                }                
            }
            if (isset($partyLoc['mode']) && $partyLoc['mode'] == "External" && isset($partyLoc['external_name'])) {
                $totalExternals++;
                if (!in_array($partyLoc['external_name'], $sameExternals)) {
                    $sameExternals[] = $partyLoc['external_name'];
                }else{
                    $invalid_external_error = array('Duplicate external party in Party Details');
                    return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, $invalid_external_error))->withInput();                    
                }                
            }
            if (isset($partyLoc['mode']) && $partyLoc['mode'] == "Intergroup" && isset($partyLoc['location_grp'])) {
                $totalExternals++;
                if (!in_array($partyLoc['location_grp'], $sameInternals)) {
                    $sameInternals[] = $partyLoc['location_grp'];
                }else{
                    $invalid_location_error = array('Duplicate internal/intergroup party with location in Party Details');
                    return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, $invalid_location_error))->withInput();                    
                }
            }
        }

        if ($internal_first_location == "") {
            $invalid_location_error = array('Please Choose one internal party with location in Party Details');
            return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, $invalid_location_error))->withInput();
        }

        if ($totalInternals > 1) {
            $invalid_location_error = array('Please Choose Only One internal party with location in Party Details');
            return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, $invalid_location_error))->withInput();
        }

        if ($totalExternals == 0) {
            $missing_external_error = array('Please Choose atleast One external/intergroup party in Party Details');
            return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, $missing_external_error))->withInput();
        }
        
        $endContractDate = null;

        switch ($request->input("Duration.effectiveDate")) {
            case 'fixedTerm':
                $endContractDate = $request->input('Duration.fixedtimeEndDateofContract');
                break;
            case 'onetimeContract':
                $endContractDate = $request->input('Duration.onetimeEndDateofContract');
                break;
        }
        
        $parentStartDate = $parentContractData->fixedDate;
        $parentEndDate = $parentContractData->contract_end_date;
        
        if($request->input('contractRenew') == 'renew'){
            if ($endContractDate != null && strtotime($request->input('Duration.fixedDate')) < strtotime($parentEndDate)){
                //return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError,['Renewable Contract effective date must be greater than Parent Contract end Date']))->withInput();
            }
        }

        if ($request->input('Duration.fixedDate') != null && $endContractDate != null) {
            if (strtotime($request->input('Duration.fixedDate')) > strtotime($endContractDate)) {
                return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError,['Contract end date must be greater than Start Date']))->withInput();
            }
        }
        
        if ($request->input('Duration.signingDate') != null && $request->input('Duration.fixedDate') != null) {
            if (strtotime($request->input('Duration.signingDate')) < strtotime($request->input('Duration.fixedDate'))) {
                //return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError,['Signing date must be greater than/Equal to Start Date']))->withInput();
            }            
        }
        if ($request->input('Duration.signingDate') != null && $endContractDate != null) {
            if (strtotime($request->input('Duration.signingDate')) > strtotime($endContractDate)) {
                return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, ['Signing date must be less than/Equal to End Date']))->withInput();
            }            
        }

        $approval_user_column = "approval_required_users_renewed";
        $approvalTypeGlobal = "renewed";
        if ($request->input('contractRenew') != 'renew') {
            $approval_user_column = "approval_required_users_addendum";
            $approvalTypeGlobal = "addendum";
        }
        
        if ($request->input('contractMode') != 'new') {
            $approval_user_column = "approval_required_users_legacy";
            $approvalTypeGlobal = "legacy";
        }        
        
        //Check Contract Duplicates Start
        
        $existContract = $this->checkDuplicateContracts($locals, $request, $endContractDate, false, $request->contractid);
        
        if($existContract){
            return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, ['Duplicate contract details detected — contract already exists <a href="' . url('/contracts/' . $existContract->id) . '" target="new">'.$existContract->contract_unique_id.'</a>']))->withInput();
        }
        
        
        //Check Contract Duplicates End          

        $financialLimit = $this->financialLimit(
            $internal_first_location['location'],
            $request->input('BasicContract.DepartmentType'),
            $request->input('BasicContract.catgoeryType'),
            $request->input('BasicContract.contractType'),
            $request->input('ContractValue.value'),
            $approval_user_column
        );


        $financialLimitDecoded = json_decode($financialLimit)[0];
        
        


        $signatory_data_decoded = (array)json_decode($financialLimitDecoded->signatory);
        $app_type_data_decoded = (array)json_decode($financialLimitDecoded->approval_type);
        $app_status_data_decoded = (array)json_decode($financialLimitDecoded->approval_status);

        $signatory_array = (array)($signatory_data_decoded['sign']);
        $owner_array = (array)($signatory_data_decoded['owner']);
        $notifier_array = ((array)($signatory_data_decoded['notify'] ?? [])) ?? [];
        $utf_array = ((array)($signatory_data_decoded['signutform'] ?? null)) ?? null;
        $signatory_data_decoded = [];
        $signatory_data_decoded['sign'] = $signatory_array[$approvalTypeGlobal];
        $signatory_data_decoded['owner'] = $owner_array[$approvalTypeGlobal];
        $signatory_data_decoded['notify'] = $notifier_array[$approvalTypeGlobal] ?? [];
        $signatory_data_decoded['signutform'] = $utf_array[$approvalTypeGlobal] ?? [];
        $financialLimitDecoded->signatory = json_encode($signatory_data_decoded);
        $financialLimitDecoded->approval_type = $app_type_data_decoded[$approvalTypeGlobal];
        $financialLimitDecoded->approval_status = $app_status_data_decoded[$approvalTypeGlobal];
        
        
        
        $signatory = $request->signatory ?? false;
        
        if (!$request->signatory) {
            //Signatory Validation
            $final_signatory = explode(":", $signatory_data_decoded['sign']);

            $signatory = $final_signatory[0] ?? 0;

            if ($signatory < 1) {
                $invalid_signatory_error = array('Signatory Not Added In Approval Rules Please Add one');
                return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, $invalid_signatory_error))->withInput();
            }
        }

        //Owner/Initiator Validation
        $owner_initiator = session()->get('contractSessionUser');

        $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
            ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
            ->first();
        if (!$initiatior_exists) {
            $invalid_owner_error = array('Owner Not Available Please Contact Administrator');
            return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, $invalid_owner_error))->withInput();
        }
        
        if (!$request->owner) {
            $owner_initiator_id = $initiatior_exists->id;
        } else {
            $owner_initiator_id = $request->owner;
        }
        
        if ($request->userNotify) {
            //Notifiers Validation
            $signatory_data_decoded['notify'] = $request->userNotify;

        }        
        
        $signatory_data_decoded['sign'] = $signatory ?? 0;
        $signatory_data_decoded['owner'] = $owner_initiator_id;
        
        $financialLimitDecoded->signatory = json_encode($signatory_data_decoded);
        
        $all_approvers = json_decode($financialLimitDecoded->approver, true);

        $branchHeads = BranchUser::select(
            'id',
            decrypt_data('branchheadname', 'branch'),
            'Branchhead',
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->where('id', $internal_first_location['location'])->first();

        $branchHeadsError = [];
        foreach ($all_approvers as $ap_data) {
            if ($ap_data->type == 'designation') {
                if ($ap_data->name == 'branch_head') {
                    $branchHeadId = $branchHeads->Branchhead;
                    if ($branchHeadId == null) {
                        $branchHeadsError[] = "Branch Head Not Added in your selected Branch Please Update In Goal Portal";
                    }
                    $ap_data->id = $branchHeadId;
                }
                if ($ap_data->name == 'branch_dep_head') {
                    $branchDeptData = unserialize($branchHeads->departments);
                    //print_r($branchDeptData);
                    if (!isset($branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')])) {
                        $branchHeadsError[] = "Branch Department Head Not Added in your selected Branch Please Update In Goal Portal";
                    } else {
                        $ap_data->id = $branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')];
                    }
                }
                if ($ap_data->name == 'overall_dept_head') {
                    $entityDeptHead = EntityBusiness::select('overall_dept_head')->where('id', $request->input('BasicContract.DepartmentType'))->first();
                    if (!$entityDeptHead || !$entityDeptHead->overall_dept_head) {
                        $branchHeadsError[] = "Department Over All Head Not Added in your Entity Business Please Update In Goal Portal";
                    } else {
                        $ap_data->id = $entityDeptHead->overall_dept_head;
                    }
                }
            }
        }

        $financialLimitDecoded->approver = json_encode($all_approvers);

        $financialLimit = json_encode([$financialLimitDecoded]);

        if (count($branchHeadsError) > 0) {
            return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, $branchHeadsError))->withInput();
        }



        $emailTrigger = new ContractNotificationController();
        $senattment = [];
        $senattment['filename'] = [];
        $senattment['filurl'] = [];

        
        DB::beginTransaction();
        
        $contract = Contract::create([
            'contract_mode' => encryptString($request->input('contractMode'), 'contract_mode'),
            'contract_type' => $request->input('BasicContract.contractType'),
            'contract_tags' => json_encode($request->input('BasicContract.contractTypeTags') ?? []),
            'contract_description' => encryptString($request->input('BasicContract.contractDescription'), 'contract_description'),
            'contract_priority' => $request->input('priority'),

            'department_id' => $request->input('BasicContract.DepartmentType'),
            'catgoery_id' => $request->input('BasicContract.catgoeryType'),

            'signatory' => $signatory,
            'owner' => $owner_initiator_id,


            'confidentialityagreement' => $request->input('BasicContract.Confidentialityagreement'),
            'exclusivity' => encryptString($request->input('BasicContract.Exclusivity'), 'exclusivity'),

            // Contract Duration
            'signing_date' => $request->input('Duration.signingDate'),
            'commencement_type' => encryptString($request->input('Duration.commencementDate'), 'commencement_type'), // corrected key
            'fixed_date' => $request->input('Duration.fixedDate'),
            'event_name' => encryptString($request->input('Duration.eventDetails'), 'event_name'),
            'end_contract_type' => encryptString($request->input('Duration.effectiveDate'), 'end_contract_type'),
            'contract_end_date' => $endContractDate,
            'renewal_type' => encryptString($request->input('Duration.typeRenewal'), 'renewal_type'),
            'period_auto_renewal' => $request->input('Duration.periodAutoRenewal'),
            'period_auto_renewal_unit' => encryptString($request->input('Duration.periodAutoRenewalPeriod'), 'period_auto_renewal_unit'),
            'auto_renewal_date' => $request->input('Duration.autoRenewalDate'),
            'manual_renewal_date' => $request->input('Duration.autoManualRenewalDate'),
            'evergreen_condition' => encryptString($request->input('Duration.conditionEndContract'), 'evergreen_condition'),
            'termination_date' => $request->input('Duration.terminationDate'),
            'termination_reason' => encryptString($request->input('Duration.reasonTermination'), 'termination_reason'),


            // Contract Value
            'currency' => encryptString($request->input('ContractValue.currency'), 'currency'),
            'billing_value' => encryptString($request->input('ContractValue.billingvalue'), 'billing_value'),
            'currency_value' => encryptString($request->input('ContractValue.value'), 'currency_value'),
            'total_value' => encryptString($request->input('ContractValue.totalvalue'), 'total_value'),
            'payment_schedule' => encryptString($request->input('ContractValue.paymentSchedule'), 'payment_schedule'),
            'currency_contract' => encryptString($request->input('ContractValue.currencyContract'), 'currency_contract'),
            'payment_terms' => encryptString($request->input('ContractValue.paymentTerms'), 'payment_terms'),
            'billing_frequency' => encryptString($request->input('ContractValue.billingFrequency'), 'billing_frequency'),
            'taxes' => encryptString($request->input('ContractValue.taxes'), 'taxes'),
            'escalation_clauses' => encryptString($request->input('ContractValue.escalationClauses'), 'escalation_clauses'),
            'discounts' => encryptString($request->input('ContractValue.discounts'), 'discounts'),
            'retention' => encryptString($request->input('ContractValue.retention'), 'retention'),
            'payment_escrow' => encryptString($request->input('ContractValue.payment_escrow'), 'payment_escrow'),
            'financial_guarantees' => encryptString($request->input('ContractValue.financialGuarantees'), 'financial_guarantees'),
            'currency_conversion' => encryptString($request->input('ContractValue.currencyConversion'), 'currency_conversion'),

            // Reminder Value
            'reminder_enable' => encryptString($request->input('Duration.reminderEnable'), 'reminder_enable') ?? null,
            'reminder_first_alert' => encryptString($request->input('Duration.Reminder.first.alertMe'), 'reminder_first_alert'),
            'reminder_first_alertMeOn' => encryptString($request->input('Duration.Reminder.first.alertMeDay') . ' ' . $request->input('Duration.Reminder.first.alertMePrior') . ' ' . $request->input('Duration.Reminder.first.alertMeType'), 'reminder_first_alertMeOn'),
            'reminder_first_alert_repeats' => encryptString($request->input('Duration.Reminder.first.repeats'), 'reminder_first_alert_repeats'),
            'reminder_second_alert' => encryptString($request->input('Duration.Reminder.second.alertMe'), 'reminder_second_alert'),
            'reminder_second_alertMeOn' => encryptString($request->input('Duration.Reminder.second.alertMeDay') . ' ' . $request->input('Duration.Reminder.second.alertMePrior') . ' ' . $request->input('Duration.Reminder.second.alertMeType'), 'reminder_second_alertMeOn'),
            'reminder_second_alert_repeats' => encryptString($request->input('Duration.Reminder.second.repeats'), 'reminder_second_alert_repeats'),
            'reminder_escalation_alert' => encryptString($request->input('Duration.Reminder.escalation.alertMe'), 'reminder_escalation_alert'),
            'reminder_escalation_alertMeOn' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay') . ' ' . $request->input('Duration.Reminder.escalation.alertMePrior') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType'), 'reminder_escalation_alertMeOn'),
            'reminder_escalation_alert_repeats' => encryptString($request->input('Duration.Reminder.escalation.repeats'), 'reminder_escalation_alert_repeats'),
            'reminder_escalation_alert_after' => encryptString($request->input('Duration.Reminder.escalation.alertMe_after'), 'reminder_escalation_alert_after'),
            'reminder_escalation_alertMeOn_after' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay_after') . ' ' . $request->input('Duration.Reminder.escalation.alertMeAfter') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType_after'), 'reminder_escalation_alertMeOn_after'),
            'reminder_escalation_alert_repeats_after' => encryptString($request->input('Duration.Reminder.escalation.repeats_after'), 'reminder_escalation_alert_repeats_after'),

            'rules_id' => $financialLimit,

            'custom_fields_data' => json_encode($request->input('customFields')),

            'parentcontract' => $request->contractid,
            
            'created_by' => $initiatior_exists->id            

        ]);

        if ($request->has('customFields')) {
            foreach ($request->input('customFields') as $customField) {
                if (isset($customField)) {

                    if (isset($customField['id']) && isset($customField['value']) && isset($contract->id)) {
                        CustomFieldsData::create([
                            'custom_field_id' => $customField['id'],
                            'custom_field_group' => 'contracts',
                            'custom_field_value' => $customField['value'],
                            'custom_field_group_id' => $contract->id
                        ]);
                    }
                }
            }
        }
        
        $contractTypeName = ContractType::where('contract_type_id', $request->input('BasicContract.contractType'))->first();

        $namePartygroup =  'Renewal - '.$contractTypeName->contract_type;

        // 
        
        if ($request->has('Duration.task')) {
            foreach ($request->input('Duration.task') as $ke => $tasks) {

            if (isset($tasks['name_of_task'])) {
                Tasks::create([
                    'name_of_task' => encryptString($tasks['name_of_task'], 'name_of_task'),
                    'priority' => encryptString($tasks['priority'], 'priority'),
                    'start_date' => encryptString($tasks['start_date'], 'start_date'),
                    'end_date' => encryptString($tasks['end_date'], 'end_date'),
                    'description' => encryptString($tasks['description'], 'description'),
                    'task_owner' => $request->input('owner'),
                    'task_reviewer' => $request->input('BasicContract.signatory'),
                    'branch' => $internal_first_location['location'],
                    'status' => $tasks['status'],
                    'contract_id' => $contract->id
                ]);
            }
        }
        }
        $contracthis = Contract::select('*')->where('id', $contract->id)->first();

        $contractHistory = ContractHistory::create([
            'contract_name' => $contracthis->contract_name,
            'id' => $contract->id,
            'contract_mode' => $contracthis->contract_mode,
            'contract_type' => $contracthis->contract_type,
            'contract_description' => $contracthis->contract_description,
            'contract_priority' => $request->input('priority'),

            'department_id' => $contracthis->department_id,
            'catgoery_id' => $contracthis->catgoery_id,

            'signatory' => $contracthis->signatory,
            'owner' => $contracthis->owner,


            'confidentialityagreement' => $contracthis->confidentialityagreement,
            'exclusivity' => $contract->exclusivity,

            // Contract Duration
            'signing_date' => $contracthis->signing_date,
            'commencement_type' => $contracthis->commencement_type,
            'fixed_date' => $contracthis->fixed_date,
            'event_name' => $contracthis->event_name,
            'end_contract_type' => $contracthis->end_contract_type,
            'contract_end_date' => $contracthis->contract_end_date,
            'renewal_type' => $contracthis->renewal_type,
            'period_auto_renewal' => $contracthis->period_auto_renewal,
            'period_auto_renewal_unit' => $contracthis->period_auto_renewal_unit,
            'auto_renewal_date' => $contracthis->auto_renewal_date,
            'manual_renewal_date' => $contracthis->manual_renewal_date,
            'evergreen_condition' => $contracthis->evergreen_condition,
            'termination_date' => $contracthis->termination_date,
            'termination_reason' => $contracthis->termination_reason,


            // Contract Value
            'currency' => $contracthis->currency,
            'billing_value' => $contracthis->billing_value,
            'currency_value' => $contracthis->currency_value,
            'total_value' => $contracthis->total_value,
            'payment_schedule' => $contracthis->payment_schedule,
            'currency_contract' => $contracthis->currency_contract,
            'payment_terms' => $contracthis->payment_terms,
            'billing_frequency' => $contracthis->billing_frequency,
            'taxes' => $contracthis->taxes,
            'escalation_clauses' => $contracthis->escalation_clauses,
            'discounts' => $contracthis->discounts,
            'retention' => $contracthis->retention,
            'payment_escrow' => $contracthis->payment_escrow,
            'financial_guarantees' => $contracthis->financial_guarantees,
            'currency_conversion' => $contracthis->currency_conversion,

            // Reminder Value
            'reminder_first_alert' => $contracthis->reminder_first_alert,
            'reminder_first_alertMeOn' => $contracthis->reminder_first_alertMeOn,
            'reminder_first_alert_repeats' => $contracthis->reminder_first_alert_repeats,
            'reminder_second_alert' => $contracthis->reminder_second_alert,
            'reminder_second_alertMeOn' => $contracthis->reminder_second_alertMeOn,
            'reminder_second_alert_repeats' => $contracthis->reminder_second_alert_repeats,
            'reminder_escalation_alert' => $contracthis->reminder_escalation_alert,
            'reminder_escalation_alertMeOn' => $contracthis->reminder_escalation_alertMeOn,
            'reminder_escalation_alert_repeats' => $contracthis->reminder_escalation_alert_repeats,
            'reminder_escalation_alert_after' => $contracthis->reminder_escalation_alert_after,
            'reminder_escalation_alertMeOn_after' => $contracthis->reminder_escalation_alertMeOn_after,
            'reminder_escalation_alert_repeats_after' => $contracthis->reminder_escalation_alert_repeats_after,
            'contract_status' => $contracthis->contract_status,
            'substatus' => $contracthis->substatus,  
            'rules_id' => $contracthis->rules_id,

            'custom_fields_data' => $contracthis->custom_fields_data,
            'contract_attachment' => $contracthis->contract_attachment,
            'contract_attachment_filename' => $contracthis->contract_attachment_filename,
            'created_by' => $initiatior_exists->id
        ]);

        foreach ($request->input('Partygroup.party') as $ke => $customField) {

            if (isset($customField)) {

                $mode = $customField['mode'] ?? null;
                $externalType = $customField['external_type'] ?? null;
                $internalName = $customField['internal_name'] ?? null;
                $externalName = $customField['external_name'] ?? null;
                $locationId = null;

                if ($mode !== 'External') {
                    $locationId = $mode === 'Internal'
                        ? ($customField['location'] ?? null)
                        : ($customField['location_grp'] ?? null);
                }


                $ContractPartyData = ContractPartyData::create([
                    'custom_field_group_id' => $contract->id,
                    'contract_party_type' => $mode,
                    'party_sub_type' => $mode === 'External' ? $externalType : 'Internal',
                    'contract_party_id' => $internalName,
                    'contract_party_exe_id' => $externalName,
                    'contract_party_location_id' => $locationId,
                ]);

                ContractPartyDataHistory::create([
                    'history_id' => $contractHistory->id,
                    'id' => $ContractPartyData->id,
                    'custom_field_group_id' => $contract->id,
                    'contract_party_type' => $mode,
                    'party_sub_type' => $mode === 'External' ? $externalType : 'Internal',
                    'contract_party_id' => $internalName,
                    'contract_party_location_id' => $locationId,
                ]);

                if ($ke < 2) {

                    $namePartygroup .= '-';
                    if ($mode === 'External' && !empty($externalName)) {
                        $party = ContractParties::select('company_name')->where('id', $externalName)->first();
                        if ($party && !empty($party->company_name)) {
                            $namePartygroup .= decryptString($party->company_name, 'company_name');
                        }
                    } else {
                        $namePartygroup .= DB::table('entity')
                            ->select('Nameoftheentity', decrypt_data('Nameoftheentity', 'entity'))
                            ->where('id', $internalName)
                            ->first()->Nameoftheentity;
                    }
                }
            }
        }

        $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $owner_initiator_id)->get();

        $nextAprroverEmail = "";

        //Creating Approval Flow
        //if($request->input('contractMode') == 'new'){
        $appArr = json_decode(trim($financialLimit));
        $randNo = rand(0, 99999);
        if (is_array($appArr) && count($appArr) > 0 && isset($users[0])) {
            $approval_type = $appArr[0]->approval_type;
            $approval_status = $appArr[0]->approval_status;
            $approvalArr = $appArr[0]->approval_status;

            //if ($approval_type == 'sequential') {

                $unique_id = $contract->id . $randNo;

                if ($approval_status == 'required') {
                    $statusPreApprvr = 'Draft';
                    $statusApprvr = 'Draft';
                    $subStatusApprvr = 'Initial Draft';
                    if ($request->input('contractMode') == 'old') {
                        $statusPreApprvr = 'Negotiation';
                        $statusApprvr = 'Approval';
                        $subStatusApprvr = 'Pending Approval';
                        $approvalArr = json_decode($appArr[0]->approver);
                        foreach ($approvalArr as $key => $appVal) {
                            $approver_id = $appVal->id;
                            $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                            ApprovalContracts::create([
                                'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                'status' => encryptString($statusApprvr, 'status'),
                                'contract_id' => $contract->id,
                                'orderval' => $key,
                                'unique_id' => $unique_id,
                                'flag' => 1,
                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                            ]);
                            if ($approval_type == 'sequential'){
                                $nextAprroverEmail = $users[0]->Email;
                                break;
                            }else{
                                if($nextAprroverEmail == ""){
                                    $nextAprroverEmail = [];
                                }
                                $multipleNextApprovers = true;
                                $nextAprroverEmail[] = $users[0]->Email;
                            }
                        }
                    }
                } else {
                    $statusPreApprvr = 'Approval';
                    $statusApprvr = 'Signing';
                    $subStatusApprvr = 'Approved';

                    $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $signatory)->get();
                    
                    if ($request->input('contractMode') == 'old') {
                        $statusPreApprvr = 'Approval';
                        $statusApprvr = 'executed';
                        $subStatusApprvr = 'active';  
                        
                        $cur_date = date('Y-m-d');
                        
                        $end_date_of_contract = $endContractDate;
                        $contract_end_type = $request->input('Duration.effectiveDate');
                        $updateOldcontract = Contract::where('id', $request->contractid)->update([ 'substatus' => 'renewed' ]); 
                        
                        if (strtotime($cur_date) > strtotime($end_date_of_contract) && $subStatusApprvr == 'active') {
                            if( $subStatusApprvr == 'active'){
                                if($contract_end_type == 'onetimeContract'){
                                    $subStatusApprvr = 'completed';
                                }
                                if($contract_end_type == 'fixedTerm'){
                                    $subStatusApprvr = 'expired';
                                }
                            }
                        }                         
                    }
                    
                }
                
                //For Sequential Approval Flow (renew flow)
                if ($request->input('contractMode') != 'old'){
                    $base_unique = $unique_id; // group base id for chunking groups
                    // Insert owner as first approver
                    $ownerRow = ApprovalContracts::create([
                        'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                        'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                        'status' => encryptString($statusApprvr, 'status'),
                        'contract_id' => $contract->id,
                        'orderval' => 0,
                        'unique_id' => $base_unique . '_g0',
                        'flag' => 1,
                        'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                        'approval_type_main' => $approval_type,
                        'approval_type_row' => 'sequential',
                        'approver_type_row' => 'Owner',
                    ]);

                    $emailTrigger->sendEmail($contract->id, '', '', $users[0]->Email, "Contract " . $request->input('contractRenew') . " request Alert", $senattment['filename'],  $senattment['filurl'], 'newContract');

                    $nextAprroverEmail = $users[0]->Email;

                    // Persist remaining groups/approvers
                    $approversJson = $appArr[0]->approver;
                    $approvalArrFull = is_array($approversJson) ? $approversJson : json_decode($approversJson);
                    $ord = 1;

                    if (isset($approvalArrFull[0]) && (isset($approvalArrFull[0]->approvers) || (is_array($approvalArrFull[0]) && isset($approvalArrFull[0]['approvers'])))) {
                        $groupIndex = 1;
                        
                        // Check if this is the new parent-grouped structure
                        $isParentGrouped = false;
                        $parentRouting = [];
                        
                        if (is_array($approvalArrFull) && count($approvalArrFull) > 0) {
                            // Extract parent routing if present
                            if (isset($approvalArrFull['_parent_routing'])) {
                                $parentRouting = $approvalArrFull['_parent_routing'];
                            }
                            
                            $firstItem = $approvalArrFull[0];
                            $firstItemArray = is_object($firstItem) ? (array)$firstItem : $firstItem;
                            if (!isset($firstItemArray['role']) && !isset($firstItemArray['approvers'])) {
                                $keys = array_keys($firstItemArray);
                                if (in_array('review', $keys) || in_array('approval', $keys) || in_array('signatory', $keys)) {
                                    $isParentGrouped = true;
                                }
                            }
                        }
                        
                        if ($isParentGrouped) {
                            $parentOrder = ['review', 'negotiation', 'finalization', 'approval', 'signatory'];
                            foreach ($parentOrder as $parentType) {
                                if (!isset($approvalArrFull[$parentType]) || !is_array($approvalArrFull[$parentType])) {
                                    continue;
                                }
                                
                                // Get parent-level routing for this parent type
                                $routing = $parentRouting[$parentType] ?? [];
                                $onApprove = $routing['on_approve'] ?? '';
                                $onReject = $routing['on_reject'] ?? '';
                                
                                foreach ($approvalArrFull[$parentType] as $group) {
                                    $groupData = is_array($group) ? $group : (array)$group;
                                    $groupUniqueId = $base_unique . '_g' . $groupIndex;
                                    $unique_id = $groupUniqueId;
                                    $groupType = $groupData['approval_type'] ?? $approval_type;
                                    $groupRole = $groupData['role'] ?? 'Approver';
                                    $groupDynamicApproverEnabled = (int)($groupData['dynamic_approver_enabled'] ?? 0) === 1 ? 1 : 0;
                                    $groupApproversRaw = $groupData['approvers'] ?? [];
                                    $groupApprovers = is_array($groupApproversRaw) ? $groupApproversRaw : (json_decode((string)$groupApproversRaw, true) ?: []);

                                    $isFirstGroup = ($groupIndex === 1);

                                    if (strtolower($groupType) === 'parallel') {
                                        foreach ($groupApprovers as $ap) {
                                            $approver_id = $ap->id ?? $ap['id'] ?? null;
                                            if (!$approver_id) continue;
                                            $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $approver_id)->first();
                                            if (!isset($apUser->Email)) continue;

                                            ApprovalContracts::create([
                                                'username' => encryptString(json_encode(['email' => $apUser->Email, 'name' => $apUser->FirstName]), 'username'),
                                                'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                                'status' => encryptString($statusApprvr, 'status'),
                                                'contract_id' => $contract->id,
                                                'orderval' => $ord,
                                                'unique_id' => $unique_id,
                                                'flag' => ($isFirstGroup ? 1 : 0),
                                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                                'approval_type_main' => $approval_type,
                                                'approval_type_row' => $groupType,
                                                'approver_type_row' => $groupRole,
                                                'next_group_on_approve' => $onApprove,
                                                'next_group_on_reject' => $onReject,
                                                'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                            ]);

                                            $emailTrigger->sendEmail($contract->id, '', '', $apUser->Email, "Contract " . $request->input('contractRenew') . " request Alert", $senattment['filename'],  $senattment['filurl'], 'newContract');

                                            $ord++;
                                        }
                                    } else {
                                        $firstInGroup = true;
                                        foreach ($groupApprovers as $ap) {
                                            $approver_id = $ap->id ?? $ap['id'] ?? null;
                                            if (!$approver_id) continue;
                                            $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $approver_id)->first();
                                            if (!isset($apUser->Email)) continue;

                                            ApprovalContracts::create([
                                                'username' => encryptString(json_encode(['email' => $apUser->Email, 'name' => $apUser->FirstName]), 'username'),
                                                'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                                'status' => encryptString($statusApprvr, 'status'),
                                                'contract_id' => $contract->id,
                                                'orderval' => $ord,
                                                'unique_id' => $unique_id,
                                                'flag' => ($isFirstGroup && $firstInGroup ? 1 : 0),
                                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                                'approval_type_main' => $approval_type,
                                                'approval_type_row' => $groupType,
                                                'approver_type_row' => $groupRole,
                                                'next_group_on_approve' => $onApprove,
                                                'next_group_on_reject' => $onReject,
                                                'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                            ]);

                                            $emailTrigger->sendEmail($contract->id, '', '', $apUser->Email, "Contract " . $request->input('contractRenew') . " request Alert", $senattment['filename'],  $senattment['filurl'], 'newContract');

                                            $ord++;
                                            $firstInGroup = false;
                                        }
                                    }

                                    $groupIndex++;
                                }
                            }
                        } else {
                            foreach ($approvalArrFull as $group) {
                                $groupData = is_array($group) ? $group : (array)$group;
                                $groupUniqueId = $base_unique . '_g' . $groupIndex;
                                $unique_id = $groupUniqueId;
                                $groupType = $groupData['approval_type'] ?? $approval_type;
                                $groupRole = $groupData['role'] ?? 'Approver';
                                $groupDynamicApproverEnabled = (int)($groupData['dynamic_approver_enabled'] ?? 0) === 1 ? 1 : 0;
                                $groupApproversRaw = $groupData['approvers'] ?? [];
                                $groupApprovers = is_array($groupApproversRaw) ? $groupApproversRaw : (json_decode((string)$groupApproversRaw, true) ?: []);

                                $isFirstGroup = ($groupIndex === 1);

                                if (strtolower($groupType) === 'parallel') {
                                    foreach ($groupApprovers as $ap) {
                                        $approver_id = $ap->id ?? $ap['id'] ?? null;
                                        if (!$approver_id) continue;
                                        $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $approver_id)->first();
                                        if (!isset($apUser->Email)) continue;

                                        ApprovalContracts::create([
                                            'username' => encryptString(json_encode(['email' => $apUser->Email, 'name' => $apUser->FirstName]), 'username'),
                                            'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                            'status' => encryptString($statusApprvr, 'status'),
                                            'contract_id' => $contract->id,
                                            'orderval' => $ord,
                                            'unique_id' => $unique_id,
                                            'flag' => ($isFirstGroup ? 1 : 0),
                                            'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                            'approval_type_main' => $approval_type,
                                            'approval_type_row' => $groupType,
                                            'approver_type_row' => $groupRole,
                                            'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                        ]);

                                        $emailTrigger->sendEmail($contract->id, '', '', $apUser->Email, "Contract " . $request->input('contractRenew') . " request Alert", $senattment['filename'],  $senattment['filurl'], 'newContract');

                                        $ord++;
                                    }
                                } else {
                                    $firstInGroup = true;
                                    foreach ($groupApprovers as $ap) {
                                        $approver_id = $ap->id ?? $ap['id'] ?? null;
                                        if (!$approver_id) continue;
                                        $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $approver_id)->first();
                                        if (!isset($apUser->Email)) continue;

                                        ApprovalContracts::create([
                                            'username' => encryptString(json_encode(['email' => $apUser->Email, 'name' => $apUser->FirstName]), 'username'),
                                            'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                            'status' => encryptString($statusApprvr, 'status'),
                                            'contract_id' => $contract->id,
                                            'orderval' => $ord,
                                            'unique_id' => $unique_id,
                                            'flag' => ($isFirstGroup && $firstInGroup ? 1 : 0),
                                            'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                            'approval_type_main' => $approval_type,
                                            'approval_type_row' => $groupType,
                                            'approver_type_row' => $groupRole,
                                            'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                        ]);

                                        $emailTrigger->sendEmail($contract->id, '', '', $apUser->Email, "Contract " . $request->input('contractRenew') . " request Alert", $senattment['filename'],  $senattment['filurl'], 'newContract');

                                        $ord++;
                                        $firstInGroup = false;
                                    }
                                }

                                $groupIndex++;
                            }
                        }
                    } else {
                        $groupUniqueId = $base_unique . '_g1';
                        $first = true;
                        foreach ($approvalArrFull as $apVal) {
                            $approver_id = $apVal->id ?? $apVal;
                            if (!$approver_id) continue;
                            $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $approver_id)->first();
                            if (!isset($apUser->Email)) continue;

                            ApprovalContracts::create([
                                'username' => encryptString(json_encode(['email' => $apUser->Email, 'name' => $apUser->FirstName]), 'username'),
                                'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                'status' => encryptString($statusApprvr, 'status'),
                                'contract_id' => $contract->id,
                                'orderval' => $ord,
                                'unique_id' => $groupUniqueId,
                                'flag' => ($first ? 1 : 0),
                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                'approval_type_main' => $approval_type,
                                'approval_type_row' => 'sequential',
                                'approver_type_row' => 'Approver',
                            ]);

                            $emailTrigger->sendEmail($contract->id, '', '', $apUser->Email, "Contract " . $request->input('contractRenew') . " request Alert", $senattment['filename'],  $senattment['filurl'], 'newContract');

                            $ord++;
                            $first = false;
                        }
                    }
                }

                Contract::where('id', $contract->id)->update(['contract_status' => $statusApprvr, 'substatus' => $subStatusApprvr]);
            //}
        }
        //}

        $namePartygroup .= '-' . date("Y");

        $namePartygroup = encryptString($namePartygroup, 'contract_name');

        //Unique Code

        $con_code = sprintf('%04d', $contract->id);
        $unique_code = "CON" . $internal_first_location['internal_name'] . $request->input('BasicContract.DepartmentType') . $request->input('BasicContract.catgoeryType') . $internal_first_location['location'] . $con_code;

        Contract::where('id', $contract->id)->update(['contract_name' => $namePartygroup, 'contract_unique_id' => $unique_code]);
        
        
       if ($contract) {
            $updateOldcontract = Contract::where('id', $request->contractid)->update([
                'renewtype' => $request->input('contractRenew')
            ]);
        }        


        if ($request->hasFile('file')) {

            $file = $request->file('file');
            $filename = file_name($file);
            $filePath = $controller->storeFile($file, '', $contract->id, $filename);
            $senattment['filename'][] = $filename;
            $senattment['filurl'][] = $filePath;
            
            $finalNotifiers = "";
            
            //For Getting Notifiers List
            if(isset($signatory_data_decoded['notify']) && count($signatory_data_decoded['notify']) > 0){
                $finalNotifiers = $signatory_data_decoded['notify'];
                $finalNotifiers = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $finalNotifiers)->pluck('Email')->toArray();
            }
            
            if(isset($all_approvers) && count($all_approvers) > 0){

                $approversArr = [];
                foreach($all_approvers as $app_data){
                    $approversArr[] = $app_data->id;
                }
                
                $approversArr = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $approversArr)->pluck('Email')->toArray();
                
                if($finalNotifiers == ""){
                    $finalNotifiers = [];
                } 
                
                $finalNotifiers = array_unique(array_merge($finalNotifiers, $approversArr));
            }

            if ($nextAprroverEmail != "") {
                $controller->changePermission($filePath, $finalNotifiers, $nextAprroverEmail);
                $MailSent = $emailTrigger->sendEmail($contract->id, '', '', $nextAprroverEmail, "Contract " . $request->input('contractRenew') . " request Alert", $senattment['filename'],  $senattment['filurl'], 'newContract');
            }
            Contract::where('id', $contract->id)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $filename]);
            ContractHistory::where('history_id', $contractHistory->history_id)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $filename]);
            
            $contracts = Contract::select('*')->where('id', $contract->id)->get();
            $ContractsFinal = $this->availableContracts($contracts, true);
            
            if(env('replace_doc_var_upload_docx')){
                $this->wordDocumentReaderActions($ContractsFinal[0], true, true);
            }
            //DB::commit();
        }

        //Create Document From Template 
        if ($request->input('attachments_type') !== null && $request->input('attachments_type') == 'template' && !$request->hasFile('file')) {

            $storagePath = '/storage/app/';

            $generateDocPath = $controller->get_file_path($contract->id);

            $html = $request->template_text;
            $html = html_entity_decode($html);

            $phpWord = new PhpWord();
            //$phpWord->getSettings()->setOutputEscapingEnabled(true);
            $section = $phpWord->addSection();
            //echo ($html);
            $html = trim($html, '"');
            $html = str_replace('&amp;', 'and', $html);
            $html = str_replace('<br>', '<br/>', $html);

            $html = $this->replaceCharacterAndStylesWord($html);

            //Clause Title Storage
            $pattern = '/clause_title_(.+?)_op/';

            $clauseTitles_ = [];

            if (preg_match_all($pattern, $html, $matches)) {

                if (isset($matches[1])) {
                    foreach ($matches[1] as $ttles) {
                        $clauseTitles_[] = [
                            'clause_category' => $ttles,
                            'contract_id' => $contract->id
                        ];
                    }
                }
            }


            if (count($clauseTitles_) > 0) {
                ClausesContractsLink::insert($clauseTitles_);
            }

            //Replace Custom Vars
            $contracts = Contract::select('*')->where('id', $contract->id)->get();
            $ContractsFinal = $this->availableContracts($contracts, true);
            $html = $this->replaceWordText('', $html, $ContractsFinal[0], false);

            // Add the HTML to the Word document
            Html::addHtml($section, $html, false, true);

            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

            $generatedDocumentName = 'drafted_contract_' . strtotime(date('d-m-y h:i:s')) . '.docx';

            $senattment['filename'][] = $generatedDocumentName;
            
            if (fileStorageType() == "Local") {
                $finalPath = base_path() . $storagePath . $generateDocPath . '/' . $generatedDocumentName;
                $writer->save($finalPath);
                $finalFilePathName = $generateDocPath . '/' . $generatedDocumentName;
            } else {
                $finalPath = base_path() . '/storage/app/contracts/tempDocs/' . $generatedDocumentName;
                $writer->save($finalPath);
                $finalFilePathName = $controller->storeContent($finalPath, $generateDocPath, $generatedDocumentName);
                unlink($finalPath);
                if(!$finalFilePathName){
                    DB::rollBack();
                    return redirect('contracts/renew/'.$request->contractid)->withErrors(['File Upload Issue'])->withInput();
                }
            }

            $senattment['filurl'][] = $finalFilePathName;
            
            $finalNotifiers = "";
            
            //For Getting Notifiers List
            if(isset($signatory_data_decoded['notify']) && count($signatory_data_decoded['notify']) > 0){

                $finalNotifiers = $signatory_data_decoded['notify'];
                $finalNotifiers = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $finalNotifiers)->pluck('Email')->toArray();                

            }
            
            if(isset($all_approvers) && count($all_approvers) > 0){

                $approversArr = [];
                foreach($all_approvers as $app_data){
                    $approversArr[] = $app_data->id;
                }
                
                $approversArr = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $approversArr)->pluck('Email')->toArray();
                
                if($finalNotifiers == ""){
                    $finalNotifiers = [];
                } 
                
                $finalNotifiers = array_unique(array_merge($finalNotifiers, $approversArr));
            }            

            if ($nextAprroverEmail != "") {
                $controller->changePermission($finalFilePathName, $finalNotifiers, $nextAprroverEmail);
                $MailSent = $emailTrigger->sendEmail($contract->id, '', '', $nextAprroverEmail, "Contract " . $request->input('contractRenew') . " request Alert", $senattment['filename'],  $senattment['filurl'], 'newContract');
            }

            Contract::where('id', $contract->id)->update(['contract_attachment' => $finalFilePathName, 'contract_attachment_filename' => $generatedDocumentName]);

            ContractHistory::where('history_id', $contractHistory->history_id)->update(['contract_attachment' => $finalFilePathName, 'contract_attachment_filename' => $generatedDocumentName]);

        }
        
        //Create User Action Log
        $this->crudUserActionLog($contract->id,'contract','create', $contract->id, 1, $users[0]->Email ?? '',false, ($users[0]->FirstName ?? '')." ".($users[0]->LastName ?? ''));
        
        DB::commit();

        return redirect('/contracts/list')->with('message', 'Contract Renewed Successfully Click <a href="' . url('/contracts/' . $contract->id) . '">Here</a> to view')->with('alert-class', 'alert-success');
    }

    public function renewCreateContractOld(Request $request)
    {


        $controller =  fileStorageTypeController();

        $locals = $request->input('Partygroup.party');
        //To Get First Internal Party Location
        $internal_first_location = "";
        foreach ($locals as $partyLoc) {

            if (isset($partyLoc['location']) && $partyLoc['location'] != "" && $partyLoc['mode'] == "Internal") {
                $internal_first_location = $partyLoc;
                break;
            }
        }
        if ($internal_first_location == "") {
            $invalid_location_error = array('Please Choose one internal party with location in Party Details');
            return redirect('contracts/renew/'.$request->contractid)->withErrors($invalid_location_error)->withInput();
        }

        $approval_user_column = "approval_required_users_renewed";
        $approvalTypeGlobal = "renewed";
        if ($request->input('contractRenew') != 'renew') {
            $approval_user_column = "approval_required_users_addendum";
            $approvalTypeGlobal = "addendum";
        }
        $financialLimit = $this->financialLimit(
            $internal_first_location['location'],
            $request->input('BasicContract.DepartmentType'),
            $request->input('BasicContract.catgoeryType'),
            $request->input('BasicContract.contractType'),
            $request->input('ContractValue.value'),
            $approval_user_column
        );

        $financialLimitDecoded = json_decode($financialLimit)[0];

        $signatory_data_decoded = (array)json_decode($financialLimitDecoded->signatory);
        $app_type_data_decoded = (array)json_decode($financialLimitDecoded->approval_type);
        $app_status_data_decoded = (array)json_decode($financialLimitDecoded->approval_status);

        $signatory_array = (array)($signatory_data_decoded['sign']);
        $owner_array = (array)($signatory_data_decoded['owner']);
        $signatory_data_decoded = [];
        $signatory_data_decoded['sign'] = $signatory_array[$approvalTypeGlobal];
        $signatory_data_decoded['owner'] = $owner_array[$approvalTypeGlobal];
        $financialLimitDecoded->signatory = json_encode($signatory_data_decoded);
        $financialLimitDecoded->approval_type = $app_type_data_decoded[$approvalTypeGlobal];
        $financialLimitDecoded->approval_status = $app_status_data_decoded[$approvalTypeGlobal];


        $all_approvers = json_decode($financialLimitDecoded->approver, true);

        if (!$request->owner) {
            //Owner/Initiator Validation
            $owner_initiator = session()->get('contractSessionUser');

            $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
                ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
                ->first();
            if (!$initiatior_exists) {
                $invalid_owner_error = array('Owner Not Available Please Contact Administrator');
                return redirect('contracts/renew/'.$request->contractid)->withErrors(array_merge($fileError, $invalid_owner_error))->withInput();
            }

            $owner_initiator_id = $initiatior_exists->id;
        } else {
            $owner_initiator_id = $request->owner;
        }

        $branchHeads = BranchUser::select(
            'id',
            decrypt_data('branchheadname', 'branch'),
            'Branchhead',
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->where('id', $internal_first_location['location'])->first();


        $branchHeadsError = [];
        foreach ($all_approvers as $ap_data) {
            if ($ap_data->type == 'designation') {
                if ($ap_data->name == 'branch_head') {
                    $branchHeadId = $branchHeads->Branchhead;
                    if ($branchHeadId == null) {
                        $branchHeadsError[] = "Branch Head Not Added in your selected Branch Please Update In Goal Portal";
                    }
                    $ap_data->id = $branchHeadId;
                }
                if ($ap_data->name == 'branch_dep_head') {
                    $branchDeptData = unserialize($branchHeads->departments);
                    //print_r($branchDeptData);
                    if (!isset($branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')])) {
                        $branchHeadsError[] = "Branch Department Head Not Added in your selected Branch Please Update In Goal Portal";
                    } else {
                        $ap_data->id = $branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')];
                    }
                }
                if ($ap_data->name == 'overall_dept_head') {
                    $entityDeptHead = EntityBusiness::select('overall_dept_head')->where('id', $request->input('BasicContract.DepartmentType'))->first();
                    if (!$entityDeptHead || !$entityDeptHead->overall_dept_head) {
                        $branchHeadsError[] = "Department Over All Head Not Added in your Entity Business Please Update In Goal Portal";
                    } else {
                        $ap_data->id = $entityDeptHead->overall_dept_head;
                    }
                }
            }
        }

        $financialLimitDecoded->approver = json_encode($all_approvers);

        $financialLimit = json_encode([$financialLimitDecoded]);




        $emailTrigger = new ContractNotificationController();
        $senattment = [];
        $senattment['filename'] = [];
        $senattment['filurl'] = [];

        $endContractDate = null;

        switch ($request->input("Duration.effectiveDate")) {
            case 'fixedTerm':
                $endContractDate = $request->input('Duration.fixedtimeEndDateofContract');
                break;
            case 'onetimeContract':
                $endContractDate = $request->input('Duration.onetimeEndDateofContract');
                break;
        }


        try {

            $contract = Contract::create([
                'contract_mode' => encryptString($request->input('contractMode'), 'contract_mode'),
                'contract_type' => $request->input('BasicContract.contractType'),
                'contract_tags' => json_encode($request->input('BasicContract.contractTypeTags') ?? []),
                'contract_description' => encryptString($request->input('BasicContract.contractDescription'), 'contract_description'),

                'department_id' => $request->input('BasicContract.DepartmentType'),
                'catgoery_id' => $request->input('BasicContract.catgoeryType'),

                'signatory' => $request->input('BasicContract.signatory'),
                'owner' => $request->input('owner'),


                'confidentialityagreement' => $request->input('BasicContract.Confidentialityagreement'),
                'exclusivity' => encryptString($request->input('BasicContract.Exclusivity'), 'exclusivity'),

                // Contract Duration
                'signing_date' => $request->input('Duration.signingDate'),
                'commencement_type' => encryptString($request->input('Duration.commencementDate'), 'commencement_type'), // corrected key
                'fixed_date' => $request->input('Duration.fixedDate'),
                'event_name' => encryptString($request->input('Duration.eventDetails'), 'event_name'),
                'end_contract_type' => encryptString($request->input('Duration.effectiveDate'), 'end_contract_type'),
                // 'onetime_end_date' => $request->input('Duration.onetimeEndDateofContract'),
                // 'fixedterm_end_date' => $request->input('Duration.fixedtimeEndDateofContract'),
                'contract_end_date' => $endContractDate,
                'renewal_type' => encryptString($request->input('Duration.typeRenewal'), 'renewal_type'),
                'period_auto_renewal' => $request->input('Duration.periodAutoRenewal'),
                'period_auto_renewal_unit' => encryptString($request->input('Duration.periodAutoRenewalPeriod'), 'period_auto_renewal_unit'),
                'auto_renewal_date' => $request->input('Duration.autoRenewalDate'),
                'manual_renewal_date' => $request->input('Duration.autoManualRenewalDate'),
                'evergreen_condition' => encryptString($request->input('Duration.conditionEndContract'), 'evergreen_condition'),
                'termination_date' => $request->input('Duration.terminationDate'),
                'termination_reason' => encryptString($request->input('Duration.reasonTermination'), 'termination_reason'),


                // Contract Value
                'currency' => encryptString($request->input('ContractValue.currency'), 'currency'),
                'billing_value' => encryptString($request->input('ContractValue.billingvalue'), 'billing_value'),
                'currency_value' => encryptString($request->input('ContractValue.value'), 'currency_value'),
                'total_value' => encryptString($request->input('ContractValue.totalvalue'), 'total_value'),
                'payment_schedule' => encryptString($request->input('ContractValue.paymentSchedule'), 'payment_schedule'),
                'currency_contract' => encryptString($request->input('ContractValue.currencyContract'), 'currency_contract'),
                'payment_terms' => encryptString($request->input('ContractValue.paymentTerms'), 'payment_terms'),
                'billing_frequency' => encryptString($request->input('ContractValue.billingFrequency'), 'billing_frequency'),
                'taxes' => encryptString($request->input('ContractValue.taxes'), 'taxes'),
                'escalation_clauses' => encryptString($request->input('ContractValue.escalationClauses'), 'escalation_clauses'),
                'discounts' => encryptString($request->input('ContractValue.discounts'), 'discounts'),
                'retention' => encryptString($request->input('ContractValue.retention'), 'retention'),
                'payment_escrow' => encryptString($request->input('ContractValue.payment_escrow'), 'payment_escrow'),
                'financial_guarantees' => encryptString($request->input('ContractValue.financialGuarantees'), 'financial_guarantees'),
                'currency_conversion' => encryptString($request->input('ContractValue.currencyConversion'), 'currency_conversion'),

                // Reminder Value
                'reminder_first_alert' => encryptString($request->input('Duration.Reminder.first.alertMe'), 'reminder_first_alert'),
                'reminder_first_alertMeOn' => encryptString($request->input('Duration.Reminder.first.alertMeDay') . ' ' . $request->input('Duration.Reminder.first.alertMePrior') . ' ' . $request->input('Duration.Reminder.first.alertMeType'), 'reminder_first_alertMeOn'),
                'reminder_first_alert_repeats' => encryptString($request->input('Duration.Reminder.first.repeats'), 'reminder_first_alert_repeats'),
                'reminder_second_alert' => encryptString($request->input('Duration.Reminder.second.alertMe'), 'reminder_second_alert'),
                'reminder_second_alertMeOn' => encryptString($request->input('Duration.Reminder.second.alertMeDay') . ' ' . $request->input('Duration.Reminder.second.alertMePrior') . ' ' . $request->input('Duration.Reminder.second.alertMeType'), 'reminder_second_alertMeOn'),
                'reminder_second_alert_repeats' => encryptString($request->input('Duration.Reminder.second.repeats'), 'reminder_second_alert_repeats'),
                'reminder_escalation_alert' => encryptString($request->input('Duration.Reminder.escalation.alertMe'), 'reminder_escalation_alert'),
                'reminder_escalation_alertMeOn' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay') . ' ' . $request->input('Duration.Reminder.escalation.alertMePrior') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType'), 'reminder_escalation_alertMeOn'),
                'reminder_escalation_alert_repeats' => encryptString($request->input('Duration.Reminder.escalation.repeats'), 'reminder_escalation_alert_repeats'),
                'reminder_escalation_alert_after' => encryptString($request->input('Duration.Reminder.escalation.alertMe_after'), 'reminder_escalation_alert_after'),
                'reminder_escalation_alertMeOn_after' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay_after') . ' ' . $request->input('Duration.Reminder.escalation.alertMeAfter') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType_after'), 'reminder_escalation_alertMeOn_after'),
                'reminder_escalation_alert_repeats_after' => encryptString($request->input('Duration.Reminder.escalation.repeats_after'), 'reminder_escalation_alert_repeats_after'),

                'rules_id' => $financialLimit,

                'custom_fields_data' => json_encode($request->input('customFields')),

                //Status Details
                'contract_status' => 'Draft',
                'substatus' => 'Initial Draft',
                'parentcontract' => $request->contractid,
                'created_by' => $owner_initiator_id
            ]);

            $ContractHistory = ContractHistory::create([
                'id' => $contract->id,
                'contract_mode' => encryptString($request->input('contractMode'), 'contract_mode'),
                'contract_type' => $request->input('BasicContract.contractType'),
                'contract_description' => encryptString($request->input('BasicContract.contractDescription'), 'contract_description'),
                'department_id' => $request->input('BasicContract.DepartmentType'),
                'catgoery_id' => $request->input('BasicContract.catgoeryType'),

                'signatory' => $request->input('BasicContract.signatory'),
                'owner' => $request->input('owner'),

                'confidentialityagreement' => $request->input('BasicContract.Confidentialityagreement'),
                'exclusivity' => encryptString($request->input('BasicContract.Exclusivity'), 'exclusivity'),



                // Contract Duration

                'signing_date' => $request->input('Duration.signingDate'),
                'commencement_type' => encryptString($request->input('Duration.commencementDate'), 'commencement_type'),
                'fixed_date' => $request->input('Duration.fixedDate'),
                'event_name' => encryptString($request->input('Duration.eventDetails'), 'event_name'),
                'end_contract_type' => encryptString($request->input('Duration.effectiveDate'), 'end_contract_type'),
                'contract_end_date' => $endContractDate,
                'renewal_type' => encryptString($request->input('Duration.typeRenewal'), 'renewal_type'),
                'period_auto_renewal' => $request->input('Duration.periodAutoRenewal'),
                'period_auto_renewal_unit' => encryptString($request->input('Duration.periodAutoRenewalPeriod'), 'period_auto_renewal_unit'),
                'auto_renewal_date' => $request->input('Duration.autoRenewalDate'),
                'manual_renewal_date' => $request->input('Duration.autoManualRenewalDate'),
                'evergreen_condition' => encryptString($request->input('Duration.conditionEndContract'), 'evergreen_condition'),
                'termination_date' => $request->input('Duration.terminationDate'),
                'termination_reason' => encryptString($request->input('Duration.reasonTermination'), 'termination_reason'),



                // Contract Value

                'currency' => encryptString($request->input('ContractValue.currency'), 'currency'),
                'billing_value' => encryptString($request->input('ContractValue.billingvalue'), 'billing_value'),
                'currency_value' => encryptString($request->input('ContractValue.value'), 'currency_value'),
                'total_value' => encryptString($request->input('ContractValue.totalvalue'), 'total_value'),
                'payment_schedule' => encryptString($request->input('ContractValue.paymentSchedule'), 'payment_schedule'),
                'currency_contract' => encryptString($request->input('ContractValue.currencyContract'), 'currency_contract'),
                'payment_terms' => encryptString($request->input('ContractValue.paymentTerms'), 'payment_terms'),
                'billing_frequency' => encryptString($request->input('ContractValue.billingFrequency'), 'billing_frequency'),
                'taxes' => encryptString($request->input('ContractValue.taxes'), 'taxes'),
                'escalation_clauses' => encryptString($request->input('ContractValue.escalationClauses'), 'escalation_clauses'),
                'discounts' => encryptString($request->input('ContractValue.discounts'), 'discounts'),
                'retention' => encryptString($request->input('ContractValue.retention'), 'retention'),
                'payment_escrow' => encryptString($request->input('ContractValue.paymentEscrow'), 'payment_escrow'),
                'financial_guarantees' => encryptString($request->input('ContractValue.financialGuarantees'), 'financial_guarantees'),
                'currency_conversion' => encryptString($request->input('ContractValue.currencyConversion'), 'currency_conversion'),




                'reminder_first_alert' => encryptString($request->input('Duration.Reminder.first.alertMe'), 'reminder_first_alert'),
                'reminder_first_alertMeOn' => encryptString($request->input('Duration.Reminder.first.alertMeDay') . ' ' . $request->input('Duration.Reminder.first.alertMePrior') . ' ' . $request->input('Duration.Reminder.first.alertMeType'), 'reminder_first_alertMeOn'),
                'reminder_first_alert_repeats' => encryptString($request->input('Duration.Reminder.first.repeats'), 'reminder_first_alert_repeats'),
                'reminder_second_alert' => encryptString($request->input('Duration.Reminder.second.alertMe'), 'reminder_second_alert'),
                'reminder_second_alertMeOn' => encryptString($request->input('Duration.Reminder.second.alertMeDay') . ' ' . $request->input('Duration.Reminder.second.alertMePrior') . ' ' . $request->input('Duration.Reminder.second.alertMeType'), 'reminder_second_alertMeOn'),
                'reminder_second_alert_repeats' => encryptString($request->input('Duration.Reminder.second.repeats'), 'reminder_second_alert_repeats'),
                'reminder_escalation_alert' => encryptString($request->input('Duration.Reminder.escalation.alertMe'), 'reminder_escalation_alert'),
                'reminder_escalation_alertMeOn' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay') . ' ' . $request->input('Duration.Reminder.escalation.alertMePrior') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType'), 'reminder_escalation_alertMeOn'),
                'reminder_escalation_alert_repeats' => encryptString($request->input('Duration.Reminder.escalation.repeats'), 'reminder_escalation_alert_repeats'),
                'reminder_escalation_alert_after' => encryptString($request->input('Duration.Reminder.escalation.alertMe_after'), 'reminder_escalation_alert_after'),
                'reminder_escalation_alertMeOn_after' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay_after') . ' ' . $request->input('Duration.Reminder.escalation.alertMeAfter') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType_after'), 'reminder_escalation_alertMeOn_after'),
                'reminder_escalation_alert_repeats_after' => encryptString($request->input('Duration.Reminder.escalation.repeats_after'), 'reminder_escalation_alert_repeats_after'),
                'created_by' => $owner_initiator_id

            ]);

            if ($contract) {
                $updateOldcontract = Contract::where('id', $request->contractid)->update([
                    'renewtype' => $request->input('contractRenew')
                ]);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }


        if ($request->has('customFields')) {
            foreach ($request->input('customFields') as $customField) {
                if (isset($customField)) {

                    if (isset($customField['id']) && isset($customField['value']) && isset($contract->id)) {
                        CustomFieldsData::create([
                            'custom_field_id' => $customField['id'],
                            'custom_field_group' => 'contracts',
                            'custom_field_value' => $customField['value'],
                            'custom_field_group_id' => $contract->id
                        ]);
                    }
                }
            }
        }
        $contractTypeName = ContractType::where('contract_type_id', $request->input('BasicContract.contractType'))->first();

        $namePartygroup =  $contractTypeName->contract_type;

        if ($request->has('Duration.task')) {
            foreach ($request->input('Duration.task') as $ke => $tasks) {

                if (isset($tasks['name_of_task'])) {
                    Tasks::create([
                        'name_of_task' => encryptString($tasks['name_of_task'], 'name_of_task'),
                        'priority' => encryptString($tasks['priority'], 'priority'),
                        'start_date' => encryptString($tasks['start_date'], 'start_date'),
                        'end_date' => encryptString($tasks['end_date'], 'end_date'),
                        'description' => encryptString($tasks['description'], 'description'),
                        'status' => $tasks['status'],
                        'contract_id' => $contract->id
                    ]);
                }
            }
        }

        foreach ($request->input('Partygroup.party') as $ke => $customField) {

            if (isset($customField)) {

                $mode = $customField['mode'] ?? null;
                $externalType = $customField['external_type'] ?? null;
                $internalName = $customField['internal_name'] ?? null;
                $externalName = $customField['external_name'] ?? null;
                $locationId = null;

                if ($mode !== 'External') {
                    $locationId = $mode === 'Internal'
                        ? ($customField['location'] ?? null)
                        : ($customField['location_grp'] ?? null);
                }


                $ContractPartyDatanew = ContractPartyData::create([
                    'custom_field_group_id' => $contract->id,
                    'contract_party_type' => $mode,
                    'party_sub_type' => $mode === 'External' ? $externalType : 'Internal',
                    'contract_party_id' => $internalName,
                    'contract_party_exe_id' => $externalName,
                    'contract_party_location_id' => $locationId,
                ]);

                ContractPartyDataHistory::create([
                    'history_id' => $ContractHistory->id,
                    'id' => $ContractPartyDatanew->id,
                    'custom_field_group_id' => $contract->id,
                    'contract_party_type' => $mode,
                    'party_sub_type' => $mode === 'External' ? $externalType : 'Internal',
                    'contract_party_id' => $internalName,
                    'contract_party_location_id' => $locationId,
                ]);

                if ($ke < 2) {

                    $namePartygroup .= '-';
                    if ($mode === 'External' && !empty($externalName)) {
                        $party = ContractParties::select('company_name')->where('id', $externalName)->first();
                        if ($party && !empty($party->company_name)) {
                            $namePartygroup .= decryptString($party->company_name, 'company_name');
                        }
                    } else {
                        $namePartygroupStr = DB::table('entity')
                            ->select('Nameoftheentity', decrypt_data('Nameoftheentity', 'entity'))
                            ->where('id', $internalName)
                            ->first()->Nameoftheentity;
                    }
                }
            }
        }

        $users = AddUsers::select('id',  decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $owner_initiator_id)->get();
        $nextAprroverEmail = "";
        $appArr = json_decode(trim($financialLimit));
        $randNo = rand(0, 99999);
        if (is_array($appArr) && count($appArr) > 0 && isset($users[0])) {
            $approval_type = $appArr[0]->approval_type;
            $approval_status = $appArr[0]->approval_status;

            if ($approval_type == 'sequential') {

                $unique_id = $contract->id . $randNo;

                if ($approval_status == 'required') {
                    $statusPreApprvr = 'Draft';
                    $statusApprvr = 'Draft';
                    $subStatusApprvr = 'Initial Draft';
                    if ($request->input('contractMode') == 'old') {
                        $statusPreApprvr = 'Negotiation';
                        $statusApprvr = 'Approval';
                        $subStatusApprvr = 'Pending Approval';
                        $cur_date = date('Y-m-d');
                        $contract_end_type = $request->input('Duration.effectiveDate');
                        $end_date_of_contract = $endContractDate;
                        if(!env('skip_renew_approval')){
                            if ($contract_end_type == 'onetimeContract') {
                                $contract_status = 'executed';
                                if ($end_date_of_contract != '') {
                                    if (strtotime($end_date_of_contract) > strtotime($cur_date)) {
                                        $subStatusApprvr = 'active';
                                    } elseif (strtotime($cur_date) > strtotime($end_date_of_contract)) {
                                        $subStatusApprvr = 'completed';
                                    }
                                } else {
                                    $subStatusApprvr = 'active';
                                }
                            } elseif ($contract_end_type == 'evergreen') {
                                $subStatusApprvr = 'active';
                            } elseif ($contract_end_type == 'fixedTerm') {
                                if ($end_date_of_contract != '') {
                                    if (strtotime($end_date_of_contract) > strtotime($cur_date)) {
                                        $subStatusApprvr = 'active';
                                    } elseif (strtotime($cur_date) > strtotime($end_date_of_contract)) {
                                        $subStatusApprvr = 'expired';
                                    }
                                } else {
                                    $subStatusApprvr = 'active';
                                }
                            }                           
                        }
                    }
                } else {
                    $statusPreApprvr = 'Approval';
                    $statusApprvr = 'Signing';
                    $subStatusApprvr = 'Approved';

                    $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $signatory)->get();
                }

                //Create Approval Flow
                ApprovalContracts::create([
                    'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                    'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                    'status' => encryptString($statusApprvr, 'status'),
                    'contract_id' => $contract->id,
                    'orderval' => 0,
                    'unique_id' => $base_unique . '_g0',
                    'flag' => 1,
                    'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                ]);

                $nextAprroverEmail = $users[0]->Email;
                Contract::where('id', $contract->id)->update(['contract_status' => $statusApprvr, 'substatus' => $subStatusApprvr]);
            }
        }
        //}

        //$users = AddUsers::select('id',  decrypt_data('FirstName', 'AddUsers'))->get();
        // $users = AddUsers::select(DB::raw("id, AES_DECRYPT(FirstName,'dummy.AddUsers') AS FirstName"))->get();

        $namePartygroup .= '-' . date("Y");

        $namePartygroup = encryptString($namePartygroup, 'contract_name');

        //Unique Code

        $con_code = sprintf('%04d', $contract->id);
        $unique_code = "CON" . $internal_first_location['internal_name'] . $request->input('BasicContract.DepartmentType') . $request->input('BasicContract.catgoeryType') . $internal_first_location['location'] . $con_code;

        Contract::where('id', $contract->id)->update(['contract_name' => $namePartygroup, 'contract_unique_id' => $unique_code]);

        if ($request->hasFile('file')) {

            $file = $request->file('file');
            $filePath = $controller->storeFile($file, $namePartygroup . '-' . $contract->id, $contract->id);
            $filename = file_name($file);
            $senattment['filename'][] = $filename;
            $senattment['filurl'][] = $filePath;
            if ($nextAprroverEmail != "") {
                $controller->changePermission($filePath, "", $nextAprroverEmail);
            }
            Contract::where('id', $contract->id)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $filename]);
        }

        return redirect('/contracts/list')->with('message', 'Contract Renewed Successfully Click <a href="' . url('/contracts/' . $contract->id) . '">Here</a> to view')->with('alert-class', 'alert-success');
        return response()->json(['message' => 'Contract renewed successfully!'], 200);
    }

    public function terminateContractList(Request $request, $id)
    {
        $contracts = Contract::select('*')->where('id', $id)->get();

        $ContractsFinal = $this->availableContracts($contracts, true);

        if (count($ContractsFinal) == 0) {
            return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract Access')->with('alert-class', 'alert-danger');
        }

        if (strtolower($ContractsFinal[0]->substatus) == 'terminated') {
            return redirect('/contracts/list')->with('message', 'Oops! Invalid Contract To Terminate')->with('alert-class', 'alert-danger');
        }

        return view('contract::contract.terminateContract')->with('contract_id', $id);
    }
    public function financialLimit($location, $department, $category, $contract_type, $contract_value, $approval_column = 'approval_required_users', $other_column_key = 0)
    {
        // Prefer rule_builder_data-driven matching (consistent with evaluateDiscountApproval)
        $defaultLimit = FinancialLimit::select("id", "approval_type", "approval_status", "$approval_column as approver", "approval_signatory_owner as signatory")->where('id', 1)->get();
        try {
            $contractValueNum = null;
            if ($contract_value !== null && $contract_value !== '' && strtoupper($contract_value) !== 'NULL') {
                $contractValueNum = is_numeric($contract_value) ? floatval($contract_value) : null;
            }

            // Get all normal financial limits that have rule builder data
            $limits = FinancialLimit::where('status', 1)
                ->where('approval_flow_type', 'normal')
                ->whereNotNull('rule_builder_data')
                ->where('id', '!=', 1) // exclude default limit
                ->get();

            foreach ($limits as $limit) {
                $rb = @json_decode($limit->rule_builder_data, true);
                if (!is_array($rb) || empty($rb['gcondition']) || !is_array($rb['gcondition'])) {
                    continue;
                }

                foreach ($rb['gcondition'] as $group) {
                    // Normalize group values to arrays when present
                    $gLocations = $group['location'] ?? ($group['locations'] ?? null);
                    $gDepartments = $group['department'] ?? ($group['departments'] ?? null);
                    $gCategory = $group['category'] ?? ($group['categories'] ?? null);
                    $gContractTypes = $group['contractType'] ?? ($group['contract_type'] ?? null);

                    $toArray = function ($v) {
                        if (is_null($v) || $v === '') return [];
                        if (is_array($v)) return array_values($v);
                        return [$v];
                    };

                    $locs = array_map('strval', $toArray($gLocations));
                    $depts = array_map('strval', $toArray($gDepartments));
                    $cats = array_map('strval', $toArray($gCategory));
                    $cts = array_map('strval', $toArray($gContractTypes));

                    $matches = function ($arr, $val) {
                        // empty array => wildcard
                        if (empty($arr)) return true;
                        // wildcard marker 0
                        if (in_array('0', $arr, true) || in_array(0, $arr, true)) return true;
                        return in_array((string)$val, $arr, true) || in_array((int)$val, $arr, true);
                    };

                    // Check basic matches
                    if (! $matches($locs, $location)) continue;
                    if (! $matches($depts, $department)) continue;
                    if (! $matches($cats, $category)) continue;
                    if (! $matches($cts, $contract_type)) continue;

                    // Check monetary bounds if present
                    $min = isset($group['limitFrom']) && $group['limitFrom'] !== '' ? floatval($group['limitFrom']) : null;
                    $max = isset($group['limitUp']) && $group['limitUp'] !== '' ? floatval($group['limitUp']) : null;

                    if ($min === null && $max === null) {
                        // both unspecified, group matches
                        //return "null value";
                        return FinancialLimit::select("id", "approval_type", "approval_status", "$approval_column as approver", "approval_signatory_owner as signatory")->where('id', $limit->id)->limit(1)->get();
                    }

                    if ($contractValueNum === null) {
                        // if contract value not provided, only match groups that do not specify bounds
                        continue;
                    }

                    $inRange =
                        ($min === null || $contractValueNum >= $min) &&
                        ($max === null || $contractValueNum <= $max);

                    if ($inRange) {
                        return FinancialLimit::select("id", "approval_type", "approval_status", "$approval_column as approver", "approval_signatory_owner as signatory")->where('id', $limit->id)->limit(1)->get();
                    }
                }
            }
        } catch (\Exception $e) {
            // If any error occurs while parsing rule_builder_data, fall back to legacy behavior below
            return $defaultLimit;
            \Log::error('financialLimit: rule_builder_data parse error: '.$e->getMessage());
        }
        
        
        
        return $defaultLimit;
    }
    
    

    /**
     * Normalise a parent-grouped approver payload (a top-level map keyed by parent
     * type: review/negotiation/finalization/approval/signatory, optionally with a
     * `_parent_routing` entry) into an ordered flat list of group objects so the
     * grouped-approver handling can process it sequentially. Non parent-grouped
     * payloads (legacy grouped arrays or legacy flat lists) are returned unchanged.
     */
    private function normalizeParentGroupedApprovers($approverJson)
    {
        $arr = is_object($approverJson) ? (array)$approverJson : $approverJson;
        if (!is_array($arr)) {
            return $approverJson;
        }

        $parentKeys = ['review', 'negotiation', 'finalization', 'approval', 'signatory'];
        if (empty(array_intersect(array_keys($arr), $parentKeys))) {
            return $approverJson; // not parent-grouped; leave as-is
        }

        $groups = [];
        foreach ($parentKeys as $parentType) {
            if (!isset($arr[$parentType]) || !is_array($arr[$parentType])) {
                continue;
            }
            foreach ($arr[$parentType] as $group) {
                $groups[] = $group;
            }
        }
        return $groups;
    }

    private function flattenApproversArray($approverJson)
    {
        $approverJson = $this->normalizeParentGroupedApprovers($approverJson);
        $approvalList = [];
        if (is_array($approverJson) && isset($approverJson[0]) && isset($approverJson[0]->approvers)) {
            foreach ($approverJson as $group) {
                if (!isset($group->approvers) || !is_array($group->approvers)) continue;
                foreach ($group->approvers as $ap) $approvalList[] = $ap;
            }
        } elseif (is_array($approverJson)) {
            $approvalList = $approverJson;
        }
        return $approvalList;
    }

    private function collectApproverIdsFromJson($approverJson)
    {
        $list = $this->flattenApproversArray($approverJson);
        $ids = [];
        foreach ($list as $ap) {
            if (isset($ap->id) && is_numeric($ap->id)) $ids[] = $ap->id;
        }
        return array_values(array_unique($ids));
    }    
    public function financialLimitOld($location, $department, $category, $contract_type, $contract_value, $approval_column = 'approval_required_users', $other_column_key = 0)
    {

        $financial_limit = [];

        $where_clause = array(
            0 => 'FIND_IN_SET(' . $location . ', location) AND  FIND_IN_SET(' . $department . ', department) AND  FIND_IN_SET(' . $category . ',category) AND ( FIND_IN_SET(' . $contract_type . ',contract_type) OR contract_type = 0)',
            1 => 'FIND_IN_SET(' . $location . ', location) AND  FIND_IN_SET(' . $department . ', department) AND  (FIND_IN_SET(' . $category . ',category) OR category = 0) AND ( FIND_IN_SET(' . $contract_type . ',contract_type) OR contract_type = 0)',
            2 => 'FIND_IN_SET(' . $location . ', location) AND  (FIND_IN_SET(' . $department . ', department) OR department = 0) AND  (FIND_IN_SET(' . $category . ',category) OR category = 0) AND ( FIND_IN_SET(' . $contract_type . ',contract_type) OR contract_type = 0)',
            3 => 'FIND_IN_SET(' . $location . ', location) AND  (FIND_IN_SET(' . $department . ', department) OR department = 0) AND  (FIND_IN_SET(' . $category . ',category) OR category = 0) AND ( FIND_IN_SET(' . $contract_type . ',contract_type) OR contract_type = 0)',
            4 => '(FIND_IN_SET(' . $location . ', location) OR location = 0) AND  (FIND_IN_SET(' . $department . ', department) OR department = 0) AND  (FIND_IN_SET(' . $category . ',category) OR category = 0) AND ( FIND_IN_SET(' . $contract_type . ',contract_type) OR contract_type = 0)'
        );

        if (!$contract_value) {
            $contract_value = 'null';
        }
        $contract_where_clause = array(
            0 => '(' . $contract_value . ' BETWEEN lower_limit AND upper_limit)',
            1 => '(' . $contract_value . ' BETWEEN lower_limit AND upper_limit)',
            2 => '(' . $contract_value . ' BETWEEN lower_limit AND upper_limit)',
            3 => '(' . $contract_value . ' BETWEEN lower_limit AND upper_limit)',
            4 => '(' . $contract_value . ' BETWEEN lower_limit AND upper_limit OR (lower_limit is null OR upper_limit is null))',
        );
        $i = 0;


        do {
            if (count($financial_limit) > 0) {
                return $financial_limit;
                break;
            }
            $financial_limit = FinancialLimit::select("id", "approval_type", "approval_status", "$approval_column as approver", "approval_signatory_owner as signatory")
                ->whereRaw($where_clause[$i])
                ->where('status', 1)
                ->where('approval_flow_type', 'normal')
                ->whereRaw($contract_where_clause[$i])
                ->limit(1)
                ->orderBy('id', 'DESC')
                ->get();

            $i++;
            if (($i == 5) && (count($financial_limit) == 0)) {

                $financial_limit = FinancialLimit::select("id", "approval_type", "approval_status", "$approval_column as approver", "approval_signatory_owner as signatory")
                    ->where('status', 1)
                    ->where('approval_flow_type', 'normal')
                    ->limit(1)
                    ->orderBy('id', 'DESC')
                    ->get();
                return $financial_limit;
                break;
            }
        } while ($i < 6);
    }

    public function contractCreateparties()
    {

        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();
        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();


        $branchs = Branch::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $branchsUser = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $branchsAvailable = BranchUser::pluck('id')->toArray();


        $entities = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'))
            ->get();

        $contractPartiesAll =  ContractParties::select('*')->get();


        $contractParties = [];

        foreach ($contractPartiesAll  as $contractPartie) {
            $contractPartie->available = true;

            //Only For Branch
            if ($contractPartie->engagement_level == 1) {
                if ($contractPartie->engagement_branch && !in_array($contractPartie->engagement_branch, $branchsAvailable)) {
                    $contractPartie->available = false;
                }
            } else {
                if ($contractPartie->engagement_access_level && !in_array($contractPartie->engagement_access_level, $branchsAvailable)) {
                    $contractPartie->available = false;
                }
            }

            if ($contractPartie->available) {
                $contractParties[] = $contractPartie;
            }
        }



        return view('contract::contract.partyDetails', compact('contractParties', 'entities', 'branchs', 'branchsUser', 'customFields', 'categorys', 'contractTypes'));
    }


    public function contractCreatePartyList(Request $request)
    {

        $branchsAvailable = BranchUser::pluck('id')->toArray();


        $entities = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'))
            ->get();

        $contractPartiesQuery =  ContractParties::select('*');

        if (isset($request->partySubType)) {
            if ($request->partySubType !== 'individual') {
                $contractPartiesQuery->where('party_sub_type', '<>', 'individual');
            } else {
                $contractPartiesQuery->where('party_sub_type', $request->partySubType);
            }
        }

        $contractPartiesAll = $contractPartiesQuery->where('status', 1)->get();


        $contractParties = [];

        foreach ($contractPartiesAll  as $contractPartie) {
            $contractPartie->available = true;

            //Only For Branch
            if ($contractPartie->engagement_level == 1) {
                if ($contractPartie->engagement_branch && !in_array($contractPartie->engagement_branch, $branchsAvailable)) {
                    $contractPartie->available = false;
                }
            } else {
                if ($contractPartie->engagement_access_level && !in_array($contractPartie->engagement_access_level, $branchsAvailable)) {
                    $contractPartie->available = false;
                }
            }

            if ($contractPartie->available) {
                $contractParties[] = ['text' => decryptString($contractPartie->company_name, 'company_name'), 'id' => $contractPartie->id];
            }
        }

        return response()->json(['results' => $contractParties], 200);
    }

    public function contractCreate(Request $req, $aiparam='')
    {

        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();

        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();

        $geo_graph = $this->getGeoGraphDropdowns();

        $branchs = Branch::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $branchsUser = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $entities = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'), decrypt_data('EntityStatus', 'entity'))
            ->get();

        $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'), decrypt_data('AccessScope', 'AddUsers'), decrypt_data('Email', 'AddUsers'))->get();
        $usersSel = AddUsersSel::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'), decrypt_data('AccessScope', 'AddUsers'), decrypt_data('Email', 'AddUsers'))->get();
        $legalAdvisors = LegalAdvisor::where('status', 1)->orderBy('name')->get();

        $contractParties =  ContractParties::select('*')->get();

        $parties_label = array();
        $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','unrequired') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
            ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
            ->where('contract_parties_label.status', 1)->get();

        foreach ($label as $label_data) {
            $parties_label[$label_data->name] = [
                'name' => $label_data->name,
                'label_name' => $label_data->label_name,
                'is_required' => $label_data->is_required,
                'error_text' => $label_data->error_text,
                'is_regex' => $label_data->is_regex,
                'regex_id' => $label_data->regex_id,
                'regex_name' => $label_data->regex_name,
                'regex_pattern' => $label_data->pattern
            ];
        }
        $branch = Branch::select('id', decrypt_data('BranchName', 'branch'))->get();

        $country = Country::select('id', 'name')->get();

        $catego =  ContractCategories::select('*')->get();

        $ent = EntityBusiness::select('*')->get();

        $defVals = ['contractMode' => admin_setting('enable_new_contracts') ? 'new' : 'old', 'Exclusivity' => 'Non Exclusive', 'commencementDate' => 'FixedDate', 'end_contract_type_def' => 'fixedTerm'];

        //Owner/Initiator Validation
        $owner_initiator = session()->get('contractSessionUser');

        $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
            ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
            ->first();
        if (!$initiatior_exists) {
            $invalid_owner_error = array('Owner Not Available Please Contact Administrator');
            return redirect('contracts/create')->withErrors(array_merge($fileError, $invalid_owner_error))->withInput();
        }

        $owner_initiator_id = $initiatior_exists->id ?? 0;

        $priority = 'medium';
        
        $viewBlade = "contractCreate";
        
        //if($aiparam != ''){
           if(admin_setting('enable_ai_feature')){
                $viewBlade = 'contractCreateAi';
           }
           
           if($aiparam == 'marketing'){
               if(admin_setting('custom_contracts_type_id')){
                    $viewBlade = 'contractCreateRep';
               }else{
                    return redirect('/contracts/list/contract-custom')->with('message', 'Admin Configuration Missing Please Add Contract Type')->with('alert-class', 'alert-danger'); 
               }
           }
        //}
        
        $uniqueTempId = $this->generateUniqueContractTempId();
        $encTempContId = encryptString($uniqueTempId, 'unique_temp_contract_id');
        return view("contract::contract.$viewBlade", compact('catego', 'ent', 'branch', 'geo_graph', 'country', 'contractParties', 'entities', 'branchs', 'branchsUser', 'customFields', 'categorys', 'contractTypes', 'users', 'parties_label', 'defVals', 'usersSel', 'owner_initiator_id', 'priority', 'encTempContId', 'legalAdvisors'));
    }

    /**
     * Add New Contract — V3.
     *
     * Same data as contractCreate() plus the active annexure masters, rendered by
     * contractCreateV3. Runs alongside contractCreate() so contracts/create is untouched.
     */
    public function contractCreateV3(Request $req)
    {
        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();

        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();

        $geo_graph = $this->getGeoGraphDropdowns();

        $branchs = Branch::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $branchsUser = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $entities = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'), decrypt_data('EntityStatus', 'entity'))
            ->get();

        $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'), decrypt_data('AccessScope', 'AddUsers'), decrypt_data('Email', 'AddUsers'))->get();
        $usersSel = AddUsersSel::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'), decrypt_data('AccessScope', 'AddUsers'), decrypt_data('Email', 'AddUsers'))->get();
        $legalAdvisors = LegalAdvisor::where('status', 1)->orderBy('name')->get();

        $contractParties =  ContractParties::select('*')->get();

        $parties_label = array();
        $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','unrequired') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
            ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
            ->where('contract_parties_label.status', 1)->get();

        foreach ($label as $label_data) {
            $parties_label[$label_data->name] = [
                'name' => $label_data->name,
                'label_name' => $label_data->label_name,
                'is_required' => $label_data->is_required,
                'error_text' => $label_data->error_text,
                'is_regex' => $label_data->is_regex,
                'regex_id' => $label_data->regex_id,
                'regex_name' => $label_data->regex_name,
                'regex_pattern' => $label_data->pattern
            ];
        }
        $branch = Branch::select('id', decrypt_data('BranchName', 'branch'))->get();

        $country = Country::select('id', 'name')->get();

        $catego =  ContractCategories::select('*')->get();

        $ent = EntityBusiness::select('*')->get();

        $defVals = ['contractMode' => admin_setting('enable_new_contracts') ? 'new' : 'old', 'Exclusivity' => 'Non Exclusive', 'commencementDate' => 'FixedDate', 'end_contract_type_def' => 'fixedTerm'];

        //Owner/Initiator Validation
        $owner_initiator = session()->get('contractSessionUser');

        $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
            ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
            ->first();
        if (!$initiatior_exists) {
            return redirect('contracts/list')->withErrors(['Owner Not Available Please Contact Administrator'])->withInput();
        }

        $owner_initiator_id = $initiatior_exists->id ?? 0;

        $priority = 'medium';

        $annexures = AnnexureMaster::where('status', 1)->orderBy('annexure_name')->get();

        $uniqueTempId = $this->generateUniqueContractTempId();
        $encTempContId = encryptString($uniqueTempId, 'unique_temp_contract_id');

        return view("contract::contract.contractCreateV3", compact('catego', 'ent', 'branch', 'geo_graph', 'country', 'contractParties', 'entities', 'branchs', 'branchsUser', 'customFields', 'categorys', 'contractTypes', 'users', 'parties_label', 'defVals', 'usersSel', 'owner_initiator_id', 'priority', 'encTempContId', 'legalAdvisors', 'annexures'));
    }

    /**
     * Store a V3 contract.
     *
     * The V3-specific inputs (tenure, price revision, per-party vendor/address/contact and
     * annexure uploads) are handled inside storeContract() itself, guarded so they are
     * no-ops when absent. This wrapper exists so the V3 form posts to its own route and
     * validation failures return to the V3 page rather than contracts/create.
     */
    public function storeContractV3(Request $request)
    {
        $this->createRedirectPath = 'contracts/create-v3';

        return $this->storeContract($request);
    }

    /**
     * Persist the annexure files uploaded on the V3 create page.
     *
     * Two input shapes are handled: `annexures[<masterId>][file]` for the rows generated
     * from the annexure master, and `custom_annexures[<i>][annexure_name|file]` for
     * free-form rows. Files go through the same storage abstraction as the contract
     * document, so they land alongside it on Local / Google / Microsoft.
     *
     * Returns the number of annexures stored.
     */
    protected function storeContractAnnexures(Request $request, $contract, $controller, $createdBy = null)
    {
        $rows = [];

        foreach ((array) $request->input('annexures', []) as $masterId => $row) {
            $file = $request->file('annexures.' . $masterId . '.file');
            if (!$file) {
                continue;
            }

            $master = AnnexureMaster::find($row['annexure_master_id'] ?? $masterId);

            $rows[] = [
                'annexure_master_id' => $master->id ?? null,
                'annexure_name'      => $master->annexure_name ?? ('Annexure ' . $masterId),
                'title'              => $master->title ?? null,
                'file'               => $file,
            ];
        }

        foreach ((array) $request->input('custom_annexures', []) as $index => $row) {
            $file = $request->file('custom_annexures.' . $index . '.file');
            if (!$file) {
                continue;
            }

            $rows[] = [
                'annexure_master_id' => null,
                'annexure_name'      => $row['annexure_name'] ?? ('Annexure ' . ($index + 1)),
                'title'              => null,
                'file'               => $file,
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        $destinationFolder = $controller->get_file_path($contract->id);
        $sortOrder = 0;
        $stored = 0;

        foreach ($rows as $row) {
            /** @var \Illuminate\Http\UploadedFile $file */
            $file = $row['file'];

            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());

            // Re-check here too: this method is reachable from any caller, not just the
            // request that passed validation above.
            if (!in_array($extension, ['doc', 'docx'], true)) {
                \Log::warning('Skipped non-Word annexure upload for contract ' . $contract->id . ': ' . $originalName);
                continue;
            }

            $storedName = 'annexure_' . $contract->id . '_' . (++$sortOrder) . '_' . strtotime('now') . '.' . $extension;

            try {
                if (fileStorageType() == "Local") {
                    $absoluteFolder = base_path() . '/storage/app/' . $destinationFolder;
                    if (!is_dir($absoluteFolder)) {
                        mkdir($absoluteFolder, 0755, true);
                    }
                    $file->move($absoluteFolder, $storedName);
                    $storedPath = $destinationFolder . '/' . $storedName;
                } else {
                    // storeContent() needs a path on disk, so the upload is staged first.
                    $tempDir = base_path() . '/storage/app/contracts/tempDocs';
                    if (!is_dir($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }
                    $file->move($tempDir, $storedName);
                    $tempPath = $tempDir . '/' . $storedName;

                    $storedPath = $controller->storeContent($tempPath, $destinationFolder, $storedName);

                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }

                    if (!$storedPath) {
                        \Log::error('Annexure upload failed for contract ' . $contract->id . ': ' . $originalName);
                        continue;
                    }
                }
            } catch (\Exception $e) {
                // One unusable annexure must not lose the contract that was just created.
                \Log::error('Annexure upload failed for contract ' . $contract->id . ' (' . $originalName . '): ' . $e->getMessage());
                continue;
            }

            ContractAnnexure::create([
                'contract_id'        => $contract->id,
                'annexure_master_id' => $row['annexure_master_id'],
                'annexure_name'      => $row['annexure_name'],
                'title'              => $row['title'],
                'file_path'          => $storedPath,
                'file_name'          => $originalName,
                'sort_order'         => $sortOrder,
                'created_by'         => $createdBy,
            ]);

            $stored++;
        }

        return $stored;
    }

    /**
     * Party row markup for "Add more parties" on the V3 create page. Same data as
     * contractCreateparties(), rendered with the V3 partial that carries the extra
     * vendor code / address / contact inputs.
     */
    public function contractCreatepartiesV3()
    {
        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();
        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();

        $branchs = Branch::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $branchsUser = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $branchsAvailable = BranchUser::pluck('id')->toArray();

        $entities = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'))->get();

        $contractPartiesAll = ContractParties::select('*')->get();

        $contractParties = [];

        foreach ($contractPartiesAll as $contractPartie) {
            $contractPartie->available = true;

            //Only For Branch
            if ($contractPartie->engagement_level == 1) {
                if ($contractPartie->engagement_branch && !in_array($contractPartie->engagement_branch, $branchsAvailable)) {
                    $contractPartie->available = false;
                }
            } else {
                if ($contractPartie->engagement_access_level && !in_array($contractPartie->engagement_access_level, $branchsAvailable)) {
                    $contractPartie->available = false;
                }
            }

            if ($contractPartie->available) {
                $contractParties[] = $contractPartie;
            }
        }

        return view('contract::contract.partyDetailsV3', compact('contractParties', 'entities', 'branchs', 'branchsUser', 'customFields', 'categorys', 'contractTypes'));
    }


    /**
     * Add New Contract (AI) — V2.
     *
     * Runs alongside contractCreate() so the existing page keeps serving users
     * while this one is being validated. It renders contractCreateAiV2 and only
     * loads the data that view actually reads:
     *
     *   - Skipped entirely: $geo_graph, $country, $parties_label, $branch,
     *     $categorys and the full $contractParties dump. None are referenced by
     *     the V2 view or its partials.
     *   - Branch / entity lookups select just id + display name instead of
     *     AES_DECRYPT-ing eleven columns per row.
     *   - Party names are fetched on demand by contractCreatePartyListV2(); only
     *     the parties already chosen (repopulating after a validation failure)
     *     are resolved here.
     */
    public function contractCreateV2(Request $req)
    {
        //Owner/Initiator Validation
        $owner_initiator = session()->get('contractSessionUser');

        $initiatior_exists = AddUsers::select('id', decrypt_data('AccessScope', 'AddUsers'))
            ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
            ->first();

        if (!$initiatior_exists) {
            return redirect('contracts/create')
                ->withErrors(['Owner Not Available Please Contact Administrator'])
                ->withInput();
        }

        $owner_initiator_id = $initiatior_exists->id ?? 0;

        $customFields  = CustomFields::where('status', 1)->orderBy('order_id')->get();
        $contractTypes = ContractType::get();
        $catego        = ContractCategories::select('*')->get();
        $ent           = EntityBusiness::select('*')->get();

        // Only the columns the party partial renders.
        $branchs     = Branch::select('id', decrypt_data('LegalName', 'branch'))->get();
        $branchsUser = BranchUser::select('id', decrypt_data('LegalName', 'branch'))->get();
        $entities    = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'))->get();

        $users    = AddUsers::select('id', decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'), decrypt_data('Email', 'AddUsers'))->get();
        $usersSel = AddUsersSel::select('id', decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'), decrypt_data('Email', 'AddUsers'))->get();

        $legalAdvisors = LegalAdvisor::where('status', 1)->orderBy('name')->get();

        $defVals = [
            'contractMode'          => admin_setting('enable_new_contracts') ? 'new' : 'old',
            'Exclusivity'           => 'Non Exclusive',
            'commencementDate'      => 'FixedDate',
            'end_contract_type_def' => 'fixedTerm',
        ];

        // Labels for parties that are already selected, so the dropdowns can show
        // the current choice without shipping the whole party list.
        $partyNameOptions = [];
        $selectedPartyIds = collect(old('Partygroup.party', []))
            ->pluck('external_name')
            ->filter(function ($id) {
                return $id !== null && $id !== '';
            })
            ->unique()
            ->values();

        if ($selectedPartyIds->isNotEmpty()) {
            $selectedParties = ContractParties::select('id', 'company_name')
                ->whereIn('id', $selectedPartyIds)
                ->get();

            foreach ($selectedParties as $selectedParty) {
                $partyNameOptions[$selectedParty->id] = decryptString($selectedParty->company_name, 'company_name');
            }
        }

        $uniqueTempId  = $this->generateUniqueContractTempId();
        $encTempContId = encryptString($uniqueTempId, 'unique_temp_contract_id');

        return view('contract::contract.contractCreateAiV2', compact(
            'catego',
            'ent',
            'contractTypes',
            'customFields',
            'entities',
            'branchs',
            'branchsUser',
            'users',
            'usersSel',
            'legalAdvisors',
            'defVals',
            'owner_initiator_id',
            'encTempContId',
            'partyNameOptions'
        ));
    }

    /**
     * Party name lookup for the V2 create page.
     *
     * Same payload shape as contractCreatePartyList() — {"results":[{id,text}]} —
     * but the decrypted list is cached. company_name is encrypted with the
     * application key rather than MySQL's AES, so every lookup otherwise has to
     * decrypt each row in PHP before it can be filtered.
     *
     * The key carries a version stamp (row count + newest updated_at) so the
     * cache invalidates itself as soon as a party is added or edited, plus the
     * session identity that PartiesRoleBasedScope and BranchUser scope on, so one
     * user's visible set is never served to another.
     */
    public function contractCreatePartyListV2(Request $request)
    {
        $branchsAvailable = BranchUser::pluck('id')->toArray();

        $subType = $request->input('partySubType');

        try {
            $stamp = ContractParties::selectRaw("COUNT(*) as row_count, COALESCE(MAX(updated_at), '') as last_change")->first();
            $version = ($stamp->row_count ?? 0) . '|' . ($stamp->last_change ?? '');
        } catch (\Throwable $e) {
            // If the stamp query fails for any reason, fall back to a short
            // time bucket rather than serving a list that never refreshes.
            $version = 't' . floor(time() / 60);
        }

        $cacheKey = 'contract:partylist:v2:' . md5(json_encode([
            $subType,
            $branchsAvailable,
            session()->get('contractSessionUserRole'),
            session()->get('contractUserId'),
            session()->get('contractSessionUser'),
            $version,
        ]));

        $results = cache()->remember($cacheKey, now()->addMinutes(10), function () use ($subType, $branchsAvailable) {
            $contractPartiesQuery = ContractParties::select(
                'id',
                'company_name',
                'party_sub_type',
                'engagement_level',
                'engagement_branch',
                'engagement_access_level'
            );

            if (!is_null($subType)) {
                if ($subType !== 'individual') {
                    $contractPartiesQuery->where('party_sub_type', '<>', 'individual');
                } else {
                    $contractPartiesQuery->where('party_sub_type', $subType);
                }
            }

            $contractParties = [];

            foreach ($contractPartiesQuery->where('status', 1)->get() as $contractPartie) {
                $available = true;

                //Only For Branch
                if ($contractPartie->engagement_level == 1) {
                    if ($contractPartie->engagement_branch && !in_array($contractPartie->engagement_branch, $branchsAvailable)) {
                        $available = false;
                    }
                } else {
                    if ($contractPartie->engagement_access_level && !in_array($contractPartie->engagement_access_level, $branchsAvailable)) {
                        $available = false;
                    }
                }

                if ($available) {
                    $contractParties[] = [
                        'id'   => $contractPartie->id,
                        'text' => decryptString($contractPartie->company_name, 'company_name'),
                    ];
                }
            }

            return $contractParties;
        });

        return response()->json(['results' => $results], 200);
    }


    public function aiDocumentDataInterpreter(Request $request){
        
        
        if(!env('test_ai_interepter')){
            if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                return response()->json(['error' => 'Please upload a valid file.'], 422);
            }
            
            $aiApiBaseUrl = rtrim((string) admin_setting('ai_api_url'), '/');
            $client = new Client();
            $uploadedFile = $request->file('file');

            $uploadResponse = $this->uploadFileAndGetHttpsUrl($request);
            $uploadData = $uploadResponse->getData(true);
            if (($uploadData['success'] ?? false) !== true || empty($uploadData['path'])) {
                return response()->json(['error' => $uploadData['message'] ?? 'File upload failed.'], 422);
            }

            $storedPath = $uploadData['path'];
            $fileStream = Storage::disk('public')->readStream($storedPath);
            if ($fileStream === false) {
                \Storage::disk('public')->delete($storedPath);
                return response()->json(['error' => 'Unable to read uploaded file.'], 422);
            }

            try {
                $response = $client->request('POST', $aiApiBaseUrl.'/process-contract?mode=mistral-ocr', [
                    'multipart' => [
                        [
                            'name'     => 'file',
                            'contents' => $fileStream,
                            'filename' => $uploadedFile->getClientOriginalName(),
                        ],
                    ],
                ]);
            } finally {
                if (is_resource($fileStream)) {
                    fclose($fileStream);
                }
                \Storage::disk('public')->delete($storedPath);
            }
            
            $jsonData = json_decode($response->getBody()->getContents(), true);
        }else{
            $body = file_get_contents(base_path('assets/supportdocs/sampleresp.json'));
            // Decode original JSON
            $jsonData = json_decode($body, true);
        }
        
        $aiTempToken = decryptString($request->aiTokenTemp, 'unique_temp_contract_id');
        
        try {
            AiResponse::create([
                'contract_temp_id' => $aiTempToken,
                'airesponse'       => json_encode($jsonData),
                'status'           => '1',
            ]);

        
        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Error saving AI response: ' . $e->getMessage(), [
                'contract_temp_id' => $aiTempToken,
                'data' => $jsonData,
            ]);
        }        
            

        $company_profile = Companyprofile::select(
            'id',
            decrypt_data('entityname', 'companyprofile'),
            decrypt_data('buildingname', 'companyprofile'),
            decrypt_data('streetname', 'companyprofile'),
            decrypt_data('areaname', 'companyprofile'),
            decrypt_data('landmark', 'companyprofile')                
        )->where('entityid', session()->get('contractSessionEntity'))->first();

        $company_branches = Branch::select(
            'id',
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch')               
        )->get();

        $party_external_partynames = ContractParties::pluck('id', 'company_name')
            ->mapWithKeys(function ($id, $name) {
                return [strtolower(decryptString($name, 'company_name')) => $id];
            });

        $resp = $jsonData;

        // Helper: case-insensitive name matching
        $checkAndMarkExternal = function (&$entry) use ($party_external_partynames) {
            if (!isset($entry['name'])) {
                $entry['exist'] = false;
                $entry['type'] = '';
                return;
            }
            $name = trim($entry['name']);
            if (!empty($name)) {
                $name = strtolower($name);
                $entry['exist'] = $party_external_partynames[$name] ?? false;
                $entry['type'] = $entry['exist'] ? 'external' : '';
            }
        };
        
        $checkAndMarkInternal = function (&$entry) use ($company_profile, $company_branches) {
            
            if($company_profile){
                
                
                $matchedPercentage = 0;
                
                foreach($company_branches as $cbranch){
            
                    // Compute similarity percentage
                    $fullName = trim("{$company_profile->entityname}");
                    
                    $searchName = $entry['name'];
                    
                    //echo strtolower($entry['name'])."--->".$fullName."<br/>";
                    
                    //similar_text(strtolower($entry['name']),strtolower($fullName), $percentName);
                    
                    similar_text(strtolower(substr($searchName, 0, strlen($fullName))), strtolower($fullName), $percentName);

    
                    // Compute similarity percentage
                    $fullAddress = trim("{$cbranch->StreetName} {$cbranch->AreaName} {$cbranch->PinCode} {$cbranch->Landmark}");
                    similar_text(strtolower($entry['address']), strtolower($fullAddress), $percent);
                    // Check if this record is the best match so far
                    if ($percentName > 50 && $percent > $matchedPercentage) {
                        $entry['type'] = 'internal';
                        $entry['exist'] = $cbranch->id;
                    }
                }
                
                //echo $percent."---".$percentName."<br/>";
        
            }
        };

        // party_1
        if (isset($resp['party_1']) && is_array($resp['party_1'])) {
            $checkAndMarkExternal($resp['party_1']);
            $checkAndMarkInternal($resp['party_1']);
        }

        // party_2
        if (isset($resp['party_2']) && is_array($resp['party_2'])) {
            $checkAndMarkExternal($resp['party_2']);
            $checkAndMarkInternal($resp['party_2']);
        }

        // party_details array
        if (isset($resp['party_details']) && is_array($resp['party_details'])) {
            foreach ($resp['party_details'] as &$party) {
                if (is_array($party)) {
                    $checkAndMarkExternal($party);
                    $checkAndMarkInternal($party);
                }
            }
            unset($party);
        }

        // Build nonExisting list (with a key to help identify)
        $nonExisting = [];
        $existing = [];

        foreach (['party_1', 'party_2'] as $k) {
            if (isset($resp[$k]) && empty($resp[$k]['exist'])) {
                $nonExisting[] = array_merge(['key' => $k], $resp[$k] ?? []);
            }else{
               $existing[] = array_merge(['key' => $k], $resp[$k] ?? []);
            }
        }

        if (isset($resp['party_details']) && is_array($resp['party_details'])) {
            foreach ($resp['party_details'] as $idx => $p) {
                if (empty($p['exist'])) {
                    $nonExisting[] = array_merge(['key' => "party_".$idx+3], $p);
                }else{
                   $existing[] = array_merge(['key' => "party_".$idx+3], $p);
                }
            }
        }
        
        
        return response()->json(['resp' => $jsonData, 'existing' => $existing,'nonExisting' => $nonExisting], 200);
        
        //die;
    }

    public function aiDocumentAnalyser(Request $request){
        
        
        if(!env('test_ai_interepter')){
            if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                return response()->json(['error' => 'Please upload a valid file.'], 422);
            }
            
            $aiApiBaseUrl = rtrim((string) admin_setting('ai_api_url'), '/');
            $client = new Client();

            $uploadedFile = $request->file('file');
            $uploadResponse = $this->uploadFileAndGetHttpsUrl($request);
            $uploadData = $uploadResponse->getData(true);
            if (($uploadData['success'] ?? false) !== true || empty($uploadData['path'])) {
                return response()->json(['error' => $uploadData['message'] ?? 'File upload failed.'], 422);
            }

            $storedPath = $uploadData['path'];
            $fileHandle = Storage::disk('public')->readStream($storedPath);
            if ($fileHandle === false) {
                \Storage::disk('public')->delete($storedPath);
                return response()->json(['error' => 'Unable to read uploaded file.'], 422);
            }

            try {
                $response = $client->request('POST', $aiApiBaseUrl.'/mistral-ocr?mode=markdown', [
                    'multipart' => [
                        [
                            'name'     => 'file',
                            'contents' => $fileHandle
                        ],
                    ],
                ]);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $errorBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;

                $shouldRetryWithLegacyPath = $statusCode === 400
                    && (
                        stripos($errorBody, 'must be a URL') !== false
                        || stripos($errorBody, 'base64') !== false
                        || stripos($errorBody, 'starting with') !== false
                    );

                if (!$shouldRetryWithLegacyPath) {
                    throw $e;
                }

                rewind($fileHandle);
                $response = $client->request('POST', $aiApiBaseUrl.'/process-contract?mode=markdown', [
                    'multipart' => [
                        [
                            'name'     => 'file',
                            'contents' => $fileHandle
                        ],
                    ],
                ]);
            } finally {
                fclose($fileHandle);
                \Storage::disk('public')->delete($storedPath);
            }
            
            $jsonData = json_decode($response->getBody()->getContents(), true);
            
            //$jsonData = [];
            
            //$jsonData['markdown'] = "# COST Plus Housekeeping Services\n## Apollo Rajshree Hospital Index\n### SINGHAR SINGHAR\n\n**JUN 24 2021**\n\n**15:20**\n\n**TWENTY RUPEES**\n\n**INDIA NON JUDICIAL**\n\n**BULLETIN (1) OTHERWISE TAMIL NADU**\n\n**FABER SINDOORI MANAGEMENT SERVICES PRIVATE LIMITED**\n\nPottipatti Plaza, 4th Floor,\nNo. 77, Nungambakkam High Road,\nChennai - 600 034.\n\n**23 FEB 2022**\n\n**06AC 545872**\n\n**M. KAILASH CHAND**\n\nSTAMP VENDOR-LAN-11727/C/91\nSATOAPET, CHENNAI-15. 1:9840173098\n\n## HOUSEKEEPING SERVICES SUPPLEMENTARY AGREEMENT\n\nTHIS AGREEMENT, made this 22nd day of February 2022 at Chennai and shall be effective from 01st June 2022, notwithstanding the actual execution date hereof,\n\n### Between:\n\n**Apollo Rajshree Hospital Private Limited** a Company incorporated under the Companies Act 1956 (India), located at Bhanvarkuan, Sector D, Scheme No 74C, Vijay Nagar, Indore, Madhya Pradesh - 452010, hereinafter referred to as **\"ARHPL\"** (which expression shall unless repugnant to the context or meaning thereof be deemed to include its successors and assigns of the other party).\n\n**Faber Sindoori Management Services Private Limited** an existing Private Limited Company within the meaning of the Companies Act, 1956 (India) having its registered office at Pottipatti Plaza, 4th Floor, No 77, Nungambakkam High Road, Nungambakkam, Chennai - 600-034, hereinafter referred to as **\"FSMS\"** (which expression shall unless repugnant to the context or meaning thereof be deemed to include its successors and assigns of the other party).\n\n**ARHPL** and **FSMS** shall be referred to individually as **\"Party\"** and collectively as **\"Parties\"**.\n\n**Ref No: FSMS/HK/IDR/2014 F**\n\n**Page 1 of 3**\n\n# Post Plus Housekeeping Services\n## Apollo Rajshree Hospital Indore\n\n## NOW THEREFORE THIS AGREEMENT WITNESSETH AS FOLLOWS:\n\nARHPL and FSMS have agreed collectively to extend the existing Housekeeping Service Agreement as per the prevailing terms and conditions specified:\n\nReference is made to the:\n- Original Agreement Ref No: FSMS/HK/IDR/2014 dated 29th May 2014\n- The Supplementary Agreement Ref No: FSMS/HK/IDR/2014 A dated 22nd June 2015\n- The Supplementary Agreement Ref No: FSMS/HK/IDR/2014 B dated 31st May 2016\n- The Supplementary Agreement Ref No: FSMS/HK/IDR/2014 C dated 13th April 2017\n- The Supplementary Agreement Ref No: FSMS/HK/IDR/2014 D dated 31st May 2018\n- The Supplementary Agreement Ref No: FSMS/HK/IDR/2014 E dated 30th April 2019\n\nThis Agreement has been extended as mutually agreed by both parties:\n\nThe following clause is added to Clause 6 of the Agreement\n\n## 5. SERVICE CHARGES\n\n5.1 For providing the trained and qualified personnel services, ARHPL shall pay to FSMS every month towards Cost to Company (CTC) with 13% Service Charges along with applicable taxes effective from 01st June 2022.\n\n5.2 FSMS shall submit the bills for the above on or before 5th of the following month and the payment by the ARHPL shall be made within 10 days of the submission of the bills. ARHPL shall acknowledge the invoices on receipt.\n\n5.3 FSMS shall claim the annual increase for their personnel as and when the increase becomes due. No belated claim under the head \"arrears\" will be entertained by ARHPL under any circumstances.\n\n## 6. VALIDITY OF THE AGREEMENT\n\nNow, the Parties hereby mutually agree to renew the Agreement for a further period of Three (3) years. (i.e. from 01st June 2022 to 31st May 2025).\n\nAll other terms and conditions of the Agreement shall be applicable to this Supplementary Agreement, mutatis mutandis.\n\nIN WITNESS WHEREOF, the Parties have executed this Agreement the day and year first above written.\n\nSigned for and on behalf of\n\nApollo Rajshree Hospital Private Limited,\n\n**Name:** ______________________________\n\nSigned for and on behalf of\n\n**Name:** ______________________________\n\nFaber Sindoori Management Services Private Limited,\n\n**Name:** ______________________________\n\n**Name:** ______________________________\n\n**Designation:** ______________________________\n\n**Name:** ______________________________\n\n**Designation:** ______________________________\n\n**Name:** ______________________________\n\n**Designation:** ______________________________\n\n![img-0.jpeg](img-0.jpeg)\n\n# SERVICE AGREEMENT \n\nThis Service Agreement is executed at Indore on this $29^{\\text {th }}$ day of May, 2014 between APOLLO RAJSHREE HOSPITAL PRIVATE LIMITED a Company Incorporated under the Companies Act, 1956 located at Bhanvarkuan, Sector D, Scheme No 74C, Vijay Nagar, Indore, Madhya Pradesh - 452010, herein after referred (1) as \"ARHPL\" (which expression shall mean and include its successors-in-office and assigns) of the One Part\n![img-1.jpeg](img-1.jpeg)\n\n# AND \n\nFABER SINDOORI MANAGEMENT SERVICES PRIVATE LIMITED a Private Limited Company, having its registered, administrative, and operational offices at No 25, 26, Prince Towers, $7^{\\text {th }}$ Floor, College Road, Nungambakkam, Chennai 6000034 , herein after referred to as \"FSMS\" (which expression shall mean and include its successors-inoffice and assigns) of the Other Part witnesseth:-\n\nWhereas the ARHPL is a health care provider and running modern multi-specialty hospital in Indore.\n\nWhereas the FSMS is providing Housekeeping services, trained and qualified personnel and implementing and monitoring these operations.\n\nWhereas the ARHPL requires trained and qualified personnel to the various departments of its hospital in Indore and approach the FSMS for providing the same.\n\nWhereas the FSMS has been appointed to provide trained and qualified personnel to ARHPL as per this Service Agreement.\n\n## NOW THIS AGREEMENT WITNESSETH AS FOLLOWS:-\n\n## 1 APPOINTMENT\n\n1.1 ARHPL has agreed to appoint FSMS as contractor for providing trained and qualified personnel in accordance with the scope and on terms and conditions hereinafter appearing\n1.2 FSMS has agreed the above appointment and accepted to provide the trained and qualified persomel as required by the ARHPL.\n\n## 2 SCOPE OF SERVICES (FSMS)\n\n2.1 To provide the trained and qualified personnel with requisite qualification as required by the ARHPL.\n2.2 To take adequate care in selecting the qualified personnel.\n2.3 To employ necessary staffs for the required services and arrange for their continuous training.\n\n## 3 SCOPE OF SERVICES (ARHPL)\n\nTo provide necessary manpower requirement details of various departments of Apollo Rajshree Hospital Indore.\n\n# 4 OBLIGATIONS OF FSMS \\& ARHPL \n\n4.1 It shall be the duty of the contactor to ensure that their personnel conform to and observe all rules and regulation of the ARHPL and maintain standards of cleanliness, decorum, safety and discipline.\n4.2 The FSMS shall ensure that their staff members are physically fit and not suffering from any disease, contagious or otherwise. If any employee found medically unfit whilst on duty is liable to be removed from the premises.\n4.3 If at any time the ARHPL is not satisfied with the performance of any of the FSMS personnel or if any of his personnel commits any act of misconduct, indiscipline or offence, the FSMS shall remove him from deployment and the staff shall be replaced with alternate staff within reasonable time.\n4.4 The FSMS shall conform and to comply with all the statutory requirements / obligations as may be required to be satisfied under various labour enactments such as The Contract Labour (Regulation and Abolition) Act 1970, The Employees' Provident Funds And Miscellaneous Provisions Act 1952, The Employment State Insurance Act, The Minimum Wages Act, The Payment of Bonus Act, The Payment of Gratuity Act, The Payment of wages Act and Workmen Compensation Act and the Rules framed there under, wherever applicable. The Contractor shall be solely responsible for any breach or noncompliance of any provisions of aforesaid Acts or any other labour enactment not specifically mentioned herein.\n4.5 The FSMS shall furnish to the ARHPL every month the returns and challan evidencing statutory compliance and payment of statutory liabilities.\n4.6 The personnel deployed in the Hospital for performing the services are the FSMS workers and they shall have nothing to do with the ARHPL. The Personnel deployed at the Hospital for the Services shall continue to remain as FSMS employees. At no time will they be construed to have become the employees of ARHPL.\n4.7 Any statutory claim, compensation, liability, damages, demand or penalty arising as a result of the employment of service personnel by the FSMS for performing the services shall be paid and borne by the FSMS and ARHPL shall have nothing to do with the same. The FSMS shall take appropriate insurance cover to meet such claims.\n\n4.8 The FSMS hereby indemnifies and shall keep indemnified the ARHPL against all claims and demands under enactment specified above hereof and also against all claims and demands by third parties for compensation, damages, costs, expenses of any nature whatsoever which the ARHPL may sustain or be put to as a result of any act of negligence or otherwise of the FSMS personnel.\n4.9 ARHPL shall provide Uniform, Shoes, Identity Card with Photograph affixed to the FSMS personnel.\n4.10 It is a duty of FSMS to replace its personnel with in a week's time to ensure ARHPL to provide uninterrupted services to the patients.\n4.11 It is mandatory on the part of FSMS personnel to adhere to the Job Descriptions provided by the ARHPL.\n\n# 5. SERVICE CHARGES \n\n5.1 For providing the trained and qualified personnel services, ARHPL shall pay to FSMS every month towards Cost to Company (CTC) with $15 \\%$ Service Charges along with applicable taxes.\n5.2 The FSMS shall submit the bills for the above on or before $5^{\\text {th }}$ of the following month and at the payment by the ARHPL shall be made within 10 days of the submission of the bills.\n5.3 FSMS shall claim the annual increase for their personnel as and when the increase becomes due. No belated claim under the head 'arrears\" will be entertained by ARHPL under any circumstances.\n\n## 6. VALIDITY OF AGREEMENT\n\nThis agreement shall remain in force for a period of 1 year from 30/05/2014 to $31 / 05 / 2015$ and it shall be open to the parties hereto to renew the same on such terms and conditions as mutually agreed.\n\n# 7 TERMINATION \n\nNotwithstanding anything contained herein both the parties are entitled to terminate / withdraw the Service Agreement by giving 1 months' notice.\n\nIn witness whereof the ARHPL and FSMS have signed in this deed on the date, month and year first above mentioned.\n\n## WITNESSES:-\n\n1. \n\n![img-2.jpeg](img-2.jpeg)\n\nARHPL\n2.\n![img-3.jpeg](img-3.jpeg)\n\nFSMS\n\n# Annexure A \n\n## Direct Cost (CTC)\n\nThe following direct costs shall be paid by ARHPL to FSMS every month, with applicable service charge of $15 \\%$ and applicable Taxes\nA. Staff Salaries and allowances\n\nHousekeepers\nWard Attenders\nDesk Attenders\nAs per ARHPL requirement\nSupervisors\nSr Supervisors\nExecutive Housekeeper and others\nB. Statutory Cost on Above\n\nEmployee's Provident Fund - $13.61 \\%$ as per the applicable act from time to time Employee's State Insurance $-4.75 \\%$ as per the applicable act from time to time\n\n## Indirect Cost\n\nThe following indirect costs shall be paid by ARHPL to FSMS with Service Charge and Service Tax, as and when incurred:\n\n1. Cost of Uniform, Shoes, ID Cards, to be provided as per approved norms as and when incurred\n2. Reimbursement of Bonus / Ex-gratia expenses incurred by FSMS\n3. Gratuity";

            if(!empty($jsonData['markdown'])){
                
                $client1 = new Client();
                
                $response = $client1->request('POST', $aiApiBaseUrl.'/contract-risk-profile', [
                    'query' => [
                        'provider' => 'gemini', // same as ?provider=gemini
                    ],
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'contract_text' => $jsonData['markdown'],
                    ],                    
                ]);
                
                $jsonData = json_decode($response->getBody()->getContents(), true);                
            }
    
        }else{
            $body = file_get_contents(base_path('assets/supportdocs/samplerespanalysis.json'));
            // Decode original JSON
            $jsonData = json_decode($body, true);
        }
            
        return response()->json(['resp' => $jsonData], 200);
        
        //die;
    }

    public function aiDocumentChatBott(Request $request){
        
        
        if(!env('test_ai_interepter')){
            
            $client = new Client();
            
            $API_KEY = admin_setting('gemini_api_key_chatbot'); // keep your key safe in .env
            $MODEL = admin_setting('gemini_api_model_chatbot');
            $ENDPOINT = "https://generativelanguage.googleapis.com/v1beta/{$MODEL}:generateContent?key={$API_KEY}";
    
            $userInput = $request->input('prompt', '');
    
            if (empty($userInput)) {
                return response()->json(['error' => 'Prompt cannot be empty.'], 400);
            }
    
            // Prepare body as per Gemini API spec
            $body = [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => $userInput]
                        ]
                    ]
                ]
            ];
    
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($ENDPOINT, $body);
    
                if ($response->failed()) {
                    return response()->json([
                        'error' => 'Gemini API request failed.',
                        'details' => $response->json()
                    ], $response->status());
                }
    
                $data = $response->json();
    
                // Extract the generated text
                $text = '';
                if (!empty($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $text = $data['candidates'][0]['content']['parts'][0]['text'];
                } elseif (!empty($data['candidates'][0]['output'])) {
                    $text = $data['candidates'][0]['output'];
                }
    
                return response()->json([
                    'success' => true,
                    'text' => $text,
                    'raw' => $data
                ]);
    
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Request to Gemini API failed.',
                    'message' => $e->getMessage()
                ], 500);
            }
    
        }else{
            $body = file_get_contents(base_path('assets/supportdocs/sampleresp.json'));
            // Decode original JSON
            $jsonData = json_decode($body, true);
        }
            
        return response()->json(['resp' => $jsonData], 200);
        
        //die;
    }

    public function index()
    {
        $customFields = CustomFields::where('status', 1)->get();
        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();

        return view('contract::contract.createfield')->with('contractTypes', $contractTypes)->with('categorys', $categorys)->with('lists', $customFields);
    }



    public function indexParty()
    {
        $customFields = CustomFields::where('status', 1)->get();
        $categorys = Category::where('category_group', 'party')->get();
        $contractTypes = ContractType::get();

        return view('contract::contract.partyCustomField')->with('contractTypes', $contractTypes)->with('categorys', $categorys)->with('lists', $customFields);
    }



    public function list(Request $request)
    {
        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();
        $contracttype = isset($request->contracttype) ? $request->contracttype : '1';
        // Generic fields apply to every contract type, so they are listed alongside the
        // fields belonging to the selected type and stay editable from any of them.
        $customFields = CustomFields::where('status', 1)
            ->where(function ($query) use ($contracttype) {
                $query->where('contract_type', $contracttype)
                    ->orWhere(function ($generic) {
                        $generic->where('is_generic', 1)->where('sub_type', 'contract');
                    });
            })
            ->orderBy('order_id')->get();
        $currentcontractType = ContractType::where('contract_type_id', $contracttype)->first();


        return view('contract::contract.createfieldlist')->with('currentcontractType', $currentcontractType)->with('contractTypes', $contractTypes)->with('lists', $customFields)->with('categorys', $categorys);
    }

    public function indexPartyList(Request $request)
    {
        $categorys = Category::where('category_group', 'party')->get();
        $contractTypes = ContractType::get();
        $contracttype = 0;
        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();

        return view('contract::contract.partyCustomFieldlist')->with('contractTypes', $contractTypes)->with('lists', $customFields)->with('categorys', $categorys);
    }




    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required',
            'type' => 'required',
            'category' => 'required',
            'contracttype' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $customField = new CustomFields();
            $customField->category = $request->category;
            $customField->field_name = $request->label;
            $customField->field_type = $request->type;
            $customField->contract_type = isset($request->contracttype) ? $request->contracttype : 1;
            $customField->required = isset($request->required) ? $request->required : 0;
            $customField->is_generic = $request->has('is_generic') ? 1 : 0;
            $customField->field_default_value = isset($request->val) ? $request->val : null;
            $customField->save();

            $customTimeline = new CustomFieldsTimeline();
            $customTimeline->custom_field_id = $customField->id;
            $customTimeline->updated_by = 1;
            $customTimeline->action = 'created';
            $customTimeline->data = json_encode($customField);
            $customTimeline->save();


            return response()->json(['message' => 'Form submitted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }



    public function update(Request $request)
    {

        // return $request->All();
        foreach ($request->custom_fields as $field) {


            CustomFields::where('custom_field_id', $field['id'])->update([
                'field_name' => $field['name'],
                'category' => $field['group'] ? $field['group'] : 1,
                'field_type' => $field['type'],
                'field_default_value' => $field['value'],
                'required' => isset($field['required']) ? 1 : 0,
                'is_generic' => isset($field['is_generic']) ? 1 : 0,
                'order_id' => $field['order'],
                'status' => $field['status'],
            ]);
            if ($field['status'] == 0) {
                $customField = new CustomFieldsTimeline();
                $customField->custom_field_id = $field['id'];
                $customField->updated_by = 1;
                $customField->action = 'deleted';
                $customField->data = json_encode($field);
                $customField->save();
            } else {
                $customField = new CustomFieldsTimeline();
                $customField->custom_field_id = $field['id'];
                $customField->updated_by = 1;
                $customField->action = 'updated';
                $customField->data = json_encode($field);
                $customField->save();
            }
        }
        return response()->json(['message' => 'Form submitted successfully'], 200);
    }

    public function contractApprovals(Request $request, $externalReq=0)
    {

        $buttonTxt = "";

        $allowedDocType = ['pdf', 'docx'];

        $id = $request->input('contactId');
        $indexId = $request->input('indexId');
        $shortDesc = $request->input('nextActionItem' . $indexId);
        $appId = $request->input('appId');
        $desc = $request->input('nextAction' . $indexId);
        $appType = $request->input('appType');
        $appDataStatus = $request->input('appStatus');
        $appPreStatus = $request->input('appPreStatus');
        $orderval = $request->input('orderval');
        $unique_id_old = $request->input('unique_id');
        $actionBtntext = $request->input('actionBtntext');
        $skipDocument = $request->input('skipDocument');
        $signPng = $request->input('signPng');
        $signPngLoc = $request->input('signPngLoc') ?? '-';
        $signType = $request->input('signType') ?? 'custom';
        $controller = fileStorageTypeController();
        $updateHistory = false;

        //For Email
        $emailTrigger = new ContractNotificationController();
        $senattment = [];
        $senattment['filename'] = [];
        $senattment['filurl'] = [];


        $contracts = Contract::select('*')->where('id', $id)->get();
        
        if($externalReq == 0){
            $contracts = $this->availableContracts($contracts, true);
    
            if (count($contracts) == 0) {
                return response()->json(['message' => 'Invalid Contract'], 200);
            }
        }

        if ($request->input('approvalInps') && $request->input('approvalInps') !== null) {
            Contract::where(['id' => $id])->update($request->input('approvalInps'));
        }
        if ($request->input('customFields') && $request->input('customFields') !== null) {
            foreach ($request->input('customFields') as $keyId => $customField) {
                CustomFieldsData::create([
                    'custom_field_id' => $keyId,
                    'custom_field_group' => 'contracts',
                    'custom_field_value' => $customField,
                    'custom_field_group_id' => $id
                ]);
            }
        }


        $filesData = "";
        $filesDataName = "";

        $filesSupport = [];

        $contracts = Contract::select('*')->where('id', $id)->get();

        $contracts = $contracts[0];

        $pdfOnlyAllowed = 'For Final Documents Pdf Only Allowed';

        $pdfOrDocOnlyAllowed = 'For Contract Documents Docx/Pdf Only Allowed';

        $skipFileMissingValidation = ['Negotiation', 'Approval'];

        if (strtolower($contracts->substatus) == 'pending approval' && $appDataStatus == 'Signing') {
            $skipFileMissingValidation[] = 'Signing';
        }


        $contractFilePresent = 0;

        if ($request->file('photos')) {
            $files = $request->file('photos');
            $filesType = $request->input('fileType');

            if (fileStorageType() == "Local" && !in_array($appDataStatus,  $skipFileMissingValidation)) {
                foreach ($files as $file) {

                    if ($filesType[$file->getClientOriginalName()] == 'contract') {
                        $contractFilePresent = 1;
                    }
                }


                if ($contractFilePresent == 0 && $skipDocument == 'false') {
                    return response()->json(['message' => 'Please Upload Contract Document'], 200);
                }
            }

            foreach ($files as $file) {

                if ($filesType[$file->getClientOriginalName()] == 'contract') {

                    if ($contracts->signing_date && $appDataStatus == 'Signing') {
                        $allowedDocType = ['pdf'];
                        if (!in_array(strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)), $allowedDocType)) {
                            return response()->json(['message' => $pdfOnlyAllowed], 200);
                        }
                    } else {
                        if (!in_array(strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)), $allowedDocType)) {
                            return response()->json(['message' => $pdfOrDocOnlyAllowed], 200);
                        }
                    }
                    // Add the file object to the filesData array
                    $filesData = $controller->storeFile($file, 'approvals', $id);
                    if(!$filesData){
                        return response()->json(['message' => 'Storage Token Expired'], 200);
                    }
                    $filesDataName = file_name($file);

                    Contract::where(['id' => $id])->update([
                        'contract_attachment_filename' => $filesDataName,
                        'contract_attachment' => $filesData
                    ]);
                    
                    $updateHistory = true;

                    //For Mail
                    $senattment['filename'][] = $filesDataName;
                    $senattment['filurl'][] = $filesData;

                } else {
                    $fileObject = new stdClass();
                    $fileObject->path = $controller->storeFile($file, 'approvals', $id);
                    $fileObject->name = file_name($file);
                    $filesSupport[] = $fileObject;

                    $senattment['filename'][] = file_name($file);
                    $senattment['filurl'][] = $fileObject->path;
                }
            }
        } else {

            if (fileStorageType() == "Local" && !in_array(strtolower(pathinfo($contracts->contract_attachment_filename, PATHINFO_EXTENSION)), $allowedDocType) && $contracts->signing_date) {
                return response()->json(['message' => $pdfOrDocOnlyAllowed], 200);
            }
            if (fileStorageType() == "Local" && !in_array($appDataStatus,  $skipFileMissingValidation) && $skipDocument == 'false') {
                return response()->json(['message' => 'Please Upload Contract Document'], 200);
            }
        }


        if ($appDataStatus == 'review' || $appDataStatus == 'Review') {
            $buttonTxt = "Reviewed on";
        } elseif ($appDataStatus == 'approval' || $appDataStatus == 'Approval') {
            $buttonTxt = "Approval on";
        } elseif ($appDataStatus == 'approved' || $appDataStatus == 'Approved') {
            $buttonTxt = "Approved on";
        } elseif ($appDataStatus == 'signing' || $appDataStatus == 'Signing') {
            $buttonTxt = "Signed on";
        }
        if ($actionBtntext == 'Send to Next Approval') {
            $buttonTxt = "Approved on";
        }


        if ($appType == 'rejected') {
            $buttonTxt = "Rejected on";
        }

        $currentApproval = ApprovalContracts::find($appId);

        //For Revoke Write access
        $currentUserEmail = json_decode(decryptString($currentApproval->username, 'username'))->email;

        //For Add Editor Access Next Approver
        $nextAprroverEmail = "";
        
        $updatedUser = ['email' => Helpers::userInfo()->email ?? 'External', 'name' => Helpers::userInfo()->FirstName ?? 'User'];
        if($externalReq > 0){
            $usersExternal = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $externalReq)->get();
            $updatedUser = ['email' => $usersExternal[0]->Email, 'name' => $usersExternal[0]->FirstName];
        }

        ApprovalContracts::where('id', $appId)->update([
            'unique_id' => $unique_id_old,
            'orderval' => $orderval,
            'next_action_item' => encryptString($shortDesc, 'next_action_item'),
            'next_action_description' => encryptString($desc, 'next_action_description'),
            'button_text' => ($contracts->signing_date && decryptString($contracts->contract_mode, 'contract_mode') == 'old' && $buttonTxt == "Signed on") ? 'Approved On' : $buttonTxt,
            'approval_status' => encryptStringx($appType, 'approval_contracts.approval_status'),
            'attachments' => $filesData,
            'attachments_filename' => $filesDataName,
            'attachments_support' => $filesSupport,
            'signed_png' => $signPng,
            'signed_type' => $signType,
            'updated_on' => date('Y-m-d H:i:s'),
            'updated_by' => json_encode($updatedUser)
        ]);

        // ---- New parent-grouped approval flow ----
        // Rows created by createApprovalRows carry a stage_name; such contracts are
        // advanced by the routing engine (advanceGroupedApproval) rather than the
        // legacy count-based engine below. Any contract file posted above has already
        // overwritten Contract.contract_attachment (the "previous agreement" update),
        // with the prior version retained in the ContractHistory snapshot here.
        if (!empty($currentApproval->stage_name)) {
            ApprovalContracts::where('id', $appId)->update(['flag' => 0]);
            $currentApproval->refresh();

            $action = ($appType == 'rejected') ? 'reject' : 'approve';
            $this->advanceGroupedApproval($contracts, $currentApproval, $action);

            // Resolve the now-active approver(s) for notification + write access.
            $activeRows = ApprovalContracts::where('contract_id', $id)->where('flag', 1)->get();
            $emails = [];
            foreach ($activeRows as $r) {
                try {
                    $u = json_decode(decryptString($r->username, 'username'), true);
                    if (!empty($u['email'])) $emails[] = $u['email'];
                } catch (\Throwable $e) {
                }
            }
            $emails = array_values(array_unique(array_filter($emails)));
            $nextAprroverEmail = count($emails) === 1 ? $emails[0] : $emails;

            $contracts_Final = Contract::select('contract_attachment')->where('id', $id)->first();
            $contract_attachment = $contracts_Final->contract_attachment;
            if ($filesDataName == "") {
                $contract_attachment_data = $controller->copyFile($id, $contracts_Final->contract_attachment) ?? [];
                if (count($contract_attachment_data) == 2) {
                    Contract::where(['id' => $id])->update([
                        'contract_attachment_filename' => $contract_attachment_data[0],
                        'contract_attachment' => $contract_attachment_data[1]
                    ]);
                    $contract_attachment = $contract_attachment_data[1];
                }
            }

            if (!empty($nextAprroverEmail)) {
                $controller->changePermission($contract_attachment, $currentUserEmail, $nextAprroverEmail);
                $emailTrigger->sendEmail($id, $desc, $shortDesc, $nextAprroverEmail, $appDataStatus, $senattment['filename'], $senattment['filurl'], 'notiMail');
            }

            // History snapshot (prior agreement retained here).
            $contracthisHistory = Contract::where('id', $id)->first()->toArray();
            if ($externalReq > 0) {
                $contracthisHistory['created_by'] = $externalReq;
            }
            $contractHistoryCreated = ContractHistory::create($contracthisHistory, ['except' => ['created_at', 'updated_at', 'contract_party_list', 'contract_name']]);
            $contractPartyHistory = ContractPartyData::where('custom_field_group_id', $id)->get()->toArray();
            $fianlContractPartyHistory = [];
            foreach ($contractPartyHistory as $cph) {
                $cph['history_id'] = $contractHistoryCreated->id;
                $fianlContractPartyHistory[] = $cph;
            }
            if (!empty($fianlContractPartyHistory)) {
                ContractPartyDataHistory::insert($fianlContractPartyHistory);
            }

            // If pre-approval stage is now complete, signal frontend to redirect to timeline.
            $responseData = ['message' => 'successful!'];
            if ($contracts->preapproval_stage === null) {
                $responseData['redirect_to'] = route('viewContract', ['id' => $id, 'tab' => 'timeline']);
            }
            return response()->json($responseData, 200);
        }

        $end_date_of_contract = $contracts->contract_end_date;
        $cur_date = date('Y-m-d');
        $contract_end_type = decryptString($contracts->end_contract_type, 'end_contract_type');
        $contract_sub_status = 'active';
        if ($buttonTxt == "Signed on") {

            if ($contract_end_type == 'onetimeContract') {
                $contract_status = 'executed';
                if ($end_date_of_contract != '') {
                    if (strtotime($end_date_of_contract) > strtotime($cur_date)) {
                        $contract_sub_status = 'active';
                    } elseif (strtotime($cur_date) > strtotime($end_date_of_contract)) {
                        $contract_sub_status = 'completed';
                    }
                } else {
                    $contract_sub_status = 'active';
                }
            } elseif ($contract_end_type == 'evergreen') {
                $contract_status = 'executed';
                $contract_sub_status = 'active';
            } elseif ($contract_end_type == 'fixedTerm') {
                $contract_status = 'executed';
                if ($end_date_of_contract != '') {
                    if (strtotime($end_date_of_contract) > strtotime($cur_date)) {
                        $contract_sub_status = 'active';
                    } elseif (strtotime($cur_date) > strtotime($end_date_of_contract)) {
                        $contract_sub_status = 'expired';
                    }
                } else {
                    $contract_sub_status = 'active';
                }
            } else {
                $contract_status = 'executed';
                $contract_sub_status = 'active';
            }

            $contractMode = $contracts->contract_mode;

            $updateSigningArray = [
                'contract_status' => $contract_status,
                'substatus' => $contract_sub_status
            ];

            //Contract::where(['id' => $id])->update($updateSigningArray);

            if ($contracts->parentcontract != 0) {
                $parentContract = Contract::where(['id' => $contracts->parentcontract])->first();
                Contract::where(['id' => $contracts->parentcontract])->update([
                    'contract_status' => 'executed',
                    'substatus' => $parentContract->renewtype == 'renew' ? 'renewed' : 'amended',

                ]);
                $updateHistory = true;
            }
        }

        $approvalsDataArr = ApprovalContracts::select('id', 'username', 'status', 'previous_status', 'contract_id', 'next_action_item', 'next_action_description', 'button_text', 'attachments', 'approval_status', 'updated_at', 'created_at', 'orderval', 'unique_id', 'flag')
            ->where('contract_id', $id)
            ->where('flag', '<>', -1)
            ->get()
            ->map(function ($task) {
                $task->username = decryptString($task->username, 'username');
                $task->status = decryptString($task->status, 'status');
                $task->previous_status = decryptString($task->previous_status, 'previous_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                return $task;
            })
            ->toArray();


        $owner = $contracts->owner;
        $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $owner)->get();


        $approvalsArr = json_decode($contracts->rules_id);
        $appArr = json_decode($approvalsArr[0]->approver);
        $approvalTypeContract = $approvalsArr[0]->approval_type ?? '';
        $sendForSign = 0;

        if ($actionBtntext !== '' && str_contains($actionBtntext, 'review') && $appType != 'rejected') {
            $appStatus = 'review';
            $nextAprroverEmail = $this->approversInsertScript($appArr, $id, $unique_id_old, $appId, $appStatus);
            if ($nextAprroverEmail == '' && $approvalTypeContract != 'parallel') {
                $appStatus = 'Negotiation';
            }else{
               // count individual approvers across all groups
               $flatApprovers = $this->flattenApproversArray($appArr);
               $totalApprovers = count($flatApprovers);
               $approvedUsers = 0;
               foreach($approvalsDataArr as $appDataArr){
                   if($appDataArr['button_text'] == 'Reviewed on' && $appDataArr['approval_status'] == 'approved' && $appDataArr['flag'] == 1){
                       $approvedUsers++;
                   }
               }
               if($approvedUsers == $totalApprovers){
                   $appStatus = 'Negotiation';
               }
            }
        } elseif ($actionBtntext !== '' && str_contains($actionBtntext, 'Negotiation') && $appType != 'rejected') {
            $appStatus = 'Negotiation';
        } elseif ($actionBtntext !== '' && str_contains($actionBtntext, 'Approval') && $appType != 'rejected') {

            $appStatus = 'Approval';
            $nextAprroverEmail = $this->approversInsertScript($appArr, $id, $unique_id_old, $appId, $appStatus);
            if ($nextAprroverEmail == '' && $approvalTypeContract != 'parallel') {
                $appStatus = 'Signing';
                $sendForSign = 1;
            } else {
               // count individual approvers across all groups
               $flatApprovers = $this->flattenApproversArray($appArr);
               $totalApprovers = count($flatApprovers);
               $approvedUsers = 0;
               foreach($approvalsDataArr as $appDataArr){
                   if($appDataArr['button_text'] == 'Approval on' && $appDataArr['approval_status'] == 'approved' && $appDataArr['flag'] == 1){
                       $approvedUsers++;
                   }
               }
               if($approvedUsers == $totalApprovers){
                   $appStatus = 'Signing';
                   $sendForSign = 1;
               }
        }
        } elseif ($actionBtntext !== '' && str_contains($actionBtntext, 'Signing') && $appType != 'rejected') {

            $appStatus = 'Signing';
        } elseif ($actionBtntext !== '' && str_contains($actionBtntext, 'To Sign') && $appType != 'rejected') {

            $appStatus = 'Signing';
        } else {
            $appStatus = '';
        }
        
        

        if ($appType == 'rejected') {

            ApprovalContracts::where(['contract_id' => $id, 'status' => $appDataStatus])->update([
                'unique_id' => $unique_id_old,
                'orderval' => $orderval,
                'next_action_item' => encryptString($shortDesc, 'next_action_item'),
                'next_action_description' => encryptString($desc, 'next_action_description'),
                'approval_status' => encryptStringx($appType, 'approval_contracts.approval_status'),
                'button_text' => $buttonTxt,
                'attachments' => $filesData,
                'attachments_filename' => $filesDataName,
                'attachments_support' => $filesSupport,
                'updated_on' => date('Y-m-d H:i:s'),
                'updated_by' => json_encode(['email' => Helpers::userInfo()->email ?? 'User', 'name' => Helpers::userInfo()->FirstName ?? 'Inactive'])
            ]);
            
            if($approvalTypeContract != 'parallel'){
                ApprovalContracts::where('unique_id', $unique_id_old)->update([
                    'flag' => 0,
                ]);
            }else{
                ApprovalContracts::where('id', $appId)->update([
                    'flag' => 0,
                ]);
            }


            if (isset($users[0])) {
                if ($appPreStatus == 'Review') {
                    $prevStatus = 'Draft';
                } elseif ($appPreStatus == 'Approval') {
                    $prevStatus = 'Review';
                    if(decryptString($contracts->contract_mode, 'contract_mode')  == 'old'){
                        $prevStatus = 'Approval';
                    }
                } else {
                    $prevStatus = 'Draft';
                        if ($appPreStatus == 'Negotiation') {
                      
                            $prevStatus = 'Draft';
                            if(decryptString($contracts->contract_mode, 'contract_mode')  == 'old'){
                                $prevStatus = 'Approval';
                            }
                    }
                }
                
                if($prevStatus != 'Approval'){
                    $username = $users[0]->Email;
                    $randNo = rand(0, 99999);
                    $orderval = 1;
                    $unique_id_new = $id . $randNo;
    
                    $nextAprroverEmail = $username;
    
    
    
    
                    ApprovalContracts::create([
                        'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                        'unique_id' => $unique_id_new,
                        'orderval' => $orderval,
                        'previous_status' => encryptString('Draft', 'previous_status'),
                        'status' => encryptString('Draft', 'status'),
                        'contract_id' => $id,
                        'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                        'flag' => '1',
                    ]);
                    Contract::where(['id' => $id])->update([
                        'contract_status' => 'Draft',
                        'substatus' => 'Under Revision'
                    ]);
                    $updateHistory = true;
                }else{
                    if (decryptString($contracts->contract_mode, 'contract_mode')  == 'old') {
                        $statusPreApprvr = 'Negotiation';
                        $statusApprvr = 'Approval';
                        $subStatusApprvr = 'Pending Approval';
                        $randNo = rand(0, 99999);
                        $unique_id_new_leg = $id . $randNo;

                        // support grouped approvers (groups with `approvers`) or legacy flat list
                        $isGrouped = is_array($appArr) && isset($appArr[0]) && isset($appArr[0]->approvers);

                        $ord = 0;
                        if ($isGrouped) {
                            foreach ($appArr as $group) {
                                $groupData = is_array($group) ? $group : (array)$group;
                                $groupDynamicApproverEnabled = (int)($groupData['dynamic_approver_enabled'] ?? 0) === 1 ? 1 : 0;
                                $groupApproversRaw = $groupData['approvers'] ?? [];
                                $groupApprovers = is_array($groupApproversRaw) ? $groupApproversRaw : (json_decode((string)$groupApproversRaw, true) ?: []);
                                if (isset($groupData['approval_type']) && strtolower((string)$groupData['approval_type']) == 'parallel') {
                                    if ($nextAprroverEmail == "") $nextAprroverEmail = [];
                                    foreach ($groupApprovers as $ap) {
                                        $approver_id = $ap->id ?? null;
                                        if (!$approver_id) continue;
                                        $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                                        if (!isset($users[0])) continue;
                                        ApprovalContracts::create([
                                            'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                            'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                            'status' => encryptString($statusApprvr, 'status'),
                                            'contract_id' => $id,
                                            'orderval' => $ord,
                                            'unique_id' => $unique_id_new_leg,
                                            'flag' => 1,
                                            'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                            'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                        ]);
                                        $nextAprroverEmail[] = $users[0]->Email;
                                        $ord++;
                                    }
                                    // parallel group: do not break, but we processed this group
                                    break;
                                } else {
                                    // sequential group: create only first approver
                                    if (count($groupApprovers) > 0) {
                                        $ap = $groupApprovers[0];
                                        $approver_id = $ap->id ?? null;
                                        if ($approver_id) {
                                            $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                                            if (isset($users[0])) {
                                                ApprovalContracts::create([
                                                    'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                                    'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                                    'status' => encryptString($statusApprvr, 'status'),
                                                    'contract_id' => $id,
                                                    'orderval' => $ord,
                                                    'unique_id' => $unique_id_new_leg,
                                                    'flag' => 1,
                                                    'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                                    'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                                ]);
                                                $nextAprroverEmail = $users[0]->Email;
                                                $ord++;
                                                break; // only first sequential approver activated now
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            foreach ($appArr as $key => $appVal) {
                                $approver_id = $appVal->id;
                                $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                                ApprovalContracts::create([
                                    'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                    'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                    'status' => encryptString($statusApprvr, 'status'),
                                    'contract_id' => $id,
                                    'orderval' => $key,
                                    'unique_id' => $unique_id_new_leg,
                                    'flag' => 1,
                                    'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                ]);

                                $emailTrigger->sendEmail($id, $desc, $shortDesc, $users[0]->Email, $appDataStatus, $senattment['filename'],  $senattment['filurl'], 'notiMail');

                                if ($approvalTypeContract == 'sequential'){
                                    $nextAprroverEmail = $users[0]->Email;
                                    break;
                                }else{
                                    if($nextAprroverEmail == ""){
                                        $nextAprroverEmail = [];
                                    }
                                    $nextAprroverEmail[] = $users[0]->Email;
                                }
                            }
                        }

                        Contract::where(['id' => $id])->update([
                            'contract_status' => $statusApprvr,
                            'substatus' => $subStatusApprvr
                        ]);
                        $updateHistory = true;
                    }                                        
                }

            }
        }
        

        if ($approvalsDataArr[count($approvalsDataArr) - 1]['approval_status'] == 'approved') {
            if ($appStatus == 'Negotiation') {
                if (isset($users[0])) {

                    $username = $users[0]->Email;
                    $randNo = rand(0, 99999);
                    $orderval = 1;
                    $unique_id_new = $id . $randNo;

                    $nextAprroverEmail = $username;

                    ApprovalContracts::create([
                        'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                        'unique_id' => $unique_id_new,
                        'orderval' => $orderval,
                        'previous_status' => encryptString('review', 'previous_status'),
                        'status' => encryptString($appStatus, 'status'),
                        'contract_id' => $id,
                        'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                        'flag' => '1',
                    ]);

                    if($approvalTypeContract != 'parallel'){
                        ApprovalContracts::where('unique_id', $unique_id_old)->update([
                            'flag' => 0,
                        ]);
                    }else{
                        // collect emails of all approvers (supports grouped and flat structures)
                        $approverIds = $this->collectApproverIdsFromJson($approvalsArr[0]->approver);
                        $currentUserEmail = AddUsers::select(decrypt_data('Email','AddUsers'))->whereIn('id', $approverIds)->get()->pluck('Email')->toArray();
                        ApprovalContracts::where('id', $appId)->update([
                            'flag' => 0,
                        ]);
                    }

                    Contract::where(['id' => $id])->update([
                        'contract_status' => 'Negotiation',
                        'substatus' => 'Under Process'
                    ]);
                    $updateHistory = true;

                }
            } elseif ($appStatus == 'Signing' || $appStatus == 'Approved') {
                
                $signatory = $contracts->signatory;

                $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $signatory)->get();

                if (isset($users[0])) {

                    $username = $users[0]->Email;
                    $randNo = rand(0, 99999);
                    $orderval = 1;
                    $unique_id_new = $id . $randNo;

                    $nextAprroverEmail = $username;

                    ApprovalContracts::create([
                        'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                        'unique_id' => $unique_id_new,
                        'orderval' => 0,
                        'previous_status' => encryptString('Approval', 'previous_status'),
                        'status' => encryptString($appStatus, 'status'),
                        'contract_id' => $id,
                        'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                        'flag' => !$contracts->signing_date ? '1' : '0',
                        'approval_type_main' => 'sequential',
                        'approval_type_row' => 'sequential',
                        'approver_type_row' => 'signatory',
                        'created_by' => json_encode($updatedUser)
                    ]);

                    $emailTrigger->sendEmail($id, $desc, $shortDesc, $username, $appDataStatus, $senattment['filename'],  $senattment['filurl'], 'notiMail');
                }

                if($approvalTypeContract != 'parallel'){
                    ApprovalContracts::where('unique_id', $unique_id_old)->update([
                        'flag' => 0,
                    ]);
                }else{
                    ApprovalContracts::where('id', $appId)->update([
                        'flag' => 0,
                    ]);
                }

                $contract_status_ = 'Signing';
                $contract_sub_status_ = 'Approved';

                if ($contracts->signing_date) {
                    $contract_status_ = 'executed';
                    $contract_sub_status_ = 'active';
                    $appDataStatus = $contract_status_;
                }

                $skipSignProcess = 0;

                if (!$signPng || $contractFilePresent == 1) {
                    $skipSignProcess = 1;
                }



                $updateSigningArray = [];

                if ($contract_sub_status_ == 'active' && $contract_status_ == 'executed' && $skipSignProcess == 0) {
                    //Check Counter Parties 
                    $counterParties = $contracts->contractPartyList->all();

                    $partiesPos = [];
                    if($signPng){
                        $approvalsSigned = ApprovalContracts::select('id', 'username', 'status', 'previous_status', 'contract_id', 'next_action_item', 'next_action_description', 'button_text', 'attachments', 'approval_status', 'updated_at', 'created_at', 'orderval', 'unique_id', 'flag')
                                        ->where('id', $appId)
                                        ->first();
                        
                        $currentSignerEmail = json_decode(decryptString($approvalsSigned->username, 'username'))->email;
                        $currentSignerName = json_decode(decryptString($approvalsSigned->username, 'username'))->name;
                        $this->crudUserActionLog($id, 'approval', 'internal-signed', $appId, 1, $currentSignerEmail, false, $currentSignerName,  $signPngLoc);
                    }

                    $externalParties = [];
                    $externalPartiesCount = 0;
                    $internalPartiesCount = 0;
                    foreach ($counterParties as $parti) {
                        if ($parti->contract_party_type == 'External' && $parti->contract_party_exe_id == !null) {
                            $repDetails = $parti->partyDetailsEx->repDetails ?? null;
                            if ($repDetails && count($repDetails) > 0) {
                                $externalPartiesCount++;
                                foreach ($repDetails as $rep) {
                                    $externalParties[] = [
                                        'email' => $rep->representative_email,
                                        'name' => decryptString($parti->partyDetailsEx->company_name, 'company_name'),
                                        'type' => 'external'
                                    ];
                                    break;
                                }
                            } else{
                                $externalPartiesCount++;
                                $externalParties[] = [
                                    'email' => $parti->partyDetailsEx->company_email,
                                    'name' => decryptString($parti->partyDetailsEx->company_name, 'company_name'),
                                    'type' => 'external'
                                ];
                            }
                            $partiesPos[] = 'external';
                        }

                        if ($parti->contract_party_type == 'Internal') {
                            $partiesPos[] = 'internal';
                            $internalPartiesCount++;
                        }
                        if ($parti->contract_party_type == 'Intergroup') {
                            $partiesPos[] = 'intergroup';
                            if ($parti->contract_party_location_id == !null) {
                                $externalPartiesCount++;
                                $loc_id__ = $parti->contract_party_location_id;
                                $branchHeadMails = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))
                                    //->where(decrypt_datas('Role', 'AddUsers'), 'Branch Head')
                                    ->whereRaw("FIND_IN_SET($loc_id__, branchhead)")
                                    ->first();
                                $externalParties[] = [
                                    'email' => $branchHeadMails->Email ?? '',
                                    'name' => $branchHeadMails->FirstName ?? '',
                                    'type' => 'intergroup'
                                ];
                            }
                        }
                    }

                    $checkExternalPartySigned = ApprovalContracts::select('id', 'username', 'status', 'previous_status', 'contract_id', 'next_action_item', 'next_action_description', 'button_text', 'attachments', 'approval_status', 'updated_at', 'created_at', 'orderval', 'unique_id', 'flag')
                        ->where('contract_id', $id)
                        ->where('button_text', 'external')
                        ->get();
                        

                    if ($externalPartiesCount > 0 && $externalPartiesCount != count($checkExternalPartySigned)) {
                        $orderval = 1;
                        foreach ($externalParties as $exparty) {
                            $randNo = rand(0, 99999);
                            $unique_id_loop = $id . $randNo;
                            $approvalRow = ApprovalContracts::create([
                                'username' => encryptString(json_encode(['email' => $exparty['email'], 'name' => $exparty['name']]), 'username'),
                                'unique_id' => $unique_id_loop,
                                'orderval' => $orderval,
                                'previous_status' => encryptString('iSigned', 'previous_status'),
                                'button_text' => 'external',
                                'status' => encryptString('Signing', 'status'),
                                'contract_id' => $id,
                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                'flag' => '-1',
                                'signed_type' => $signType,
                                'created_by' => json_encode($updatedUser)
                            ]);
                            $orderval++;
                            $this->crudUserActionLog($id, 'approval', 'ex-signing-email', $approvalRow->id, 0, $exparty['email'], false, $exparty['name']);
                            $ExternalMailSent = $emailTrigger->sendEmail($id, '', '', $exparty, $appDataStatus, $senattment['filename'],  $senattment['filurl'], 'externalApproval');
                        }
                    }

                    $partySigned = 0;

                    foreach ($checkExternalPartySigned as $signedEx) {
                        if ($signedEx->flag == 0 && $signedEx->signed_png !== null) {
                            $partySigned++;
                        }
                    }

                    if ($partySigned == 0) {
                        $contract_status_ = 'Signing';
                        $contract_sub_status_ = 'External';
                    }

                    if ($partySigned < $externalPartiesCount) {
                        $contract_status_ = 'Signing';
                        $contract_sub_status_ = 'External Partial';
                    }


                    if ($partySigned == $externalPartiesCount) {
                        $contract_status_ = 'executed';
                        $contract_sub_status_ = 'active';
                    }
                }

                if ($contract_sub_status_ == 'active' && $contract_status_ == 'executed' && $skipSignProcess == 1) {
                    
                    $this->crudUserActionLog($id, 'contract', 'all-signed', 0, 0, null);

                    if (fileStorageType() != "Local" && strtolower(pathinfo($contracts->contract_attachment_filename, PATHINFO_EXTENSION)) == 'docx') {
                        
                        $file_name = 'doc_' . strtotime(date('y-m-d h:i:s')) . '.docx';

                        $contentDocx = $controller->downloadUrl($contracts->contract_attachment, $file_name);

                        $file_path = 'contracts/tempDocs/';

                        $filePath = Storage::disk('local')->put($file_path . $file_name, $contentDocx);

                        $storedWordFile = base_path() . '/storage/app/' . $file_path . $file_name;

                        $unlinkFiles = $file_path . $file_name;

                        $htmlDoc = $this->convertWordToHtmlBuffer($storedWordFile);

                        $pdf = \PDF::loadView("contract::contract.signedPdf", ['htmlDoc' => $htmlDoc]);

                        $pdf->setPaper('A4', 'portrait');

                        $pdf->render();

                        $output = $pdf->output();

                        $generatePdfPath = $controller->get_file_path($contracts->id);

                        $generatedPdfDocumentFinalName = 'executed_contract_' . strtotime(date('d-m-y h:i:s')) . '.pdf';

                        $filePath = Storage::disk('local')->put($file_path . $generatedPdfDocumentFinalName, $output);

                        $storedWordFile = base_path() . '/storage/app/' . $file_path . $generatedPdfDocumentFinalName;

                        $generatedPdfDocumentFinal = $controller->storeContent(base_path() . '/storage/app/' . $file_path . $generatedPdfDocumentFinalName, $generatePdfPath, $generatedPdfDocumentFinalName);

                        if (strpos(strtolower($generatedPdfDocumentFinal), "error") !== false) {
                            return response()->json(['success' => false, 'message' => 'Pdf Not Generated Please Contact Admin'], 200);
                        }
                        $updateSigningArray['contract_attachment_filename'] = $generatedPdfDocumentFinalName;
                        $updateSigningArray['contract_attachment'] = $generatedPdfDocumentFinal;
                        $filesDataName = $generatedPdfDocumentFinalName;
                    }
                }
                

                if (strtotime($cur_date) > strtotime($end_date_of_contract) && $contract_sub_status_ == 'active') {
                    if( $contract_sub_status == 'active'){
                        if($contract_end_type == 'onetimeContract'){
                            $contract_sub_status = 'completed';
                        }
                        if($contract_end_type == 'fixedTerm'){
                            $contract_sub_status = 'expired';
                        }
                    }
                    
                    $contract_sub_status_ = $contract_sub_status;
                } 
                $updateSigningArray['contract_status'] = $contract_status_;
                $updateSigningArray['substatus'] = $contract_sub_status_;
                Contract::where(['id' => $id])->update($updateSigningArray);
                $updateHistory = true;

                if($nextAprroverEmail == ""){
                    $signatory = $contracts->signatory;
                
                    $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $signatory)->get();
                
                    if (isset($users[0])) {
                
                        $username = $users[0]->Email;
                        $nextAprroverEmail = $username;
                    }
                }                
            } else {

                if($nextAprroverEmail == ""){
                    $signatory = $contracts->signatory;
    
                    $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $signatory)->get();
    
                    if (isset($users[0])) {
    
                        $username = $users[0]->Email;
                        $nextAprroverEmail = $username;
                    }
                }
            }
        }



        $contracts_Final = Contract::select('contract_attachment')->where('id', $id)->first();
        $contract_attachment = $contracts_Final->contract_attachment;
        if ($filesDataName == "") {

            $contract_attachment_data = $controller->copyFile($id, $contracts_Final->contract_attachment) ?? [];

            if (count($contract_attachment_data) == 2) {
                Contract::where(['id' => $id])->update([
                    'contract_attachment_filename' => $contract_attachment_data[0],
                    'contract_attachment' => $contract_attachment_data[1]
                ]);
                $contract_attachment = $contract_attachment_data[1];
                
                $updateHistory = true;
                
                //On Copy of Documents give access to approvers
                $approvers = json_decode($contracts_Final->rules_id);
                if (isset($approvers[0]) && $approvers[0]->approver) {
                    $approverIds = $this->collectApproverIdsFromJson($approvers[0]->approver);
                    $approverIds[] = $contracts_Final->owner;
                    $approverIds[] = $contracts_Final->signatory;
                    $emailApprovers = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))
                        ->whereIn('id', $approverIds)->get()->pluck('Email')->toArray();
                    if(!is_array($currentUserEmail) && $currentUserEmail != ""){
                        $emailApprovers[] = $currentUserEmail;
                    }elseif(is_array($currentUserEmail)){
                        $emailApprovers = array_unique(array_merge($currentUserEmail, $emailApprovers));
                    }
                    $currentUserEmail = $emailApprovers;
                }                
            }
        }

        //Only Approver There
        if ($nextAprroverEmail != "") {
            $toUser = $nextAprroverEmail;
            
            //For Mail Functionality
            $controller->changePermission($contract_attachment, $currentUserEmail, $nextAprroverEmail);
            $MailSent = $emailTrigger->sendEmail($id, $desc, $shortDesc, $toUser, $appDataStatus, $senattment['filename'],  $senattment['filurl'], 'notiMail');
        }
        
        if($updateHistory){
            $contracthisHistory = Contract::where('id', $id)->first()->toArray();
            if($externalReq > 0 ){
                $contracthisHistory['created_by'] = $externalReq;
            }
            $contractHistoryCreated = ContractHistory::create($contracthisHistory, ['except' => ['created_at', 'updated_at', 'contract_party_list', 'contract_name']]);
            $contractPartyHistory = ContractPartyData::where('custom_field_group_id', $id)->get()->toArray();
            
            $fianlContractPartyHistory = [];
            if(!empty($contractPartyHistory)){
                foreach($contractPartyHistory as $cph){
                    $cph['history_id'] = $contractHistoryCreated->id;
                    $fianlContractPartyHistory[] = $cph;
                }
            }
            if(!empty($fianlContractPartyHistory)){
                $contractPartyHistoryCreated = ContractPartyDataHistory::insert($fianlContractPartyHistory); 
            }
        }

        return response()->json(['message' => 'successful!'], 200);
    }

    public function resendExternalApprovalMail(Request $request)
    {
        $linkId = $request->input('linkid');
        $exMail = $request->input('exMail');
        $today = date('Y-m-d');
        $validForMail = true;
        $resendEmail = true;
        $chekExternalUser = ExternalTempUser::select('*')
            ->where('email', $exMail)
            ->where('contract_id', $linkId)
            // ->where('is_active', 1)
            // ->whereDate('accessExpiryDate', '>', $today)
            ->first();

        if ($chekExternalUser) {
            if($chekExternalUser->is_active != 1){
                $message = "Alreay Signed";
                $messageType = false;
                $validForMail = $messageType;
            }            
            if($chekExternalUser->accessExpiryDate < $today){
                $message = "Link Expired Please Contact Administrtor";
                $messageType = false;
                $validForMail = $messageType;
            }
            
        } else {
            if($exMail != ""){
                $messageType = true;
                $message = "Email ID Present";
            }else{
                $message = "Representative Email ID Not Present In Party Info";
                $messageType = false;
            }
            $resendEmail = false;
            $validForMail = $messageType;
        }
        

        if(!$resendEmail){
            $exMail = ['email'=>$exMail, 'name'=>""];
        }

        if($validForMail){
            $emailTrigger = new ContractNotificationController();
            $ExternalMailSent = $emailTrigger->sendEmail($linkId, $chekExternalUser, '', $exMail, 'Signing', '',  [], $resendEmail ? 'reSendexternalApproval' : 'externalApproval');
            if ($ExternalMailSent) {
                $message = "Email Sent Successfully";
                $messageType = true;
            } else {
                $message = "Oops! Mail Not Sent Please Try After Some Time";
                $messageType = false;
            }
        }        

        return response()->json(['success' => $messageType, 'message' => $message], 200);
    }

    public function OtpSigningActions(Request $request)
    {
        $id = $request->input('contactId');

        $contracts = Contract::select('*')->where('id', $id)->get();

        $contracts = $this->availableContracts($contracts, true);

        if (count($contracts) == 0) {
            return response()->json(['message' => 'Invalid Contract'], 200);
        } else {
            $contracts = $contracts[0];
        }


        $signatoryusers = AddUsers::select(decrypt_data('Email', 'AddUsers'))->where('id', $contracts->signatory)->get();

        if (isset($signatoryusers[0])) {
            $currentUserEmail = $signatoryusers[0]->Email;

            $emailTrigger = new ContractNotificationController();

            $MailSent = $emailTrigger->sendEmail($contracts->id, '', '', $currentUserEmail, 'Signing Request OTP', '',  [], 'OTPSign');

            if ($MailSent) {
                return response()->json(['success' => true, 'message' => "OTP Sent To Email $currentUserEmail"], 200);
            } else {
                return response()->json(['success' => false, 'message' => 'OTP Not Sent Retry After Some Time'], 200);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Signatory Email Not Exist Please Contact Admin'], 200);
        }
    }

    public function OtpApprovalActions(Request $request)
    {
        $id = $request->input('contactId');
        $indexId = $request->input('indexId');
        $shortDesc = $request->input('nextActionItem' . $indexId);
        $appId = $request->input('appId');
        $desc = $request->input('nextAction' . $indexId);
        $appType = $request->input('appType');
        $appDataStatus = $request->input('appStatus');
        $appPreStatus = $request->input('appPreStatus');
        $orderval = $request->input('orderval');
        $unique_id_old = $request->input('unique_id');
        $actionBtntext = $request->input('actionBtntext');
        $skipDocument = $request->input('skipDocument');

        $contracts = Contract::select('*')->where('id', $id)->get();

        $contracts = $this->availableContracts($contracts, true);

        if (count($contracts) == 0) {
            return response()->json(['message' => 'Invalid Contract'], 200);
        } else {
            $contracts = $contracts[0];
        }

        $checkOTPExist = OtpActions::select("*")->where('otp_ref', $contracts->id)->where('status', 1)->where('otp_type', 'signing')->orderBy('id', 'DESC')->limit(1)->get();

        if (count($checkOTPExist) == 1 &&  $checkOTPExist[0]->status == 1) {
            $otpCreated = $checkOTPExist[0]->otp_number;
            if ($otpCreated == $request->input('nextOtp')) {
                $updateOtp = OtpActions::where(['id'=> $checkOTPExist[0]->id])->update(['status' => 0]);
                //$htmlDoc = $this->convertWordToHtmlBuffer(base_path().'/storage/app/'.$contracts->contract_attachment);
                $htmlDoc = false;
                return response()->json(['success' => true, 'message' => 'OTP Verified Please Click Proceed Signing!', 'html' => $htmlDoc], 200);
            }
        } else if (count($checkOTPExist) == 1 && $checkOTPExist[0]->status == 2) {
            return response()->json(['success' => false, 'message' => 'Already OTP Verified Try Refresh'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Invalid OTP'], 200);
    }


    public function setUpSigningActions(Request $request)
    {

        $id = $request->input('contactId');
        $currentSign = $request->input('currentSign');

        $contracts = Contract::select('*')->where('id', $id)->get();

        $contracts = $this->availableContracts($contracts, true);

        $fileStoreController = fileStorageTypeController();

        if (count($contracts) == 0) {
            return response()->json(['message' => 'Invalid Contract'], 200);
        } else {
            $contracts = $contracts[0];
        }
        if ($request->file('uploadsign')) {
            $files = $request->file('uploadsign');
        }

        $unlinkTempFile = false;
        $unlinkFiles = "";

        //if(strtolower(pathinfo($contracts->contract_attachment_filename, PATHINFO_EXTENSION)) == 'docx'){
            if (fileStorageType() == "Local") {
                $storedWordFile = base_path() . '/storage/app/' . $contracts->contract_attachment;
            } else {
                $file_name = 'doc_' . strtotime(date('y-m-d h:i:s')) . '.docx';
    
                $contentDocx = $fileStoreController->downloadUrl($contracts->contract_attachment, $file_name);
    
                $file_path = 'contracts/tempDocs/';
    
                $filePath = Storage::disk('local')->put($file_path . $file_name, $contentDocx);
    
                $storedWordFile = base_path() . '/storage/app/' . $file_path . $file_name;
    
                $unlinkFiles = $file_path . $file_name;
    
                $unlinkTempFile = true;
            }
    
    
            $htmlDoc = $this->convertWordToHtmlBuffer($storedWordFile, ['images' => [$currentSign]]);
    
            if ($unlinkTempFile && $unlinkFiles != "") {
                Storage::delete($unlinkFiles);
            }
        //}
        //$htmlDoc = false;
        return response()->json(['success' => true, 'message' => 'Sign Proceeded!', 'html' => $htmlDoc], 200);

        return view('contract::contract.signEditor', compact('htmlDoc', 'currentSign'));

        //Download PDF
        $counterParties = count($contracts->contractPartyList->all()) ?? 0;
        $totalWidth = 80;
        $signplaceHolder = "";
        if ($counterParties > 0) {
            $eachDivWidth = ($totalWidth / $counterParties) - 1;
            for ($i = 0; $i < $counterParties; $i++) {
                $currentParty = $i + 1;
                $signplaceHolder .= "<td style='width:$eachDivWidth%; text-align:center; border: none !important;'>Party $currentParty Sign<br/> xxx</td>";
            }
        }

        $htmlDoc = str_replace('</style>', '</style><footer><table style="width:100%; position:relative; border: none !important;" cellspacing="0" cellpadding="0" ><tr>' . $signplaceHolder . '</tr></table></footer>', $htmlDoc);


        $pdf = \PDF::loadView("contract::contract.signedPdf", ['htmlDoc' => $htmlDoc]);
        return $pdf->stream('document_' . strtotime(date('y-m-d h:i:s')) . '.pdf');
        // return view('contract::contract.signedPdf', compact('htmlDoc'));


    }

    public function approversInsertScript($appArr, $contract_id, $app_unique_id, $appId, $appstatus)
    {
        $isNextStage = 0;
        $approvalsDataArr = ApprovalContracts::select('id', 'username',  'contract_id', 'status', 'approval_status', 'unique_id', 'flag')
            ->where('contract_id', $contract_id)
            ->where('unique_id', $app_unique_id)
            ->where('flag', '<>', -1)
            ->get()
            ->map(function ($task) {
                $task->username = decryptString($task->username, 'username');
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                $task->previous_status = decryptString($task->previous_status, 'previous_status');
                $task->status = decryptString($task->status, 'status');
                return $task;
            })
            ->toArray();

        // Normalise parent-grouped payloads (top-level map keyed by parent type
        // review/approval/signatory ...) into an ordered flat list of groups so the
        // grouped handling below can process them sequentially instead of fatally
        // array-indexing a stdClass.
        $appArr = $this->normalizeParentGroupedApprovers($appArr);

        // Detect grouped approvers (groups with `approvers` arrays) or legacy flat approver list
        $isGrouped = is_array($appArr) && isset($appArr[0]) && isset($appArr[0]->approvers);

        // If grouped, iterate groups and create approvals for the next pending group / approver
        if ($isGrouped) {
            $cumulative = 0; // count of approvers already created across groups

            foreach ($appArr as $groupIndex => $group) {
                $groupData = is_array($group) ? $group : (array)$group;
                $groupDynamicApproverEnabled = (int)($groupData['dynamic_approver_enabled'] ?? 0) === 1 ? 1 : 0;
                $groupApproversRaw = $groupData['approvers'] ?? [];
                $groupApprovers = is_array($groupApproversRaw) ? $groupApproversRaw : (json_decode((string)$groupApproversRaw, true) ?: []);
                $groupSize = count($groupApprovers);

                // If this entire group has already been created, skip
                if ($cumulative + $groupSize <= count($approvalsDataArr)) {
                    $cumulative += $groupSize;
                    continue;
                }

                // Determine next_status based on existing approvals
                $cur_status = isset($approvalsDataArr[0]['status']) ? $approvalsDataArr[0]['status'] : 'review';
                if (isset($appArr[$groupIndex + 1])) {
                    $next_status = $cur_status;
                } else {
                    if ($cur_status == 'review') {
                        $next_status = 'Negotiation';
                    } else {
                        $next_status = 'Signing';
                    }
                }

                if ($appstatus == 'Approval') {
                    $substatus = 'Pending Approval';
                } else {
                    $substatus = $appstatus;
                }

                // Determine how many from this group are already created
                $createdInThisGroup = max(0, count($approvalsDataArr) - $cumulative);

                // If parallel: create all remaining approvers in this group (they should all be pending simultaneously)
                if (isset($groupData['approval_type']) && strtolower((string)$groupData['approval_type']) == 'parallel') {
                    $nextEmails = [];
                    for ($i = $createdInThisGroup; $i < $groupSize; $i++) {
                        $ap = $groupApprovers[$i];
                        $approver_id = $ap->id ?? null;
                        if (!$approver_id) continue;
                        $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                        if (!isset($users[0])) continue;

                        ApprovalContracts::create([
                            'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                            'unique_id' => $app_unique_id,
                            'orderval' => $cumulative + $i,
                            'previous_status' => encryptString($appstatus, 'previous_status'),
                            'status' => encryptString($next_status, 'status'),
                            'next_status' => encryptString($next_status, 'next_status'),
                            'contract_id' => $contract_id,
                            'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                            'flag' => '1',
                            'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                        ]);

                        $nextEmails[] = $users[0]->Email;
                    }

                    Contract::where(['id' => $contract_id])->update([
                        'contract_status' => $appstatus,
                        'substatus' => $substatus
                    ]);

                    // Return list of emails (parallel)
                    return $nextEmails;
                }

                // If sequential: create only the next approver in this group
                $nextIndexInGroup = $createdInThisGroup;
                if ($nextIndexInGroup < $groupSize) {
                    $ap = $groupApprovers[$nextIndexInGroup];
                    $approver_id = $ap->id ?? null;
                    if ($approver_id) {
                        $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                        if (isset($users[0])) {
                            ApprovalContracts::create([
                                'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                'unique_id' => $app_unique_id,
                                'orderval' => count($approvalsDataArr),
                                'previous_status' => encryptString($appstatus, 'previous_status'),
                                'status' => encryptString($next_status, 'status'),
                                'next_status' => encryptString($next_status, 'next_status'),
                                'contract_id' => $contract_id,
                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                'flag' => '1',
                                'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                            ]);

                            Contract::where(['id' => $contract_id])->update([
                                'contract_status' => $appstatus,
                                'substatus' => $substatus
                            ]);

                            return $users[0]->Email;
                        }
                    }
                }

                // Should not reach here, but continue to next group if necessary
                $cumulative += $groupSize;
            }

            return "";
        }

        // Legacy flat approver list behavior
        if (is_array($appArr) && isset($appArr[count($approvalsDataArr)])) {
            $approver_id = $appArr[count($approvalsDataArr)]->id;
            $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
            $cur_status = $approvalsDataArr[0]['status'];

            if (isset($appArr[count($approvalsDataArr) + 1])) {
                $next_status = $cur_status;
            } else {
                if ($cur_status == 'review') {
                    $next_status = 'Negotiation';
                } else {
                    $next_status = 'Signing';
                }
            }

            if ($appstatus == 'Approval') {
                $substatus = 'Pending Approval';
            } else {
                $substatus = $appstatus;
            }

            ApprovalContracts::create([
                'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                'unique_id' => $app_unique_id,
                'orderval' => count($approvalsDataArr),
                'previous_status' => encryptString($appstatus, 'previous_status'),
                'status' => encryptString($next_status, 'status'),
                'next_status' => encryptString($next_status, 'next_status'),
                'contract_id' => $contract_id,
                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                'flag' => '1',
            ]);

            Contract::where(['id' => $contract_id])->update([
                'contract_status' => $appstatus,
                'substatus' => $substatus
            ]);
        }

    }

    public function sendContractForReview(Request $request)
    {

        $contract_id =  $request->input('id');
        $nextAppStatus =  $request->input('nextAppStatus');
        $curAppStatus =  $request->input('curAppStatus');
        $userInputVal =  $request->input('userInputVal');
        $ReviewDescription =  $request->input('ReviewDescription');
        $shortDescrip =  $request->input('shortDescrip');
        $fileTypeDoc =  $request->input('fileTypeDoc');
        $skipDocument = $request->input('skipDocument');
        $updateHistory = false;

        $appRowId =  $request->input('appRowId');

        if ($request->input('approvalInps') && $request->input('approvalInps') !== null) {
            if ($request->input('approvalInps.fixedDate') != null && $request->input('approvalInps.contract_end_date') != null) {
                if (strtotime($request->input('Duration.fixedDate')) > strtotime($request->input('approvalInps.contract_end_date'))) {
                    return response()->json(['message' => 'Contract end date must be greater than Start Date'], 200);
                }
            }            
            Contract::where(['id' => $contract_id])->update($request->input('approvalInps'));
        }

        if ($request->input('customFields') && $request->input('customFields') !== null) {
            foreach ($request->input('customFields') as $keyId => $customField) {
                CustomFieldsData::create([
                    'custom_field_id' => $keyId,
                    'custom_field_group' => 'contracts',
                    'custom_field_value' => $customField,
                    'custom_field_group_id' => $contract_id
                ]);
            }
        }

        $controller = fileStorageTypeController();

        $filesData = "";
        $filesDataName = "";

        $allowedDocType = ['pdf', 'docx'];
        $pdfOnlyAllowed = 'For Final Documents Pdf Only Allowed';
        $pdfOrDocOnlyAllowed = 'For Contract Documents Docx/Pdf Only Allowed';


        //For Email
        $emailTrigger = new ContractNotificationController();
        $senattment = [];
        $senattment['filename'] = [];
        $senattment['filurl'] = [];

        //---- users Data ------ //

        $contracts = Contract::select('*')->where('id', $contract_id)->get();

        $contracts = $this->availableContracts($contracts, true);

        if (count($contracts) == 0) {
            return response()->json(['message' => 'Invalid Contract'], 200);
        } else {
            $contracts = $contracts[0];
        }

        $filesSupport = [];

        $skipFileMissingValidation = ['Negotiation'];

        if ($request->file('photos')) {
            $files = $request->file('photos');
            $filesType = $request->input('fileType');

            if (fileStorageType() == "Local" && !in_array($curAppStatus,  $skipFileMissingValidation)) {
                $contractFilePresent = 0;
                foreach ($files as $file) {

                    if ($filesType[$file->getClientOriginalName()] == 'contract') {
                        $contractFilePresent = 1;
                    }
                }


                if ($contractFilePresent == 0 && $skipDocument == 'false') {
                    return response()->json(['message' => 'Please Upload Contract Document'], 200);
                }
            }

            foreach ($files as $file) {

                if ($filesType[$file->getClientOriginalName()] == 'contract') {

                    if ($contracts->signing_date && $curAppStatus == 'Signing') {
                        $allowedDocType = ['pdf'];
                        if (!in_array(strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)), $allowedDocType)) {
                            return response()->json(['message' => $pdfOnlyAllowed], 200);
                        }
                    } else {
                        if (!in_array(strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)), $allowedDocType)) {
                            return response()->json(['message' => $pdfOrDocOnlyAllowed], 200);
                        }
                    }
                    // Add the file object to the filesData array
                    $filesDataName = file_name($file);
                    $filesData = $controller->storeFile($file, 'approvals', $contract_id, $filesDataName);
                    if(!$filesData){
                        return response()->json(['message' => 'Storage Server Down/Token Expired'], 200);
                    }
                    Contract::where(['id' => $contract_id])->update([
                        'contract_attachment_filename' => $filesDataName,
                        'contract_attachment' => $filesData
                    ]);

                    $senattment['filename'][] = $filesDataName;
                    $senattment['filurl'][] = $filesData;

                } else {
                    $filesDataName = file_name($file);
                    $fileObject = new stdClass();
                    $fileObject->name = $filesDataName;
                    $fileObject->path = $controller->storeFile($file, 'approvals', $contract_id, $filesDataName);
                    $filesSupport[] = $fileObject;

                    $senattment['filename'][] = $filesDataName;
                    $senattment['filurl'][] = $fileObject->path;
                }
            }
        } else {

            if (fileStorageType() == "Local" && !in_array($curAppStatus,  $skipFileMissingValidation) && $skipDocument == 'false') {
                return response()->json(['message' => 'Please Upload Contract Document'], 200);
            }
        }

        //$controller->getComments($contracts->contract_attachment);

        $owner = $contracts->owner;
        $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $owner)->get();


        $signatory = $contracts->signatory;
        $usersign = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $signatory)->get();

        // -------End ---------//


        //-------- approvers data from contracts --------//

        $approvalArr = Contract::select('rules_id')->where('id', $contract_id)->get();

        $currentApproval = ApprovalContracts::find($appRowId);


        //For Revoke Write access
        $currentUserEmail = json_decode(decryptString($currentApproval->username, 'username'))->email;

        //For Add Editor Access Next Approver
        $nextAprroverEmail = "";

        $appArr = json_decode(trim($approvalArr[0]['rules_id']));
        $randNo = rand(0, 99999);

        //-----------------------------------------------//


        // die;
        ApprovalContracts::where(['contract_id' => $contract_id])->update([
            'flag' => 0,
            'approval_status' => encryptStringx($userInputVal, 'approval_contracts.approval_status')
        ]);

        ApprovalContracts::where(['id' => $appRowId])->update([
            'next_action_item' => encryptString($shortDescrip, 'next_action_item'),
            'next_action_description' => encryptString($ReviewDescription, 'next_action_description'),
            'next_status' => encryptString($nextAppStatus, 'next_status'),
            'attachments' => $filesData,
            'attachments_filename' => $filesDataName,
            'attachments_support' => $filesSupport,
            'updated_on' => date('Y-m-d H:i:s'),
            'updated_by' => json_encode(['email' => Helpers::userInfo()->email ?? 'User', 'name' => Helpers::userInfo()->FirstName ?? 'Inactive'])
        ]);

        $multipleNextApprovers = false;
        
        if ($userInputVal == 'rejected') {
            if (isset($users[0])) {

                $username = $users[0]->Email;
                $orderval = 0;
                $unique_id_new = $contract_id . $randNo;

                $nextAprroverEmail = $username;

                ApprovalContracts::create([
                    'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                    'unique_id' => $unique_id_new,
                    'orderval' => $orderval,
                    'previous_status' => encryptString('Draft', 'previous_status'),
                    'status' => encryptString('Draft', 'status'),
                    'contract_id' => $contract_id,
                    'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                    'flag' => '1'
                ]);

                Contract::where(['id' => $contract_id])->update([
                    'contract_status' => 'Draft',
                    'substatus' => 'Under Revision'
                ]);
                
                $updateHistory = true;

            }
        } else {
            foreach ($appArr as $appData) {
                $approval_type = $appData->approval_type;
                $approval_status = $appData->approval_status;
                $approvalArr = json_decode($appData->approver);

                if ($approval_status == 'required') {
                    if ($nextAppStatus == 'Signing') {

                        if (isset($usersign[0])) {

                            $username = $usersign[0]->Email;
                            $randNo = rand(0, 99999);
                            $orderval = 0;
                            $unique_id_new = $contract_id . $randNo;


                            $nextAprroverEmail = $username;

                            $approvalRow = ApprovalContracts::create([
                                'username' => encryptString(json_encode(['email' => $usersign[0]->Email, 'name' => $usersign[0]->FirstName]), 'username'),
                                'unique_id' => $unique_id_new,
                                'orderval' => 0,
                                'previous_status' => encryptString('Approved', 'previous_status'),
                                'status' => encryptString('Signing', 'status'),
                                'contract_id' => $contract_id,
                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                'flag' => '1',
                                'approval_type_main' => 'sequential',
                                'approval_type_row' => 'sequential',
                                'approver_type_row' => 'signatory'
                            ]);

                            Contract::where(['id' => $contract_id])->update([
                                'contract_status' => 'Signing',
                                'substatus' => 'Approved'
                            ]);
                            
                            $updateHistory = true;
                            
                            //User Action Log
                            $this->crudUserActionLog($contract_id, 'approval', 'signing-email', $approvalRow->id, 0, $usersign[0]->Email, false, $usersign[0]->FirstName." ".$usersign[0]->LastName);
                        }
                    } elseif ($nextAppStatus == 'Negotiation') {
                        // echo $nextAppStatus;die;
                        if (isset($users[0])) {

                            $username = $users[0]->Email;
                            $randNo = rand(0, 99999);
                            $orderval = 0;
                            $unique_id_new = $contract_id . $randNo;

                            $nextAprroverEmail = $username;

                            ApprovalContracts::create([
                                'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                'unique_id' => $unique_id_new,
                                'orderval' => $orderval,
                                'previous_status' => encryptString('review', 'previous_status'),
                                'status' => encryptString('Negotiation', 'status'),
                                'contract_id' => $contract_id,
                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                'flag' => '1'
                            ]);

                            Contract::where(['id' => $contract_id])->update([
                                'contract_status' => 'Negotiation',
                                'substatus' => 'Under Process'
                            ]);
                            $updateHistory = true;
                        }
                    } else {
                        
                        if($nextAppStatus == 'review' && decryptString($contracts->contract_mode, 'contract_mode')  == 'old'){
                            $nextAppStatus = 'Approval';
                            $curAppStatus = 'Negotiation';
                        }
                        // Normalize approval array: support legacy JSON string and new grouped array formats
                        if (is_array($appData->approver)) {
                            $approvalArrNorm = $appData->approver;
                        } else {
                            $approvalArrNorm = json_decode($appData->approver);
                        }

                        // Detect grouped structure
                        $isGrouped = false;
                        $isParentGrouped = false;
                        $parentRouting = [];

                        $parentKeys = ['review', 'negotiation', 'finalization', 'approval', 'signatory'];

                        if (is_object($approvalArrNorm)) {
                            // Parent-grouped payloads decode to a stdClass keyed by parent
                            // type (review/approval/signatory ...). Normalise the top-level
                            // container to an array so parent-type keys and _parent_routing
                            // can be read by key (nested groups/approvers stay objects).
                            $topKeys = array_keys((array)$approvalArrNorm);
                            if (!empty(array_intersect($topKeys, $parentKeys))) {
                                $approvalArrNorm = (array)$approvalArrNorm;
                                $isGrouped = true;
                                $isParentGrouped = true;
                                if (isset($approvalArrNorm['_parent_routing'])) {
                                    $parentRouting = (array)$approvalArrNorm['_parent_routing'];
                                }
                            }
                        } elseif (is_array($approvalArrNorm) && isset($approvalArrNorm[0]) && isset($approvalArrNorm[0]->approvers)) {
                            // Legacy grouped structure: indexed array of group objects
                            $isGrouped = true;
                        }

                        $unique_id = $contract_id . $randNo;

                        if ($isParentGrouped) {
                            // New parent-grouped flow: initiate ONLY the review stage. createApprovalRows
                            // activates just the first group's first approver (sequential) or every
                            // approver of the first group (parallel). Later stages are advanced by
                            // contractApprovals -> advanceGroupedApproval driven by _parent_routing.
                            $reviewGroups = $approvalArrNorm['review'] ?? [];
                            if (!empty($reviewGroups)) {
                                // flow_type 'preapproval' so the rows render in the existing
                                // Pre-Approval Flow UI (preApprovalFlow.blade / showPreApprovalPage).
                                $this->createApprovalRows($contract_id, 'preapproval', 'review', $reviewGroups);
                                Contract::where('id', $contract_id)->update(['preapproval_stage' => 'review']);

                                $activeReviewRows = ApprovalContracts::where('contract_id', $contract_id)
                                    ->where('stage_name', 'review')
                                    ->where('flag', 1)
                                    ->get();
                                $emails = [];
                                foreach ($activeReviewRows as $r) {
                                    try {
                                        $u = json_decode(decryptString($r->username, 'username'), true);
                                        if (!empty($u['email'])) $emails[] = $u['email'];
                                    } catch (\Throwable $e) {
                                    }
                                }
                                if (count($emails) > 1) {
                                    $multipleNextApprovers = true;
                                    $nextAprroverEmail = $emails;
                                } elseif (count($emails) === 1) {
                                    $nextAprroverEmail = $emails[0];
                                }
                            }

                        } elseif ($isGrouped) {
                            // Legacy flat array structure with 'role' field
                            $group = $approvalArrNorm[0];
                            $groupData = is_array($group) ? $group : (array)$group;
                            $groupType = $groupData['approval_type'] ?? $approval_type;
                            $groupRole = $groupData['role'] ?? 'Approver';
                            $groupDynamicApproverEnabled = (int)($groupData['dynamic_approver_enabled'] ?? 0) === 1 ? 1 : 0;
                            $groupApproversRaw = $groupData['approvers'] ?? [];
                            $groupApprovers = is_array($groupApproversRaw) ? $groupApproversRaw : (json_decode((string)$groupApproversRaw, true) ?: []);

                            if (strtolower($groupType) == 'parallel') {
                                $nextAprroverEmail = [];
                                $ord = 0;
                                foreach ($groupApprovers as $ap) {
                                    $approver_id = $ap->id ?? null;
                                    if (!$approver_id) continue;
                                    $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                                    if (!isset($users[0])) continue;
                                            ApprovalContracts::create([
                                                'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                                'previous_status' => encryptString($curAppStatus, 'previous_status'),
                                                'status' => encryptString($nextAppStatus, 'status'),
                                                'contract_id' => $contract_id,
                                                'orderval' => $ord,
                                                'unique_id' => $unique_id,
                                                'flag' => 1,
                                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                                'approval_type_main' => $approval_type,
                                                'approval_type_row' => $groupType,
                                                'approver_type_row' => $groupRole,
                                                'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                            ]);

                                            $nextAprroverEmail[] = $users[0]->Email;
                                            $ord++;
                                        }
                                        $multipleNextApprovers = true;
                                    } else {
                                        // sequential group: create only first approver
                                        if (!empty($groupApprovers)) {
                                            $ap = $groupApprovers[0];
                                            $approver_id = $ap->id ?? null;
                                            if ($approver_id) {
                                                $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                                                if (isset($users[0])) {
                                                    ApprovalContracts::create([
                                                        'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                                        'previous_status' => encryptString($curAppStatus, 'previous_status'),
                                                        'status' => encryptString($nextAppStatus, 'status'),
                                                        'contract_id' => $contract_id,
                                                        'orderval' => 0,
                                                        'unique_id' => $unique_id,
                                                        'flag' => 1,
                                                        'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                                        'approval_type_main' => $approval_type,
                                                        'approval_type_row' => $groupType,
                                                        'approver_type_row' => $groupRole,
                                                        'dynamic_approver_enabled' => $groupDynamicApproverEnabled,
                                                    ]);

                                            $nextAprroverEmail = $users[0]->Email;
                                        }
                                    }
                                }
                            }

                        } else {
                            // Legacy flat approver list behavior
                            foreach ($approvalArr as $key => $appVal) {
                                $approver_id = $appVal->id;
                                $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                                ApprovalContracts::create([
                                    'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                    'previous_status' => encryptString($curAppStatus, 'previous_status'),
                                    'status' => encryptString($nextAppStatus, 'status'),
                                    'contract_id' => $contract_id,
                                    'orderval' => $key,
                                    'unique_id' => $unique_id,
                                    'flag' => 1,
                                    'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                                    'approval_type_main' => $approval_type,
                                    'approval_type_row' => $approval_type,
                                    'approver_type_row' => 'Approver',
                                ]);
                                if ($approval_type == 'sequential'){
                                    $nextAprroverEmail = $users[0]->Email;
                                    break;
                                }else{
                                    if($nextAprroverEmail == ""){
                                        $nextAprroverEmail = [];
                                    }
                                    $multipleNextApprovers = true;
                                    $nextAprroverEmail[] = $users[0]->Email;
                                }
                            }
                        }

                        if ($nextAppStatus == 'Approval') {
                            $substatus = 'Pending Approval';
                        } elseif ($nextAppStatus == 'review') {
                            $substatus = 'Under Process';
                        }
                        Contract::where(['id' => $contract_id])->update([
                            'contract_status' => $nextAppStatus,
                            'substatus' => $substatus
                        ]);
                        $updateHistory = true;
                    }
                }
            }
        }

        $toUser = $nextAprroverEmail;
        $contracts_Final = Contract::select('contract_attachment')->where('id', $contract_id)->first();
        $contract_attachment = $contracts_Final->contract_attachment;
        if ($filesDataName == "") {
            $contract_attachment_data = $controller->copyFile($contract_id, $contracts_Final->contract_attachment) ?? [];

            if (count($contract_attachment_data) == 2) {
                Contract::where(['id' => $contract_id])->update([
                    'contract_attachment_filename' => $contract_attachment_data[0],
                    'contract_attachment' => $contract_attachment_data[1]
                ]);
                $updateHistory = true;
                $contract_attachment = $contract_attachment_data[1];
                
                //On Copy of Documents give access to approvers
                $approvers = json_decode($contracts_Final->rules_id);
                if (isset($approvers[0]) && $approvers[0]->approver) {
                    $approverIds = $this->collectApproverIdsFromJson($approvers[0]->approver);
                    $approverIds[] = $contracts_Final->owner;
                    $approverIds[] = $contracts_Final->signatory;
                    $emailApprovers = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))
                        ->whereIn('id', $approverIds)->get()->pluck('Email')->toArray();
                    if($currentUserEmail != ""){
                        $emailApprovers[] = $currentUserEmail;
                    }
                    $currentUserEmail = $emailApprovers;
                }                
            }
        }
        
        if($updateHistory){
            $contracthisHistory = Contract::where('id', $contract_id)->first()->toArray();
            $contractHistoryCreated = ContractHistory::create($contracthisHistory, ['except' => ['created_at', 'updated_at', 'contract_party_list', 'contract_name']]);
            $contractPartyHistory = ContractPartyData::where('custom_field_group_id', $contract_id)->get()->toArray();
            
            $fianlContractPartyHistory = [];
            if(!empty($contractPartyHistory)){
                foreach($contractPartyHistory as $cph){
                    $cph['history_id'] = $contractHistoryCreated->id;
                    $fianlContractPartyHistory[] = $cph;
                }
            }
            if(!empty($fianlContractPartyHistory)){
                $contractPartyHistoryCreated = ContractPartyDataHistory::insert($fianlContractPartyHistory); 
            }
        }

        //For Mail Functionality
        $MailSent = $emailTrigger->sendEmail($contract_id, $ReviewDescription, $shortDescrip, $toUser, $nextAppStatus, $senattment['filename'],  $senattment['filurl'], 'notiMail');
        $controller->changePermission($contract_attachment, $currentUserEmail, $nextAprroverEmail);
        return response()->json(['message' => 'successful!'], 200);
    }

    public function contractImport(Request $request)
    {
        return view('contract::contract.contractImport');
    }

    public function linkUnlinkContract(Request $request)
    {
        if (!isset($request->contractEndType)) {
            $request->contractEndType = "fixedTerm";
        }
        $messageCode = 200;

        if (isset($request->linkStatus))
            if ($request->linkStatus == 'link') {

                $contractLinked = Contract::where('id', $request->linkid)->update([
                    'parentcontract' => $request->parentContract
                ]);

                $messageUpdate = "Contract Linked Successfully";
                $successType = true;

                if ($contractLinked) {

                    $contracthisLinked = Contract::where('id', $request->linkid)->first();

                    $contractHistoryLinked = ContractHistory::create([
                        'contract_name' => $contracthisLinked->contract_name,
                        'id' => $contracthisLinked->id,
                        'contract_mode' => $contracthisLinked->contract_mode,
                        'contract_type' => $contracthisLinked->contract_type,
                        // 'contract_name' => $contract->/ 'contract_name,
                        'contract_description' => $contracthisLinked->contract_description,

                        'department_id' => $contracthisLinked->department_id,
                        'catgoery_id' => $contracthisLinked->catgoery_id,

                        'signatory' => $contracthisLinked->signatory,
                        'owner' => $contracthisLinked->owner,


                        'confidentialityagreement' => $contracthisLinked->confidentialityagreement,
                        'exclusivity' => $contracthisLinked->exclusivity,

                        // Contract Duration
                        'signing_date' => $contracthisLinked->signing_date,
                        'commencement_type' => $contracthisLinked->commencement_type,
                        'fixed_date' => $contracthisLinked->fixed_date,
                        'event_name' => $contracthisLinked->event_name,
                        'end_contract_type' => $contracthisLinked->end_contract_type,
                        // 'onetime_end_date' => $contracthisLinked->onetime_end_date,
                        // 'fixedterm_end_date' => $contracthisLinked->fixedterm_end_date,
                        'contract_end_date' => $contracthisLinked->contract_end_date,
                        'renewal_type' => $contracthisLinked->renewal_type,
                        'period_auto_renewal' => $contracthisLinked->period_auto_renewal,
                        'period_auto_renewal_unit' => $contracthisLinked->period_auto_renewal_unit,
                        'auto_renewal_date' => $contracthisLinked->auto_renewal_date,
                        'manual_renewal_date' => $contracthisLinked->manual_renewal_date,
                        'evergreen_condition' => $contracthisLinked->evergreen_condition,
                        'termination_date' => $contracthisLinked->termination_date,
                        'termination_reason' => $contracthisLinked->termination_reason,


                        // Contract Value
                        'currency' => $contracthisLinked->currency,
                        'currency_value' => $contracthisLinked->currency_value,
                        'payment_schedule' => $contracthisLinked->payment_schedule,
                        'currency_contract' => $contracthisLinked->currency_contract,
                        'payment_terms' => $contracthisLinked->payment_terms,
                        'billing_frequency' => $contracthisLinked->billing_frequency,
                        'taxes' => $contracthisLinked->taxes,
                        'escalation_clauses' => $contracthisLinked->escalation_clauses,
                        'discounts' => $contracthisLinked->discounts,
                        'retention' => $contracthisLinked->retention,
                        'payment_escrow' => $contracthisLinked->payment_escrow,
                        'financial_guarantees' => $contracthisLinked->financial_guarantees,
                        'currency_conversion' => $contracthisLinked->currency_conversion,

                        // Reminder Value
                        'reminder_first_alert' => $contracthisLinked->reminder_first_alert,
                        'reminder_first_alertMeOn' => $contracthisLinked->reminder_first_alertMeOn,
                        'reminder_first_alert_repeats' => $contracthisLinked->reminder_first_alert_repeats,
                        'reminder_second_alert' => $contracthisLinked->reminder_second_alert,
                        'reminder_second_alertMeOn' => $contracthisLinked->reminder_second_alertMeOn,
                        'reminder_second_alert_repeats' => $contracthisLinked->reminder_second_alert_repeats,
                        'reminder_escalation_alert' => $contracthisLinked->reminder_escalation_alert,
                        'reminder_escalation_alertMeOn' => $contracthisLinked->reminder_escalation_alertMeOn,
                        'reminder_escalation_alert_repeats' => $contracthisLinked->reminder_escalation_alert_repeats,

                        'rules_id' => $contracthisLinked->rules_id,

                        'custom_fields_data' => $contracthisLinked->custom_fields_data,
                        'contract_attachment' => $contracthisLinked->contract_attachment,
                        'contract_attachment_filename' => $contracthisLinked->contract_attachment_filename,
                        'created_by' => $contracthisLinked->created_by ? $contracthisLinked->created_by : 1,
                        'parentcontract' => $request->parentContract
                    ]);

                    $updateOldcontract = Contract::where('id', $request->parentContract)->update([
                        'substatus' => 'renewed'
                    ]);


                    $contracthisOldContract = Contract::where('id', $request->parentContract)->first();

                    $contractHistoryOld = ContractHistory::create([
                        'contract_name' => $contracthisOldContract->contract_name,
                        'id' => $contracthisOldContract->id,
                        'contract_mode' => $contracthisOldContract->contract_mode,
                        'contract_type' => $contracthisOldContract->contract_type,
                        // 'contract_name' => $contract->/ 'contract_name,
                        'contract_description' => $contracthisOldContract->contract_description,

                        'department_id' => $contracthisOldContract->department_id,
                        'catgoery_id' => $contracthisOldContract->catgoery_id,

                        'signatory' => $contracthisOldContract->signatory,
                        'owner' => $contracthisOldContract->owner,


                        'confidentialityagreement' => $contracthisOldContract->confidentialityagreement,
                        'exclusivity' => $contracthisLinked->exclusivity,

                        // Contract Duration
                        'signing_date' => $contracthisOldContract->signing_date,
                        'commencement_type' => $contracthisOldContract->commencement_type,
                        'fixed_date' => $contracthisOldContract->fixed_date,
                        'event_name' => $contracthisOldContract->event_name,
                        'end_contract_type' => $contracthisOldContract->end_contract_type,
                        // 'onetime_end_date' => $contracthisOldContract->onetime_end_date,
                        // 'fixedterm_end_date' => $contracthisOldContract->fixedterm_end_date,
                        'contract_end_date' => $contracthisOldContract->contract_end_date,
                        'renewal_type' => $contracthisOldContract->renewal_type,
                        'period_auto_renewal' => $contracthisOldContract->period_auto_renewal,
                        'period_auto_renewal_unit' => $contracthisOldContract->period_auto_renewal_unit,
                        'auto_renewal_date' => $contracthisOldContract->auto_renewal_date,
                        'manual_renewal_date' => $contracthisOldContract->manual_renewal_date,
                        'evergreen_condition' => $contracthisOldContract->evergreen_condition,
                        'termination_date' => $contracthisOldContract->termination_date,
                        'termination_reason' => $contracthisOldContract->termination_reason,


                        // Contract Value
                        'currency' => $contracthisOldContract->currency,
                        'currency_value' => $contracthisOldContract->currency_value,
                        'payment_schedule' => $contracthisOldContract->payment_schedule,
                        'currency_contract' => $contracthisOldContract->currency_contract,
                        'payment_terms' => $contracthisOldContract->payment_terms,
                        'billing_frequency' => $contracthisOldContract->billing_frequency,
                        'taxes' => $contracthisOldContract->taxes,
                        'escalation_clauses' => $contracthisOldContract->escalation_clauses,
                        'discounts' => $contracthisOldContract->discounts,
                        'retention' => $contracthisOldContract->retention,
                        'payment_escrow' => $contracthisOldContract->payment_escrow,
                        'financial_guarantees' => $contracthisOldContract->financial_guarantees,
                        'currency_conversion' => $contracthisOldContract->currency_conversion,

                        // Reminder Value
                        'reminder_first_alert' => $contracthisOldContract->reminder_first_alert,
                        'reminder_first_alertMeOn' => $contracthisOldContract->reminder_first_alertMeOn,
                        'reminder_first_alert_repeats' => $contracthisOldContract->reminder_first_alert_repeats,
                        'reminder_second_alert' => $contracthisOldContract->reminder_second_alert,
                        'reminder_second_alertMeOn' => $contracthisOldContract->reminder_second_alertMeOn,
                        'reminder_second_alert_repeats' => $contracthisOldContract->reminder_second_alert_repeats,
                        'reminder_escalation_alert' => $contracthisOldContract->reminder_escalation_alert,
                        'reminder_escalation_alertMeOn' => $contracthisOldContract->reminder_escalation_alertMeOn,
                        'reminder_escalation_alert_repeats' => $contracthisOldContract->reminder_escalation_alert_repeats,

                        'rules_id' => $contracthisOldContract->rules_id,

                        'custom_fields_data' => $contracthisOldContract->custom_fields_data,
                        'contract_attachment' => $contracthisOldContract->contract_attachment,
                        'contract_attachment_filename' => $contracthisOldContract->contract_attachment_filename,
                        'created_by' => $contracthisOldContract->created_by ? $contracthisOldContract->created_by : 1,
                        'substatus' => 'renewed'
                    ]);
                }
            } else if ($request->linkStatus == 'unlink') {

                $updateParentcon = Contract::where('id', $request->parentContract)->first();

                // if($request->contractEndType == 'fixedTerm'){
                //     $end_date_of_contract = $updateParentcon->fixedterm_end_date;
                // }else{
                //     $end_date_of_contract = $updateParentcon->onetime_end_date;
                // }
                $end_date_of_contract = $updateParentcon->contract_end_date;

                $cur_date = date("Y-m-d");

                $checkeDate = DateTime::createFromFormat("Y-m-d", $end_date_of_contract);



                if ($checkeDate !== false) {

                    if (strtotime($end_date_of_contract) > strtotime($cur_date)) {
                        $contract_sub_status = 'active';
                    } elseif (strtotime($cur_date) > strtotime($end_date_of_contract)) {
                        $contract_sub_status = 'expired';
                    }

                    $contractUnlinked = Contract::where('id', $request->linkid)->update([
                        'parentcontract' => 0
                    ]);

                    $contracthisLinked = Contract::where('id', $request->linkid)->first();

                    $contractHistoryLinked = ContractHistory::create([
                        'contract_name' => $contracthisLinked->contract_name,
                        'id' => $contracthisLinked->id,
                        'contract_mode' => $contracthisLinked->contract_mode,
                        'contract_type' => $contracthisLinked->contract_type,
                        // 'contract_name' => $contract->/ 'contract_name,
                        'contract_description' => $contracthisLinked->contract_description,

                        'department_id' => $contracthisLinked->department_id,
                        'catgoery_id' => $contracthisLinked->catgoery_id,

                        'signatory' => $contracthisLinked->signatory,
                        'owner' => $contracthisLinked->owner,


                        'confidentialityagreement' => $contracthisLinked->confidentialityagreement,
                        'exclusivity' => $contracthisLinked->exclusivity,

                        // Contract Duration
                        'signing_date' => $contracthisLinked->signing_date,
                        'commencement_type' => $contracthisLinked->commencement_type,
                        'fixed_date' => $contracthisLinked->fixed_date,
                        'event_name' => $contracthisLinked->event_name,
                        'end_contract_type' => $contracthisLinked->end_contract_type,
                        // 'onetime_end_date' => $contracthisLinked->onetime_end_date,
                        // 'fixedterm_end_date' => $contracthisLinked->fixedterm_end_date,
                        'contract_end_date' => $contracthisLinked->contract_end_date,
                        'renewal_type' => $contracthisLinked->renewal_type,
                        'period_auto_renewal' => $contracthisLinked->period_auto_renewal,
                        'period_auto_renewal_unit' => $contracthisLinked->period_auto_renewal_unit,
                        'auto_renewal_date' => $contracthisLinked->auto_renewal_date,
                        'manual_renewal_date' => $contracthisLinked->manual_renewal_date,
                        'evergreen_condition' => $contracthisLinked->evergreen_condition,
                        'termination_date' => $contracthisLinked->termination_date,
                        'termination_reason' => $contracthisLinked->termination_reason,


                        // Contract Value
                        'currency' => $contracthisLinked->currency,
                        'currency_value' => $contracthisLinked->currency_value,
                        'payment_schedule' => $contracthisLinked->payment_schedule,
                        'currency_contract' => $contracthisLinked->currency_contract,
                        'payment_terms' => $contracthisLinked->payment_terms,
                        'billing_frequency' => $contracthisLinked->billing_frequency,
                        'taxes' => $contracthisLinked->taxes,
                        'escalation_clauses' => $contracthisLinked->escalation_clauses,
                        'discounts' => $contracthisLinked->discounts,
                        'retention' => $contracthisLinked->retention,
                        'payment_escrow' => $contracthisLinked->payment_escrow,
                        'financial_guarantees' => $contracthisLinked->financial_guarantees,
                        'currency_conversion' => $contracthisLinked->currency_conversion,

                        // Reminder Value
                        'reminder_first_alert' => $contracthisLinked->reminder_first_alert,
                        'reminder_first_alertMeOn' => $contracthisLinked->reminder_first_alertMeOn,
                        'reminder_first_alert_repeats' => $contracthisLinked->reminder_first_alert_repeats,
                        'reminder_second_alert' => $contracthisLinked->reminder_second_alert,
                        'reminder_second_alertMeOn' => $contracthisLinked->reminder_second_alertMeOn,
                        'reminder_second_alert_repeats' => $contracthisLinked->reminder_second_alert_repeats,
                        'reminder_escalation_alert' => $contracthisLinked->reminder_escalation_alert,
                        'reminder_escalation_alertMeOn' => $contracthisLinked->reminder_escalation_alertMeOn,
                        'reminder_escalation_alert_repeats' => $contracthisLinked->reminder_escalation_alert_repeats,

                        'rules_id' => $contracthisLinked->rules_id,

                        'custom_fields_data' => $contracthisLinked->custom_fields_data,
                        'contract_attachment' => $contracthisLinked->contract_attachment,
                        'contract_attachment_filename' => $contracthisLinked->contract_attachment_filename,
                        'created_by' => $contracthisLinked->created_by ? $contracthisLinked->created_by : 1,
                        'parentcontract' => 0
                    ]);

                    if ($contractUnlinked) {
                        $updateParentcon->update([
                            'substatus' => $contract_sub_status
                        ]);

                        $contracthisOldContract = Contract::where('id', $request->parentContract)->first();

                        $contractHistoryOld = ContractHistory::create([
                            'contract_name' => $contracthisOldContract->contract_name,
                            'id' => $contracthisOldContract->id,
                            'contract_mode' => $contracthisOldContract->contract_mode,
                            'contract_type' => $contracthisOldContract->contract_type,
                            // 'contract_name' => $contract->/ 'contract_name,
                            'contract_description' => $contracthisOldContract->contract_description,

                            'department_id' => $contracthisOldContract->department_id,
                            'catgoery_id' => $contracthisOldContract->catgoery_id,

                            'signatory' => $contracthisOldContract->signatory,
                            'owner' => $contracthisOldContract->owner,


                            'confidentialityagreement' => $contracthisOldContract->confidentialityagreement,
                            'exclusivity' => $contracthisLinked->exclusivity,

                            // Contract Duration
                            'signing_date' => $contracthisOldContract->signing_date,
                            'commencement_type' => $contracthisOldContract->commencement_type,
                            'fixed_date' => $contracthisOldContract->fixed_date,
                            'event_name' => $contracthisOldContract->event_name,
                            'end_contract_type' => $contracthisOldContract->end_contract_type,
                            // 'onetime_end_date' => $contracthisOldContract->onetime_end_date,
                            // 'fixedterm_end_date' => $contracthisOldContract->fixedterm_end_date,
                            'contract_end_date' => $contracthisOldContract->contract_end_date,
                            'renewal_type' => $contracthisOldContract->renewal_type,
                            'period_auto_renewal' => $contracthisOldContract->period_auto_renewal,
                            'period_auto_renewal_unit' => $contracthisOldContract->period_auto_renewal_unit,
                            'auto_renewal_date' => $contracthisOldContract->auto_renewal_date,
                            'manual_renewal_date' => $contracthisOldContract->manual_renewal_date,
                            'evergreen_condition' => $contracthisOldContract->evergreen_condition,
                            'termination_date' => $contracthisOldContract->termination_date,
                            'termination_reason' => $contracthisOldContract->termination_reason,


                            // Contract Value
                            'currency' => $contracthisOldContract->currency,
                            'currency_value' => $contracthisOldContract->currency_value,
                            'payment_schedule' => $contracthisOldContract->payment_schedule,
                            'currency_contract' => $contracthisOldContract->currency_contract,
                            'payment_terms' => $contracthisOldContract->payment_terms,
                            'billing_frequency' => $contracthisOldContract->billing_frequency,
                            'taxes' => $contracthisOldContract->taxes,
                            'escalation_clauses' => $contracthisOldContract->escalation_clauses,
                            'discounts' => $contracthisOldContract->discounts,
                            'retention' => $contracthisOldContract->retention,
                            'payment_escrow' => $contracthisOldContract->payment_escrow,
                            'financial_guarantees' => $contracthisOldContract->financial_guarantees,
                            'currency_conversion' => $contracthisOldContract->currency_conversion,

                            // Reminder Value
                            'reminder_first_alert' => $contracthisOldContract->reminder_first_alert,
                            'reminder_first_alertMeOn' => $contracthisOldContract->reminder_first_alertMeOn,
                            'reminder_first_alert_repeats' => $contracthisOldContract->reminder_first_alert_repeats,
                            'reminder_second_alert' => $contracthisOldContract->reminder_second_alert,
                            'reminder_second_alertMeOn' => $contracthisOldContract->reminder_second_alertMeOn,
                            'reminder_second_alert_repeats' => $contracthisOldContract->reminder_second_alert_repeats,
                            'reminder_escalation_alert' => $contracthisOldContract->reminder_escalation_alert,
                            'reminder_escalation_alertMeOn' => $contracthisOldContract->reminder_escalation_alertMeOn,
                            'reminder_escalation_alert_repeats' => $contracthisOldContract->reminder_escalation_alert_repeats,

                            'rules_id' => $contracthisOldContract->rules_id,

                            'custom_fields_data' => $contracthisOldContract->custom_fields_data,
                            'contract_attachment' => $contracthisOldContract->contract_attachment,
                            'contract_attachment_filename' => $contracthisOldContract->contract_attachment_filename,
                            'created_by' => $contracthisOldContract->created_by ? $contracthisOldContract->created_by : 1,
                            'substatus' => $contract_sub_status
                        ]);
                    }

                    $messageUpdate = "Contract Unlinked Successfully";
                    $successType = true;
                } else {
                    $messageUpdate = "Invalid Request";
                    $successType = false;
                }
            } else {
                $messageUpdate = "Invalid Request";
                $successType = false;
            }

        return response()->json(['success' => $successType, 'message' => $messageUpdate], $messageCode);
    }

    public function getFile($con, $loc, $contype, $conid, $constat, $filename)
    {
        return Storage::disk('contracts')->response("$con/$loc/$contype/$conid/$constat/$filename");
    }
    
    
    public function getCloudFile($con)
    {
        $fileUrl = fileViewUrl($con);
        
        return view('contract::contract.cloudFiles', compact('fileUrl'));
    }

    public function getRemiderEmails()
    {

        $contracts = Contract::select("*")->orderBy('id', 'desc')->where('status', 1)->get();

        $contracts = $this->availableContracts($contracts, true);

        $contractIds = [];

        $today = date("d-m-Y");

        foreach ($contracts as $con) {
            $contractIds[] = $con['id'];
        }

        $alertCols = [
            'reminder_[alertType]_alert_[after]',
            'reminder_[alertType]_alertMeOn_[after]',
            'reminder_[alertType]_alert_repeats_[after]',
        ];

        $alertTypes = ['first', 'second', 'escalation', 'escalation|_after'];
        $alertColumns = [];

        $finalAlerts = [];
        $alertTypGroup = [];

        foreach ($alertTypes as $alTyp) {
            $befAfter = explode('|', $alTyp);
            $individualAlert = [];
            foreach ($alertCols as $alCol) {
                $alCol = str_replace('[alertType]', $befAfter[0], $alCol);
                $alCol = str_replace('_[after]', $befAfter[1] ?? '', $alCol);
                $finalAlerts[] = $alCol;
                //$individualAlert[] = $alCol;
            }
            $alertColumns[] = $befAfter[0] . ($befAfter[1] ?? '');
            //$alertTypGroup[] = $individualAlert;
        }



        $emailReminders = Contract::select("*")->whereIn('id', $contractIds)->get()->map(function ($task) use ($finalAlerts) {
            foreach ($finalAlerts as $fiAl) {
                $task->$fiAl = decryptString($task->$fiAl, $fiAl);
            }
            $task->reminder_enable = decryptString($task->reminder_enable, 'reminder_enable');
            $task->renewal_type = decryptString($task->renewal_type, 'renewal_type');
            return $task;
        });

        $emailReminderSettings = ReminderSettings::where('reminder_severity', 'medium')->get()
            ->map(function ($task) use ($finalAlerts) {
                foreach ($finalAlerts as $fiAl) {
                    $task->$fiAl = decryptString($task->$fiAl, $fiAl);
                }
                return $task;
            });


        $remaindersFinalEmail = [];
        foreach ($emailReminders as $erem) {
            $remainderDataArr = [];
            //$remaindersArr = 
            foreach ($alertTypes as $key => $alTyp) {
                $befAfter = explode('|', $alTyp);
                $alType = $befAfter[0];
                $afterTxt = ($befAfter[1] ?? ''); //."___".$erem->id;
                $remaindersArr[$alType . $afterTxt] = '0';
                $firstComDate = $erem["contract_end_date"];
                $finalComDate = $erem["contract_end_date"];
                $remDefSettings = $emailReminderSettings[0] ?? false;
                if ($remDefSettings) {
                    $remRenewalDate = $remDefSettings["reminder_{$alType}_alert{$afterTxt}"];
                    $remAlertmeOn = $remDefSettings["reminder_{$alType}_alertMeOn{$afterTxt}"];
                    $remAlertRepeats = $remDefSettings["reminder_{$alType}_alert_repeats{$afterTxt}"];
                }
                if ($erem->reminder_enable == 'off') {
                    $remRenewalDate = $erem["reminder_{$alType}_alert{$afterTxt}"];
                    $remAlertmeOn = $erem["reminder_{$alType}_alertMeOn{$afterTxt}"];
                    $remAlertRepeats = $erem["reminder_{$alType}_alert_repeats{$afterTxt}"];
                }
                if ($remRenewalDate == 'Renewal Date') {
                    if ($erem['renewal_type'] == 'automaticrenewal') {
                        if ($erem["auto_renewal_date"] && !empty($erem["auto_renewal_date"])) {
                            $firstComDate = $erem["auto_renewal_date"];
                        }
                    } else {
                        if ($erem["manual_renewal_date"] && !empty($erem["manual_renewal_date"])) {
                            $firstComDate = $erem["manual_renewal_date"];
                        }
                    }
                }

                $fisrtComDays = explode(" ", $remAlertmeOn)[0];
                $fisrtComBeAft = explode(" ", $remAlertmeOn)[2];

                $fisrtComRepeat = $remAlertRepeats;

                if (!$fisrtComDays || $fisrtComDays == '') {
                    $fisrtComDays = 15;
                }



                if (strlen($firstComDate) > 0 && strtotime($firstComDate) > 0) {

                    if ($fisrtComBeAft != 'after') {
                        if (strpos($firstComDate, "/") > -1) {
                            $tempfirstComDateArray = explode("/", $firstComDate);
                            $tempfirstComDate = $tempfirstComDateArray[0] . '-' . $tempfirstComDateArray[1] . '-' . $tempfirstComDateArray[2];
                            $finalComDate = date('d-m-Y', strtotime('-' . $fisrtComDays . ' day', strtotime($tempfirstComDate)));
                        } else {
                            $finalComDate = date('d-m-Y', strtotime('-' . $fisrtComDays . ' day', strtotime($firstComDate)));
                        }
                    } else {
                        if (strpos($firstComDate, "/") > -1) {
                            $tempfirstComDateArray = explode("/", $firstComDate);
                            $tempfirstComDate = $tempfirstComDateArray[0] . '-' . $tempfirstComDateArray[1] . '-' . $tempfirstComDateArray[2];
                            $finalComDate = date('d-m-Y', strtotime('+' . $fisrtComDays . ' day', strtotime($tempfirstComDate)));
                        } else {
                            $finalComDate = date('d-m-Y', strtotime('+' . $fisrtComDays . ' day', strtotime($firstComDate)));
                        }
                    }

                    if (strtotime($today) >= strtotime($finalComDate) && strtotime($today) <= strtotime($firstComDate)) {
                        if (strtotime($today) == strtotime($firstComDate)) {
                            $remaindersArr[$alType . $afterTxt] = 'Today';
                        } elseif ($fisrtComRepeat == 'Daily') {
                            $remaindersArr[$alType . $afterTxt] = 'Daily';
                        } elseif ($fisrtComRepeat == 'Every 3 days') {
                            $diff = strtotime($today) - strtotime($finalComDate);
                            $diffDays = abs(round($diff / 86400));
                            if ($diffDays % 3 == 0) {
                                $remaindersArr[$alType . $afterTxt] = 'Every 3 Days';
                            }
                        } elseif ($fisrtComRepeat == 'Weekly') {
                            $diff = strtotime($today) - strtotime($finalComDate);
                            $diffDays = abs(round($diff / 86400));
                            if ($diffDays % 7 == 0) {
                                $remaindersArr[$alType . $afterTxt] = 'weekly';
                            }
                        } elseif ($fisrtComRepeat == 'Fortnightly') {
                            $diff = strtotime($today) - strtotime($finalComDate);
                            $diffDays = abs(round($diff / 86400));
                            if ($diffDays % 14 == 0) {
                                $remaindersArr[$alType . $afterTxt] = 'Fortnightly';
                            }
                        } elseif ($fisrtComRepeat == 'Monthly') {
                            $diff = strtotime($today) - strtotime($finalComDate);
                            $diffDays = abs(round($diff / 86400));
                            if ($diffDays % 30 == 0) {
                                $remaindersArr[$alType . $afterTxt] = 'Monthly';
                            }
                        }
                    }
                }
            }

            $remainderDataArr['contract_number'] = $erem->contract_unique_id;
            $remainderDataArr['start_date'] = $erem->fixed_date;
            $remainderDataArr['end_date'] = $erem->contract_end_date;
            $remainderDataArr['actions'] = url('contracts/' . $erem->id);
            $remainderDataArr['firstRemain'] = $remaindersArr['first'];
            $remainderDataArr['secondRemain'] = $remaindersArr['second'];
            $remainderDataArr['escalationRemain'] = $remaindersArr['escalation'];
            $remainderDataArr['escalationRemainAfter'] = $remaindersArr['escalation_after'];
            $remainderDataArr['ccmails'] = [];

            if ($remaindersArr['first'] != 0) {
                $userEmail = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))
                    ->where('id', $erem->owner)->first();
                if($userEmail){
                    $remaindersFinalEmail[$userEmail->Email][$erem->contract_unique_id] = $remainderDataArr;
                }
            }

            if ($remaindersArr['second'] != 0) {
                $approvers = json_decode($erem->rules_id);
                $emailIds = [];
                if (isset($approvers[0]) && $approvers[0]->approver) {
                    $emailIds = $this->collectApproverIdsFromJson($approvers[0]->approver);
                    $emailApprovers = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))
                        ->whereIn('id', $emailIds)->get()->pluck('Email')->toArray();
                    foreach ($emailApprovers as $eml) {
                        $remaindersFinalEmail[$eml][$erem->contract_unique_id] = $remainderDataArr;
                    }
                }
            }

            if ($remaindersArr['escalation'] != 0) {
                $approvers = json_decode($erem->rules_id);

                $emailIds = [];
                if (isset($approvers[0]) && $approvers[0]->approver) {
                    $emailIds = $this->collectApproverIdsFromJson($approvers[0]->approver);
                }

                $finalMergeArray = array_unique(array_merge([$erem->owner, $erem->signatory], $emailIds));
                $ownerSigAppEmails = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))
                    ->whereIn('id', $finalMergeArray)->get()->pluck('Email')->toArray();

                foreach ($ownerSigAppEmails as $eml) {
                    $remaindersFinalEmail[$eml][$erem->contract_unique_id] = $remainderDataArr;
                }
            }

            if ($remaindersArr['escalation_after'] != 0) {
                $approvers = json_decode($erem->rules_id);

                $emailIds = [];
                if (isset($approvers[0]) && $approvers[0]->approver) {
                    $emailIds = $this->collectApproverIdsFromJson($approvers[0]->approver);
                }

                $branchHeadMails = [];

                if ($erem->location_branch != '-') {
                    $branchHeadMails = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))
                        ->where(decrypt_datas('Role', 'AddUsers'), 'Branch Head')
                        ->whereIn('branchhead', $erem->location_branch ?? [])->get()->pluck('Email')->toArray();
                }

                $finalMergeArray = array_unique(array_merge([$erem->owner, $erem->signatory], $emailIds));

                $ownerSigAppEmails = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))
                    ->whereIn('id', $finalMergeArray)->get()->pluck('Email')->toArray();
                $finalMailArray = array_unique(array_merge($ownerSigAppEmails, $branchHeadMails));
                foreach ($finalMailArray as $eml) {
                    $remaindersFinalEmail[$eml][$erem->contract_unique_id] = $remainderDataArr;
                }
            }
        }
        $emailTrigger = new ContractNotificationController();


        if (env('enable_contract_reminders')) {
            $MailSent = $emailTrigger->sendEmail('', '', '', '', $remaindersFinalEmail, [], [], 'reminder');
        }

        return view('contract::contract.emailReminderList', compact('remaindersFinalEmail'));
    }

    public function reminderSettingsActions()
    {
        $remiderSettingsType = 'medium';
        $remiderSettings = ReminderSettings::where('reminder_severity', $remiderSettingsType)
            ->get()
            ->map(function ($task) {
                $task->reminder_first_alert = decryptString($task->reminder_first_alert, 'reminder_first_alert');
                $task->reminder_first_alertMeOn = decryptString($task->reminder_first_alertMeOn, 'reminder_first_alertMeOn');
                $task->reminder_first_alert_repeats = decryptString($task->reminder_first_alert_repeats, 'reminder_first_alert_repeats');
                $task->reminder_second_alert = decryptString($task->reminder_second_alert, 'reminder_second_alert');
                $task->reminder_second_alertMeOn = decryptString($task->reminder_second_alertMeOn, 'reminder_second_alertMeOn');
                $task->reminder_second_alert_repeats = decryptString($task->reminder_first_alertMeOn, 'reminder_second_alert_repeats');
                $task->reminder_escalation_alert = decryptString($task->reminder_escalation_alert, 'reminder_escalation_alert');
                $task->reminder_escalation_alertMeOn = decryptString($task->reminder_escalation_alertMeOn, 'reminder_escalation_alertMeOn');
                $task->reminder_escalation_alert_repeats = decryptString($task->reminder_escalation_alert_repeats, 'reminder_escalation_alert_repeats');
                $task->reminder_escalation_alert_after = decryptString($task->reminder_escalation_alert_after, 'reminder_escalation_alert_after');
                $task->reminder_escalation_alertMeOn_after = decryptString($task->reminder_escalation_alertMeOn_after, 'reminder_escalation_alertMeOn_after');
                $task->reminder_escalation_alert_repeats_after = decryptString($task->reminder_escalation_alert_repeats_after, 'reminder_escalation_alert_repeats_after');
                return $task;
            });
        $remiderSettings = $remiderSettings[0] ?? [];
        return view('contract::contract.remiderSettings', compact('remiderSettings', 'remiderSettingsType'));
    }

    public function reminderSettingsSave(Request $request)
    {

        //Created By Validation
        $owner_initiator = session()->get('contractSessionUser');

        $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
            ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
            ->first();
        if (!$initiatior_exists) {
            $invalid_owner_error = array('Invalid User Actions Please Contact Administrator');
            return redirect('/reminderSettings')->withErrors($invalid_owner_error)->withInput();
        }

        $reminderSettingsData = [
            'reminder_first_alert' => encryptString($request->input('Duration.Reminder.first.alertMe'), 'reminder_first_alert'),
            'reminder_first_alertMeOn' => encryptString($request->input('Duration.Reminder.first.alertMeDay') . ' ' . $request->input('Duration.Reminder.first.alertMePrior') . ' ' . $request->input('Duration.Reminder.first.alertMeType'), 'reminder_first_alertMeOn'),
            'reminder_first_alert_repeats' => encryptString($request->input('Duration.Reminder.first.repeats'), 'reminder_first_alert_repeats'),
            'reminder_second_alert' => encryptString($request->input('Duration.Reminder.second.alertMe'), 'reminder_second_alert'),
            'reminder_second_alertMeOn' => encryptString($request->input('Duration.Reminder.second.alertMeDay') . ' ' . $request->input('Duration.Reminder.second.alertMePrior') . ' ' . $request->input('Duration.Reminder.second.alertMeType'), 'reminder_second_alertMeOn'),
            'reminder_second_alert_repeats' => encryptString($request->input('Duration.Reminder.second.repeats'), 'reminder_second_alert_repeats'),
            'reminder_escalation_alert' => encryptString($request->input('Duration.Reminder.escalation.alertMe'), 'reminder_escalation_alert'),
            'reminder_escalation_alertMeOn' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay') . ' ' . $request->input('Duration.Reminder.escalation.alertMePrior') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType'), 'reminder_escalation_alertMeOn'),
            'reminder_escalation_alert_repeats' => encryptString($request->input('Duration.Reminder.escalation.repeats'), 'reminder_escalation_alert_repeats'),
            'reminder_escalation_alert_after' => encryptString($request->input('Duration.Reminder.escalation.alertMe_after'), 'reminder_escalation_alert_after'),
            'reminder_escalation_alertMeOn_after' => encryptString($request->input('Duration.Reminder.escalation.alertMeDay_after') . ' ' . $request->input('Duration.Reminder.escalation.alertMeAfter') . ' ' . $request->input('Duration.Reminder.escalation.alertMeType_after'), 'reminder_escalation_alertMeOn_after'),
            'reminder_escalation_alert_repeats_after' => encryptString($request->input('Duration.Reminder.escalation.repeats_after'), 'reminder_escalation_alert_repeats_after')
        ];
        $remiderSettings = ReminderSettings::where('reminder_severity', $request->input('Duration.Reminder.severity'))->first();

        if ($remiderSettings) {
            $remiderSettings->update($reminderSettingsData);
        } else {
            $reminderSettingsData['created_by'] = $initiatior_exists->id;
            $SaveReminderSettings = ReminderSettings::create($reminderSettingsData);
        }

        return redirect('/reminderSettings')->with('alert-class', 'alert-success')->with('message', 'Settings Updated');
    }

    public function approverInsertOnContractActions($appArr, $contractData, $needSignatoryApproval = false)
    {
        $nextAprroverEmail = "";
        $randNo = rand(0, 99999);
        if (is_array($appArr) && count($appArr) > 0) {
            $approval_type = $appArr[0]->approval_type;
            $approval_status = $appArr[0]->approval_status;
            $approvalArr = json_decode($appArr[0]->approver);
            $approvalUserExist = array_search(Helpers::userInfo()->email, array_column($approvalArr, 'email'));
            if ($approval_type == 'sequential' && $approval_status == 'required') {
                $approvalContracts = [];
                foreach ($approvalArr as $key => $appVal) {
                    $approver_id = $appVal->id;
                    $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                    if (isset($users[0])) {
                        $unique_id = $contractData->id . $randNo;
                        $statusPreApprvr = 'Negotiation';
                        $statusApprvr = 'Approval';
                        $subStatusApprvr = 'Pending Approval';
                        $flag = 1;
                        if ($approvalUserExist) {
                            if ($approvalUserExist <= $key) {
                                $statusPreApprvr = 'Approval';
                                $statusApprvr = 'approved';
                                $subStatusApprvr = 'Pending Approval';
                                $flag = 0;
                            }
                        }

                        $approvalContracts[] = [
                            'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                            'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                            'status' => encryptString($statusApprvr, 'status'),
                            'contract_id' => $contractData->id,
                            'orderval' => 0,
                            'unique_id' => $unique_id,
                            'flag' => $flag,
                            'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                        ];

                        $nextAprroverEmail = $users[0]->Email;
                        if (!$approvalUserExist || ($approvalUserExist == $key)) {
                            break;
                        }
                    }
                }

                //Add Signatory Record
                if ($needSignatoryApproval) {
                    if (count($approvalArr) == ($approvalUserExist + 1)) {

                        $signatory = $contractData->signatory;

                        $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $signatory)->get();

                        if (isset($users[0])) {
                            $username = $users[0]->Email;
                            $randNo = rand(0, 99999);
                            $orderval = 1;
                            $unique_id_new = $contractData->id . $randNo;

                            $nextAprroverEmail = $username;

                            $statusPreApprvr = 'Approval';
                            $statusApprvr = 'Signing';
                            $subStatusApprvr = 'Approved';
                            $approvalContracts[] = [
                                'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                'status' => encryptString($statusApprvr, 'status'),
                                'contract_id' => $contractData->id,
                                'orderval' => 0,
                                'unique_id' => $unique_id,
                                'flag' => !$contractData->signing_date ? '1' : '0',
                                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                            ];
                        }
                    }
                }
                
                foreach ($approvalContracts as &$row) {
                    $row = ApprovalContracts::prepareData($row);
                }
                
                ApprovalContracts::insert($approvalContracts);
                Contract::where('id', $contractData->id)->update(['contract_status' => $statusApprvr, 'substatus' => $subStatusApprvr]);
            }
        }

        return $nextAprroverEmail;
    }

    public function obligationDashboard()
    {
        $ContractObligations = ContractObligations::where('flag', 1)
            ->get();
        $ContractObligations->map(function ($oblig) use (&$count) {});
        $countData = $count[$task->status]++;
        // Fetching users with decrypted data
        $users = AddUsers::select(
            'id',
            decrypt_data('Salutation', 'AddUsers'),
            decrypt_data('FirstName', 'AddUsers'),
            decrypt_data('LastName', 'AddUsers')
        )->get();
        $contracts_list_all = Contract::select('contract_name', 'id')->where('status', 1)->get();
        $contracts_list = $this->availableContracts($contracts_list_all, true);
        return view('contract::contract.contractObligationDash', compact('ContractObligations', 'users', 'contracts_list'));
    }

    //obligation create and delete function

    public function addObligation(Request $request)
    {

        $obName =  $request->input('taskName');
        $task_status =  $request->input('task_status');
        $task_priority =  $request->input('task_priority');
        $task_type =  $request->input('task_type');
        $dueDate =  $request->input('dueDate');

        $recuringEndDate =  $request->input('recuringEndDate');
        $OnetimeDate =  $request->input('OnetimeDate');
        $recuring_ends_on =  $request->input('task_ends_on');
        $repeats =  $request->input('repeats');

        $contract_id =  $request->input('contract_id');
        $description =  $request->input('task_description');
        $task_ends_on =  $request->input('task_ends_on');
        $owner =  $request->input('owner');
        $signatory =  $request->input('signatory');
        $sliderName =  $request->input('sliderName');
        $task_id =  $request->input('task_id');
        $frequency =  $request->input('frequency');

        if ($frequency == 'Choose Frequency') {
            $frequency = 'none';
        }
        if ($sliderName != 'update') {
            ContractObligations::create([
                'obligation_name' => $obName,
                'description' => $description,
                'task_type' => $task_type,
                'priority' => $task_priority,
                'due_date' => $dueDate,
                'onetime_end_date' => $OnetimeDate,
                'recuring_due_date' => $recuringEndDate,
                'end_frequency' => $recuring_ends_on,
                'contract_id' => $contract_id,
                'repeats' => $repeats,
                'frequency' => $frequency,
                'status' => $task_status,
                'owner' => $owner,
                'reviewer' => $signatory,
                'flag' => 1,
            ]);
        } else {
            ContractObligations::where('id', $task_id)->update([
                'obligation_name' => $obName,
                'description' => $description,
                'task_type' => $task_type,
                'priority' => $task_priority,
                'due_date' => $dueDate,
                'onetime_end_date' => $OnetimeDate,
                'recuring_due_date' => $recuringEndDate,
                'end_frequency' => $recuring_ends_on,
                'contract_id' => $contract_id,
                'repeats' => $repeats,
                'frequency' => $frequency,
                'status' => $task_status,
                'owner' => $owner,
                'reviewer' => $signatory,
                'flag' => 1,
            ]);
        }


        return response()->json(['message' => 'successful!'], 200);
    }


    public function deleteObligation(Request $request)
    {

        // print_r($request->input('task_id'));die;

        $task_id =  $request->input('task_id');


        ContractObligations::where('id', $task_id)->update(['flag' => 0]);

        return response()->json(['message' => 'successful!'], 200);
    }

    public function getSignatoryApprovalRules(Request $request)
    {

        $locals = json_decode($request->location);
        
        $approval_user_column = "";
        $approval_user_suffix = "0";
        if ($request->input('contractMode') == 'new') {
            $approval_user_column = "approval_required_users";
        }
        
        if ($request->input('contractMode') == 'old') {
            $approval_user_column = "approval_required_users_legacy";
            $approval_user_suffix = 'legacy';
        }
        
        if($approval_user_column == ""){
            $messageCode = 200;
            $success = false;
            $approversArr = "Approval Rules/DOA Missing Please Add One";
        }else{
    
            $financialLimit = $this->financialLimit(
                ((int)($locals[0] ?? 0)) ?? 'null',
                $request->DepartmentType ?? 'null',
                $request->catgoeryType ?? 'null',
                $request->contractType ?? 'null',
                $request->value ?? 'null',
                $approval_user_column
            );
            
            
            $financialLimitDecoded = json_decode($financialLimit)[0] ?? [];
            
            $approversArr = [];
            if(!empty($financialLimitDecoded)){
                $signatory_data_decoded = ((array)json_decode($financialLimitDecoded->signatory)) ?? [];
        
        
                $approversArr['signat'] = ((array)($signatory_data_decoded['sign']))[$approval_user_suffix] ?? "";
                if(!empty($approversArr['signat'])){
                    $approversNotiArr = isset($signatory_data_decoded['notify']) ? ((array)$signatory_data_decoded['notify'])[0] : '';
                    $approversArr['notyfy'] = [''];
                    if($approversNotiArr != ''){
                        $approversArr['notyfy'] = [];
                        foreach($approversNotiArr as $appNoti){
                          $approversArr['notyfy'][] = explode(':', $appNoti)[0]; 
                        }
                    }
                    $messageCode = 200;
                    $success = true;
                }else{
                    $messageCode = 200;
                    $success = false;
                    $approversArr = 'Signatory';                
                }
            }else{
                    $messageCode = 200;
                    $success = false;
                    $approversArr = 'Signatory';                
            }
        }


        return response()->json(['success' => $success, 'message' => $approversArr], $messageCode);
    }


    public function convertWordToHtmlBuffer($docxFile, $addtional = [])
    {

        // Load the DOCX file
        $phpWord = PhpWordIOFactory::load($docxFile);


        // Convert to HTML
        $htmlWriter = PhpWordIOFactory::createWriter($phpWord, 'HTML');

        // Start output buffering
        ob_start();

        // Save the HTML content into the buffer
        $htmlWriter->save('php://output');

        // Get the HTML content from the buffer
        $buffer = ob_get_clean();
        //ob_end_clean();


        $buffer = str_replace('div style=', 'div class="pageClassDiv" style=', $buffer);

        $buffer = str_replace('<style>', '<style> @scope{', $buffer);

        $buffer = str_replace('</style>', '}</style>', $buffer);

        if (isset($addtional['images'])) {
            foreach ($addtional['images'] as $img) {
                $buffer .= "<img src='$img' alt='ONTRACT' />";
            }
        }

        // You can display the buffer directly or save it as needed
        return $buffer;
    }


    //External Access Check

    public function accessExContract(Request $request)
    {
        $exId = $request->contractToken;
        $message = "Oops! Your Link Expired";
        $messageClass = "warning";
        if ($request->exusername && $request->expassword && $request->contractToken) {
            $id_ = $this->checkExternalUser($request->contractToken, false);

            if ($id_ && $id_[1] == $request->exusername) {
                $chekCredentials = ExternalTempUser::select('email')
                    ->where('email', $request->exusername)
                    ->where('password', $request->expassword)
                    ->first();
                if ($chekCredentials) {
                    session()->put('contractSessionExUser', $request->exusername);
                    $message = "Successfully Validated Credentials...";
                    $messageClass = "success";
                } else {
                    $message = "Oops! Invalid Credentials";
                    $messageClass = "danger";
                }
            }else{
                $message = "Oops! Invalid Credentials";
                $messageClass = "danger";                
            }
        }

        return redirect("/contracts/external/approval/$exId")->with('message', $message)->with('alert-class', "alert-$messageClass");
    }
    //External Actions

    public function viewExContract(Request $request, $id)
    {

        $exId = $id;

        $id_ = $this->checkExternalUser($id, false);

        if (!$id_) {
            return view('contract::contract.externalAccess');
        } else {
            $id = $id_[0];
            $emailCheck = $id_[1];
            $tfaEnabled = $id_[2];
            if (!session()->get('contractSessionExUser') && $tfaEnabled == 1 || session()->get('contractSessionExUser') != $emailCheck) {
                return view('contract::contract.externalLogin', compact('exId'));
            }
            
            $this->crudUserActionLog($id, 'approval', 'ex-signing-email', 0, 1, $emailCheck, true);
        }

        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();

        $contractTypes = ContractType::get();


        $contractHistory = ContractHistory::where('id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetching users with decrypted data
        $users = AddUsers::select(
            'id',
            decrypt_data('Salutation', 'AddUsers'),
            decrypt_data('FirstName', 'AddUsers'),
            decrypt_data('LastName', 'AddUsers')
        )->get();

        // Attach user data to contract history
        foreach ($contractHistory as $history) {
            $user = $users->where('id', $history->created_by)->first();
            if ($user) {
                $history->user_name = $user;
            } else {
                $ukUserObject = new stdClass();
                $ukUserObject->Salutation = '';
                $ukUserObject->FirstName = $history->created_by > 0 ? 'External' : 'User';
                $ukUserObject->LastName = $history->created_by > 0 ? 'User' : 'InActive';
                $history->user_name = $ukUserObject; // In case no matching user is found
            }
        }


        if (isset($_GET['history']) && $_GET['history'] != "") {
            $contracts = ContractHistory::where('history_id', $_GET['history'])->first();
            $contractParty = ContractPartyDataHistory::where('history_id', $_GET['history'])->get();
        } else {
            $contracts = Contract::select('*')->where('id', $id)->first();
            $contractParty = ContractPartyData::where('custom_field_group_id', $id)->get();
        }

        $approvalsArr_ = ApprovalContracts::select('*')->where('contract_id', $id)->orderBy('id', 'DESC')/*->where('flag', 1)*/
            ->where('flag', '<>', -1)
            ->get()
            ->map(function ($task) {
                $task->username = decryptString($task->username, 'username');
                $task->status = decryptString($task->status, 'status');
                $task->previous_status = decryptString($task->previous_status, 'previous_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                $task->next_status = decryptString($task->next_status, 'next_status');
                $task->signed_png = $task->signed_png;
                return $task;
            })
            ->groupBy('unique_id')
            ->reverse();

        //Set Signed Symbol                                  
        $partySigned = [];
        if (in_array(strtolower($contracts->contract_status), ['executed', 'signing'])) {
            foreach ($approvalsArr_ as $key => $approvalsData) {
                if ($approvalsData[0]->button_text == 'external') {
                    if ($approvalsData[0]->signed_png !== null) {
                        $partySigned[] = true;
                    } else {
                        $partySigned[] = false;
                    }
                }
            }
        }


        $externalSigned = 0;

        foreach ($contractParty as $contractPart) {
            $entities = EntityMain::withoutGlobalScopes()->select('id', decrypt_data('Nameoftheentity', 'entity'))
                ->where('id', $contractPart->contract_party_id)
                ->first();

            if (isset($entities->Nameoftheentity)) {
                $contractPart->Nameoftheentity = $entities->Nameoftheentity;
            }

            $contractPart->signed = false;
            if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal') {

                if (in_array(true, $partySigned)) {
                    $contractPart->signed = true;
                }
                if (!isset($branchFirst)) {
                    $branchFirst = BranchUser::select(
                        'id',
                        decrypt_data('BranchName', 'branch'),
                        decrypt_data('branchstatus', 'branch'),
                        decrypt_data('Doorno', 'branch'),
                        decrypt_data('StreetName', 'branch'),
                        decrypt_data('AreaName', 'branch'),
                        decrypt_data('Landmark', 'branch'),
                        decrypt_data('PinCode', 'branch'),
                        decrypt_data('ContactNumber', 'branch'),
                        decrypt_data('branchheadname', 'branch'),
                        decrypt_data('departments', 'branch'),
                        decrypt_data('LegalName', 'branch')
                    )->where('id', $contractPart->contract_party_location_id)->first();


                    $contractPart->contract_party = $branchFirst;
                }
            }

            if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Intergroup') {

                if (isset($partySigned[$externalSigned])) {
                    $contractPart->signed = $partySigned[$externalSigned];
                }
                $externalSigned++;
            }
            if ($contractPart->contract_party_exe_id == !null && $contractPart->contract_party_type == 'External') {

                $contractParties =  ContractParties::select('*')->where('id', $contractPart->contract_party_exe_id)->get();

                $contractPart->contract_party_id_exe = $contractParties;
                //Set Signed Symbol

                if (isset($partySigned[$externalSigned])) {
                    $contractPart->signed = $partySigned[$externalSigned];
                }
                $externalSigned++;
                $contractPart->Nameoftheentity = decryptString($contractParties[0]->company_name, 'company_name');
            }
        }


        if (isset($contracts->catgoery_id)) {
            $Categoryname = ContractCategories::where('id', $contracts->catgoery_id)->first();
            $contracts->catgoery_id = $Categoryname->name;
        }

        if (isset($contracts->department_id)) {
            $EntityBusinessName = EntityBusiness::where('id', $contracts->department_id)->first();
            $contracts->department_identity = $contracts->department_id;
            $contracts->department_id = $EntityBusinessName->name ?? '';
        }

        if (isset($contracts->contract_type)) {
            $contracts->contract_type_id = $contracts->contract_type;
            $contracts->contract_type = ContractType::where('contract_type_id', $contracts->contract_type)->first()->contract_type;
        }



        $approvalsArr = ApprovalContracts::select('*')->where('contract_id', $id)->orderBy('id', 'DESC')/*->where('flag', 1)*/
            ->where('flag', -1)
            ->get()
            ->map(function ($task) {
                $task->username = decryptString($task->username, 'username');
                $task->status = decryptString($task->status, 'status');
                $task->previous_status = decryptString($task->previous_status, 'previous_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                $task->next_status = decryptString($task->next_status, 'next_status');
                return $task;
            })
            ->groupBy('unique_id')
            ->reverse();

        $approvalsAttach = ApprovalContracts::select('*')
            ->where('contract_id', $id)
            ->where('flag', 1)
            ->orderBy('id', 'desc') // Order by created_at in descending order
            ->get()
            ->map(function ($task) {
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                return $task;
            });

        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();
        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();



        $branchs = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $branchsAll = Branch::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $entities = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'))
            ->get();


        $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->get();

        $contractParties =  ContractParties::select('*')->get();

        $catego =  ContractCategories::select('*')->get();

        $ent = EntityBusiness::select('*')->get();

        if (isset($_GET['history'])) {
            $ContractPartyData = ContractPartyDataHistory::where('history_id', $_GET['history'])->get();
        } else {
            $ContractPartyData = ContractPartyData::where('custom_field_group_id', $id)->get();
        }


        $contractsold = Contract::select('*')->where('id', $id)->first();

        if ($contractsold) {
            $contractsoldothers = Contract::select('*')->where([
                ['catgoery_id', $contractsold->catgoery_id],
                ['department_id', $contractsold->department_id],
                ['contract_type', $contractsold->contract_type],
            ])->whereNot('id', $id)->get();
        }

        $contract_party_locations = ContractPartyData::where('custom_field_group_id', $contracts->id)->where('contract_party_type', 'internal')->pluck('contract_party_location_id');

        $contract_party_id = ContractPartyData::where('custom_field_group_id', $contracts->id)->where('contract_party_type', 'External')->pluck('contract_party_exe_id');

        $ContractPartyLocList = ContractPartyData::whereIn('contract_party_location_id', $contract_party_locations)->pluck('custom_field_group_id');

        $ContractPartyDataList = ContractPartyData::whereIn('contract_party_exe_id', $contract_party_id)->pluck('custom_field_group_id');


        $FinalContractList = $ContractPartyLocList->intersect($ContractPartyDataList);

        $contractspartsList = Contract::select('*')->whereIn('id', $FinalContractList)->where('id', '<>', $id)->get();

        $contractspartsList = $this->availableContracts($contractspartsList, true);


        //Get Parent Contracts
        $getParentContracts = "SELECT parentcontract FROM
(SELECT id,parentcontract,
       CASE WHEN id in ('" . $id . "') THEN @idlist := CONCAT(IFNULL(@idlist,''),',',parentcontract)
            WHEN FIND_IN_SET(id,@idlist) THEN @idlist := CONCAT(@idlist,',',parentcontract)
            END as checkId
FROM contracts
ORDER BY id DESC)T
WHERE checkId IS NOT NULL";

        $contractsparentListQuery = DB::select($getParentContracts);

        $parentContractArr = [];

        foreach ($contractsparentListQuery as $conpar) {
            $parentContractArr[] = $conpar->parentcontract;
        }

        $contractsparentList = Contract::select('*')->whereIn('id', $parentContractArr)->get();

        $contractsparentList = $this->availableContracts($contractsparentList, true);


        $contractParties =  ContractParties::select('*')->get();

        //Get Susequesnt Contracts
        $childsList = NULL;
        $finalListChild = [];


        if (count($parentContractArr) == 1 && $parentContractArr[0] == 0) {
            $parentContractArr[] = $id;
        }

        foreach ($parentContractArr as $parCon) {
            if ($parCon > 0) {
                $getSubSequesntContracts = "SELECT GROUP_CONCAT(lv SEPARATOR ',') as childList FROM (
                                   SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM contracts 
                                   WHERE FIND_IN_SET(parentcontract, @pv)) AS lv FROM contracts 
                                   JOIN
                                   (SELECT @pv:=" . $parCon . ") tmp
                                   ) a";

                $contractsSubSeqList = DB::select($getSubSequesntContracts);

                foreach ($contractsSubSeqList as $conSubSeq) {

                    if ($conSubSeq->childList != "" && $conSubSeq->childList !== NULL) {
                        $childsList .= $conSubSeq->childList;
                    }
                }

                $finalListChild = explode(",", $childsList);
            }
        }


        $contractsSubseqList = Contract::whereIn('id', $finalListChild)->where('id', '<>', $id)->where('status', 1)->get();

        // Required fields with labels
        $reqfieldsText = [
            'currency_value' => 'Contract Value',
            'payment_schedule' => 'Contract Value: Payment Schedule',
            'fixed_date' => 'Fixed Date',
            'contract_end_date' => 'Contract End Date',
            'termination_date' => 'Termination - Date',
            'signing_date' => 'Signing Date',
            'end_contract_type' => 'Contract End Date',
            'contract_priority' => 'Contract Priority'
        ];

        $reqFieldsOptions = [
            'text' => [
                'end_contract_type' => 'One time,Fixed Term,Evergreen',
                'contract_priority' => 'Low,Medium,High'
            ],
            'value' => [
                'end_contract_type' => 'onetimeContract,fixedTerm,evergreen',
                'contract_priority' => 'low,medium,high'
            ]
        ];
        $reqfieldsInpType = [
            'currency_value' => 'text',
            'payment_schedule' => 'text',
            'fixed_date' => 'date',
            'contract_end_date' => 'date',
            'termination_date' => 'date',
            'signing_date' => 'date',
            'end_contract_type' => 'radio',
            'contract_priority' => 'select'
        ];

        $reqfieldsInpField = [
            'currency_value' => 'approvalInps',
            'payment_schedule' => 'approvalInps',
            'fixed_date' => 'approvalInps',
            'contract_end_date' => 'approvalInps',
            'termination_date' => 'approvalInps',
            'signing_date' => 'approvalInps',
            'end_contract_type' => 'approvalInps',
            'contract_priority' => 'approvalInps'
        ];

        $reqfieldsInpEdit = [
            'currency_value' => false,
            'payment_schedule' => false,
            'fixed_date' => false,
            'contract_end_date' => false,
            'termination_date' => false,
            'signing_date' => false,
            'end_contract_type' => false,
            'contract_priority' => false
        ];

        $reqfieldsVal = [
            'fixed_date' => true,
            'contract_end_date' => true,
            'end_contract_type' => true
        ];

        // Dynamically add fields based on specific conditions
        $commencement_type = decryptString($contracts->commencement_type, 'commencement_type');
        $end_contract_type = decryptString($contracts->end_contract_type, 'end_contract_type');
        $contract_value = decryptString($contracts->currency_value, 'currency_value');
        $payment_schedule = decryptString($contracts->payment_schedule, 'payment_schedule');

        $reqfieldsVals = [
            'currency_value' => $contract_value,
            'payment_schedule' => $payment_schedule,
            'fixed_date' => $contracts->fixed_date,
            'contract_end_date' => $contracts->contract_end_date,
            'termination_date' => $contracts->termination_date,
            'signing_date' => $contracts->signing_date,
            'end_contract_type' => $end_contract_type,
            'contract_priority' => $contracts->contract_priority
        ];

        if ($commencement_type == 'FixedDate' && empty($contracts->fixed_date)) {
            $reqfieldsVal['fixed_date'] = false;
        }

        if ($end_contract_type == 'onetimeContract' && empty($contracts->contract_end_date)) {
            $reqfieldsVal['contract_end_date'] = false;
        }

        if ($end_contract_type == 'fixedTerm' && empty($contracts->contract_end_date)) {
            $reqfieldsVal['contract_end_date'] = false;
        }

        if ($end_contract_type == 'termination' && empty($contracts->termination_date)) {
            $reqfieldsVal['termination_date'] = false;
        }

        if ((strtolower($contracts->contract_status) == 'signing')) {
            $reqfieldsVal['signing_date'] = empty($contracts->signing_date) ? false : true;
            $reqfieldsVals['signing_date'] = 'signing_date';
            $reqfieldsInpEdit['signing_date'] = !$reqfieldsVal['signing_date'];
        }

        $skipValidationReqInps = ['negotiation', 'signing', 'approval'];
        $enableEditReqInps = ['negotiation', 'approval'];

        foreach ($customFields as $cusField) {
            if ($cusField->required == 1) {
                $customFieldReq = true;
                if (empty(dataCustomFields($contracts->id, $cusField->custom_field_id))) {
                    $customFieldReq = false;
                }
                $reqfieldsVal[$cusField->custom_field_id] = $customFieldReq;
                $reqfieldsText[$cusField->custom_field_id] = $cusField->field_name;
                $reqfieldsInpType[$cusField->custom_field_id] = $cusField->field_type;
                $reqfieldsVals[$cusField->custom_field_id] = dataCustomFields($contracts->id, $cusField->custom_field_id);
                $reqfieldsInpField[$cusField->custom_field_id] = 'customFields';
                $reqfieldsInpEdit[$cusField->custom_field_id] = true;
            }
        }

        if (!in_array(strtolower($contracts->contract_status), $skipValidationReqInps)) {
            $reqfieldsVal = [];
        }

        if (in_array(strtolower($contracts->contract_status), $enableEditReqInps)) {
            $reqfieldsInpEdit['fixed_date'] = true;
            $reqfieldsInpEdit['contract_end_date'] = true;
            $reqfieldsInpEdit['termination_date'] = true;
            if (env('enable_contract_priority')) {
                $reqfieldsVal['contract_priority'] = true;
            }
        }



        $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();

        $contractsSubseqList = Contract::select('*')->whereIn('id', $finalListChild)->where('id', '<>', $id)->where('status', 1)->get();

        $contractsSubseqList = $this->availableContracts($contractsSubseqList, true);

        $ContractObligations = ContractObligations::where('contract_id', $id)->where('flag', 1)
            ->get();

        return view('contract::contract.viewExDetailContract', compact('branchFirst', 'reqfieldsVal', 'reqfieldsText', 'reqfieldsVals', 'reqfieldsInpType', 'reqfieldsInpField', 'reqFieldsOptions', 'reqfieldsInpEdit'))
            ->with('exId', $exId)
            ->with('emailCheck', $emailCheck)
            ->with('accessData', $id_)
            ->with('contract', $contracts)
            ->with('contractPartyData', $contractParty)
            ->with('approvalsArr', $approvalsArr)
            ->with('ContractObligations', $ContractObligations);
    }

    public function OtpExSigningActions(Request $request)
    {
        $id = $request->input('contactId');

        $encId = $id;

        $id = $this->checkExternalUser($id);

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid Access'], 200);
        }

        $contracts = Contract::select('*')->where('id', $id)->get();

        if (count($contracts) == 0) {
            return response()->json(['message' => 'Invalid Contract'], 200);
        } else {
            $contracts = $contracts[0];
        }


        $signatoryusers = ExternalTempUser::select('email')->where('accessSlug', $encId)->get();

        if (isset($signatoryusers[0])) {

            $currentUserEmail = $signatoryusers[0]->email;

            $emailTrigger = new ContractNotificationController();

            $MailSent = $emailTrigger->sendEmail($contracts->id, '', '', $currentUserEmail, 'Signing Request OTP', '',  [], 'OTPSign');

            if ($MailSent) {
                return response()->json(['success' => true, 'message' => "OTP Sent To Email $currentUserEmail"], 200);
            } else {
                return response()->json(['success' => false, 'message' => 'OTP Not Sent Retry After Some Time'], 200);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Signatory Email Not Exist Please Contact Admin'], 200);
        }
    }

    public function OtpExApprovalActions(Request $request)
    {

        $id = $request->input('contactId');

        $encId = $id;

        $id = $this->checkExternalUser($id);

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid Access'], 200);
        }

        $contracts = Contract::select('*')->where('id', $id)->get();

        if (count($contracts) == 0) {
            return response()->json(['message' => 'Invalid Contract'], 200);
        } else {
            $contracts = $contracts[0];
        }

        $checkOTPExist = OtpActions::select("*")->where('otp_ref', $contracts->id)->where('otp_type', 'signing')->orderBy('id', 'DESC')->limit(1)->get();

        if (count($checkOTPExist) == 1 &&  $checkOTPExist[0]->status == 1) {
            $otpCreated = $checkOTPExist[0]->otp_number;
            if ($otpCreated == $request->input('nextOtp')) {
                $updateOtp = OtpActions::where(['id'=> $checkOTPExist[0]->id])->update(['status' => 0]);
                $htmlDoc = false;
                return response()->json(['success' => true, 'message' => 'OTP Verified Please Click Proceed Signing!', 'html' => $htmlDoc], 200);
            }
        } else if (count($checkOTPExist) == 1 && $checkOTPExist[0]->status == 2) {
            return response()->json(['success' => false, 'message' => 'Already OTP Verified Try Refresh'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Invalid OTP'], 200);
    }

    public function setUpExSigningActions(Request $request)
    {

        $id = $request->input('contactId');

        $currentSign = $request->input('currentSign');

        $encId = $id;

        $id = $this->checkExternalUser($id);

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid Access'], 200);
        }

        $contracts = Contract::select('*')->where('id', $id)->get();

        if (count($contracts) == 0) {
            return response()->json(['message' => 'Invalid Contract'], 200);
        } else {
            $contracts = $contracts[0];
        }
        if ($request->file('uploadsign')) {
            $files = $request->file('uploadsign');
        }

        $fileStoreController = fileStorageTypeController();

        $unlinkTempFile = false;
        $unlinkFiles = "";

        if (fileStorageType() == "Local") {
            $storedWordFile = base_path() . '/storage/app/' . $contracts->contract_attachment;
        } else {
            $file_name = 'doc_' . strtotime(date('y-m-d h:i:s')) . '.docx';

            $contentDocx = $fileStoreController->downloadUrl($contracts->contract_attachment, $file_name);

            $file_path = 'contracts/tempDocs/';

            $filePath = Storage::disk('local')->put($file_path . $file_name, $contentDocx);

            $storedWordFile = base_path() . '/storage/app/' . $file_path . $file_name;

            $unlinkFiles = $file_path . $file_name;

            $unlinkTempFile = true;
        }


        $htmlDoc = $this->convertWordToHtmlBuffer($storedWordFile, ['images' => [$currentSign]]);

        if ($unlinkTempFile && $unlinkFiles != "") {
            Storage::delete($unlinkFiles);
        }

        //$htmlDoc = false;
        return response()->json(['success' => true, 'message' => 'OTP Verified!', 'html' => $htmlDoc], 200);
    }

    public function contractExApprovals(Request $request, $externalReq=0)
    {

        $id = $request->input('contactId');
        
        $updateHistory = false;

        $currentSign = $request->input('currentSign');

        $encId = $id;

        $id_ = $this->checkExternalUser($id, false);

        if (!$id_) {
            return response()->json(['success' => false, 'message' => 'Invalid Access'], 200);
        }else{
            $id = $id_[0];
            $emailCheck = $id_[1];
        }

        $contracts = Contract::select('*')->where('id', $id)->get();

        if (count($contracts) == 0) {
            return response()->json(['message' => 'Invalid Contract'], 200);
        } else {
            $contracts = $contracts[0];
        }

        $buttonTxt = "";

        $indexId = $request->input('indexId');
        $shortDesc = $request->input('nextActionItem' . $indexId);
        $appId = $request->input('appId');
        $desc = $request->input('nextAction' . $indexId);
        $appType = $request->input('appType');
        $appDataStatus = $request->input('appStatus');
        $appPreStatus = $request->input('appPreStatus');
        $orderval = $request->input('orderval');
        $unique_id_old = $request->input('unique_id');
        $actionBtntext = $request->input('actionBtntext');
        $skipDocument = $request->input('skipDocument');
        $signPng = $request->input('signPng');
        $signPngLoc = $request->input('signPngLoc') ?? '-';
        $controller = fileStorageTypeController();

        //For Email
        $emailTrigger = new ContractNotificationController();
        $senattment = [];
        $senattment['filename'] = [];
        $senattment['filurl'] = [];


        $filesData = "";

        $filesDataName = "";

        $filesSupport = [];


        $skipFileMissingValidation = ['Negotiation', 'Approval'];

        if (strtolower($contracts->substatus) == 'pending approval' && $appDataStatus == 'Signing') {
            $skipFileMissingValidation[] = 'Signing';
        }


        if ($request->file('photos')) {
            $files = $request->file('photos');
            $filesType = $request->input('fileType');

            if (fileStorageType() == "Local" && !in_array($appDataStatus,  $skipFileMissingValidation)) {
                $contractFilePresent = 0;
                foreach ($files as $file) {

                    if ($filesType[$file->getClientOriginalName()] == 'contract') {
                        $contractFilePresent = 1;
                    }
                }


                if ($contractFilePresent == 0) {
                    return response()->json(['message' => 'Please Upload Contract Document'], 200);
                }
            }

            foreach ($files as $file) {

                if ($filesType[$file->getClientOriginalName()] == 'contract') {
                    // Add the file object to the filesData array
                    $filesData = $controller->storeFile($file, 'approvals', $id);
                    $filesDataName = file_name($file);

                    Contract::where(['id' => $id])->update([
                        'contract_attachment_filename' => $filesDataName,
                        'contract_attachment' => $filesData
                    ]);
                    
                    $updateHistory = true;

                    //For Mail
                    $senattment['filename'][] = $filesDataName;
                    $senattment['filurl'][] = $filesData;

                } else {
                    $fileObject = new stdClass();
                    $fileObject->path = $controller->storeFile($file, 'approvals', $id);
                    $fileObject->name = file_name($file);
                    $filesSupport[] = $fileObject;

                    $senattment['filename'][] = file_name($file);
                    $senattment['filurl'][] = $fileObject->path;
                }
            }
        } else {
            if (fileStorageType() == "Local" && !in_array($appDataStatus,  $skipFileMissingValidation) && $skipDocument == 'false') {
                return response()->json(['message' => 'Please Upload Contract Document'], 200);
            }
        }


        if (strtolower($appDataStatus) == 'signing' && strtolower($appPreStatus) == 'isigned') {
            $buttonTxt = "Signed on";
        } else {
            return response()->json(['message' => 'Invalid Request!'], 200);
        }

        $currentApproval = ApprovalContracts::find($appId);

        //For Revoke Write access
        $currentUserEmail = json_decode(decryptString($currentApproval->username, 'username'))->email;

        //For Add Editor Access Next Approver
        $nextAprroverEmail = "";

        ApprovalContracts::where('id', $appId)->update([
            'unique_id' => $unique_id_old,
            'orderval' => $orderval,
            'next_action_item' => encryptString($shortDesc, 'next_action_item'),
            'next_action_description' => encryptString($desc, 'next_action_description'),
            'approval_status' => encryptStringx($appType, 'approval_contracts.approval_status'),
            'attachments' => $filesData,
            'attachments_filename' => $filesDataName,
            'attachments_support' => $filesSupport,
            'signed_png' => $signPng,
            'flag' => 0,
            'updated_on' => date('Y-m-d H:i:s'),
            'updated_by' => json_encode(['email' => Helpers::userInfo()->email ?? $emailCheck, 'name' => Helpers::userInfo()->FirstName ?? ''])
        ]);

        $alertMessage = 'Sign Processed Successfully';

        if ($buttonTxt == "Signed on") {
            $contract_end_type = decryptString($contracts->end_contract_type, 'end_contract_type');
            $end_date_of_contract = $contracts->contract_end_date;

            $cur_date = date('Y-m-d');

            if ($contract_end_type == 'onetimeContract') {
                $contract_status = 'executed';
                if ($end_date_of_contract != '') {
                    if (strtotime($end_date_of_contract) > strtotime($cur_date)) {
                        $contract_sub_status = 'active';
                    } elseif (strtotime($cur_date) > strtotime($end_date_of_contract)) {
                        $contract_sub_status = 'completed';
                    }
                } else {
                    $contract_sub_status = 'active';
                }
            } elseif ($contract_end_type == 'evergreen') {
                $contract_status = 'executed';
                $contract_sub_status = 'active';
            } elseif ($contract_end_type == 'fixedTerm') {
                $contract_status = 'executed';
                if ($end_date_of_contract != '') {
                    if (strtotime($end_date_of_contract) > strtotime($cur_date)) {
                        $contract_sub_status = 'active';
                    } elseif (strtotime($cur_date) > strtotime($end_date_of_contract)) {
                        $contract_sub_status = 'expired';
                    }
                } else {
                    $contract_sub_status = 'active';
                }
            } else {
                $contract_status = 'executed';
                $contract_sub_status = 'active';
            }

            $contractMode = $contracts->contract_mode;

            $updateSigningArray = [];


            if ($contracts->signing_date) {
                $contract_status_ = 'executed';
                $contract_sub_status_ = 'active';
            }

            if ($contract_status_ == 'executed' && $contract_sub_status_ == 'active') {
                //Check Counter Parties 
                $counterParties = $contracts->contractPartyList->all();

                $partiesPos = [];

                $externalParties = [];
                $internalParties = [];
                $externalPartiesCount = 0;
                foreach ($counterParties as $parti) {
                    if ($parti->contract_party_type == 'External' && $parti->contract_party_exe_id == !null) {
                        $repDetails = $parti->partyDetailsEx->repDetails ?? null;
                        if ($repDetails && count($repDetails) > 0) {
                            $externalPartiesCount++;
                            foreach ($repDetails as $rep) {
                                $externalParties[] = [
                                    'email' => $rep->representative_email,
                                    'name' => decryptString($parti->partyDetailsEx->company_name, 'company_name'),
                                    'type' => 'external'
                                ];
                                break;
                            }
                        } else{
                            $externalPartiesCount++;
                            $externalParties[] = [
                                'email' => $parti->partyDetailsEx->company_email,
                                'name' => decryptString($parti->partyDetailsEx->company_name, 'company_name'),
                                'type' => 'external'
                            ];
                        }
                        $partiesPos[] = 'external';
                    }

                    if ($parti->contract_party_type == 'Internal') {
                        $entities = EntityMain::withoutGlobalScopes()->select('id', decrypt_data('Nameoftheentity', 'entity'))
                            ->where('id', $parti->contract_party_id)
                            ->first();
                        $iPartyname = '';
                        if (isset($entities->Nameoftheentity)) {
                            $iPartyname = $entities->Nameoftheentity;
                        }
                        $partiesPos[] = 'internal';
                        $internalParties[] = ['name' => $iPartyname];
                    }

                    if ($parti->contract_party_type == 'Intergroup') {
                        $partiesPos[] = 'intergroup';
                        if ($parti->contract_party_location_id == !null) {
                            $externalPartiesCount++;
                            $loc_id__ = $parti->contract_party_location_id;
                            $branchHeadMails = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))
                                //->where(decrypt_datas('Role', 'AddUsers'), 'Branch Head')
                                ->whereRaw("FIND_IN_SET($loc_id__, branchhead)")
                                ->first();
                            $externalParties[] = [
                                'email' => $branchHeadMails->Email,
                                'name' => $branchHeadMails->FirstName,
                                'type' => 'intergroup'
                            ];
                        }
                    }
                }

                $checkExternalPartySigned = ApprovalContracts::select('id', 'username', 'status', 'previous_status', 'contract_id', 'next_action_item', 'next_action_description', 'button_text', 'attachments', 'approval_status', 'updated_at', 'created_at', 'orderval', 'unique_id', 'flag')
                    ->where('contract_id', $id)
                    ->where('button_text', 'external')
                    ->get();

                $partySigned = 0;

                foreach ($checkExternalPartySigned as $signedEx) {
                    if ($signedEx->flag == 0) {
                        $partySigned++;
                    }
                }

                $alertMessage = 'Sign Updated Successfully';
                
                $this->crudUserActionLog($id, 'approval', 'external-signed', 0, 0, $emailCheck, false, '', $signPngLoc);

                if ($partySigned == 0) {
                    $contract_status_ = 'Signing';
                    $contract_sub_status_ = 'External';
                }

                if ($partySigned < $externalPartiesCount) {
                    $contract_status_ = 'Signing';
                    $contract_sub_status_ = 'External Partial';
                    $alertMessage = "Sign Updated Successfully. Pending With Other Parties Sign";
                }

                if ($partySigned == $externalPartiesCount) {
                    $contract_status_ = 'executed';
                    $contract_sub_status_ = 'active';
                    $alertMessage = 'All Parties Signed and Contract Executed';
                    $this->crudUserActionLog($id, 'contract', 'all-signed', 0, 0, null);
                }

                //Generate PDF When Fully Signed
                if ($contract_sub_status_ == 'active' && $contract_status_ == 'executed') {

                    $contractMode = encryptString('old', 'contract_mode');

                    $updateSigningArray['contract_mode'] = $contractMode;

                    //Generate And Store PDF Final
                    $storagePath = '/storage/app/';


                    $unlinkTempFile = false;
                    $unlinkFiles = "";
                    if(strtolower(pathinfo($contracts->contract_attachment_filename, PATHINFO_EXTENSION)) == 'docx'){
                        if (fileStorageType() == "Local") {
                            $storedWordFile = base_path() . '/storage/app/' . $contracts->contract_attachment;
                            $generatePdfPath = $controller->get_file_path($contracts->id) . '/';
                        } else {
    
                            $file_name = 'doc_' . strtotime(date('y-m-d h:i:s')) . '.docx';
    
                            $contentDocx = $controller->downloadUrl($contracts->contract_attachment, $file_name);
    
                            $file_path = 'contracts/tempDocs/';
    
                            $filePath = Storage::disk('local')->put($file_path . $file_name, $contentDocx);
    
                            $storedWordFile = base_path() . '/storage/app/' . $file_path . $file_name;
    
                            $unlinkFiles = $file_path . $file_name;
    
                            $unlinkTempFile = true;
    
                            $generatePdfPath = $controller->get_file_path($contracts->id);
                        }


                        $htmlDoc = $this->convertWordToHtmlBuffer($storedWordFile);
    
                        if ($unlinkTempFile && $unlinkFiles != "") {
                            Storage::delete($unlinkFiles);
                        }
                        //$htmlDoc = $this->convertWordToHtmlBuffer($storedWordFile);
                        $allPartySigned = ApprovalContracts::select('status', 'button_text', 'flag', 'signed_png')
                            ->where('contract_id', $id)
                            ->whereNotNull('signed_png')
                            ->get();
    
    
    
    
                        //Generate PDF and Save
                        if (count($allPartySigned) == count($counterParties)) {
    
                            $internalSigns = [];
                            $externalSigns = [];
                            foreach ($allPartySigned as $sgned) {
                                if ($sgned->button_text == 'external') {
                                    $externalSigns[] = $sgned->signed_png;
                                } else {
                                    $internalSigns[] = $sgned->signed_png;
                                }
                            }
                            //Setup Sign
                            $totalParties = count($counterParties);
                            $totalWidth = 80;
                            $signplaceHolder = "";
                            if ($totalParties > 0) {
                                $eachDivWidth = ($totalWidth / $totalParties) - 1;
                                $expartyNameCount = 0;
                                $inpartyNameCount = 0;
                                for ($i = 0; $i < $totalParties; $i++) {
                                    //Decide Internal or External
                                    $inExDecide = $partiesPos[$i];
    
                                    if ($inExDecide == 'internal') {
                                        $iPartyName_ = $internalParties[$inpartyNameCount]['name'];
                                        $forName = "For $iPartyName_<br/>";
                                        $signPng_ = array_shift($internalSigns);
                                        $inpartyNameCount++;
                                    } else {
    
                                        $partyName_ = $externalParties[$expartyNameCount]['name'];
                                        $forName = "For $partyName_<br/>";
                                        $signPng_ = array_shift($externalSigns);
                                        $expartyNameCount++;
                                    }
                                    $signImage = "$forName <img height='30' src='$signPng_' />";
                                    $signplaceHolder .= "<td style='width:$eachDivWidth%; text-align:center; border: none !important;'>$signImage</td>";
                                }
                            }
    
                            $htmlDoc = str_replace('</style>', '</style><footer><table style="width:100%; position:relative; border: none !important;" cellspacing="0" cellpadding="0" ><tr>' . $signplaceHolder . '</tr></table></footer>', $htmlDoc);
                        } else {
                            return response()->json(['success' => true, 'message' => 'Other Party Sign Pending'], 200);
                        }
                    
                        //$htmlDoc .= $this->getSignedHistory($id);
                        
                        $pdf = \PDF::loadView("contract::contract.signedPdf", ['htmlDoc' => $htmlDoc]);
    
                        $pdf->setPaper('A4', 'portrait');
                        
                        $pdf->render();
                        
                        $output_temp = $pdf->output();
                        
                        $file_temp_path = 'contracts/tempDocs/';
                        
                        $file_temp_name = 'signed_temp'.$contracts->contract_unique_id.'.pdf';
                        
                        $fileTempPath = Storage::disk('local')->put($file_temp_path . $file_temp_name, $output_temp);
                        
                        $generatedPdfDocumentFinalName = 'signed_'.$contracts->contract_unique_id.strtotime(date('d-m-y h:i:s')) . '.pdf';
                        
                        $doc_temp_path = base_path() . '/storage/app/' . $file_temp_path . $file_temp_name;                
                        
                        $doc_final_path = base_path() . '/storage/app/' . $file_temp_path . $generatedPdfDocumentFinalName;                
                        
                        $pdfTemp = new PdfSignerv1(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        
                        // Add first PDF
                        $pageCount1 = $pdfTemp->setSourceFile($doc_temp_path);
                        for ($i = 1; $i <= $pageCount1; $i++) {
                            $tplIdx = $pdfTemp->importPage($i);
                            $pdfTemp->AddPage();
                            $pdfTemp->useTemplate($tplIdx);
                        }
                        
                        
                        $pdfTemp->AddPage();
                        
                        $htmlHistoryDoc = $this->getSignedHistory($id);
                        
                        $pdfTemp->writeHTML($htmlHistoryDoc, true, false, true, false, ''); 
                        
                        $pdfTemp->output($doc_final_path, 'F');
                        
                        unlink($doc_temp_path);
                        
                        $output = file_get_contents($doc_final_path);
                        
                        unlink($doc_final_path);
                        
    
                        if (fileStorageType() == "Local") {
                            $generatedPdfDocumentFinal = $generatePdfPath . $generatedPdfDocumentFinalName;
                            file_put_contents(base_path() . $storagePath . $generatedPdfDocumentFinal, $output);
                        } else {
    
                            $filePathGenarated = 'contracts/tempDocs/';
    
                            $filePath = Storage::disk('local')->put($filePathGenarated . $generatedPdfDocumentFinalName, $output);
    
                            $storedWordFile = base_path() . '/storage/app/' . $filePathGenarated . $generatedPdfDocumentFinalName;
    
                            $generatedPdfDocumentFinal = $controller->storeContent(base_path() . '/storage/app/' . $filePathGenarated . $generatedPdfDocumentFinalName, $generatePdfPath, $generatedPdfDocumentFinalName);
    
                            if (strpos(strtolower($generatedPdfDocumentFinal), "error") !== false) {
                                return response()->json(['success' => false, 'message' => 'Pdf Not Generated Please Contact Admin'], 200);
                            }
                        }
                        $updateSigningArray['contract_attachment_filename'] = $generatedPdfDocumentFinalName;
                        $updateSigningArray['contract_attachment'] = $generatedPdfDocumentFinal;
                    }

                    $updateSigningArray['signing_date'] = date('Y-m-d');
                }
                
                if (strtotime($cur_date) > strtotime($end_date_of_contract) && $contract_sub_status_ == 'active') {

                    if( $contract_sub_status == 'active'){

                        if($contract_end_type == 'onetimeContract'){

                            $contract_sub_status = 'completed';

                        }

                        if($contract_end_type == 'fixedTerm'){

                            $contract_sub_status = 'expired';

                        }

                    }

                    

                    $contract_sub_status_ = $contract_sub_status;

                }
                
                $updateSigningArray['contract_status'] = $contract_status_;                
                $updateSigningArray['substatus'] = $contract_sub_status_;

                Contract::where(['id' => $id])->update($updateSigningArray);
                
                $updateHistory = true;

                $currentUser = ExternalTempUser::where(['accessSlug' => $encId])->update(['is_active' => 0]);

                //session()->invalidate();

                if ($contracts->parentcontract != 0 && $contract_status == 'executed') {
                    $parentContract = Contract::where(['id' => $contracts->parentcontract])->first();
                    Contract::where(['id' => $contracts->parentcontract])->update([
                        'contract_status' => 'executed',
                        'substatus' => $parentContract->renewtype == 'renew' ? 'renewed' : 'amended',

                    ]);
                }
                
                if($updateHistory){
                    $contracthisHistory = Contract::where('id', $id)->first()->toArray();
                    $contracthisHistory['created_by'] = '-1';
                    $contractHistoryCreated = ContractHistory::create($contracthisHistory, ['except' => ['created_at', 'updated_at', 'contract_party_list', 'contract_name']]);
                    $contractPartyHistory = ContractPartyData::where('custom_field_group_id', $id)->get()->toArray();
                    
                    $fianlContractPartyHistory = [];
                    if(!empty($contractPartyHistory)){
                        foreach($contractPartyHistory as $cph){
                            $cph['history_id'] = $contractHistoryCreated->id;
                            $fianlContractPartyHistory[] = $cph;
                        }
                    }
                    if(!empty($fianlContractPartyHistory)){
                        $contractPartyHistoryCreated = ContractPartyDataHistory::insert($fianlContractPartyHistory); 
                    }
                }
            }
        }

        return response()->json(['success' => true, 'message' => $alertMessage], 200);
    }


    public function documentExViewer(Request $request, $conid)
    {

        $id = $conid;

        $currentSign = $request->input('currentSign');

        $encId = $id;

        $id = $this->checkExternalUser($id);

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid Access'], 200);
        }

        $ContractsFinal = Contract::select('*')->where('id', $id)->get();

        $contractFileName = $ContractsFinal[0]->contract_attachment_filename;

        $filename = $contractFileName;
        $file_url = fileViewUrl($ContractsFinal[0]->contract_attachment, false);
        $filepath = $ContractsFinal[0]->contract_attachment;
        $extFile = pathinfo($filename, PATHINFO_EXTENSION);

        $showInGoogleDocs = ['doc', 'docx'];

        if (fileStorageType() != "Local") {
            $getUrl = get_google_drive_doc_link($contractFileName, $ContractsFinal[0]->contract_attachment, 'view', 'test');
            if (!str_contains($getUrl, '/invalidfile')) {
                $docAlertBox = Helpers::getDocumentDisplaySection($getUrl);
                return $docAlertBox;
            } else {
                return '<div class="alert alert-danger mx-2">Invalid User/File Access</div>';
            }
        }

        $file_data = [
            [
                'label' => __('Label'),
                'value' => "Value"
            ]
        ];
        $file_data = [
            [
                'label' => __('Label'),
                'value' => "Value"
            ]
        ];

        return LaravelFileViewer::show($filename, $filepath, $file_url, $file_data);
    }

    public function checkExternalUser($userEnc, $onlyId = true, $wholeData=false)
    {


        $today = date('Y-m-d');

        $linkAvailable = ExternalTempUser::select('email', 'accessExpiryDate', '2FA', 'id', 'opened', 'is_active')
            ->where('accessSlug', $userEnc)
            //->where('is_active', 1)
            ->whereDate('accessExpiryDate', '>', $today)
            ->first();

        if (!$linkAvailable) {
            return false;
        }
        
        //Update Mail Opened
        if($linkAvailable['opened'] == 0){
            $mailOpenedData = [];
            $mailOpenedData['opened'] = 1;
            $mailOpenedData['opened_date'] = date('Y-m-d H:i:s');
            $mailOpenedData['ip_details'] = json_encode(['ip' => $_SERVER['REMOTE_ADDR']]);
            $currentUser = ExternalTempUser::where(['accessSlug' => $userEnc])->update($mailOpenedData);
        }        

        $tempExDatas = decryptString($userEnc, 'externalApproval');

        $tempExData = explode('||', $tempExDatas);

        if (isset($tempExData[0])) {
            $contracts = Contract::select('*')->where('id', $tempExData[0])->get();
            if (count($contracts) == 1) {
                if($wholeData){
                    return $linkAvailable;
                }
                if (!$onlyId) {
                    $tfaEnabled = $linkAvailable['2FA'];
                    array_push($tempExData, $tfaEnabled);
                    array_push($tempExData, $linkAvailable['is_active']);
                    return $tempExData;
                }
                return $tempExData[0];
            }
        }

        return false;
    }

    public function wordDocumentReaderActions($contract, $replaceText = false, $modifyFile = false, $readWord = false)
    {


        //Replace Texts Inside Document

        $fileStorageController =  fileStorageTypeController();

        $extFile = pathinfo($contract->contract_attachment_filename, PATHINFO_EXTENSION);

        $fileExist = true;

        if (fileStorageType() == "Local") {
            $relativePathFile = base_path() . '/storage/app/' . $contract->contract_attachment;
            if (!Storage::exists($contract->contract_attachment)) {
                $fileExist = false;
            }
        } else {

            $file_name = $contract->contract_attachment_filename;

            $contentDocx = $fileStorageController->downloadUrl($contract->contract_attachment, $file_name);

            if (strpos(strtolower($contentDocx), 'invalid') !== false) {
                $fileExist = false;
            } else {
                $file_path = 'contracts/tempDocs/';

                $filePath = Storage::disk('local')->put($file_path . $file_name, $contentDocx);

                $relativePathFile = base_path() . '/storage/app/' . $file_path . $file_name;
            }
        }

        $allowedDocs = ['doc', 'docx'];

        //Allow Only Docx
        if (in_array($extFile, $allowedDocs)) {
            if ($replaceText && $modifyFile && $fileExist) {
                // For Replace Text After Saved Docs
                $phpWord_process = new \PhpOffice\PhpWord\TemplateProcessor($relativePathFile);

                $allCustomVars = CustomVarDocs::where('status', 1)->get();

                $dataFetchedArray = $this->dataFetchForCustomVars($contract);

                $templateVars = [];
                //echo "<pre>";
                //print_r($allCustomVars);
                
                //print_r($dataFetchedArray['contractparty']);
                
                //die;

                foreach ($allCustomVars as $cusVars) {
                    $replaceText = $dataFetchedArray[$cusVars->var_table][$cusVars->var_field] ?? $cusVars->var_var;
                    $inVar = $cusVars->var_var;
                    $outVar = preg_replace('/^\$\{(.+)\}$/', '$1', $inVar);
                    $templateVars[$outVar] = $replaceText;
                }
                

                $phpWord_process->setValues($templateVars);

                $phpWord_process->saveAs($relativePathFile);

                if (fileStorageType() != "Local") {

                    $file_name = $contract->contract_attachment_filename;

                    $currentPath = $fileStorageController->get_file_path($contract->id);

                    $updateDocument = $fileStorageController->storeContent($relativePathFile, $currentPath, $file_name);
                    if ($updateDocument !== false) {
                        Contract::where(['id' => $contract->id])->update([
                            'contract_attachment' => $updateDocument,
                            'contract_attachment_filename' => $file_name
                        ]);
                    }
                }
            } else if ($readWord && $fileExist) {

                //For Read And Extract Word Texts
                $phpWord = PhpWordIOFactory::load($relativePathFile);

                // Create the HTML writer
                $writer = PhpWordIOFactory::createWriter($phpWord, 'HTML');

                // Capture the output into a variable
                ob_start();
                $writer->save('php://output');
                $htmlContent = ob_get_clean();

                // Now $htmlContent contains the HTML string
                //echo $htmlContent; // or use it in any other way  

                $categorys = ClausesCategory::where('category_group', 'title')->get()->toArray();

                echo "<pre>";


                $allKeysFound = $this->searchWordsFromString($htmlContent, $categorys, 'category_name');

                print_r($allKeysFound);
                die;
                // Iterate over all sections in the document
                foreach ($phpWord->getSections() as $section) {

                    //print_r($section);
                    // Iterate over all elements in the section
                    $elements = $section->getElements();
                    foreach ($elements as $element) {
                        // Check if the element is a TextRun (commonly used for runs of text)
                        if (get_class($element) === 'PhpOffice\PhpWord\Element\TextRun') {
                            // Extract text from TextRun elements
                            foreach ($element->getElements() as $textElement) {
                                if (get_class($textElement) === 'PhpOffice\PhpWord\Element\Text') {
                                    //echo $textElement->getText() . "\n";
                                    if ($replaceText) {
                                        $textElement = $this->replaceWordText($textElement, $textElement->getText(), $contract);
                                    }
                                    echo "<b>Text----> </b>" . $textElement->getText() . "<br/>";

                                    if (method_exists($textElement, 'getFontStyle')) {
                                        $style = $textElement->getFontStyle();
                                        if ($style && method_exists($style, 'isBold') && $style->isBold()) {
                                            echo "BOLD TEXT: " . $textElement->getText() . "\n";
                                        }
                                    }

                                    $paragraphStyle = $textElement->getParagraphStyle();
                                    // Only proceed if a style is set and it's a heading
                                    if ($paragraphStyle && method_exists($paragraphStyle, 'getStyleName')) {
                                        $styleName = $paragraphStyle->getStyleName();
                                        echo 'Style Name--->' . $styleName . "<br/>";

                                        if (stripos($styleName, 'Heading') === 0) {
                                            // Collect the full paragraph text
                                            $text = '';
                                            foreach ($textElement->getElements() as $child) {
                                                if ($child instanceof Text) {
                                                    $text .= $child->getText();
                                                }
                                            }
                                            echo "Heading Found: $text (Style: $styleName)\n";
                                        }
                                    }
                                }

                                if (method_exists($textElement, 'getStyle')) {
                                    echo "styleExist";
                                }
                            }
                        }
                        // You can also check for other types of elements like Paragraph or Table
                        if (get_class($element) === 'PhpOffice\PhpWord\Element\Paragraph') {
                            foreach ($element->getElements() as $subElement) {
                                if (get_class($subElement) === 'PhpOffice\PhpWord\Element\Text') {
                                    if ($replaceText) {
                                        $subElement = $this->replaceWordText($subElement, $subElement->getText(), $contract);
                                    }
                                    echo "<b>Paragraph----> </b>" . $subElement->getText() . "<br/>";
                                }
                            }
                        }
                        if (get_class($element) === 'PhpOffice\PhpWord\Element\Table') {
                            foreach ($element->getRows() as $row) {
                                foreach ($row->getCells() as $cell) {
                                    if ($replaceText) {
                                        $cell = $this->replaceWordText($cell, $cell->getText(), $contract);
                                    }
                                    echo "<b>Table----> </b>" . $cell->getText() . "<br/>";
                                }
                            }
                        }
                    }
                }
            }

            if (fileStorageType() != "Local" && $fileExist) {
                Storage::delete($relativePathFile);
            }
        }else if(strtolower($extFile) == 'pdf'){
            $text = SpatiePdf::getText($relativePathFile);
            
            dd($text);            
        }

    }

    public function replaceWordText($element = "", $text, $contract, $wordElm = true)
    {

        $allCustomVars = CustomVarDocs::where('status', 1)->get();

        $dataFetchedArray = $this->dataFetchForCustomVars($contract);

        foreach ($allCustomVars as $cusVars) {

            if (strpos($text, $cusVars->var_var) !== false) {
                $replaceText = $dataFetchedArray[$cusVars->var_table][$cusVars->var_field] ?? $cusVars->var_var;
                $newText = str_replace($cusVars->var_var, $replaceText, $text);
                if ($wordElm) {
                    $element->setText($newText); // Set the updated text
                } else {
                    $text = $newText;
                }
            }
        }

        if ($wordElm) {
            return $element;
        } else {
            return $text;
        }
    }

    //Build Contract Data Array For Custom Vars
    public function dataFetchForCustomVars($contract)
    {
        $dataFetchedArray = [];
        
        // Extract credit days from confidentialityagreement JSON (if present)
        $credit_days = null;
        $credit_limit = null;
        if (!empty($contract->confidentialityagreement)) {
            $conf = @json_decode($contract->confidentialityagreement, true);
            if (is_array($conf) && isset($conf['credit_days'])) {
                $credit_days = $conf['credit_days'];
            }
            if (is_array($conf) && isset($conf['credit_limit'])) {
                $credit_limit = $conf['credit_limit'];
            }
        }
        // Attach a friendly property for templates
        $contract->creditdays = $credit_days;
        $contract->creditlimit = $credit_limit;

        // Build HTML snippets for confidentialityagreement multi-row fields
        $conf = [];
        if (!empty($contract->confidentialityagreement)) {
            $conf = @json_decode($contract->confidentialityagreement, true) ?: [];
        }

        // Attach confidentialityagreement values as key/value properties
        $contract->bank_guarantee = $conf['bank_guarantee'] ?? null;
        $contract->coc_ip = $conf['coc_ip'] ?? null;
        $contract->coc_op = $conf['coc_op'] ?? null;
        $contract->communication_protocol = $conf['communication_protocol'] ?? null;
        // Employees/Dependants: provide as key/value array (selection + lists)
        $contract->employees_dependants = [
            'selection' => $conf['employees_dependants'] ?? null,
            'employees' => $conf['employees'] ?? null,
            'dependants' => $conf['dependants'] ?? null,
        ];

        // Sponsors: provide rows (s_no, key => value) instead of HTML table
        $sponsorsRows = [];
        $rawSponsors = $conf['sponsors'] ?? [];
        if (!empty($rawSponsors) && is_array($rawSponsors)) {
            $sno = 1;
            foreach ($rawSponsors as $s) {
                if (is_object($s)) $s = (array)$s;
                if (is_array($s)) {
                    $sponsorsRows[] = [
                        's_no' => $sno,
                        'name' => $s['name'] ?? '',
                        'sublimit' => (string)($s['sublimit'] ?? ''),
                        'validity' => (string)($s['validity'] ?? ''),
                    ];
                } else {
                    $sponsorsRows[] = ['s_no' => $sno, 'name' => (string)$s, 'sublimit' => '', 'validity' => ''];
                }
                $sno++;
            }
        }

        // Attach structured rows for templates / replacements
        $contract->sponsors_rows = $sponsorsRows;

        // Also provide a plain-text representation (line-break separated) for use in PHPWord replacements
        $sponsorsLines = [];
        foreach ($sponsorsRows as $row) {
            $parts = [];
            if (isset($row['s_no'])) $parts[] = $row['s_no'] . '.';
            if (!empty($row['name'])) $parts[] = 'Name: ' . $row['name'];
            if (isset($row['sublimit']) && $row['sublimit'] !== '') $parts[] = 'Sub-limit: ' . $row['sublimit'];
            if (isset($row['validity']) && $row['validity'] !== '') $parts[] = 'Validity: ' . $row['validity'];
            if (!empty($parts)) $sponsorsLines[] = implode(' | ', $parts);
        }
        $contract->sponsors_text = implode("\n", $sponsorsLines);
        
        
        // Calculate contract period in days between fixed_date and contract_end_date
        $contractPeriodDays = null;
        if (!empty($contract->fixed_date) && !empty($contract->contract_end_date)) {
            try {
                $startDate = \Carbon\Carbon::parse($contract->fixed_date)->startOfDay();
                $endDate = \Carbon\Carbon::parse($contract->contract_end_date)->startOfDay();
                if ($endDate->greaterThanOrEqualTo($startDate)) {
                    $contractPeriodDays = $startDate->diffInDays($endDate);
                }
            } catch (\Throwable $e) {
                $contractPeriodDays = null;
            }
        }
        $contract->contract_period_days = $contractPeriodDays;        

        $dataFetchedArray['contracts'] = $contract;
        $customvarpartydata = [];
        $customvarpartycustomfielddata = [];
        $customvarcustomfielddata = [];
        $partyCount = 1;
        foreach ($contract->contractPartyList->all() as $parti) {
            if ($parti->contract_party_type == 'External' && $parti->contract_party_exe_id == !null) {
                
                $customvarpartydata['name' . $partyCount] = decryptString($parti->partyDetailsEx->company_name, 'company_name');
                $customvarpartydata['pan' . $partyCount] = decryptString($parti->partyDetailsEx->pan, 'pan');
                $customvarpartydata['gst' . $partyCount] = decryptString($parti->partyDetailsEx->gst, 'gst');
                
                
                $customFields = CustomFields::where('status', 1)->orderBy('order_id')->get();
                
                foreach($customFields as $cusFld){
                    if($cusFld->contract_type == 0){
                        $customvarpartycustomfielddata[$cusFld->field_name . $partyCount] = dataCustomFieldsParty($parti->contract_party_exe_id, $cusFld->custom_field_id);
                    }else{
                        $customvarcustomfielddata[$cusFld->field_name . $partyCount] = dataCustomFields($contract->id, $cusFld->custom_field_id);
                    }
                }
                
                //Set Rep Details
                $repDetails = $parti->partyDetailsEx->repDetails ?? null;
                if ($repDetails && count($repDetails) > 0) {
                    foreach ($repDetails as $rep) {
                        $customvarpartydata['repname' . $partyCount]       = $rep->representative_name ?? null;
                        $customvarpartydata['repemail' . $partyCount]      = $rep->representative_email ?? null;
                        $customvarpartydata['repdesignation' . $partyCount]= $rep->representative_designation ?? null;
                        $customvarpartydata['repcontact' . $partyCount]    = $rep->representative_contact ?? null;
                        $customvarpartydata['repnationality' . $partyCount]= $rep->representative_nationality ?? null;
                        $customvarpartydata['repbrs' . $partyCount]        = $rep->representative_brs ?? null;
                        $customvarpartydata['reppassport' . $partyCount]   = $rep->passport_number ?? null;
                        break; // Only first representative
                    }
                }                
                
                $response = $parti->partyDetailsEx;

                $addressParts = [];
                
                // Define the fields in desired order
                $fields = ['building_no', 'area_name', 'landmark', 'city', 'state', 'pincode', 'country'];
                
                // Filter out empty fields and build a one-line address
                $addressParts = array_filter(array_map(function($key) use ($response) {
                    if($key == 'state'){
                        $response[$key] = $response->stateDetails->name ?? '';
                    }
                    if($key == 'country'){
                        $response[$key] = $response->countryDetails->name ?? '';
                    }
                    return !empty($response[$key]) ? $response[$key] : null;
                }, $fields));
                
                // Combine all parts into a single line
                $oneLineAddress = implode(', ', $addressParts);

                $customvarpartydata['address' . $partyCount] = $oneLineAddress;
                $customvarpartydata['organization_type' . $partyCount] = ucfirst($response->organization_type ?? ($parti->partyDetailsEx->organization_type ?? ''));

                // Include escalation matrix values if present on party details
                $escalation = json_decode($parti->partyDetailsEx->escalation_matrix,true) ?? null;
                
                if (!empty($escalation) && is_array($escalation)) {
                    $customvarpartydata['escalation_count' . $partyCount] = count($escalation);
                    foreach ($escalation as $idx => $esc) {
                        $i = $idx + 1;
                        $customvarpartydata['escalation_name' . $partyCount] = $esc['name'] ?? (is_object($esc) ? ($esc->name ?? '') : '');
                        $customvarpartydata['escalation_designation' . $partyCount] = $esc['designation'] ?? (is_object($esc) ? ($esc->designation ?? '') : '');
                        $customvarpartydata['escalation_email' . $partyCount] = $esc['email'] ?? (is_object($esc) ? ($esc->email ?? '') : '');
                        $customvarpartydata['escalation_contact' . $partyCount] = $esc['contact'] ?? $esc['phone'] ?? (is_object($esc) ? ($esc->contact ?? $esc->phone ?? '') : '');
                    }
                }                

            } else if ($parti->contract_party_type == 'Intergroup' || $parti->contract_party_type == 'Internal') {
                
                $partyName = EntityMain::select(decrypt_data('Nameoftheentity', 'entity'))->where('id', $parti->contract_party_id)->first();
                
                //print_r(Companyprofile::select('*')->where('entityid', 1)->toSql());

                $customvarpartydata['name' . $partyCount] = $partyName->Nameoftheentity;
                
                
                if ($parti->contract_party_location_id == !null) {
                    $loc_id__ = $parti->contract_party_location_id;
                    $branchHeadMails = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))
                        ->whereRaw("FIND_IN_SET($loc_id__, branchhead)")
                        ->first();
                    if ($branchHeadMails) {
                        $customvarpartydata['repname' . $partyCount] = $branchHeadMails->Email;
                        $customvarpartydata['repemail' . $partyCount] = $branchHeadMails->FirstName;
                    }
                }                
                
                $response = $partyName->addressDetailsIn ?? [];
                
                if(count($response) > 0){
                    $addressParts = [];
                    
                    // Define the fields in desired order
                    $fields = ['buildingname', 'streetname', 'areaname', 'landmark', 'country', 'state', 'city', 'pincode'];
                    
                    // Filter out empty fields and build a one-line address
                    $addressParts = array_filter(array_map(function($key) use ($response) {
                        if($key == 'state'){
                            $response[$key] = $response->stateDetails->name ?? '';
                        }
                        if($key == 'country'){
                            $response[$key] = $response->countryDetails->name;
                        }
                        return !empty($response[$key]) ? $response[$key] : null;
                    }, $fields));
                    
                    // Combine all parts into a single line
                    $oneLineAddress = implode(', ', $addressParts);
    
                    $customvarpartydata['address' . $partyCount] = $oneLineAddress; 
                }
            }
            $partyCount++;
        }

        $dataFetchedArray['contractparty'] = $customvarpartydata;
        $dataFetchedArray['partycustomfields'] = $customvarpartycustomfielddata;
        $dataFetchedArray['customfields'] = $customvarcustomfielddata;

        return $dataFetchedArray;
    }

    public function replaceCharacterAndStylesWord($html)
    {
        $html = trim($html, '"');
        $html = str_replace('&amp;', 'and', $html);
        $html = str_replace('<br>', '<br/>', $html);

        for ($i = 1; $i <= 6; $i++) {
            $size = 22 - ($i - 1) * 2; // Example: H1 = 22pt, H2 = 20pt, ..., H6 = 12pt
            $pattern = "/<h$i>(.*?)<\/h$i>/i";
            $replacement = "<p><strong><span style=\"font-size:{$size}pt;\">$1</span></strong></p>";
            $html = preg_replace($pattern, $replacement, $html);
        }

        return $html;
    }

    public function moveFilesToOtherStorage($fromFileType = 'Local', $id = 0)
    {

        if ($id == 0) {
            $contracts = Contract::select('*')->where('status', 1)->whereNotNull('contract_attachment_filename')->get();
        } else {
            $contracts = Contract::select('*')->where('id', $id)->whereNotNull('contract_attachment_filename')->get();
        }

        foreach ($contracts as $cons) {

            $contractIs = $cons->id;

            if ($cons->fileMoved == 0) {
                $controller = fileStorageTypeController();
                $generateDocPath = $controller->get_file_path($contractIs);
                if ($fromFileType) {
                    $finalPath = base_path() . "/storage/app/" . $cons->contract_attachment;
                }
                $finalFilePathName = $controller->storeContent($finalPath, $generateDocPath, $cons->contract_attachment_filename);


                //var_dump(strpos(strtolower($finalFilePathName), 'error'));
                if (strpos(strtolower($finalFilePathName), 'error') === false) {
                    Contract::where('id', $cons->id)->update(['contract_attachment' => $finalFilePathName, 'fileMoved' => 1]);
                    echo "File Uploaded to Cloud for Contract ($contractIs) " . $finalFilePathName . " <br/>";
                } else {
                    echo "File Uploaded Error for Contract ($contractIs) ---> " . $finalFilePathName . " <br/>";
                }
            } else {
                echo "File Already Uploaded to Cloud for Contract ($contractIs) {$cons->contract_attachment} <br/>";
            }
        }
    }


    //Words Search Method
    public function searchWordsFromString($messageToSearch, $keysToSearch, $tableKey)
    {

        $keysFoundListArray = [];

        foreach ($keysToSearch as $val) {
            array_push($keysFoundListArray, $val[$tableKey]);
        }

        $keysFromFromDb = implode(",", $keysFoundListArray);

        $keyWordRegEx = implode('|', explode(",", strtolower($keysFromFromDb)));
        //$percentageUpsiRegEx = '|[0-9]+%|[0-9]+ Percentage';
        //$amountUpsiRegEx = '|Rs+ (\d+)|₹+ (\d+)';
        $keysDetected = [];

        //preg_match_all(('/' . $keyWordUpsiRegEx . $percentageUpsiRegEx . $amountUpsiRegEx . '/i'), $messageToSearch, $matchedStrings);
        preg_match_all(('/' . $keyWordRegEx . '/i'), $messageToSearch, $matchedStrings);

        if (count($matchedStrings) > 0) {
            $keysDetected = array_intersect_key($matchedStrings[0], array_unique(array_map('strtolower', $matchedStrings[0])));
        }

        return $keysDetected;
    }
    
    public function getSignedHistory($contractId){
        /*
            Actions That Fire Log
            1.Store Contract
            2.Contract(EX)Approvals/SendContractReview - On Moved To Signing Status/Signed Internal As Well As External
            3.View(EX)Contract
            4.All Parties Signed
        */
        $logHistory = UserActionLog::where('group_id', $contractId)->get();
        
        //echo "<pre>";
        $actionTexts = [
            'contract' => [
                'create' => 'Contract Created by [name]([email]) on [date] in [ipdetails]',
                'all-signed' => 'Contract Document Process Completed on [date]',
            ],
            'approval' => [
                'signing-email-0' => 'Contract Document Emailed to [name]([email]) on [date]',
                'signing-email-1' => 'Contract Document Email Viewed By [name]([email]) on [date] in [ipdetails][location]',
                'internal-signed' => 'Contract Document Signed By [name]([email]) on [date] in [ipdetails][location]',
                'ex-signing-email-0' => 'Contract Document Emailed to External [name]([email]) on [date]',
                'ex-signing-email-1' => 'Contract Document Email Viewed By [name]([email]) on [date] in [ipdetails][location]',
                'external-signed' => 'Contract Document Signed By [name]([email]) on [date] in [ipdetails][location]',
            ],
        ];
        

        $finalLogHtml = '';
        //History Builder
        foreach($logHistory as $lhis){
            $actionNameKey = $lhis->action_name;
            $bothViewSendEmail = '';
            if(strpos($actionNameKey, 'signing-email') !== false){
                if($lhis->status == 1){
                    $bothViewSendEmail = $actionNameKey;
                    $actionNameKey .= "-0";
                }else{
                    $actionNameKey .= "-".$lhis->status;
                }
            }
            
            $finalString = $actionTexts[$lhis->action_type][$actionNameKey];
            $finalLogHtml .= $this->stringReplaceForSignLog($finalString,$lhis);
            if($bothViewSendEmail != ''){
                $finalLogHtml .= $this->stringReplaceForSignLog($actionTexts[$lhis->action_type][$bothViewSendEmail.'-1'],$lhis, false);
            }
            
        }
        
        return $finalLogHtml;

    }
    
    public function stringReplaceForSignLog($finalString, $lhis,  $takeUpdateDate=true){
        
            $finalString = str_replace('[name]', $lhis->actioner_name, $finalString);
            $finalString = str_replace('[email]', $lhis->actioner_id, $finalString);
            $dateString = $lhis->created_at;
            if(!$takeUpdateDate){
               $dateString = $lhis->updated_at; 
            }
            $finalString = str_replace('[date]', $dateString, $finalString);
            $finalString = str_replace('[ipdetails]', "IP : ".json_decode($lhis->log_details)->ip ?? '', $finalString);
            $finalString = str_replace('[location]', " from Location: ".json_decode($lhis->log_details)->coords ?? '', $finalString);
            return $finalString."<br/><br/>";
    }
    
    public function emailOpenerActions(Request $request, $emailRequestToken=null){
        if($emailRequestToken){
            $emailTempData = $this->checkExternalUser($emailRequestToken, false, true);
            if ($emailTempData) {
                if($emailTempData['opened'] == 0){
                    $mailOpenedData = [];
                    $mailOpenedData['opened'] = 1;
                    $mailOpenedData['opened_date'] = date('Y-m-d H:i:s');
                    $mailOpenedData['ip_details'] = json_encode(['ip' => $_SERVER['REMOTE_ADDR']]);
                    $currentUser = ExternalTempUser::where(['accessSlug' => $emailRequestToken])->update($mailOpenedData);
                }
            }
        }
        // Set headers to serve a 1x1 transparent PNG
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output 1x1 transparent PNG
        $im = imagecreatetruecolor(1, 1);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transparent);
        imagesavealpha($im, true);
        imagepng($im);
        imagedestroy($im);        
    }
    
    
    public function misConfigAction(Request $request)
    {

        if(!session('misconfig')){
            return redirect('/contracts/list');
        }
        return view('noaccess.misconfig');
    }
    
    public function checkDuplicateContracts($locals, $request, $endContractDate, $bulkUpload = false, $updateContract=0){
        
        
        if(!$bulkUpload){
            $inpContractType = $request->input('BasicContract.contractType');
            $inpDepartmentType = $request->input('BasicContract.DepartmentType');
            $inpcatgoeryType = $request->input('BasicContract.catgoeryType');
            $inpfixedDate = $request->input('Duration.fixedDate');
            $inpeffectiveDate = $request->input('Duration.effectiveDate');
            $inpContractValue = $request->input('ContractValue.value');            
        }else{
            $inpContractType = $request['contractType'];
            $inpDepartmentType = $request['DepartmentType'];
            $inpcatgoeryType = $request['catgoeryType'];
            $inpfixedDate = $request['fixedDate'];
            $inpeffectiveDate = $request['effectiveDate'];
            $inpContractValue = $request['ContractValue'];  
            $party_external_partynames = ContractParties::pluck('id','company_name');
            
            $finalPartyNames = [];
            foreach($party_external_partynames as $key => $nmes){
                $finalPartyNames[decryptString($key, 'company_name')] = $nmes;
            }
            
            //$locals = (array)$locals;
        }
        
        $existingPartiesQuery = DB::table('contract_party_data as a')->select('a.custom_field_group_id');
        $totalParties = count($locals);
        $checkedAllparties = 0;
        

        foreach ($locals as $key => $partyLoc) {
            $prevTblAlias = 'a';
            $currTblAlias = 'a';
            $joinNeeded = false;
            if($key > 0){
                $prevTblAlias = strtolower(getAlphabet(($key)));
                $currTblAlias = strtolower(getAlphabet(($key+1)));
                $joinNeeded = true;
            }else{
                $checkedAllparties++;
            }
            if (isset($partyLoc['location']) && $partyLoc['location'] != "" && $partyLoc['mode'] == "Internal") {
                

                if($bulkUpload){
                    // $party1_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[9]])
                    // ->value('id');
                    $partyLoc['location'] = Branch::where(decrypt_datas('LegalName', 'branch'), $partyLoc['location'])
                    ->first()->id;
                }
                
               
                $existingPartiesQuery->where("$currTblAlias.contract_party_type", 'Internal');
                $existingPartiesQuery->where("$currTblAlias.contract_party_location_id", $partyLoc['location']);
            }
            if (isset($partyLoc['mode']) && $partyLoc['mode'] == "External" && isset($partyLoc['external_name'])) {
                
                if($bulkUpload){
                    $partyLoc['external_name'] = $finalPartyNames[$partyLoc['external_name']] ?? '';
                }
                
                $existingPartiesQuery->where("$currTblAlias.contract_party_type", 'External');
                $existingPartiesQuery->where("$currTblAlias.contract_party_exe_id", $partyLoc['external_name']);
            }
            if (isset($partyLoc['mode']) && $partyLoc['mode'] == "Intergroup" && isset($partyLoc['location_grp'])) {
                
                if($bulkUpload){
                    // $party1_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[9]])
                    // ->value('id');
                    $partyLoc['location_grp'] = Branch::where(decrypt_datas('LegalName', 'branch'), $partyLoc['location_grp'])
                    ->first()->id;
                }

                $existingPartiesQuery->where("$currTblAlias.contract_party_type", 'Intergroup');
                $existingPartiesQuery->where("$currTblAlias.contract_party_location_id", $partyLoc['location_grp']);
            }
            
            if($joinNeeded){
                $checkedAllparties++;
                $existingPartiesQuery->join("contract_party_data as $currTblAlias", "$prevTblAlias.custom_field_group_id", '=', "$currTblAlias.custom_field_group_id");
            }
        }

        $existingPartiesQuery->distinct();
        if($updateContract != 0){
            $existingPartiesQuery->where('a.custom_field_group_id','<>' ,$updateContract);
        }
        if($totalParties == $checkedAllparties){
            $existingPartiesResult = $existingPartiesQuery->pluck('a.custom_field_group_id');
        }else{
            $existingPartiesResult = [];
        }
        
        
        //sort($existingPartiesResult);
        
        
        $existingPartiesResult = $existingPartiesResult->sort();
        
        if(count($existingPartiesResult) > 0){
            
            $duplicateContract = false;
            
            $existingContractQuery = Contract::select('id','contract_unique_id', 'end_contract_type');
            $existingContractQuery->where("contract_type", '=', $inpContractType);
            $existingContractQuery->where("department_id", '=', $inpDepartmentType);
            $existingContractQuery->where("catgoery_id", '=', $inpcatgoeryType);
            if($inpfixedDate != null){
                $existingContractQuery->where("fixed_date", date('Y-m-d',strtotime($inpfixedDate)));
            }
            if($inpeffectiveDate != 'evergreen' && $endContractDate != null){
                $existingContractQuery->where("contract_end_date", date('Y-m-d',strtotime($endContractDate)));
            }   
            $existingContractQuery->whereIn('id', $existingPartiesResult);
            $existingContractResult = $existingContractQuery->get();

            foreach($existingContractResult as $existContract){
                $contractPartyCount = ContractPartyData::where('custom_field_group_id', $existContract->id)->count();
                if($contractPartyCount == $totalParties){
                    if (decryptString($existContract->end_contract_type, 'end_contract_type') == $inpeffectiveDate) {
                        $duplicateContract = true;
                    }

                    if (decryptString($existContract->currency_value, 'currency_value') == $inpContractValue) {
                        $duplicateContract = true;
                    }else{
                       $duplicateContract = false; 
                    }
                }
                
                if($duplicateContract){
                    if(!$bulkUpload){
                        return $existContract;
                    }else{
                        $existConResp = new stdClass();
                        $existConResp->contract_unique_id = $existContract->contract_unique_id;                        
                        $existConResp->id = $existContract->id; 
                        return $existConResp;
                    }
                    break;
                }
            }

        }
        return false;
    }
    
    private function generateUniqueContractTempId($length = 8)
    {
        do {
            // Generate a random numeric ID (you can customize length)
            $randomId = random_int(pow(10, $length - 1), pow(10, $length) - 1);
    
            // Check if it already exists
            $exists = AiResponse::where('contract_temp_id', $randomId)->exists();
    
        } while ($exists);
    
        return $randomId;
    } 

    private function approvalActorIsOwnerOrAdmin($contract): bool
    {
        $userRole = session()->get('contractSessionUserRole');
        $userInfo = Helpers::userInfo();
        // if (($userRole === 'Admin' || $userRole === 'Super Admin') || strtolower((string)($userInfo->email ?? '')) === 'admin@legalitysimplified.com') {
        //     return true;
        // }

        $ownerEmail = null;
        if (!empty($contract->created_by)) {
            if (is_numeric($contract->created_by)) {
                $owner = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))->find($contract->created_by);
                $ownerEmail = $owner->Email ?? null;
            } else {
                $cb = @json_decode($contract->created_by, true);
                $ownerEmail = $cb['email'] ?? $contract->created_by;
            }
        }
  
        return !empty($ownerEmail) && strtolower((string)$ownerEmail) === strtolower((string)($userInfo->email ?? ''));
    }

    private function approvalDecryptStatus($value): string
    {
        try {
            return strtolower(trim((string)decryptString($value, 'approval_status')));
        } catch (\Throwable $e) {
            return strtolower(trim((string)$value));
        }
    }

    private function approvalDecryptUsernameToArray($value): array
    {
        try {
            $decrypted = decryptString($value, 'username');
            $decoded = @json_decode($decrypted, true);
            if (is_array($decoded)) return $decoded;
            return ['email' => $decrypted, 'name' => $decrypted];
        } catch (\Throwable $e) {
            $decoded = @json_decode($value, true);
            if (is_array($decoded)) return $decoded;
            return ['email' => $value, 'name' => $value];
        }
    }

    private function resolveContractPrimaryUserEmail(Contract $contract): ?string
    {
        $ownerEmail = null;

        if (!empty($contract->created_by)) {
            if (is_numeric($contract->created_by)) {
                $owner = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))->find($contract->created_by);
                $ownerEmail = $owner->Email ?? null;
            } else {
                $cb = @json_decode($contract->created_by, true);
                $ownerEmail = $cb['email'] ?? ($contract->created_by ?? null);
            }
        }

        if (empty($ownerEmail) && !empty($contract->signatory)) {
            $signatory = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))->find($contract->signatory);
            $ownerEmail = $signatory->Email ?? null;
        }

        return filter_var((string)$ownerEmail, FILTER_VALIDATE_EMAIL) ? $ownerEmail : null;
    }

    private function sendExecutedActiveNotifications(Contract $contract): void
    {
        if (
            strtolower((string)($contract->contract_status ?? '')) !== 'executed'
            || strtolower((string)($contract->substatus ?? '')) !== 'active'
            || !empty($contract->legal_finalized_notified_at)
        ) {
            return;
        }

        $contractCode = (string)($contract->contract_unique_id ?: $contract->id);
        $legalViewLink = url('/contracts/' . $contract->id . '/legal/view');
        $userViewLink = url('/contracts/' . $contract->id);

        $legalDesc = 'Contract unique ID ' . $contractCode . ' was executed and is now active. <br/>For reference, please open contract legal view link: ' . $legalViewLink;
        $userDesc = 'Your contract unique ID ' . $contractCode . ' was executed and is now active. <br/>Please use this link to view details: ' . $userViewLink;
        $normalizeEmail = function ($email): ?string {
            $normalized = strtolower(trim((string)$email));
            return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
        };

        $emailTrigger = new ContractNotificationController();
        $sentAny = false;
        $sentRecipients = [];

        $legalAdvisorEmail = $normalizeEmail($contract->legal_advisor_email ?? null);
        if (!empty($legalAdvisorEmail)) {
            try {
                $emailTrigger->sendEmail(
                    $contract->id,
                    $legalDesc,
                    'Executed Contract Notification',
                    $legalAdvisorEmail,
                    'Executed',
                    [],
                    [],
                    'notiMail'
                );
                $sentAny = true;
                $sentRecipients[$legalAdvisorEmail] = true;
            } catch (\Throwable $e) {
                \Log::warning('Failed legal advisor executed notification for contract ' . $contract->id . ': ' . $e->getMessage());
            }
        }

        $recipientMap = [];

        $primaryUserEmail = $this->resolveContractPrimaryUserEmail($contract);
        $primaryUserEmail = $normalizeEmail($primaryUserEmail);
        if (!empty($primaryUserEmail)) {
            $recipientMap[$primaryUserEmail] = $primaryUserEmail;
        }

        if (!empty($contract->signatory) && is_numeric($contract->signatory)) {
            $signatory = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))->find($contract->signatory);
            $signatoryEmail = $signatory->Email ?? null;
            $signatoryEmail = $normalizeEmail($signatoryEmail);
            if (!empty($signatoryEmail)) {
                $recipientMap[$signatoryEmail] = $signatoryEmail;
            }
        }

        $approvalRows = ApprovalContracts::select('username')
            ->where('contract_id', $contract->id)
            ->get();

        foreach ($approvalRows as $row) {
            $userData = $this->approvalDecryptUsernameToArray($row->username ?? '');
            $to = $normalizeEmail($userData['email'] ?? '');
            if (!empty($to)) {
                $recipientMap[$to] = $to;
            }
        }

        foreach (array_values($recipientMap) as $recipientEmail) {
            if (isset($sentRecipients[$recipientEmail])) {
                continue;
            }
            try {
                $emailTrigger->sendEmail(
                    $contract->id,
                    $userDesc,
                    'Contract Execution Update',
                    $recipientEmail,
                    'Executed',
                    [],
                    [],
                    'notiMail'
                );
                $sentAny = true;
                $sentRecipients[$recipientEmail] = true;
            } catch (\Throwable $e) {
                \Log::warning('Failed approver/signatory executed notification for contract ' . $contract->id . ' to ' . $recipientEmail . ': ' . $e->getMessage());
            }
        }

        if ($sentAny) {
            Contract::where('id', $contract->id)->update([
                'legal_finalized_notified_at' => now(),
            ]);
        }
    }

    private function notifyApprovalRows(int $contractId, $rows, string $status = 'Review'): void
    {
        $emailTrigger = new ContractNotificationController();
        $senattment = ['filename' => [], 'filurl' => []];
        foreach ($rows as $row) {
            $userData = $this->approvalDecryptUsernameToArray($row->username ?? '');
            $to = $userData['email'] ?? '';
            if (!empty($to) && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $emailTrigger->sendEmail($contractId, '', 'Approval Request', $to, $status, $senattment['filename'], $senattment['filurl'], 'notiMail');
            }
        }
    }

    private function getExternalRepresentativeOptions($contractId)
    {
        return ContractPartiesRepresentative::query()
            ->select(
                'contract_parties_representative.id as representative_id',
                'contract_parties_representative.representative_name',
                'contract_parties_representative.representative_email',
                'contract_parties_representative.representative_designation',
                'contract_parties_representative.parties_id as party_id',
                'contract_parties.company_name as party_name'
            )
            ->join('contract_parties', 'contract_parties.id', '=', 'contract_parties_representative.parties_id')
            ->join('contract_party_data', 'contract_party_data.contract_party_exe_id', '=', 'contract_parties.id')
            ->where('contract_party_data.custom_field_group_id', $contractId)
            ->where('contract_party_data.contract_party_type', 'External')
            ->where('contract_parties.status', 1)
            ->where('contract_parties_representative.status', 1)
            ->distinct()
            ->orderBy('contract_parties.company_name', 'asc')
            ->orderBy('contract_parties_representative.representative_name', 'asc')
            ->get()
            ->map(function ($row) {
                $party = trim((string)(decryptString($row->party_name, 'company_name') ?? ''));
                $name = trim((string)($row->representative_name ?? ''));
                $email = trim((string)($row->representative_email ?? ''));
                $designation = trim((string)($row->representative_designation ?? ''));
                return [
                    'party_id' => (int)($row->party_id ?? 0),
                    'party_name' => $party,
                    'representative_id' => (int)($row->representative_id ?? 0),
                    'representative_name' => $name,
                    'representative_email' => $email,
                    'designation' => $designation,
                    'label' => $party . ' - ' . $name . ' - ' . $email . ' - ' . $designation,
                ];
            })
            ->values();
    }

    private function resolveAutoNextFromRules($contract, $currentGroupId, $allApprovals): bool
    {
        try {
            $rulesRaw = $contract->rules_id ?? null;
            if (empty($rulesRaw)) return false;

            $rulesDecoded = is_string($rulesRaw) ? @json_decode(trim($rulesRaw)) : $rulesRaw;
            if (!is_array($rulesDecoded) && !($rulesDecoded instanceof \Traversable)) {
                $rulesDecoded = (array)$rulesDecoded;
            }
            if (empty($rulesDecoded) || !isset($rulesDecoded[0])) return false;

            $firstRule = $rulesDecoded[0];
            $groupsRaw = $firstRule->approver ?? ($firstRule['approver'] ?? null);
            if (is_string($groupsRaw)) {
                $groups = @json_decode($groupsRaw, true);
            } else {
                $groups = is_array($groupsRaw) ? $groupsRaw : [];
            }

            if (!is_array($groups) || empty($groups)) return false;

            $orderedGroupIds = collect($allApprovals)
                ->sortBy('orderval')
                ->pluck('unique_id')
                ->filter()
                ->unique()
                ->values();

            $idx = $orderedGroupIds->search($currentGroupId);
            if ($idx === false) return false;

            $groupRule = $groups[$idx] ?? null;
            if (!is_array($groupRule)) return false;

            return (int)($groupRule['auto_next_enabled'] ?? 0) === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function advanceNextApprovalGroup(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $contract = Contract::findOrFail($id);
            if (!$this->approvalActorIsOwnerOrAdmin($contract)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Only owner/admin can advance to next level.');
            }

            $gateEntry = ApprovalContracts::where('contract_id', $id)
                ->where('awaiting_owner_trigger', 1)
                ->orderBy('orderval', 'asc')
                ->first();

            if (!$gateEntry) {
                DB::rollBack();
                return redirect()->back()->with('error', 'No level is pending owner trigger.');
            }

            $gateGroupId = $gateEntry->unique_id;
            $gateMaxOrder = ApprovalContracts::where('contract_id', $id)->where('unique_id', $gateGroupId)->max('orderval');

            $hasActiveExternalPre = ApprovalContracts::where('contract_id', $id)
                ->where('stage_type', 'external_pre')
                ->where('flag', 1)
                ->exists();
            if ($hasActiveExternalPre) {
                DB::rollBack();
                return redirect()->back()->with('error', 'External pre-approver group is still active. Complete it before advancing.');
            }

            $hasUnexpectedActiveGroup = ApprovalContracts::where('contract_id', $id)
                ->where('unique_id', '!=', $gateGroupId)
                ->where('flag', 1)
                ->exists();
            if ($hasUnexpectedActiveGroup) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Another approval level is already active. Please refresh and retry.');
            }

            ApprovalContracts::where('contract_id', $id)->where('unique_id', $gateGroupId)->update(['awaiting_owner_trigger' => 0]);

            $nextGroupEntry = ApprovalContracts::where('contract_id', $id)
                ->where('orderval', '>', $gateMaxOrder)
                ->orderBy('orderval', 'asc')
                ->first();

            if (!$nextGroupEntry) {
                DB::rollBack();
                return redirect()->back()->with('error', 'No next level found to activate.');
            }

            $nextGroupId = $nextGroupEntry->unique_id;
            $nextGroupRows = ApprovalContracts::where('contract_id', $id)
                ->where('unique_id', $nextGroupId)
                ->orderBy('orderval', 'asc')
                ->get();

            if ($nextGroupRows->isEmpty()) {
                DB::rollBack();
                return redirect()->back()->with('error', 'No rows found in next level.');
            }

            $rowType = strtolower((string)($nextGroupRows->first()->approval_type_row ?? 'sequential'));
            $notifyRows = collect();

            if ($rowType === 'parallel') {
                ApprovalContracts::where('contract_id', $id)->where('unique_id', $nextGroupId)->update(['flag' => 1]);
                $notifyRows = ApprovalContracts::where('contract_id', $id)->where('unique_id', $nextGroupId)->where('flag', 1)->get();
            } else {
                ApprovalContracts::where('contract_id', $id)->where('unique_id', $nextGroupId)->update(['flag' => 0]);
                $firstToActivate = $nextGroupRows->first(function ($row) {
                    return $this->approvalDecryptStatus($row->approval_status ?? '') !== 'approved';
                });
                if ($firstToActivate) {
                    ApprovalContracts::where('id', $firstToActivate->id)->update(['flag' => 1]);
                    $notifyRows = collect([$firstToActivate]);
                }
            }

            $contract->update([
                'contract_status' => 'Review',
                'substatus' => 'In Review',
                'approval_gate_state' => 'none',
            ]);

            $this->notifyApprovalRows((int)$id, $notifyRows, 'Review');

            DB::commit();
            return redirect()->back()->with('success', 'Next level activated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to activate next level: ' . $e->getMessage());
        }
    }

    public function addPreapproverGroup(Request $request, $id)
    {
        $requestedRepresentativeIds = collect($request->input('representative_ids', []))
            ->map(function ($value) {
                return (int)$value;
            })
            ->filter(function ($value) {
                return $value > 0;
            })
            ->unique()
            ->values()
            ->all();

        $validator = Validator::make($request->all(), [
            'representative_ids' => 'required|array|min:1',
            'representative_ids.*' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if (empty($requestedRepresentativeIds)) {
            return redirect()->back()->with('error', 'Select at least one valid external representative.');
        }

        DB::beginTransaction();
        try {
            $contract = Contract::findOrFail($id);
            if (!$this->approvalActorIsOwnerOrAdmin($contract)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Only owner/admin can add pre-approvers.');
            }

            $gateEntry = ApprovalContracts::where('contract_id', $id)
                ->where('awaiting_owner_trigger', 1)
                ->orderBy('orderval', 'asc')
                ->first();

            if (!$gateEntry) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Pre-approver can be added only when waiting for owner trigger.');
            }

            if (strtolower((string)($gateEntry->stage_type ?? 'internal')) === 'external_pre') {
                DB::rollBack();
                return redirect()->back()->with('error', 'External pre-approver already added for this checkpoint. Use Send To Next Level.');
            }

            $hasActiveInternalGroup = ApprovalContracts::where('contract_id', $id)
                ->where('flag', 1)
                ->where(function ($query) {
                    $query->whereNull('stage_type')->orWhere('stage_type', '!=', 'external_pre');
                })
                ->exists();
            if ($hasActiveInternalGroup) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Cannot add pre-approver while an internal level is active.');
            }

            $representatives = ContractPartiesRepresentative::query()
                ->select(
                    'contract_parties_representative.id as representative_id',
                    'contract_parties_representative.representative_name',
                    'contract_parties_representative.representative_email',
                    'contract_parties_representative.representative_designation',
                    'contract_parties.company_name as party_name'
                )
                ->join('contract_parties', 'contract_parties.id', '=', 'contract_parties_representative.parties_id')
                ->where('contract_parties.status', 1)
                ->where('contract_parties_representative.status', 1)
                ->whereRaw('LOWER(contract_parties.party_type) = ?', ['external'])
                ->whereIn('contract_parties_representative.id', $requestedRepresentativeIds)
                ->get();

            if ($representatives->isEmpty()) {
                DB::rollBack();
                return redirect()->back()->with('error', 'No valid external representatives found.');
            }

            $gateGroupId = $gateEntry->unique_id;
            $gateMaxOrder = ApprovalContracts::where('contract_id', $id)->where('unique_id', $gateGroupId)->max('orderval');

            $nextGroupEntry = ApprovalContracts::where('contract_id', $id)
                ->where('orderval', '>', $gateMaxOrder)
                ->orderBy('orderval', 'asc')
                ->first();

            $insertAtOrder = $nextGroupEntry ? (int)$nextGroupEntry->orderval : ((int)$gateMaxOrder + 1);
            $rowsToInsert = $representatives->count();

            ApprovalContracts::where('contract_id', $id)
                ->where('orderval', '>=', $insertAtOrder)
                ->increment('orderval', $rowsToInsert);

            $runtimeGroupId = $id . rand(10000, 99999);
            $createdBy = ['email' => Helpers::userInfo()->email ?? 'External', 'name' => Helpers::userInfo()->FirstName ?? 'User'];
            $currentOrder = $insertAtOrder;
            $insertedRows = [];

            foreach ($representatives as $rep) {
                $name = trim((string)($rep->representative_name ?? ''));
                $email = trim((string)($rep->representative_email ?? ''));
                if (empty($email)) {
                    continue;
                }

                $row = ApprovalContracts::create([
                    'username' => encryptString(json_encode(['email' => $email, 'name' => $name]), 'username'),
                    'unique_id' => $runtimeGroupId,
                    'orderval' => $currentOrder,
                    'previous_status' => encryptString('Review', 'previous_status'),
                    'status' => encryptString('Pre Approval', 'status'),
                    'contract_id' => $id,
                    'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                    'approval_type_main' => 'sequential',
                    'approval_type_row' => 'parallel',
                    'approver_type_row' => 'Preapprover',
                    'group_key' => 'external_pre_' . $runtimeGroupId,
                    'stage_type' => 'external_pre',
                    'stage_origin' => 'runtime',
                    'auto_next_enabled' => 0,
                    'awaiting_owner_trigger' => 0,
                    'flag' => 1,
                    'created_by' => json_encode($createdBy),
                ]);
                $insertedRows[] = $row;
                $currentOrder++;
            }

            ApprovalContracts::where('contract_id', $id)->where('unique_id', $gateGroupId)->update(['awaiting_owner_trigger' => 0]);
            $contract->update([
                'contract_status' => 'Review',
                'substatus' => 'In Review',
                'approval_gate_state' => 'none',
                'preapprover_payload' => json_encode($representatives->values()),
            ]);

            $this->notifyApprovalRows((int)$id, collect($insertedRows), 'Review');

            DB::commit();
            return redirect()->back()->with('success', 'External pre-approver group added and activated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to add pre-approver group: ' . $e->getMessage());
        }
    }

    public function addDynamicGroupApprover(Request $request, $id, $groupId)
    {
        $validator = Validator::make($request->all(), [
            'approver_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $contract = Contract::findOrFail($id);

            if (!$this->approvalActorIsOwnerOrAdmin($contract)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Only owner/admin can add dynamic approvers.');
            }

            $finalStatuses = ['signing', 'executed', 'active', 'expired', 'terminated', 'completed'];
            if (in_array(strtolower((string)$contract->contract_status), $finalStatuses, true)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Dynamic approvers cannot be added in the current contract stage.');
            }

            $allRows = ApprovalContracts::where('contract_id', $id)
                ->where('flag', '<>', -1)
                ->orderBy('orderval', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $groupRows = $allRows->where('unique_id', $groupId)->values();

            if ($groupRows->isEmpty()) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Approval group not found.');
            }

            $groupFirst = $groupRows->first();
            if ((int)($groupFirst->dynamic_approver_enabled ?? 0) !== 1) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Dynamic approver is not enabled for this group.');
            }

            $groupStatuses = $groupRows->map(function ($row) {
                return $this->approvalDecryptStatus($row->approval_status ?? 'pending');
            })->values();

            if ($groupStatuses->isNotEmpty() && $groupStatuses->every(function ($status) {
                return in_array($status, ['approved', 'rejected'], true);
            })) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Cannot add approver to a completed approval group.');
            }

            $approver = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))
                ->where('id', (int)$request->input('approver_id'))
                ->first();

            if (!$approver || empty($approver->Email)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Selected approver is invalid.');
            }

            $newApproverEmail = strtolower(trim((string)$approver->Email));

            foreach ($groupRows as $row) {
                $status = $this->approvalDecryptStatus($row->approval_status ?? 'pending');
                try {
                    $existing = json_decode(decryptString($row->username, 'username'), true);
                    $existingEmail = strtolower(trim((string)($existing['email'] ?? '')));
                } catch (\Throwable $e) {
                    $existingEmail = '';
                }

                if ($status === 'pending' && !empty($existingEmail) && $existingEmail === $newApproverEmail) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'This approver is already pending in the selected group.');
                }
            }

            $groupType = strtolower((string)($groupFirst->approval_type_row ?? 'sequential'));
            $isGroupActive = $groupRows->contains(function ($row) {
                return (int)$row->flag === 1 && $this->approvalDecryptStatus($row->approval_status ?? 'pending') === 'pending';
            });

            $newFlag = ($groupType === 'parallel' && $isGroupActive) ? 1 : 0;

            $nextStatus = 'Review';
            $prevStatus = 'Review';
            try {
                $nextStatus = decryptString($groupFirst->status, 'status') ?: 'Review';
            } catch (\Throwable $e) {
                $nextStatus = 'Review';
            }
            try {
                $prevStatus = decryptString($groupFirst->previous_status, 'previous_status') ?: 'Review';
            } catch (\Throwable $e) {
                $prevStatus = 'Review';
            }

            $approverName = trim((string)($approver->FirstName ?? '')); 
            if (!empty($approver->LastName)) {
                $approverName = trim($approverName . ' ' . $approver->LastName);
            }

            $createdBy = ['email' => Helpers::userInfo()->email ?? 'External', 'name' => Helpers::userInfo()->FirstName ?? 'User'];

            $newRow = ApprovalContracts::create([
                'username' => encryptString(json_encode(['email' => $approver->Email, 'name' => $approverName]), 'username'),
                'unique_id' => $groupId,
                'orderval' => ((int)$allRows->max('orderval')) + 1,
                'previous_status' => encryptString($prevStatus, 'previous_status'),
                'status' => encryptString($nextStatus, 'status'),
                'contract_id' => $id,
                'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                'flag' => $newFlag,
                'approval_type_main' => $groupFirst->approval_type_main,
                'approval_type_row' => $groupFirst->approval_type_row,
                'approver_type_row' => $groupFirst->approver_type_row,
                'group_key' => $groupFirst->group_key,
                'stage_type' => $groupFirst->stage_type,
                'stage_origin' => $groupFirst->stage_origin,
                // Carry the grouped-flow discriminators so the new row participates in the
                // same stage's completion/activation checks (advanceGroupedApproval filters
                // on flow_type/stage_name/superseded). Omitting these previously left the
                // row invisible to the pre-approval flow (stage_name=null).
                'flow_type' => $groupFirst->flow_type,
                'stage_name' => $groupFirst->stage_name,
                'superseded' => 0,
                'file_permission' => $groupFirst->file_permission,
                'auto_next_enabled' => (int)($groupFirst->auto_next_enabled ?? 0),
                'awaiting_owner_trigger' => (int)($groupFirst->awaiting_owner_trigger ?? 0),
                'dynamic_approver_enabled' => 1,
                'created_by' => json_encode($createdBy),
            ]);

            // Keep group ordering deterministic by inserting the new row immediately after
            // the target group's last row, then resequence orderval for all active rows.
            $orderedRowIds = $allRows->pluck('id')->values()->all();
            $targetGroupRowIds = $groupRows->pluck('id')->values()->all();
            $insertAfterId = !empty($targetGroupRowIds) ? end($targetGroupRowIds) : null;

            $newOrderedIds = [];
            if ($insertAfterId === null) {
                $newOrderedIds[] = $newRow->id;
            }

            foreach ($orderedRowIds as $rowId) {
                $newOrderedIds[] = $rowId;
                if ($insertAfterId !== null && (int)$rowId === (int)$insertAfterId) {
                    $newOrderedIds[] = $newRow->id;
                }
            }

            if (!in_array($newRow->id, $newOrderedIds, true)) {
                $newOrderedIds[] = $newRow->id;
            }

            foreach ($newOrderedIds as $index => $rowId) {
                ApprovalContracts::where('id', $rowId)->update(['orderval' => $index + 1]);
            }

            if ($newFlag === 1) {
                $notifyStatus = ucfirst(strtolower((string)($contract->contract_status ?? 'Review')));
                $this->notifyApprovalRows((int)$id, collect([$newRow]), $notifyStatus);
            }

            try {
                $historyModel = new \App\Models\ContractHistory();
                $fillKeys = $historyModel->getFillable();
                $snapshot = array_intersect_key($contract->toArray(), array_flip($fillKeys));
                $snapshot['updated_by'] = json_encode($createdBy);
                \App\Models\ContractHistory::create($snapshot);
            } catch (\Throwable $e) {
                \Log::error('Failed to write contract history on dynamic approver add: ' . $e->getMessage());
            }

            DB::commit();
            return redirect()->back()->with('success', 'Dynamic approver added successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to add dynamic approver: ' . $e->getMessage());
        }
    }
    
    

    /**
     * Display the approval flow for a contract.
     */
    public function approvalFlow(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);

        // Check if user has access
        $contracts = $this->availableContracts(collect([$contract]), true);
        if (count($contracts) == 0) {
            return redirect()->back()->with('error', 'Access denied');
        }
        $contract = $contracts[0];

        // Get approvals
        $approvals = ApprovalContracts::select('*')->where('contract_id', $id)->orderBy('id', 'DESC')
            ->where('flag', '<>', -1)
            ->get()
            ->map(function ($task) {
                $task->username = decryptString($task->username, 'username');
                $task->status = decryptString($task->status, 'status');
                $task->previous_status = decryptString($task->previous_status, 'previous_status');
                $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                $task->approval_status = decryptString($task->approval_status, 'approval_status');
                $task->next_status = decryptString($task->next_status, 'next_status');
                // Decode username JSON
                $userData = json_decode($task->username, true);
                

                $task->approver_email = $userData['email'] ?? '';
                $task->approver_name = $userData['name'] ?? '';
                return $task;
            });

        // Determine current approval for the user
        $currentApproval = null;
        $isCurrentApprover = false;
        $userInfo = Helpers::userInfo();
        if ($userInfo) {
            $userEmail = strtolower($userInfo->email ?? '');
            foreach ($approvals as $approval) {
                $approverEmail = strtolower($approval->approver_email ?? '');
                if ($approval->flag == 1 && $approverEmail === $userEmail) {
                    $currentApproval = $approval;
                    $isCurrentApprover = true;
                    break;
                }
            }
        }

        $attachmentUrl = null;
        if (!empty($contract->contract_attachment)) {
            $attachmentUrl = asset('storage/' . $contract->contract_attachment);
        }

        $userInfo = Helpers::userInfo();
        $userCanGate = $this->approvalActorIsOwnerOrAdmin($contract);
        $waitingGateGroupIds = ApprovalContracts::where('contract_id', $id)
            ->where('awaiting_owner_trigger', 1)
            ->pluck('unique_id')
            ->filter()
            ->unique()
            ->values();
        $canAdvanceNext = $userCanGate && $waitingGateGroupIds->count() > 0;
        $externalRepresentativeOptions = $userCanGate ? $this->getExternalRepresentativeOptions($id) : collect();
        $dynamicApproverOptions = $userCanGate
            ? AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => (int)($user->id ?? 0),
                        'name' => trim(((string)($user->FirstName ?? '')) . ' ' . ((string)($user->LastName ?? ''))),
                        'email' => (string)($user->Email ?? ''),
                    ];
                })
                ->filter(function ($user) {
                    return !empty($user['email']);
                })
                ->values()
            : collect();

        return view('contract::contract.approvalFlow', compact('contract', 'approvals', 'currentApproval', 'isCurrentApprover', 'attachmentUrl', 'userInfo', 'userCanGate', 'waitingGateGroupIds', 'canAdvanceNext', 'externalRepresentativeOptions', 'dynamicApproverOptions'));
    }

    /**
     * Handle approval response (approve/reject).
     */
    public function approvalRespond(Request $request, $id, $approvalId)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $contract = Contract::findOrFail($id);
            $approval = ApprovalContracts::findOrFail($approvalId);

            if (empty($contract->contract_attachment)) {
                return redirect()->back()->with('error', 'Template not yet created');
            }

            // Only allow current active approver to act OR allow Admin/Super Admin to act on any approval
            $userRole = session()->get('contractSessionUserRole');
            $isAdmin = ($userRole === 'Admin' || $userRole === 'Super Admin') || (optional(Helpers::userInfo())->email ?? '') === 'admin@legalitysimplified.com';
            if ((int)$approval->flag !== 1 && !$isAdmin) {
                return redirect()->back()->with('error', 'This approval step is not active for you.');
            }

            $action = $request->input('action');
            $comments = $request->input('comments');
            // Check if this is the owner's first approval action and contract has pre-approval routing
            if ($action === 'approve' && $approval->approver_type_row === 'Owner' && $approval->orderval == 0) {
                $routingConfig = $this->getRoutingConfig($contract->rules_id);
                if ($routingConfig) {
                    // Contract has pre-approval routing. Mark the owner step approved
                    // then hand off to the pre-approval flow (invoked directly to
                    // avoid a GET redirect to the POST-only sendToPreApproval route).
                    try {
                        $ownerKey = 'approver_' . $approval->id;
                        try {
                            $decUsername = decryptString($approval->username, 'username');
                            $decArr = json_decode($decUsername, true);
                            $ownerKey = $decArr['email'] ?? $ownerKey;
                        } catch (\Throwable $e) {}
                        $approval->approval_status = encryptStringx('approved', 'approval_contracts.approval_status');
                        $approval->status = encryptString('Approved', $ownerKey);
                        $approval->flag = 0;
                        $approval->updated_on = date('Y-m-d H:i:s');
                        $approval->updated_by = json_encode(['email' => Helpers::userInfo()->email ?? 'User', 'name' => Helpers::userInfo()->FirstName ?? 'User']);
                        $approval->button_text = 'Sent to Pre-Approval on ' . date('d M Y H:i');
                        $approval->save();
                    } catch (\Throwable $e) {
                        Log::error('Failed to mark owner step before pre-approval: ' . $e->getMessage());
                    }
                    DB::commit();
                    return $this->sendToPreApproval($contract->id);
                }
            }
            // Prepare actor info for updated_by
            $updatedUser = ['email' => Helpers::userInfo()->email ?? 'External', 'name' => Helpers::userInfo()->FirstName ?? 'User'];

            // Capture contract field edits if approver provided them with the approval
            $startDate = $request->input('contract_start_date');
            $endDate = $request->input('contract_end_date');
            $contractValue = $request->input('contract_value');

            // If approver is allowed to edit and submitted values, apply them to contract when approving
            $canEditContract = false;
            $userRole = session()->get('contractSessionUserRole');
            $isAdmin = ($userRole === 'Admin' || $userRole === 'Super Admin') || (optional(Helpers::userInfo())->email ?? '') === 'admin@legalitysimplified.com';
            if ($isAdmin) $canEditContract = true;
            // Allow if user is active approver
            if ((int)$approval->flag === 1) $canEditContract = true;

            // Determine a stable key for encryption. Prefer approver email where available.
            try {
                $decUsername = decryptString($approval->username, 'username');
                $decArr = json_decode($decUsername, true);
                $usernameKey = $decArr['email'] ?? ($decUsername ?: ('approver_' . $approval->id));
            } catch (\Throwable $e) {
                $usernameKey = 'approver_' . $approval->id;
            }

            // If an admin is acting, use admin email as key when possible
            if ($isAdmin) {
                $adminEmail = optional(Helpers::userInfo())->email ?? null;
                if ($adminEmail) $usernameKey = $adminEmail;
            }

            if ($action === 'reject') {
                // Set to rejected and return to draft
                $approval->approval_status = encryptStringx('rejected', 'approval_contracts.approval_status');
                $approval->status = encryptString('Rejected', $usernameKey);
                $approval->next_action_description = encryptString($comments, $usernameKey);
                $approval->flag = 0;
                $approval->updated_on = date('Y-m-d H:i:s');
                // record who updated this approval
                $approval->updated_by = json_encode($updatedUser);
                // set a human-readable button label with timestamp
                $approval->button_text = 'Rejected on ' . date('d M Y H:i');
                $approval->save();

                $contract->update([
                    'contract_status' => 'Draft',
                    'substatus' => 'Initial Draft'
                ]);

                // Record contract history snapshot after status change
                try {
                    $historyModel = new \App\Models\ContractHistory();
                    $fillKeys = $historyModel->getFillable();
                    $snapshot = array_intersect_key($contract->toArray(), array_flip($fillKeys));
                    // include the new status/substatus explicitly
                    $snapshot['contract_status'] = $contract->contract_status;
                    $snapshot['substatus'] = $contract->substatus;
                    //$snapshot['updated_by'] = json_encode($updatedUser ?? ['email'=>Helpers::userInfo()->email ?? 'External','name'=>Helpers::userInfo()->FirstName ?? 'User']);
                    \App\Models\ContractHistory::create($snapshot);
                } catch (\Throwable $e) {
                    \Log::error('Failed to write contract history on reject: ' . $e->getMessage());
                }

                ApprovalContracts::where('contract_id', $contract->id)->where('id', '!=', $approval->id)->update(['flag' => 1]);

                DB::commit();
                return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('success', 'You have rejected the contract. It has been returned to the owner.');
            }

            // If contract fields were submitted and approver is allowed, update contract now
            if ($action === 'approve' && $canEditContract) {
                $updateContract = [];
                if (!empty($startDate)) $updateContract['fixed_date'] = $startDate;
                if (!empty($endDate)) $updateContract['contract_end_date'] = $endDate;
                if (!empty($contractValue)) $updateContract['currency_value'] = function_exists('encryptString') ? encryptString($contractValue, 'currency_value') : $contractValue;
                if (!empty($updateContract)) {
                    Contract::where(['id' => $contract->id])->update($updateContract);
                }
            }

            // Approve
            $approval->approval_status = encryptStringx('approved', 'approval_contracts.approval_status');
            $approval->status = encryptString('Approved', $usernameKey);
            $approval->next_action_description = encryptString($comments, $usernameKey);
            $approval->flag = 0;
            $approval->updated_on = date('Y-m-d H:i:s');
            // record who updated this approval
            $approval->updated_by = json_encode($updatedUser);
            // set a human-readable button label with timestamp
            $approval->button_text = 'Approved on ' . date('d M Y H:i');
            $approval->save();

            // If current group is parallel, notify other active approvers in the same group
            try {
                $groupIdCur = $approval->unique_id ?? null;
                if ($groupIdCur) {
                    $firstInGroup = ApprovalContracts::where('contract_id', $contract->id)->where('unique_id', $groupIdCur)->first();
                    $rowTypeCur = strtolower($firstInGroup->approval_type_row ?? 'sequential');
                    if ($rowTypeCur === 'parallel') {
                        $emailTrigger = new ContractNotificationController();
                        $senattment = ['filename' => [], 'filurl' => []];
                        $activeRows = ApprovalContracts::where('contract_id', $contract->id)->where('unique_id', $groupIdCur)->where('flag', 1)->get();
                        foreach ($activeRows as $r) {
                            try {
                                $dec = json_decode(decryptString($r->username, 'username'), true);
                                $decStatus = decryptString($r->approval_status, 'approval_status');
                                $to = '';
                                if(strtolower($decStatus) == 'pending'){
                                    $to = $dec['email'] ?? '';
                                }
                            } catch (\Throwable $e) {
                                $to = '';
                                \Log::error('Failed to generate email 1: ' . $e->getMessage());
                                DB::rollBack();
                            }
                            if (!empty($to)) {
                                \Log::info('generate email 1 ');
                                //$emailTrigger->sendEmail($contract->id, '', 'Approval Request', $to, 'Review', $senattment['filename'], $senattment['filurl'], 'notiMail');
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // non-fatal: continue workflow even if notify fails
                \Log::error('Failed to generate email notify: ' . $e->getMessage());
                DB::rollBack();                
            }

            // Recompute remaining approvals and determine next group or final signing
            $allApprovals = ApprovalContracts::where('contract_id', $contract->id)->orderBy('orderval', 'asc')->get();

            // Helper to get decrypted status
            $getStatus = function ($entry) {
                try {
                    return strtolower(trim(decryptString($entry->approval_status, 'approval_status')));
                } catch (\Throwable $e) {
                    return strtolower(trim($entry->approval_status ?? ''));
                }
            };

            // Mark current approval as approved already; find any remaining unapproved rows
            $remaining = $allApprovals->filter(function ($r) use ($getStatus) {
                return $getStatus($r) !== 'approved';
            });

            if ($remaining->isEmpty()) {
                // All approvals done -> move to signing
                $contract->update([
                    'contract_status' => 'Signing',
                    'substatus' => 'Approved'
                ]);
                // proceed to finalization below
            } else {
                // Determine next group to activate: earliest unapproved row
                $nextRow = $remaining->sortBy('orderval')->first();
                $nextGroupId = $nextRow->unique_id ?? null;
                $currentGroupId = $approval->unique_id ?? null;

                if (!empty($currentGroupId) && !empty($nextGroupId) && $currentGroupId !== $nextGroupId) {
                    $currentGroupEntries = $allApprovals->filter(function ($r) use ($currentGroupId) {
                        return ($r->unique_id ?? '') === $currentGroupId;
                    });

                    $currentGroupFirst = $currentGroupEntries->first();
                    $groupStageType = strtolower((string)($currentGroupFirst->stage_type ?? 'internal'));
                    $autoNextEnabled = (int)($currentGroupFirst->auto_next_enabled ?? 0) === 1;
                    if ($groupStageType === 'internal' && !$autoNextEnabled) {
                        $autoNextEnabled = $this->resolveAutoNextFromRules($contract, $currentGroupId, $allApprovals);
                    }

                    if ($groupStageType === 'external_pre' || !$autoNextEnabled) {
                        ApprovalContracts::where('contract_id', $contract->id)
                            ->where('unique_id', $currentGroupId)
                            ->update([
                                'awaiting_owner_trigger' => 1,
                                'flag' => 0,
                            ]);

                        $contract->update([
                            'contract_status' => 'Review',
                            'substatus' => 'Awaiting Owner Next Level',
                            'approval_gate_state' => 'waiting_owner_next',
                        ]);

                        try {
                            $historyModel = new \App\Models\ContractHistory();
                            $fillKeys = $historyModel->getFillable();
                            $snapshot = array_intersect_key($contract->toArray(), array_flip($fillKeys));
                            $snapshot['contract_status'] = $contract->contract_status;
                            $snapshot['substatus'] = $contract->substatus;
                            //$snapshot['updated_by'] = json_encode($updatedUser ?? ['email'=>Helpers::userInfo()->email ?? 'External','name'=>Helpers::userInfo()->FirstName ?? 'User']);
                            \App\Models\ContractHistory::create($snapshot);
                        } catch (\Throwable $e) {
                            \Log::error('Failed to write contract history on owner gate wait: ' . $e->getMessage());
                        }

                        DB::commit();
                        return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('success', 'Group completed. Waiting for owner to trigger next level.');
                    }
                }

                // Get entries for next group (grouped by unique_id)
                $nextGroupEntries = $allApprovals->filter(function ($r) use ($nextGroupId) {
                    return ($r->unique_id ?? '') === ($nextGroupId ?? '');
                })->sortBy('orderval');

                if ($nextGroupEntries->isEmpty()) {
                    // Fallback: activate first remaining single approver
                    $firstRem = $remaining->first();
                    if ($firstRem) {
                        ApprovalContracts::where('id', $firstRem->id)->update(['flag' => 1]);
                    }
                } else {
                    $rowTypeNext = strtolower($nextGroupEntries->first()->approval_type_row ?? 'sequential');

                    if ($rowTypeNext === 'parallel') {
                        // Activate all in this group (by unique_id)
                        ApprovalContracts::where('contract_id', $contract->id)->where('unique_id', $nextGroupId)->update(['flag' => 1]);
                    } else {
                        // Sequential: ensure all in group have flag 0 then activate first unapproved
                        ApprovalContracts::where('contract_id', $contract->id)->where('unique_id', $nextGroupId)->update(['flag' => 0]);
                        $firstToActivate = $nextGroupEntries->first(function ($r) use ($getStatus) {
                            return $getStatus($r) !== 'approved';
                        });
                        if ($firstToActivate) {
                            ApprovalContracts::where('id', $firstToActivate->id)->update(['flag' => 1]);
                        }
                    }
                }

                // Notify next approvers based on group activation (parallel: all; sequential: first only)
                try {
                    $emailTrigger = new ContractNotificationController();
                    $senattment = ['filename' => [], 'filurl' => []];
                    $notifyStatus = 'Review';

                    // Fallback single approver activated case
                    if (empty($nextGroupId) && isset($firstRem) && $firstRem) {
                        try {
                            $dec = json_decode(decryptString($firstRem->username, 'username'), true);
                            $to = $dec['email'] ?? '';
                        } catch (\Throwable $e) {
                            $to = '';
                            \Log::error('Failed to generate email notify 1: ' . $e->getMessage());
                            DB::rollBack();                               
                        }
                        if (!empty($to)) {
                            \Log::info('generate email 2 ');
                            $emailTrigger->sendEmail($contract->id, '', 'Approval Request', $to, $notifyStatus, $senattment['filename'], $senattment['filurl'], 'notiMail');
                        }
                    } else {
                        if (isset($rowTypeNext) && strtolower($rowTypeNext) === 'parallel') {
                            $activeRows = ApprovalContracts::where('contract_id', $contract->id)->where('unique_id', $nextGroupId)->where('flag', 1)->get();
                            foreach ($activeRows as $r) {
                                try {
                                    $dec = json_decode(decryptString($r->username, 'username'), true);
                                    $decStatus = decryptString($r->approval_status, 'approval_status');
                                    $to = '';
                                    if(strtolower($decStatus) == 'pending'){
                                        $to = $dec['email'] ?? '';
                                    }
                                } catch (\Throwable $e) {
                                    $to = '';
                                    \Log::error('Failed to generate email notify 2: ' . $e->getMessage());
                                    DB::rollBack();                                       
                                }
                                if (!empty($to)) {
                                    \Log::info('generate email 3 ');
                                    $emailTrigger->sendEmail($contract->id, '', 'Approval Request', $to, $notifyStatus, $senattment['filename'], $senattment['filurl'], 'notiMail');
                                }
                            }
                        } else {
                            $firstActive = ApprovalContracts::where('contract_id', $contract->id)->where('unique_id', $nextGroupId)->where('flag', 1)->orderBy('orderval', 'asc')->first();
                            if ($firstActive) {
                                try {
                                    $dec = json_decode(decryptString($firstActive->username, 'username'), true);
                                    $to = $dec['email'] ?? '';
                                } catch (\Throwable $e) {
                                    $to = '';
                                    \Log::error('Failed to generate email notify 3: ' . $e->getMessage());
                                    DB::rollBack();                                       
                                }
                                if (!empty($to)) {
                                    \Log::info('generate email 4 ');
                                    $emailTrigger->sendEmail($contract->id, '', 'Approval Request', $to, $notifyStatus, $senattment['filename'], $senattment['filurl'], 'notiMail');
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // non-fatal: don't break workflow if notify fails
                \Log::error('Failed to generate email notify 4: ' . $e->getMessage());
                DB::rollBack();                       
                }

                // update contract status to In Review
                $contract->update(['contract_status' => 'Review', 'substatus' => 'In Review']);

                // Record contract history snapshot after status change to In Review
                try {
                    $historyModel = new \App\Models\ContractHistory();
                    $fillKeys = $historyModel->getFillable();
                    $snapshot = array_intersect_key($contract->toArray(), array_flip($fillKeys));
                    $snapshot['contract_status'] = $contract->contract_status;
                    $snapshot['substatus'] = $contract->substatus;
                    //$snapshot['updated_by'] = json_encode($updatedUser ?? ['email'=>Helpers::userInfo()->email ?? 'External','name'=>Helpers::userInfo()->FirstName ?? 'User']);
                    \App\Models\ContractHistory::create($snapshot);
                } catch (\Throwable $e) {
                    \Log::error('Failed to write contract history on status change to In Review: ' . $e->getMessage());
                }

                DB::commit();
                return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('success', 'Approved. Workflow moved to next step.');
            }

            // No next approver: final approval
            $contract->update([
                'contract_status' => 'Signing',
                'substatus' => 'Approved'
            ]);

            // Record contract history snapshot after final approval
            try {
                $historyModel = new \App\Models\ContractHistory();
                $fillKeys = $historyModel->getFillable();
                $snapshot = array_intersect_key($contract->toArray(), array_flip($fillKeys));
                $snapshot['contract_status'] = $contract->contract_status;
                $snapshot['substatus'] = $contract->substatus;
                //$snapshot['updated_by'] = json_encode($updatedUser ?? ['email'=>Helpers::userInfo()->email ?? 'External','name'=>Helpers::userInfo()->FirstName ?? 'User']);
                \App\Models\ContractHistory::create($snapshot);
            } catch (\Throwable $e) {
                \Log::error('Failed to write contract history on final approval: ' . $e->getMessage());
            }


            $updatedUser = ['email' => Helpers::userInfo()->email ?? 'External', 'name' => Helpers::userInfo()->FirstName ?? 'User'];

            $emailTrigger = new ContractNotificationController();

            //Check Counter Parties 
            $counterParties = $contract->contractPartyList->all();

            $partiesPos = [];

            $externalParties = [];
            $externalPartiesCount = 0;
            $internalPartiesCount = 0;
            foreach ($counterParties as $parti) {
                if ($parti->contract_party_type == 'External' && $parti->contract_party_exe_id == !null) {
                    $repDetails = $parti->partyDetailsEx->repDetails ?? null;
                    if ($repDetails && count($repDetails) > 0) {
                        $externalPartiesCount++;
                        foreach ($repDetails as $rep) {
                            $externalParties[] = [
                                'email' => $rep->representative_email,
                                'name' => decryptString($parti->partyDetailsEx->company_name, 'company_name'),
                                'type' => 'external'
                            ];
                            break;
                        }
                    } else{
                        $externalPartiesCount++;
                        $externalParties[] = [
                            'email' => $parti->partyDetailsEx->company_email,
                            'name' => decryptString($parti->partyDetailsEx->company_name, 'company_name'),
                            'type' => 'external'
                        ];
                    }
                    $partiesPos[] = 'external';
                }

                if ($parti->contract_party_type == 'Intergroup' || $parti->contract_party_type == 'Internal') {
                    $partiesType = strtolower($parti->contract_party_type);
                    $partiesPos[] = $partiesType;
                    if ($parti->contract_party_location_id == !null) {
                        $externalPartiesCount++;
                        $loc_id__ = $parti->contract_party_location_id;
                        $branchHeadMails = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))
                            //->where(decrypt_datas('Role', 'AddUsers'), 'Branch Head')
                            ->whereRaw("FIND_IN_SET($loc_id__, branchhead)")
                            ->first();
                        $externalParties[] = [
                            'email' => $branchHeadMails->Email ?? '',
                            'name' => $branchHeadMails->FirstName ?? '',
                            'type' => $partiesType
                        ];
                    }
                }
            }

            $checkExternalPartySigned = ApprovalContracts::select('id', 'username', 'status', 'previous_status', 'contract_id', 'next_action_item', 'next_action_description', 'button_text', 'attachments', 'approval_status', 'updated_at', 'created_at', 'orderval', 'unique_id', 'flag')
                ->where('contract_id', $id)
                ->where('button_text', 'external')
                ->get();
                


            if ($externalPartiesCount > 0 && $externalPartiesCount != count($checkExternalPartySigned)) {
                $orderval = 1;
                foreach ($externalParties as $exparty) {
                    $randNo = rand(0, 99999);
                    $unique_id_loop = $id . $randNo;
                    $approvalRow = ApprovalContracts::create([
                        'username' => encryptString(json_encode(['email' => $exparty['email'], 'name' => $exparty['name']]), 'username'),
                        'unique_id' => $unique_id_loop,
                        'orderval' => $orderval,
                        'previous_status' => encryptString('iSigned', 'previous_status'),
                        'button_text' => 'external',
                        'status' => encryptString('Signing', 'status'),
                        'contract_id' => $id,
                        'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                        'approval_type_main' => 'sequential',
                        'approval_type_row' => 'sequential',
                        'approver_type_row' => 'signatory',                        
                        'flag' => '-1',
                        'signed_type' => 'custom',
                        'created_by' => json_encode($updatedUser)
                    ]);
                    $orderval++;
                    $this->crudUserActionLog($id, 'approval', 'ex-signing-email', $approvalRow->id, 0, $exparty['email'], false, $exparty['name']);
                    //$ExternalMailSent = $emailTrigger->sendEmail($id, '', '', $exparty, $appDataStatus, $senattment['filename'],  $senattment['filurl'], 'externalApproval');
                }
            }            

            // The approval is complete and MUST persist. Commit before the optional
            // executed-PDF rendering below so a rendering/storage failure can never roll
            // the contract back out of Signing (which previously left it stuck in its
            // prior status while still showing a "sent for signing" success message).
            

            // Generate final PDF from current contract template (no annexures).
            // Best-effort, post-commit side effect: a failure here is logged and surfaced
            // as a soft warning but never reverts the committed approval.
            $pdfWarning = '';
            try {
                $fileStorageController = fileStorageTypeController();

                if (fileStorageType() != "Local" && strtolower(pathinfo($contract->contract_attachment_filename, PATHINFO_EXTENSION)) == 'docx') {

                    $file_name = 'doc_' . strtotime(date('y-m-d h:i:s')) . '.docx';

                    $contentDocx = $fileStorageController->downloadUrl($contract->contract_attachment, $file_name);

                    $file_path = 'contracts/tempDocs/';

                    $filePath = Storage::disk('local')->put($file_path . $file_name, $contentDocx);

                    $storedWordFile = base_path() . '/storage/app/' . $file_path . $file_name;

                    $unlinkFiles = $file_path . $file_name;

                    $htmlDoc = $this->convertWordToHtmlBuffer($storedWordFile);

                    $pdf = \PDF::loadView("contract::contract.signedPdf", ['htmlDoc' => $htmlDoc]);

                    $pdf->setPaper('A4', 'portrait');

                    $pdf->render();

                    $output = $pdf->output();

                    $generatePdfPath = $fileStorageController->get_file_path($contract->id);

                    $generatedPdfDocumentFinalName = 'executed_contract_' . strtotime(date('d-m-y h:i:s')) . '.pdf';

                    $filePath = Storage::disk('local')->put($file_path . $generatedPdfDocumentFinalName, $output);

                    $storedWordFile = base_path() . '/storage/app/' . $file_path . $generatedPdfDocumentFinalName;

                    $generatedPdfDocumentFinal = $fileStorageController->storeContent(base_path() . '/storage/app/' . $file_path . $generatedPdfDocumentFinalName, $generatePdfPath, $generatedPdfDocumentFinalName);

                    if (strpos(strtolower($generatedPdfDocumentFinal), "error") !== false) {
                        \Log::error('Failed to store final executed PDF for contract ' . $contract->id . ': ' . $generatedPdfDocumentFinal);
                        $pdfWarning = ' Note: the executed PDF could not be generated automatically; please regenerate it from the contract.';
                    } elseif (!empty($generatedPdfDocumentFinalName)) {
                        Contract::where(['id' => $contract->id])->update([
                            'contract_attachment' => $generatedPdfDocumentFinal,
                            'contract_attachment_filename' => $generatedPdfDocumentFinalName
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                \Log::error('Failed to generate final PDF for contract ' . $contract->id . ': ' . $e->getMessage());
                $pdfWarning = ' Note: the executed PDF could not be generated automatically; please regenerate it from the contract.';
            }
            
            if($pdfWarning == ''){
                DB::commit();
                return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('success', 'Final approval completed. Contract sent for signing.');
            }else{
                return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('error', 'Final approval failed. Contract not sent for signing.'. $pdfWarning);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to process approval: ' . $e->getMessage());
        }
    }  
    
    
    public function completeSignUpload(Request $request, $id)
    {
        $contract = Contract::find($id);
        
        if (!$contract) {
            return response()->json(['success' => false, 'message' => 'Invalid Contract'], 404);
        }

        if (!$request->hasFile('signed_file') && !$request->filled('signed_file_base64')) {
            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }
        
        $isSignatory = false;
            
        if (!empty($contract->signatory)) {
            $signatory = AddUsers::select('id',  decrypt_data('Email', 'AddUsers'))
                    ->where('id', $contract->signatory)
                    ->first();
            if ($signatory) {
                $ownerEmail = $signatory->Email ?? null;
                $userInfo = Helpers::userInfo();
                $currentIdentifier = strtolower($userInfo->email);
                if ($ownerEmail && strtolower($ownerEmail) === $currentIdentifier) {
                    $isSignatory = true;
                }
            }
        }        
$isCreator = false;
        // Check if the current user is the contract creator (supports numeric id or stored JSON/email)
        if (!empty($contract->created_by)) {
            if (is_numeric($contract->created_by) && intval($contract->created_by) === intval($userInfo->id ?? 0)) {
                $isCreator = true;
            } else {
                $cb = @json_decode($contract->created_by, true);
                $ownerEmail = $cb['email'] ?? ($contract->created_by ?? null);
                if ($ownerEmail && strtolower($ownerEmail) === strtolower($userInfo->email ?? '')) {
                    $isCreator = true;
                }
            }
        }

        // Allow if signatory or the contract creator (creator allowed only when contract is in Signing/Approved state)
        if (!($isSignatory || $isCreator)) {
            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('error', 'Invalid Operation');
        }

        $validator = \Validator::make($request->all(), [
            'signed_file' => 'sometimes|file|mimes:pdf|max:20480',
            'signed_file_base64' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            //return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('error', $validator->errors()->first());
        }

        $storageController = fileStorageTypeController();

        try {
            if ($request->hasFile('signed_file')) {
                $file = $request->file('signed_file');
                $generatedName = 'signed_' . ($contract->contract_unique_id ?? $contract->id) . '.' . $file->getClientOriginalExtension();

                if (fileStorageType() != 'Local') {
                    $filePath = $storageController->storeFile($file, '', $contract->id, $generatedName);
                } else {
                    $generatePdfPath = $storageController->get_file_path($contract->id);
                    $generatedPdfDocumentFinal = $generatePdfPath . '/' . $generatedName;
                    Storage::put($generatedPdfDocumentFinal, file_get_contents($file));
                    $filePath = $generatedPdfDocumentFinal;
                }
            }

            //Contract::where('id', $id)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $generatedName]);
            
            $cur_date = date('Y-m-d');
            
            $end_date_of_contract = $contract->contract_end_date;
            
            $mainStatus = "executed";
            $subStatusApprvr = "active";

            if (strtotime($cur_date) > strtotime($end_date_of_contract)) {
                $subStatusApprvr = 'expired';
            }                        
                        
            Contract::where('id', $id)->update([
                'contract_attachment' => $filePath, 
                'contract_attachment_filename' => $generatedName,
                'contract_status' => $mainStatus,
                'substatus' => $subStatusApprvr                            
            ]);            

            $updatedContract = Contract::find($id);
            if ($updatedContract) {
                $this->sendExecutedActiveNotifications($updatedContract);
            }

            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('success', 'Contract Signed File Uploaded Successfully');
        } catch (\Exception $e) {
            \Log::error('completeSignUpload error: ' . $e->getMessage().'Line:'.$e->getLine());
            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('error', 'Contract Signed File Upload Failed');
        }
    }

    public function uploadFileAndGetHttpsUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $uploadedFile = $request->file('file');
        $tempPath = $uploadedFile->getPathname();
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'bin');
        $storedFileName = 'contract_' . time() . '_' . uniqid() . '.' . $extension;
        $storedRelativePath = 'contracts/uploads/' . date('Y/m') . '/' . $storedFileName;

        try {
            $stream = fopen($tempPath, 'rb');
            if ($stream === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to read uploaded file.',
                ], 500);
            }

            Storage::disk('public')->put($storedRelativePath, $stream);
            fclose($stream);

            $publicUrl = Storage::disk('public')->url($storedRelativePath);
            $httpsUrl = str_starts_with($publicUrl, 'http')
                ? preg_replace('/^http:/i', 'https:', $publicUrl)
                : secure_url(ltrim($publicUrl, '/'));

            // Uploaded temp file is normally cleaned by PHP; this is an explicit cleanup as requested.
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }

            return response()->json([
                'success' => true,
                'path' => $storedRelativePath,
                'url' => $httpsUrl,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('uploadFileAndGetHttpsUrl error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'File upload failed.',
            ], 500);
        }
    }

    /**
     * Return JSON for comparing a contract history snapshot with current contract data.
     */
    public function compareHistory(Request $request, $id, $historyId)
    {
        try {
            $contract = Contract::findOrFail($id);
            $history = ContractHistory::where('history_id', $historyId)->orWhere('id', $historyId)->first();
            if (! $history) return response()->json(['status' => false, 'message' => 'History not found'], 404);
            // Prepare friendly display values for common fields
            $keys = ['contract_name','fixed_date','contract_end_date','currency_value','contract_status','substatus','owner','contract_attachment_filename'];
            $historyDisplay = [];
            $currentDisplay = [];
            foreach ($keys as $k) {
                try {
                    $historyVal = $history->$k ?? null;
                } catch (\Throwable $e) { $historyVal = null; }
                try {
                    $currentVal = $contract->$k ?? null;
                } catch (\Throwable $e) { $currentVal = null; }

                if (function_exists('decryptString')) {
                    try { $historyDisplay[$k] = is_string($historyVal) ? @decryptString($historyVal, $k) : $historyVal; } catch (\Throwable $e) { $historyDisplay[$k] = $historyVal; }
                    try { $currentDisplay[$k] = is_string($currentVal) ? @decryptString($currentVal, $k) : $currentVal; } catch (\Throwable $e) { $currentDisplay[$k] = $currentVal; }
                } else {
                    $historyDisplay[$k] = $historyVal;
                    $currentDisplay[$k] = $currentVal;
                }
            }

            return response()->json(['status' => true, 'history' => $history, 'current' => $contract, 'history_display' => $historyDisplay, 'current_display' => $currentDisplay]);
        } catch (\Throwable $e) {
            \Log::error('compareHistory error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error fetching history'], 500);
        }
    }

    /**
     * Parse the full pre-approval configuration from a contract's rules_id JSON.
     *
     * rules_id column stores a JSON array, e.g.:
     *   [{"id":1,"approval_type":"sequential","approval_status":"required",
     *     "approver":"{\"review\":[...],\"negotiation\":[],...,\"_parent_routing\":{...}}",
     *     "signatory":"{...}"}]
     *
     * The stage groups (review/negotiation/finalization/approval/signatory) and
     * the "_parent_routing" transition map live INSIDE the "approver" field
     * (which itself is a JSON string), NOT in "approval_type" (which is just
     * a plain string like "sequential").
     *
     * @return array|null  Associative array of the parsed approver config, or null.
     */
    public function getApprovalConfig($rulesId)
    {
        try {
            if (empty($rulesId)) {
                return null;
            }

            $rulesArray = is_string($rulesId) ? json_decode($rulesId, true) : $rulesId;

            // Normalize objects to arrays
            if (is_object($rulesArray)) {
                $rulesArray = json_decode(json_encode($rulesArray), true);
            }

            if (!is_array($rulesArray) || empty($rulesArray)) {
                return null;
            }

            // Get the first rule object (may be keyed 0 or associative)
            $rule = $rulesArray[0] ?? reset($rulesArray);
            if (is_object($rule)) {
                $rule = json_decode(json_encode($rule), true);
            }
            if (!is_array($rule)) {
                return null;
            }

            // The actual stage/routing config lives in the "approver" field
            $approverRaw = $rule['approver'] ?? null;
            if (empty($approverRaw)) {
                return null;
            }

            $approverConfig = is_string($approverRaw) ? json_decode($approverRaw, true) : $approverRaw;
            if (is_object($approverConfig)) {
                $approverConfig = json_decode(json_encode($approverConfig), true);
            }

            if (!is_array($approverConfig)) {
                return null;
            }

            return $approverConfig;
        } catch (\Throwable $e) {
            Log::error('getApprovalConfig error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Return the "_parent_routing" transition map from the contract's rules_id.
     *
     * @return array|null
     */
    public function getRoutingConfig($rulesId)
    {
        $config = $this->getApprovalConfig($rulesId);
        if (!is_array($config)) {
            return null;
        }
        return $config['_parent_routing'] ?? null;
    }

    /**
     * Apply a stage transition driven by the contract's `_parent_routing` rules.
     *
     * Performs only the DB mutations (contract status + creation/deactivation of the
     * next stage's approval rows) and returns the resolved stage name:
     *   - approve: the `on_approve` stage, or null when none is configured (caller
     *     decides the terminal behaviour, e.g. move to Signing);
     *   - reject:  the `on_reject` fallback stage (default 'review').
     *
     * Shared by preApprovalRespond, the grouped main flow (advanceGroupedApproval)
     * and the external negotiationRespond guest flow.
     *
     * @param  string $action          'approve' or 'reject'
     * @param  string $flowType         flow_type used for this contract's stage rows
     * @param  bool   $autoNegotiation  dispatch negotiation rows + emails immediately
     *                                   instead of waiting for an owner trigger
     */
    private function transitionApprovalStage($contract, $currentStage, $action, $flowType = 'preapproval', $autoNegotiation = false)
    {
        $routingConfig = $this->getRoutingConfig($contract->rules_id) ?? [];
        $approvalConfig = $this->getApprovalConfig($contract->rules_id);

        if ($action === 'reject') {
            $fallbackStage = $routingConfig[$currentStage]['on_reject'] ?? 'review';
            if ($fallbackStage === 'draft' || $fallbackStage === 'terminate') {
                $contract->update([
                    'contract_status' => 'Draft',
                    'substatus' => 'Initial Draft',
                    'preapproval_stage' => null,
                ]);
                ApprovalContracts::where('contract_id', $contract->id)->where('flow_type', $flowType)->update(['flag' => 0]);
            } else {
                $contract->update(['preapproval_stage' => $fallbackStage]);
                $stageGroups = is_array($approvalConfig) ? ($approvalConfig[$fallbackStage] ?? []) : [];
                if (!empty($stageGroups)) {
                    ApprovalContracts::where('contract_id', $contract->id)->where('flow_type', $flowType)->where('stage_name', $fallbackStage)->update(['flag' => 0]);
                    $this->createApprovalRows($contract->id, $flowType, $fallbackStage, $stageGroups);
                }
            }
            return $fallbackStage;
        }

        // approve
        $nextStage = $routingConfig[$currentStage]['on_approve'] ?? null;
        if (!$nextStage) {
            return null;
        }

        if ($nextStage === 'negotiation') {
            $contract->update([
                'preapproval_stage' => 'negotiation',
                'contract_status' => 'Negotiation',
                'substatus' => $autoNegotiation ? 'Under Process' : 'Awaiting Negotiation',
            ]);
            if ($autoNegotiation) {
                $this->dispatchNegotiation($contract, $flowType);
            }
        } elseif ($nextStage === 'finalization') {
            $contract->update([
                'preapproval_stage' => 'finalization',
                'contract_status' => 'Finalization',
                'substatus' => 'In Progress'
            ]);
            $finalizationGroups = is_array($approvalConfig) ? ($approvalConfig['finalization'] ?? []) : [];
            if (!empty($finalizationGroups)) {
                $this->createApprovalRows($contract->id, $flowType, 'finalization', $finalizationGroups);
            }
        } elseif ($nextStage === 'approval') {
            $contract->update([
                'preapproval_stage' => null,
                'preapproval_completed_at' => now(),
                'contract_status' => 'Approval',
                'substatus' => 'Pending Approval',
            ]);
            // Deactivate all pre-approval rows since we're exiting that flow.
            ApprovalContracts::where('contract_id', $contract->id)->where('flow_type', $flowType)->update(['flag' => 0]);
            // Create approval rows with flow_type='approval' (not preapproval) so they render
            // in the main timeline/approval UI, not in the pre-approval flow UI.
            $approvalGroups = is_array($approvalConfig) ? ($approvalConfig['approval'] ?? []) : [];
            if (!empty($approvalGroups)) {
                $this->createApprovalRows($contract->id, 'approval', 'approval', $approvalGroups);
            }
        } elseif ($nextStage === 'signatory') {
            // Terminal stage: hand off to the signatory / signing machinery.
            ApprovalContracts::where('contract_id', $contract->id)->update(['flag' => 0]);
            $this->activateSignatory($contract);
        } else {
            $contract->update(['preapproval_stage' => $nextStage]);
            $stageGroups = is_array($approvalConfig) ? ($approvalConfig[$nextStage] ?? []) : [];
            if (!empty($stageGroups)) {
                $this->createApprovalRows($contract->id, $flowType, $nextStage, $stageGroups);
            }
        }

        return $nextStage;
    }

    /**
     * Decrypt a grouped approval row's approval_status. Rows are first written with
     * the fixed 'approval_status' key (createApprovalRows) but re-encrypted with the
     * acting user's email key once acted on, so both are attempted.
     */
    private function groupedRowApprovalStatus($row)
    {
        try {
            $decUsername = decryptString($row->username, 'username');
            $decArr = json_decode($decUsername, true);
            $statusKey = $decArr['email'] ?? ('approver_' . $row->id);
            return strtolower(trim((string)decryptString($row->approval_status, $statusKey)));
        } catch (\Throwable $e) {
            try {
                return strtolower(trim((string)decryptString($row->approval_status, 'approval_status')));
            } catch (\Throwable $e2) {
                return strtolower(trim((string)$row->approval_status));
            }
        }
    }

    /**
     * Terminal transition of a grouped flow: create the signatory's signing row and
     * move the contract to Signing. The signing row intentionally carries no
     * stage_name so it is handled by the existing signing machinery, not routed
     * back through advanceGroupedApproval.
     */
    private function activateSignatory($contract)
    {
        $signatory = $contract->signatory;
        // Resolve the signatory WITHOUT the UserContractScope global scope: the signatory
        // is a valid, already-selected user for this contract but may belong to a different
        // entity / lack "Contracts" access, so the tenant scope would otherwise hide them
        // and leave the contract stuck at the Approval stage.
        $users = AddUsers::withoutGlobalScopes()
            ->select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))
            ->where('id', $signatory)->get();
        if (!isset($users[0])) {
            Log::error('activateSignatory: signatory user not found for contract ' . $contract->id . ' (signatory id ' . $signatory . '); moving to Signing without an approver row.');
            $contract->update(['contract_status' => 'Signing', 'substatus' => 'Approved']);
            return;
        }
        $randNo = rand(0, 99999);
        ApprovalContracts::create([
            'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
            'unique_id' => $contract->id . $randNo,
            'orderval' => (ApprovalContracts::where('contract_id', $contract->id)->max('orderval') ?? 0) + 1,
            'previous_status' => encryptString('Approval', 'previous_status'),
            'status' => encryptString('Signing', 'status'),
            'contract_id' => $contract->id,
            'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
            'flag' => !$contract->signing_date ? '1' : '0',
            'approval_type_main' => 'sequential',
            'approval_type_row' => 'sequential',
            'approver_type_row' => 'signatory',
        ]);
        $contract->update(['contract_status' => 'Signing', 'substatus' => 'Approved']);
    }

    /**
     * Decrypt a grouped approval row's username payload and return the approver email.
     */
    private function groupedRowEmail($row)
    {
        try {
            $decArr = json_decode(decryptString($row->username, 'username'), true);
            return $decArr['email'] ?? '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Grant each supplied approval row's approver the cloud-file permission level
     * configured for its group (default 'editor') on the contract document. Storage
     * errors are logged, never thrown, so an approval transition is never broken by a
     * cloud API hiccup. The storage-account owner is skipped by the drive controllers.
     */
    private function grantGroupFilePermission($contract, $rows)
    {
        if (empty($contract->contract_attachment) || empty($rows)) {
            return;
        }
        try {
            $controller = fileStorageTypeController();
            foreach ($rows as $row) {
                $email = $this->groupedRowEmail($row);
                if (empty($email)) {
                    continue;
                }
                $level = $row->file_permission ?: 'editor';
                $controller->setFilePermission($contract->contract_attachment, $email, $level);
            }
        } catch (\Throwable $e) {
            Log::error('grantGroupFilePermission error for contract ' . $contract->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Downgrade each supplied approval row's approver to read-only on the contract
     * document (used once a group / stage has completed). Storage errors are logged.
     */
    private function downgradeRowsToReadonly($contract, $rows)
    {
        if (empty($contract->contract_attachment) || empty($rows)) {
            return;
        }
        try {
            $controller = fileStorageTypeController();
            foreach ($rows as $row) {
                $email = $this->groupedRowEmail($row);
                if (empty($email)) {
                    continue;
                }
                $controller->setFilePermission($contract->contract_attachment, $email, 'readonly');
            }
        } catch (\Throwable $e) {
            Log::error('downgradeRowsToReadonly error for contract ' . $contract->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Advance a parent-grouped approval after the acting row has already been marked
     * approved/rejected by the caller. Resolution order:
     *   1. next still-pending approver inside the same (sequential) group;
     *   2. next group inside the same stage that hasn't started yet;
     *   3. stage complete -> route to the next stage via `_parent_routing`.
     *
     * Returns a short token describing the outcome: 'next_approver' | 'next_group' |
     * 'waiting' | 'stage:<name>' | 'reject:<name>' | 'signing'.
     */
    private function advanceGroupedApproval($contract, $approvalRow, $action)
    {
        $flowType = $approvalRow->flow_type ?: 'grouped';
        $currentStage = $approvalRow->stage_name ?: ($contract->preapproval_stage ?? 'review');
        $groupKey = $approvalRow->group_key ?: $approvalRow->unique_id;

        if ($action === 'reject') {
            $fallbackStage = $this->transitionApprovalStage($contract, $currentStage, 'reject', $flowType, false);
            return 'reject:' . $fallbackStage;
        }

        // 1) Sequential group: activate the next still-pending approver in this group.
        $isParallelGroup = strtolower((string)($approvalRow->approval_type_row ?? 'sequential')) === 'parallel';
        if (!$isParallelGroup) {
            $nextInGroup = ApprovalContracts::where('contract_id', $contract->id)
                ->where('group_key', $groupKey)
                ->where('superseded', 0)
                ->where('orderval', '>', $approvalRow->orderval)
                ->orderBy('orderval', 'asc')
                ->get()
                ->first(function ($row) {
                    return $this->groupedRowApprovalStatus($row) !== 'approved';
                });
            if ($nextInGroup) {
                ApprovalContracts::where('id', $nextInGroup->id)->update(['flag' => 1]);
                // Grant the newly-activated approver their group's file permission.
                $this->grantGroupFilePermission($contract, [$nextInGroup]);
                return 'next_approver';
            }
        }

        // 2) Whole stage done? Otherwise activate the next not-yet-started group.
        // Only consider the current (non-superseded) batch for this stage.
        $stageRows = ApprovalContracts::where('contract_id', $contract->id)
            ->where('stage_name', $currentStage)
            ->where('superseded', 0)
            ->orderBy('orderval', 'asc')
            ->get();

        $groups = [];
        foreach ($stageRows as $row) {
            $gk = $row->group_key ?: $row->unique_id;
            $groups[$gk][] = $row;
        }

        $allApproved = true;
        foreach ($stageRows as $row) {
            if ($this->groupedRowApprovalStatus($row) !== 'approved') {
                $allApproved = false;
                break;
            }
        }

        if (!$allApproved) {
            foreach ($groups as $rows) {
                $groupHasActivity = false;
                foreach ($rows as $r) {
                    if ((int)$r->flag === 1 || $this->groupedRowApprovalStatus($r) === 'approved') {
                        $groupHasActivity = true;
                        break;
                    }
                }
                if (!$groupHasActivity) {
                    // The group that just finished (the acting row's group) is fully
                    // approved -> drop its approvers to read-only before handing the
                    // document to the next group.
                    $completedGroupRows = $groups[$groupKey] ?? [];
                    $completedFullyApproved = !empty($completedGroupRows);
                    foreach ($completedGroupRows as $cr) {
                        if ($this->groupedRowApprovalStatus($cr) !== 'approved') {
                            $completedFullyApproved = false;
                            break;
                        }
                    }
                    if ($completedFullyApproved) {
                        $this->downgradeRowsToReadonly($contract, $completedGroupRows);
                    }

                    $parallel = strtolower((string)($rows[0]->approval_type_row ?? 'sequential')) === 'parallel';
                    if ($parallel) {
                        foreach ($rows as $r) {
                            ApprovalContracts::where('id', $r->id)->update(['flag' => 1]);
                        }
                    } else {
                        $rows = [$rows[0]];
                        ApprovalContracts::where('id', $rows[0]->id)->update(['flag' => 1]);
                    }
                    // Grant the newly-activated group their configured file permission.
                    $this->grantGroupFilePermission($contract, $rows);
                    return 'next_group';
                }
            }
            // Remaining approvers still pending in an active group -> just wait.
            return 'waiting';
        }

        // 3) Stage complete -> drop this stage's approvers to read-only, then route to
        // the next stage. Negotiation is NOT auto-sent: the owner triggers it via the
        // "Send Negotiation Email" button in the Pre-Approval Flow UI (negotiationEmail).
        $this->downgradeRowsToReadonly($contract, $stageRows);
        $nextStage = $this->transitionApprovalStage($contract, $currentStage, 'approve', $flowType, false);
        if (!$nextStage) {
            $this->activateSignatory($contract);
            return 'signing';
        }
        return 'stage:' . $nextStage;
    }

    public function sendToPreApproval($id)
    {
        DB::beginTransaction();
        try {
            $contract = Contract::findOrFail($id);
            $rulesId = $contract->rules_id;
            $approvalConfig = $this->getApprovalConfig($rulesId);
            if (!$approvalConfig || empty($approvalConfig['_parent_routing'])) {
                return redirect()->back()->with('error', 'Pre-approval routing configuration not found.');
            }
            $reviewGroups = $approvalConfig['review'] ?? [];
            if (empty($reviewGroups)) {
                return redirect()->back()->with('error', 'No review groups configured in approval type.');
            }
            $contract->update([
                'contract_status' => 'Pre-Approval',
                'substatus' => 'Review',
                'preapproval_stage' => 'review',
            ]);
            $this->createApprovalRows($contract->id, 'preapproval', 'review', $reviewGroups);
            DB::commit();
            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'pre-approval'])->with('success', 'Contract sent to next level.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('sendToPreApproval error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send to pre-approval: ' . $e->getMessage());
        }
    }

    public function showPreApprovalPage($id)
    {
        $contract = Contract::findOrFail($id);

        // If pre-approval is complete (preapproval_stage is null), redirect to timeline
        if ($contract->preapproval_stage === null) {
            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline']);
        }

        $preApprovalSteps = ApprovalContracts::where('contract_id', $contract->id)
            ->where('flow_type', 'preapproval')
            ->orderBy('orderval', 'asc')
            ->get()
            ->map(function ($step) {
                $userData = [];
                try {
                    $userData = json_decode(decryptString($step->username, 'username'), true) ?: [];
                } catch (\Throwable $e) {
                    $userData = [];
                }
                $step->approver_email = $userData['email'] ?? '';
                $step->approver_name = $userData['name'] ?? '';
                return $step;
            });
        $rulesId = $contract->rules_id;
        $approvalConfig = $this->getApprovalConfig($rulesId);
        $routingConfig = is_array($approvalConfig) ? ($approvalConfig['_parent_routing'] ?? []) : [];
        $approvalType = is_array($approvalConfig) ? $approvalConfig : [];

        // Owner/admin may add dynamic approvers to groups that have it enabled.
        $userCanGate = $this->approvalActorIsOwnerOrAdmin($contract);
        $dynamicApproverOptions = $userCanGate
            ? AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => (int)($user->id ?? 0),
                        'name' => trim(((string)($user->FirstName ?? '')) . ' ' . ((string)($user->LastName ?? ''))),
                        'email' => (string)($user->Email ?? ''),
                    ];
                })
                ->filter(function ($user) {
                    return !empty($user['email']);
                })
                ->values()
            : collect();

        return view('contract::contract.preApprovalFlow', compact('contract', 'preApprovalSteps', 'routingConfig', 'approvalType', 'userCanGate', 'dynamicApproverOptions'));
    }

    public function preApprovalRespond(Request $request, $id, $approvalId)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        DB::beginTransaction();
        try {
            $contract = Contract::findOrFail($id);
            $approval = ApprovalContracts::findOrFail($approvalId);
            $userRole = session()->get('contractSessionUserRole');
            $isAdmin = ($userRole === 'Admin' || $userRole === 'Super Admin') || (optional(Helpers::userInfo())->email ?? '') === 'admin@legalitysimplified.com';
            if ((int)$approval->flag !== 1 && !$isAdmin) {
                return redirect()->back()->with('error', 'This approval step is not active for you.');
            }
            $action = $request->input('action');
            $comments = $request->input('comments');
            $updatedUser = ['email' => Helpers::userInfo()->email ?? 'External', 'name' => Helpers::userInfo()->FirstName ?? 'User'];
            try {
                $decUsername = decryptString($approval->username, 'username');
                $decArr = json_decode($decUsername, true);
                $usernameKey = $decArr['email'] ?? ($decUsername ?: ('approver_' . $approval->id));
            } catch (\Throwable $e) {
                $usernameKey = 'approver_' . $approval->id;
            }
            if ($isAdmin) {
                $adminEmail = optional(Helpers::userInfo())->email ?? null;
                if ($adminEmail) $usernameKey = $adminEmail;
            }
            if ($action === 'reject') {
                $approval->approval_status = encryptStringx('rejected', 'approval_contracts.approval_status');
                $approval->status = encryptString('Rejected', $usernameKey);
                $approval->next_action_description = encryptString($comments, $usernameKey);
                $approval->flag = 0;
                $approval->updated_on = date('Y-m-d H:i:s');
                $approval->updated_by = json_encode($updatedUser);
                $approval->button_text = 'Rejected on ' . date('d M Y H:i');
                $approval->save();
                $currentStage = $contract->preapproval_stage ?? 'review';
                $this->transitionApprovalStage($contract, $currentStage, 'reject', 'preapproval', false);
                DB::commit();
                return redirect()->route('viewContract', ['id' => $id, 'tab' => 'pre-approval'])->with('success', 'You have rejected the this step.');
            }
            $approval->approval_status = encryptStringx('approved', 'approval_contracts.approval_status');
            $approval->status = encryptString('Approved', $usernameKey);
            $approval->next_action_description = encryptString($comments, $usernameKey);
            $approval->flag = 0;
            $approval->updated_on = date('Y-m-d H:i:s');
            $approval->updated_by = json_encode($updatedUser);
            $approval->button_text = 'Approved on ' . date('d M Y H:i');
            $approval->save();

            // Advance within the group / to the next group / to the next stage.
            $outcome = $this->advanceGroupedApproval($contract, $approval, 'approve');
            DB::commit();

            if ($outcome === 'next_approver' || $outcome === 'next_group' || $outcome === 'waiting') {
                return redirect()->route('viewContract', ['id' => $id, 'tab' => 'pre-approval'])->with('success', 'Approved. Waiting for the remaining approvers in this stage.');
            }
            if ($outcome === 'signing') {
                return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('success', 'Approval completed. Contract moved to signing.');
            }
            if (strpos((string)$outcome, 'stage:') === 0) {
                $nextStage = substr($outcome, 6);
                if ($nextStage === '') {
                    return redirect()->route('viewContract', ['id' => $id, 'tab' => 'pre-approval'])->with('error', 'No next stage configured in routing.');
                }
                if ($nextStage === 'approval') {
                    return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('success', 'this step completed. Contract moved to standard approval flow.');
                }
                // Pre-approval stages: review, negotiation, finalization. Approval and beyond go to timeline.
                $preApprovalStages = ['review', 'negotiation', 'finalization'];
                if (in_array($nextStage, $preApprovalStages)) {
                    if ($nextStage === 'negotiation') {
                        return redirect()->route('viewContract', ['id' => $id, 'tab' => 'pre-approval'])->with('success', 'moved to negotiation stage. Please trigger negotiation emails to external parties.');
                    }
                    return redirect()->route('viewContract', ['id' => $id, 'tab' => 'pre-approval'])->with('success', 'moved to ' . $nextStage . ' stage.');
                } else {
                    // Moved out of pre-approval (e.g. to approval stage)
                    return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('success', 'this step completed. Contract moved to ' . ucfirst($nextStage) . ' stage.');
                }
            }
            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'pre-approval'])->with('success', 'Approved.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('preApprovalRespond error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to process pre-approval response: ' . $e->getMessage());
        }
    }

    public function negotiationEmail($id)
    {
        DB::beginTransaction();
        try {
            $contract = Contract::findOrFail($id);

            $recipients = $this->dispatchNegotiation($contract);
            if (empty($recipients) || !is_array($recipients)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'No external party emails found.');
            }

            DB::commit();
            $emailList = implode(', ', array_filter(array_map(function ($d) {
                return $d['email'] ?? null;
            }, $recipients)));
            $successMsg = 'Negotiation review email' . (count($recipients) > 1 ? 's' : '')
                . ' sent to: ' . $emailList . '. The contract owner has been notified.';
            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'pre-approval'])->with('success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('negotiationEmail error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send negotiation emails: ' . $e->getMessage());
        }
    }

    /**
     * Owner-side update of the contract document from the Pre-Approval (negotiation) page.
     *
     * To preserve change tracking, when an existing document of the SAME format is present
     * this replaces the CONTENT of that existing cloud file in place (same file id) so the
     * storage backend (Google Drive / OneDrive) keeps the prior content as a previous
     * version. Only when there is no existing file, or the uploaded format differs (a
     * revision can't span formats), does it create a new file.
     */
    public function preApprovalUpdateAttachment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'attachment_file' => 'required|file|mimes:pdf,doc,docx,xlsx,xls|max:50000',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $contract = Contract::findOrFail($id);

            $file = $request->file('attachment_file');
            $newExt = strtolower((string)$file->getClientOriginalExtension());

            // Same storage-aware pattern as negotiationRespond.
            $storageType = fileStorageType();
            if ($storageType === 'Google') {
                $fileController = new GoogleDriveController();
            } elseif ($storageType === 'Microsoft') {
                $fileController = new MicrosoftDriveController();
            } else {
                $fileController = new LocalDriveController();
            }

            $existingId = $contract->contract_attachment;
            $existingExt = strtolower((string)pathinfo($contract->contract_attachment_filename ?? '', PATHINFO_EXTENSION));
            $updatedInPlace = false;

            if (!empty($existingId) && $existingExt !== '' && $existingExt === $newExt) {
                // Replace the content of the SAME cloud file -> the backend versions it,
                // so the previous and current content are both tracked in the cloud.
                $result = $fileController->updateFileContent($existingId, $file->getPathname());
                if (empty($result) || (is_string($result) && strncasecmp($result, 'Error', 5) === 0)) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Failed to update the contract document. Please try again.');
                }
                // contract_attachment + filename intentionally unchanged: same file, new version.
                $contract->touch();
                $updatedInPlace = true;
            } else {
                // No existing file, or a format change -> a new file (cannot version across formats).
                $fileName = 'contract_' . $contract->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $newAttachmentPath = $fileController->storeFile($file, 'negotiation', $contract->id, $fileName);
                if (empty($newAttachmentPath) || (is_string($newAttachmentPath) && strncasecmp($newAttachmentPath, 'Error', 5) === 0)) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Failed to upload the contract document. Please try again.');
                }
                $contract->update([
                    'contract_attachment' => $newAttachmentPath,
                    'contract_attachment_filename' => $fileName,
                ]);
            }

            // Re-grant the currently active negotiation approver(s) access. For an in-place
            // update the file id is unchanged (idempotent); for a new file it restores access.
            $activeNegotiationRows = ApprovalContracts::where('contract_id', $contract->id)
                ->where('stage_name', 'negotiation')
                ->where('superseded', 0)
                ->where('flag', 1)
                ->get();
            if ($activeNegotiationRows->count() > 0) {
                $this->grantGroupFilePermission($contract, $activeNegotiationRows->all());
            }

            DB::commit();
            $successMsg = $updatedInPlace
                ? 'Contract document updated. The change is saved as a new version of the same file, so previous content is retained in cloud storage for change tracking.'
                : 'Contract document updated successfully.';
            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'pre-approval'])
                ->with('success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('preApprovalUpdateAttachment error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update the contract document: ' . $e->getMessage());
        }
    }

    /**
     * Create the negotiation approval rows and notify the external parties /
     * representatives. Returns the number of recipients notified, or -1 when no
     * external emails were found. The caller owns the surrounding DB transaction,
     * so this can be reused both by the standalone owner-triggered
     * `negotiationEmail` action and by the automatic grouped-flow transition.
     */
    private function dispatchNegotiation($contract, $flowType = 'preapproval')
    {
        $emails = [];
        $emailDetails = [];

        // Send the negotiation email to a single recipient only: the FIRST authorized
        // representative of the external party. Contracts link to parties through
        // contract_party_data (custom_field_group_id = contract id, contract_party_type,
        // contract_party_exe_id -> contract_parties.id).
        $firstRep = ContractPartiesRepresentative::query()
            ->select('contract_parties_representative.representative_name', 'contract_parties_representative.representative_email')
            ->join('contract_parties', 'contract_parties.id', '=', 'contract_parties_representative.parties_id')
            ->join('contract_party_data', 'contract_party_data.contract_party_exe_id', '=', 'contract_parties.id')
            ->where('contract_party_data.custom_field_group_id', $contract->id)
            ->where('contract_party_data.contract_party_type', 'External')
            ->where('contract_parties_representative.status', 1)
            ->orderBy('contract_parties_representative.id', 'asc')
            ->first();

        if ($firstRep && !empty($firstRep->representative_email)) {
            $emails[] = $firstRep->representative_email;
            $emailDetails[] = ['email' => $firstRep->representative_email, 'name' => $firstRep->representative_name ?: 'Representative'];
        } else {
            // Fallback: the first external party's company email if no representative exists.
            // company_email is stored plain; company_name is encrypted. We intentionally do
            // NOT filter contract_parties.status: a party already linked to this contract must
            // be notified even if the master party record is marked inactive.
            $firstParty = ContractParties::query()
                ->select('contract_parties.id', 'contract_parties.company_name', 'contract_parties.company_email')
                ->join('contract_party_data', 'contract_party_data.contract_party_exe_id', '=', 'contract_parties.id')
                ->where('contract_party_data.custom_field_group_id', $contract->id)
                ->where('contract_party_data.contract_party_type', 'External')
                ->orderBy('contract_parties.id', 'asc')
                ->first();
            if ($firstParty && !empty($firstParty->company_email)) {
                $companyName = 'Company';
                try {
                    $companyName = trim((string)(decryptString($firstParty->company_name, 'company_name') ?? '')) ?: 'Company';
                } catch (\Throwable $e) {
                }
                $emails[] = $firstParty->company_email;
                $emailDetails[] = ['email' => $firstParty->company_email, 'name' => $companyName];
            }
        }

        $emails = array_unique(array_filter($emails));
        if (empty($emails)) {
            return -1;
        }

        // Resolve the cloud-file permission level configured for the negotiation group
        // and stamp it on each recipient so createApprovalRows persists + grants it.
        $negLevel = 'editor';
        $negConfig = $this->getApprovalConfig($contract->rules_id);
        if (is_array($negConfig) && !empty($negConfig['negotiation'][0]['file_permission'])) {
            $negLevel = $negConfig['negotiation'][0]['file_permission'];
        }
        foreach ($emailDetails as $k => $d) {
            $emailDetails[$k]['file_permission'] = $negLevel;
        }

        // First, deactivate any existing negotiation approval rows
        ApprovalContracts::where('contract_id', $contract->id)
            ->where('stage_name', 'negotiation')
            ->update(['flag' => 0]);

        // Create approval records for each external party/representative. The first
        // (flag=1) recipient is granted their configured file permission inside
        // createApprovalRows.
        $this->createApprovalRows($contract->id, $flowType, 'negotiation', $emailDetails);

        // Activate the first approver in the negotiation group
        $firstNegotiationApproval = ApprovalContracts::where('contract_id', $contract->id)
            ->where('stage_name', 'negotiation')
            ->orderBy('orderval', 'asc')
            ->first();
        if ($firstNegotiationApproval) {
            $firstNegotiationApproval->flag = 1;
            $firstNegotiationApproval->save();
        }

        // Download the current contract document from the storage backend to a local
        // temp file so the actual file is attached to the email (not just a link).
        $localAttachment = null;
        $attachName = $contract->contract_attachment_filename ?: ('contract_' . $contract->id . '.pdf');
        if (!empty($contract->contract_attachment)) {
            try {
                $ext = pathinfo($attachName, PATHINFO_EXTENSION);
                $localName = 'negotiation_' . $contract->id . '_' . time() . ($ext ? '.' . $ext : '');
                $relPath = 'contracts/tempDocs/' . $localName;
                // downloadUrl may return a PSR-7 stream (Google) or a string; normalise.
                $content = (string) fileStorageTypeController()->downloadUrl($contract->contract_attachment, $localName);
                if ($content !== '' && strncasecmp($content, 'Error', 5) !== 0) {
                    Storage::disk('local')->put($relPath, $content);
                    $localAttachment = storage_path('app/' . $relPath);
                }
            } catch (\Throwable $e) {
                Log::error('negotiationEmail attachment download failed: ' . $e->getMessage());
            }
        }

        $emailController = new ContractNotificationController();

        // Send negotiation approval emails to each recipient (with the document attached).
        foreach ($emailDetails as $recipient) {
            try {
                if (!empty($recipient['email'])) {
                    $emailController->sendEmail(
                        $contract->id,
                        '',
                        'Contract Review & Negotiation',
                        $recipient,
                        'Negotiation',
                        $localAttachment ? [$attachName] : [],
                        $localAttachment ? [$localAttachment] : [],
                        'negotiationApproval'
                    );
                }
            } catch (\Throwable $e) {
                Log::error('negotiationEmail send error to ' . $recipient['email'] . ': ' . $e->getMessage());
            }
        }

        // Intimate the contract owner that negotiation email(s) were dispatched, listing
        // the recipient(s). Failure to notify the owner must not block the negotiation.
        $this->notifyOwnerNegotiationSent($contract, $emailDetails, $localAttachment, $attachName);

        return $emailDetails;
    }

    /**
     * Send an intimation email to the contract owner (coordinator, falling back to the
     * contract's primary user) that negotiation review email(s) were sent to the given
     * external recipients. Reuses the generic 'notiMail' template.
     */
    private function notifyOwnerNegotiationSent($contract, $emailDetails, $localAttachment = null, $attachName = null)
    {
        try {
            // Prefer the contract owner (coordinator) id, else the primary-user resolver.
            $ownerEmail = null;
            if (!empty($contract->owner) && is_numeric($contract->owner)) {
                $ownerUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))
                    ->find($contract->owner);
                if ($ownerUser) {
                    $ownerEmail = $ownerUser->Email ?? null;
                }
            }
            if (empty($ownerEmail)) {
                $ownerEmail = $this->resolveContractPrimaryUserEmail($contract);
            }
            if (empty($ownerEmail)) {
                return;
            }

            $recipientList = implode(', ', array_map(function ($d) {
                $email = $d['email'] ?? '';
                $name = $d['name'] ?? '';
                return $name !== '' ? ($name . ' (' . $email . ')') : $email;
            }, $emailDetails));

            $message = 'The contract has been sent for negotiation review. Negotiation email(s) were sent to the following external recipient(s): ' . $recipientList . '.';

            (new ContractNotificationController())->sendEmail(
                $contract->id,
                $message,
                'Negotiation Email Sent',
                $ownerEmail,
                'Negotiation Intimation',
                $localAttachment ? [$attachName] : [],
                $localAttachment ? [$localAttachment] : [],
                'notiMail'
            );
        } catch (\Throwable $e) {
            Log::error('notifyOwnerNegotiationSent failed for contract ' . $contract->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Create approval_contracts rows for a pre-approval / approval stage.
     *
     * Each stage is an array of groups. A group has the shape:
     *   {
     *     "role": "Approver",
     *     "approval_type": "sequential" | "parallel",
     *     "auto_next_enabled": 0,
     *     "dynamic_approver_enabled": 0,
     *     "approvers": [ {"id":1,"type":"name","name":"Admin","email":"..."}, ... ]
     *   }
     *
     * Placeholder groups (e.g. negotiation) may instead be a flat array of
     * simple approver descriptors [{"email":..,"name":..}].
     *
     * The first approver of the first group is activated (flag = 1). For
     * sequential groups only the first approver is active; parallel groups
     * activate all approvers in the first group.
     */
    private function createApprovalRows($contractId, $flowType, $stageName, $groups)
    {
        if (empty($groups) || !is_array($groups)) {
            return;
        }

        // Re-entering a stage (reject-back, re-sent negotiation, etc.) creates a fresh
        // batch of rows. Mark any prior rows for this stage as superseded so the old
        // batch (which may contain approved/rejected rows) is excluded from the stage
        // completion / group-activation checks. They remain for the approval history.
        ApprovalContracts::where('contract_id', $contractId)
            ->where('flow_type', $flowType)
            ->where('stage_name', $stageName)
            ->update(['superseded' => 1, 'flag' => 0]);

        $existingMaxOrder = ApprovalContracts::where('contract_id', $contractId)->max('orderval') ?? 0;
        $orderVal = $existingMaxOrder + 1;
        $createdby = json_encode(['email' => Helpers::userInfo()->email ?? 'System', 'name' => Helpers::userInfo()->FirstName ?? 'System']);

        // Rows activated in this batch (flag = 1) whose approvers should be granted
        // their configured cloud-file permission once creation completes.
        $activatedRows = [];

        foreach ($groups as $groupIndex => $group) {
            $groupData = is_object($group) ? json_decode(json_encode($group), true) : $group;

            // Determine group type and the actual list of approvers.
            $groupType = 'sequential';
            $groupRole = 'Approver';
            $dynamicEnabled = 0;
            $groupFilePermission = 'editor';
            $approvers = [];

            if (is_array($groupData) && isset($groupData['approvers'])) {
                // Standard group shape with nested approvers.
                $groupType = strtolower($groupData['approval_type'] ?? 'sequential');
                $groupRole = $groupData['role'] ?? 'Approver';
                $dynamicEnabled = (int)($groupData['dynamic_approver_enabled'] ?? 0) === 1 ? 1 : 0;
                $groupFilePermission = $groupData['file_permission'] ?? 'editor';
                $rawApprovers = $groupData['approvers'];
                $approvers = is_array($rawApprovers) ? $rawApprovers : (json_decode((string)$rawApprovers, true) ?: []);
            } elseif (is_array($groupData) && (isset($groupData['email']) || isset($groupData['id']))) {
                // A single approver descriptor passed directly (e.g. placeholder).
                $approvers = [$groupData];
            } elseif (is_array($groupData)) {
                // A flat list of approver descriptors.
                $approvers = $groupData;
            } else {
                continue;
            }

            $groupKey = $flowType . '_' . $stageName . '_' . $groupIndex;
            $isFirstGroup = ($groupIndex === 0);
            $isParallel = ($groupType === 'parallel');
            $firstInGroup = true;

            foreach ($approvers as $approver) {
                $ap = is_object($approver) ? json_decode(json_encode($approver), true) : $approver;

                // Resolve email/name. Prefer values embedded in the config; fall
                // back to a user lookup by id when only an id is available.
                $email = '';
                $name = '';
                if (is_array($ap)) {
                    $email = $ap['email'] ?? $ap['approver_email'] ?? '';
                    $name = $ap['name'] ?? $ap['approver_name'] ?? '';
                    if (empty($email) && !empty($ap['id'])) {
                        $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))
                            ->where('id', $ap['id'])->first();
                        if ($apUser) {
                            $email = $apUser->Email ?? '';
                            $name = $name ?: ($apUser->FirstName ?? '');
                        }
                    }
                } else {
                    // Scalar approver (id or email string)
                    if (is_numeric($approver)) {
                        $apUser = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))
                            ->where('id', $approver)->first();
                        if ($apUser) {
                            $email = $apUser->Email ?? '';
                            $name = $apUser->FirstName ?? '';
                        }
                    } else {
                        $email = (string)$approver;
                    }
                }

                if (empty($email)) {
                    continue;
                }

                // Activation rule: first group only. Sequential activates just
                // the first approver; parallel activates every approver.
                $flag = 0;
                if ($isFirstGroup) {
                    $flag = $isParallel ? 1 : ($firstInGroup ? 1 : 0);
                }

                // Per-approver override (flat descriptors, e.g. negotiation) takes
                // precedence over the group-level level.
                $filePermission = (is_array($ap) && !empty($ap['file_permission'])) ? $ap['file_permission'] : $groupFilePermission;

                $username = json_encode(['email' => $email, 'name' => $name]);
                $createdRow = ApprovalContracts::create([
                    'contract_id' => $contractId,
                    'username' => encryptString($username, 'username'),
                    'unique_id' => $groupKey,
                    'status' => encryptString('Pending', 'status'),
                    'approval_status' => encryptStringx('pending', 'approval_contracts.approval_status'),
                    'orderval' => $orderVal,
                    'flag' => $flag,
                    'flow_type' => $flowType,
                    'stage_name' => $stageName,
                    'group_key' => $groupKey,
                    'approval_type_row' => $groupType,
                    'approver_type_row' => $groupRole,
                    'dynamic_approver_enabled' => $dynamicEnabled,
                    'file_permission' => $filePermission,
                    'created_by' => $createdby,
                ]);

                if ($flag === 1) {
                    $activatedRows[] = $createdRow;
                }

                $orderVal++;
                $firstInGroup = false;
            }
        }

        // Grant the freshly-activated approvers their configured cloud-file permission.
        if (!empty($activatedRows)) {
            $contract = Contract::find($contractId);
            if ($contract) {
                $this->grantGroupFilePermission($contract, $activatedRows);
            }
        }
    }

    /**
     * Display negotiation review page for guest access
     */
    public function negotiationAccess(Request $request, $accessSlug)
    {
        DB::beginTransaction();
        try {
            $tempUser = ExternalTempUser::where('accessSlug', $accessSlug)
                ->where('is_active', 1)
                ->first();

            if (!$tempUser) {
                // Render a terminal page rather than redirecting back (a used/expired
                // link would otherwise bounce and cause a redirect loop).
                return view('contract::contracts.negotiationReview', [
                    'tokenExpired' => true,
                    'accessSlug' => $accessSlug
                ]);
            }

            $expiryDate = $tempUser->accessExpiryDate;
            if (strtotime($expiryDate) < strtotime(date('Y-m-d'))) {
                return view('contract::contracts.negotiationReview', [
                    'tokenExpired' => true,
                    'accessSlug' => $accessSlug
                ]);
            }

            $contract = Contract::findOrFail($tempUser->contract_id);

            // contract_name is encrypted; fall back to the unique id if it can't be read.
            $contractName = $contract->contract_unique_id;
            try {
                $decName = decryptString($contract->contract_name, 'contract_name');
                if (!empty($decName)) {
                    $contractName = $decName;
                }
            } catch (\Throwable $e) {
            }

            // Build a storage-aware EDITABLE URL (Google/Microsoft/Local) so the external
            // negotiator opens the document in edit mode and the cloud tracks their changes
            // as versions of the same file — not a bare public-disk asset() path.
            $attachmentUrl = null;
            if (!empty($contract->contract_attachment)) {
                $attachmentUrl = fileViewUrl($contract->contract_attachment, false, true);
            }

            DB::commit();

            return view('contract::contracts.negotiationReview', [
                'contract' => $contract,
                'contractName' => $contractName,
                'accessSlug' => $accessSlug,
                'attachmentUrl' => $attachmentUrl,
                'expiryDate' => $expiryDate,
                'tokenExpired' => false
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('negotiationAccess error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to access contract review page.');
        }
    }

    /**
     * Handle negotiation approval response
     */
    public function negotiationRespond(Request $request, $accessSlug)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:accept,reject',
            'attachment_file' => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls|max:50000',
            'comments' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $tempUser = ExternalTempUser::where('accessSlug', $accessSlug)
                ->where('is_active', 1)
                ->first();

            if (!$tempUser) {
                // Terminal page (e.g. after the response was already submitted / link used)
                // to avoid a redirect loop back into negotiationAccess.
                DB::rollBack();
                return view('contract::contracts.negotiationReview', [
                    'tokenExpired' => true,
                    'accessSlug' => $accessSlug
                ]);
            }

            $expiryDate = $tempUser->accessExpiryDate;
            if (strtotime($expiryDate) < strtotime(date('Y-m-d'))) {
                DB::rollBack();
                return view('contract::contracts.negotiationReview', [
                    'tokenExpired' => true,
                    'accessSlug' => $accessSlug
                ]);
            }

            $contract = Contract::findOrFail($tempUser->contract_id);
            $action = $request->input('action');
            $comments = $request->input('comments');

            // Handle file upload if provided
            $newAttachmentPath = null;
            if ($request->hasFile('attachment_file')) {
                $file = $request->file('attachment_file');
                $fileName = 'contract_' . $contract->id . '_' . time() . '.' . $file->getClientOriginalExtension();

                // Determine which storage controller to use based on config
                $storageType = fileStorageType();
                if ($storageType === 'Google') {
                    $fileController = new GoogleDriveController();
                } elseif ($storageType === 'Microsoft') {
                    $fileController = new MicrosoftDriveController();
                } else {
                    $fileController = new LocalDriveController();
                }

                $newAttachmentPath = $fileController->storeFile($file, 'negotiation', $contract->id, $fileName);

                // Update contract with new attachment
                $contract->update([
                    'contract_attachment' => $newAttachmentPath,
                    'contract_attachment_filename' => $fileName
                ]);
            }

            // Find the active negotiation approval record
            $approval = ApprovalContracts::where('contract_id', $contract->id)
                ->where('stage_name', 'negotiation')
                ->where('flag', 1)
                ->first();

            if ($approval) {
                $usernameKey = 'approver_' . $approval->id;
                try {
                    $decUsername = decryptString($approval->username, 'username');
                    $decArr = json_decode($decUsername, true);
                    $usernameKey = $decArr['email'] ?? $usernameKey;
                } catch (\Throwable $e) {
                }

                // Build action description
                $actionDescription = $action === 'accept' ? 'Approved' : 'Rejected';
                if (!empty($newAttachmentPath)) {
                    $actionDescription .= ' with modified attachment';
                }
                if (!empty($comments)) {
                    $actionDescription .= ': ' . $comments;
                }

                // Update approval status
                $approval->approval_status = encryptStringx($action === 'accept' ? 'approved' : 'rejected', 'approval_contracts.approval_status');
                $approval->status = encryptString($action === 'accept' ? 'Approved' : 'Rejected', $usernameKey);
                $approval->next_action_description = encryptString($actionDescription, $usernameKey);
                $approval->flag = 0;
                $approval->updated_on = date('Y-m-d H:i:s');
                $approval->updated_by = json_encode(['email' => $tempUser->email, 'name' => $tempUser->name]);
                $approval->button_text = ($action === 'accept' ? 'Accepted' : 'Rejected') . ' on ' . date('d M Y H:i');

                // Store any modified attachment in audit trail
                if ($newAttachmentPath) {
                    $attachments = json_decode($approval->attachments ?? '[]', true) ?: [];
                    $attachments[] = $newAttachmentPath;
                    $approval->attachments = json_encode($attachments);

                    $attachmentFilenames = json_decode($approval->attachments_filename ?? '[]', true) ?: [];
                    $attachmentFilenames[] = $request->file('attachment_file')->getClientOriginalName();
                    $approval->attachments_filename = json_encode($attachmentFilenames);
                }

                $approval->save();

                // The external negotiator has responded -> drop them to read-only on
                // the contract document (mirrors the internal group-completion logic).
                $this->downgradeRowsToReadonly($contract, [$approval]);

                // Advance to the next stage via the contract's _parent_routing rules
                // (shared with the internal grouped flow and preApprovalRespond). A
                // null next stage on accept is treated as terminal -> Signing.
                $flowType = $approval->flow_type ?: 'preapproval';
                $nextStage = $this->transitionApprovalStage(
                    $contract,
                    $approval->stage_name ?: 'negotiation',
                    $action === 'accept' ? 'approve' : 'reject',
                    $flowType,
                    true
                );
                if ($action === 'accept' && !$nextStage) {
                    $contract->update([
                        'contract_status' => 'Signing',
                        'substatus' => 'Approved'
                    ]);
                }
            }

            // Deactivate the temporary access
            $tempUser->is_active = 0;
            $tempUser->opened = 1;
            $tempUser->opened_date = date('Y-m-d H:i:s');
            $tempUser->save();

            DB::commit();

            $successMessage = $action === 'accept'
                ? 'Contract accepted successfully. It will proceed to the next stage.'
                : 'Contract rejected successfully. It has been returned for revision.';

            // Render the confirmation directly instead of redirecting back: the temp
            // access was just deactivated, so a redirect to negotiationAccess would fail
            // its is_active lookup and bounce back, causing a redirect loop.
            return view('contract::contracts.negotiationReview', [
                'successMessage' => $successMessage,
                'accessSlug' => $accessSlug,
                'tokenExpired' => false,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('negotiationRespond error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to process your response: ' . $e->getMessage());
        }
    }
}
