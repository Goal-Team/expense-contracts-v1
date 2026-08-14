$(document).ready(function(){
    $('#showAllFields').trigger('change');
    //$('[name="Partygroup[party][1][external_type]"]').val('organization').trigger('change');
    
    let partyIndex = $('#party-table-body tr').length;

    $(document).on('change', '.partygroup', function () {
        const $row = $(this).closest('tr');
        const mode = $(this).val();
        toggleFields($row, mode);
    });

    function toggleFields($row, mode) {
        $row.find('.location-wrap, .location-grp-wrap, .external-type-wrap, .external-name-wrap, .internal-name-wrap').hide();

        if (mode === 'Internal') {
            $row.find('.location-wrap').show();
            $row.find('.internal-name-wrap').show();
        } else if (mode === 'Intergroup') {
            $row.find('.location-grp-wrap').show();
            $row.find('.internal-name-wrap').show();
        } else if (mode === 'External') {
            $row.find('.external-type-wrap').show();
            $row.find('.external-name-wrap').show();
        }
    }

    $('#add-party-row').click(function () {
        partyIndex = $('#party-table-body tr').length;
        const template = $('#party-row-template').html();
        const newRowHtml = template
            .replace(/__INDEX__/g, partyIndex)
            .replace(/__NUMBER__/g, partyIndex + 1);
        const $newRow = $(newRowHtml).attr('data-index', partyIndex);

        $('#party-table-body').append($newRow);
        $newRow.find('.select2').select2();
        toggleFields($newRow, 'External');
        partyIndex++;
    });

    $('#party-table-body').on('click', '.delete-party-row', function () {
        $(this).closest('tr').remove();

        $('#party-table-body tr').each(function (i, row) {
            const $row = $(row);
            $row.attr('data-index', i);
            $row.find('td:first').text(`Party ${i + 1}`);

            $row.find(':input').each(function () {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[party]\[\d+]/, `[party][${i}]`);
                    $(this).attr('name', newName);
                }
            });
        });

        partyIndex = $('#party-table-body tr').length;
    });

    // On load
    $('#party-table-body tr').each(function () {
        const $row = $(this);
        const mode = $row.find('.partygroup:checked').val() || 'Internal';
        toggleFields($row, mode);
    }); 
    
// NOTE: the '.partySubType' change handler that used to live here was a duplicate of the
// one in contract.js (which is also loaded on this page and targets the same elements via
// data-party-row / #partyExternal_<row>). Both fired on every party-type change, each
// firing its own /contracts/create/partylist request and re-running .select2() on the same
// name dropdown without destroying the previous instance. The stacked select2 instances and
// their listeners made opening the External party dropdown hang the page
// ("This page isn't responding"). contract.js is now the single owner of this behaviour.

$('#onboardHorizontalImageModal').on('hidden.bs.modal', function (e) {
   let datacut = $('.popap').data('cut');
   $(`tr [data-index=${datacut}]`).find('.partySubType').trigger('change');
});

