{{-- <!DOCTYPE html>
<html>
<head>
<title>Page Title</title>
</head>
<body>

<h1>This is a Heading</h1>
<p>This is a paragraph.</p>

</body>
</html>--}}
<!doctype html>
<html lang="{{ app()->getLocale() }}">

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
  <header class="site-header" style="background-color:orangeRed;color:white;padding:20px;">
    <h2>Simplify your links!</h2>
    <form class="search-bar">
      <input type="url" name="originalURL" placeholder="Your original URL here">
      <button type="submit">SHORTEN URL</button>
    </form>
  </header>

  @yield('content')
</body>

</html>