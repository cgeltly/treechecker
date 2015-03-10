
<div class="page-header">
    <h3>
        {{ $title }}
    </h3>
    <h4>
        {{ $count }} {{ $subtitle }}
    </h4>
</div>

<table id="families" class="table table-striped table-hover">
    <thead>
        <tr>
            <th>@lang('gedcom/gedcoms/table.file_name')</th>
            <th>@lang('gedcom/gedcoms/table.key')</th>
            <th>@lang('gedcom/families/table.husband')</th>
            <th>@lang('gedcom/families/table.husb_name')</th>
            <th>@lang('gedcom/families/table.wife')</th>
            <th>@lang('gedcom/families/table.wife_name')</th>
        </tr>
    </thead>
</table>

@include('layouts.table', array('ajax_source' => $source, 'id' => 'families'))