$('#analysisAccordion .accordion-collapse').on('show.bs.collapse', function() {
    const $body = $(this).find('.accordion-body');
    const type = $(this).attr('id').includes('Risk') ? 'Risk Analysis' : 'Clause Analysis';
    
    // Call API only if body is empty
    if ($body.html().trim() === '') {
      fetchAnalysis(type, $body);
    }
});
    
});
$(document).on('change', '#ai-docs', async function (e){
    //e.preventDefault(); // Prevent default form submission if inside a form
    
    //document.getElementById('load').style.visibility="visible";

    var fileInput = $('#ai-docs')[0]; // Get the DOM element for the file input
    var files = fileInput.files;

    if (files.length > 0) {
        var formData = new FormData();
        
        let aiToken = $('#aiTokenTemp').val() ?? false;
        // Append the selected file to the FormData object
        // The key 'myFile' will be used on the server-side to access the file
        formData.append('file', files[0]); 
        formData.append('aiTokenTemp', aiToken); 
        
        $('.contract-ai-loader').removeClass('d-none');
        $('#contract_response_head_reading').removeClass('d-none');
        $('#contract_response_head').addClass('d-none');
        $('#contract_response').html('');
        
        if(aiToken){
            $.ajax({
               url:  APP_URL + '/contracts/aidata',
               type: 'POST',
               data: formData,
                processData: false,  // ❗ Prevent jQuery from processing the data
                contentType: false,  // ❗ Prevent jQuery from setting the content type                   
               headers: {
                   'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
               },
               success: function(response) {
                    let finalResponse = response.resp;

                    document.getElementById('load').style.visibility="hidden";
                    
                    $('#contractDetails').val(finalResponse.basic_details?.contract_description);
                    
                    //For Description
                    $('#contractDescription').text(finalResponse.basic_details?.contract_description);
                    
                    if(finalResponse.party_details && finalResponse.party_details.length > 0){
                        $('#party-table-body tr').slice(2).remove();
                        let findex = 0;
                        for(let parti of finalResponse.party_details){
                            $('#add-party-row').trigger('click');
                            findex++;
                        }
                    }
                    
                    //For Party 1
                    let party1Address = finalResponse.party_1?.address;
                    
                    //let cords = geocodeAddress(party1Address, 'Partygroup[party][0][location]');
                    
                    //For Party 2
                    let party2name = finalResponse.party_2?.name ?? null;
                    let party2Address = finalResponse.party_2?.address;
                    
                    if(response.existing?.length > 0){
                        response.existing.forEach(function (item) {
                            
                            let party_number = item.key.split("_");
                            let inpName = 'external_name';
                            let inpType = 'External';
                            if(item.type != '' && item.type != 'external'){
                                inpName = 'location';
                                inpType = 'Internal';
                                $(`[name="Partygroup[party][${(party_number[1])-1}][mode]"][value="${inpType}"]`).prop('checked', true).trigger('change');
                                $(`[name="Partygroup[party][${(party_number[1])-1}][${inpName}]"]`).val(item.exist).prop('selected', true).trigger('change'); 
                            }else{
                                $(`[name="Partygroup[party][${(party_number[1])-1}][mode]"][value="${inpType}"]`).prop('checked', true).trigger('change');
                                $(`[name="Partygroup[party][${(party_number[1])-1}][external_type]"]`).val('organization').trigger('change');
                                $(`[name="Partygroup[party][${(party_number[1])-1}][${inpName}]"]`).one('ajax:done', function() {
                                    setTimeout(function() {
                                        $(`[name="Partygroup[party][${(party_number[1])-1}][${inpName}]"]`).val(item.exist).prop('selected', true).trigger('change');
                                    }, 1000);
                                });
                            }
                            
                            
                        });
                    }
                    
                    let endContractType = finalResponse.contract_duration?.contract_end_type ?? 'onetimeContract';
                    let endDateElm = false;
                    if(endContractType == 'fixedTerm'){
                        endDateElm = `fixedtimeEndDateofContract`;
                    }
                    if(endContractType == 'onetimeContract'){
                        endDateElm = `onetimeEndDateofContract`;
                    }

                    $(`[name="Duration[effectiveDate]"][value="${endContractType}"]`).trigger('click');
                    $(`[name="Duration[typeRenewal]"]`).trigger('change');
                    
                    let $selectRenewal = $('[name="Duration[typeRenewal]"]');
                    
                    let responseRenewalValue = finalResponse.contract_duration?.type_of_renewal ?? '';
                    
                    // find the matching option ignoring case
                    let matchRenewal = $selectRenewal.find('option').filter(function() {
                        return $(this).val().toLowerCase() === responseRenewalValue.toLowerCase();
                    });
                    
                    // if found, set and trigger Select2 update
                    if (matchRenewal.length) {
                        $selectRenewal.val(matchRenewal.val()).trigger('change.select2');
                    } else {
                        console.warn("No matching option found for:", responseRenewalValue);
                    }                    
                    $('[name="Duration[fixedDate]"]').val(finalResponse.contract_duration?.commencement_date ?? '').trigger('change');
                    $('[name="Duration[fixedDate]"]')[0]._flatpickr.setDate(finalResponse.contract_duration?.commencement_date ?? '');
                    $(`[name="Duration[${endDateElm}]"]`).val(finalResponse.contract_duration?.end_date_of_contract ?? '').trigger('change');
                    if(endDateElm){
                        $(`[name="Duration[${endDateElm}]"]`)[0]._flatpickr.setDate(finalResponse.contract_duration?.end_date_of_contract ?? '');
                    }
                    let billFreqResp = finalResponse.payment_terms_details?.billing_frequency;
                    $('#BillingFrequency option').filter(function () {
                        return $(this).text().toLowerCase().includes(billFreqResp.toLowerCase());
                    }).prop('selected', true).trigger('change');
                    
                    $('#ContractValue').val(finalResponse.contract_currency).trigger('change');
                    $('#ContractBillingValue').val(finalResponse.contract_value).trigger('change');
                    $('#contract_response_head').removeClass('d-none');
                    $('#contract_response').append(buildTree(finalResponse));
                    $('.contract-ai-loader').removeClass('d-none');
                    $('#contract_response_head_reading').addClass('d-none');
                    
                    //Other Details
                    $('[name="ContractValue[discounts]"]').val(finalResponse.payment_terms_details.discounts_or_rebates);
                    $('[name="ContractValue[escalationClauses]"]').val(finalResponse.payment_terms_details.escalation_clauses);
                    $('[name="ContractValue[financialGuarantees]"]').val(finalResponse.payment_terms_details.financial_guarantees_or_bonds);
                    $('[name="ContractValue[payment_escrow]"]').val(finalResponse.payment_terms_details.payment_escrow);
                    $('[name="ContractValue[paymentTerms]"]').text(finalResponse.payment_terms_details.payment_terms_description);
                    $('[name="ContractValue[retention]"]').val(finalResponse.payment_terms_details.retention_or_holdbacks);
                    $('[name="ContractValue[taxes]"]').val(finalResponse.payment_terms_details.taxes_and_fees);
                    
                    if(response.nonExisting?.length > 0){
                        renderNonExistingList(response.nonExisting);
                    }
               },
               error: function(xhr, status, error) {
                    $('#contract_response_head').removeClass('d-none');
                    $('.contract-ai-loader').removeClass('d-none');
                    $('#contract_response_head_reading').text('Invalid File Type').addClass('text-danger');
                    document.getElementById('load').style.visibility="hidden";                   
               }
        });
        }else{
            $('#contract_response_head').removeClass('d-none');
            $('.contract-ai-loader').removeClass('d-none');
            $('#contract_response_head_reading').text('Invalid Token').addClass('text-danger');            
        }
    }
});

