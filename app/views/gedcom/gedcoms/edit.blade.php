<div class="page-header">
    <h2>@lang('gedcom/gedcoms/title.edit')</h2>
</div>

{{ Form::model($gedcom, array('method' => 'POST', 'action' => array('GedcomsController@postUpdate', $gedcom->id))) }}

@include('layouts.errors')

<div class="form-group">
    {{ Form::label('tree_name', Lang::get('gedcom/gedcoms/table.tree_name'), array('class' => 'control_label')) }}
    {{ Form::text('tree_name', Input::old('tree_name'), array('class' => 'form-control')) }}
</div>
<div class="form-group">
    {{ Form::label('source', Lang::get('gedcom/gedcoms/table.source'), array('class' => 'control_label')) }}
    {{ Form::text('source', Input::old('source'), array('class' => 'form-control')) }}
</div>
<div class="form-group">
    {{ Form::label('notes', Lang::get('gedcom/gedcoms/table.notes'), array('class' => 'control_label')) }}
    {{ Form::textarea('notes', Input::old('notes'), array('style' => 'height: 90px', 'class' => 'form-control')) }}
</div>

{{ Form::submit(Lang::get('common/form.save'), array('class' => 'btn btn-primary')) }}
{{ Form::reset(Lang::get('common/form.reset'), array('class' => 'btn btn-default')) }}
{{ Form::close() }}
