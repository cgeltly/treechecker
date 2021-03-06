<!-- Navigation bar -->
<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            {{ HTML::link('home', Lang::get('common/common.home'), array('class' => 'navbar-brand')) }}
        </div>
        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                @if(!Auth::check())
                <li>{{ HTML::link('users/register', Lang::get('users/title.register')) }}</li>
                <li>{{ HTML::link('users/login', Lang::get('users/title.login')) }}</li>
                <li>{{ HTML::link('about', Lang::get('common/common.about')) }}</li>
                @else
                <li>{{ HTML::link('users/dashboard', 'Dashboard') }}</li>
                <li>{{ HTML::link('gedcoms/upload', Lang::get('gedcom/gedcoms/table.upload')) }}</li>
                <li>{{ HTML::link('gedcoms', Lang::get('gedcom/gedcoms/table.files')) }}</li>
                <li>{{ HTML::link('individuals', Lang::get('gedcom/individuals/table.individuals')) }}</li>
                <li>{{ HTML::link('families', Lang::get('gedcom/families/table.families')) }}</li>
                <li>{{ HTML::link('events', Lang::get('gedcom/events/table.events')) }}</li>
                <li>{{ HTML::link('errors', Lang::get('gedcom/errors/table.errors')) }}</li>
                <li>&nbsp;</li>
                <li>{{ HTML::link('about', Lang::get('common/common.about')) }}</li>
                <li>&nbsp;</li>
                <li>&nbsp;</li>
                <li>{{ HTML::link('users/logout', Lang::get('users/title.logout')) }}</li>
                @endif
            </ul>
        </div>
    </div>
</div>