$(document).on('click', '.btn-show-ai-response', function (e) {
    let aiResponseOffcanvasElm = document.getElementById('aiResponseOffcanvas');
    let aiResponseOffcanvas = new bootstrap.Offcanvas(aiResponseOffcanvasElm);    
    aiResponseOffcanvas.show();
});

$(document).on('click', '.btn-show-ai-chat', function (e) {
    let aiResponseChatOffcanvasElm = document.getElementById('aiResponseChatOffcanvas');
    let aiResponseChatOffcanvas = new bootstrap.Offcanvas(aiResponseChatOffcanvasElm);    
    aiResponseChatOffcanvas.show();
});

// Delegate click for dynamically created elements too
$(document).on('click', '.btn-add-party', function (e) {
    e.preventDefault();
    console.log('came inside');
    let $btn = $(this);
    let name = $btn.data('name') || '';
    let pan = $btn.data('pan') || '';    
    let gst = $btn.data('gst') || '';    
    
    $('.popap').attr('data-cut', $(this).attr('data-exdd'));
    let subType = $(`#partyExternal_${$(this).attr('data-exdd')}_type`).val();
    
    $.ajax({
        url: APP_URL + `/parties/contract-parties-${subType != "individual" ? 'org' : 'ind'}-add?by=ajax`,
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            $('.popap').html(response);
            
            //For Missing Party Details
            let $modalPartyAdd = $('#onboardHorizontalImageModal');
            let modalEl = $modalPartyAdd[0];
            let bsModalPartyAdd = new bootstrap.Modal(modalEl);
            bsModalPartyAdd.show(); 
            
            //For Missing Party Details
            let $nameInput = $('#company_name');
            let $panInput = $('#PANNumber');            
            let $gstInput = $('#gstinnumber') ?? false;            
            $nameInput.val(name);
            $panInput.val(pan);  

            if($gstInput){
                $gstInput.val(gst);
            }
        }
    });

});

function renderNonExistingList(nonExisting) {
    var $list = $('#non-existing-list');
    $list.empty();
    $('#messageContainer').empty();

    if (!nonExisting || nonExisting.length === 0) {
        $('#messageContainer').html('<div class="alert alert-success">All parties exist in the database.</div>');
        return;
    }else{
        $('#messageContainer').html('<h5>Non-existing Parties</h5>');
    }

    nonExisting.forEach(function (item) {
        var addressHtml = item.address ? '<div class="text-muted">' + $('<div>').text(item.address).html() + '</div>' : '';
        let party_number = item.key.split("_");
        let inpType = 'External';        
        $(`[name="Partygroup[party][${(party_number[1])-1}][mode]"][value="${inpType}"]`).prop('checked', true).trigger('change');
        $(`[name="Partygroup[party][${(party_number[1])-1}][external_type]"]`).val('organization').trigger('change');

        var $item = $(
            '<div class="list-group-item d-flex justify-content-between align-items-start" data-key="' + $('<div>').text(item.key).html() + '">' +
                '<div><strong>' + $('<div>').text(item.name).html() + '</strong>' + addressHtml + '</div>' +
                '<div><button type="button" class="btn btn-primary btn-add-party" data-exdd="'+((party_number[1])-1)+'" data-gst="'+ item.gst_number +'" data-pan="'+ item.pan_number +'" data-name="' + $('<div>').text(item.name).html() + '" data-address="' + $('<div>').text(item.address || '').html() + '">Add as party</button></div>' +
            '</div>'
        );
        $list.append($item);
    });
}


