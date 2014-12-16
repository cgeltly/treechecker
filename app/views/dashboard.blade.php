<div class="jumbotron">
<h1>Dashboard</h1>
@if ($gedcom_count == 0)
<p>
Looks like you have just started! Your first step would be to upload your GEDCOM file.
</p>
@else
<p>
Welcome back! You have currently uploaded {{$user->gedcoms()->count()}} file(s).
</p>
@endif
<p>
{{HTML::link('gedcoms/upload', 'Upload a new file »', array('class' => 'btn btn-primary btn-lg', 'role' => 'button')) }}
@if ($gedcom_count > 0)
{{HTML::link('gedcoms', 'View uploaded files »', array('class' => 'btn btn-success btn-lg', 'role' => 'button')) }}
@endif
</p>
</div>