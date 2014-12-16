
<div class="page-header">
    <h3>
        {{ $title }}
    </h3>
</div>

<table id="families" class="table table-striped table-hover">
    <thead>
        <tr>
            @if($source == 'families/data')
                <th>@lang('gedcom/gedcoms/table.file_name')</th>
            @endif
            <th>@lang('gedcom/gedcoms/table.key')</th>
            <th>@lang('gedcom/families/table.husband')</th>
            <th>@lang('gedcom/families/table.wife')</th>
        </tr>
    </thead>
</table>

@include('layouts.table', array('ajax_source' => $source, 'id' => 'families'))
