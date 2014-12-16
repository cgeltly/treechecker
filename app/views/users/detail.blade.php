
<div class="page-header">
    <h3>
        @lang('users/title.user')
    </h3>
</div>    

<table class="table table-striped">
    <tr>
        <th>@lang('common/common.first_name')</th>
        <td>{{{ $user->first_name }}}</td>
    </tr>
    <tr>
        <th>@lang('common/common.last_name')</th>
        <td>{{{ $user->last_name }}}</td>
    </tr>
    <tr>
        <th>@lang('common/common.email')</th>
        <td>{{ HTML::mailto($user->email) }}
        </td>
    </tr>
</table>