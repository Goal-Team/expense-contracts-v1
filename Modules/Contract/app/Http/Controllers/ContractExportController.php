<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AddUsers;
use App\Models\Category;
use App\Models\ContractParties;
use App\Models\ContractType;
use App\Models\CustomFields;
use App\Models\Contract;

use App\Models\ContractPartyData;
use App\Models\ContractCategories;
use App\Models\EntityBusiness;
use App\Models\EntityMain;

use App\Models\State;

use Illuminate\Support\Facades\DB;

use Modules\Contract\Services\ContractListFilters;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractExportController extends Controller
{

    private function getExportColumnOptions()
    {
        return [
            'sl_no' => 'Sl No',
            'contract_id' => 'Contract Id',
            'contract_status' => 'Contract Status',
            'contract_sub_status' => 'Contract Sub Status',
            'contract' => 'Contract',
            'contract_type_name' => 'Contract Type Name',
            'department' => 'Department',
            'category' => 'Category',
            'exclusivity' => 'Exclusivity',
            'contract_description' => 'Contract Description',
            'previous_contract_no' => 'Previous Contract No',
            'party_1_type' => 'Party 1 Type',
            'party_1_internal_party_name' => 'Party 1 Internal Party Name',
            'party_1_internal_location' => 'Party 1 Internal Location (Branch Address)',
            'party_1_external_party_name' => 'Party 1 External Party Name',
            'party_2_type' => 'Party 2 Type',
            'party_2_internal_party_name' => 'Party 2 Internal Party Name',
            'party_2_internal_location' => 'Party 2 Internal Location (Branch Address)',
            'party_2_external_party_name' => 'Party 2 External Party Name',
            'other_internal_parties' => 'Other Internal Partys',
            'other_external_parties' => 'Other External Partys',
            'coordinator' => 'Co-ordinator',
            'signatory' => 'Signatory',
            'signing_date' => 'Signing Date',
            'commencement_type' => 'Commencement Type',
            'commencement_date' => 'Commencement Date',
            'contract_end_type' => 'Contract End Type',
            'end_date_of_contract' => 'End date of contract',
            'contract_currency' => 'Contract Currency',
            'contract_value' => 'Contract Value',
            'type_of_renewal' => 'Type of Renewal',
            'period_of_auto_renewal' => 'Period of auto renewal',
            'condition_for_end_of_contract' => 'Condition for end of contract',
            'enable_reminder' => 'Enable Reminder',
            'first_level_alert_me_about' => 'First level Alert Me about',
            'first_level_alert_me_on' => 'First level Alert Me on',
            'first_level_repeats' => 'First level Repeats',
            'second_level_alert_me_about' => 'Second level Alert Me about',
            'second_level_alert_me_on' => 'Second level Alert Me on',
            'second_level_repeats' => 'Second level Repeats',
            'escalation_level_alert_me_about' => 'Escalation level Alert Me about',
            'escalation_level_alert_me_on' => 'Escalation level Alert Me on',
            'escalation_level_repeats' => 'Escalation level Repeats',
            'payment_schedule' => 'Payment Schedule',
            'payment_terms' => 'Payment Terms',
            'billing_frequency' => 'Billing Frequency',
            'taxes_and_fees' => 'Taxes and Fees',
            'escalation_clauses' => 'Escalation Clauses',
            'discounts_or_rebates' => 'Discounts or Rebates',
            'retention_or_holdbacks' => 'Retention or Holdbacks',
            'payment_escrow' => 'Payment Escrow',
            'financial_guarantees_or_bonds' => 'Financial Guarantees or Bonds',
            'party_1_external_vendor_code' => 'Party 1 External Vendor Code',
            'party_2_external_vendor_code' => 'Party 2 External Vendor Code',
            'other_external_vendor_codes' => 'Other External Vendor Codes',
            'party_1_external_pan' => 'Party 1 External PAN',
            'party_2_external_pan' => 'Party 2 External PAN',
            'other_external_pans' => 'Other External PANs',
            'attachment' => 'Attachment',
            'custom_fields' => 'Custom Fields'
        ];
    }

    private function getExportColumnLetterMap()
    {
        return [
            'sl_no' => 'A',
            'contract_id' => 'B',
            'contract_status' => 'C',
            'contract_sub_status' => 'D',
            'contract' => 'E',
            'contract_type_name' => 'F',
            'department' => 'G',
            'category' => 'H',
            'exclusivity' => 'I',
            'contract_description' => 'J',
            'previous_contract_no' => 'K',
            'party_1_type' => 'L',
            'party_1_internal_party_name' => 'M',
            'party_1_internal_location' => 'N',
            'party_1_external_party_name' => 'O',
            'party_2_type' => 'P',
            'party_2_internal_party_name' => 'Q',
            'party_2_internal_location' => 'R',
            'party_2_external_party_name' => 'S',
            'other_internal_parties' => 'T',
            'other_external_parties' => 'U',
            'coordinator' => 'V',
            'signatory' => 'W',
            'signing_date' => 'X',
            'commencement_type' => 'Y',
            'commencement_date' => 'Z',
            'contract_end_type' => 'AA',
            'end_date_of_contract' => 'AB',
            'contract_currency' => 'AC',
            'contract_value' => 'AD',
            'type_of_renewal' => 'AE',
            'period_of_auto_renewal' => 'AF',
            'condition_for_end_of_contract' => 'AG',
            'enable_reminder' => 'AH',
            'first_level_alert_me_about' => 'AI',
            'first_level_alert_me_on' => 'AJ',
            'first_level_repeats' => 'AK',
            'second_level_alert_me_about' => 'AL',
            'second_level_alert_me_on' => 'AM',
            'second_level_repeats' => 'AN',
            'escalation_level_alert_me_about' => 'AO',
            'escalation_level_alert_me_on' => 'AP',
            'escalation_level_repeats' => 'AQ',
            'payment_schedule' => 'AR',
            'payment_terms' => 'AS',
            'billing_frequency' => 'AT',
            'taxes_and_fees' => 'AU',
            'escalation_clauses' => 'AV',
            'discounts_or_rebates' => 'AW',
            'retention_or_holdbacks' => 'AX',
            'payment_escrow' => 'AY',
            'financial_guarantees_or_bonds' => 'AZ',
            'party_1_external_vendor_code' => 'BA',
            'party_2_external_vendor_code' => 'BB',
            'other_external_vendor_codes' => 'BC',
            'party_1_external_pan' => 'BD',
            'party_2_external_pan' => 'BE',
            'other_external_pans' => 'BF',
            'attachment' => 'BG'
        ];
    }

    private function applySelectedColumns($sheet, array $selectedColumns)
    {
        $selectedColumns = array_values(array_unique($selectedColumns));
        $columnLetterMap = $this->getExportColumnLetterMap();

        if (count($selectedColumns) === 0) {
            $selectedColumns = array_keys($columnLetterMap);
            $selectedColumns[] = 'custom_fields';
        }

        $selectedCoreColumnLetters = [];
        foreach ($selectedColumns as $selectedColumn) {
            if (isset($columnLetterMap[$selectedColumn])) {
                $selectedCoreColumnLetters[] = $columnLetterMap[$selectedColumn];
            }
        }

        $selectedCoreColumnIndexes = [];
        foreach ($selectedCoreColumnLetters as $columnLetter) {
            $selectedCoreColumnIndexes[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnLetter);
        }

        $keepCustomFields = in_array('custom_fields', $selectedColumns, true);
        $keepAttachment = in_array('attachment', $selectedColumns, true);
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Core columns now occupy A..BG (1..59); custom fields start at BH (60).
        // Remove attachment column (BG) when not selected.
        if (!$keepAttachment && $highestColumnIndex >= 59) {
            $sheet->removeColumn('BG');
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        }

        // Remove custom fields (BH onwards) when not selected.
        if (!$keepCustomFields && $highestColumnIndex >= 60) {
            $sheet->removeColumn('BH', $highestColumnIndex - 59);
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        }

        // Remove core columns from right to left so indexes remain stable.
        for ($index = min($highestColumnIndex, 59); $index >= 1; $index--) {
            if (!in_array($index, $selectedCoreColumnIndexes, true)) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
                $sheet->removeColumn($columnLetter);
            }
        }
    }

    public function __construct() {
      if(Controller::checkCurrentAuth("Contracts") != 1){
          return abort('404');
      }        
    }





    public function contractBuilkImport(Request $request)
    {
        // The filters arrive on the query string, straight from the list page's
        // Contract Export button (ticket 10). The blade passes them through on the
        // form action - this page picks nothing itself any more, so no contract
        // type list and no cookie is read.
        return view('contract::contractimport.contractBuilkExport')
            ->with('exportColumns', $this->getExportColumnOptions());
    }

    public function bulkDownload(Request $request)
    {
        $selectedColumns = $request->input('export_columns', []);
        $allInOnePage = (int) $request->input('all_in_one_page', 0) === 1;

        // $cookies = \Cookie::get();
        // print_r($cookies);
        // die;
        $spreadsheet = new Spreadsheet();

        $writer = new Xlsx($spreadsheet);

        $sheet = $spreadsheet->getActiveSheet();

        $checkDepartment = EntityBusiness::pluck('name', 'id')->toArray();

        $checkCategory = ContractCategories::pluck('name', 'id')->toArray();

        $checkInternalPartyNameTemp = EntityMain::select('id',decrypt_data('Nameoftheentity', 'entity'))->get();
        $checkInternalPartyName = [];
        foreach ($checkInternalPartyNameTemp as $checkInternalPartyNam) {
            $checkInternalPartyName[$checkInternalPartyNam->id] = $checkInternalPartyNam->Nameoftheentity;
        }

        $checkPartyInternalLocationTemp = DB::table('branch')->select('id',decrypt_data('BranchName', 'branch'))->get();
        $checkPartyInternalLocation = [];
        foreach ($checkPartyInternalLocationTemp as $checkPartyInternalLocatio) {
            $checkPartyInternalLocation[$checkPartyInternalLocatio->id] = $checkPartyInternalLocatio->BranchName;
        }

        $checkPartyExternalPartyNameTemp =  ContractParties::select('id','company_name', 'state', 'gst', 'vendor_code', 'active_vendor_code', 'pan')->get();
        $checkPartyExternalPartyName = [];
        $checkPartyExternalVendorCode = [];
        $checkPartyExternalPan = [];
        foreach ($checkPartyExternalPartyNameTemp as $contractPartie) {
            if (decryptString($contractPartie, 'company_name') != null) {

                $cname  = decryptString($contractPartie->company_name, 'company_name');
                if (isset($contractPartie->state) && $contractPartie->state > 0) {

                    $state = State::select("name", "id")

                        ->where('id', $contractPartie->state)

                        ->pluck('name')->first();

                    $cname .= ':' . $state;
                }

                if (isset($contractPartie->gst)) {

                    $cname .= ':' . decryptString($contractPartie->gst, 'gst');
                }
                $checkPartyExternalPartyName[$contractPartie->id] = $cname;

                // Vendor code values from contract_parties (vendor_code, active_vendor_code).
                $vendorCode = trim((string) ($contractPartie->vendor_code ?? ''));
                $activeVendorCode = trim((string) ($contractPartie->active_vendor_code ?? ''));
                if ($vendorCode !== '' && $activeVendorCode !== '') {
                    $checkPartyExternalVendorCode[$contractPartie->id] = $vendorCode . ' / ' . $activeVendorCode;
                } elseif ($vendorCode !== '') {
                    $checkPartyExternalVendorCode[$contractPartie->id] = $vendorCode;
                } elseif ($activeVendorCode !== '') {
                    $checkPartyExternalVendorCode[$contractPartie->id] = $activeVendorCode;
                } else {
                    $checkPartyExternalVendorCode[$contractPartie->id] = '';
                }

                $pan = trim((string) decryptString($contractPartie->pan, 'pan'));
                $checkPartyExternalPan[$contractPartie->id] = $pan;
            }
        }

        $checkCoordinatorTemp = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->get();

        $checkCoordinator = [];
        foreach($checkCoordinatorTemp as $checkCoordinatorTe){
            $checkCoordinator[$checkCoordinatorTe->id] = $checkCoordinatorTe->Salutation.' '.$checkCoordinatorTe->FirstName.' '.$checkCoordinatorTe->LastName;
        }

        // The list's filter state rides the form action's query string (ticket 10):
        // ?status=..&contype=1,2&concates=..&locations=..&my=1&search=.. - the same
        // values the green Contract Export button copied from the list URL, plus the
        // search box value. ContractListFilters is the same service the list AJAX
        // uses, so the exported rows always equal the rows the list shows.
        $filters = new ContractListFilters();
        $base = $filters->filtered([
            'contype' => $request->query('contype'),
            'concates' => $request->query('concates'),
            'locations' => $request->query('locations'),
            'my' => $request->query('my'),
        ]);
        // No status parameter means no list filter was on: export everything visible.
        $filters->applyStatus($base, (string) $request->query('status', 'all'));
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $filters->applySearch($base, $search);
        }

        // One sheet per contract type: the picked types when contype is set, every
        // type otherwise. Types with no matching contract are skipped below.
        $contypes = ContractListFilters::parseIdList($request->query('contype'));
        if (count($contypes) > 0) {
            $ContractTypes = ContractType::whereIn('contract_type_id', $contypes)
                ->pluck('short_name', 'contract_type_id')
                ->toArray();
        } else {
            $ContractTypes = ContractType::pluck('short_name', 'contract_type_id')->toArray();
        }


        $hasExportSheet = false;
        $allInOneSheet = null;
        $allInOneRow = 3;

        foreach ($ContractTypes as $key => $ContractType) {

            // One query per type sheet, on the shared filtered query. contracts.*
            // because the sheet writer below reads far more columns than the list's
            // slim select - the list keeps its own 12-column select.
            $contracts = (clone $base)
                ->where('contracts.contract_type', $key)
                ->orderBy('contracts.id', 'desc')
                ->select('contracts.*')
                ->get();

            if (count($contracts) === 0) {
                continue;
            }

            // Commencement Type
            $isAllInOneContinuation = false;
            if ($allInOnePage) {
                if ($allInOneSheet === null) {
                    $allInOneSheet = clone $sheet;
                    $allInOneSheet->setTitle('All Contracts');
                } else {
                    $isAllInOneContinuation = true;
                }
                $newSheet = $allInOneSheet;
            } else {
                $newSheet = clone $sheet;
                $newSheet->setTitle($ContractType);
            }

            if (!$isAllInOneContinuation) {
                // Basic Contract Information
                $newSheet->setCellValue('A1', 'Basic Contract Information');
                $newSheet->mergeCells('A1:K1');
                $newSheet->setCellValue('A2', 'Sl No');
                $newSheet->setCellValue('B2', 'Contract Id');
                $newSheet->setCellValue('C2', 'Contract Status');
                $newSheet->setCellValue('D2', 'Contract Sub Status');
                $newSheet->setCellValue('E2', 'Contract');
                $newSheet->setCellValue('F2', 'Contract Type Name');
                $newSheet->setCellValue('G2', 'Department');
                $newSheet->setCellValue('H2', 'Category');
                $newSheet->setCellValue('I2', 'Exclusivity');
                $newSheet->setCellValue('J2', 'Contract Description');
                $newSheet->setCellValue('K2', 'Previous Contract No');

                // Party Details Section
                $newSheet->setCellValue('L1', 'Party Details');
                $newSheet->mergeCells('L1:U1');
                $newSheet->setCellValue('L2', 'Party 1 Type');
                $newSheet->setCellValue('M2', 'Party 1 Internal Party Name');
                $newSheet->setCellValue('N2', 'Party 1 Internal Location (Branch Address)');
                $newSheet->setCellValue('O2', 'Party 1 External Party Name');
                $newSheet->setCellValue('P2', 'Party 2 Type');
                $newSheet->setCellValue('Q2', 'Party 2 Internal Party Name');
                $newSheet->setCellValue('R2', 'Party 2 Internal Location (Branch Address)');
                $newSheet->setCellValue('S2', 'Party 1 External Party Name');
                $newSheet->setCellValue('T2', 'Other Internal Partys');
                $newSheet->setCellValue('U2', 'Other External Partys');

                // Ownership Section
                $newSheet->setCellValue('V1', 'Ownership');
                $newSheet->mergeCells('V1:W1');
                $newSheet->setCellValue('V2', 'Co-ordinator');
                $newSheet->setCellValue('W2', 'Signatory');
                $newSheet->setCellValue('X2', 'Signing Date');

                // Contract Duration Section
                $newSheet->setCellValue('Y1', 'Contract Duration');
                $newSheet->mergeCells('Y1:AQ1');
                $newSheet->setCellValue('Y2', 'Commencement Type');
                $newSheet->setCellValue('Z2', 'Commencement Date');
                $newSheet->setCellValue('AA2', 'Contract End Type');
                $newSheet->setCellValue('AB2', 'End date of contract');
                $newSheet->setCellValue('AC2', 'Contract Currency');
                $newSheet->setCellValue('AD2', 'Contract Value');
                $newSheet->setCellValue('AE2', 'Type of Renewal');
                $newSheet->setCellValue('AF2', 'Period of auto renewal');
                $newSheet->setCellValue('AG2', 'Condition for end of contract');
                $newSheet->setCellValue('AH2', 'Enable Reminder');
                $newSheet->setCellValue('AI2', 'First level Alert Me about');
                $newSheet->setCellValue('AJ2', 'First level Alert Me on');
                $newSheet->setCellValue('AK2', 'First level Repeats');
                $newSheet->setCellValue('AL2', 'Second level Alert Me about');
                $newSheet->setCellValue('AM2', 'Second level Alert Me on');
                $newSheet->setCellValue('AN2', 'Second level Repeats');
                $newSheet->setCellValue('AO2', 'Escalation level Alert Me about');
                $newSheet->setCellValue('AP2', 'Escalation level Alert Me on');
                $newSheet->setCellValue('AQ2', 'Escalation level Repeats');

                // Contract Value Section
                $newSheet->setCellValue('AR1', 'Contract Value');
                $newSheet->mergeCells('AR1:AZ1');
                $newSheet->setCellValue('AR2', 'Payment Schedule');
                $newSheet->setCellValue('AS2', 'Payment Terms');
                $newSheet->setCellValue('AT2', 'Billing Frequency');
                $newSheet->setCellValue('AU2', 'Taxes and Fees');
                $newSheet->setCellValue('AV2', 'Escalation Clauses');
                $newSheet->setCellValue('AW2', 'Discounts or Rebates');
                $newSheet->setCellValue('AX2', 'Retention or Holdbacks');
                $newSheet->setCellValue('AY2', 'Payment Escrow');
                $newSheet->setCellValue('AZ2', 'Financial Guarantees or Bonds');

                // Vendor Code Section (external party vendor codes from contract_parties)
                $newSheet->setCellValue('BA1', 'Vendor Codes');
                $newSheet->mergeCells('BA1:BC1');
                $newSheet->setCellValue('BA2', 'Party 1 External Vendor Code');
                $newSheet->setCellValue('BB2', 'Party 2 External Vendor Code');
                $newSheet->setCellValue('BC2', 'Other External Vendor Codes');

                // PAN Section (external party PAN from contract_parties.pan)
                $newSheet->setCellValue('BD1', 'PAN');
                $newSheet->mergeCells('BD1:BF1');
                $newSheet->setCellValue('BD2', 'Party 1 External PAN');
                $newSheet->setCellValue('BE2', 'Party 2 External PAN');
                $newSheet->setCellValue('BF2', 'Other External PANs');

                // Attachment Section
                $newSheet->setCellValue('BG1', 'Attachment');
                $newSheet->setCellValue('BG2', 'Attachment URL');

                $categorys = Category::where('category_group', 'contract')->get();
                $startingColumn = 'BH';
                $rows = 2;

                foreach ($categorys as $category) {
                    $customFields = CustomFields::where('status', 1)->where('contract_type', $key)->where('category', $category->category_id)->orderBy('order_id')->get();
                    $k = 0;
                    foreach ($customFields as $customField) {
                        $newSheet->setCellValue($startingColumn . $rows,  $customField->field_name );
                        $k++;
                        $startingColumn++;
                    }
                }
            }

            $row = $allInOnePage ? $allInOneRow : 3;


            foreach ($contracts as $key => $con) {

                $cell = 'A' . $row;
                $newSheet->setCellValue($cell, ($row - 2));

                $cell = 'B' . $row;
                $newSheet->setCellValue($cell, $con->contract_unique_id);

                $cell = 'C' . $row; 
                $newSheet->setCellValue($cell, $con->contract_status);


                $cell = 'D' . $row;
                $newSheet->setCellValue($cell, $con->substatus);



                $cell = 'E' . $row;
                $con->contract_mode = decryptString($con->contract_mode, 'contract_mode');
                $con->contract_mode = ($con->contract_mode != 'new') ? 'Legacy Contracts' : 'New';
                $newSheet->setCellValue($cell, $con->contract_mode);

                $cell = 'F' . $row;
                $newSheet->setCellValue($cell, $ContractType);



                $cell = 'G' . $row;
                $newSheet->setCellValue($cell, $checkDepartment[$con->department_id] ?? '');



                $cell = 'H' . $row;
                $newSheet->setCellValue($cell, $checkCategory[$con->catgoery_identity] ?? '');



                $cell = 'I' . $row;
                $con->exclusivity = decryptString($con->exclusivity, 'exclusivity');
                $newSheet->setCellValue($cell, $con->exclusivity);




                $cell = 'J' . $row;
                $con->contract_description = decryptString($con->contract_description, 'contract_description');
                $newSheet->setCellValue($cell, $con->contract_description);



                $cell = 'K' . $row;
                if ($con->parentcontract != null && $con->parentcontract != 0) {
                    $contract_parnt = Contract::where('contract_type', $con->parentcontract)->value('contract_unique_id');
                    $newSheet->setCellValue($cell, $contract_parnt);
                }


                $party_external_partynames = ContractPartyData::where('custom_field_group_id',$con->id)->get();

                $party_external_partynamesadd = '';
                $party_external_expartynamesadd = '';
                $other_external_vendor_codes_add = '';
                $other_external_pans_add = '';

                foreach($party_external_partynames as  $key=>$party_external_partyname){

                    if(isset($party_external_partyname)){    
                        
                        if($key == 0 || $key == 1){
                            if(in_array($party_external_partyname->contract_party_type,['Internal','Intergroup'])){  
                                if($key == 0){
                                    $cell = 'L' . $row;
                                    $newSheet->setCellValue($cell, $party_external_partyname->contract_party_type);
                                    $cell = 'M' . $row;
                                    $newSheet->setCellValue($cell, ($checkInternalPartyName[$party_external_partyname->contract_party_id] ?? ''));

                                    $cell = 'N' . $row;
                                    $newSheet->setCellValue($cell, $checkPartyInternalLocation[$party_external_partyname->contract_party_location_id] ?? ''); 
                                }else{
                                    $cell = 'P' . $row;
                                    $newSheet->setCellValue($cell, $party_external_partyname->contract_party_type);
                                    $cell = 'Q' . $row;
                                    $newSheet->setCellValue($cell, ($checkInternalPartyName[$party_external_partyname->contract_party_id] ?? ''));

                                    $cell = 'R' . $row;
                                    $newSheet->setCellValue($cell, $checkPartyInternalLocation[$party_external_partyname->contract_party_location_id] ?? '');    
                                }
                                
                            }else{
                                if($key == 0){
                                    $cell = 'L' . $row;
                                    $newSheet->setCellValue($cell, $party_external_partyname->contract_party_type);
                                    $cell = 'O' . $row;
                                    $newSheet->setCellValue($cell, $checkPartyExternalPartyName[$party_external_partyname->contract_party_exe_id] ?? '');
                                    // Party 1 External Vendor Code
                                    $cell = 'BA' . $row;
                                    $newSheet->setCellValue($cell, $checkPartyExternalVendorCode[$party_external_partyname->contract_party_exe_id] ?? '');
                                    // Party 1 External PAN
                                    $cell = 'BD' . $row;
                                    $newSheet->setCellValue($cell, $checkPartyExternalPan[$party_external_partyname->contract_party_exe_id] ?? '');
                                }else{
                                    $cell = 'P' . $row;
                                    $newSheet->setCellValue($cell, $party_external_partyname->contract_party_type);
                                    $cell = 'S' . $row;
                                    $newSheet->setCellValue($cell, $checkPartyExternalPartyName[$party_external_partyname->contract_party_exe_id] ?? '');
                                    // Party 2 External Vendor Code
                                    $cell = 'BB' . $row;
                                    $newSheet->setCellValue($cell, $checkPartyExternalVendorCode[$party_external_partyname->contract_party_exe_id] ?? '');
                                    // Party 2 External PAN
                                    $cell = 'BE' . $row;
                                    $newSheet->setCellValue($cell, $checkPartyExternalPan[$party_external_partyname->contract_party_exe_id] ?? '');
                                } 
                            }
                        } else{


                            if(in_array($party_external_partyname->contract_party_type,['Internal','Intergroup'])){ 
                                $party_external_partynamesadd .= ($checkInternalPartyName[$party_external_partyname->contract_party_id] ?? '').'-'.($checkPartyInternalLocation[$party_external_partyname->contract_party_location_id] ?? '').',';
                            }else{
                                
                                $party_external_expartynamesadd = ($checkPartyExternalPartyName[$party_external_partyname->contract_party_exe_id] ?? '').',';
                                $vc = $checkPartyExternalVendorCode[$party_external_partyname->contract_party_exe_id] ?? '';
                                if ($vc !== '') {
                                    $other_external_vendor_codes_add .= $vc . ',';
                                }

                                $pan = $checkPartyExternalPan[$party_external_partyname->contract_party_exe_id] ?? '';
                                if ($pan !== '') {
                                    $other_external_pans_add .= $pan . ',';
                                }
                            }  
                            
                        }
                    }
                }


                $cell = 'T' . $row;
                $newSheet->setCellValue($cell, $party_external_partynamesadd);

                $cell = 'U' . $row;
                $newSheet->setCellValue($cell, $party_external_expartynamesadd);

                // Other External Vendor Codes (aggregated for parties beyond Party 1 / Party 2)
                $cell = 'BC' . $row;
                $newSheet->setCellValue($cell, rtrim($other_external_vendor_codes_add, ','));

                // Other External PANs (aggregated for parties beyond Party 1 / Party 2)
                $cell = 'BF' . $row;
                $newSheet->setCellValue($cell, rtrim($other_external_pans_add, ','));

                $cell = 'V' . $row;
                $newSheet->setCellValue($cell, $checkCoordinator[$con->owner] ?? "");

                $cell = 'W' . $row;
                $newSheet->setCellValue($cell, $checkCoordinator[$con->signatory] ?? "");

                $cell = 'X' . $row;
                $newSheet->setCellValue($cell, $con->signing_date);


                $cell = 'Y' . $row;

                $con->commencement_type =  decryptString($con->commencement_type, 'commencement_type');

                $newSheet->setCellValue($cell, $con->commencement_type);


                $cell = 'Z' . $row;
                $newSheet->setCellValue($cell, $con->fixed_date);


                $cell = 'AA' . $row;
                $con->end_contract_type = decryptString($con->end_contract_type,'end_contract_type');
                $newSheet->setCellValue($cell, $con->end_contract_type);

                $cell = 'AB' . $row;
                $newSheet->setCellValue($cell, (decryptString($con->end_contract_type,'end_contract_type') == 'fixedTerm') ? $con->contract_end_date : '');

                $cell = 'AC' . $row;
                $con->currency_contract = decryptString($con->currency_contract, 'currency_contract');
                $newSheet->setCellValue($cell, $con->currency_contract);


                $cell = 'AD' . $row;
                $con->currency_value = decryptString($con->currency_value,'currency_value');
                $newSheet->setCellValue($cell,$con->currency_value);

                $cell = 'AE' . $row;
                $con->renewal_type = decryptString($con->renewal_type,'renewal_type');
                $newSheet->setCellValue($cell, $con->renewal_type);

                $cell = 'AF' . $row;
                $con->period_auto_renewal = decryptString($con->period_auto_renewal, 'period_auto_renewal');
                $newSheet->setCellValue($cell, $con->period_auto_renewal);

                $cell = 'AG' . $row;
                $con->reminder_enable = decryptString($con->evergreen_condition, 'evergreen_condition');             
                // $newSheet->setCellValue($cell,  $con->evergreen_condition );

                

                $cell = 'AH' . $row;
                $con->reminder_enable = decryptString($con->reminder_enable, 'reminder_enable');             
                $newSheet->setCellValue($cell,  $con->reminder_enable );

                $cell = 'AI' . $row;
                $con->reminder_first_alert =  decryptString($con->reminder_first_alert, 'reminder_first_alert');
                $newSheet->setCellValue($cell,$con->reminder_first_alert);

                $con->reminder_first_alertMeOn = decryptString($con->reminder_first_alertMeOn, 'reminder_first_alertMeOn');
                $cell = 'AJ' . $row;
                $newSheet->setCellValue($cell, $con->reminder_first_alertMeOn);

                $cell = 'AK' . $row;
                $con->reminder_first_alert_repeats =  decryptString($con->reminder_first_alert_repeats, 'reminder_first_alert_repeats');
                $newSheet->setCellValue($cell,$con->reminder_first_alert_repeats);

                $cell = 'AL' . $row;
                $con->reminder_second_alert = decryptString($con->reminder_second_alert,'reminder_second_alert');
                $newSheet->setCellValue($cell, $con->reminder_second_alert);

                $cell = 'AM' . $row;
                $con->reminder_second_alertMeOn = decryptString($con->reminder_second_alertMeOn,'reminder_second_alertMeOn');
                $newSheet->setCellValue($cell, $con->reminder_second_alertMeOn);

                $cell = 'AN' . $row;
                $con->reminder_second_alert_repeats = decryptString($con->reminder_second_alert_repeats,'reminder_second_alert_repeats');
                $newSheet->setCellValue($cell, $con->reminder_second_alert_repeats);

                $cell = 'AO' . $row;
                $con->reminder_escalation_alert = decryptString($con->reminder_escalation_alert,'reminder_escalation_alert');
                $newSheet->setCellValue($cell, $con->reminder_escalation_alert);

                $cell = 'AP' . $row;
                $con->reminder_escalation_alertMeOn =  decryptString($con->reminder_escalation_alertMeOn,'reminder_escalation_alertMeOn');
                $newSheet->setCellValue($cell, $con->reminder_escalation_alertMeOn);

                $cell = 'AQ' . $row;
                $con->reminder_escalation_alert_repeats =  decryptString($con->reminder_escalation_alert_repeats,'reminder_escalation_alert_repeats');
                $newSheet->setCellValue($cell, $con->reminder_escalation_alert_repeats);

                $cell = 'AR' . $row;
                $con->payment_schedule =  decryptString($con->payment_schedule,'payment_schedule');
                $newSheet->setCellValue($cell, $con->payment_schedule);

                
                $cell = 'AS' . $row;
                $con->payment_terms =  decryptString($con->payment_terms,'reminder_escalation_alert_repeats');
                $newSheet->setCellValue($cell, $con->payment_terms);


                $cell = 'AT' . $row;
                $con->billing_frequency =  decryptString($con->billing_frequency,'billing_frequency');
                $newSheet->setCellValue($cell, $con->billing_frequency);

                $cell = 'AU' . $row;
                $con->taxes =  decryptString($con->taxes,'taxes');
                $newSheet->setCellValue($cell, $con->taxes);


                $cell = 'AV' . $row;
                $con->escalation_clauses =  decryptString($con->escalation_clauses,'escalation_clauses');
                $newSheet->setCellValue($cell, $con->escalation_clauses);

                $cell = 'AW' . $row;
                $con->discounts =  decryptString($con->discounts,'discounts');
                $newSheet->setCellValue($cell, $con->discounts);

                $cell = 'AX' . $row;
                $con->retention =  decryptString($con->retention,'retention');
                $newSheet->setCellValue($cell, $con->retention);

                $cell = 'AY' . $row;
                $con->payment_escrow =  decryptString($con->payment_escrow,'payment_escrow');
                $newSheet->setCellValue($cell, $con->payment_escrow);

                $cell = 'AZ' . $row;
                $con->financial_guarantees =  decryptString($con->financial_guarantees,'financial_guarantees');
                $newSheet->setCellValue($cell, $con->financial_guarantees);

                // Attachment URL
                $cell = 'BG' . $row;
                $attachmentUrl = '';
                if (!empty($con->contract_attachment) && function_exists('attachmentDummyUrl')) {
                    $attachmentUrl = attachmentDummyUrl($con->contract_attachment, true, $con->id);
                }
                $newSheet->setCellValue($cell, $attachmentUrl);

                $row++;
            }


            if ($allInOnePage) {
                $allInOneRow = $row;
                $hasExportSheet = true;
                continue;
            }

            $highestColumn = $newSheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $newSheet->getColumnDimension($columnLetter)->setAutoSize(true);
                $newSheet->freezePane('A3');
            }

            $this->applySelectedColumns($newSheet, $selectedColumns);
            $spreadsheet->addSheet($newSheet);
            $hasExportSheet = true;
        }


        if ($hasExportSheet) {
            if ($allInOnePage && $allInOneSheet !== null) {
                $highestColumn = $allInOneSheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $allInOneSheet->getColumnDimension($columnLetter)->setAutoSize(true);
                    $allInOneSheet->freezePane('A3');
                }

                $this->applySelectedColumns($allInOneSheet, $selectedColumns);
                $spreadsheet->addSheet($allInOneSheet);
            }
            $spreadsheet->removeSheetByIndex(0);
        } else {
            $sheet->setTitle('No Data');
            $sheet->setCellValue('A1', 'No contracts found for selected filters.');
        }

        // header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-Disposition: attachment; filename="contractsData.xlsx"');
        // header('Cache-Control: max-age=0');


        //$writer->save('php://output');
        // Stream the file to the browser
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );        
        
        $timestampFile = strtotime(date('Y-m-d H:i:s'));
        $response->headers->set('Content-Type','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set("Content-Disposition","attachment; filename=contracts_export_$timestampFile.xlsx");
        $response->headers->set('Cache-Control','max-age=0');

        setcookie('preload', false, 0, "/");
        return $response;
        exit;
    }
}
