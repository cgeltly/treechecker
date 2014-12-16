<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@lang('common/common.treechecker')</title>

        <!-- Bootstrap CSS (and Bootstrap for DataTables) -->
        {{ HTML::style('bootstrap/css/bootstrap.min.css') }}
        {{ HTML::style('bootstrap/css/bootstrap-theme.min.css') }}
        {{ HTML::style('//cdn.datatables.net/plug-ins/725b2a2115b/integration/bootstrap/3/dataTables.bootstrap.css') }}
    </head>

    <body role="document" style="padding-top: 70px;">
        <!-- jQuery/jQueryUI -->
        {{ HTML::script('//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js') }}
        {{ HTML::script('//code.jquery.com/ui/1.11.1/jquery-ui.js') }}
        
        <!-- D3 -->
        {{ HTML::script('//cdnjs.cloudflare.com/ajax/libs/d3/3.4.11/d3.min.js') }}
        
        <!-- DataTables -->
        {{ HTML::script('//cdn.datatables.net/1.10.2/js/jquery.dataTables.js') }}
        {{ HTML::script('//cdn.datatables.net/plug-ins/725b2a2115b/integration/bootstrap/3/dataTables.bootstrap.js') }}
        
        <!-- Google Charts --> 
        {{ HTML::script('//www.google.com/jsapi') }}
        
        <!-- Bootstrap JavaScript -->
        {{ HTML::script('bootstrap/js/bootstrap.min.js') }}

        @include('layouts.navbar')

        <!-- Content -->
        <div class="container" role="main">
            @if(Session::has('message'))
            <div class="alert alert-success" role="alert">{{ Session::get('message') }}</div>
            @endif

            {{ $content }} 
        </div>

    </body>
</html>