function getAllParties(jsonData) {
    let parties = [];

    if (jsonData.resp.party_1?.name) parties.push(jsonData.resp.party_1.name);
    if (jsonData.resp.party_2?.name) parties.push(jsonData.resp.party_2.name);

    if (Array.isArray(jsonData.resp.party_details)) {
        jsonData.resp.party_details.forEach(p => {
            if (p.name) parties.push(p.name);
        });
    }

    return parties;
}

function getPartyAddress(name) {
    if (jsonData.resp.party_1?.name === name) return jsonData.resp.party_1.address;
    if (jsonData.resp.party_2?.name === name) return jsonData.resp.party_2.address;

    let match = jsonData.resp.party_details?.find(p => p.name === name);
    return match ? match.address : '';
}
// Capitalize key names
function capitalizeKey(key) {
    return key.replace(/_/g, ' ')
              .replace(/\b\w/g, c => c.toUpperCase());
}

// Recursively build the tree
function buildTree(obj) {
    const ul = document.createElement('ul');

    for (const key in obj) {
        const li = document.createElement('li');
        const value = obj[key];

        const keySpan = document.createElement('span');
        keySpan.classList.add('key');
        keySpan.textContent = capitalizeKey(key) + ': ';

        if (value === null || value === undefined) {
            const nullSpan = document.createElement('span');
            nullSpan.classList.add('null', 'value');
            nullSpan.textContent = 'null';
            nullSpan.title = "Click to copy";
            nullSpan.onclick = () => copyToClipboard("null", nullSpan);

            li.appendChild(keySpan);
            li.appendChild(nullSpan);
        } else if (typeof value === 'object') {
            const toggle = document.createElement('span');
            toggle.textContent = '[+] ';
            toggle.classList.add('toggle');
            toggle.onclick = () => {
                childUl.classList.toggle('hidden');
                toggle.textContent = childUl.classList.contains('hidden') ? '[+] ' : '[-] ';
            };

            const childUl = buildTree(value);
            childUl.classList.add('hidden');

            li.prepend(toggle);
            li.appendChild(keySpan);
            li.appendChild(childUl);
        } else {
            const valueSpan = document.createElement('span');
            valueSpan.classList.add('value');
            valueSpan.textContent = value;
            valueSpan.title = "Click to copy";
            valueSpan.onclick = () => copyToClipboard(value, valueSpan);

            li.appendChild(keySpan);
            li.appendChild(valueSpan);
        }

        ul.appendChild(li);
    }

    return ul;
}

function copyToClipboard(text, element) {
    navigator.clipboard.writeText(text).then(() => {
        // Add 'copied' style
        element.classList.add('copied');

        // Create or reuse a copied message span
        let message = document.createElement('span');
        message.classList.add('copy-msg');
        message.textContent = ' Copied!';
        element.parentNode.insertBefore(message, element.nextSibling);

        // Remove after delay
        setTimeout(() => {
            element.classList.remove('copied');
            if (message) {
                message.remove();
            }
        }, 1000);
    });
}



