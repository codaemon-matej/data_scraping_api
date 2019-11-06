<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow,noarchive">
        <title>Unclaimed Money</title>
        <!-- Bootstrap CSS -->
        <link href="<?php echo $this->config->item('iframe_asset_url');?>assets/style/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo $this->config->item('iframe_asset_url');?>assets/style/jquery.dataTables.min.css?a=5" rel="stylesheet">

        <?php if( $this->router->fetch_class() == 'iframe' ) { ?> 
        <link href="<?php echo $this->config->item('iframe_asset_url');?>assets/style/style.css?a=19" rel="stylesheet">
        <?php } ?>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
        <link href="https://fonts.googleapis.com/css?family=Lato:300,300i,400,400i,700,700i,900,900i" rel="stylesheet">
        
        <style>
            .error {
                color:red;
                font-weight: 200
            }
        </style>
        <?php if( $this->router->fetch_class() == 'iframe' ) { ?> 
        <!-- Google Analytics -->
        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

            ga('create', 'UA-546951-7', 'auto');
            ga('send', 'pageview');
        </script>
        <!-- End Google Analytics -->
        <?php } ?>
    </head>
    <body>        
        <?php echo $content_for_layout; ?>
        <input type="hidden" id="base_url" value="<?php echo base_url(); ?>">
        <input type="hidden" id="iframe_url" value="<?php echo $this->config->item('iframe_asset_url');?>">
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.3.1.js "></script>
        <!-- DataTables 
        <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js "></script>
        <!-- Bootstrap JavaScript -->
        <script src="<?php echo $this->config->item('iframe_asset_url');?>assets/lib/jquery.dataTables.min1.js?a=3"></script>        
        <script src="<?php echo $this->config->item('iframe_asset_url');?>assets/lib/bootstrap.min.js?a=3"></script>        
        <!-- Validate JavaScript -->
        <script src="<?php echo $this->config->item('iframe_asset_url');?>assets/lib/jquery.validate.min.js?a=3"></script>
        <!-- Custom JavaScript -->
        <?php if( $this->router->fetch_class() == 'iframe' ) { ?>                  
        <script type="text/javascript" src="<?php echo $this->config->item('iframe_asset_url');?>assets/js/custom-iframe.js?n=110"></script>
        <?php } else { ?>            
        <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/custom.js?a=3"></script>
        <?php } ?>
    </body>
</html>