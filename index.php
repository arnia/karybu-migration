<?php
try{
    require_once 'core/Migration.php';
    $migration = new Migration();
    $types = $migration->getSupportedSources();
}
catch (Exception $e){
    echo $e->getMessage();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="generator" content="Karybu (karybu.org)" />
    <title>Karybu Migration Export Tool</title>
    <link rel="stylesheet" href="assets/jquery/css/ui-lightness/jquery-ui-1.10.3.custom.css" />
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="assets/karybu/css/karybu.css" />
    <script type="text/javascript" src="assets/jquery/js/jquery-1.9.1.js"></script>
    <script type="text/javascript" src="assets/jquery/js/jquery-ui-1.10.3.custom.js"></script>
    <script type="text/javascript" src="assets/jquery/js/jquery.loadTemplate-0.5.4.js"></script>
    <script type="text/javascript" src="assets/karybu/js/karybu.js"></script>
</head>
<body>
<div id="migrate" class="wrapper">
    <form action="" class="form form-horizontal">
        <div id="select-source" class="step">
            <h5>Select application to export</h5>
            <div class="control-group">
                <label class="control-label">Select application type</label>
                <div class="controls">
                    <select name="source_type" id="source_type">
                        <option value="">Select export source</option>
                        <?php foreach ($types as $key=>$value) : ?>
                            <option value="<?php echo $key?>"><?php echo $value?></option>
                        <?php endforeach;?>
                    </select>
                </div>
            </div>
            <div id="path_holder" class="control-group" style="display:none">
                <label class="control-label">Path to application instance</label>
                <div class="controls">
                    <input type="text" name="path" id="path" />
                    <span class="help-inline">Relative or absolute path</span>
                </div>
            </div>
            <div class="control-group">
                <button type="button" id="app-continue" class="btn">Continue</button>
            </div>
        </div>
        <div id="entities" class="step" style="display:none"></div>
        <div id="download" class="step" style="display:none">Download</div>
    </form>
</div>
<script type="text/html" id="partition-template">
    <div class="control-group">
        <label class="control-label">
            <div data-content="label"></div>
            <div data-content="count"></div>
        </label>
        <div class="controls">
            <span data-content="input"></span>
            <button type="button" class="btn update-partition">Update partition size</button>
        </div>
    </div>
</script>
<script type="text/hrml" id="download-template">
    <div class="control-group">
        <label class="control-label">
            <div data-content="label"></div>
            <div data-content="files"></div>
        </label>
    </div>
</script>
<script type="text/javascript">
    $('#migrate').migrate({
        partitionTemplate:'partition-template',
        downloadTemplate: 'download-template'
    });
</script>
</body>
</html>