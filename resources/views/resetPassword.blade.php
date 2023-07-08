<!DOCTYPE html>
<html lang="en">
<head>
    <style> body {
        background-color: #30444F;
            margin: 0;
            padding: 0;
    }
    h1 {
        color: #E7D31F;
        font-family: "Poppins", sans-serif;
    }
    </style>
    <link href="/assets/font/Poppins/Poppins-Regular.ttf" rel="stylesheet">
    <title>Reset Password Page</title>
</head>
<body>
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;"></div>
    @if($errors->any())
        <ul style="text-align: center; color: red;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <h1 style="text-align: center;">Reset Password Page</h1>
    <form method="POST" style="text-align: center;">
        @csrf
        <input type="hidden" name="id" value="{{ $user[0]['id'] }}">
        <input type="password" name="password" placeholder="New Password">
        <br><br>
        <input type="password" name="password_confirmation" placeholder="Confirm Password">
        <br><br>
        <input type="submit">
    </form>
</body>
</html>