async function geocodeAddress(address, elms) {
    $.ajax({
        url:  `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(address)}&key=AIzaSyDQRH1bN0QJKJg_M2IhE03JGlebTu8Um-M`,
        type: 'POST',
        success: function(data) {
            if (data.status === 'OK') {
                const location = data.results[0].geometry.location;
                let cords = `${location.lat},${location.lng}`;
                $(`[name="${elms}"] option`).filter(function () {
                    return $(this).text().toLowerCase().includes(cords);
                }).prop('selected', true).trigger('change');
                console.log(cords);
            } else {
                return { error: data.status };
            }
        }
    });
}

    // ----- UI logic -----
  $(function () {
      $('#send').on('click', async function () {
        callAiChat();
      });

      $('#clear').on('click', function () {
        $('#prompt').val('');
        $('#response').text('No response yet.');
        $('#meta').text('');
      });

      $('#example').on('click', function () {
        $('#prompt').val('Write a short, friendly onboarding message (2 sentences) for someone who just created an account on a team task app.');
      });
    });
    
  //For Initiate Modal
    
  $(function () {
      var $modal = $('#contractModal');
      if (!$modal.length) return;

      var bsModal = new bootstrap.Modal($modal[0], {
        keyboard: true,
        backdrop: true
      });

      var $alert = $('#contractModalAlert');

      bsModal.show();
      
      // Prevent modal closing if invalid (backdrop/esc/cancel)
      $modal.on('hide.bs.modal', function (e) {
        // Allow closing if editing flag is not set? (we keep validation always)
        if (!validateModalFields()) {
          e.preventDefault();
          showAlert('Please fill all mandatory fields (marked with *).');
        }
      });      
      
      function showAlert(text) {
        $alert.text(text || 'Please fill all mandatory fields (marked with *).').removeClass('d-none');
        setTimeout(function () { $alert.addClass('d-none'); }, 4000);
      }

      // Clear modal fields and validation state (call when opening Add/New)
      function clearModalFields() {
        // clear selects
        $modal.find('select').each(function () {
          $(this).val(null).trigger('change');
        });
        // clear inputs & textareas
        $modal.find('input[type!="radio"][type!="checkbox"], textarea').each(function () {
          $(this).val('');
        });
        // clear radio/checkboxes
        $modal.find('input[type="radio"], input[type="checkbox"]').prop('checked', false);
        // remove validation classes
        $modal.find('.is-invalid').removeClass('is-invalid');
        // remove editing flag (so Save will create a new accordion entry)
        $modal.removeData('editingAccordionId');
      }

      // Ensure accordion container exists. If user removed it from DOM, create it after the Add button.
      function ensureAccordionContainer() {
        var $container = $('#contractAccordionContainer');
        if (!$container.length) {
          $container = $('<div id="contractAccordionContainer" class="accordion mt-4"></div>');
          $('#contractAddBtn').after($container);
        }
        return $container;
      }

      // validate modal fields (same logic as before)
      function validateModalFields() {
        var valid = true;
        var $firstInvalid = null;

        if ($.fn.select2) {
          $modal.find('select.select2').each(function () { $(this).trigger('change.select2'); });
        }

        $modal.find('[required]').each(function () {
          var $el = $(this);
          if ($el.is(':disabled') || !$el.is(':visible')) return;
          var tag = (this.tagName || '').toLowerCase();

          if ($el.is(':checkbox') || $el.is(':radio')) {
            var name = $el.attr('name');
            if (!name) return;
            var checked = $modal.find('[name="' + name + '"]:checked').length || $('[name="' + name + '"]:checked').length;
            if (checked === 0) {
              valid = false;
              if (!$firstInvalid) $firstInvalid = $el;
              $modal.find('[name="' + name + '"]').addClass('is-invalid');
            } else {
              $modal.find('[name="' + name + '"]').removeClass('is-invalid');
            }
            return;
          }

          var val = $el.val();
          if (tag === 'select') {
            if (val === null || val === '' || (Array.isArray(val) && val.length === 0)) {
              valid = false;
              if (!$firstInvalid) $firstInvalid = $el;
              $el.addClass('is-invalid');
            } else {
              $el.removeClass('is-invalid');
            }
            return;
          }

          if ($.trim(String(val || '')) === '') {
            valid = false;
            if (!$firstInvalid) $firstInvalid = $el;
            $el.addClass('is-invalid');
          } else {
            $el.removeClass('is-invalid');
          }
        });

        if (!valid && $firstInvalid && $firstInvalid.length) {
          var top = $firstInvalid.offset().top - 100;
          $('html, body').animate({ scrollTop: top }, 300, function () {
            try { $firstInvalid.focus(); } catch (e) {}
          });
        }
        return valid;
      }

      // collect data (adapt to actual modal fields)
      function collectModalData() {
        var data = {};
        data.contractTypeValue = $('#contracttype').val();
        data.contractTypeText = $('#contracttype option:selected').text() || '';
        data.departmentValue = $('#DepartmentType').val();
        data.departmentText = $('#DepartmentType option:selected').text() || '';
        data.categoryValue = $('#catgoeryType').val();
        data.categoryText = $('#catgoeryType option:selected').text() || '';
        data.exclusivityValue = $('select[name="BasicContract[Exclusivity]"]').val();
        data.exclusivityText = $('select[name="BasicContract[Exclusivity]"] option:selected').text() || '';
        data.description = $('#contractDescription').val() || '';
        var tagTexts = $('#contracttypetags option:selected').map(function () { return $(this).text(); }).get();
        data.tags = tagTexts;
        data.tagsText = tagTexts.join(', ');
        data.priority = $('#priority').val() || '';
        // add custom fields if needed
        data.custom = {};
        $modal.find('[data-custom-field-name]').each(function () {
          var name = $(this).data('custom-field-name');
          data.custom[name] = $(this).val();
        });
        return data;
      }

      // simple unique id
      function uid() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
      }

      // build accordion html (same as previous implementation)
      function buildAccordionCard(data, accordionId) {
        var idCollapse = 'contractAccordionCollapse-' + accordionId;
        var headerId = 'contractAccordionHeading-' + accordionId;
        var safeTitle = 'Basic Contract Information';

        var html = '<div class="accordion-item" data-accordion-id="' + accordionId + '">';
        var ShowToggleHtml = ``;
        html += '<h2 class="accordion-header" id="' + headerId + '">';
        html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#' + idCollapse + '" aria-expanded="false" aria-controls="' + idCollapse + '">';
        html += '<span class="fw-bold me-2">' + escapeHtml(safeTitle) + '</span>';
        var summaryParts = [];
        if (data.departmentText) summaryParts.push(escapeHtml(data.departmentText));
        if (data.categoryText) summaryParts.push(escapeHtml(data.categoryText));
        if (data.priority) summaryParts.push(escapeHtml(data.priority));
        //if (summaryParts.length) html += '<small class="text-muted ms-2">' + summaryParts.join(' · ') + '</small>';
        html += '</button></h2>';

        html += '<div id="' + idCollapse + '" class="accordion-collapse collapse" aria-labelledby="' + headerId + '">';
        html += '<div class="accordion-body">';
        html += '<div><strong>Contract Type:</strong> ' + escapeHtml(data.contractTypeText) +ShowToggleHtml+'</div>';
        html += '<div><strong>Department:</strong> ' + escapeHtml(data.departmentText) + '</div>';
        html += '<div><strong>Category:</strong> ' + escapeHtml(data.categoryText) + '</div>';
        html += '<div><strong>Exclusivity:</strong> ' + escapeHtml(data.exclusivityText) + '</div>';
        html += '<div><strong>Description:</strong> <div class="mt-1">' + nl2br(escapeHtml(data.description)) + '</div></div>';
        if (data.tags && data.tags.length) html += '<div class="mt-2"><strong>Other Scopes:</strong> ' + escapeHtml(data.tagsText) + '</div>';
        if (data.priority) html += '<div class="mt-2"><strong>Priority:</strong> ' + escapeHtml(data.priority) + '</div>';
        if (data.custom && Object.keys(data.custom).length) {
          html += '<div class="mt-2"><strong>Custom Fields:</strong><ul>';
          $.each(data.custom, function (k, v) {
            html += '<li>' + escapeHtml(k) + ': ' + escapeHtml(v) + '</li>';
          });
          html += '</ul></div>';
        }
        html += '<div class="mt-3">';
        html += '<button type="button" class="btn btn-sm btn-outline-primary me-2 contract-accordion-edit" data-accordion-id="' + accordionId + '">Edit</button>';
        html += '</div>';
        html += '</div></div></div>';
        return html;
      }

      function nl2br(str) { return String(str || '').replace(/\n/g, '<br/>'); }

      // When user clicks "Add Contract" button
      $('#contractAddBtn').on('click', function () {
        clearModalFields();
        // ensure container exists so Save handler can append
        ensureAccordionContainer();
        bsModal.show();
        // focus first required field if you want
        setTimeout(function () {
          var $first = $modal.find('[required]').first();
          if ($first.length) try { $first.focus(); } catch (e) {}
        }, 200);
      });

      // Save handler: validate, create accordion if needed, append and store data
      $('#contractModalSave').on('click', function () {
        //if ($.fn.select2) $modal.find('select.select2').each(function () { $(this).trigger('change'); });

        if (!validateModalFields()) {
          showAlert('Please fill all mandatory fields (marked with *).');
          return;
        }

        var data = collectModalData();
        var accordionId = $modal.data('editingAccordionId');

        var $accordionContainer = ensureAccordionContainer();

        if (accordionId) {
          var $existing = $accordionContainer.find('[data-accordion-id="' + accordionId + '"]');
          if ($existing.length) {
            var newHtml = buildAccordionCard(data, accordionId);
            $existing.replaceWith(newHtml);
            $('#showAllFields').trigger('change');
            // store raw data on element for accurate edits later
            var $newElem = $accordionContainer.find('[data-accordion-id="' + accordionId + '"]');
            $newElem.data('contractData', data);
            // open updated item
            var $newCollapse = $('#contractAccordionCollapse-' + accordionId);
            $accordionContainer.find('.accordion-collapse.show').each(function () {
              var bs = bootstrap.Collapse.getInstance(this);
              if (bs) bs.hide();
            });
            new bootstrap.Collapse($newCollapse[0], { toggle: true });
            $modal.removeData('editingAccordionId');
          }
        } else {
          var newId = uid();
          var newHtml = buildAccordionCard(data, newId);
          $accordionContainer.append(newHtml);
          var $newItem = $accordionContainer.find('[data-accordion-id="' + newId + '"]');
          $newItem.data('contractData', data); // store raw data for future edit
          // expand it
          var $newCollapse = $('#contractAccordionCollapse-' + newId);
          $accordionContainer.find('.accordion-collapse.show').each(function () {
            var bs = bootstrap.Collapse.getInstance(this);
            if (bs) bs.hide();
          });
          new bootstrap.Collapse($newCollapse[0], { toggle: true });
        }

        bsModal.hide();
        $('#ai-docs').focus();
      });

      // Edit and Remove within accordion
      $(document).on('click', '.contract-accordion-edit', function () {
        var id = $(this).data('accordion-id');
        var $item = $('#contractAccordionContainer').find('[data-accordion-id="' + id + '"]');
        if (!$item.length) return;
        var storedData = $item.data('contractData') || {};
        // Map storedData back to modal fields (prefer IDs where we stored them)
        if (storedData.contractTypeValue) $('#contracttype').val(storedData.contractTypeValue).trigger('change');
        else if (storedData.contractTypeText) {
          var match = $('#contracttype option').filter(function () { return $.trim($(this).text()) === storedData.contractTypeText; }).first();
          if (match.length) $('#contracttype').val(match.val()).trigger('change');
        }

        if (storedData.departmentValue) $('#DepartmentType').val(storedData.departmentValue).trigger('change');
        else if (storedData.departmentText) {
          var m = $('#DepartmentType option').filter(function () { return $.trim($(this).text()) === storedData.departmentText; }).first();
          if (m.length) $('#DepartmentType').val(m.val()).trigger('change');
        }

        if (storedData.categoryValue) $('#catgoeryType').val(storedData.categoryValue).trigger('change');
        else if (storedData.categoryText) {
          var m2 = $('#catgoeryType option').filter(function () { return $.trim($(this).text()) === storedData.categoryText; }).first();
          if (m2.length) $('#catgoeryType').val(m2.val()).trigger('change');
        }

        if (storedData.exclusivityValue) $('select[name="BasicContract[Exclusivity]"]').val(storedData.exclusivityValue).trigger('change');
        else if (storedData.exclusivityText) {
          var m3 = $('select[name="BasicContract[Exclusivity]"] option').filter(function () { return $.trim($(this).text()) === storedData.exclusivityText; }).first();
          if (m3.length) $('select[name="BasicContract[Exclusivity]"]').val(m3.val()).trigger('change');
        }

        if (storedData.description) $('#contractDescription').val(storedData.description);
        if (storedData.tags && storedData.tags.length) {
          // if we stored tag values earlier, set them, otherwise match by text
          var vals = [];
          $('#contracttypetags option').each(function () {
            if (storedData.tags.indexOf($(this).text()) !== -1 || storedData.tags.indexOf($(this).val()) !== -1) vals.push($(this).val());
          });
          if (vals.length) $('#contracttypetags').val(vals).trigger('change');
        }
        if (storedData.priority) $('#priority').val(storedData.priority).trigger('change');

        $modal.data('editingAccordionId', id);
        bsModal.show();
      });

      // Remove validation highlight on input/change
      $modal.on('input change', '[required]', function () {
        var $el = $(this);
        if ($el.is(':checkbox') || $el.is(':radio')) {
          var name = $el.attr('name');
          if (name && $modal.find('[name="' + name + '"]:checked').length) $modal.find('[name="' + name + '"]').removeClass('is-invalid');
        } else {
          if ($.trim(String($el.val() || '')) !== '') $el.removeClass('is-invalid');
        }
      });

    });
    
  $(document).on('click', '.get-suggestions', function() {
    const $body = $(this).closest('.accordion-body');
    const type = $body.attr('id').includes('risk') ? 'Risk Analysis' : 'Clause Analysis';
    fetchAnalysis(type, $body);
  });    
  
  async function callAiChat(promptElm='promptAiContract', responseHtml='response'){
        const prompt = $(`#${promptElm}`).val().trim();
        const conDetails = $('#contractDetails').val().trim();
        if (!prompt) {
          ///alert('Please enter a prompt.');
          return;
        }
        $(`#${responseHtml}`).text('Thinking...');
        $('#meta').text('');

        try {
            const data = await $.ajax({
              url: APP_URL + '/contracts/aidata/chatbot', // your Laravel route
              method: 'POST',
              dataType: 'json',
              data: {
                prompt: prompt
              },
              headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              },              
            });
            
            if (data.error) {
              $(`#${responseHtml}`).html('<span class="text-danger">' + data.error + '</span>');
            } else {
              // Convert markdown-like formatting to simple HTML
              let formatted = formatChatResponse(data.text);
              $(`#${responseHtml}`).html(formatted);
            }
            
            console.log('Gemini API response:', data.raw);
        } catch (err) {
            console.error(err);
            $(`#${responseHtml}`).html('<span class="text-danger">Server error. Check console.</span>');
        }     
  }
  

    function formatMarkdownForJSIndented(markdown, indentLevel = 2) {
      const indent = ' '.repeat(indentLevel);
    
      // Escape backticks
      const escaped = markdown.replace(/`/g, '\\`');
    
      // Add indentation to each line
      const indented = escaped
        .split('\n')
        .map(line => (line ? indent + line : line))
        .join('\n');
    
      // Wrap in backticks
      return `\`\n${indented}\n\``;
    }
  
    function formatChatResponse(text) {
      if (!text) return '';
    
      // 1. Escape HTML characters
      text = text.replace(/&/g, '&amp;')
                 .replace(/</g, '&lt;')
                 .replace(/>/g, '&gt;');
    
      // 2. Handle fenced code blocks (```language ... ```)
      text = text.replace(/```(\w+)?\n([\s\S]*?)```/g, (m, lang, code) => {
        lang = lang ? `language-${lang}` : '';
        return `<pre><code class="${lang}">${code.trim()}</code></pre>`;
      });
    
      // 3. Bold (**text**)
      text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
      // 4. Line breaks
      text = text.replace(/\n/g, '<br>');
    
      return text;
    }
  
  
      // Function to call API
    function fetchAnalysis(type, $container) {
      $container.html('<div class="text-muted">Analysing Please Wait...</div>');
        var fileInput = $('#ai-docs')[0]; // Get the DOM element for the file input
        var files = fileInput.files;
        
        if (files.length > 0) {
            var formData = new FormData();
            // Append the selected file to the FormData object
            // The key 'myFile' will be used on the server-side to access the file
            formData.append('file', files[0]); 

              // Replace this URL with your actual API endpoint
              $.ajax({
               url:  APP_URL + '/contracts/aidata/riskanalysis',
               type: 'POST',
               data: formData,
                processData: false,  // ❗ Prevent jQuery from processing the data
                contentType: false,  // ❗ Prevent jQuery from setting the content type                   
               headers: {
                   'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
               },
                success: function(data) {
                  const result = data.resp || 'No data returned';
                //   $container.html(`
                //     <p><strong>${type}:</strong> ${result}</p>
                //     <button class="btn btn-outline-primary btn-sm mt-2 get-suggestions">Get Suggestions</button>
                //   `);
                    // ============================
                    //  1️⃣ Missing Clauses Table
                    // ============================
                    var missingContainer = $("#clauseBody");
                    
                    missingContainer.html('');
                
                    var missingTable = $("<table class='table table-bordered'></table>");
                    var missingHeader = $("<thead><tr><th>Clause</th><th>Status</th></tr></thead>");
                    var missingBody = $("<tbody></tbody>");
                
                    $.each(data.resp.missing_clauses, function(key, value) {
                        var clauseName = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                        var statusClass = value ? "present" : "missing";
                        var statusText = value ? "✅ Present" : "❌ Missing";
                        missingBody.append(
                            $("<tr></tr>")
                                .append($("<td></td>").text(clauseName))
                                .append($("<td></td>").addClass(statusClass).text(statusText))
                        );
                    });
                
                    missingTable.append(missingHeader).append(missingBody);
                    missingContainer.append(missingTable);
                
                
                    // ============================
                    //  2️⃣ Overall Risk Table
                    // ============================
                    var riskContainer = $("#riskBody");
                    
                    riskContainer.html('');
                
                    var risk = data.resp.overall_risk;
                    var riskBox = $("<div class='risk-container'></div>");
                    riskBox.append(`<div class='risk-header'>Overall Risk Level: ${risk.risk_level}</div>`);
                    riskBox.append(`<div class='risk-summary'>${risk.risk_summary}</div>`);
                    
                    var riskList = $("<ul class='risk-list'></ul>");
                    $.each(risk.potential_risks, function(i, item) {
                        riskList.append($("<li></li>").text(item));
                    });
                    
                    riskBox.append("<strong>Key Risks:</strong>");
                    riskBox.append(riskList);

                    riskContainer.append(riskBox);                 
                },
                error: function(xhr, status, error) {
                  $container.html(`<div class="text-danger">Error: ${error}</div>`);
                }
              });
        }else{
            $container.html('<div class="text-danger">Invalid File Given...</div>');
        }
    }
    
    
    //input ai chat box

    function sendMessage() {
        var text = $('#ai-user-input').val().trim();
        if (!text) return;
        
        $('#promptAiContract').val(text);
        $('.btn-show-ai-chat').trigger('click');

        callAiChat('ai-user-input');
    }

    $('#ai-user-input').keydown(function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });    

  // small utility to avoid XSS when inserting plain text
  function escapeHtml(text) {
    if (text == null) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }    
