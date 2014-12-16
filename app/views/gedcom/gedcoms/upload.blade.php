<div class="page-header">
    <h2>@lang('gedcom/gedcoms/title.upload')</h2>
</div>

{{ Form::open(array('method' => 'POST', 'action' => array('FileUploadsController@postUpload'), 'class' => 'form-horizontal', 'files' => true)) }}

@include('layouts.errors')

<div class="form-group">
    {{ Form::label('file', Lang::get('common/common.file'), array('class' => 'control_label')) }}
    {{ Form::file('file', null, array('class' => 'form-control')) }}
</div>
<div class="form-group">
    {{ Form::label('tree_name', Lang::get('gedcom/gedcoms/table.tree_name'), array('class' => 'control_label')) }}
    {{ Form::text('tree_name', null, array('class' => 'form-control')) }}
</div>
<div class="form-group">
    {{ Form::label('source', Lang::get('gedcom/gedcoms/table.source'), array('class' => 'control_label')) }}
    {{ Form::text('source', null, array('class' => 'form-control')) }}
</div>
<div class="form-group">
    {{ Form::label('notes', Lang::get('gedcom/gedcoms/table.notes'), array('class' => 'control_label')) }}
    {{ Form::textarea('notes', null, array('class' => 'form-control')) }}
</div>

{{ Form::submit(Lang::get('common/form.upload'), array('class' => 'btn btn-primary')) }}
{{ Form::reset(Lang::get('common/form.reset'), array('class' => 'btn btn-default')) }}
{{ Form::close() }}
