var quill;
var metaData = [];
$(document).ready(function() {
  
  $( "#contractTypeSelector" ).modal('show');
  
   var toolbarOptions = {
    container: [
      ['bold', 'italic', 'underline', 'strike'],
      ['blockquote', 'code-block'],
      [{ 'header': 1 }, { 'header': 2 }],
      [{ 'list': 'ordered' }, { 'list': 'bullet' }],
      [{ 'script': 'sub' }, { 'script': 'super' }],
      [{ 'indent': '-1' }, { 'indent': '+1' }],
      [{ 'direction': 'rtl' }],
      [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
      [{ 'color': [] }, { 'background': [] }],
      [{ 'font': [] }],
      [{ 'align': [] }],
      ['clean'],
      ['link', 'image', 'video']
    ]
  }

    const editor = $('#snow-container');

    quill = new Quill("#snow-container", {
        modules: {
          toolbar: toolbarOptions
        },
        theme: "snow"
    });

    // get html content
    quill.getHTML = () => {
      return quill.root.innerHTML;
    };
    
    quill.on('text-change', () => {
        console.log('get html', quill.getHTML());
    });  

  $('#btn-html-undo').on('click', () => { 
    quill.history.undo();
    console.log('undo');

  }); 
  $('#btn-html-redo').on('click', () => { 
    quill.history.redo();
    console.log('redo');
  });
  
    $('#btn-doc-downloader').on('click', () => {
    // Get HTML content
    var html = quill.root.innerHTML;
    var converted = htmlDocx.asBlob(('<!DOCTYPE html>' + html));
    saveAs(converted, 'test.docx');
    
    });
  
    // Fetch data when contract type / payment type / entity type change
    $('#contracttype, #payment_type, #entity_type_id').on('change', function () {
        getClauseListData('all');
        getClauseListData('default', $('#contracttype'));
    });

    // Populate entity types when contract type or scope changes (load all entity types; optional scope filter could be added)
    function loadEntityTypes(scope = null){
        let url = APP_URL + '/parties/parties-get-entity-types';
        let data = {};
        if(scope) data.scope = scope;
        $.get(url, data, function(items){
            const $sel = $('#entity_type_id');
            $sel.empty().append('<option value="">- Entity Type -</option>');
            items.forEach(function(it){
                $sel.append(`<option value="${it.id}">${it.name}</option>`);
            });
        });
    }

    // load entity types on page load
    loadEntityTypes();

    $(document).on('click', '#save-contract-template', function(){
        saveContractTemplate();
    });
});


//For Drag and Drop
/* Events fired on the drag target */
document.addEventListener("dragstart", function(event) {
  event.dataTransfer.setData("Text", event.target.id);
});

document.addEventListener("drag", function(event) {
  //document.getElementById("demo").innerHTML = "The text is being dragged";
});

/* Events fired on the drop target */
document.addEventListener("dragover", function(event) {
  event.preventDefault();
});

document.addEventListener("drop", function(event) {
  event.preventDefault();
  if ( event.target.className == "ql-editor" ) {
    const data = event.dataTransfer.getData("Text");
    let dataContentType = $(`#${data}`).data('con-type');
    var newElement;
    if(dataContentType == 'heading'){
        newElement = document.createElement('h6');
    }else{
        newElement = document.createElement('p');
    }
    newElement.textContent = document.getElementById(data).textContent;
    let adjacentPos = 'afterend';
    if(event.target.classList.contains('ql-editor')){
        adjacentPos = 'beforeend';
    }
    event.target.insertAdjacentElement(adjacentPos, newElement);
    newElement.setAttribute("id", data);
  }
});

// Fetch Clauses List
function getClauseListData(type="all", selVal=null) {
   $.ajax({
       url: APP_URL + '/contract-setup/clause/list/template/'+type,
       type: 'POST',
       data: {
           contracttype: $('#contracttype').val(),
           payment_type: $('#payment_type').val() || null,
           entity_type_id: $('#entity_type_id').val() || null
       },
       headers: {
           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
       },
       success: function(response) {
           if(type == "all"){
            $('#form-list').html(response);
           }else{
            if(selVal){
                $('#contractTypeText').html(selVal.children("option").filter(":selected").text());
                $('#contractTypeSelected').val(selVal.val());
            }
            //quill.clipboard.dangerouslyPasteHTML(response);
            var delta = quill.clipboard.convert(response);
            quill.setContents(delta, 'silent'); 
            customVarsOptions();
           }
           resetIndex();
       },
       error: function(xhr, status, error) {
           console.error(xhr.responseText);
       }
   });
}

// Save Contract List
function saveContractTemplate() {
   $.ajax({
       url: APP_URL + '/contract-setup/clause/template-store',
       type: 'POST',
       data: {
           'contracttype': $('#contractTypeSelected').val(),
           'payment_type': $('#payment_type').val() || null,
           'entity_type_id': $('#entity_type_id').val() || null,
           'templatebuilded': encodeURIComponent(quill.getHTML())
       },
       headers: {
           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
       },
       success: function(response) {
           if(response.success){
               $(`#${response.operation ?? ''}_alert`).show().text(response.message);
               setTimeout(() => {
                   $(`#${response.operation ?? ''}_alert`).hide().text('');
               }, 2000);               
               getClauseListData();
           }else{
               $('#error').show().text(response.message);
               setTimeout(() => {
                   $('#error').hide().text('');
               }, 2000);                
           }
       },
       error: function(xhr, status, error) {
           console.error(xhr.responseText);
       }
   });
}

//Custom Vars
function customVarsOptions(){
    
    const contentNew = quill.getContents();    
    
    $('#snow-container').html('');
    $('.ql-toolbar').remove();
    
    $.ajax({
       url: APP_URL + '/contract-setup/customvarlist',
       type: 'GET',
       headers: {
           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
       },
       success: function(response) {
           var toolbarOptions_ = {
            container: [
              ['bold', 'italic', 'underline', 'strike'],
              ['blockquote', 'code-block'],
              [{ 'header': 1 }, { 'header': 2 }],
              [{ 'list': 'ordered' }, { 'list': 'bullet' }],
              [{ 'script': 'sub' }, { 'script': 'super' }],
              [{ 'indent': '-1' }, { 'indent': '+1' }],
              [{ 'direction': 'rtl' }],
              [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
              [{ 'color': [] }, { 'background': [] }],
              [{ 'font': [] }],
              [{ 'align': [] }],
              ['clean'],
              ['link', 'image', 'video'],
              [{'formvariables': Object.values(response.data)}],
            ],
            handlers: {
                "formvariables": function (value, elm) { 
                    if (value) {
                        const formVarOptions = document.querySelectorAll('.ql-formvariables .ql-picker-item');
                        var textChoosed = false;
                        formVarOptions.forEach(function(elmm){
                            if(elmm.textContent == value){
                                textChoosed = elmm.dataset.value;
                            }
                        });
                        if(textChoosed){
                            const cursorPosition = this.quill.getSelection().index;
                            quill.insertText(cursorPosition, " ");
                            quill.insertText(cursorPosition + 1, textChoosed, {'customQuillClass': 'highlightQuillCustomVar' });
                            quill.insertText(cursorPosition + 1 + textChoosed.length, " ");
                            quill.setSelection(cursorPosition + 1 + textChoosed.length + 1, 0);
                            //quill.scrollSelectionIntoView();
                        }
                    }
                }
            }
          };
        const Inline = Quill.import('blots/inline');
        
        class customQuillClass extends Inline {
          static create(value) {
            const node = super.create();
            node.classList.add(value);
            return node;
          }
        
          static formats(node) {
            return node.className;
          }
        }
        
        customQuillClass.blotName = 'customQuillClass';
        customQuillClass.tagName = 'span';
        
        Quill.register(customQuillClass);          
        quill = new Quill("#snow-container", {
            modules: {
              toolbar: toolbarOptions_,
            },
            theme: "snow"
        });
        
        // get html content
        quill.getHTML = () => {
          return quill.root.innerHTML;
        };
        
        quill.on('text-change', () => {
            console.log('get html', quill.getHTML());
        });         
        
        // We need to manually supply the HTML content of our custom dropdown list
        const placeholderPickerItems = Array.prototype.slice.call(document.querySelectorAll('.ql-formvariables .ql-picker-item'));
        const optionCustVars = Object.keys(response.data);
        placeholderPickerItems.forEach((item, $idx) => {item.textContent = item.dataset.value; item.dataset.value = optionCustVars[$idx]});
        document.querySelector('.ql-formvariables .ql-picker-label').innerHTML = 'Custom Variable' + document.querySelector('.ql-formvariables .ql-picker-label').innerHTML; 
        
        quill.setContents(contentNew);

       },
       error: function(xhr, status, error) {
           console.error(xhr.responseText);
       }
    });
}

// Function to reset index values
function resetIndex() {
    $('.panel-body').find('input.index').each(function(index) {
        $(this).val(index + 1);
    });
    $('input.group').each(function(index) {
        $(this).val($(this).closest('.panel-default').find('.panel-title').data('id'));
    });
}


    // Function to copy text to clipboard
    function copyTextToClipboard(text) {
        const tempInput = $("<input>");
        $("body").append(tempInput);
        tempInput.val(text).select();
        document.execCommand("copy");
        tempInput.remove();

        showToast("Copied: " + text);
    }

    // Click event for h6 (category title)
    $(document).on('click',".panel-title", function () {
        const text = $(this).text().trim();
        copyTextToClipboard(text);
    });

    // Click event for p.value (field value)
    $(document).on('click',".value", function () {
        const text = $(this).text().trim();
        copyTextToClipboard(text);
    });

    // Show toast
    function showToast(message) {
        const toast = $('<div class="custom-toast"></div>').text(message);
        $("body").append(toast);

        setTimeout(() => {
            toast.addClass("show");
            setTimeout(() => {
                toast.removeClass("show");
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }, 100);
    }