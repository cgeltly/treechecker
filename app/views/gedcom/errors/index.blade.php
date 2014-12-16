
<div class="page-header">
    <h3>
        @lang('gedcom/errors/title.errors_management')
    </h3>
</div>

<table id="errors" class="table table-striped table-hover">
    <thead>
        <tr>
            <th>@lang('gedcom/gedcoms/table.file_name')</th>
            <th>@lang('gedcom/individuals/table.individual')</th>
            <th>@lang('gedcom/families/table.family')</th>
            <th>@lang('gedcom/errors/table.classification')</th>
            <th>@lang('gedcom/errors/table.severity')</th>
            <th>@lang('gedcom/errors/table.message')</th>
        </tr>
    </thead>
</table>

@include('layouts.table', array('ajax_source' => 'errors/data', 'id' => 'errors'))

