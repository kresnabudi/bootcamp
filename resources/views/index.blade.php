<!doctype html>
<html lang="{{ app()->getLocale() }}">
@extends('head')

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Ralali Bootcamp</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

  <!-- Styles -->
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
  <header class="site-header">
    <h1>Ralali Bootcamp</h1>
    <h2>Git Flow</h2>
    <form class="search-bar">
      <input type="text" name="keyword">
      <button type="submit">Cari</button>
    </form>
  </header>

  	@extends('body')
  	@yield('content')
	@section('footer')
	@endsection
</body>
</html>