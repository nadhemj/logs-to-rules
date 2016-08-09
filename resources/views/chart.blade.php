@extends('layout')
@section('title')
    APG
@stop
@section('content')
    {{--CHART--}}
    <div id="holder"></div>
    {{--BUTTONS--}}
    <button id="to_main">Back to upload form</button>
    <button id="to_chart">Back to chart</button>
    <button id="download_raw">Get raw current rules set file</button>
    <button id="download">Get human-readable current rules set file</button>
    {{--TABLE HEADERS--}}
    <div class="table__first-column column header">Rules</div>
    {{--<div class="table__second-column column header">Rule ID</div>--}}
    <div class="table__third-column column header">Source IP</div>
    <div class="table__fourth-column column header">Destination IP</div>
    <div class="table__fifth-column column header">Port</div>
    <div class="table__sixth-column column header">Protocol</div>
    <div class="table__seventh-column column header">Hits</div>
    <div class="table__eighth-column  column header">Permissiveness</div>
    {{--TABLE CONTENTS--}}
    <div class="table">
        <div class="table__first-column content">
            <div id="tree_table"></div>
        </div>
    </div>
    {{--PRELOADER--}}
    <div id="circleG">
        <div id="circleG_1" class="circleG"></div>
        <div id="circleG_2" class="circleG"></div>
        <div id="circleG_3" class="circleG"></div>
    </div>
@stop
@section('script')
    <script src="js/raphael.min.js"></script>
    <script src="js/jstree.min.js"></script>
    <script src="js/results.js"></script>
@stop
