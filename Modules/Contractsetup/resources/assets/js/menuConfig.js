var quill;
var metaData = [];
$(document).ready(function() {
  $('#btnAddMenu').on('click', function(){
    $('#menuForm')[0].reset();
    $('#menuModal').modal('show');
  });

  $('.btn-edit').on('click', function(){
    var id = $(this).data('id');
    $.get(APP_URL + '/contract-setup/admin/menu-configs/'+id+'/edit', function(res){
      if(res.config){
        $('#menu_id').val(res.config.id);
        $('#menu_type').val(res.config.menu_type);
        $('#role').val(res.config.role);
        $('#menu_json').val(res.config.menu_json);
        $('#active').prop('checked', res.config.active);
        $('#menuModal').modal('show');
      }
    });
  });

  $('#menuForm').on('submit', function(e){
    e.preventDefault();
    var id = $('#menu_id').val();
    var url = id ? APP_URL + '/contract-setup/admin/menu-configs/'+id : APP_URL + '/contract-setup/admin/menu-configs';
    var method = id ? 'PUT' : 'POST';
    $.ajax({
      url: url,
      method: method,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },      
      data: $(this).serialize(),
      success: function(res){
        location.reload();
      }
    });
  });

  $('.btn-delete').on('click', function(){
    if(!confirm('Are you sure?')) return;
    var id = $(this).data('id');
    $.ajax({url: APP_URL + '/contract-setup/admin/menu-configs/'+id, method: 'DELETE',            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }, success: function(){ location.reload(); }});
  });

});