
<div class="page-header">
    <h3>
        @lang('gedcom/individuals/title.show_individual', 
        array('key' => $individual->gedcom_key, 
        'first' => $individual->first_name, 
        'last' => $individual->last_name))
    </h3>
</div>

@if ($individual->private)
<div class="alert alert-warning" role="alert">
    @lang('gedcom/individuals/messages.is_marked_private')
</div>
{{ HTML::linkAction('IndividualsController@getMarkPublic', 
        Lang::get('gedcom/individuals/messages.mark_public'), 
        array($individual->id), 
        array('class' => 'btn btn-primary pull-right', 'role' => 'button')) }}
@else
{{ HTML::linkAction('IndividualsController@getMarkPrivate', 
        Lang::get('gedcom/individuals/messages.mark_private'), 
        array($individual->id), 
        array('class' => 'btn btn-primary pull-right', 'role' => 'button')) }}
@endif

<script>
    $(function () {
        $("#tabs").tabs();
    });
</script>

<div id="content">
    <ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
        <li class="active">
            <a href="#details" data-toggle="tab">@lang('common/common.details')</a>
        </li>
        <li>
            <a href="#ancestors" data-toggle="tab">@lang('gedcom/individuals/title.ancestors')</a>
        </li>
        <li>
            <a href="#descendants" data-toggle="tab">@lang('gedcom/individuals/title.descendants')</a>
        </li>
        <li>
            <a href="#families" data-toggle="tab">@lang('gedcom/families/table.families')</a>
        </li>
        <li>
            <a href="#events" data-toggle="tab">@lang('gedcom/events/table.events')</a>
        </li>
        <li>
            <a href="#errors" data-toggle="tab">@lang('gedcom/errors/table.errors')</a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="details">
            <table class="table table-striped">
                <tr>
                    <th>@lang('gedcom/gedcoms/table.key')</th>
                    <td>{{{ $individual->gedcom_key }}}</td>
                </tr>     
                <tr>
                    <th>@lang('common/common.first_name')</th>
                    <td>{{{ $individual->first_name }}}</td>
                </tr>
                <tr>
                    <th>@lang('common/common.last_name')</th>
                    <td>{{{ $individual->last_name }}}</td>
                </tr>
                <tr>
                    <th>@lang('common/common.sex')</th>
                    <td>{{{ $individual->sex }}}</td>
                </tr>
                @if ($individual->age())
                <tr>
                    <th>@lang('gedcom/individuals/table.age_death')</th>
                    <td>{{{ $individual->age() }}}</td>
                </tr>
                @endif
            </table>
        </div>
        <div class="tab-pane" id="ancestors">
        </div>
        <div class="tab-pane" id="descendants">
        </div>
        <div class="tab-pane" id="families">
            @include('gedcom.individuals.families')
        </div>
        <div class="tab-pane" id="events">
            <table id="individual_events" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>@lang('gedcom/events/table.event')</th>
                        <th>@lang('common/common.date')</th>
                        <th>@lang('common/common.place')</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="tab-pane" id="errors">
            <table id="individual_errors" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>@lang('gedcom/errors/table.type_broad')</th>
                        <th>@lang('gedcom/errors/table.eval_broad')</th>
                        <th>@lang('gedcom/errors/table.error')</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@include('layouts.table', array('ajax_source' => 'individuals/events/' . $individual->id, 'id' => 'individual_events'))
@include('layouts.table', array('ajax_source' => 'individuals/errors/' . $individual->id, 'id' => 'individual_errors'))
@include('gedcom.individuals.ancestors', array('tree' => 'individuals/ancestors/' . $individual->id))
@include('gedcom.individuals.descendants', array('tree' => 'individuals/descendants/' . $individual->id))
