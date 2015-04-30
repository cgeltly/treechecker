
<script>
    function doPoll()
    {
        $.get("{{ URL::to('parse/progress') }}", function (data)
        {
            // Get the return value and set that in the progress bar
            value = $.parseJSON(data);
            $('.progress-bar').attr('aria-valuenow', value);
            $('.progress-bar').css('width', value + '%');
            $('.progress-bar').html(value + '%');
            if (value === 100)
            {
                // Parsing completed
                $('.parse-message').html("{{ Lang::get('gedcom/gedcoms/actions.parsing_completed') }}");
                setTimeout(function () {
                    location.reload();
                }, 2000);
            }
            else
            {
                // Re-poll after two seconds
                setTimeout(doPoll, 10000);
            }
        });
    }
    $(document).on('click', '.parse', function (event) {
        // Start parsing
        event.preventDefault();
        $.get(String($(this).attr('href')));
        // Show the progress bar
        $('.parse-progress').show();
        // Start polling the progress
        doPoll();
    });
</script>
<div class="page-header">
    <h3>
        @lang('gedcom/gedcoms/title.gedcom_management')
    </h3>
</div>
<div class="parse-progress" style="display: none;">
    <h4 class="parse-message">@lang('gedcom/gedcoms/actions.parsing_started')</h4>
    <div class="progress">
        <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100">
        </div>
    </div>
</div>
<table id="gedcoms" class="table table-striped table-hover">
    <thead>
        <tr>
            <th>@lang('gedcom/gedcoms/table.file_name')</th>
            <th>@lang('gedcom/gedcoms/table.tree_name')</th>
            <th>@lang('gedcom/gedcoms/table.source')</th>
            <th>@lang('gedcom/gedcoms/table.notes')</th>
            <th>@lang('common/common.actions')</th>
        </tr>
    </thead>
</table>
@include('layouts.table', array('ajax_source' => 'gedcoms/data', 'id' => 'gedcoms'))