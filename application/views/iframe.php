<section class="search-unclaimed-money-florida-page-banner">
    <div class="search-unclaimed-money-florida-page-banner-section-overlay">
        <div class="container"> 
            <div class="banner text-center">
                <h1 class="banner-title">Search for Unclaimed Money</h1>
                <div class="banner-form">
                    <form class="form-inline custom-field" id="search_form" name="search_form" method="GET">
                        <div class="col-md-10 input-main-div">
                            <div class="form-group col-md-4 col-sm-3 col-xs-12 custom-form-group">
                                <input class="form-control" id="fname" name="fn" placeholder="First Name" type="text">
                            </div>
                            <div class="form-group col-md-4 col-sm-3 col-xs-12 custom-form-group">
                                <input class="form-control" id="lname" name="ln" placeholder="Last Name" type="text">
                            </div>
                            <div class="form-group col-md-4 col-sm-3 col-xs-12 custom-form-group select-box">
                                <div class="custom-select">
                                    <div class="custom-select-arrow"></div>
                                    <select id="state" name="state" class="form-control">
                                        <option value=""> Select State </option>
                                        <?php foreach($states as $st) { ?>
                                        <option data-status="<?php echo $st['status'];?>" data-url="<?php echo $st['url'];?>" value="<?php echo $st['code'];?>"><?php echo $st['state'];?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div><label id="state-error" class="error" for="state"></label></div>
                            </div>
                        </div>
                        <button type="submit" onClick="ga('send', 'event', 'unclaimed money', 'search', 'from results');" class="btn my-search-btn-primary search-button col-md-2 col-sm-3 col-xs-12">Search</button>
                    </form>
                    <input type="hidden" name="access_token" value="<?php echo $access_token; ?>">
                    <input type="hidden" value="" id="claim_url">
                </div>
            </div>
        </div>
    </div>
</section>
            
<section class="sp search-result">
    <div class="container"> 
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <div class="page-header-box">
                    <h1 class="article-title"><b><span class="search_cnt"></span> Results</b> for <b><span class="people_name"></span></b> in <b><span class="only_state_name"></span></b> in the last 24 hours!</h1>
                    <p class="sub-heading" id="sub-heading-locate">Locate the person you're looking for in the results below. If the name and address match, click the green button to start the claim process.</p>
                    <!-- <hr class="heading-bdr"> -->
                </div>
            </div>
        </div>
    </div>
    <div class="container table-div-2">
        <div class="row">
            <!-- Added by amit -->
            <div class="col-md-12">
                <div class="table-responsive mob-table-main">
                    <div class="table-res col-sm-12">
                        <table id="datatable" class="table table-hover table-striped table-responsive datatable no-footer" role="grid" aria-describedby="datatable_info" >
                            <thead>
                                <tr role="row">
                                    <th class="sorting_disabled" rowspan="1" colspan="1" >Name</th>
                                    <th class="sorting_disabled" rowspan="1" colspan="1" >Address</th>
                                    <th class="sorting_disabled" rowspan="1" colspan="1" >Amount</th>
                                    <th class="sorting_disabled" rowspan="1" colspan="1" >Reported By</th>
                                    <th class="sorting_disabled" rowspan="1" colspan="1" >Claim</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                        <div class="col-sm-10 col-sm-offset-1 text-center no-access">
                            <h4 class="heading">We could not access results <span class="state_name"></span></h4>
                            <p class="sub-heading">
                                Please refer to the state's official website
                                <br>
                                for the most up-to-date results
                            </p>
                            <a href="#" target="_blank" class="no-access-url"><button class="btn btn-visit-site">Visit Official Site</button></a>
                        </div>                       
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
