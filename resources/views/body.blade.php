<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Ralali Bootcamp</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Ubuntu:500&display=swap" rel="stylesheet">

  <!-- Styles -->
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <style>
    .card {
      border-radius: 15px;
      background-color: #D8690A;
      box-shadow: 0 4px 8px 0 rgba(100, 0, 0, 0.5);
      padding: 20px;
      max-width: 300px;
      margin: 0 auto;
      text-align: center;
    }
    
    .text {
      color: white;
      font-family: Raleway;
    }

    button:hover, a:hover {
      opacity: 0.7; 
    }

    button {
      border-radius: 20px;
      margin-top: 5px;
      border: none;
      outline: 0;
      display: inline-block;
      padding: 8px;
      color: #D8690A;
      background-color: white;
      text-align: center;
      cursor: pointer;
      width: 100%;
      font-size: 18px;
      font-family: Ubuntu;
    }
  </style>
</head>

<body>
  <div class="card">
      <h4 class="text">Welcome To</h4>
      <h1 class="text">Ralali Internship Git Flow Session</h1>
      <button>login</button>
      <button>register</button>
  </div>
  @yield('content')
</body>
</html>