
@if ($gedcom->error_checked)
<div class="alert alert-success" role="alert">
    You ran the error check successfully, below are the results. Do you want to check again? 
    {{ HTML::link('check/start/' . $gedcom->id, 'Click here', array('class' => 'alert-link')) }}
</div>
@endif

<div class="page-header">
    <h3>
        Errors in GEDCOM {{{ $gedcom->file_name }}}
    </h3>
</div>

<h4>Errors with tree content</h4>
@if (!$errors->isEmpty())
<ul class="list-group">
    @foreach ($errors as $e)
    <li class="list-group-item list-group-item-{{{ $e->eval_broad === 'error' ? 'danger' : 'warning' }}}">
        {{{ $e->message }}}
        @if ($e->indi_id)
        {{ HTML::link('individuals/show/' . $e->indi_id, 'View individual', array('class' => 'badge')) }}
        @endif
        @if ($e->fami_id)
        {{ HTML::link('families/show/' . $e->fami_id, 'View family', array('class' => 'badge')) }}
        @endif
    </li>
    @endforeach
</ul>
@elseif(!$gedcom->error_checked)
<div class="alert alert-warning" role="alert">
    You have not yet run the error check. Do you want to do that now?
    {{ HTML::link('check/start/' . $gedcom->id, 'Click here', array('class' => 'alert-link')) }}
</div>
@else
<div class="alert alert-success" role="alert">
    No errors found during checking. Good stuff!
</div>
@endif

<h4>Errors during parsing</h4>
@if (!$parse_errors->isEmpty())
<ul class="list-group">
    @foreach ($parse_errors as $e)
    <li class="list-group-item list-group-item-{{{ $e->eval_broad === 'error' ? 'danger' : 'warning' }}}">
        {{{ $e->message }}}
        @if ($e->indi_id)
        {{ HTML::link('individuals/show/' . $e->indi_id, 'View individual', array('class' => 'badge')) }}
        @endif
        @if ($e->fami_id)
        {{ HTML::link('families/show/' . $e->fami_id, 'View family', array('class' => 'badge')) }}
        @endif
    </li>
    @endforeach
</ul>
@else 
<div class="alert alert-success" role="alert">
    No errors during parsing.
</div>
@endif
