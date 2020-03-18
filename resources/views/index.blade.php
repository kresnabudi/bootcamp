@extends('headear')

<body>
  <header class="site-header">
    <h1>Ralali Bootcamp</h1>
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