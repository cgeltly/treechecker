
<div class="page-header">
    <h3>
        @lang('gedcom/gedcoms/title.file_stats')
    </h3>
</div>    

<table class="table table-striped">
    <tr>
        <th>@lang('gedcom/gedcoms/table.file_name')</th>
        <td>{{{ $gedcom->file_name }}}</td>
    </tr>
    <tr>
        <th>@lang('gedcom/individuals/table.individuals')</th>
        <td>{{{ $statistics['all_ind'] }}} ({{ HTML::link('gedcoms/individuals/' . $gedcom->id, 'show') }})</td>
    </tr>
    <tr>
        <th>Males and females</th>
        <td>Males: {{{ $statistics['males'] }}}, 
            Females: {{{ $statistics['females'] }}}, 
            Unknowns: {{{ $statistics['unknowns'] }}}
        </td>
    </tr>
    <tr>
        <th>Sex ratio</th>
        <td>
            {{{ $statistics['sex_ratio'] }}}
        </td>
    </tr>
    <tr>
        <th>Birth dates</th>
        <td>Earliest: {{{ $statistics['min_birth'] }}}, 
            Latest: {{{ $statistics['max_birth'] }}}
        </td>
    </tr>
    <tr>
        <th>Death dates</th>
        <td>Earliest: {{{ $statistics['min_death'] }}}, 
            Latest: {{{ $statistics['max_death'] }}}
        </td>
    </tr>
    <tr>
        <th>Ages at death</th>
        <td>Average: {{{ $statistics['avg_age'] }}}, 
            Oldest: {{{ $statistics['max_age'] }}},
            Youngest: {{{ $statistics['min_age'] }}}
        </td>
    </tr>
    <tr>
        <th>@lang('gedcom/families/table.families')</th>
        <td>{{{ $statistics['all_fami'] }}} ({{ HTML::link('gedcoms/families/' . $gedcom->id, 'show') }})</td>
    </tr>
    <tr>
        <th>Families with children</th>
        <td>{{{ $statistics['fams_with_children'] }}} </td>
    </tr>
        <tr>
        <th>Average number of children per family</th>
        <td>{{{ $statistics['avg_fam_size'] }}} 
            (Largest family: {{{ $statistics['max_fam_size'] }}} children)</td>
    </tr>
    <tr>
        <th>@lang('gedcom/events/table.events')</th>
        <td>Total: {{{ $statistics['total_events'] }}}, 
            Related to individuals: {{{ $statistics['indi_events'] }}}, 
            Related to families: {{{ $statistics['fami_events'] }}}
        </td>
    </tr>
</table>
