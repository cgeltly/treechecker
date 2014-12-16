<div class="page-header">
    <h2>@lang('users/title.login')</h2>
</div>

@if(Session::has('error'))
<div class="alert alert-warning" role="alert">{{ Session::get('error') }}</div>
@endif

{{ Form::open(array('url' => 'users/signin', 'class' => 'form-inline', 'role' => 'form')) }}
<div class="form-group">
    {{ Form::label('email', Lang::get('common/common.email'), array('class' => 'control_label')) }}
    {{ Form::email('email', null, array('class' => 'form-control')) }}
</div>
<div class="form-group">
    {{ Form::label('password', Lang::get('common/common.password'), array('class' => 'control_label')) }}
    {{ Form::password('password', array('class' => 'form-control')) }}
</div>
{{ Form::submit(Lang::get('users/title.login'), array('class' => 'btn btn-default'))}}
{{ Form::close() }}