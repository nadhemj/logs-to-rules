@extends('layout')
@section('title')
    APG
@stop
@section('content')
    <div class="content" id="form">
        <form action="/upload" method="post" enctype="multipart/form-data">
            <input type="file" name="file">
            <input type="submit" value="Upload File to Server">
        </form>
    </div>
    {{--PRELOADER--}}
    <div id="circleG">
        <div id="circleG_1" class="circleG"></div>
        <div id="circleG_2" class="circleG"></div>
        <div id="circleG_3" class="circleG"></div>
    </div>
    <div id="success">
        <p>Log file processed.</p>
        <button id="results">See the results</button>
    </div>
@stop
@section('script')
    <script src="js/jquery.form.min.js"></script>
    <script src="js/app.js"></script>
@stop

