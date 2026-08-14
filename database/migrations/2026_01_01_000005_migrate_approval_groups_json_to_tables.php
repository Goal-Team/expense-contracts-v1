<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FinancialLimit;
use App\Models\ApprovalGroupSet;
use App\Models\ApprovalGroup;
use App\Models\ApprovalGroupApprover;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $financialLimits = FinancialLimit::all();
        
        $approvalTypes = [
            '' => 'approval_required_users',
            'edit' => 'approval_required_users_edit',
            'renew' => 'approval_required_users_renewed',
            'addendum' => 'approval_required_users_addendum',
            'legacy' => 'approval_required_users_legacy',
            'legacy_edit' => 'approval_required_users_legacy_edit',
            'terminate' => 'approval_required_users_terminate',
        ];
        
        foreach ($financialLimits as $limit) {
            $defaultSetId = null;
            
            foreach ($approvalTypes as $type => $column) {
                $jsonData = $limit->$column;
                if (empty($jsonData)) continue;
                
                $groups = @json_decode($jsonData, true);
                if (!is_array($groups) || empty($groups)) continue;
                
                $existingSet = ApprovalGroupSet::where('financial_limit_id', $limit->id)
                    ->where('approval_type', $type)
                    ->first();
                
                if ($existingSet) {
                    $existingSet->groups()->delete();
                    $existingSet->delete();
                }
                
                $set = ApprovalGroupSet::create([
                    'financial_limit_id' => $limit->id,
                    'approval_type' => $type,
                ]);
                
                if ($type === '') {
                    $defaultSetId = $set->id;
                }
                
                foreach (['review', 'negotiation', 'finalization', 'approval', 'signatory'] as $parentType) {
                    if (!isset($groups[$parentType]) || !is_array($groups[$parentType])) {
                        continue;
                    }
                    
                    foreach ($groups[$parentType] as $index => $group) {
                        if (!is_array($group)) continue;
                        
                        $approvalGroup = ApprovalGroup::create([
                            'approval_group_set_id' => $set->id,
                            'parent_type' => $parentType,
                            'role' => $group['role'] ?? 'Approver',
                            'approval_type' => $group['approval_type'] ?? 'sequential',
                            'auto_next_enabled' => $group['auto_next_enabled'] ?? 0,
                            'dynamic_approver_enabled' => $group['dynamic_approver_enabled'] ?? 0,
                            'order_index' => $index,
                        ]);
                        
                        if (isset($group['approvers']) && is_array($group['approvers'])) {
                            foreach ($group['approvers'] as $approverIndex => $approver) {
                                if (!is_array($approver)) continue;
                                
                                ApprovalGroupApprover::create([
                                    'approval_group_id' => $approvalGroup->id,
                                    'approver_id' => $approver['id'] ?? 0,
                                    'approver_type' => $approver['type'] ?? 'name',
                                    'approver_name' => $approver['name'] ?? '',
                                    'approver_email' => $approver['email'] ?? '',
                                    'order_index' => $approverIndex,
                                ]);
                            }
                        }
                    }
                }
            }
            
            if ($defaultSetId) {
                $limit->update(['approval_group_set_id' => $defaultSetId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ApprovalGroupApprover::truncate();
        ApprovalGroup::truncate();
        ApprovalGroupSet::truncate();
        
        FinancialLimit::whereNotNull('approval_group_set_id')->update(['approval_group_set_id' => null]);
    }
};
