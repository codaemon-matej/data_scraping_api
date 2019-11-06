<div class="row">
        <div class="col-md-12">
            <div class="row">
                <form id="search_form" name="search_form">
                    <div class="col-md-2 form-group">
                        <input type="text" id="fname" name="fname" placeholder="First Name (Required)" class="form-control">
                    </div>
                    
                    <div class="col-md-2 form-group">
                        <input type="text" id="lname" name="lname" placeholder="Last Name (Required)" class="form-control">
                    </div>

                    <div class="col-md-2 form-group">
                        <select id="state" name="state" class="form-control">
                            <option value=""> Select State </option>
                            <?php foreach($states as $st) { ?>
                                <option value="<?php echo $st['code'];?>"><?php echo $st['state'];?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4 form-group" style="border-left: 2px solid black;">
                        <input type="text" id="bname" name="bname" placeholder="Or Business Name" class="form-control">
                    </div>                    
                </form>
                <div class="col-md-2 form-group">
                    <button class="btn btn-success form-control search-button">Search</button>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">User's Search Result</div>

                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="datatable" class="table table-hover table-bordered table-striped datatable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>CoOwner Name</th>
                                    <th>Property ID</th>
                                    <th>State</th>
                                    <th>Location</th>
                                    <th>Amount</th>
                                    <th>Shares</th>
                                    <th>Reporting Company</th>
                                    <th>Reported By</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>