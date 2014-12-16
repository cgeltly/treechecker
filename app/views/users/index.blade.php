
<div class="page-header">
    <h3>
        @lang('users/title.user_management')
    </h3>
</div>

<table id="users" class="table table-striped table-hover">
    <thead>
        <tr>
            <th>@lang('common/common.first_name')</th>
            <th>@lang('common/common.last_name')</th>
            <th>@lang('common/common.email')</th>
            <th>@lang('common/common.role')</th>
        </tr>
    </thead>
</table>

@include('layouts.table', array('ajax_source' => 'users/data', 'id' => 'users'))
