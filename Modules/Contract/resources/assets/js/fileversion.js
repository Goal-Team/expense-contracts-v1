var quill;
var metaData = [];
$(document).ready(function() {
  getClauseListData();
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
      ['link', 'image', 'video'],
      ['comment'],
      ['undo', 'redo']
    ],
    handlers: {'comment': function() {}}
  }
  quill = new Quill("#snow-container", {
    placeholder: "Compose an epic...",
    modules: {
      toolbar: toolbarOptions,
      inline_comment: true
    },
    theme: "snow"
  });

  $('#btn-html-undo').on('click', () => { 
    quill.history.undo();
    console.log('undo');

  }); 
  $('#btn-html-redo').on('click', () => { 
    quill.history.redo();
    console.log('redo');
  });
  
// Fetch data on contract type change
$('#contracttype').on('change', function () {
    getClauseListData('all');
    getClauseListData('default');
});

});

$(document).on("click", "#comment-button", function() {
  var prompt = window.prompt("Please enter Comment", "");
  var txt;
  if (prompt == null || prompt == "") {
    txt = "User cancelled the prompt.";
  } else {
    var range = quill.getSelection();
    if (range) {
      if (range.length == 0) {
        alert("Please select text", range.index);
      } else {
        var text = quill.getText(range.index, range.length);
        console.log("User has highlighted: ", text);
        metaData.push({ range: range, comment: prompt });
        quill.formatText(range.index, range.length, {
          background: "#fff72b"
        });
        drawComments(metaData);
      }
    } else {
      alert("User cursor is not in editor");
    }
  }
});

function drawComments(metaData) {
  var $commentContainer = $("#comments-container");
  var content = "";
  $.each(metaData, function(index, value) {
    content +=
      "<a class='comment-link' href='javascript:;' data-index='" +
      index +
      "'><li class='list-group-item'>" +
      value.comment +
      "</li></a>";
  });
  $commentContainer.html(content);
}

$(document).on('click','.comment-link',function () {
            var index = $(this).data('index');
            console.log("comment link called",index);
            var data = metaData[index];
            quill.setSelection(data.range.index, data.range.length);
            //quill.scrollSelectionIntoView();
        });
   
// Fetch Clauses List
function getClauseListData(type="all") {
   var form = $('#createCustom').serialize();
   $.ajax({
       url: APP_URL + '/contract-setup/clause/list/template/'+type,
       type: 'POST',
       data: {contracttype: $('#contracttype').val()},
       headers: {
           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
       },
       success: function(response) {
           if(type == "all"){
            $('#form-list').html(response);
           }else{
            $('#snow-container').html(response);
           }
           resetIndex();
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