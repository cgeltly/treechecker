
<div class="page-header">
    <h3>
        {{ $title }}
    </h3>
</div>

<table id="individuals" class="table table-striped table-hover">
    <thead>
        <tr>
            @if($source == 'individuals/data')
                <th>@lang('gedcom/gedcoms/table.file_name')</th>
            @endif
            <th>@lang('gedcom/gedcoms/table.key')</th>
            <th>@lang('common/common.first_name')</th>
            <th>@lang('common/common.last_name')</th>
            <th>@lang('common/common.sex')</th>
        </tr>
    </thead>
</table>

@include('layouts.table', array('ajax_source' => $source, 'id' => 'individuals'))

