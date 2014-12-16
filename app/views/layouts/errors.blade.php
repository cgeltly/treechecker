@if ($errors->any())
<div class="panel panel-danger">
    <div class="panel-heading">Please check your input:</div>
    <div class="panel-body">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif
