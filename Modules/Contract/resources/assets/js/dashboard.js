'use strict';

$(function () {
    
    $('#contracttype').select2({
        placeholder: "Choose Contract Type",
        allowClear: true
    });
    $('#contractlocs').select2({
        placeholder: "Choose Location",
        allowClear: true
    });
    $(document).on('click', '.clickableDashItems', function(){
        let attStatus = $(this).data("status");
        setCookie('filterStatus', attStatus,1);
        getContTypeLocs();
        window.location.href = APP_URL + '/contracts/list';
    });
    
    $(document).on('click', '.clickableDashUser', function(){
        let attStatus = $(this).data("status");
        let attUser = $(this).data("user");
        setCookie('filterStatus', attStatus,1);
        setCookie('myFilterStatus', attUser,1);
        getContTypeLocs();
        window.location.href = APP_URL + '/contracts/list';
    });
    
    $(document).on('click', '.clickableDashTasks', function(){
        let attStatus = $(this).data("status");
        let attUser = $(this).data("user");
        setCookie('myFilterTasks', attUser,1);
        getContTypeLocs();
        window.location.href = APP_URL + '/tasks?status=' + attStatus;
    });
    
    document.cookie = 'filterStatus' + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
    document.cookie = 'filterApplied' + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
    document.cookie = 'filterSet' + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
    document.cookie = 'myFilterStatus' + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
    
});

function getContTypeLocs(){
    let contractType = $('#contracttype').val() ?? "";
    let contractLocs = $('#contractlocs').val() ?? "";
    if(contractType != ""){
        setCookie('filterConType', JSON.stringify(contractType),1);
    }
    if(contractLocs != ""){
        setCookie('filterConLoc', JSON.stringify(contractLocs),1);
    }
}

function setCookie(name,value,days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}