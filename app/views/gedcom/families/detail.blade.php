
<div class="page-header">
<h3>
@lang('gedcom/families/title.show_family')
</h3>
</div>
<table class="table table-striped">
<tr>
<th>@lang('gedcom/gedcoms/table.file_name')</th>
<td>{{{ $family->gc->file_name }}}</td>
</tr>
<tr>
<th>@lang('gedcom/gedcoms/table.key')</th>
<td>{{{ $family->gedcom_key }}}</td>
</tr>
<tr>
<th>@lang('gedcom/families/table.husband')</th>
<td>{{ $husband ? HTML::link("individuals/show/" . $husband->id,
$husband->first_name . ' ' . $husband->last_name) .
' (' . $husband->gedcom_key . ')' : '' }}</td>
</tr>
<tr>
<th>@lang('gedcom/families/table.wife')</th>
<td>{{ $wife ? HTML::link("individuals/show/" . $wife->id,
$wife->first_name . ' ' . $wife->last_name) .
' (' . $wife->gedcom_key . ')' : '' }}</td>
</tr>
<tr>
<th>@lang('gedcom/families/table.children')</th>
<td>{{{ $family->children()->count() }}}</td>
</tr>
@foreach ($family->children as $child)
<tr>
<th></th>
<td>{{ HTML::link("individuals/show/" . $child->id,
$child->first_name . ' ' . $child->last_name) .
' (' . $child->gedcom_key . ')' }}</td>
</tr>
@endforeach
</table>
<div class="page-header">
<h4>
@lang('gedcom/events/table.events')
</h4>
</div>
<table id="family_events" class="table table-striped table-hover">
<thead>
<tr>
<th>@lang('gedcom/events/table.event')</th>
<th>@lang('common/common.date')</th>
<th>@lang('common/common.place')</th>
</tr>
</thead>
</table>
@include('layouts.table', array('ajax_source' => 'families/events/' . $family->id, 'id' => 'family_events'))