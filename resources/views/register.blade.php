<div class="auth">
    <div>
        <h1>Register</h1>
        {{-- @if (count($errors) > 0)
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif --}}
        <div class="alert alert-danger">
            <ul>
                <li>Error 1</li>
                <li>Error 2</li>
                <li>Error 3</li>
            </ul>
        </div>
        <form class="register" action="/register" method="post">
            {{ csrf_field() }}
            <input class="email" type="email" name="email" placeholder="Email">
            <input class="username" type="text" name="username" placeholder="Username">
            <input class="password" type="password" name="password" placeholder="Password">
            <input class="confirmation" type="password" name="confirmation" placeholder="Confirm Password">
            <input class="submit" type="submit" value="Register">
        </form>
    </div>
</div>

<style>
    .alert {
        padding: 0px;
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        margin-bottom: 8px;
    }

    .alert ul {
        margin: 8px;
        padding-left: 16px;
        padding-right: 16px;
    }

    .auth,
    .auth form {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .auth > div {
        width: 300px;
        max-width: 300px;
        margin-top: 50px;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 1px 1px 10px 2px rgba(0, 0, 0, .2);
    }

    .auth form input:not([type=submit]) {
        width: 100%;
        margin-bottom: 8px;
        height: 36px;
        border-radius: 4px;
        padding: 8px;
        border: 1px solid #ddd;
    }

    .auth form input[type="submit"] {
        width: 100%;
        background: green;
        color: white;
        border-radius: 4px;
        padding: 12px 8px;
        border: 1px solid green;
        margin-top: 8px;
        cursor: pointer;
    }
</style>