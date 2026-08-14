
$(document).on("click", "div.pageClassDiv", function() {
    //console.log($('#signMain').attr('src'));
    if(!$(this).hasClass('signatureAdded')){
      $(this).append(`<div class="position-relative pe-3 sign-parent-div"><img class="float-end signatureActive" width="100" src="${$('#signMain').attr('src')}" /><i class="remove-img-sign ti ti-circle-minus position-absolute end-0 top-0 text-danger"></i></div>`);
      $(this).addClass('signatureAdded');
    }
});

$(document).on("click", "#allPageSign", function() {
    $(this).removeClass('signatureAdded');
    $('div.pageClassDiv').each(function(){
        if($(this).find('img.signatureActive').length == 0){
            $(this).append(`<div class="position-relative pe-3 sign-parent-div"><img class="float-end signatureActive" width="100" src="${$('#signMain').attr('src')}" /><i class="remove-img-sign ti ti-circle-minus position-absolute end-0 top-0 text-danger"></i></div>`);
            $(this).addClass('signatureAdded');
        }
    });
    
});

$(document).on("click", "i.remove-img-sign", function() {
    $(this).closest( "div.sign-parent-div" ).remove();
});