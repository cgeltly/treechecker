@if(!$individual->families->isEmpty())
<h4>As child</h4>
<ul> 
    @foreach($individual->families AS $family)
    <li>{{ HTML::link("families/show/" . $family->id, $family->gedcom_key) }}</li>
    @endforeach
</ul>
@endif
@if(!$individual->familiesAsHusband->isEmpty())
<h4>As husband</h4>
<ul> 
    @foreach($individual->familiesAsHusband AS $family)
    <li>{{ HTML::link("families/show/" . $family->id, $family->gedcom_key) }}</li>
    @endforeach
</ul>
@endif
@if(!$individual->familiesAsWife->isEmpty())
<h4>As wife</h4>
<ul> 
    @foreach($individual->familiesAsWife AS $family)
    <li>{{ HTML::link("families/show/" . $family->id, $family->gedcom_key) }}</li>
    @endforeach
</ul>
@endif
