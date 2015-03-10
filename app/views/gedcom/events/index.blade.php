
<div class="page-header">
    <h3>
        {{ $title }}
    </h3>
    <h4>
        {{ $count }} {{ $subtitle }}
    </h4>
</div>

<table id="events" class="table table-striped table-hover">
    <thead>
        <tr>
            <th>@lang('gedcom/individuals/table.individual')</th>
            <th>@lang('gedcom/families/table.family')</th>
            <th>@lang('gedcom/events/table.event')</th>
            <th>@lang('common/common.date')</th>
            <th>@lang('common/common.place')</th>
        </tr>
    </thead>
</table>

@include('layouts.table', array('ajax_source' => 'events/data', 'id' => 'events'))

