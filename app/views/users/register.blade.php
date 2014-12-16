<div class="page-header">
    <h2>@lang('users/title.register')</h2>
</div>

{{ Form::open(array('url' => 'users/create', 'class' => 'form-horizontal', 'role' => 'form')) }}

@include('layouts.errors')

<div class="form-group {{ $errors->has('first_name') ? 'has-error' : '' }}">
    {{ Form::label('first_name', Lang::get('common/common.first_name'), array('class' => 'control_label')) }}
    {{ Form::text('first_name', null, array('class' => 'form-control')) }}
</div>
<div class="form-group {{ $errors->has('last_name') ? 'has-error' : '' }}">
    {{ Form::label('last_name', Lang::get('common/common.last_name'), array('class' => 'control_label')) }}
    {{ Form::text('last_name', null, array('class' => 'form-control')) }}
</div>
<div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
    {{ Form::label('email', Lang::get('common/common.email'), array('class' => 'control_label')) }}
    {{ Form::email('email', null, array('class' => 'form-control')) }}
</div>
<div class="form-group {{ $errors->has('password') ? 'has-error' : '' }}">
    {{ Form::label('password', Lang::get('common/common.password'), array('class' => 'control_label')) }}
    {{ Form::password('password', array('class' => 'form-control')) }}
</div>
<div class="form-group {{ $errors->has('password_confirmation') ? 'has-error' : '' }}">
    {{ Form::label('password_confirmation', Lang::get('common/common.confirm_password'), array('class' => 'control_label')) }}
    {{ Form::password('password_confirmation', array('class' => 'form-control')) }}
</div>

{{ Form::submit(Lang::get('users/title.register'), array('class' => 'btn btn-default'))}}
{{ Form::close() }}
