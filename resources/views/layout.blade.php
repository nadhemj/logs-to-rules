<!DOCTYPE html>
<html>
<link href="css/preloader.css" rel="stylesheet" type="text/css" >
<link href="css/app.css" rel="stylesheet" type="text/css" >
<link href="css/themes/default/style.min.css" rel="stylesheet" type="text/css" >
@yield('style')
<head>
    <title>@yield('title')</title>
</head>
<body class="container" id="generator">
    @yield('content')
</body>
<script src="js/jquery-3.1.0.min.js"></script>
@yield('script')
</html>
