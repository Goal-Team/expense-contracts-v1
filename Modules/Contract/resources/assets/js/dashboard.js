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
    // The list page reads its filters from the URL query string now (dev rule
    // 2026-08-27), so the handoff travels as query parameters, not cookies.
    $(document).on('click', '.clickableDashItems', function(){
        let attStatus = $(this).data("status");
        window.location.href = APP_URL + '/contracts/list?' + listFilterQuery(attStatus, false);
    });

    $(document).on('click', '.clickableDashUser', function(){
        let attStatus = $(this).data("status");
        window.location.href = APP_URL + '/contracts/list?' + listFilterQuery(attStatus, true);
    });

    $(document).on('click', '.clickableDashTasks', function(){
        let attStatus = $(this).data("status");
        let attUser = $(this).data("user");
        setCookie('myFilterTasks', attUser,1);
        window.location.href = APP_URL + '/tasks?status=' + attStatus;
    });
    
    document.cookie = 'filterStatus' + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
    document.cookie = 'filterApplied' + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
    document.cookie = 'filterSet' + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
    document.cookie = 'myFilterStatus' + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
    
});

// Query string for the contract list: the clicked status, the my=1 flag for the
// "my actions" tiles, and the dashboard's own type/location selects.
function listFilterQuery(status, myOnly){
    let params = new URLSearchParams();
    params.set('status', status);
    if(myOnly){
        params.set('my', '1');
    }
    let contractType = $('#contracttype').val();
    let contractLocs = $('#contractlocs').val();
    // Comma-separated ints (contype=1,2 - dev call 2026-08-28, no JSON in the
    // URL). URLSearchParams encodes a comma as %2C; a comma is legal unencoded
    // in a query string (RFC 3986), so put the literal comma back.
    if(Array.isArray(contractType) && contractType.length > 0){
        params.set('contype', contractType.join(','));
    }
    if(Array.isArray(contractLocs) && contractLocs.length > 0){
        params.set('locations', contractLocs.join(','));
    }
    return params.toString().replace(/%2C/g, ',');
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