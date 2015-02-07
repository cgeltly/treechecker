
<div class="jumbotron">
    
<p>
        
{{ HTML::image('images/treechecker_logo.png','Logo', ['align'=>"left", 'width'=>"260"]) }} 
<h1>TreeChecker</h1>
<br clear="left">
<h2>Error recognition for genealogical trees</h2>
</p>        
<br>
<p>
    This application is being developed to improve the quality of genealogical data
worldwide. It allows you to upload your GEDCOM family tree file and to check for 
irregularities in the dates, events, relationships and coding of the file.
The aim is to enable people to correct errors in their files and bring family tree
research up to a higher standard.
</p> 
<br>
<p>
{{HTML::link('users/dashboard', 'Register »', array('class' => 'btn btn-primary btn-lg', 'role' => 'button')) }}
{{HTML::link('users/dashboard', 'Login »', array('class' => 'btn btn-success btn-lg', 'role' => 'button')) }}
</p>
</div>