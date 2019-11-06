var base_url = $('#base_url').val();
var iframe_url = $('#iframe_url').val();
$(document).ready(function () {
    
    // Takes value from url parameter
    var fname = getUrlParam('fn','');
    var lname = getUrlParam('ln','');
    var state = getUrlParam('state','');

    state = state.toUpperCase();
    $("input[name=fn]").val(fname);
    $("input[name=ln]").val(lname);
    $('select[name=state]').val(state);

    var state_text = $('#state option:selected').text();
    $('.state_name').text(' in ' + state_text);
    var status = $('option:selected', this).attr('data-status');
    var url = $('option:selected', this).attr('data-url');

   
    if(status == 0)
    {
        $(".no-access").show();
        $('.no-access-url').attr("href", url);
        // GA Event for track state not available
        ga('send', 'event', 'unclaimed money', 'search results', 'state not available');
    }
    else
    {         
    	addValues('top',fname,lname,state,state_text);
    }
    
});

// Function to scrape data using ajax and bind to datatable
function addValues(position,fname,lname,state,state_text) {
    
    $("#search_form").validate({
        rules: {
            fn: "required",
            ln: "required",
            state: "required",
        },
        messages: {
            fn: "Enter first name",
            ln: "Enter last name",
            state: "Enter state",
        }
    });
    validate = $("#search_form").valid();

    if (position == "top" && validate == true)
    {
        access_token = $('input[name=access_token]').val();
        $.fn.dataTable.ext.errMode = function (settings, helpPage, message) {
            $('div.dataTables_filter input').addClass('custom-search');
            var scrollPos = $(".mob-table-main").offset().top;
            $(window).scrollTop(scrollPos);            
            $(".article-title").hide();
            $(".loading-cont").html('<p class="loading-text"><b>An error occurred processing your search. Please try again in a few minutes.</b></p>');
            // GA Event for track error
            ga('send', 'event', 'unclaimed money', 'search results', 'error');
        };

        table = $('#datatable').DataTable({
            "ordering": true,
            "order": [[ 0, "desc" ]],
            "destroy": true,
            "pagingType": "simple",
            "pageLength": 25,
            "dom": '<"search-bg col-sm-12"lBf>rtip',
            "oLanguage": {
                "sLoadingRecords": '<div class="col-sm-6 col-sm-offset-3 col-md-4 col-md-offset-4 text-center loading-cont"><img src="'+iframe_url+'/assets/image/loading_mark.gif" alt="" class="loading-icn"><p class="loading-text"><b>Please wait while we search for matches</b><br>It may take up to a minute or two, depending on which state you are searching</p></div>',
                "sEmptyTable": '<div class="col-sm-6 col-sm-offset-3 text-center no-match"><p class="loading-text">Sorry! No matches were found</div>',
                "sSearch": "<span class='srch-label-text hidden-xs'><b>Search within results </b><br class='no-line-brk'> Filter results by keywords like city or street.</span> <span class='srch-label-text visible-xs'><b>Search within results: </b>Filter results by keywords like city or street.</span>",
            },
            "ajax": {
                "url": iframe_url+"getdata",
                "dataSrc": "",
                "data": {"fname": fname, "lname": lname, "access_token": access_token, "state": state},
                "type": "POST",
            },
            "initComplete": function (settings, json) {
                if(json.length)
                {                 
                    // GA Events for track search results
                    ga('send', 'event', 'unclaimed money', 'search results', json.length);

                    if ($('#datatable tr').length < 26) {
                        $('.paginate_button').hide();
                    }
                    $("#sub-heading-locate").show();
                    $('#claim_url').val(json[0].state_url);
                    $(".search_cnt").html(json.length);
                    $('.only_state_name').html(json[0].state_name);
                    $(".article-title").show();
                }
                else
                {
                    $(".search_cnt").html('No');
                    $('.only_state_name').html(state_text);
                    $(".article-title").show();
                }
                $('.people_name').html(titleCase(fname) + ' ' + titleCase(lname));
                $('div.dataTables_filter input').addClass('custom-search');
                
                if(json.length > 0)
                {
                    $(".dataTables_info").show();
                    $(".dataTables_paginate").show();
                }
            },
            "columnDefs": [
                {
                    "className": "desktop-table",
                    "targets": [0],
                    "render": function (data, type, row, meta) {
                        var name = (row['Name'] != '') ? row['Name'] : ""
                        return '<i class="fa fa-user-circle-o fa-2x" aria-hidden="true"></i>'+name;
                    }
                },
                {
                    "className": "desktop-table",
                    "targets": [1],
                    "render": function (data, type, row, meta) {
                        return row['Location'] + ' ' + row['State'];
                    }
                },
                {
                    "className": "desktop-table",
                    "targets": [2],
                    "render": function (data, type, row, meta) {
                        var amount = (row['Amount'] != '') ? row['Amount'] : "";
                        return amount;
                    }
                },
                {
                    "className": "desktop-table",
                    "targets": [3],
                    "render": function (data, type, row, meta) {
                        return row['ReportedBy'];
                    }
                },
                {
                    "className": "desktop-table",
                    "targets": [4],
                    "render": function (data, type, row, meta) {
                        url = (row['url'] != '') ? row['url'] : 'javascript:void(0)';
                        searchid = row['searchId'];
                        return "<a id='"+searchid+"' onclick='openModal(this)' class='btn btn-block btn-table'>Claim</a>";
                    }
                },
                {
                    "className": "table-mob col-xs-12",
                    "targets": [5],
                    "render": function (data, type, row, meta) {
                        var url = (row['url'] != '') ? row['url'] : 'javascript:void(0)';
                        var name = (row['Name'] != '') ? row['Name'] : "";
                        var state = (row['State'] != '') ? row['State'] : "";
                        var location = (row['Location'] != '') ? row['Location']+ ' ' + row['State'] : "";
                        var amount = (row['Amount'] != '') ? row['Amount'] : "";
                        var reported_by = (row['ReportedBy'] != '') ? row['ReportedBy'] : "";
                        var searchid = row['searchId'];
                        return '<div class="main-content"><i class="fa fa-address-book-o" aria-hidden="true"></i><span class="content-right">'+name+'</span></div><div class="main-content"><i class="fa fa-location-arrow" aria-hidden="true"></i><span class="content-right">'+location+'</span></div><div class="main-content"><i class="fa fa-money" aria-hidden="true"></i><span class="content-right">'+amount+'</span></div><div class="main-content"><i class="fa fa-building-o" aria-hidden="true"></i><span class="content-right">'+reported_by+'</span></div><div class="main-content"><a class="btn btn-block btn-table" onclick="openModal(this)">Claim</a></div>';
                    }
                },
            ]
        });
    }
}

