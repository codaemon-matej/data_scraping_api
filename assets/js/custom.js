
var base_url = $('#base_url').val();

//search task and remove it from list
$('.search-button').click(function () {
    var bisname = $('#bname').val();
    if(bisname=="")
    {
        $("#search_form").validate({
            rules: {
                fname: "required",
                lname: "required",
                state: "required",
            },
            messages: {
                fname: "Enter first name",
                lname: "Enter last name",
                state: "Enter state",
            }
        });

        validate = $("#search_form").valid();
    }
    else
    {
        validate = true;
    }
    if (validate)
    {
        $(".search-button").prop("disabled", true);
        fname = $("input[name=fname]").val();
        lname = $("input[name=lname]").val();
        state = $('select[name=state]').val();
        state = state.toUpperCase();
        bname = $('input[name=bname]').val();
        table = $('#datatable').DataTable({
            "ordering":  false,
            "destroy": true,
            "pagingType": "full_numbers",
            "dom": 'lBfrtip',
            "ajax": {
                "url": base_url + "datatablecontroller/search_data",
                "dataSrc": "",
                "data": {"fname": fname, "lname": lname, "state": state, "bname": bname},
                "type": "POST",
            },
            success: function (response) {
                //success process here
                $(".search-button").prop("disabled", false);
            },
            "initComplete": function (settings, json) {
                $(".search-button").prop("disabled", false);
            },
            "columnDefs": [
                {
                    "targets": [0],
                    "render": function (data, type, row, meta) {
                        return (row['Name'] != '') ? row['Name'] : "";
                    }
                },
                {
                    "targets": [1],
                    "render": function (data, type, row, meta) {
                        return row['CoOwnerName'];
                    }
                },
                {
                    "targets": [2],
                    "render": function (data, type, row, meta) {
                        return row['PropertyId'];
                    }
                },
                {
                    "targets": [3],
                    "render": function (data, type, row, meta) {
                        url = (row['url'] != '') ? row['url'] : 'javascript:void(0)'
                        return "<a href='" + url + "' target='_blank'>" + row['State'] + "</a>";
                    }
                },
                {
                    "targets": [4],
                    "render": function (data, type, row, meta) {
                        return row['Location'];
                    }
                },
                {
                    "targets": [5],
                    "render": function (data, type, row, meta) {
                        var amount = (row['Amount'] != '') ? row['Amount'] : "";
                        return amount;
                    }
                },
                {
                    "targets": [6],
                    "render": function (data, type, row, meta) {
                        return row['Shares'];
                    }
                },
                {
                    "targets": [7],
                    "render": function (data, type, row, meta) {
                        return row['ReportingCompany'];
                    }
                },
                {
                    "targets": [8],
                    "render": function (data, type, row, meta) {
                        return row['ReportedBy'];
                    }
                },
            ]
        });
    }

});
$(document).ready(function () {
    $('#datatable').DataTable({
        paging: true,
        sPaginationType: "full_numbers",
    });
});