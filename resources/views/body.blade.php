<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Ralali Bootcamp 123</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

  <!-- Styles -->
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
  <header class="site-header">
    <h2>R a l a l i   B o o t  c a m p</h2>
    <form class="search-bar">
      <input type="text" name="keyword">
      <button type="submit">Cari</button>
    </form>
  </header>

  @yield('content')
</body>

</html>