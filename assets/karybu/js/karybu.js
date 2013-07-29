(function($){
    $.widget("karybu.migrate", {
        options:{
            partitionTemplate:''
        },
        _create: function() {
            var that = this;
            $('#source_type').on('change', function(){
                that.setSource($(this).val());
            });
            $('#source_type').change();
            $('#app-continue').on('click', function(){
                if (!$('#source_type').val()) {
                    alert('Select application type');
                }
                else if (!$('#path').val()) {
                    alert('Fill in the application path')
                }
                else{
                    $.ajax({
                        type: "POST",
                        url: 'service.php',
                        data: 'action=source' + '&' + $('#select-source :input, #select-source select').serialize(),
                        success: function(data){
                            data = eval('(' + data +')');
                            if (data.error == 1) {
                                alert(data.message);
                            }
                            else {
                                $('#entities').html('');
                                that.show($('#download'));
                                that.show($('#entities'));
                                var fileHtml = ''
                                for (var i in data.entities) {
                                    var obj = data.entities[i];
                                    obj.input = '<input type="hidden" value="' + obj.count +'" name="count" />' +
                                        '<input type="text" name="' + obj.entity +'" value="1" />';
                                    var d = $("<div></div>").loadTemplate($('#' + that.options.partitionTemplate), obj);
                                    $('#entities').append(d);
                                    //download
                                    that.setDownloadSources(obj.entity, obj.count, 1);
                                    d.find('button.update-partition').bind('click', function(){
                                        var count = $(this).parent().find('input[name="count"]').val();
                                        var input = $(this).parent().find('input[name!="count"]');
                                        that.setDownloadSources(input.attr('name'), count, input.val());
                                    });
                                }
                            }
                        }
                    });
                }
            });
        },
        setSource: function(source){
            if (source) {
                this.show($('#path_holder'));
            }
            else {
                this.hide($('#path_holder'));
            }
        },
        setDownloadSources: function(type, total, chunks) {
            var html = '';
            if (isNaN(chunks) || chunks < 1){
                chunks = 1;
            }
            var chunkSize = Math.ceil(total/chunks);
            for (var i=1; i <= chunks; i++){
                var start = (i-1) * chunkSize;
                if (start < total) {
                    var filename = type + '.' + this.strpad(i, '0', 6) + '.xml';
                    var link = 'service.php?action=export&path=' + encodeURIComponent($('#path').val()) + '&source_type=' + $('#source_type').val() + '&filename=' + filename + '&start=' + start + '&limit=' + chunkSize + '&type=' + type;
                    html += '<a href="' + link +'">' + filename + '</a><br />';
                }
            }
            var data = {};
            data.label = type;
            data.files = html;
            var append = false;
            if ($('#download-' + type).length == 0){
                append = true;
            }
            var f = $('<div id="download-' + type +'"></div>').loadTemplate($('#' + this.options.downloadTemplate), data);
            if (append){
                $('#download').append(f);
            }
            else{
                $('#download-' + type).replaceWith(f);
            }
        },
        strpad: function(val, pad, length){
            while (val.toString().length < length){
                val = pad + '' + val;
            }
            return val;
        },
        show: function(element){
            $(element).slideDown(300);
        },
        hide: function(element){
            $(element).slideUp(300);
        }
    });
})(jQuery)