$(document).ready(function(){
    $('#jobSummaryPanel').togglePanel();
    
    $('.field-editable').editField();
    
    $('form#top_form').remove();
});

(function($) {
    $.editField = function(obj) {
        var edit_btn = $(obj).find('.field-edit');
        var f_val = obj.find('.field-val');
        var f_btns = obj.find('.field-btns');
        var f_input = obj.find('.field-input');
        
        function hideField() {
            f_btns.addClass('hidden');
            f_input.addClass('hidden');
            if (obj.hasClass('field-calendar') || obj.hasClass('field-status')) {
                obj.find('.sub-field-group').addClass('hidden');
            }
            
            f_val.removeClass('hidden');
        }
        
        function showField() {
            f_val.addClass('hidden');
            
            f_btns.removeClass('hidden');
            f_input.removeClass('hidden');
            if (obj.hasClass('field-calendar') || obj.hasClass('field-status')) {
                obj.find('.sub-field-group').removeClass('hidden');
            }
        }
        
        function changeFieldVal(txt, val) {
            f_val.find('.curr-val').html(val);
            if (obj.hasClass('field-status')) {
                obj.find('.curr-txt').html(txt);
            } else {
                f_val.find('.curr-txt').html(txt);
            }
            hideField();
        }
        
        function changeCurrVal(val) {
            f_val.find('.curr-val').html(val);
            hideField();
        }
        
        function refreshAuditTable() {
            window['jobModules'].setRefreshModule('Audit');
            var tab = $('.dynamic-tab-pane-control#Something > .tab-row > h2.tab.selected');
            if (tab.find('a').html() == 'Audit') {
                tab.trigger('click');
            }
        }
        
        function updateJob(data, callback) {
            var new_val = null;
            var old_val = null;
            
            if (typeof data.value != 'undefined') {
                new_val = data.value;
            }
            if (typeof data.assigned != 'undefined') {
                new_val = data.assigned;
            }
            if (typeof data.new_id != 'undefined') {
                new_val = data.new_id;
            }
            if (typeof data.new_value != 'undefined') {
                new_val = data.new_value;
            }
            if (typeof data.next_value != 'undefined') {
                new_val = data.next_value;
            }
            
            if (typeof data.old_val != 'undefined') {
                old_val = data.old_val;
            }
            if (typeof data.current_value != 'undefined') {
                old_val = data.current_value;
            }
            
            if (old_val == null || old_val.length < 1 || new_val == null || new_val.length < 1) {
                alert('Please enter a value before saving.');

                return false;
            } else {
                if (old_val == new_val) {
                    alert('Please enter a new value before saving.');

                    return false;
                } else {
                    $.ajax({
                        url  : 'index2.php',
                        type : 'POST',
                        data : data,
                        async: true,
                        cache: true,
                        success: function(result) {
                            if (result == 0) {
                                alert('An error has occured, and claim imformation has not been updated.');

                                return false;
                            } else {
                                refreshAuditTable();

                                eval(callback);

                                return true;
                            }
                        }
                    });
                }
            }
        }
        
        function isDMYDate(date_string) {
            var pat = /[0-9]{2}\/[0-9]{2}\/[0-9]{4}/;
            return pat.test(date_string);
        }
        
        function dateConvert(date) {
            var d = date.split('/');
            return d[2]+''+d[1]+''+d[0];
        }
        
        function initDatepicker() {
            if (obj.hasClass('field-calendar')) {
                var str_date;
                
                $('.datepicker-container').datepicker({
                    changeMonth: true,
                    changeYear : true,
                    dateFormat : 'dd/mm/yy',
                    onSelect   : function(val, sel_obj){
                        var par_obj = $('#'+sel_obj.id).closest('.field-calendar');
                        var str_date = par_obj.find('.curr-val').html().trim();
                        if (isDMYDate(str_date)) {
                            par_obj.find('.additional-info').addClass('hidden');
                            if (dateConvert(val) > dateConvert(str_date)) {
                                par_obj.find('.additional-info').removeClass('hidden');
                            }
                        }
                    }
                });
                
                obj.find('.datepicker-container').datepicker('setDate', f_val.find('.curr-val').html().trim());
                
                if (obj.attr('data-type') == 'start') {
                    str_date = $('.field-calendar[data-type="complete"]').find('.curr-val').html().trim();
                    if (isDMYDate(str_date)) {
                        obj.find('.datepicker-container').datepicker('option', 'maxDate', str_date);
                    }
                } else if (obj.attr('data-type') == 'complete') {
                    str_date = $('.field-calendar[data-type="start"]').find('.curr-val').html().trim();
                    if (isDMYDate(str_date)) {
                        obj.find('.datepicker-container').datepicker('option', 'minDate', str_date);
                    }
                }
            }
        }
        
        function init() {
            if (navigator.appVersion.indexOf("Win")!=-1) {
                obj.addClass('win-size');
            }
        }
        
        init();
        
        initDatepicker();
        
        edit_btn.click(function(){
            showField();
        });
        
        f_btns.find('.btn[data-action="cancel"]').click(function(){
            hideField();
        });
        
        f_btns.find('.btn[data-action="save"]').click(function(){
            var type = obj.attr('data-type');
            var jid  = obj.attr('data-jid');
            var data = {};
            var input_type = '';
            var txt = '';
            
            switch (type) {
                case 'jobno':
                case 'e_code':
                    if (type == 'e_code') {
                        input_type = 'select';
                    } else {
                        input_type = 'text';
                    }
                    data = {
                        option : 'com_jobsystem',
                        task   : 'update_claim_data',
                        jid    : jid,
                        field  : type,
                        value  : f_input.val(),
                        old_val: f_val.find('.curr-val').html().trim(),
                        Itemid : 46,
                        no_html: 1,
                        ajax   : 1
                    }
                    
                    break;
                    
                case 'assigned':
                case 'estimator':
                case 'case_manager':
                    input_type = 'select';
                    data = {
                        option  : 'com_jobsystem',
                        task    : 'save_assignee_ajax',
                        jid     : jid,
                        type    : type,
                        assigned: f_input.val(),
                        old_val : f_val.find('.curr-val').html().trim(),
                        Itemid  : 46,
                        no_html : 1,
                        ajax    : 1
                    }
                    break;
                case 'start':
                case 'complete':
                    input_type = 'calendar';
                    data = {
                        option   : 'com_jobsystem',
                        task     : 'save_date_change',
                        jid      : jid,
                        col_name : type,
                        new_value: f_input.val(),
                        old_val  : f_val.find('.curr-val').html().trim(),
                        reason   : obj.find('select.sub-field-input option:selected').map(function(){ return this.text }).get().join(','),
                        details  : obj.find('textarea.sub-field-input').val(),
                        Itemid   : 46,
                        no_html  : 1,
                        ajax     : 1
                    }
                    break;
                case 'status':
                    input_type = 'select';
                    data = {
                        option       : 'com_jobsystem',
                        task         : 'change_manager',
                        act          : 'save_building_property',
                        jid          : jid,
                        property     : type,
                        next_value   : f_input.val(),
                        current_value: f_val.find('.curr-val').html().trim(),
                        dUserList    : obj.find('select.sub-field-input').val(),
                        Itemid       : 46,
                        no_html      : 1,
                        ajax         : 1
                        
                    }
                    break;
                case 'business_line':
                    input_type = 'select';
                    data = {
                        option : 'com_jobsystem',
                        task   : 'business_line_functions',
                        act    : 'save_business_line',
                        new_id : f_input.val(),
                        old_val: f_val.find('.curr-val').html().trim(),
                        jid    : jid,
                        Itemid : 46,
                        no_html: 1,
                        ajax   : 1
                        
                    }
                    break;
            }

            if (input_type == 'select') {
                txt = f_input.find('option:selected').text().trim();
                updateJob(data, 'changeFieldVal("'+txt+'","'+f_input.val()+'")');
            } else {
                updateJob(data, 'changeCurrVal("'+f_input.val()+'")');
            }
        });
    }
    
    $.fn.editField = function(options) {
        this.each(function() {
            if (undefined == $(this).data('editField')) {
                var plugin = new $.editField($(this), options);
                $(this).data('editField', plugin);
            }
        });
    };
}(jQuery));