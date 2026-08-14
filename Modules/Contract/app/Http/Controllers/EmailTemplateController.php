<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractParties;
use App\Models\ConsultationMaster;
use App\Models\TestMaster;
use App\Models\LocationMaster;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class EmailTemplateController extends Controller
{
    private $consultations;
    private $tests;
    private $locations;
    private $ceoFinanceEmail = 'SDD@LegalitySimplified.onmicrosoft.com'; // CEO Finance email
    private $corporateFinanceHeadEmail = 'SDD@LegalitySimplified.onmicrosoft.com'; // Corporate Finance Head email
    private $corporateLegalEmail = 'SDD@LegalitySimplified.onmicrosoft.com'; // Corporate Legal email

    public function __construct()
    {
        // Load data from database (with JSON fallback)
        $this->consultations = $this->loadConsultationsFromDB();
        $this->tests = $this->loadTestsFromDB();
        $this->locations = $this->loadLocationsFromDB();
    }

    /**
     * Load consultations from database
     */
    private function loadConsultationsFromDB()
    {
        try {
            $consultations = ConsultationMaster::select('id', 'name', 'price')
                ->where('status', 1)
                ->orderBy('name')
                ->get()
                ->toArray();
            return !empty($consultations) ? $consultations : $this->loadJsonData('consultations.json');
        } catch (\Exception $e) {
            return $this->loadJsonData('consultations.json');
        }
    }

    /**
     * Load tests from database
     */
    private function loadTestsFromDB()
    {
        try {
            $tests = TestMaster::select('id', 'name', 'price')
                ->where('status', 1)
                ->orderBy('name')
                ->get()
                ->toArray();
            return !empty($tests) ? $tests : $this->loadJsonData('tests.json');
        } catch (\Exception $e) {
            return $this->loadJsonData('tests.json');
        }
    }

    /**
     * Load locations from database
     */
    private function loadLocationsFromDB()
    {
        try {
            $locations = LocationMaster::select('id', 'location_name as name', 'region')
                ->where('status', 1)
                ->orderBy('location_name')
                ->get()
                ->toArray();
            return !empty($locations) ? $locations : $this->loadJsonData('locations.json');
        } catch (\Exception $e) {
            return $this->loadJsonData('locations.json');
        }
    }

    /**
     * Load JSON data from storage (fallback)
     */
    private function loadJsonData($filename)
    {
        $path = storage_path('app/data/' . $filename);
        if (! file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true);
    }

    /**
     * Generate email template
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateTemplate(Request $request, $id=0)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'renew' => 'required|boolean',
            'new_contract' => 'required|array',
            'new_contract.agreement_name' => 'required|string',
            'new_contract.customer_id' => 'required|integer',
            //'new_contract.customer_name' => 'required|string',
            'new_contract.scope' => 'required|string',
            'new_contract.entity_type_id' => 'required|integer',
            'new_contract.scope_of_services' => 'required|array',
            'new_contract.discounts' => 'required|array',
            'new_contract.health_check_rows' => 'required|array',
            'new_contract.locations' => 'required|array',
            'new_contract.start_date' => 'required|date',
            'new_contract.end_date' => 'required|date',
            'new_contract.duration_confirmed' => 'required|boolean',
            'new_contract.editor_text' => 'nullable|string',
            'send_email' => 'nullable|boolean', // Optional parameter to send email
            'send_corporate_finance_email' => 'nullable|boolean', // Optional parameter to send corporate finance email
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $requestData = $request->all();
            $contract = $requestData['new_contract'];

            // Check if any discount is greater than 15%
            $hasHighDiscount = $this->checkHighDiscount($contract['discounts']);

            // Get approvers data
            $approverData = $this->getApprovers($contract['locations'], $hasHighDiscount);

            // Build location details
            $locationDetails = $this->buildLocationDetails($approverData['locations']);

            // Build discount details
            $discountDetails = $this->buildDiscountDetails($contract['discounts']);

            // Build health check package details
            $healthCheckDetails = $this->buildHealthCheckDetails($contract['health_check_rows']);
            
            $nme = ContractParties::select('company_name')->where('id', $contract['customer_id'])->first()->company_name ?? false;
            
            $customerName = "";
            if($nme){
                $customerName .= decryptString($nme, 'company_name');   
            }            

            // Prepare structured data
            $structuredData = [
                'contract_info' => [
                    'agreement_name' => $contract['agreement_name'],
                    'contract_id' => $id,
                    'customer_id' => $customerName,
                    'customer_name' => $customerName,
                    'contract_type' => $requestData['renew'] ? 'Renewal' : 'New Contract',
                    'scope' => ucfirst($contract['scope']),
                    'entity_type_id' => $contract['entity_type_id'],
                    'scope_of_services' => $contract['scope_of_services'],
                    'start_date' => $contract['start_date'],
                    'end_date' => $contract['end_date'],
                    'duration_confirmed' => $contract['duration_confirmed'],
                    'editor_text' => $contract['editor_text'] ?? ''
                ],
                'locations' => [
                    'count' => $approverData['location_count'],
                    'region_count' => $approverData['region_count'],
                    'details' => $locationDetails
                ],
                'discounts' => $discountDetails,
                'health_check_packages' => $healthCheckDetails,
                'approvers' => [
                    'list' => $approverData['approvers'],
                    'logic' => [
                        'total_locations' => $approverData['location_count'],
                        'total_regions' => $approverData['region_count'],
                        'has_high_discount' => $hasHighDiscount['has_high_discount'],
                        'max_discount' => $hasHighDiscount['max_discount'],
                        'approval_level' => $this->getApprovalLevel(
                            $approverData['location_count'], 
                            $approverData['region_count'],
                            $hasHighDiscount['has_high_discount']
                        ),
                        'explanation' => $this->getApprovalExplanation(
                            $approverData['location_count'], 
                            $approverData['region_count'],
                            $hasHighDiscount['has_high_discount']
                        )
                    ]
                ],
                'generated_at' => now()->toDateTimeString()
            ];
            
            if($id > 0){
                Contract::where('id', $id)->update(['contractData' => json_encode($structuredData)]);                
            }

            // Generate HTML template
            $htmlTemplate = $this->generateHTMLTemplate($structuredData);

            // Send email if requested
            $emailSent = false;
            $emailResponse = null;
            
            //if (isset($requestData['send_email']) && $requestData['send_email'] === true) {
                $emailResponse = $this->sendApprovalEmail($structuredData, $htmlTemplate);
                $emailSent = $emailResponse['success'];
            //}

            // Send corporate finance email if requested
            $corporateFinanceEmailSent = false;
            $corporateFinanceEmailResponse = null;
            
            if (isset($requestData['renew']) && $requestData['renew'] === true) {
                $corporateFinanceEmailResponse = $this->sendCorporateFinanceEmail($structuredData);
                $corporateFinanceEmailSent = $corporateFinanceEmailResponse['success'];
           }

            // Prepare response
            $response = [
                'success' => true,
                'data' => $structuredData,
                'html_template' => $htmlTemplate,
                'email_sent' => $emailSent,
                'corporate_finance_email_sent' => $corporateFinanceEmailSent
            ];

            if ($emailResponse) {
                $response['email_details'] = $emailResponse;
            }

            if ($corporateFinanceEmailResponse) {
                $response['corporate_finance_email_details'] = $corporateFinanceEmailResponse;
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('Error generating template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send approval email to approvers
     */
    public function sendApprovalEmail($data, $htmlTemplate)
    {

        try {
            // Separate approvers by role
            $emailRecipients = $this->separateApproversByRole($data['approvers']['list']);

            $subject = 'Contract Approval Request - ' . $data['contract_info']['agreement_name'];

            // Send email
            Mail::send([], [], function ($message) use ($emailRecipients, $subject, $htmlTemplate) {
                // Set TO recipients (Unit Heads and CEO Finance)
                if (!  empty($emailRecipients['to'])) {
                    $message->to($emailRecipients['to']);
                }

                // Set CC recipients (Region Heads)
                if (! empty($emailRecipients['cc'])) {
                    $message->cc($emailRecipients['cc']);
                }

                // Set BCC recipients (Finance Head)
                if (!empty($emailRecipients['bcc'])) {
                    $message->bcc($emailRecipients['bcc']);
                }

                $message->subject($subject);
                $message->html($htmlTemplate);
            });

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'recipients' => $emailRecipients,
                'sent_at' => now()->toDateTimeString()
            ];

        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send email to Corporate Finance Head for credit check
     */
    public function sendCorporateFinanceEmail($data)
    {
        try {
            $customerName = $data['contract_info']['customer_name'];
            $agreementName = $data['contract_info']['agreement_name'];
            $contractsListUrl = url('contracts/show/contract-custom/'.$data['contract_info']['contract_id']);

            // Generate Corporate Finance Email Template
            $htmlTemplate = $this->generateCorporateFinanceEmailTemplate($customerName, $agreementName, $contractsListUrl);

            $subject = 'Credit Check Required - ' . $customerName;

            // Send email
            Mail::send([], [], function ($message) use ($subject, $htmlTemplate) {
                $message->to($this->corporateFinanceHeadEmail);
                $message->subject($subject);
                $message->html($htmlTemplate);
            });

            return [
                'success' => true,
                'message' => 'Corporate Finance email sent successfully',
                'recipient' => $this->corporateFinanceHeadEmail,
                'sent_at' => now()->toDateTimeString()
            ];

        } catch (\Exception $e) {
            Log::error('Corporate Finance email sending failed: ' .  $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send Corporate Finance email',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate Corporate Finance Email Template
     */
    private function generateCorporateFinanceEmailTemplate($customerName, $agreementName, $contractsListUrl)
    {
        $html = '<!  DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Credit Check Required - ' . htmlspecialchars($customerName) . '</title>
</head>
<body style="margin: 0; padding: 20px; font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5;">
    <div style="max-width: 700px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); border-radius: 8px; overflow: hidden;">
        
        <!-- Header -->
        <div style="background-color: #2c3e50; background-image: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 30px; text-align: center;">
            <h1 style="margin: 0 0 10px 0; font-size: 26px; font-weight: 600;">🔍 CREDIT CHECK REQUIRED</h1>
            <p style="margin: 0; font-size: 14px; opacity: 0.9;">New Contract Agreement - Finance Review</p>
        </div>
        
        <!-- Content -->
        <div style="padding: 30px;">
            
            <!-- Greeting -->
            <p style="margin: 0 0 20px 0; font-size: 15px; color: #555;">Dear Corporate Finance Team,</p>
            
            <p style="margin: 0 0 20px 0; font-size: 15px; color: #555; line-height: 1.8;">
                A new contract agreement has been initiated and requires your financial assessment. Please review the customer\'s credit profile and provide your recommendations.
            </p>
            
            <!-- Customer Information Box -->
            <div style="background-color: #e8f4f8; border-left: 4px solid #3498db; padding: 20px; margin: 25px 0; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-size: 18px; color: #2c3e50; font-weight: 600;">📋 Customer Information</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #666; width: 180px;">Customer Name:</td>
                        <td style="padding: 8px 0; color: #333; font-weight: 700; font-size: 16px;">' . htmlspecialchars($customerName) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #666;">Agreement Name:</td>
                        <td style="padding: 8px 0; color: #333;">' . htmlspecialchars($agreementName) . '</td>
                    </tr>
                </table>
            </div>
            
            <!-- Action Required -->
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 25px 0; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-size: 18px; color: #856404; font-weight: 600;">⚠️ Action Required</h2>
                <p style="margin: 0 0 15px 0; font-size: 14px; color: #856404; line-height: 1.8;">
                    Please review and provide the following information for this customer:
                </p>
                <table style="width: 100%; border-collapse: collapse; background-color: white; border-radius: 4px; overflow: hidden;">
                    <tr>
                        <td style="padding: 15px; border-bottom: 1px solid #e0e0e0;">
                            <div style="display: flex; align-items: center;">
                                <span style="font-size: 20px; margin-right: 12px;">💰</span>
                                <div>
                                    <div style="font-weight: 600; color: #333; margin-bottom: 4px;">Current Outstanding</div>
                                    <div style="font-size: 13px; color: #666;">Review the customer\'s existing outstanding balance</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 15px; border-bottom: 1px solid #e0e0e0;">
                            <div style="display: flex; align-items: center;">
                                <span style="font-size: 20px; margin-right: 12px;">📊</span>
                                <div>
                                    <div style="font-weight: 600; color: #333; margin-bottom: 4px;">Recommend Allowable Credit Limit</div>
                                    <div style="font-size: 13px; color: #666;">Provide recommended credit limit for this customer</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 15px;">
                            <div style="display: flex; align-items: center;">
                                <span style="font-size: 20px; margin-right: 12px;">📝</span>
                                <div>
                                    <div style="font-weight: 600; color: #333; margin-bottom: 4px;">Recommendation and Comments to Finance</div>
                                    <div style="font-size: 13px; color: #666;">Share your assessment and any relevant comments</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Call to Action Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . htmlspecialchars($contractsListUrl) . '" style="display: inline-block; background-color: #3498db; background-image: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);">
                    📄 View Contract Details
                </a>
            </div>
            
            <div style="text-align: center; margin: 15px 0;">
                <p style="margin: 0; font-size: 13px; color: #999;">
                    Or copy this link: <a href="' . htmlspecialchars($contractsListUrl) . '" style="color: #3498db; text-decoration: none;">' . htmlspecialchars($contractsListUrl) . '</a>
                </p>
            </div>
            
            <!-- Important Note -->
            <div style="background-color: #f8f9fa; padding: 15px; margin: 25px 0; border-radius: 4px; border: 1px solid #dee2e6;">
                <p style="margin: 0; font-size: 13px; color: #666; line-height: 1.6;">
                    <strong style="color: #333;">Note:</strong> Please complete your assessment at your earliest convenience. The contract approval process is pending your financial review.
                </p>
            </div>
            
        </div>
        
        <!-- Footer -->
        <div style="background-color: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0 0 5px 0; font-size: 13px; color: #666;">
                <strong>Contract Management System</strong>
            </p>
            <p style="margin: 0; font-size: 12px; color: #999;">
                Generated on ' . date('l, F d, Y \a\t h:i:s A') . '
            </p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                This is an automated notification. Please do not reply to this email.
            </p>
        </div>
        
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Send Template Change Request Email to Corporate Legal
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTemplateChangeRequestEmail(Request $request, $id)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:2000',
                'requested_changes' => 'nullable|string|max:5000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get contract details
            $contract = Contract::find($id);
            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contract not found'
                ], 404);
            }

            $customerName = '';
            $party = $contract->contractPartyList->get(1) ?? null;
            if ($party && $party->partyDetailsEx) {
                $customerName = $party->partyDetailsEx->company_name ?? '';
                if (function_exists('decryptString') && $customerName) {
                    $customerName = @decryptString($customerName, 'company_name') ?: $customerName;
                }
            }

            // Decrypt agreement name
            $agreementName = $contract->agreement_name ?? $contract->contract_name ?? 'N/A';
            if (function_exists('decryptString') && $agreementName && $agreementName !== 'N/A') {
                $agreementName = @decryptString($agreementName, 'agreement_name') ?: $agreementName;
            }

            // Use contract_unique_id for display
            $contractUniqueId = $contract->contract_unique_id ?? $id;

            // Get requester info from userInfo helper
            $userInfo = \App\Helpers\Helpers::userInfo();
            $requesterName = 'Unknown';
            $requesterEmail = 'N/A';
            if ($userInfo) {
                $requesterName = $userInfo->FirstName ?? $userInfo->UserName ?? 'Unknown';
                $requesterEmail = $userInfo->email ?? 'N/A';
            }

            // Generate contract URL using attachmentDummyUrl if contract has attachment
            $contractUrl = url('contracts/show/contract-custom/' . $id);
            $contractAttachmentUrl = '';
            if (!empty($contract->contract_attachment) && function_exists('attachmentDummyUrl')) {
                $contractAttachmentUrl = attachmentDummyUrl($contract->contract_attachment, true);
            }

            $reason = $request->input('reason');
            $requestedChanges = $request->input('requested_changes', '');

            // Generate HTML template
            $htmlTemplate = $this->generateTemplateChangeRequestEmailTemplate(
                $customerName,
                $agreementName,
                $contractUrl,
                $requesterName,
                $requesterEmail,
                $reason,
                $requestedChanges,
                $contractUniqueId,
                $contractAttachmentUrl
            );

            $subject = 'Template Change Request - Contract #' . $contractUniqueId . ' - ' . ($customerName ?: $agreementName);

            // Send email
            Mail::send([], [], function ($message) use ($subject, $htmlTemplate) {
                $message->to($this->corporateLegalEmail);
                $message->subject($subject);
                $message->html($htmlTemplate);
            });

            return response()->json([
                'success' => true,
                'message' => 'Template change request sent successfully to Corporate Legal',
                'recipient' => $this->corporateLegalEmail,
                'sent_at' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Template Change Request email sending failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send template change request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate Template Change Request Email Template
     */
    private function generateTemplateChangeRequestEmailTemplate($customerName, $agreementName, $contractUrl, $requesterName, $requesterEmail, $reason, $requestedChanges, $contractId, $contractAttachmentUrl = '')
    {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Template Change Request</title>
</head>
<body style="margin: 0; padding: 20px; font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5;">
    <div style="max-width: 700px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); border-radius: 8px; overflow: hidden;">
        
        <!-- Header -->
        <div style="background-color: #8e44ad; background-image: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%); color: white; padding: 30px; text-align: center;">
            <h1 style="margin: 0 0 10px 0; font-size: 26px; font-weight: 600;">📝 TEMPLATE CHANGE REQUEST</h1>
            <p style="margin: 0; font-size: 14px; opacity: 0.9;">Contract Agreement - Legal Review Required</p>
        </div>
        
        <!-- Content -->
        <div style="padding: 30px;">
            
            <!-- Greeting -->
            <p style="margin: 0 0 20px 0; font-size: 15px; color: #555;">Dear Corporate Legal Team,</p>
            
            <p style="margin: 0 0 20px 0; font-size: 15px; color: #555; line-height: 1.8;">
                A template change request has been submitted for the following contract. Please review and take appropriate action.
            </p>
            
            <!-- Contract Information Box -->
            <div style="background-color: #f3e5f5; border-left: 4px solid #8e44ad; padding: 20px; margin: 25px 0; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-size: 18px; color: #4a235a; font-weight: 600;">📋 Contract Information</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #666; width: 180px;">Contract ID:</td>
                        <td style="padding: 8px 0; color: #333; font-weight: 700;">#' . htmlspecialchars($contractId) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #666;">Customer Name:</td>
                        <td style="padding: 8px 0; color: #333; font-weight: 700; font-size: 16px;">' . htmlspecialchars($customerName ?: 'N/A') . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #666;">Agreement Name:</td>
                        <td style="padding: 8px 0; color: #333;">' . htmlspecialchars($agreementName) . '</td>
                    </tr>
                </table>
            </div>
            
            <!-- Requester Information -->
            <div style="background-color: #e8f4f8; border-left: 4px solid #3498db; padding: 20px; margin: 25px 0; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-size: 18px; color: #2c3e50; font-weight: 600;">👤 Requester Information</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #666; width: 180px;">Requested By:</td>
                        <td style="padding: 8px 0; color: #333; font-weight: 600;">' . htmlspecialchars($requesterName) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #666;">Email:</td>
                        <td style="padding: 8px 0; color: #333;">' . htmlspecialchars($requesterEmail) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #666;">Request Date:</td>
                        <td style="padding: 8px 0; color: #333;">' . date('F d, Y \a\t h:i A') . '</td>
                    </tr>
                </table>
            </div>
            
            <!-- Reason for Change -->
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 25px 0; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-size: 18px; color: #856404; font-weight: 600;">📌 Reason for Template Change</h2>
                <p style="margin: 0; font-size: 14px; color: #333; line-height: 1.8; background: white; padding: 15px; border-radius: 4px;">' . nl2br(htmlspecialchars($reason)) . '</p>
            </div>';
            
        // Add requested changes section if provided
        if (!empty($requestedChanges)) {
            $html .= '
            <!-- Requested Changes -->
            <div style="background-color: #d4edda; border-left: 4px solid #28a745; padding: 20px; margin: 25px 0; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-size: 18px; color: #155724; font-weight: 600;">✏️ Requested Changes</h2>
                <p style="margin: 0; font-size: 14px; color: #333; line-height: 1.8; background: white; padding: 15px; border-radius: 4px;">' . nl2br(htmlspecialchars($requestedChanges)) . '</p>
            </div>';
        }
            
        $html .= '
            <!-- Call to Action Buttons -->
            <div style="text-align: center; margin: 30px 0;">';
        
        // View Contract Details button
        $html .= '
                <a href="' . htmlspecialchars($contractUrl) . '" style="display: inline-block; background-color: #8e44ad; background-image: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(142, 68, 173, 0.3); margin: 5px;">
                    📄 View Contract Details
                </a>';
        
        // View Contract Document button (only if attachment URL exists)
        if (!empty($contractAttachmentUrl)) {
            $html .= '
                <a href="' . htmlspecialchars($contractAttachmentUrl) . '" style="display: inline-block; background-color: #2c3e50; background-image: linear-gradient(135deg, #2c3e50 0%, #3d566e 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3); margin: 5px;">
                    📎 View Contract Document
                </a>';
        }
        
        $html .= '
            </div>
            
            <div style="text-align: center; margin: 15px 0;">
                <p style="margin: 0; font-size: 13px; color: #999;">
                    Contract Details: <a href="' . htmlspecialchars($contractUrl) . '" style="color: #8e44ad; text-decoration: none;">' . htmlspecialchars($contractUrl) . '</a>
                </p>';
        
        if (!empty($contractAttachmentUrl)) {
            $html .= '
                <p style="margin: 5px 0 0 0; font-size: 13px; color: #999;">
                    Contract Document: <a href="' . htmlspecialchars($contractAttachmentUrl) . '" style="color: #2c3e50; text-decoration: none;">' . htmlspecialchars($contractAttachmentUrl) . '</a>
                </p>';
        }
        
        $html .= '
            </div>
            
            <!-- Important Note -->
            <div style="background-color: #f8f9fa; padding: 15px; margin: 25px 0; border-radius: 4px; border: 1px solid #dee2e6;">
                <p style="margin: 0; font-size: 13px; color: #666; line-height: 1.6;">
                    <strong style="color: #333;">Note:</strong> Please review this template change request and coordinate with the requester if additional information is needed.
                </p>
            </div>
            
        </div>
        
        <!-- Footer -->
        <div style="background-color: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0 0 5px 0; font-size: 13px; color: #666;">
                <strong>Contract Management System</strong>
            </p>
            <p style="margin: 0; font-size: 12px; color: #999;">
                Generated on ' . date('l, F d, Y \a\t h:i:s A') . '
            </p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                This is an automated notification. Please do not reply to this email.
            </p>
        </div>
        
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Separate approvers by role into TO, CC, BCC
     */
    private function separateApproversByRole($approvers)
    {
        $to = [];
        $cc = [];
        $bcc = [];
        
        foreach ($approvers as $approver) {
            $email = $approver['email'];
            $role = $approver['role'];

            switch ($role) {
                case 'Unit Head':
                    $to[] = $email;
                    break;
                case 'CEO Finance':
                    $bcc[] = $email;
                    break;
                case 'Region Head':
                    $cc[] = $email;
                    break;
                case 'Finance Head':
                    $bcc[] = $email;
                    break;
                default:
                    $to[] = $email; // Default to TO
                    break;
            }
        }

        // Remove duplicates
        $to = array_unique($to);
        $cc = array_unique($cc);
        $bcc = array_unique($bcc);

        return [
            'to' => array_values($to),
            'cc' => array_values($cc),
            'bcc' => array_values($bcc),
            'total_recipients' => count($to) + count($cc) + count($bcc)
        ];
    }

    /**
     * Check if any discount is greater than 15%
     */
    private function checkHighDiscount($discounts)
    {
        $maxDiscount = 0;
        $hasHighDiscount = false;
        $highDiscounts = [];

        foreach ($discounts as $discount) {
            $discountPercent = $discount['discount_percent'];
            
            if ($discountPercent > $maxDiscount) {
                $maxDiscount = $discountPercent;
            }
            
            if ($discountPercent > 15) {
                $hasHighDiscount = true;
                $highDiscounts[] = [
                    'category' => $discount['category'],
                    'subcategory' => $discount['subcategory'],
                    'discount_percent' => $discountPercent
                ];
            }
        }

        return [
            'has_high_discount' => $hasHighDiscount,
            'max_discount' => $maxDiscount,
            'high_discounts' => $highDiscounts
        ];
    }

    /**
     * Get consultation name by ID
     */
    private function getConsultationName($id)
    {
        // Return ID itself if not numeric
        if (!is_numeric($id)) {
            return $id;
        }        
        foreach ($this->consultations as $consult) {
            if ($consult['id'] == $id) {
                return $consult['name'];
            }
        }
        return 'Unknown Consultation';
    }

    /**
     * Get test details by ID
     */
    private function getTestDetails($id)
    {
        foreach ($this->tests as $test) {
            if ($test['id'] == $id) {
                return $test;
            }
        }
        return ['id' => $id, 'name' => 'Unknown Test', 'price' => 0];
    }

    /**
     * Get location details by ID
     */
    private function getLocationDetails($id)
    {
        foreach ($this->locations as $location) {
            if ($location['id'] == $id) {
                return $location;
            }
        }
        return null;
    }

    /**
     * Determine approvers based on conditions
     */
    private function getApprovers($selectedLocationIds, $discountData)
    {
        $approvers = [];
        $selectedLocations = [];
        $regions = [];
        $unitHeads = [];
        $regionHeads = [];


        // Get selected location details
        foreach ($selectedLocationIds as $locId) {
            $loc = $this->getLocationDetails($locId);
            if ($loc) {
                $selectedLocations[] = $loc;
                $regions[$loc['region']] = true;
                $unitHeads[$loc['unit-head']] = true;
                $regionHeads[$loc['region-head']] = true;
            }
        }

        $locationCount = count($selectedLocations);
        $regionCount = count($regions);

        // Condition 1: If more than one location, add unit-heads as approvers
        if ($locationCount >= 1) {
            foreach (array_keys($unitHeads) as $unitHead) {
                $approvers[] = [
                    'email' => $unitHead,
                    'role' => 'Unit Head',
                    'reason' => 'Single/Multiple locations'
                ];
            }
        }

        // Condition 2: If more than one location in same region, add region head
        if ($locationCount > 1 && $regionCount == 1) {
            foreach (array_keys($regionHeads) as $regionHead) {
                if (!$this->approverExists($approvers, $regionHead)) {
                    $approvers[] = [
                        'email' => $regionHead,
                        'role' => 'Region Head',
                        'reason' => 'Multiple locations in same region'
                    ];
                }
            }
        }

        // Condition 3: If more than one location in different regions, add region head and finance head
        if ($locationCount > 1 && $regionCount > 1) {
            foreach (array_keys($regionHeads) as $regionHead) {
                if (!$this->approverExists($approvers, $regionHead)) {
                    $approvers[] = [
                        'email' => $regionHead,
                        'role' => 'Region Head',
                        'reason' => 'Multiple regions'
                    ];
                }
            }
            // Add finance head
            $financeHead = 'finance@legalitysimplified.  com';
            if (!$this->approverExists($approvers, $financeHead)) {
                $approvers[] = [
                    'email' => $financeHead,
                    'role' => 'Finance Head',
                    'reason' => 'Multiple regions'
                ];
            }
        }

        // NEW CONDITION: If any discount is greater than 15%, add CEO Finance
        if ($discountData['has_high_discount']) {
            if (!$this->approverExists($approvers, $this->ceoFinanceEmail)) {
                $approvers[] = [
                    'email' => $this->ceoFinanceEmail,
                    'role' => 'CEO Finance',
                    'reason' => 'Discount exceeds 15% (Max: ' . $discountData['max_discount'] .   '%)'
                ];
            }
        }

        return [
            'approvers' => $approvers,
            'location_count' => $locationCount,
            'region_count' => $regionCount,
            'locations' => $selectedLocations
        ];
    }

    /**
     * Check if approver already exists
     */
    private function approverExists($approvers, $email)
    {
        foreach ($approvers as $approver) {
            if ($approver['email'] === $email) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build location details array
     */
    private function buildLocationDetails($locations)
    {
        $details = [];
        foreach ($locations as $loc) {
            $details[] = [
                'id' => $loc['id'],
                'name' => $loc['name'],
                'region' => ucfirst($loc['region']),
                'unit_head' => $loc['unit-head'],
                'region_head' => $loc['region-head']
            ];
        }
        return $details;
    }

    /**
     * Build discount details array
     */
    private function buildDiscountDetails($discounts)
    {
        $details = [];
        foreach ($discounts as $discount) {
            $details[] = [
                'category' => $discount['category'],
                'subcategory' => $discount['subcategory'],
                'discount_percent' => $discount['discount_percent'],
                'is_high_discount' => $discount['discount_percent'] > 15,
                'room_charges' => $discount['room_charges'] ??   []
            ];
        }
        return $details;
    }

    /**
     * Build health check package details array
     */
    private function buildHealthCheckDetails($healthCheckRows)
    {
        $details = [];
        foreach ($healthCheckRows as $index => $row) {
            $tests = [];
            $testsTotal = 0;
            
            foreach ($row['selected_test_ids'] as $testId) {
                $testDetails = $this->getTestDetails($testId);
                $tests[] = [
                    'id' => $testDetails['id'],
                    'name' => $testDetails['name'],
                    'price' => $testDetails['price']
                ];
                $testsTotal += $testDetails['price'];
            }

            $consultations = [];
            $consultationsTotal = 0;
            
            foreach ($row['selected_consultation_ids'] as $consultId) {
                $consultName = $this->getConsultationName($consultId);
                $price = isset($row['prices'][$consultId]) ? $row['prices'][$consultId] : 0;
                $consultations[] = [
                    'id' => $consultId,
                    'name' => $consultName,
                    'price' => $price
                ];
                $consultationsTotal += $price;
            }

            $details[] = [
                'package_number' => $index + 1,
                'package_name' => $row['row_name'],
                'package_price' => $row['package_price'],
                'tests' => $tests,
                'tests_total' => $testsTotal,
                'consultations' => $consultations,
                'consultations_total' => $consultationsTotal
            ];
        }
        return $details;
    }

    /**
     * Get approval level description
     */
    private function getApprovalLevel($locationCount, $regionCount, $hasHighDiscount)
    {
        $level = '';
        
        if ($locationCount > 1 && $regionCount > 1) {
            $level = 'Multi-Region Approval';
        } elseif ($locationCount > 1 && $regionCount == 1) {
            $level = 'Single Region Multi-Location Approval';
        } elseif ($locationCount > 1) {
            $level = 'Multi-Location Approval';
        }

        if ($hasHighDiscount) {
            $level .= ' + High Discount Approval';
        }

        return $level ?: 'Standard Approval';
    }

    /**
     * Get approval explanation
     */
    private function getApprovalExplanation($locationCount, $regionCount, $hasHighDiscount)
    {
        $explanations = [];

        if ($locationCount > 1 && $regionCount > 1) {
            $explanations[] = 'Multiple locations across different regions require approval from Unit Head, Region Head, and Finance Head';
        } elseif ($locationCount > 1 && $regionCount == 1) {
            $explanations[] = 'Multiple locations in the same region require approval from Unit Head and Region Head';
        } elseif ($locationCount > 1) {
            $explanations[] = 'Multiple locations require approval from Unit Head';
        }

        if ($hasHighDiscount) {
            $explanations[] = 'Discount exceeding 15% requires CEO Finance approval';
        }

        return implode('.   ', $explanations);
    }

    /**
     * Generate HTML template with INLINE STYLES for email compatibility
     */
    /**
     * Generate HTML template with INLINE STYLES for email compatibility
     */
    public function generateHTMLTemplate($data)
    {
        $contractInfo = $data['contract_info'];
        $locations = $data['locations'];
        $discounts = $data['discounts'];
        $packages = $data['health_check_packages'];
        $approvers = $data['approvers'];
        $generatedAt = $data['generated_at'];

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Approval Request - ' . htmlspecialchars($contractInfo['agreement_name']) . '</title>
</head>
<body style="margin: 0; padding: 20px; font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5;">
    <div style="max-width: 900px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px; text-align: center;">
            <h1 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 600;">CONTRACT APPROVAL REQUEST</h1>
            <p style="margin: 0; font-size: 14px; opacity: 0.9;">Generated on ' . date('F d, Y \a\t h:i A', strtotime($generatedAt)) . '</p>
        </div>
        
        <!-- Content -->
        <div style="padding: 5px;">';
        
        // High discount alert
        if ($approvers['logic']['has_high_discount']) {
            $html .= '
            <div style="padding: 15px; border-radius: 4px; margin: 15px 0; display: flex; align-items: center; background-color: #fff3cd; border-left: 4px solid #ffc107; color: #856404;">
                <div style="font-size: 24px; margin-right: 15px;">⚠️</div>
                <div>
                    <strong>High Discount Alert! </strong><br>
                    This contract contains discounts exceeding 15% (Maximum: ' . $approvers['logic']['max_discount'] . '%).  CEO Finance approval is required.
                </div>
            </div>';
        }
        
        $html .= '
            <!-- Contract Information -->
            <div style="margin-bottom: 30px; padding: 20px; background-color: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-weight: 600; color: #667eea; font-size: 18px;">▶ Contract Information</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 12px; background-color: white; border-radius: 4px; border: 1px solid #e0e0e0; width: 50%; vertical-align: top;">
                            <div style="font-weight: 600; color: #666; font-size: 13px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Agreement Name</div>
                            <div style="font-size: 15px; color: #333;">' . htmlspecialchars($contractInfo['agreement_name']) .  '</div>
                        </td>
                        <td style="padding: 12px; background-color: white; border-radius: 4px; border: 1px solid #e0e0e0; width: 50%; vertical-align: top;">
                            <div style="font-weight: 600; color: #666; font-size: 13px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Customer ID</div>
                            <div style="font-size: 15px; color: #333;">#' . htmlspecialchars($contractInfo['customer_name']) .  '</div>
                        </td>
                    </tr>
                    <tr><td colspan="2" style="padding: 0px;"></td></tr>
                    <tr>
                        <td style="padding: 12px; background-color: white; border-radius: 4px; border: 1px solid #e0e0e0; vertical-align: top;">
                            <div style="font-weight: 600; color: #666; font-size: 13px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Contract Type</div>
                            <div style="font-size: 15px; color: #333;">
                                <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2;">' . htmlspecialchars($contractInfo['contract_type']) .  '</span>
                            </div>
                        </td>
                        <td style="padding: 12px; background-color: white; border-radius: 4px; border: 1px solid #e0e0e0; vertical-align: top;">
                            <div style="font-weight: 600; color: #666; font-size: 13px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Scope</div>
                            <div style="font-size: 15px; color: #333;">' .  htmlspecialchars($contractInfo['scope']) . '</div>
                        </td>
                    </tr>
                    <tr><td colspan="2" style="padding: 0px;"></td></tr>
                    <tr>
                        <td style="padding: 12px; background-color: white; border-radius: 4px; border: 1px solid #e0e0e0; vertical-align: top;">
                            <div style="font-weight: 600; color: #666; font-size: 13px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Start Date</div>
                            <div style="font-size: 15px; color: #333;">' . date('M d, Y', strtotime($contractInfo['start_date'])) . '</div>
                        </td>
                        <td style="padding: 12px; background-color: white; border-radius: 4px; border: 1px solid #e0e0e0; vertical-align: top;">
                            <div style="font-weight: 600; color: #666; font-size: 13px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">End Date</div>
                            <div style="font-size: 15px; color: #333;">' . date('M d, Y', strtotime($contractInfo['end_date'])) . '</div>
                        </td>
                    </tr>
                </table>
                
                <div style="margin-top: 15px;">
                    <div style="font-weight: 600; color: #666; font-size: 13px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Scope of Services</div>
                    <div>';
        
        foreach ($contractInfo['scope_of_services'] as $service) {
            $html .= '<span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; margin: 2px; background-color: #e8f5e9; color: #388e3c;">' . htmlspecialchars($service) . '</span>';
        }
        
        $html .= '
                    </div>
                </div>
            </div>
            
            <!-- Locations -->
            <div style="margin-bottom: 30px; padding: 20px; background-color: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-weight: 600; color: #667eea; font-size: 18px;">▶ Locations (' . $locations['count'] . ' location(s) across ' . $locations['region_count'] . ' region(s))</h2>';
        
        foreach ($locations['details'] as $location) {
            $html .= '
                <div style="padding: 10px; margin: 8px 0; background-color: white; border-radius: 4px; border-left: 3px solid #667eea;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="font-weight: 600; color: #333;">' . htmlspecialchars($location['name']) . '</td>
                            <td style="text-align: right;">
                                <span style="background-color: #e3f2fd; color: #1976d2; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">' . htmlspecialchars($location['region']) . '</span>
                            </td>
                        </tr>
                    </table>
                </div>';
        }
        
        $html .= '
            </div>
            
            <!-- Discounts -->
            <div style="margin-bottom: 30px; padding: 20px; background-color: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-weight: 600; color: #667eea; font-size: 18px;">▶ Discount Structure</h2>';
        
        if ($approvers['logic']['has_high_discount']) {
            $html .= '
                <div style="background-color: #fff3cd; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-size: 13px;">
                    <strong>⚠️ Note:</strong> Rows highlighted in orange indicate discounts exceeding 15%
                </div>';
        }
        
        $html .= '
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px; background-color: white; border-radius: 4px; overflow: hidden;">
                    <thead>
                        <tr>
                            <th style="background-color: #667eea; color: white; padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Category</th>
                            <th style="background-color: #667eea; color: white; padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Subcategory</th>
                            <th style="background-color: #667eea; color: white; padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Discount</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($discounts as $discount) {
            $bgColor = $discount['is_high_discount'] ? '#fff3e0' : 'white';
            $badgeBg = $discount['is_high_discount'] ? '#ffebee' : '#fff3e0';
            $badgeColor = $discount['is_high_discount'] ? '#d32f2f' : '#f57c00';
            
            $html .= '
                        <tr style="background-color: ' . $bgColor . ';">
                            <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; font-size: 14px;"><strong>' . htmlspecialchars($discount['category']) . '</strong></td>
                            <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; font-size: 14px;">' . htmlspecialchars($discount['subcategory']) . '</td>
                            <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; font-size: 14px;">
                                <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; background-color: ' . $badgeBg . '; color: ' . $badgeColor . ';">' . htmlspecialchars($discount['discount_percent']) . '%</span>';
            
            if ($discount['is_high_discount']) {
                $html .= ' <strong style="color: #d32f2f; margin-left: 10px;">⚠️ Exceeds 15%</strong>';
            }
            
            $html .= '
                            </td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
            
            <!-- Health Check Packages -->
            <div style="margin-bottom: 30px; padding: 20px; background-color: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-weight: 600; color: #667eea; font-size: 18px;">▶ Health Check Packages</h2>';
        
        foreach ($packages as $package) {
            $html .= '
                <div style="background-color: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin: 15px 0;">
                    <!-- Package Header -->
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
                        <tr>
                            <td style="font-weight: 600; font-size: 16px; color: #333;">Package ' . $package['package_number'] . ': ' . htmlspecialchars($package['package_name']) . '</td>
                            
                        </tr>
                    </table>
                    
                    <!-- Package Items -->
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                                <!-- Tests -->
                                <div style="background-color: #f9f9f9; padding: 15px; border-radius: 4px;">
                                    <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #667eea; font-weight: 600;">Tests Included</h4>
                                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px; padding-bottom: 5px; border-bottom: 2px solid #e0e0e0;">
                                        <tr>
                                            <td style="font-weight: 600; color: #667eea; font-size: 12px;">Item</td>
                                            
                                        </tr>
                                    </table>';
            
            foreach ($package['tests'] as $test) {
                $html .= '
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="padding: 6px 0; font-size: 13px; color: #555;">✓ ' . htmlspecialchars($test['name']) . '</td>
                                            
                                            
                                        </tr>
                                    </table>';
            }
            
            $html .= '
                                </div>
                            </td>
                            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                                <!-- Consultations -->
                                <div style="background-color: #f9f9f9; padding: 15px; border-radius: 4px;">
                                    <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #667eea; font-weight: 600;">Consultations Included</h4>
                                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px; padding-bottom: 5px; border-bottom: 2px solid #e0e0e0;">
                                        <tr>
                                            <td style="font-weight: 600; color: #667eea; font-size: 12px;">Item</td>
                                            
                                        </tr>
                                    </table>';
            
            foreach ($package['consultations'] as $consultation) {
                $html .= '
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="padding: 6px 0; font-size: 13px; color: #555;">✓ ' . htmlspecialchars($consultation['name']) . '</td>
                                            
                                            
                                        </tr>
                                    </table>';
            }
            
            $html .= '
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>';
        }
        
    $contractsListUrl = url('contracts/show/contract-custom/'.$data['contract_info']['contract_id']);        
        
        $html .= '
            </div>
            
            <div style="text-align: center; margin: 10px 0;">
                <a href="' . htmlspecialchars($contractsListUrl) . '" style="display: inline-block; background-color: #3498db; background-image: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; padding: 10px 10px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 12px; box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);">
                    📄 View Contract Details
                </a>
            </div>            
        </div>
        
        <!-- Footer -->
        <div style="text-align: center; padding: 20px; background-color: #f5f5f5; border-top: 1px solid #e0e0e0; font-size: 13px; color: #666;">
            <p style="margin: 0;"><strong>Please review and approve at your earliest convenience.</strong></p>
            <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">Document generated on ' . date('l, F d, Y \a\t h:i:s A', strtotime($generatedAt)) . '</p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }
}