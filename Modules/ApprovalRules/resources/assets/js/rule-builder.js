$(document).ready(function () {

    let groupCount = 0;

    // master lists (populated by API)
    let branches = [];
    let departments = [];
    let categories = [];
    let contractTypes = [];
    let locations = []; // locations_master list


    // ============================================
    // FUNCTION TO ADD GROUP
    // ============================================
    let custometType  = ["Not Applicable","Domestic", "International"];
    let entityType  = ["Private / Public Company", "Public Sector Undertakings ( PSU )", "Non-Corporate Bodies (Assn, committees etc.,)","Aggregators"];
    let defaultOptions = ["IP","OP"];
    let psuRelatedOptions = {
        "IP":   ["Room charges","Investigation","OT","Professional Fee - Exl ", "Consultation"],
        "OP":   ["Investigation", "Consultation"]
    };
    let otherRelatedOptions = {
        "IP":   ["Room charges","Investigation","OT","Professional Fee - Exl ", "Consultation"],
        "OP":   ["Investigation", "Consultation"]
    };

    function buildOptions(list, includeAnyAll = false) {
        let html = `<option value="">Select</option>`;
        if(includeAnyAll) html += `<option value="0">Any / All</option>`;
        list.forEach(item => {
            let label = item.name || item.location_name || '';
            if (item.region) label += ' — ' + item.region;
            html += `<option value="${item.id}">${label}</option>`;
        });
        return html;
    }

    function addGroup() {
        groupCount++;

        let custometTypeOptions = `<option value="" disabled selected>Select Type</option>`;
        custometType.forEach(c => {
            custometTypeOptions += `<option>${c}</option>`;
        });

        // build selects from master lists
        let locationOptions = buildOptions(branches, true);
        let departmentOptions = buildOptions(departments, true);
        let categoryOptions = buildOptions(categories, true);
        let contractTypeOptions = buildOptions(contractTypes, true);

        let groupHTML = `
        <div class="group-box" id="group_${groupCount}">
            <div class="removebtn">
                <button class="removeGroupBtn" data-group="${groupCount}">Remove Group</button>
            </div>
            <h3>Group ${groupCount}</h3>
            <div class="form-grid">
                 <div>
                    <label>Location</label>
                    <select class="location opt-select-any-all" multiple>
                        ${locationOptions}
                    </select>
                </div>

                <div>
                    <label>Department</label>
                    <select class="department opt-select-any-all" multiple>
                        ${departmentOptions}
                    </select>
                </div>

                <div>
                    <label>Category</label>
                    <select class="mainCategory opt-select-any-all" multiple>
                        ${categoryOptions}
                    </select>
                </div>

                <div>
                    <label>Contract Type</label>
                    <select class="contractType opt-select-any-all" multiple>
                        ${contractTypeOptions}
                    </select>
                </div>

                <div>
                    <label>Payment Type</label>
                    <select class="payment-type">
                        <option value="Not Applicable">Not Applicable</option>
                        <option value="Cash">Cash</option>
                        <option value="Credit">Credit</option>
                    </select>
                </div>

                <div>
                    <label>Locations Master</label>
                    <select class="locations-master opt-select-any-all" multiple>
                        ${buildOptions(locations, true)}
                    </select>
                </div>

                <div>
                    <label>Limit From</label>
                    <input type="number" class="limitFrom" placeholder="Limit From">
                </div>

                <div>
                    <label>Limit Up</label>
                    <input type="number" class="limitUp" placeholder="Limit Up">
                </div>

                <div>
                    <label>Entity Scope</label>
                    <select class="customer-type" data-group="${groupCount}">
                        ${custometTypeOptions}
                    </select>                    
                </div>
            </div>
            <div id="discountrows_${groupCount}">
                <div class="discountAccessTYpe">
                    <h4 style="margin-top:20px;">Discount Rules</h4>
                    <div id="rowAccessType_${groupCount}"></div>
                </div>
                <div class="rows-container" id="rows_${groupCount}"></div>
                <div class="rowbtn">
                    <button type="button" class="addRowBtn" data-group="${groupCount}">Add Row</button>
                </div>
            </div>
            
        </div>
        `;

        $("#groupsContainer").append(groupHTML);
        // initialize select2 on dynamic selects so multi-select works nicely
        let $newGroup = $(`#group_${groupCount}`);
        $newGroup.find('.location, .department, .mainCategory, .contractType, .locations-master').select2({placeholder: 'Select', width: '100%'});
        // Payment/Locations Master fields are only enabled when admin setting for custom contracts exist
        if (!window.CUSTOM_CONTRACTS_TYPE_ID) {
            $newGroup.find('.payment-type').prop('disabled', true).closest('div').hide();
            $newGroup.find('.locations-master').prop('disabled', true).closest('div').hide();
        } else {
            $newGroup.find('.payment-type').prop('disabled', false).closest('div').show();
            // locations-master visibility depends on the group's selected contractType
            // default hidden until a contract type including custom_contracts_type_id is selected
            $newGroup.find('.locations-master').prop('disabled', true).closest('div').hide();
        }

        // check initial visibility based on any pre-selected contract types
        (function checkContractTypesForGroup($grp){
            let sel = $grp.find('.contractType').val() || [];
            let enabled = false;
            if (window.CUSTOM_CONTRACTS_TYPE_ID && sel.length) {
                sel = Array.isArray(sel) ? sel : [sel];
                enabled = sel.map(String).includes(String(window.CUSTOM_CONTRACTS_TYPE_ID));
            }
            if (enabled) {
                $grp.find('.locations-master').prop('disabled', false).closest('div').show();
            } else {
                $grp.find('.locations-master').prop('disabled', true).closest('div').hide();
            }
        })($newGroup);
       
    }

    // load master lists first, then add initial group
    function loadMasterLists() {
        return Promise.all([
            $.get(APP_URL + '/contract-setup/api/branches'),
            $.get(APP_URL + '/contract-setup/api/departments'),
            $.get(APP_URL + '/contract-setup/api/categories'),
            $.get(APP_URL + '/contract-setup/api/contract-types'),
            $.get(APP_URL + '/contract-setup/api/locations')
        ]).then(function(responses) {
            // responses: [branches, departments, categories, contract-types, locations]
            branches = responses[0].data || [];
            departments = responses[1].data || [];
            categories = responses[2].data || [];
            contractTypes = responses[3].data || [];
            locations = responses[4].data || [];
        }).catch(function(err) {
            console.error('Failed to load master lists for rule builder', err);
            branches = departments = categories = contractTypes = locations = [];
        });
    }

    // Load existing finalData into UI
    function loadFinalData(data){
        if(!data || !Array.isArray(data.gcondition)) return;
        // clear current groups
        $("#groupsContainer").empty();
        groupCount = 0;
        // set master condition type if present
        if(data.gconditiontype){
            if($("#groupAccessType .groupaccesstype").length === 0){
                let accessGroupHtml =`
                    <select class="groupaccesstype">
                        <option value="" disabled selected>Select</option>
                        <option value="ALL">ALL</option>
                        <option value="ANY">ANY</option>
                    </select> `;
                $("#groupAccessType").append(accessGroupHtml);
            }
            $("#groupAccessType .groupaccesstype").val(data.gconditiontype);
        }

        data.gcondition.forEach(function(g){
            addGroup();
            let gid = groupCount;
            let $group = $(`#group_${gid}`);
            if(typeof g.location !== 'undefined') $group.find('.location').val(g.location).trigger('change');
            if(typeof g.department !== 'undefined') $group.find('.department').val(g.department).trigger('change');
            if(typeof g.category !== 'undefined') $group.find('.mainCategory').val(g.category).trigger('change');
            if(typeof g.contractType !== 'undefined') {
                $group.find('.contractType').val(g.contractType).trigger('change');
                // after contract type set, ensure locations-master visibility is computed
                let sel = $group.find('.contractType').val() || [];
                if (window.CUSTOM_CONTRACTS_TYPE_ID && sel.length) {
                    sel = Array.isArray(sel) ? sel : [sel];
                    let enabled = sel.map(String).includes(String(window.CUSTOM_CONTRACTS_TYPE_ID));
                    if (enabled) {
                        $group.find('.locations-master').prop('disabled', false).closest('div').show();
                    } else {
                        $group.find('.locations-master').prop('disabled', true).closest('div').hide();
                    }
                }
            }
            if(typeof g.limitFrom !== 'undefined') $group.find('.limitFrom').val(g.limitFrom);
            if(typeof g.limitUp !== 'undefined') $group.find('.limitUp').val(g.limitUp);
            // set customer type / entity scope (support legacy keys)
            let custVal = g.customerType ?? g.customer_type ?? g.entityScope ?? g.customerScope ?? '';
            if(custVal !== ''){
                $group.find('.customer-type').val(custVal);
                if(custVal === 'Domestic'){
                    $(`#rows_${gid}`).show();
                    $(`#discountrows_${gid}`).show();
                }else{
                    $(`#rows_${gid}`).empty();
                    $(`#rows_${gid}`).hide();
                    $(`#discountrows_${gid}`).hide();
                }
            }

            // populate Payment Type and Locations Master when present
            if (typeof g.paymentType !== 'undefined' && g.paymentType !== null) {
                $group.find('.payment-type').val(g.paymentType);
            }
            if (typeof g.locationsMaster !== 'undefined' && Array.isArray(g.locationsMaster)) {
                $group.find('.locations-master').val(g.locationsMaster).trigger('change');
            }
            // handle discount rows
            if(g.discountRules && Array.isArray(g.discountRules.condition)){
                // ensure rows container cleared
                $(`#rows_${gid}`).empty();
                g.discountRules.condition.forEach(function(cond){
                    addRows(gid);
                    let $row = $(`#rows_${gid} .discount-grid`).last();
                    // support legacy keys so edit works for older saved data
                    let entityVal = cond.entitytype ?? cond.entity_type ?? cond.entityType ?? '';
                    if(entityVal !== '') $row.find('.entity-type').val(entityVal);
                    let discVal = cond.discountoption ?? cond.discount_option ?? cond.discountOption ?? '';
                    if(discVal !== '') $row.find('.discount-option').val(discVal).trigger('change');
                    // set related-option after discount-option change handlers populate it (retry a few times until options are available)
                    let subVal = cond.subcategory ?? cond.sub_category ?? cond.subCategory ?? '';
                    if(subVal !== ''){
                        (function trySetRelated(attempts){
                            var $rel = $row.find('.related-option');
                            if($rel.children().length > 1 || attempts <= 0){
                                // Try select by value first, then by option text (trimmed)
                                var matched = false;
                                if ($rel.find('option[value="'+subVal+'"]').length) {
                                    $rel.val(subVal);
                                    matched = true;
                                } else {
                                    $rel.find('option').each(function(){
                                        if ($(this).text().trim() === String(subVal).trim()){
                                            $(this).prop('selected', true);
                                            matched = true; 
                                            return false; // break loop
                                        }
                                    });
                                }
                                if (matched) {
                                    $rel.trigger('change');
                                } else {
                                    // fallback: attempt to set value (may match text)
                                    $rel.val(subVal);
                                }
                                return;
                            }
                            setTimeout(function(){ trySetRelated(attempts - 1); }, 50);
                        })(20);
                    }
                    let minVal = cond.mindiscount ?? cond.min_discount ?? cond.minDiscount ?? '';
                    if(minVal !== '') $row.find('.min-discount').val(minVal);
                    let maxVal = cond.maxdiscount ?? cond.max_discount ?? cond.maxDiscount ?? '';
                    if(maxVal !== '') $row.find('.max-discount').val(maxVal);
                });

                // Show row access type if more than one
                let rowCount = $(`#rows_${gid} .discount-grid`).length;
                if(rowCount > 1){
                    $(`#rowAccessType_${gid}`).show();
                    if ($(`#rowAccessType_${gid} .rowaccesstype`).length === 0) {
                        let accessRowHtml =`
                            <select class="rowaccesstype">
                                <option value="" disabled selected>Select</option>
                                <option value="ALL">ALL</option>
                                <option value="ANY">ANY</option>
                            </select> `;
                        $(`#rowAccessType_${gid}`).append(accessRowHtml);
                    }
                    $(`#rowAccessType_${gid} .rowaccesstype`).val(g.discountRules.conditiontype || 'ALL');
                }
            }
        });
    }

    // ============================================
    // 1️⃣ ADD FIRST GROUP AUTOMATICALLY (after loading master lists)
    // ============================================
    loadMasterLists().then(function(){
        addGroup();
        // if there is existing rule_builder_data populate UI
        let rb = $('#rule_builder_data').val() || '';
        if(rb && rb.length > 0){
            try{
                let parsed = JSON.parse(rb);
                loadFinalData(parsed);
            }catch(e){
                console.error('Invalid rule_builder_data', e);
            }
        }
    });
    // addRows(1);

    let groupID = 1;
    // ============================================
    // ADD GROUP BUTTON
    // ============================================
    $("#addGroupBtn").click(function () {
        groupID++
        addGroup();
         addRows(groupID);
         $(`#discountrows_${groupID}`).hide();
        let groupCountvalue = $("#groupsContainer .group-box").length;
        if (groupCountvalue > 1) {
            $(`#groupAccessType`).show(); 
            if ($("#groupAccessType .groupaccesstype").length === 0) {
                let accessGroupHtml =`
                    <select class="groupaccesstype">
                        <option value="" disabled selected>Select</option>
                        <option value="ALL">ALL</option>
                        <option value="ANY">ANY</option>
                    </select> `
                $("#groupAccessType").append(accessGroupHtml);
            }
        }
    });

    // Toggle locations-master visibility when contractType selection changes
    $(document).on('change', '.contractType', function () {
        let $grp = $(this).closest('.group-box');
        let sel = $(this).val() || [];
        let enabled = false;
        if (window.CUSTOM_CONTRACTS_TYPE_ID && sel.length) {
            sel = Array.isArray(sel) ? sel : [sel];
            enabled = sel.map(String).includes(String(window.CUSTOM_CONTRACTS_TYPE_ID));
        }
        if (enabled) {
            $grp.find('.locations-master').prop('disabled', false).closest('div').show();
        } else {
            $grp.find('.locations-master').prop('disabled', true).closest('div').hide();
            // clear selection when hidden
            $grp.find('.locations-master').val(null).trigger('change');
        }
    });
 
    // ============================================
    // REMOVE GROUP
    // ============================================
    $(document).on("click", ".removeGroupBtn", function () {
        let groupID = $(this).data("group");

        if (confirm("Are you sure you want to remove this group?")) {
            $(`#group_${groupID}`).remove();
        }
        let groupCountvalue = $("#groupsContainer .group-box").length;
        if (groupCountvalue <= 1) {
            $(`#groupAccessType`).hide();    
            $("#groupAccessType .groupaccesstype").remove();         
        }
        if(groupCountvalue === 0){
            addGroup();
            $(`#discountrows_${groupID+1}`).hide();

        }
    });

    // ============================================
    // ADD ROW
    // ============================================
    // $(document).on("click", ".addRowBtn", function () {

    //     let groupID = $(this).data("group");

    //     let rowHTML = `
    //        <div class="discount-grid">
    //         <select class="row-category">
    //             <option value="">Category</option>
    //             <option>Electronics</option>
    //             <option>Clothing</option>
    //         </select>
    //         <select class="row-subcategory">
    //             <option value="">Sub Category</option>
    //             <option>Mobile</option>
    //             <option>Laptop</option>
    //         </select>
    //         <input type="number" class="row-discount" placeholder="Discount">
    //         <div class="removerowicon">
    //             <button class="removeRuleBtn">X</button>
    //         </div>
    //     </div>
    //     `;

    //     $(`#group_${groupID} .rows-container`).append(rowHTML);
    // });

    $(document).on("click", ".addRowBtn", function () {
        groupID = $(this).data("group");
        addRows(groupID)
        let rowCount = $(`#group_${groupID} #rows_${groupID} .discount-grid`).length;
        if (rowCount > 1) {
            $(`#rowAccessType_${groupID}`).show(); 
            if ($(`#rowAccessType_${groupID} .rowaccesstype`).length === 0) {
                let accessRowHtml =`
                    <select class="rowaccesstype">
                        <option value="" disabled selected>Select</option>
                        <option value="ALL">ALL</option>
                        <option value="ANY">ANY</option>
                    </select> `;
                $(`#rowAccessType_${groupID}`).append(accessRowHtml);
            }
        }
    });

    function addRows(groupID) {
        let entityTypeOptions = `<option value="" disabled selected>Select Entity Type</option>`;
        entityType.forEach(c => {
            entityTypeOptions += `<option>${c}</option>`;
        });
        let discountOptions = `<option value="" disabled selected>Select Discount Option</option>`;
        defaultOptions.forEach(c => {
            discountOptions += `<option>${c}</option>`;
        });
        let rowHTML = `
            <div class="discount-grid" id="rowgroup_${groupID}">
                <div >
                    <label>Entity Type</label>
                    <select class="entity-type" data-group="${groupID}">
                        ${entityTypeOptions}
                    </select>                    
                </div>
                <div>
                    <label>Discount Option</label>
                    <select class="discount-option" data-group="${groupID}">
                        ${discountOptions}
                    </select>
                </div>

                <div>
                    <label>Sub Category</label>
                    <select class="related-option" data-group="${groupID}">
                        <option value="" disabled selected>Select Sub Category</option>
                    </select>
                </div>
                <div>
                    <label>Min Discount</label>
                    <input type="number" class="min-discount" data-group="${groupID}" placeholder="Max Discount">
                </div>
                <div>
                    <label>Max Discount</label>
                    <input type="number" class="max-discount" data-group="${groupID}" placeholder="Min Discount">
                </div>
                <div class="removerowicon">
                    <label style="visibility: hidden;">Remove</label>
                    <button class="removeRuleBtn" data-group="${groupID}">X</button>
                </div>
            </div>
            
        `;

        $(`#group_${groupID} .rows-container`).append(rowHTML);
    }
    $(`#discountrows_${groupID}`).hide(); 
    $(document).on("change", ".customer-type", function () {
        let value = $(this).val();
        let groupID = $(this).data("group");

        if (value === "Domestic") {
            addRows(groupID);     
            $(`#rows_${groupID}`).show();
            $(`#discountrows_${groupID}`).show();
        } else {
            $(`#rows_${groupID}`).empty(); 
            $(`#rows_${groupID}`).hide(); 
            $(`#discountrows_${groupID}`).hide(); 
        }
    });
    // ============================================
    // REMOVE ROW
    // ============================================
    // $(document).on("click", ".removeRuleBtn", function () {
    //     $(this).closest(".rule-row").remove();
    // });

    $(document).on("click", ".removeRuleBtn", function(e){
        e.preventDefault(); // prevent default button behavior
        $(this).closest(".discount-grid").remove(); // remove the row
          let rowCount = $(`#group_${groupID} #rows_${groupID} .discount-grid`).length;
        if (rowCount <= 1) {
            $(`#rowAccessType_${groupID}`).hide();  
            $(`#rowAccessType_${groupID} .rowaccesstype`).remove();
           
        }
        if(rowCount === 0){
            addRows(1);
        }
    });

    //Entity type based option
    // $(document).on("change", ".entity-type", function () {
    //     let value = $(this).val();
    //     let groupID = $(this).data("group");
    //     let list = (value === "Public Sector Undertakings ( PSU )")
    //                 ? defaultOptions
    //                 : normalOptions;

    //     let html = `<option value="" disabled selected>Select Option</option>`;
    //     list.forEach(item => {
    //         html += `<option>${item}</option>`;
    //     });

    //     $(`#rowgroup_${groupID} .discount-option`).html(html);
    // });
    //Entity type based option

    //Discount based option
    $(document).on("change", ".discount-option", function () {
        let value = $(this).val();

        // Target only the current row so we don't overwrite related-option selects in other rows of the same group
        let related = psuRelatedOptions[value] || [];

        let html = `<option value="" disabled selected>Select Sub Category</option>`;
        related.forEach(item => {
            html += `<option>${item}</option>`;
        });

        let $row = $(this).closest('.discount-grid');
        $row.find('.related-option').html(html);
    });
    //Discount based option

    // ============================================
    // SUBMIT FORM TO API
    // ============================================

    function buildFinalData(){
        let finalData = { "gconditiontype": "",gcondition: [] };

        let groupCountvalue = $("#groupsContainer .group-box").length;

        // Set master condition type
        if (groupCountvalue > 1) {
            finalData.gconditiontype = $("#groupAccessType .groupaccesstype").val() || "ALL";
        } else {
            finalData.gconditiontype = "ALL";
        }
        
        $(".group-box").each(function () {
            
            let groupID = $(this).attr("id").split("_")[1];

            let group = {
                location: $(this).find(".location").val(),
                department: $(this).find(".department").val(),
                category: $(this).find(".mainCategory").val(),
                contractType: $(this).find(".contractType").val(),
                paymentType: $(this).find(".payment-type").val(),
                locationsMaster: $(this).find(".locations-master").val(),
                limitFrom: $(this).find(".limitFrom").val(),
                limitUp: $(this).find(".limitUp").val(),                customerType: $(this).find(".customer-type").val(),                discountRules: { "conditiontype":'',condition: [] }
            };
            group.discountRules.conditiontype = $("#rowAccessType_"+groupID+" .rowaccesstype").val() || "ALL";

            $(this).find(".discount-grid").each(function () {
                // group.discountRules.conditiontype = $(this).find(".rowaccesstype").val()
                group.discountRules.condition.push({
                    entitytype: $(this).find(".entity-type").val(),
                    discountoption: $(this).find(".discount-option").val(),
                    subcategory: $(this).find(".related-option").val(),
                    mindiscount: $(this).find(".min-discount").val(),
                    maxdiscount: $(this).find(".max-discount").val()
                });
            });

            finalData.gcondition.push(group);
        });

        return finalData;
    }

    $("#submitForm").click(function () {
        let finalData = buildFinalData();
        console.log(finalData);

        // Put finalData into hidden input so it submits with the form
        if($('#rule_builder_data').length){
            $('#rule_builder_data').val(JSON.stringify(finalData));
        }
        // If submit button is non-native, ensure the form is submitted
        $('#financial_form').submit();

    });

    // Ensure finalData is attached to the form before any form submit (covers native submits)
    $("#financial_form").on('submit', function(e){
        let finalData = buildFinalData();
        if($('#rule_builder_data').length){
            $('#rule_builder_data').val(JSON.stringify(finalData));
        }
        return true;
    });

});