// function to get url parameter, if not set return empty string.
function getUrlParam(parameter, defaultvalue){
    var urlparameter = defaultvalue;
    if(window.location.href.indexOf(parameter) > -1){
        urlparameter = getUrlVars()[parameter];
    }

    urlparameter = changeSpecialChar(urlparameter);
    
    return urlparameter;
}

function changeSpecialChar(e) {
    e = e.replace(/\+/g, ' ');
    e = e.replace(/%20/g, ' ');
    e = e.replace(/[+;#$%\/@,<>()1234567890]/g, '');    
    return e
}

// function to get url parameter
function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

//Covert String to Title Case
function titleCase(str) {
  return str.toLowerCase().split(' ').map(function(word) {
    return (word.charAt(0).toUpperCase() + word.slice(1));
  }).join(' ');
}

//Redirect to official site on click Claim
function openModal(thisObj) {
    // GA event for tracking click on Claim button
    ga('send', 'event', 'unclaimed money', 'click', 'claim');
    var state_name_full = $('#state option:selected').text();
    var url = $("#claim_url").val();

    $('.modal-text').remove();
    $('.btn-table').css({'background-color': 'transparent','color': '#81B44C','border': '2px solid #81B44C'});
    $('.btn-table').attr('disabled',false);

    $(thisObj).css({'background-color': 'transparent','color': '#EBEAF4','border': '2px solid #EBEAF4'});
    $(thisObj).closest('tr').after('<tr class="modal-text"><td colspan="5"><img src="'+iframe_url+'assets/image/triangle.svg" class="triangle-icon"><p class="message">Each state has their own claim process and rules on how to retrieve this money. You will now be leaving our site to file your claim on the official '+state_name_full+' website</p><a href="'+url+'" onClick="ga("send", "event", "unclaimed money", "click", "visit state claims site");" target="_blank" class="btn btn-message">Go to official state website <i class="fa fa-external-link" aria-hidden="true"></i></a></td></tr>');
    $(thisObj).attr('disabled',true);
}